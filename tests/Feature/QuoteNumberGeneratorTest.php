<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class QuoteNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_atomically_increments_a_yearly_counter(): void
    {
        $generator = app(QuoteNumberGenerator::class);

        $this->assertSame('AC-2030-0001', $generator->next(2030));
        $this->assertSame('AC-2030-0002', $generator->next(2030));
        $this->assertSame('AC-2031-0001', $generator->next(2031));

        $this->assertDatabaseHas('quote_number_counters', ['year' => 2030, 'last_number' => 2]);
        $this->assertDatabaseHas('quote_number_counters', ['year' => 2031, 'last_number' => 1]);
    }

    public function test_it_continues_after_an_existing_quote_when_a_counter_does_not_exist(): void
    {
        $user = User::factory()->create();
        $user->quotes()->create([
            'number' => 'AC-2032-0042',
            'title' => 'Postojeća ponuda',
            'status' => QuoteStatus::Draft,
            'issue_date' => '2032-01-01',
            'currency' => 'EUR',
            'discount_percent' => 0,
            'tax_percent' => 25,
        ]);

        $this->assertSame('AC-2032-0043', app(QuoteNumberGenerator::class)->next(2032));
    }

    public function test_counter_migration_backfills_existing_quote_numbers(): void
    {
        $user = User::factory()->create();
        $user->quotes()->create([
            'number' => 'AC-2034-0123',
            'title' => 'Ponuda prije migracije',
            'status' => QuoteStatus::Draft,
            'issue_date' => '2034-01-01',
            'currency' => 'EUR',
            'discount_percent' => 0,
            'tax_percent' => 25,
        ]);

        Schema::drop('quote_number_counters');
        $migration = require database_path('migrations/2026_08_28_000100_create_quote_number_counters_table.php');
        $migration->up();

        $this->assertDatabaseHas('quote_number_counters', ['year' => 2034, 'last_number' => 123]);
        $this->assertSame('AC-2034-0124', app(QuoteNumberGenerator::class)->next(2034));
    }

    public function test_a_rolled_back_transaction_does_not_consume_a_number(): void
    {
        $generator = app(QuoteNumberGenerator::class);

        try {
            DB::transaction(function () use ($generator) {
                $this->assertSame('AC-2033-0001', $generator->next(2033));

                throw new RuntimeException('Rollback testne transakcije.');
            });
        } catch (RuntimeException) {
            // Očekivani rollback.
        }

        $this->assertSame('AC-2033-0001', $generator->next(2033));
    }

    public function test_demo_seeder_uses_the_counter_and_decimal_calculator(): void
    {
        $this->seed();

        $quotes = Quote::orderBy('id')->get();

        $this->assertCount(4, $quotes);
        $this->assertSame([
            'AC-'.now()->year.'-0001',
            'AC-'.now()->year.'-0002',
            'AC-'.now()->year.'-0003',
            'AC-'.now()->year.'-0004',
        ], $quotes->pluck('number')->all());
        $this->assertSame('32000.00', $quotes->first()->subtotal);
        $this->assertSame('8000.00', $quotes->first()->tax_total);
        $this->assertSame('40000.00', $quotes->first()->total);
        $this->assertDatabaseHas('quote_number_counters', ['year' => now()->year, 'last_number' => 4]);
    }
}
