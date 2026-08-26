<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Http\Requests\DealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DealController extends Controller
{
    public function index(Request $request): View
    {
        $deals = Deal::with(['company', 'contact'])->withCount('quotes')->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'ilike', '%'.$request->string('search').'%'))
            ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')))
            ->latest()->paginate(12)->withQueryString();

        return view('deals.index', compact('deals'));
    }

    public function board(Request $request): View
    {
        $items = Deal::with(['company', 'contact'])->withCount('quotes')->where('owner_id', $request->user()->id)->orderBy('expected_close_date')->get()->groupBy(fn ($item) => $item->stage->value);

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

        return view('deals.show', compact('deal'));
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

    public function destroy(Request $request, Deal $deal): RedirectResponse
    {
        $this->assertOwner($request, $deal);
        $deal->delete();

        return redirect()->route('deals.index')->with('success', 'Deal je arhiviran.');
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
