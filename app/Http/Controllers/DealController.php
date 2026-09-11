<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Http\Controllers\Concerns\SortsAndPaginates;
use App\Http\Requests\DealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealController extends Controller
{
    use SortsAndPaginates;

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request, ['title', 'stage', 'value', 'probability', 'expected_close_date', 'created_at']);
        $archived = $request->boolean('archived');

        $deals = $this->filtered($request, $archived)
            ->with(['company', 'contact'])
            ->withCount('quotes')
            ->orderBy($sort, $direction)
            ->paginate($this->perPageFrom($request))
            ->withQueryString();

        return view('deals.index', [
            'deals' => $deals,
            'archived' => $archived,
            'archivedCount' => Deal::onlyTrashed()->where('owner_id', $request->user()->id)->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function board(Request $request): View
    {
        $items = $this->filtered($request, false)->with(['company', 'contact'])->withCount('quotes')->orderBy('expected_close_date')->get()->groupBy(fn ($item) => $item->stage->value);

        return view('deals.board', compact('items'));
    }

    public function create(Request $request): View
    {
        return view('deals.form', ['deal' => new Deal, ...$this->lookups($request)]);
    }

    public function store(DealRequest $request): RedirectResponse
    {
        $deal = $request->user()->deals()->create($request->validated());

        return redirect()->route('deals.show', $deal)->with('success', 'Deal je uspješno izrađen.');
    }

    public function show(Request $request, Deal $deal): View
    {
        $this->assertOwner($request, $deal);
        $deal->load(['company', 'contact', 'quotes']);

        return view('deals.show', [
            'deal' => $deal,
            'timeline' => $deal->activities()->with(['owner:id,name', 'company:id,name', 'contact:id,first_name,last_name', 'quote:id,number'])->latest()->limit(15)->get(),
            'openTasks' => $deal->activities()->ownedBy($request->user()->id)->tasks()->whereNull('completed_at')->orderByRaw('due_at asc nulls last')->get(),
        ]);
    }

    public function edit(Request $request, Deal $deal): View
    {
        $this->assertOwner($request, $deal);

        return view('deals.form', compact('deal') + $this->lookups($request));
    }

    public function update(DealRequest $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->update($request->validated());

        return redirect()->route('deals.show', $deal)->with('success', 'Deal je ažuriran.');
    }

    public function updateStage(Request $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->update($request->validate(['stage' => ['required', Rule::enum(DealStage::class)]]));

        return back()->with('success', 'Faza deala je promijenjena.');
    }

    public function export(Request $request): StreamedResponse
    {
        $deals = $this->filtered($request, $request->boolean('archived'))->with(['company', 'contact'])->withCount('quotes')->orderBy('title')->get();

        $rows = $deals->map(fn (Deal $deal) => [
            $deal->title,
            $deal->company?->name ?? '',
            $deal->contact?->full_name ?? '',
            $deal->stage->label(),
            $deal->currency,
            number_format((float) $deal->value, 2, ',', ''),
            $deal->probability.'%',
            $deal->currency.' '.number_format((float) $deal->value * $deal->probability / 100, 2, ',', ''),
            $deal->expected_close_date?->format('d.m.Y.') ?? '',
            $deal->quotes_count,
        ]);

        return Csv::stream('prilike-'.now()->format('Y-m-d').'.csv', [
            'Naziv', 'Tvrtka', 'Kontakt', 'Faza', 'Valuta', 'Vrijednost', 'Vjerojatnost', 'Ponderirano', 'Očekivano zatvaranje', 'Ponude',
        ], $rows);
    }

    public function restore(Request $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->restore();

        return redirect()->route('deals.show', $deal)->with('success', 'Prilika je vraćena iz arhive.');
    }

    public function forceDestroy(Request $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->forceDelete();

        return redirect()->route('deals.index')->with('success', 'Prilika je trajno obrisana.');
    }

    public function destroy(Request $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->delete();

        return redirect()->route('deals.index')->with('success', 'Deal je arhiviran.');
    }

    private function filtered(Request $request, bool $archived): Builder
    {
        return Deal::query()
            ->when($archived, fn (Builder $query) => $query->onlyTrashed())
            ->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn (Builder $query) => $query->where('title', 'ilike', '%'.$request->string('search').'%'))
            ->when($request->filled('stage'), fn (Builder $query) => $query->where('stage', $request->string('stage')));
    }

    private function lookups(Request $request): array
    {
        return [
            'companies' => Company::where('owner_id', $request->user()->id)->orderBy('name')->get(),
            'contacts' => Contact::where('owner_id', $request->user()->id)->orderBy('last_name')->get(),
        ];
    }

    private function assertOwner(Request $request, Deal $deal): void
    {
        abort_unless($deal->owner_id === $request->user()->id, 404);
    }
}
