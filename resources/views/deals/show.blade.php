@extends('layouts.app')
@section('title',$deal->title)
@section('content')
<x-page-header eyebrow="DEALOVI / DETALJI" :title="$deal->title"><a class="secondary-btn" href="{{ route('deals.edit',$deal) }}">Uredi</a><a class="primary-btn" href="{{ route('quotes.create',['deal'=>$deal->id]) }}">＋ Izradi ponudu</a></x-page-header>
<div class="show-layout"><section class="detail-card"><div class="hero-identity"><span class="hero-avatar">↗</span><div><x-status :status="$deal->stage" /><h2>{{ $deal->title }}</h2><p>{{ $deal->company?->name ?? 'Tvrtka nije povezana' }}</p></div></div><div class="deal-value-hero">{{ $deal->currency }} {{ number_format($deal->value,2,',','.') }}</div><small>Vjerojatnost zatvaranja · {{ $deal->probability }}%</small><div class="probability-bar"><span style="width:{{ $deal->probability }}%"></span></div><div class="detail-grid"><div class="detail-item"><small>Tvrtka</small>@if($deal->company)<a href="{{ route('companies.show',$deal->company) }}">{{ $deal->company->name }}</a>@else<strong>—</strong>@endif</div><div class="detail-item"><small>Kontakt</small>@if($deal->contact)<a href="{{ route('contacts.show',$deal->contact) }}">{{ $deal->contact->full_name }}</a>@else<strong>—</strong>@endif</div><div class="detail-item"><small>Očekivano zatvaranje</small><strong>{{ $deal->expected_close_date?->format('d.m.Y.') ?? '—' }}</strong></div><div class="detail-item"><small>Vlasnik</small><strong>{{ $deal->owner->name }}</strong></div></div><div class="notes-box"><h3>Opis i sljedeći koraci</h3><p>{{ $deal->description ?: 'Nema zabilježenog opisa.' }}</p></div></section><aside><section class="side-card"><h3>Ponude <small>({{ $deal->quotes->count() }})</small></h3><div class="related-list">@forelse($deal->quotes as $quote)<a href="{{ route('quotes.show',$quote) }}"><span><strong>{{ $quote->number }}</strong><small>{{ $quote->status->label() }}</small></span><strong>{{ $quote->currency }} {{ number_format($quote->total,0,',','.') }}</strong></a>@empty<p class="side-empty">Nema ponuda. Izradite je jednim klikom iz ovog deala.</p>@endforelse</div><a class="secondary-btn" style="width:100%;margin-top:12px" href="{{ route('quotes.create',['deal'=>$deal->id]) }}">＋ Nova ponuda</a><div class="danger-zone"><span>Zapis više nije potreban?</span><form method="POST" action="{{ route('deals.destroy',$deal) }}" onsubmit="return confirm('Arhivirati deal?')">@csrf @method('DELETE')<button class="danger-btn">Arhiviraj</button></form></div></section></aside></div>

<section class="panel detail-activity">
    <div class="panel-head"><div><p class="eyebrow">AKTIVNOSTI</p><h2>Bilješke i zadaci</h2></div><a href="{{ route('activities.index', ['scope' => 'all']) }}">Sve aktivnosti →</a></div>
    <x-activity-form :deal-id="$deal->id" :company-id="$deal->company_id" :contact-id="$deal->contact_id" compact placeholder="Npr. Dogovoriti demo s tehničkim timom…" />
    @if ($openTasks->isNotEmpty())
        <h3 class="sub-heading">Otvoreni zadaci ({{ $openTasks->count() }})</h3>
        <x-timeline :items="$openTasks" :show-context="false" />
    @endif
    <h3 class="sub-heading">Povijest</h3>
    <x-timeline :items="$timeline" empty-text="Faze, vrijednost i ponude ovog deala bilježe se automatski." />
</section>
@endsection
