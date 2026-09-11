<?php

namespace App\Enums;

enum ActivityType: string
{
    use HasOptions;

    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case Task = 'task';
    case Note = 'note';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Poziv', self::Meeting => 'Sastanak', self::Email => 'E-mail', self::Task => 'Zadatak', self::Note => 'Bilješka', self::System => 'Sustav'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'blue', self::Meeting => 'violet', self::Email => 'cyan', self::Task => 'amber', self::Note => 'slate', self::System => 'emerald'
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => '☎', self::Meeting => '◷', self::Email => '✉', self::Task => '✓', self::Note => '✎', self::System => '⚙'
        };
    }

    /**
     * Tipovi koje korisnik može ručno unijeti (sustavski zapisi nisu dio forme).
     *
     * @return array<int, self>
     */
    public static function selectable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type !== self::System));
    }

    /**
     * Bilješka je zapis o prošlosti, ostali tipovi mogu biti otvoreni zadaci.
     */
    public function isActionable(): bool
    {
        return $this !== self::Note && $this !== self::System;
    }
}
