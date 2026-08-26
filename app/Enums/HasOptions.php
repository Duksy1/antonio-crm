<?php

namespace App\Enums;

trait HasOptions
{
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label(), 'color' => $case->color()],
            self::cases(),
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
