<?php

namespace App\Enums;

enum ContactStatus: string
{
    use HasOptions;

    case New = 'new';
    case Active = 'active';
    case DecisionMaker = 'decision_maker';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novi', self::Active => 'Aktivan', self::DecisionMaker => 'Donositelj odluke', self::Inactive => 'Neaktivan'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'violet', self::Active => 'blue', self::DecisionMaker => 'amber', self::Inactive => 'slate'
        };
    }
}
