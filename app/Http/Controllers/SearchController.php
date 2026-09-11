<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const LIMIT = 8;

    public function __invoke(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $term = trim((string) $request->string('q'));

        return view('search.index', [
            'term' => $term,
            'groups' => $term === '' ? [] : $this->groups($term, $request->user()->id),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string, total: int, index: string, items: array<int, array<string, mixed>>}>
     */
    private function groups(string $term, int $ownerId): array
    {
        $like = '%'.$term.'%';

        $companies = fn (): Builder => Company::query()->where('owner_id', $ownerId)
            ->where(fn (Builder $query) => $query->where('name', 'ilike', $like)->orWhere('industry', 'ilike', $like)->orWhere('city', 'ilike', $like)->orWhere('email', 'ilike', $like));

        $contacts = fn (): Builder => Contact::query()->where('owner_id', $ownerId)
            ->where(fn (Builder $query) => $query->where('first_name', 'ilike', $like)->orWhere('last_name', 'ilike', $like)->orWhere('email', 'ilike', $like)->orWhere('job_title', 'ilike', $like));

        $deals = fn (): Builder => Deal::query()->where('owner_id', $ownerId)
            ->where(fn (Builder $query) => $query->where('title', 'ilike', $like)->orWhere('description', 'ilike', $like));

        $quotes = fn (): Builder => Quote::query()->where('owner_id', $ownerId)
            ->where(fn (Builder $query) => $query->where('number', 'ilike', $like)->orWhere('title', 'ilike', $like));

        $activities = fn (): Builder => Activity::query()->where('owner_id', $ownerId)
            ->where(fn (Builder $query) => $query->where('subject', 'ilike', $like)->orWhere('notes', 'ilike', $like));

        return array_values(array_filter([
            $this->group('companies', 'Tvrtke', route('companies.index', ['search' => $term]), $companies(),
                fn (Company $company) => [
                    'title' => $company->name,
                    'meta' => $this->meta([$company->industry, $company->city]),
                    'status' => $company->status,
                    'url' => route('companies.show', $company),
                ]),
            $this->group('contacts', 'Kontakti', route('contacts.index', ['search' => $term]), $contacts(),
                fn (Contact $contact) => [
                    'title' => $contact->full_name,
                    'meta' => $this->meta([$contact->job_title, $contact->company?->name]),
                    'status' => $contact->status,
                    'url' => route('contacts.show', $contact),
                ], ['company:id,name']),
            $this->group('deals', 'Prilike', route('deals.index', ['search' => $term]), $deals(),
                fn (Deal $deal) => [
                    'title' => $deal->title,
                    'meta' => $this->meta([$deal->company?->name, $deal->currency.' '.number_format((float) $deal->value, 0, ',', '.')]),
                    'status' => $deal->stage,
                    'url' => route('deals.show', $deal),
                ], ['company:id,name']),
            $this->group('quotes', 'Ponude', route('quotes.index', ['search' => $term]), $quotes(),
                fn (Quote $quote) => [
                    'title' => $quote->number.' · '.$quote->title,
                    'meta' => $this->meta([$quote->company?->name, $quote->currency.' '.number_format((float) $quote->total, 2, ',', '.')]),
                    'status' => $quote->status,
                    'url' => route('quotes.show', $quote),
                ], ['company:id,name']),
            $this->group('activities', 'Zadaci i bilješke', route('activities.index', ['search' => $term, 'scope' => 'all']), $activities(),
                fn (Activity $activity) => [
                    'title' => $activity->subject,
                    'meta' => $this->meta([$activity->type->label(), $activity->company?->name, $activity->due_at?->format('d.m.Y. H:i')]),
                    'status' => $activity->type,
                    'url' => route('activities.index', ['search' => $term, 'scope' => 'all']),
                ], ['company:id,name']),
        ], fn (array $group) => $group['total'] > 0));
    }

    /**
     * @param  array<int, string|null>  $parts
     */
    private function meta(array $parts): string
    {
        return trim(implode(' · ', array_filter($parts, fn (?string $part) => filled($part)))) ?: 'Bez dodatnih podataka';
    }

    /**
     * @param  array<int, string>  $with
     * @param  callable(mixed): array<string, mixed>  $map
     * @return array{key: string, label: string, total: int, index: string, items: array<int, array<string, mixed>>}
     */
    private function group(string $key, string $label, string $index, Builder $query, callable $map, array $with = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'index' => $index,
            'total' => (clone $query)->count(),
            'items' => $query->with($with)->orderBy('id', 'desc')->limit(self::LIMIT)->get()->map($map)->all(),
        ];
    }
}
