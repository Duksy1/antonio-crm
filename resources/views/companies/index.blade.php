@extends('layouts.app')
@section('title','Tvrtke')
@section('content')
<x-page-header eyebrow="ODNOSI" title="Tvrtke" description="Organizacije s kojima gradite poslovne odnose."><a class="primary-btn" href="{{ route('companies.create') }}">＋ Nova tvrtka</a></x-page-header>
<x-view-toolbar :list-route="route('companies.index')" :board-route="route('companies.board')" filter-name="status" :options="App\Enums\CompanyStatus::cases()" placeholder="Pretraži tvrtke, industrije ili gradove…" />
<x-list-tools :columns="['created_at' => 'Datum dodavanja', 'name' => 'Naziv', 'status' => 'Status', 'city' => 'Grad', 'annual_revenue' => 'Godišnji prihod']" export-route="companies.export" :archived="$archived" :archived-count="$archivedCount" />

@if ($archived)
<div class="archive-banner"><span>🗄</span><p>Prikazujete arhivirane tvrtke. Vratite zapis u aktivnu bazu ili ga trajno obrišite.</p></div>
@endif

<div class="entity-grid">
@forelse($companies as $company)
@if ($archived)
    <div class="entity-card archived"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar">{{ strtoupper(substr($company->name,0,2)) }}</span><div><h3>{{ $company->name }}</h3><p>Arhivirano {{ $company->deleted_at?->format('d.m.Y.') }}</p></div></div><x-status :status="$company->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Lokacija</span><strong>{{ $company->city ?: '—' }}{{ $company->country ? ', '.$company->country : '' }}</strong></div><div class="meta-row"><span>Kontakti</span><strong>{{ $company->contacts_count }}</strong></div></div><div class="entity-card-footer archive-actions"><form method="POST" action="{{ route('companies.restore', $company) }}">@csrf @method('PATCH')<button class="secondary-btn" type="submit">↩ Vrati</button></form><form method="POST" action="{{ route('companies.force-destroy', $company) }}" onsubmit="return confirm('Trajno obrisati ovu tvrtku i sve poveznice?')">@csrf @method('DELETE')<button class="danger-btn" type="submit">Trajno obriši</button></form></div></div>
@else
    <a class="entity-card" href="{{ route('companies.show',$company) }}"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar">{{ strtoupper(substr($company->name,0,2)) }}</span><div><h3>{{ $company->name }}</h3><p>{{ $company->industry ?: 'Industrija nije unesena' }}</p></div></div><x-status :status="$company->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Lokacija</span><strong>{{ $company->city ?: '—' }}{{ $company->country ? ', '.$company->country : '' }}</strong></div><div class="meta-row"><span>Prihod</span><strong>{{ $company->annual_revenue ? '€'.number_format($company->annual_revenue,0,',','.') : '—' }}</strong></div></div><div class="entity-card-footer"><span>{{ $company->contacts_count }} kontakata</span><span>{{ $company->deals_count }} dealova →</span></div></a>
@endif
@empty <x-empty title="{{ $archived ? 'Arhiva je prazna' : 'Nema pronađenih tvrtki' }}" text="{{ $archived ? 'Ovdje završavaju tvrtke koje arhivirate.' : 'Dodajte prvu tvrtku ili prilagodite kriterije pretraživanja.' }}"><a class="primary-btn" href="{{ route('companies.create') }}">Dodaj tvrtku</a></x-empty> @endforelse
</div><div class="pagination-wrap">{{ $companies->links() }}</div>
@endsection
