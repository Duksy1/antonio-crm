@extends('layouts.app')
@section('title', 'Pregled')
@section('content')
<x-page-header eyebrow="PREGLED POSLOVANJA" title="Dobro jutro, {{ explode(' ', auth()->user()->name)[0] }}."><span class="date-chip">{{ now()->translatedFormat('l, j. F') }}</span></x-page-header>

<section class="metric-grid">
    <article class="metric-card accent"><div class="metric-top"><span>Vrijednost pipelinea</span><i>↗</i></div><strong>€{{ number_format($stats['pipeline'], 0, ',', '.') }}</strong><p>{{ $stats['openDeals'] }} aktivnih prilika</p><div class="spark-bars"><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
    <article class="metric-card"><div class="metric-top"><span>Tvrtke</span><b class="metric-icon">⌂</b></div><strong>{{ $stats['companies'] }}</strong><p>u aktivnoj bazi</p></article>
    <article class="metric-card"><div class="metric-top"><span>Kontakti</span><b class="metric-icon">◎</b></div><strong>{{ $stats['contacts'] }}</strong><p>poslovnih odnosa</p></article>
    <article class="metric-card"><div class="metric-top"><span>Prihvaćene ponude</span><b class="metric-icon">✓</b></div><strong>€{{ number_format($stats['acceptedQuotes'], 0, ',', '.') }}</strong><p>ukupna realizacija</p></article>
</section>

<div class="dashboard-grid">
    <section class="panel pipeline-panel">
        <div class="panel-head"><div><p class="eyebrow">PRODAJNI LIJEVAK</p><h2>Pipeline po fazama</h2></div><a href="{{ route('deals.board') }}">Otvori kanban →</a></div>
        <div class="funnel-list">
            @foreach(App\Enums\DealStage::cases() as $stage)
                @php($row = $stageTotals->get($stage->value))
                <div class="funnel-row"><span class="funnel-name"><i class="color-{{ $stage->color() }}"></i>{{ $stage->label() }}</span><div class="funnel-track"><span class="color-{{ $stage->color() }}" style="width: {{ min(100, max(4, (($row?->total ?? 0) / max(1, $stats['pipeline'])) * 100)) }}%"></span></div><strong>€{{ number_format($row?->total ?? 0, 0, ',', '.') }}</strong><small>{{ $row?->count ?? 0 }}</small></div>
            @endforeach
        </div>
    </section>

    <section class="panel activity-panel">
        <div class="panel-head"><div><p class="eyebrow">NAJNOVIJE</p><h2>Ponude</h2></div><a href="{{ route('quotes.index') }}">Sve ponude →</a></div>
        <div class="mini-list">
            @forelse($quotes as $quote)
                <a href="{{ route('quotes.show', $quote) }}"><span class="doc-icon">▤</span><span><strong>{{ $quote->number }}</strong><small>{{ $quote->company?->name ?? 'Bez tvrtke' }}</small></span><span class="mini-amount">€{{ number_format($quote->total, 0, ',', '.') }}<x-status :status="$quote->status" /></span></a>
            @empty <x-empty title="Još nema ponuda" text="Izradite prvu ponudu iz deala." /> @endforelse
        </div>
    </section>
</div>

<section class="panel recent-panel">
    <div class="panel-head"><div><p class="eyebrow">ZADNJE AŽURIRANO</p><h2>Aktivni dealovi</h2></div><a class="text-btn" href="{{ route('deals.create') }}">＋ Novi deal</a></div>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Prilika</th><th>Tvrtka</th><th>Faza</th><th>Vrijednost</th><th>Zatvaranje</th></tr></thead><tbody>
        @forelse($deals as $deal)<tr onclick="location.href='{{ route('deals.show', $deal) }}'"><td><strong>{{ $deal->title }}</strong></td><td>{{ $deal->company?->name ?? '—' }}</td><td><x-status :status="$deal->stage" /></td><td><strong>{{ $deal->currency }} {{ number_format($deal->value, 0, ',', '.') }}</strong></td><td>{{ $deal->expected_close_date?->format('d.m.Y.') ?? '—' }}</td></tr>@empty<tr><td colspan="5">Još nema dealova.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
