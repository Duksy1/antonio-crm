<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_archived_companies_can_be_listed_and_restored(): void
    {
        $company = $this->company('Arhivirana tvrtka');
        $company->delete();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        $this->actingAs($this->user)->get(route('companies.index'))->assertOk()->assertDontSee('Arhivirana tvrtka');

        $this->actingAs($this->user)->get(route('companies.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee('Arhivirana tvrtka')
            ->assertSee('Vrati');

        $this->actingAs($this->user)->patch(route('companies.restore', $company->id))
            ->assertRedirect(route('companies.show', $company->id));

        $this->assertNotSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_archived_company_can_be_deleted_permanently(): void
    {
        $company = $this->company('Za brisanje');
        $company->delete();

        $this->actingAs($this->user)->delete(route('companies.force-destroy', $company->id))
            ->assertRedirect(route('companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_other_users_cannot_restore_or_purge_records(): void
    {
        $other = User::factory()->create();
        $company = Company::create(['owner_id' => $other->id, 'name' => 'Tuđa tvrtka', 'status' => CompanyStatus::Lead, 'country' => 'HR']);
        $company->delete();

        $this->actingAs($this->user)->patch(route('companies.restore', $company->id))->assertNotFound();
        $this->actingAs($this->user)->delete(route('companies.force-destroy', $company->id))->assertNotFound();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_company_export_respects_active_filters(): void
    {
        $this->company('Zagrebačka Tvrtka');
        $this->company('Splitska Tvrtka');
        $this->company('Zagrebački Obrt');

        $response = $this->actingAs($this->user)->get(route('companies.export', ['search' => 'Zagreb']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Zagrebačka Tvrtka', $csv);
        $this->assertStringContainsString('Zagrebački Obrt', $csv);
        $this->assertStringNotContainsString('Splitska Tvrtka', $csv);
    }

    public function test_lists_support_sorting_and_page_size(): void
    {
        $this->company('Alfa');
        $this->company('Beta');

        $this->actingAs($this->user)->get(route('companies.index', ['sort' => 'name', 'direction' => 'asc', 'per_page' => 24]))
            ->assertOk()->assertSee('Alfa')->assertSee('Beta');

        $this->actingAs($this->user)->get(route('companies.index', ['sort' => 'nepostojeci', 'per_page' => 999]))
            ->assertOk();
    }

    private function company(string $name): Company
    {
        return Company::create(['owner_id' => $this->user->id, 'name' => $name, 'status' => CompanyStatus::Customer, 'country' => 'HR']);
    }
}
