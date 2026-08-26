<?php

namespace App\Enums;

enum DealStage: string
{
    use HasOptions;

    case Qualification = 'qualification';
    case Discovery = 'discovery';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Qualification => 'Kvalifikacija', self::Discovery => 'Analiza potreba', self::Proposal => 'Ponuda', self::Negotiation => 'Pregovori', self::Won => 'Dobiveno', self::Lost => 'Izgubljeno'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Qualification => 'violet', self::Discovery => 'blue', self::Proposal => 'cyan', self::Negotiation => 'amber', self::Won => 'emerald', self::Lost => 'rose'
        };
    }
}
