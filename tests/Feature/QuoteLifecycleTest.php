<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_quote_can_be_duplicated_as_a_new_draft(): void
    {
        $quote = $this->quote(QuoteStatus::Sent);
        $quote->items()->create(['description' => 'Usluga', 'quantity' => 2, 'unit' => 'dan', 'unit_price' => 500, 'line_total' => 1000, 'position' => 1]);

        $this->actingAs($this->user)->post(route('quotes.duplicate', $quote))->assertRedirect();

        $copy = Quote::where('id', '!=', $quote->id)->with('items')->firstOrFail();
        $this->assertSame(QuoteStatus::Draft, $copy->status);
        $this->assertStringContainsString('(kopija)', $copy->title);
        $this->assertStringStartsWith('AF-'.now()->year.'-', $copy->number);
        $this->assertNotSame($quote->number, $copy->number);
        $this->assertCount(1, $copy->items);
        $this->assertSame('1000.00', $copy->items->first()->line_total);
    }

    public function test_accepting_a_quote_marks_the_deal_as_won(): void
    {
        $quote = $this->quote(QuoteStatus::Sent);

        $this->actingAs($this->user)->patch(route('quotes.status', $quote), ['status' => QuoteStatus::Accepted->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
        $this->assertSame(DealStage::Won, $quote->deal->fresh()->stage);
        $this->assertDatabaseHas('activities', ['deal_id' => $quote->deal_id, 'subject' => 'Faza prilike: Pregovori → Dobiveno']);
    }

    public function test_expire_command_closes_outdated_sent_quotes(): void
    {
        $outdated = $this->quote(QuoteStatus::Sent, ['valid_until' => now()->subDay()]);
        $valid = $this->quote(QuoteStatus::Sent, ['valid_until' => now()->addWeek()]);
        $accepted = $this->quote(QuoteStatus::Accepted, ['valid_until' => now()->subWeek()]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Expired, $outdated->fresh()->status);
        $this->assertSame(QuoteStatus::Sent, $valid->fresh()->status);
        $this->assertSame(QuoteStatus::Accepted, $accepted->fresh()->status);
        $this->assertDatabaseHas('activities', ['quote_id' => $outdated->id, 'subject' => 'Status ponude: Poslano → Isteklo']);
    }

    public function test_expired_quotes_are_flagged_in_the_list(): void
    {
        $this->quote(QuoteStatus::Sent, ['valid_until' => now()->subDay()]);

        $this->actingAs($this->user)->get(route('quotes.index'))->assertOk()->assertSee('istekla');
    }

    public function test_quote_export_and_pdf_still_work(): void
    {
        $quote = $this->quote(QuoteStatus::Draft);
        $quote->items()->create(['description' => 'Usluga', 'quantity' => 1, 'unit' => 'kom', 'unit_price' => 100, 'line_total' => 100, 'position' => 1]);

        $export = $this->actingAs($this->user)->get(route('quotes.export'));
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($quote->number, $export->streamedContent());

        $this->actingAs($this->user)->get(route('quotes.pdf', $quote))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function quote(QuoteStatus $status, array $overrides = []): Quote
    {
        $company = Company::create(['owner_id' => $this->user->id, 'name' => 'Acme', 'status' => CompanyStatus::Customer, 'country' => 'HR']);
        $deal = $this->user->deals()->create(['company_id' => $company->id, 'title' => 'Prilika', 'stage' => DealStage::Negotiation, 'value' => 1000, 'currency' => 'EUR', 'probability' => 70]);

        return $this->user->quotes()->create([
            'deal_id' => $deal->id,
            'company_id' => $company->id,
            'number' => 'AF-'.now()->year.'-'.fake()->unique()->numberBetween(100, 999),
            'title' => 'Ponuda',
            'status' => $status,
            'issue_date' => now(),
            'valid_until' => now()->addDays(14),
            'currency' => 'EUR',
            'discount_percent' => 0,
            'tax_percent' => 25,
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 250,
            'total' => 1250,
            ...$overrides,
        ]);
    }
}
