<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['owner_id', 'deal_id', 'company_id', 'contact_id', 'number', 'title', 'status', 'issue_date', 'valid_until', 'currency', 'discount_percent', 'tax_percent', 'subtotal', 'discount_total', 'tax_total', 'total', 'notes', 'terms'];

    protected function casts(): array
    {
        return ['status' => QuoteStatus::class, 'issue_date' => 'date', 'valid_until' => 'date', 'discount_percent' => 'decimal:2', 'tax_percent' => 'decimal:2', 'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }

    public function isExpired(): bool
    {
        return $this->status === QuoteStatus::Sent && $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [QuoteStatus::Draft, QuoteStatus::Sent], true);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
