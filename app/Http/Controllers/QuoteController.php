<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Http\Requests\QuoteRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Services\QuoteCalculator;
use App\Services\QuoteNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteCalculator $calculator,
        private readonly QuoteNumberGenerator $numberGenerator,
    ) {}

    public function index(Request $request): View
    {
        $quotes = Quote::with(['company', 'deal'])->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'ilike', '%'.$request->string('search').'%')->orWhere('number', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(12)->withQueryString();

        return view('quotes.index', compact('quotes'));
    }

    public function board(Request $request): View
    {
        $items = Quote::with(['company', 'deal'])->where('owner_id', $request->user()->id)->orderByDesc('issue_date')->get()->groupBy(fn ($item) => $item->status->value);

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

        return view('quotes.show', compact('quote'));
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
        $quote->update($request->validate(['status' => ['required', Rule::enum(QuoteStatus::class)]]));

        return back()->with('success', 'Status ponude je promijenjen.');
    }

    public function pdf(Request $request, Quote $quote)
    {
        $this->assertOwner($request, $quote);
        $quote->load(['company', 'contact', 'deal', 'items']);

        return Pdf::loadView('quotes.pdf', compact('quote'))->setPaper('a4')->download($quote->number.'.pdf');
    }

    public function destroy(Request $request, Quote $quote): RedirectResponse
    {
        $this->assertOwner($request, $quote);
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Ponuda je arhivirana.');
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
