<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Controllers\Concerns\SortsAndPaginates;
use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    use SortsAndPaginates;

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request, ['last_name', 'first_name', 'status', 'created_at'], 'last_name');
        $archived = $request->boolean('archived');

        $contacts = $this->filtered($request, $archived)
            ->with('company')
            ->orderBy($sort, $direction)
            ->paginate($this->perPageFrom($request))
            ->withQueryString();

        return view('contacts.index', [
            'contacts' => $contacts,
            'archived' => $archived,
            'archivedCount' => Contact::onlyTrashed()->where('owner_id', $request->user()->id)->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function board(Request $request): View
    {
        $items = $this->filtered($request, false)->with('company')->orderBy('last_name')->get()->groupBy(fn ($item) => $item->status->value);

        return view('contacts.board', compact('items'));
    }

    public function create(Request $request): View
    {
        return view('contacts.form', ['contact' => new Contact, 'companies' => $this->companies($request)]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = $request->user()->contacts()->create($request->validated());

        return redirect()->route('contacts.show', $contact)->with('success', 'Kontakt je uspješno izrađen.');
    }

    public function show(Request $request, Contact $contact): View
    {
        $this->assertOwner($request, $contact);
        $contact->load(['company', 'deals', 'quotes']);

        return view('contacts.show', [
            'contact' => $contact,
            'timeline' => $contact->activities()->with(['owner:id,name', 'deal:id,title', 'quote:id,number'])->latest()->limit(15)->get(),
            'openTasks' => $contact->activities()->ownedBy($request->user()->id)->tasks()->whereNull('completed_at')->orderByRaw('due_at asc nulls last')->get(),
        ]);
    }

    public function edit(Request $request, Contact $contact): View
    {
        $this->assertOwner($request, $contact);

        return view('contacts.form', compact('contact') + ['companies' => $this->companies($request)]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->update($request->validated());

        return redirect()->route('contacts.show', $contact)->with('success', 'Kontakt je ažuriran.');
    }

    public function updateStatus(Request $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->update($request->validate(['status' => ['required', Rule::enum(ContactStatus::class)]]));

        return back()->with('success', 'Status kontakta je promijenjen.');
    }

    public function export(Request $request): StreamedResponse
    {
        $contacts = $this->filtered($request, $request->boolean('archived'))->with('company')->orderBy('last_name')->get();

        $rows = $contacts->map(fn (Contact $contact) => [
            $contact->full_name,
            $contact->company?->name ?? '',
            $contact->status->label(),
            $contact->job_title ?? '',
            $contact->email ?? '',
            $contact->phone ?? '',
            $contact->linkedin_url ?? '',
            $contact->birthday?->format('d.m.Y.') ?? '',
        ]);

        return Csv::stream('kontakti-'.now()->format('Y-m-d').'.csv', [
            'Ime i prezime', 'Tvrtka', 'Status', 'Pozicija', 'E-mail', 'Telefon', 'LinkedIn', 'Rođendan',
        ], $rows);
    }

    public function restore(Request $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->restore();

        return redirect()->route('contacts.show', $contact)->with('success', 'Kontakt je vraćen iz arhive.');
    }

    public function forceDestroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->forceDelete();

        return redirect()->route('contacts.index')->with('success', 'Kontakt je trajno obrisan.');
    }

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontakt je arhiviran.');
    }

    private function filtered(Request $request, bool $archived): Builder
    {
        return Contact::query()
            ->when($archived, fn (Builder $query) => $query->onlyTrashed())
            ->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('first_name', 'ilike', '%'.$request->string('search').'%')->orWhere('last_name', 'ilike', '%'.$request->string('search').'%')->orWhere('email', 'ilike', '%'.$request->string('search').'%')->orWhere('job_title', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')));
    }

    private function companies(Request $request)
    {
        return Company::where('owner_id', $request->user()->id)->orderBy('name')->get();
    }

    private function assertOwner(Request $request, Contact $contact): void
    {
        abort_unless($contact->owner_id === $request->user()->id, 404);
    }
}
