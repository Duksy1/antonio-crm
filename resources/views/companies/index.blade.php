@extends('layouts.app')
@section('title','Tvrtke')
@section('content')
<x-page-header eyebrow="ODNOSI" title="Tvrtke" description="Organizacije s kojima gradite poslovne odnose."><a class="primary-btn" href="{{ route('companies.create') }}">＋ Nova tvrtka</a></x-page-header>
<x-view-toolbar :list-route="route('companies.index')" :board-route="route('companies.board')" filter-name="status" :options="App\Enums\CompanyStatus::cases()" placeholder="Pretraži tvrtke ili industrije…" />
<div class="entity-grid">
@forelse($companies as $company)
<a class="entity-card" href="{{ route('companies.show',$company) }}"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar">{{ strtoupper(substr($company->name,0,2)) }}</span><div><h3>{{ $company->name }}</h3><p>{{ $company->industry ?: 'Industrija nije unesena' }}</p></div></div><x-status :status="$company->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Lokacija</span><strong>{{ $company->city ?: '—' }}{{ $company->country ? ', '.$company->country : '' }}</strong></div><div class="meta-row"><span>Prihod</span><strong>{{ $company->annual_revenue ? '€'.number_format($company->annual_revenue,0,',','.') : '—' }}</strong></div></div><div class="entity-card-footer"><span>{{ $company->contacts_count }} kontakata</span><span>{{ $company->deals_count }} dealova →</span></div></a>
@empty <x-empty title="Nema pronađenih tvrtki" text="Dodajte prvu tvrtku ili prilagodite kriterije pretraživanja."><a class="primary-btn" href="{{ route('companies.create') }}">Dodaj tvrtku</a></x-empty> @endforelse
</div><div class="pagination-wrap">{{ $companies->links() }}</div>
@endsection
