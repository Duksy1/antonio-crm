@props(['title', 'text'])
<div class="empty-state"><span>✦</span><h3>{{ $title }}</h3><p>{{ $text }}</p>{{ $slot }}</div>
