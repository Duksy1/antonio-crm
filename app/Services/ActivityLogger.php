<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\Activity;

final class ActivityLogger
{
    /**
     * Zapisuje aktivnost u vremensku crtu. Sustavski zapisi se odmah smatraju
     * dovršenima kako ne bi završili među otvorenim zadacima.
     *
     * @param  array{owner_id?: int, company_id?: int|null, contact_id?: int|null, deal_id?: int|null, quote_id?: int|null}  $context
     * @param  array{notes?: string|null, due_at?: mixed, completed_at?: mixed}  $attributes
     */
    public function log(ActivityType $type, string $subject, array $context = [], array $attributes = []): ?Activity
    {
        $ownerId = $context['owner_id'] ?? auth()->id();

        if ($ownerId === null) {
            return null;
        }

        return Activity::create([
            'owner_id' => $ownerId,
            'company_id' => $context['company_id'] ?? null,
            'contact_id' => $context['contact_id'] ?? null,
            'deal_id' => $context['deal_id'] ?? null,
            'quote_id' => $context['quote_id'] ?? null,
            'type' => $type,
            'subject' => $subject,
            'notes' => $attributes['notes'] ?? null,
            'due_at' => $attributes['due_at'] ?? null,
            'completed_at' => $attributes['completed_at'] ?? ($type === ActivityType::System ? now() : null),
        ]);
    }
}
