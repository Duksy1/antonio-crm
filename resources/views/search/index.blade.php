@extends('layouts.app')
@section('title','Pretraga')
@section('content')
<x-page-header eyebrow="GLOBALNA PRETRAGA" title="Pretraga" :description="$term !== '' ? 'Rezultati za „'.$term.'”' : 'Pretražite tvrtke, kontakte, prilike, ponude i aktivnosti.'">
    <form class="search-form" method="GET" action="{{ route('search') }}">
        <div class="search-box"><svg viewBox="0 0 24 24"><path d="m19.6 21-6.3-6.3a7 7 0 1 1 1.4-1.4l6.3 6.3-1.4 1.4ZM8 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/></svg><input type="search" name="q" value="{{ $term }}" placeholder="npr. Orbita, AF-2026, Ana…" autofocus></div>
        <button class="primary-btn" type="submit">Pretraži</button>
    </form>
</x-page-header>

@if ($term === '')
    <x-empty title="Upišite pojam za pretragu" text="Pretraga obuhvaća sve module i radi i po djelomičnom poklapanju." />
@elseif (empty($groups))
    <x-empty title="Nema rezultata za „{{ $term }}”" text="Pokušajte s drugim pojmom ili provjerite arhivirane zapise." />
@else
    <div class="search-groups">
        @foreach ($groups as $group)
            <section class="panel search-group">
                <div class="panel-head"><div><p class="eyebrow">{{ mb_strtoupper($group['label']) }}</p><h2>{{ $group['label'] }} <small>({{ $group['total'] }})</small></h2></div><a href="{{ $group['index'] }}">Svi rezultati →</a></div>
                <div class="search-list">
                    @foreach ($group['items'] as $item)
                        <a class="search-row" href="{{ $item['url'] }}"><span class="search-row-body"><strong>{{ $item['title'] }}</strong><small>{{ $item['meta'] }}</small></span><x-status :status="$item['status']" /></a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
@endsection
