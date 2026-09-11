@extends('layouts.app')
@section('title',$contact->full_name)
@section('content')
<x-page-header eyebrow="KONTAKTI / DETALJI" :title="$contact->full_name"><a class="secondary-btn" href="{{ route('contacts.edit',$contact) }}">Uredi</a><a class="primary-btn" href="{{ route('deals.create',['contact'=>$contact->id]) }}">＋ Novi deal</a></x-page-header>
<div class="show-layout"><section class="detail-card"><div class="hero-identity"><span class="hero-avatar">{{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}</span><div><x-status :status="$contact->status" /><h2>{{ $contact->full_name }}</h2><p>{{ $contact->job_title ?: 'Pozicija nije unesena' }} @if($contact->company) · <a href="{{ route('companies.show',$contact->company) }}">{{ $contact->company->name }}</a>@endif</p></div></div><div class="detail-grid"><div class="detail-item"><small>E-mail</small><a href="mailto:{{ $contact->email }}">{{ $contact->email ?: '—' }}</a></div><div class="detail-item"><small>Telefon</small><a href="tel:{{ $contact->phone }}">{{ $contact->phone ?: '—' }}</a></div><div class="detail-item"><small>LinkedIn</small>@if($contact->linkedin_url)<a target="_blank" href="{{ $contact->linkedin_url }}">Otvori profil ↗</a>@else<strong>—</strong>@endif</div><div class="detail-item"><small>Rođendan</small><strong>{{ $contact->birthday?->format('d.m.Y.') ?? '—' }}</strong></div></div><div class="notes-box"><h3>Bilješke</h3><p>{{ $contact->notes ?: 'Nema zabilježenih bilješki.' }}</p></div></section><aside><section class="side-card"><h3>Dealovi <small>({{ $contact->deals->count() }})</small></h3><div class="related-list">@forelse($contact->deals as $deal)<a href="{{ route('deals.show',$deal) }}"><span><strong>{{ $deal->title }}</strong><small>{{ $deal->stage->label() }}</small></span><strong>{{ $deal->currency }} {{ number_format($deal->value,0,',','.') }}</strong></a>@empty<p class="side-empty">Nema povezanih dealova.</p>@endforelse</div></section><section class="side-card"><h3>Ponude <small>({{ $contact->quotes->count() }})</small></h3><div class="related-list">@forelse($contact->quotes as $quote)<a href="{{ route('quotes.show',$quote) }}"><span><strong>{{ $quote->number }}</strong><small>{{ $quote->status->label() }}</small></span><strong>{{ $quote->currency }} {{ number_format($quote->total,0,',','.') }}</strong></a>@empty<p class="side-empty">Nema povezanih ponuda.</p>@endforelse</div><div class="danger-zone"><span>Zapis više nije potreban?</span><form method="POST" action="{{ route('contacts.destroy',$contact) }}" onsubmit="return confirm('Arhivirati kontakt?')">@csrf @method('DELETE')<button class="danger-btn">Arhiviraj</button></form></div></section></aside></div>

<section class="panel detail-activity">
    <div class="panel-head"><div><p class="eyebrow">AKTIVNOSTI</p><h2>Bilješke i zadaci</h2></div><a href="{{ route('activities.index', ['scope' => 'all']) }}">Sve aktivnosti →</a></div>
    <x-activity-form :contact-id="$contact->id" :company-id="$contact->company_id" compact placeholder="Npr. Poslati sažetak razgovora…" />
    @if ($openTasks->isNotEmpty())
        <h3 class="sub-heading">Otvoreni zadaci ({{ $openTasks->count() }})</h3>
        <x-timeline :items="$openTasks" :show-context="false" />
    @endif
    <h3 class="sub-heading">Povijest</h3>
    <x-timeline :items="$timeline" />
</section>
@endsection
