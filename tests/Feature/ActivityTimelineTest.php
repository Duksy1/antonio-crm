<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_log_and_complete_an_activity(): void
    {
        $company = $this->company();

        $this->actingAs($this->user)->post(route('activities.store'), [
            'type' => ActivityType::Call->value,
            'subject' => 'Nazvati klijenta',
            'company_id' => $company->id,
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $activity = Activity::where('subject', 'Nazvati klijenta')->firstOrFail();
        $this->assertSame(ActivityType::Call, $activity->type);
        $this->assertSame($this->user->id, $activity->owner_id);
        $this->assertNull($activity->completed_at);

        $this->actingAs($this->user)->get(route('activities.index'))->assertOk()->assertSee('Nazvati klijenta');

        $this->actingAs($this->user)->patch(route('activities.toggle', $activity))->assertRedirect();
        $this->assertNotNull($activity->fresh()->completed_at);
    }

    public function test_notes_are_logged_as_completed_and_point_to_record(): void
    {
        $company = $this->company();

        $this->actingAs($this->user)->post(route('activities.store'), [
            'type' => ActivityType::Note->value,
            'subject' => 'Bilješka s poziva',
            'company_id' => $company->id,
        ])->assertRedirect();

        $this->assertNotNull(Activity::where('subject', 'Bilješka s poziva')->firstOrFail()->completed_at);
        $this->actingAs($this->user)->get(route('companies.show', $company))
            ->assertOk()->assertSee('Bilješka s poziva')->assertSee('Povijest');
    }

    public function test_scopes_filter_activities_and_ownership_is_enforced(): void
    {
        Activity::factory()->for($this->user, 'owner')->dueAt(now()->subDay())->create(['subject' => 'Kasni zadatak']);
        Activity::factory()->for($this->user, 'owner')->dueAt(now()->addDays(4))->create(['subject' => 'Budući zadatak']);

        $this->actingAs($this->user)->get(route('activities.index', ['scope' => 'overdue']))
            ->assertOk()->assertSee('Kasni zadatak')->assertDontSee('Budući zadatak');

        $other = Activity::factory()->create(['subject' => 'Tuđi zadatak']);

        $this->actingAs($this->user)->patch(route('activities.toggle', $other))->assertNotFound();
        $this->actingAs($this->user)->delete(route('activities.destroy', $other))->assertNotFound();
    }

    public function test_record_changes_are_logged_automatically(): void
    {
        $company = $this->company();
        $company->update(['status' => CompanyStatus::Customer]);

        $contact = $this->user->contacts()->create(['company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Anić', 'status' => ContactStatus::New]);
        $contact->update(['status' => ContactStatus::Active]);

        $deal = $this->user->deals()->create(['company_id' => $company->id, 'title' => 'Prilika', 'stage' => DealStage::Qualification, 'value' => 5000, 'currency' => 'EUR', 'probability' => 20]);
        $deal->update(['stage' => DealStage::Won, 'value' => 6500]);

        $this->assertDatabaseHas('activities', ['type' => 'system', 'company_id' => $company->id, 'subject' => 'Status tvrtke: Lead → Klijent']);
        $this->assertDatabaseHas('activities', ['type' => 'system', 'contact_id' => $contact->id, 'subject' => 'Status kontakta: Novi → Aktivan']);
        $this->assertDatabaseHas('activities', ['type' => 'system', 'deal_id' => $deal->id, 'subject' => 'Faza prilike: Kvalifikacija → Dobiveno']);
        $this->assertDatabaseHas('activities', ['type' => 'system', 'deal_id' => $deal->id, 'subject' => 'Vrijednost prilike: 5.000,00 → 6.500,00 EUR']);
    }

    public function test_system_entries_cannot_be_changed_or_deleted(): void
    {
        $activity = Activity::factory()->system()->for($this->user, 'owner')->create();

        $this->actingAs($this->user)->patch(route('activities.toggle', $activity))->assertNotFound();
        $this->actingAs($this->user)->delete(route('activities.destroy', $activity))->assertNotFound();
    }

    public function test_activities_can_be_exported_as_csv(): void
    {
        Activity::factory()->for($this->user, 'owner')->create(['subject' => 'Izvezi me']);

        $response = $this->actingAs($this->user)->get(route('activities.export'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Izvezi me', $response->streamedContent());
    }

    private function company(): Company
    {
        return $this->user->companies()->create(['name' => 'Acme', 'status' => CompanyStatus::Lead, 'country' => 'HR']);
    }
}
