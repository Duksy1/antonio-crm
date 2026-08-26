<?php

namespace App\Enums;

enum CompanyStatus: string
{
    use HasOptions;

    case Lead = 'lead';
    case Prospect = 'prospect';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead', self::Prospect => 'Potencijalni klijent', self::Customer => 'Klijent', self::Inactive => 'Neaktivan'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lead => 'violet', self::Prospect => 'blue', self::Customer => 'emerald', self::Inactive => 'slate'
        };
    }
}
