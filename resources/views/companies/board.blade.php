@extends('layouts.app')
@section('title','Tvrtke · Kanban')
@section('content')
<x-page-header eyebrow="ODNOSI / KANBAN" title="Tvrtke po statusu"><a class="primary-btn" href="{{ route('companies.create') }}">＋ Nova tvrtka</a></x-page-header>
<x-view-toolbar :list-route="route('companies.index')" :board-route="route('companies.board')" filter-name="status" :options="App\Enums\CompanyStatus::cases()" :board="true" />
<div class="board-wrap"><div class="kanban">
@foreach(App\Enums\CompanyStatus::cases() as $status)<section class="kanban-column"><header class="kanban-column-head"><span class="kanban-column-title color-{{ $status->color() }}"><i></i>{{ $status->label() }}</span><span class="kanban-count">{{ $items->get($status->value,collect())->count() }}</span></header><div class="kanban-cards">
@forelse($items->get($status->value,collect()) as $company)<article class="kanban-card"><a href="{{ route('companies.show',$company) }}"><h3>{{ $company->name }}</h3><p>{{ $company->industry ?: 'Bez industrije' }}</p><div class="kanban-value">{{ $company->annual_revenue ? '€'.number_format($company->annual_revenue,0,',','.') : '—' }}</div></a><div class="kanban-card-footer"><span>{{ $company->contacts_count }} kontakata</span><form method="POST" action="{{ route('companies.status',$company) }}">@csrf @method('PATCH')<select class="move-select" name="status" onchange="this.form.submit()">@foreach(App\Enums\CompanyStatus::cases() as $move)<option value="{{ $move->value }}" @selected($move===$company->status)>{{ $move->label() }}</option>@endforeach</select></form></div></article>@empty<div class="board-empty">Nema tvrtki</div>@endforelse
</div></section>@endforeach
</div></div>
@endsection
