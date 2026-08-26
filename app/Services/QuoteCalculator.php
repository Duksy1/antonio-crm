<?php

namespace App\Services;

final class QuoteCalculator
{
    public function calculate(array $items, float $discountPercent, float $taxPercent): array
    {
        $normalized = collect($items)->values()->map(function (array $item, int $position) {
            $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

            return [...$item, 'line_total' => $lineTotal, 'position' => $position];
        });

        $subtotal = round($normalized->sum('line_total'), 2);
        $discountTotal = round($subtotal * $discountPercent / 100, 2);
        $taxable = $subtotal - $discountTotal;
        $taxTotal = round($taxable * $taxPercent / 100, 2);

        return [
            'items' => $normalized->all(),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => round($taxable + $taxTotal, 2),
        ];
    }
}
