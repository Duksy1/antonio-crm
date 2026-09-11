<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Controllers\Concerns\SortsAndPaginates;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    use SortsAndPaginates;

    private const SCOPES = ['open', 'today', 'overdue', 'completed', 'all'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $this->scopeFrom($request);

        $activities = $this->filtered($request, $scope)
            ->with(['company:id,name', 'contact:id,first_name,last_name', 'deal:id,title', 'quote:id,number'])
            ->when($scope === 'open', fn (Builder $query) => $query->orderByRaw('due_at asc nulls last')->orderByDesc('created_at'))
            ->when($scope !== 'open', fn (Builder $query) => $query->latest())
            ->paginate($this->perPageFrom($request, 20, [20, 50, 100]))
            ->withQueryString();

        return view('activities.index', [
            'activities' => $activities,
            'scope' => $scope,
            'stats' => $this->stats($user->id),
            'companies' => $user->companies()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = ActivityType::from($data['type']);

        $request->user()->activities()->create([
            ...$this->payload($data),
            'type' => $type,
            'completed_at' => $request->boolean('done') || ! $type->isActionable() ? now() : null,
        ]);

        return back()->with('success', 'Aktivnost je zabilježena.');
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->assertEditable($request, $activity);

        $data = $request->validated();
        $type = ActivityType::from($data['type']);

        $activity->update([
            ...$this->payload($data),
            'type' => $type,
            'completed_at' => match (true) {
                $request->boolean('done') => $activity->completed_at ?? now(),
                $type->isActionable() => null,
                default => $activity->completed_at ?? now(),
            },
        ]);

        return back()->with('success', 'Aktivnost je ažurirana.');
    }

    public function toggle(Request $request, Activity $activity): RedirectResponse
    {
        $this->assertEditable($request, $activity);

        $activity->update(['completed_at' => $activity->isCompleted() ? null : now()]);

        return back()->with('success', $activity->isCompleted() ? 'Zadatak je dovršen.' : 'Zadatak je ponovno otvoren.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $this->assertEditable($request, $activity);
        $activity->delete();

        return back()->with('success', 'Aktivnost je obrisana.');
    }

    public function export(Request $request): StreamedResponse
    {
        $scope = $this->scopeFrom($request);

        $activities = $this->filtered($request, $scope)
            ->with(['company:id,name', 'contact:id,first_name,last_name', 'deal:id,title'])
            ->orderByDesc('created_at')
            ->get();

        $rows = $activities->map(fn (Activity $activity) => [
            $activity->type->label(),
            $activity->subject,
            $activity->company?->name ?? '',
            $activity->contact?->full_name ?? '',
            $activity->deal?->title ?? '',
            $activity->due_at?->format('Y-m-d H:i') ?? '',
            $activity->isCompleted() ? 'Da' : 'Ne',
            $activity->completed_at?->format('Y-m-d H:i') ?? '',
            $activity->created_at->format('Y-m-d H:i'),
        ]);

        return Csv::stream('aktivnosti-'.now()->format('Y-m-d').'.csv', [
            'Tip', 'Naslov', 'Tvrtka', 'Kontakt', 'Prilika', 'Rok', 'Dovršeno', 'Dovršeno u', 'Izrađeno',
        ], $rows);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'subject' => $data['subject'],
            'notes' => $data['notes'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'quote_id' => $data['quote_id'] ?? null,
        ];
    }

    private function filtered(Request $request, string $scope): Builder
    {
        return Activity::query()->ownedBy($request->user()->id)
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('search'), fn (Builder $query) => $query->where('subject', 'ilike', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('company_id'), fn (Builder $query) => $query->where('company_id', $request->integer('company_id')))
            ->when($scope === 'open', fn (Builder $query) => $query->tasks()->whereNull('completed_at'))
            ->when($scope === 'today', fn (Builder $query) => $query->tasks()->whereNull('completed_at')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]))
            ->when($scope === 'overdue', fn (Builder $query) => $query->tasks()->overdue())
            ->when($scope === 'completed', fn (Builder $query) => $query->tasks()->whereNotNull('completed_at'));
    }

    private function scopeFrom(Request $request): string
    {
        $scope = $request->string('scope')->toString();

        return in_array($scope, self::SCOPES, true) ? $scope : 'open';
    }

    /**
     * @return array{overdue: int, today: int, open: int, completedThisWeek: int}
     */
    private function stats(int $ownerId): array
    {
        $base = fn () => Activity::query()->ownedBy($ownerId)->tasks();

        return [
            'overdue' => $base()->overdue()->count(),
            'today' => $base()->whereNull('completed_at')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'open' => $base()->whereNull('completed_at')->count(),
            'completedThisWeek' => $base()->where('completed_at', '>=', now()->subDays(7))->count(),
        ];
    }

    private function assertEditable(Request $request, Activity $activity): void
    {
        abort_unless($activity->owner_id === $request->user()->id, 404);
        abort_if($activity->isSystem(), 404);
    }
}
