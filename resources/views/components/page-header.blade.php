@props(['eyebrow', 'title', 'description' => null])
<div class="page-header">
    <div><p class="eyebrow">{{ $eyebrow }}</p><h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif</div>
    <div class="page-actions">{{ $slot }}</div>
</div>
