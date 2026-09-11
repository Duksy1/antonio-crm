<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'company_id', 'contact_id', 'deal_id', 'quote_id', 'type', 'subject', 'notes', 'due_at', 'completed_at'];

    protected function casts(): array
    {
        return ['type' => ActivityType::class, 'due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function scopeOwnedBy(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeTasks(Builder $query): Builder
    {
        return $query->where('type', '!=', ActivityType::System);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->open()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]);
    }

    public function isSystem(): bool
    {
        return $this->type === ActivityType::System;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isCompleted() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function isDoneToday(): bool
    {
        return $this->completed_at !== null && $this->completed_at->isToday();
    }

    /**
     * Kontekstualne poveznice na povezane zapise (tvrtka, kontakt, deal, ponuda).
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function contextLinks(): array
    {
        $links = [];

        if ($this->company) {
            $links[] = ['label' => $this->company->name, 'url' => route('companies.show', $this->company)];
        }

        if ($this->contact) {
            $links[] = ['label' => $this->contact->full_name, 'url' => route('contacts.show', $this->contact)];
        }

        if ($this->deal) {
            $links[] = ['label' => $this->deal->title, 'url' => route('deals.show', $this->deal)];
        }

        if ($this->quote) {
            $links[] = ['label' => $this->quote->number, 'url' => route('quotes.show', $this->quote)];
        }

        return $links;
    }
}
