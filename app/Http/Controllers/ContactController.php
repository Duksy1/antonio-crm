<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = Contact::with('company')->where('owner_id', $request->user()->id)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'ilike', '%'.$request->string('search').'%')->orWhere('last_name', 'ilike', '%'.$request->string('search').'%')->orWhere('email', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(12)->withQueryString();

        return view('contacts.index', compact('contacts'));
    }

    public function board(Request $request): View
    {
        $items = Contact::with('company')->where('owner_id', $request->user()->id)->orderBy('last_name')->get()->groupBy(fn ($item) => $item->status->value);

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

        return view('contacts.show', compact('contact'));
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

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->assertOwner($request, $contact);
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontakt je arhiviran.');
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
