@extends('layouts.app')
@section('title','Aktivnosti')
@section('content')
<x-page-header eyebrow="ZADACI I POVIJEST" title="Aktivnosti" description="Zadaci, pozivi, sastanci i automatska povijest promjena na jednom mjestu." />

<section class="metric-grid metric-grid-quad">
    <article class="metric-card {{ $stats['overdue'] > 0 ? 'warn' : '' }}"><div class="metric-top"><span>Kasni</span><b class="metric-icon">!</b></div><strong>{{ $stats['overdue'] }}</strong><p>zadataka izvan roka</p></article>
    <article class="metric-card"><div class="metric-top"><span>Danas</span><b class="metric-icon">◷</b></div><strong>{{ $stats['today'] }}</strong><p>zadataka s rokom danas</p></article>
    <article class="metric-card"><div class="metric-top"><span>Otvoreno</span><b class="metric-icon">✓</b></div><strong>{{ $stats['open'] }}</strong><p>ukupno otvorenih zadataka</p></article>
    <article class="metric-card"><div class="metric-top"><span>Zatvoreno</span><b class="metric-icon">↗</b></div><strong>{{ $stats['completedThisWeek'] }}</strong><p>dovršeno u zadnjih 7 dana</p></article>
</section>

<section class="panel composer-panel">
    <div class="panel-head"><div><p class="eyebrow">BRZI UNOS</p><h2>Nova aktivnost</h2></div><span class="panel-hint">Tip „Bilješka” se odmah bilježi kao dovršeno.</span></div>
    <x-activity-form :company-id="request('company_id')" />
</section>

<div class="scope-tabs">
    @foreach (['open' => 'Otvoreno', 'today' => 'Danas', 'overdue' => 'Kasni', 'completed' => 'Dovršeno', 'all' => 'Sve'] as $key => $label)
        <a class="{{ $scope === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['scope' => $key, 'page' => null]) }}">
            {{ $label }}@if ($key === 'overdue' && $stats['overdue'])<b>{{ $stats['overdue'] }}</b>@endif
        </a>
    @endforeach
</div>

<div class="form-shell wide">
    <x-view-toolbar :list-route="route('activities.index')" filter-name="type" :options="App\Enums\ActivityType::cases()" placeholder="Pretraži naslove zadataka…" filter-label="Svi tipovi" :extra="['scope' => $scope]" />
    <x-list-tools export-route="activities.export" :per-page-options="[20, 50, 100]" />
    <x-timeline :items="$activities" empty-title="Nema aktivnosti u ovom prikazu" empty-text="Promijenite filter ili dodajte novi zadatak u brzom unosu." />
    <div class="pagination-wrap">{{ $activities->links() }}</div>
</div>
@endsection
