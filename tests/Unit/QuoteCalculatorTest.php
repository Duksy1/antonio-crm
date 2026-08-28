<?php

namespace Tests\Unit;

use App\Services\QuoteCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QuoteCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_money_with_deterministic_decimal_rounding(): void
    {
        $totals = (new QuoteCalculator)->calculate([
            ['description' => 'Precizna stavka', 'quantity' => '0.33', 'unit' => 'kom', 'unit_price' => '0.15'],
            ['description' => 'Osnovna stavka', 'quantity' => '2', 'unit' => 'kom', 'unit_price' => '10'],
        ], '12.50', '25');

        $this->assertSame('0.05', $totals['items'][0]['line_total']);
        $this->assertSame('20.05', $totals['subtotal']);
        $this->assertSame('2.51', $totals['discount_total']);
        $this->assertSame('4.39', $totals['tax_total']);
        $this->assertSame('21.93', $totals['total']);
    }

    #[Test]
    public function it_never_converts_decimal_inputs_to_binary_floats(): void
    {
        $totals = (new QuoteCalculator)->calculate([
            ['description' => 'Mala vrijednost', 'quantity' => '0.10', 'unit' => 'kom', 'unit_price' => '0.20'],
        ], 0, 0);

        $this->assertSame('0.10', $totals['items'][0]['quantity']);
        $this->assertSame('0.20', $totals['items'][0]['unit_price']);
        $this->assertSame('0.02', $totals['subtotal']);
        $this->assertSame('0.02', $totals['total']);
    }
}
