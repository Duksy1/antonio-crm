@props(['items', 'emptyTitle' => 'Nema aktivnosti', 'emptyText' => 'Povijest se puni automatski čim se zapisi mijenjaju.', 'showContext' => true])
<div class="timeline">
    @forelse ($items as $activity)
        <article class="timeline-item {{ $activity->isSystem() ? 'is-system' : '' }} {{ $activity->isOverdue() ? 'is-overdue' : '' }}">
            <span class="timeline-icon color-{{ $activity->type->color() }}" title="{{ $activity->type->label() }}">{{ $activity->type->icon() }}</span>
            <div class="timeline-body">
                <div class="timeline-head">
                    <strong>{{ $activity->subject }}</strong>
                    <span class="timeline-meta">{{ $activity->created_at->diffForHumans() }}@if ($activity->owner) · {{ $activity->owner->name }}@endif</span>
                </div>
                @if ($activity->notes)<p>{{ $activity->notes }}</p>@endif
                <div class="timeline-foot">
                    <span class="type-chip color-{{ $activity->type->color() }}">{{ $activity->type->label() }}</span>
                    @if ($activity->due_at)<span class="due-chip {{ $activity->isOverdue() ? 'overdue' : '' }}">rok {{ $activity->due_at->format('d.m.Y. H:i') }}</span>@endif
                    @if ($activity->isCompleted())<span class="done-chip">✓ dovršeno</span>@endif
                    @if ($showContext)
                        @foreach ($activity->contextLinks() as $link)
                            <a class="context-chip" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    @endif
                    @unless ($activity->isSystem())
                        <form method="POST" action="{{ route('activities.toggle', $activity) }}">@csrf @method('PATCH')<button class="text-btn" type="submit">{{ $activity->isCompleted() ? 'Ponovno otvori' : 'Označi dovršeno' }}</button></form>
                        <form method="POST" action="{{ route('activities.destroy', $activity) }}" onsubmit="return confirm('Obrisati ovu aktivnost?')">@csrf @method('DELETE')<button class="text-btn is-danger" type="submit">Obriši</button></form>
                    @endunless
                </div>
            </div>
        </article>
    @empty
        <div class="side-empty"><strong>{{ $emptyTitle }}</strong><p>{{ $emptyText }}</p></div>
    @endforelse
</div>
