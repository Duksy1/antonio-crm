<?php

namespace App\Enums;

enum QuoteStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Skica', self::Sent => 'Poslano', self::Accepted => 'Prihvaćeno', self::Declined => 'Odbijeno', self::Expired => 'Isteklo'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate', self::Sent => 'blue', self::Accepted => 'emerald', self::Declined => 'rose', self::Expired => 'amber'
        };
    }
}
