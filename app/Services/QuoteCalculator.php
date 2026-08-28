<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class QuoteCalculator
{
    private const int MONEY_SCALE = 2;

    public function calculate(array $items, string|int $discountPercent, string|int $taxPercent): array
    {
        $subtotal = BigDecimal::zero()->toScale(self::MONEY_SCALE);
        $normalized = [];

        foreach (array_values($items) as $position => $item) {
            $quantity = BigDecimal::of((string) $item['quantity']);
            $unitPrice = BigDecimal::of((string) $item['unit_price']);
            $lineTotal = $quantity
                ->multipliedBy($unitPrice)
                ->toScale(self::MONEY_SCALE, RoundingMode::HalfUp);

            $subtotal = $subtotal->plus($lineTotal);
            $normalized[] = [
                ...$item,
                'quantity' => (string) $quantity->toScale(2),
                'unit_price' => (string) $unitPrice->toScale(self::MONEY_SCALE),
                'line_total' => (string) $lineTotal,
                'position' => $position,
            ];
        }

        $subtotal = $subtotal->toScale(self::MONEY_SCALE);
        $discountTotal = $this->percentageOf($subtotal, $discountPercent);
        $taxable = $subtotal->minus($discountTotal);
        $taxTotal = $this->percentageOf($taxable, $taxPercent);
        $total = $taxable->plus($taxTotal)->toScale(self::MONEY_SCALE);

        return ['items' => $normalized, 'subtotal' => (string) $subtotal, 'discount_total' => (string) $discountTotal, 'tax_total' => (string) $taxTotal, 'total' => (string) $total];
    }

    private function percentageOf(BigDecimal $amount, string|int $percentage): BigDecimal
    {
        return $amount
            ->multipliedBy(BigDecimal::of((string) $percentage))
            ->dividedBy(100, self::MONEY_SCALE, RoundingMode::HalfUp);
    }
}
