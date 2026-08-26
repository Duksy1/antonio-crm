@props(['listRoute', 'boardRoute', 'filterName', 'options', 'placeholder' => 'Pretraži…', 'board' => false])
<div class="view-toolbar">
    @unless($board)
    <form class="search-form" method="GET" action="{{ $listRoute }}">
        <div class="search-box"><svg viewBox="0 0 24 24"><path d="m19.6 21-6.3-6.3a7 7 0 1 1 1.4-1.4l6.3 6.3-1.4 1.4ZM8 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}"></div>
        <select class="filter-select" name="{{ $filterName }}" onchange="this.form.submit()"><option value="">Svi statusi</option>@foreach($options as $option)<option value="{{ $option->value }}" @selected(request($filterName) === $option->value)>{{ $option->label() }}</option>@endforeach</select>
    </form>
    @else <span class="board-tip"><i></i> Promijenite status izravno na kartici</span>
    @endunless
    <div class="view-switch"><a href="{{ $listRoute }}" class="{{ !$board ? 'active' : '' }}">▦ Popis</a><a href="{{ $boardRoute }}" class="{{ $board ? 'active' : '' }}">☷ Kanban</a></div>
</div>
