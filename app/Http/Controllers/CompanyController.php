<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::withCount(['contacts', 'deals'])->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'ilike', '%'.$request->string('search').'%')->orWhere('industry', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(12)->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function board(Request $request): View
    {
        $items = Company::withCount(['contacts', 'deals'])->where('owner_id', $request->user()->id)->orderBy('name')->get()->groupBy(fn ($item) => $item->status->value);

        return view('companies.board', compact('items'));
    }

    public function create(): View
    {
        return view('companies.form', ['company' => new Company]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $company = $request->user()->companies()->create($request->validated());

        return redirect()->route('companies.show', $company)->with('success', 'Tvrtka je uspješno izrađena.');
    }

    public function show(Request $request, Company $company): View
    {
        $this->assertOwner($request, $company);
        $company->load(['contacts' => fn ($q) => $q->latest(), 'deals' => fn ($q) => $q->latest(), 'quotes' => fn ($q) => $q->latest()]);

        return view('companies.show', compact('company'));
    }

    public function edit(Request $request, Company $company): View
    {
        $this->assertOwner($request, $company);

        return view('companies.form', compact('company'));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $company->update($request->validated());

        return redirect()->route('companies.show', $company)->with('success', 'Tvrtka je ažurirana.');
    }

    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $data = $request->validate(['status' => ['required', Rule::enum(CompanyStatus::class)]]);
        $company->update($data);

        return back()->with('success', 'Status tvrtke je promijenjen.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Tvrtka je arhivirana.');
    }

    private function assertOwner(Request $request, Company $company): void
    {
        abort_unless($company->owner_id === $request->user()->id, 404);
    }
}
