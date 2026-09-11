@extends('layouts.app')
@section('title','Dealovi')
@section('content')
<x-page-header eyebrow="PIPELINE" title="Dealovi" description="Pratite vrijednost i momentum svake prilike."><a class="primary-btn" href="{{ route('deals.create') }}">＋ Novi deal</a></x-page-header>
<x-view-toolbar :list-route="route('deals.index')" :board-route="route('deals.board')" filter-name="stage" :options="App\Enums\DealStage::cases()" placeholder="Pretraži dealove…" />
<x-list-tools :columns="['created_at' => 'Datum dodavanja', 'title' => 'Naziv', 'stage' => 'Faza', 'value' => 'Vrijednost', 'probability' => 'Vjerojatnost', 'expected_close_date' => 'Očekivano zatvaranje']" export-route="deals.export" :archived="$archived" :archived-count="$archivedCount" />

@if ($archived)
<div class="archive-banner"><span>🗄</span><p>Prikazujete arhivirane prilike. Vratite zapis u aktivni pipeline ili ga trajno obrišite.</p></div>
@endif

<div class="entity-grid">
@forelse($deals as $deal)
@if ($archived)
    <div class="entity-card archived"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar blue">↗</span><div><h3>{{ $deal->title }}</h3><p>Arhivirano {{ $deal->deleted_at?->format('d.m.Y.') }}</p></div></div><x-status :status="$deal->stage" /></div><div class="entity-card-body"><div class="meta-row"><span>Tvrtka</span><strong>{{ $deal->company?->name ?? '—' }}</strong></div><div class="meta-row"><span>Vrijednost</span><strong>{{ $deal->currency }} {{ number_format($deal->value,0,',','.') }}</strong></div></div><div class="entity-card-footer archive-actions"><form method="POST" action="{{ route('deals.restore', $deal) }}">@csrf @method('PATCH')<button class="secondary-btn" type="submit">↩ Vrati</button></form><form method="POST" action="{{ route('deals.force-destroy', $deal) }}" onsubmit="return confirm('Trajno obrisati ovu priliku?')">@csrf @method('DELETE')<button class="danger-btn" type="submit">Trajno obriši</button></form></div></div>
@else
    <a class="entity-card" href="{{ route('deals.show',$deal) }}"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar blue">↗</span><div><h3>{{ $deal->title }}</h3><p>{{ $deal->company?->name ?? 'Bez tvrtke' }}</p></div></div><x-status :status="$deal->stage" /></div><div class="entity-card-body"><div class="meta-row"><span>Vrijednost</span><strong>{{ $deal->currency }} {{ number_format($deal->value,0,',','.') }}</strong></div><div class="meta-row"><span>Vjerojatnost</span><strong>{{ $deal->probability }}%</strong></div></div><div class="entity-card-footer"><span>{{ $deal->expected_close_date?->format('d.m.Y.') ?? 'Bez roka' }}</span><span>{{ $deal->quotes_count }} ponuda →</span></div></a>
@endif
@empty<x-empty title="{{ $archived ? 'Arhiva je prazna' : 'Nema pronađenih dealova' }}" text="{{ $archived ? 'Ovdje završavaju prilike koje arhivirate.' : 'Dodajte prvu prodajnu priliku.' }}"><a class="primary-btn" href="{{ route('deals.create') }}">Dodaj deal</a></x-empty>@endforelse</div><div class="pagination-wrap">{{ $deals->links() }}</div>
@endsection
