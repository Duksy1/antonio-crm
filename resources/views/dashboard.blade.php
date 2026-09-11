@extends('layouts.app')
@section('title', 'Pregled')
@section('content')
<x-page-header eyebrow="PREGLED POSLOVANJA" title="Dobro jutro, {{ explode(' ', auth()->user()->name)[0] }}."><a class="secondary-btn" href="{{ route('activities.index', ['scope' => 'open']) }}">Moji zadaci ({{ $stats['openTasks'] }})</a></x-page-header>

<section class="metric-grid">
    <article class="metric-card accent"><div class="metric-top"><span>Vrijednost pipelinea</span><i>↗</i></div><strong>€{{ number_format($stats['pipeline'], 0, ',', '.') }}</strong><p>{{ $stats['openDeals'] }} aktivnih prilika · prognoza €{{ number_format($stats['forecast'], 0, ',', '.') }}</p><div class="spark-bars"><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
    <article class="metric-card {{ $stats['overdueTasks'] > 0 ? 'warn' : '' }}"><div class="metric-top"><span>Otvoreni zadaci</span><b class="metric-icon">✓</b></div><strong>{{ $stats['openTasks'] }}</strong><p>{{ $stats['dueTodayTasks'] }} danas · {{ $stats['overdueTasks'] }} kasni</p></article>
    <article class="metric-card"><div class="metric-top"><span>Stopa dobitka</span><b class="metric-icon">%</b></div><strong>{{ $stats['winRate'] === null ? '—' : $stats['winRate'].'%' }}</strong><p>dobiveno / zatvoreno</p></article>
    <article class="metric-card"><div class="metric-top"><span>Dobiveno ovaj mjesec</span><b class="metric-icon">€</b></div><strong>€{{ number_format($stats['wonThisMonth'], 0, ',', '.') }}</strong><p>ukupno €{{ number_format($stats['wonValue'], 0, ',', '.') }}</p></article>
</section>

<section class="stat-strip">
    <span><small>Tvrtke</small><strong>{{ $stats['companies'] }}</strong></span>
    <span><small>Kontakti</small><strong>{{ $stats['contacts'] }}</strong></span>
    <span><small>Ponude u čekanju</small><strong>{{ $stats['awaitingQuotes'] }}</strong></span>
    <span><small>Vrijednost ponuda u čekanju</small><strong>€{{ number_format($stats['awaitingQuotesValue'], 0, ',', '.') }}</strong></span>
    <span><small>Prihvaćene ponude</small><strong>€{{ number_format($stats['acceptedQuotes'], 0, ',', '.') }}</strong></span>
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

    <section class="panel agenda-panel">
        <div class="panel-head"><div><p class="eyebrow">RADNI PLAN</p><h2>Zadaci po roku</h2></div><a href="{{ route('activities.index', ['scope' => 'all']) }}">Sve aktivnosti →</a></div>
        <div class="agenda-list">
            @forelse($agenda as $task)
                <div class="agenda-row {{ $task->isOverdue() ? 'is-overdue' : '' }}">
                    <span class="timeline-icon color-{{ $task->type->color() }}">{{ $task->type->icon() }}</span>
                    <div><strong>{{ $task->subject }}</strong><small>{{ $task->due_at?->format('d.m.Y. H:i') }}@if ($task->company) · {{ $task->company->name }}@endif</small></div>
                    <form method="POST" action="{{ route('activities.toggle', $task) }}">@csrf @method('PATCH')<button class="chip-btn" type="submit" title="Označi dovršeno">✓</button></form>
                </div>
            @empty
                <div class="side-empty"><strong>Nema zakazanih zadataka</strong><p>Dodajte zadatak u brzom unosu na stranici Aktivnosti.</p></div>
            @endforelse
        </div>
    </section>
</div>

<div class="dashboard-grid">
    <section class="panel activity-panel">
        <div class="panel-head"><div><p class="eyebrow">NAJNOVIJE</p><h2>Ponude</h2></div><a href="{{ route('quotes.index') }}">Sve ponude →</a></div>
        <div class="mini-list">
            @forelse($quotes as $quote)
                <a href="{{ route('quotes.show', $quote) }}"><span class="doc-icon">▤</span><span><strong>{{ $quote->number }}</strong><small>{{ $quote->company?->name ?? 'Bez tvrtke' }}</small></span><span class="mini-amount">€{{ number_format($quote->total, 0, ',', '.') }}<x-status :status="$quote->status" /></span></a>
            @empty <x-empty title="Još nema ponuda" text="Izradite prvu ponudu iz deala." /> @endforelse
        </div>
    </section>

    <section class="panel activity-panel">
        <div class="panel-head"><div><p class="eyebrow">VREMENSKA CRTA</p><h2>Zadnje aktivnosti</h2></div><a href="{{ route('activities.index', ['scope' => 'all']) }}">Otvori →</a></div>
        <x-timeline :items="$timeline" empty-title="Još nema aktivnosti" empty-text="Svaka promjena statusa, faze ili ponude zapisuje se ovdje." />
    </section>
</div>

<section class="panel recent-panel">
    <div class="panel-head"><div><p class="eyebrow">ZADNJE AŽURIRANO</p><h2>Aktivni dealovi</h2></div><a class="text-btn" href="{{ route('deals.create') }}">＋ Novi deal</a></div>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Prilika</th><th>Tvrtka</th><th>Faza</th><th>Vrijednost</th><th>Zatvaranje</th></tr></thead><tbody>
        @forelse($deals as $deal)<tr onclick="location.href='{{ route('deals.show', $deal) }}'"><td><strong>{{ $deal->title }}</strong></td><td>{{ $deal->company?->name ?? '—' }}</td><td><x-status :status="$deal->stage" /></td><td><strong>{{ $deal->currency }} {{ number_format($deal->value, 0, ',', '.') }}</strong></td><td>{{ $deal->expected_close_date?->format('d.m.Y.') ?? '—' }}</td></tr>@empty<tr><td colspan="5">Još nema dealova.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
