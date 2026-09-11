<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Concerns\SortsAndPaginates;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    use SortsAndPaginates;

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request, ['name', 'status', 'city', 'annual_revenue', 'created_at']);
        $archived = $request->boolean('archived');

        $companies = $this->filtered($request, $archived)
            ->withCount(['contacts', 'deals'])
            ->orderBy($sort, $direction)
            ->paginate($this->perPageFrom($request))
            ->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
            'archived' => $archived,
            'archivedCount' => Company::onlyTrashed()->where('owner_id', $request->user()->id)->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function board(Request $request): View
    {
        $items = $this->filtered($request, false)->withCount(['contacts', 'deals'])->orderBy('name')->get()->groupBy(fn ($item) => $item->status->value);

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
        $company->load([
            'contacts' => fn ($q) => $q->latest(),
            'deals' => fn ($q) => $q->latest(),
            'quotes' => fn ($q) => $q->latest(),
        ]);

        return view('companies.show', [
            'company' => $company,
            'timeline' => $company->activities()->with(['owner:id,name', 'contact:id,first_name,last_name', 'deal:id,title', 'quote:id,number'])->latest()->limit(15)->get(),
            'openTasks' => $company->activities()->ownedBy($request->user()->id)->tasks()->whereNull('completed_at')->orderByRaw('due_at asc nulls last')->get(),
        ]);
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
        $company->update($request->validate(['status' => ['required', Rule::enum(CompanyStatus::class)]]));

        return back()->with('success', 'Status tvrtke je promijenjen.');
    }

    public function export(Request $request): StreamedResponse
    {
        $companies = $this->filtered($request, $request->boolean('archived'))
            ->withCount(['contacts', 'deals'])
            ->orderBy('name')
            ->get();

        $rows = $companies->map(fn (Company $company) => [
            $company->name,
            $company->status->label(),
            $company->industry ?? '',
            $company->city ?? '',
            $company->country,
            $company->email ?? '',
            $company->phone ?? '',
            $company->website ?? '',
            $company->employees ?? '',
            $company->annual_revenue === null ? '' : number_format((float) $company->annual_revenue, 2, ',', ''),
            $company->contacts_count,
            $company->deals_count,
        ]);

        return Csv::stream('tvrtke-'.now()->format('Y-m-d').'.csv', [
            'Naziv', 'Status', 'Industrija', 'Grad', 'Država', 'E-mail', 'Telefon', 'Web', 'Zaposleni', 'Godišnji prihod', 'Kontakti', 'Prilike',
        ], $rows);
    }

    public function restore(Request $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $company->restore();

        return redirect()->route('companies.show', $company)->with('success', 'Tvrtka je vraćena iz arhive.');
    }

    public function forceDestroy(Request $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $company->forceDelete();

        return redirect()->route('companies.index')->with('success', 'Tvrtka je trajno obrisana.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->assertOwner($request, $company);
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Tvrtka je arhivirana.');
    }

    private function filtered(Request $request, bool $archived): Builder
    {
        return Company::query()
            ->when($archived, fn (Builder $query) => $query->onlyTrashed())
            ->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('name', 'ilike', '%'.$request->string('search').'%')->orWhere('industry', 'ilike', '%'.$request->string('search').'%')->orWhere('city', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')));
    }

    private function assertOwner(Request $request, Company $company): void
    {
        abort_unless($company->owner_id === $request->user()->id, 404);
    }
}
