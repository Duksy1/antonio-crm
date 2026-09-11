@extends('layouts.app')
@section('title','Kontakti')
@section('content')
<x-page-header eyebrow="LJUDI" title="Kontakti" description="Osobe koje pokreću svaku poslovnu priliku."><a class="primary-btn" href="{{ route('contacts.create') }}">＋ Novi kontakt</a></x-page-header>
<x-view-toolbar :list-route="route('contacts.index')" :board-route="route('contacts.board')" filter-name="status" :options="App\Enums\ContactStatus::cases()" placeholder="Pretraži ime, e-mail ili poziciju…" />
<x-list-tools :columns="['last_name' => 'Prezime', 'first_name' => 'Ime', 'status' => 'Status', 'created_at' => 'Datum dodavanja']" export-route="contacts.export" :archived="$archived" :archived-count="$archivedCount" />

@if ($archived)
<div class="archive-banner"><span>🗄</span><p>Prikazujete arhivirane kontakte. Vratite zapis u aktivnu bazu ili ga trajno obrišite.</p></div>
@endif

<div class="entity-grid">
@forelse($contacts as $contact)
@if ($archived)
    <div class="entity-card archived"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar coral">{{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}</span><div><h3>{{ $contact->full_name }}</h3><p>Arhivirano {{ $contact->deleted_at?->format('d.m.Y.') }}</p></div></div><x-status :status="$contact->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Tvrtka</span><strong>{{ $contact->company?->name ?? '—' }}</strong></div><div class="meta-row"><span>E-mail</span><strong>{{ $contact->email ?: '—' }}</strong></div></div><div class="entity-card-footer archive-actions"><form method="POST" action="{{ route('contacts.restore', $contact) }}">@csrf @method('PATCH')<button class="secondary-btn" type="submit">↩ Vrati</button></form><form method="POST" action="{{ route('contacts.force-destroy', $contact) }}" onsubmit="return confirm('Trajno obrisati ovaj kontakt?')">@csrf @method('DELETE')<button class="danger-btn" type="submit">Trajno obriši</button></form></div></div>
@else
    <a class="entity-card" href="{{ route('contacts.show',$contact) }}"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar coral">{{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}</span><div><h3>{{ $contact->full_name }}</h3><p>{{ $contact->job_title ?: 'Pozicija nije unesena' }}</p></div></div><x-status :status="$contact->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Tvrtka</span><strong>{{ $contact->company?->name ?? '—' }}</strong></div><div class="meta-row"><span>E-mail</span><strong>{{ $contact->email ?: '—' }}</strong></div></div><div class="entity-card-footer"><span>{{ $contact->phone ?: 'Bez telefona' }}</span><span>Detalji →</span></div></a>
@endif
@empty<x-empty title="{{ $archived ? 'Arhiva je prazna' : 'Nema pronađenih kontakata' }}" text="{{ $archived ? 'Ovdje završavaju kontakti koje arhivirate.' : 'Dodajte prvi kontakt ili promijenite filtere.' }}"><a class="primary-btn" href="{{ route('contacts.create') }}">Dodaj kontakt</a></x-empty>@endforelse</div><div class="pagination-wrap">{{ $contacts->links() }}</div>
@endsection
