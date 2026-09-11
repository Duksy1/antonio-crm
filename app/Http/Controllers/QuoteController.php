<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Concerns\SortsAndPaginates;
use App\Http\Requests\QuoteRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\QuoteCalculator;
use App\Services\QuoteNumberGenerator;
use App\Support\Csv;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteController extends Controller
{
    use SortsAndPaginates;

    public function __construct(
        private readonly QuoteCalculator $calculator,
        private readonly QuoteNumberGenerator $numberGenerator,
    ) {}

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request, ['number', 'title', 'status', 'total', 'issue_date', 'valid_until']);
        $archived = $request->boolean('archived');

        $quotes = $this->filtered($request, $archived)
            ->with(['company', 'deal'])
            ->orderBy($sort, $direction)
            ->paginate($this->perPageFrom($request))
            ->withQueryString();

        return view('quotes.index', [
            'quotes' => $quotes,
            'archived' => $archived,
            'archivedCount' => Quote::onlyTrashed()->where('owner_id', $request->user()->id)->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function board(Request $request): View
    {
        $items = $this->filtered($request, false)->with(['company', 'deal'])->orderByDesc('issue_date')->get()->groupBy(fn ($item) => $item->status->value);

        return view('quotes.board', compact('items'));
    }

    public function create(Request $request): View
    {
        $quote = new Quote(['issue_date' => now(), 'valid_until' => now()->addDays(14), 'currency' => 'EUR', 'tax_percent' => 25, 'discount_percent' => 0]);
        if ($request->filled('deal')) {
            $deal = Deal::with(['company', 'contact'])->where('owner_id', $request->user()->id)->findOrFail($request->integer('deal'));
            $quote->fill(['deal_id' => $deal->id, 'company_id' => $deal->company_id, 'contact_id' => $deal->contact_id, 'title' => 'Ponuda — '.$deal->title, 'currency' => $deal->currency]);
        }

        return view('quotes.form', ['quote' => $quote, ...$this->lookups($request)]);
    }

    public function store(QuoteRequest $request): RedirectResponse
    {
        $quote = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $totals = $this->calculator->calculate($data['items'], $data['discount_percent'], $data['tax_percent']);
            unset($data['items']);
            $quote = $request->user()->quotes()->create([...$data, ...collect($totals)->except('items')->all(), 'number' => $this->numberGenerator->next()]);
            $quote->items()->createMany($totals['items']);

            return $quote;
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Ponuda je uspješno izrađena.');
    }

    public function show(Request $request, Quote $quote): View
    {
        $this->assertOwner($request, $quote);
        $quote->load(['company', 'contact', 'deal', 'items']);

        return view('quotes.show', [
            'quote' => $quote,
            'timeline' => $quote->activities()->with(['owner:id,name', 'company:id,name', 'contact:id,first_name,last_name', 'deal:id,title'])->latest()->limit(10)->get(),
            'openTasks' => $quote->activities()->ownedBy($request->user()->id)->tasks()->whereNull('completed_at')->orderByRaw('due_at asc nulls last')->get(),
        ]);
    }

    public function edit(Request $request, Quote $quote): View
    {
        $this->assertOwner($request, $quote);
        $quote->load('items');

        return view('quotes.form', compact('quote') + $this->lookups($request));
    }

    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        DB::transaction(function () use ($request, $quote) {
            $data = $request->validated();
            $totals = $this->calculator->calculate($data['items'], $data['discount_percent'], $data['tax_percent']);
            unset($data['items']);
            $quote->update([...$data, ...collect($totals)->except('items')->all()]);
            $quote->items()->delete();
            $quote->items()->createMany($totals['items']);
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Ponuda je ažurirana.');
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $status = QuoteStatus::from($request->validate(['status' => ['required', Rule::enum(QuoteStatus::class)]])['status']);

        DB::transaction(function () use ($quote, $status): void {
            $quote->update(['status' => $status]);

            if ($status === QuoteStatus::Accepted && $quote->deal && $quote->deal->stage !== DealStage::Won) {
                $quote->deal->update(['stage' => DealStage::Won]);
            }
        });

        return back()->with('success', $status === QuoteStatus::Accepted && $quote->deal
            ? 'Ponuda je prihvaćena, prilika je označena kao dobivena.'
            : 'Status ponude je promijenjen.');
    }

    public function duplicate(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $quote->load('items');

        $copy = DB::transaction(function () use ($request, $quote): Quote {
            $copy = $request->user()->quotes()->create([
                ...$quote->only(['deal_id', 'company_id', 'contact_id', 'currency', 'discount_percent', 'tax_percent', 'subtotal', 'discount_total', 'tax_total', 'total', 'notes', 'terms']),
                'number' => $this->numberGenerator->next(),
                'title' => $quote->title.' (kopija)',
                'status' => QuoteStatus::Draft,
                'issue_date' => now(),
                'valid_until' => now()->addDays(14),
            ]);

            $copy->items()->createMany($quote->items->map(fn (QuoteItem $item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'position' => $item->position,
            ])->all());

            return $copy;
        });

        return redirect()->route('quotes.show', $copy)->with('success', 'Ponuda je duplicirana kao nova verzija.');
    }

    public function pdf(Request $request, Quote $quote)
    {
        $this->assertOwner($request, $quote);
        $quote->load(['company', 'contact', 'deal', 'items']);

        return Pdf::loadView('quotes.pdf', compact('quote'))->setPaper('a4')->download($quote->number.'.pdf');
    }

    public function export(Request $request): StreamedResponse
    {
        $quotes = $this->filtered($request, $request->boolean('archived'))->with(['company', 'deal'])->orderByDesc('issue_date')->get();

        $rows = $quotes->map(fn (Quote $quote) => [
            $quote->number,
            $quote->title,
            $quote->company?->name ?? '',
            $quote->deal?->title ?? '',
            $quote->status->label(),
            $quote->issue_date?->format('d.m.Y.') ?? '',
            $quote->valid_until?->format('d.m.Y.') ?? '',
            $quote->currency,
            number_format((float) $quote->subtotal, 2, ',', ''),
            number_format((float) $quote->discount_total, 2, ',', ''),
            number_format((float) $quote->tax_total, 2, ',', ''),
            number_format((float) $quote->total, 2, ',', ''),
        ]);

        return Csv::stream('ponude-'.now()->format('Y-m-d').'.csv', [
            'Broj', 'Naslov', 'Tvrtka', 'Prilika', 'Status', 'Datum izdanja', 'Vrijedi do', 'Valuta', 'Osnovica', 'Popust', 'PDV', 'Ukupno',
        ], $rows);
    }

    public function restore(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $quote->restore();

        return redirect()->route('quotes.show', $quote)->with('success', 'Ponuda je vraćena iz arhive.');
    }

    public function forceDestroy(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $quote->forceDelete();

        return redirect()->route('quotes.index')->with('success', 'Ponuda je trajno obrisana.');
    }

    public function destroy(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Ponuda je arhivirana.');
    }

    private function filtered(Request $request, bool $archived): Builder
    {
        return Quote::query()
            ->when($archived, fn (Builder $query) => $query->onlyTrashed())
            ->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('title', 'ilike', '%'.$request->string('search').'%')->orWhere('number', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')));
    }

    private function lookups(Request $request): array
    {
        $userId = $request->user()->id;

        return [
            'companies' => Company::where('owner_id', $userId)->orderBy('name')->get(),
            'contacts' => Contact::where('owner_id', $userId)->orderBy('last_name')->get(),
            'deals' => Deal::where('owner_id', $userId)->orderBy('title')->get(),
        ];
    }

    private function assertOwner(Request $request, Quote $quote): void
    {
        abort_unless($quote->owner_id === $request->user()->id, 404);
    }
}
