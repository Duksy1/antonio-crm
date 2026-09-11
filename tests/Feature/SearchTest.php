<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_global_search_finds_records_across_modules(): void
    {
        $company = Company::create(['owner_id' => $this->user->id, 'name' => 'Orbita Digital', 'status' => CompanyStatus::Customer, 'country' => 'HR']);
        Contact::create(['owner_id' => $this->user->id, 'company_id' => $company->id, 'first_name' => 'Marta', 'last_name' => 'Kovač', 'status' => ContactStatus::Active]);
        $deal = $this->user->deals()->create(['company_id' => $company->id, 'title' => 'Orbita onboarding', 'stage' => DealStage::Proposal, 'value' => 10000, 'currency' => 'EUR', 'probability' => 50]);
        $quote = $this->user->quotes()->create([
            'deal_id' => $deal->id, 'company_id' => $company->id, 'number' => 'AF-2026-0009', 'title' => 'Orbita ponuda',
            'status' => QuoteStatus::Sent, 'issue_date' => now(), 'currency' => 'EUR',
            'discount_percent' => 0, 'tax_percent' => 25, 'subtotal' => 100, 'discount_total' => 0, 'tax_total' => 25, 'total' => 125,
        ]);
        $this->user->activities()->create(['company_id' => $company->id, 'type' => ActivityType::Task, 'subject' => 'Orbita follow-up']);

        $response = $this->actingAs($this->user)->get(route('search', ['q' => 'Orbita']));

        $response->assertOk()
            ->assertSee('Tvrtke')
            ->assertSee('Orbita Digital')
            ->assertSee('Orbita onboarding')
            ->assertSee($quote->number)
            ->assertSee('Orbita follow-up');

        $this->actingAs($this->user)->get(route('search', ['q' => 'Kovač']))
            ->assertOk()->assertSee('Marta Kovač');

        $this->actingAs($this->user)->get(route('search', ['q' => 'AF-2026']))
            ->assertOk()->assertSee($quote->number);
    }

    public function test_search_never_leaks_other_users_records(): void
    {
        $other = User::factory()->create();
        Company::create(['owner_id' => $other->id, 'name' => 'Tajna Tvrtka', 'status' => CompanyStatus::Lead, 'country' => 'HR']);

        $this->actingAs($this->user)->get(route('search', ['q' => 'Tajna']))
            ->assertOk()
            ->assertSee('Nema rezultata')
            ->assertDontSee('Tajna Tvrtka');
    }

    public function test_empty_search_shows_instructions(): void
    {
        $this->actingAs($this->user)->get(route('search'))->assertOk()->assertSee('Upišite pojam za pretragu');
    }

    public function test_search_requires_authentication(): void
    {
        $this->get(route('search', ['q' => 'test']))->assertRedirect(route('login'));
    }
}
