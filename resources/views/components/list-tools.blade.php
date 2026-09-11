@props(['columns' => [], 'exportRoute' => null, 'archived' => false, 'archivedCount' => 0, 'perPageOptions' => [12, 24, 48, 96]])
<div class="list-tools">
    <form class="list-tools-form" method="GET" action="">
        @foreach (request()->except(['sort', 'direction', 'per_page', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        @if ($columns)
            <label class="tool-field">Sortiraj
                <select class="filter-select" name="sort" onchange="this.form.submit()">
                    @foreach ($columns as $value => $label)
                        <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="tool-field">Smjer
                <select class="filter-select" name="direction" onchange="this.form.submit()">
                    <option value="desc" @selected(request('direction') !== 'asc')>Silazno</option>
                    <option value="asc" @selected(request('direction') === 'asc')>Uzlazno</option>
                </select>
            </label>
        @endif
        <label class="tool-field">Po stranici
            <select class="filter-select" name="per_page" onchange="this.form.submit()">
                @foreach ($perPageOptions as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', $perPageOptions[0]) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </label>
        <input type="hidden" name="page" value="1">
    </form>
    <div class="list-tools-actions">
        @if ($archivedCount > 0)
            <a class="chip-btn {{ $archived ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['archived' => $archived ? null : 1, 'page' => null]) }}">
                {{ $archived ? '↩ Aktivni zapisi' : '🗄 Arhiva ('.$archivedCount.')' }}
            </a>
        @elseif ($archived)
            <a class="chip-btn active" href="{{ request()->fullUrlWithQuery(['archived' => null, 'page' => null]) }}">↩ Aktivni zapisi</a>
        @endif
        @if ($exportRoute)
            <a class="chip-btn" href="{{ route($exportRoute, request()->query()) }}">⤓ CSV izvoz</a>
        @endif
    </div>
</div>
