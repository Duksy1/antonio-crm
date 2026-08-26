<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => bcrypt('secret-password')]);
    }

    public function test_login_requires_valid_credentials_and_regenerates_session(): void
    {
        $this->post('/login', ['email' => $this->user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_authenticated_user_can_create_every_crm_entity(): void
    {
        $companyResponse = $this->actingAs($this->user)->post(route('companies.store'), [
            'name' => 'Acme d.o.o.', 'status' => CompanyStatus::Lead->value, 'country' => 'HR',
        ]);
        $company = Company::firstOrFail();
        $companyResponse->assertRedirect(route('companies.show', $company));

        $contactResponse = $this->post(route('contacts.store'), [
            'company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Anić',
            'status' => ContactStatus::Active->value, 'email' => 'ana@acme.test',
        ]);
        $contact = Contact::firstOrFail();
        $contactResponse->assertRedirect(route('contacts.show', $contact));

        $dealResponse = $this->post(route('deals.store'), [
            'company_id' => $company->id, 'contact_id' => $contact->id, 'title' => 'CRM implementacija',
            'stage' => DealStage::Proposal->value, 'value' => 10000, 'currency' => 'EUR', 'probability' => 60,
        ]);
        $deal = Deal::firstOrFail();
        $dealResponse->assertRedirect(route('deals.show', $deal));

        $quoteResponse = $this->post(route('quotes.store'), [
            'deal_id' => $deal->id, 'company_id' => $company->id, 'contact_id' => $contact->id,
            'title' => 'Ponuda za CRM', 'status' => QuoteStatus::Draft->value,
            'issue_date' => now()->toDateString(), 'valid_until' => now()->addDays(14)->toDateString(),
            'currency' => 'EUR', 'discount_percent' => 10, 'tax_percent' => 25,
            'items' => [['description' => 'Implementacija', 'quantity' => 2, 'unit' => 'dan', 'unit_price' => 5000]],
        ]);
        $quote = Quote::with('items')->firstOrFail();
        $quoteResponse->assertRedirect(route('quotes.show', $quote));
        $this->assertSame('10000.00', $quote->subtotal);
        $this->assertSame('11250.00', $quote->total);
        $this->assertCount(1, $quote->items);
    }

    public function test_kanban_status_endpoint_updates_deal_stage(): void
    {
        $company = $this->company();
        $deal = $this->user->deals()->create([
            'company_id' => $company->id, 'title' => 'Pipeline deal', 'stage' => DealStage::Qualification,
            'value' => 5000, 'currency' => 'EUR', 'probability' => 20,
        ]);

        $this->actingAs($this->user)->patch(route('deals.stage', $deal), ['stage' => DealStage::Negotiation->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(DealStage::Negotiation, $deal->fresh()->stage);
        $this->get(route('deals.board'))->assertOk()->assertSee('Pipeline deal');
    }

    public function test_user_cannot_access_another_users_records(): void
    {
        $other = User::factory()->create();
        $company = $other->companies()->create(['name' => 'Privatna tvrtka', 'status' => CompanyStatus::Customer, 'country' => 'HR']);

        $this->actingAs($this->user)->get(route('companies.show', $company))->assertNotFound();
        $this->patch(route('companies.status', $company), ['status' => CompanyStatus::Inactive->value])->assertNotFound();
    }

    public function test_quote_can_be_exported_as_pdf(): void
    {
        $company = $this->company();
        $quote = $this->user->quotes()->create([
            'company_id' => $company->id, 'number' => 'AC-2026-0001', 'title' => 'Test ponuda',
            'status' => QuoteStatus::Draft, 'issue_date' => now(), 'currency' => 'EUR',
            'discount_percent' => 0, 'tax_percent' => 25, 'subtotal' => 100, 'discount_total' => 0,
            'tax_total' => 25, 'total' => 125,
        ]);
        $quote->items()->create(['description' => 'Usluga', 'quantity' => 1, 'unit' => 'kom', 'unit_price' => 100, 'line_total' => 100]);

        $this->actingAs($this->user)->get(route('quotes.pdf', $quote))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    private function company(): Company
    {
        return $this->user->companies()->create(['name' => 'Acme', 'status' => CompanyStatus::Lead, 'country' => 'HR']);
    }
}
