@extends('layouts.app')
@section('title','Ponude')
@section('content')
<x-page-header eyebrow="DOKUMENTI" title="Ponude" description="Od nacrta do prihvaćenog posla, bez gubitka konteksta."><a class="primary-btn" href="{{ route('quotes.create') }}">＋ Nova ponuda</a></x-page-header>
<x-view-toolbar :list-route="route('quotes.index')" :board-route="route('quotes.board')" filter-name="status" :options="App\Enums\QuoteStatus::cases()" placeholder="Pretraži broj ili naslov…" />
<x-list-tools :columns="['number' => 'Broj', 'issue_date' => 'Datum izdanja', 'valid_until' => 'Vrijedi do', 'status' => 'Status', 'total' => 'Ukupan iznos', 'title' => 'Naslov']" export-route="quotes.export" :archived="$archived" :archived-count="$archivedCount" />

@if ($archived)
<div class="archive-banner"><span>🗄</span><p>Prikazujete arhivirane ponude. Vratite zapis ili ga trajno obrišite.</p></div>
@endif

<div class="entity-grid">
@forelse($quotes as $quote)
@if ($archived)
    <div class="entity-card archived"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar violet">▤</span><div><h3>{{ $quote->number }}</h3><p>Arhivirano {{ $quote->deleted_at?->format('d.m.Y.') }}</p></div></div><x-status :status="$quote->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Klijent</span><strong>{{ $quote->company?->name ?? '—' }}</strong></div><div class="meta-row"><span>Ukupno</span><strong>{{ $quote->currency }} {{ number_format($quote->total,2,',','.') }}</strong></div></div><div class="entity-card-footer archive-actions"><form method="POST" action="{{ route('quotes.restore', $quote) }}">@csrf @method('PATCH')<button class="secondary-btn" type="submit">↩ Vrati</button></form><form method="POST" action="{{ route('quotes.force-destroy', $quote) }}" onsubmit="return confirm('Trajno obrisati ovu ponudu?')">@csrf @method('DELETE')<button class="danger-btn" type="submit">Trajno obriši</button></form></div></div>
@else
    <a class="entity-card" href="{{ route('quotes.show',$quote) }}"><div class="entity-card-head"><div class="entity-identity"><span class="entity-avatar violet">▤</span><div><h3>{{ $quote->number }}</h3><p>{{ $quote->title }}</p></div></div><x-status :status="$quote->status" /></div><div class="entity-card-body"><div class="meta-row"><span>Klijent</span><strong>{{ $quote->company?->name ?? '—' }}</strong></div><div class="meta-row"><span>Ukupno</span><strong>{{ $quote->currency }} {{ number_format($quote->total,2,',','.') }}</strong></div></div><div class="entity-card-footer"><span>Vrijedi do {{ $quote->valid_until?->format('d.m.Y.') ?? '—' }}@if($quote->isExpired()) · <b class="overdue-flag">istekla</b>@endif</span><span>Otvori →</span></div></a>
@endif
@empty<x-empty title="{{ $archived ? 'Arhiva je prazna' : 'Nema pronađenih ponuda' }}" text="{{ $archived ? 'Ovdje završavaju ponude koje arhivirate.' : 'Izradite ponudu izravno ili iz kanbana dealova.' }}"><a class="primary-btn" href="{{ route('quotes.create') }}">Izradi ponudu</a></x-empty>@endforelse</div><div class="pagination-wrap">{{ $quotes->links() }}</div>
@endsection
