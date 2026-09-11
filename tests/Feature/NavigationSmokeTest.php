<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_every_primary_page_renders_for_the_demo_workspace(): void
    {
        $company = Company::create(['owner_id' => $this->user->id, 'name' => 'Acme d.o.o.', 'status' => CompanyStatus::Customer, 'country' => 'HR']);
        $contact = Contact::create(['owner_id' => $this->user->id, 'company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Anić', 'status' => ContactStatus::Active]);
        $deal = $this->user->deals()->create(['company_id' => $company->id, 'contact_id' => $contact->id, 'title' => 'CRM projekat', 'stage' => DealStage::Won, 'value' => 15000, 'currency' => 'EUR', 'probability' => 100]);
        $quote = Quote::create([
            'owner_id' => $this->user->id, 'deal_id' => $deal->id, 'company_id' => $company->id, 'contact_id' => $contact->id,
            'number' => 'AF-2026-0001', 'title' => 'Ponuda', 'status' => QuoteStatus::Sent, 'issue_date' => now(),
            'valid_until' => now()->addDays(10), 'currency' => 'EUR', 'discount_percent' => 0, 'tax_percent' => 25,
            'subtotal' => 1000, 'discount_total' => 0, 'tax_total' => 250, 'total' => 1250,
        ]);
        $this->user->activities()->create(['company_id' => $company->id, 'deal_id' => $deal->id, 'quote_id' => $quote->id, 'type' => ActivityType::Task, 'subject' => 'Nazvati klijenta', 'due_at' => now()->addHours(2)]);

        $pages = [
            route('dashboard'),
            route('companies.index'),
            route('companies.board'),
            route('companies.create'),
            route('companies.show', $company),
            route('companies.edit', $company),
            route('contacts.index'),
            route('contacts.board'),
            route('contacts.create'),
            route('contacts.show', $contact),
            route('contacts.edit', $contact),
            route('deals.index'),
            route('deals.board'),
            route('deals.create'),
            route('deals.show', $deal),
            route('deals.edit', $deal),
            route('quotes.index'),
            route('quotes.board'),
            route('quotes.create'),
            route('quotes.show', $quote),
            route('quotes.edit', $quote),
            route('activities.index'),
            route('activities.index', ['scope' => 'all']),
            route('activities.index', ['scope' => 'overdue']),
            route('profile.edit'),
            route('search', ['q' => 'Acme']),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->user)->get($url)->assertOk();
        }

        $this->actingAs($this->user)->get(route('dashboard'))->assertSee('Stopa dobitka')->assertSee('Nazvati klijenta');
    }

    public function test_login_page_renders_with_demo_helper(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Popuni demo podatke')
            ->assertSee('demo@apexflow-crm.test')
            ->assertSee('Apex');
    }
}
