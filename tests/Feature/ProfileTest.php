<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => bcrypt('secret-password')]);
    }

    public function test_profile_page_shows_workspace_summary(): void
    {
        Company::create(['owner_id' => $this->user->id, 'name' => 'Acme', 'status' => CompanyStatus::Lead, 'country' => 'HR']);

        $this->actingAs($this->user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($this->user->name)
            ->assertSee('Sažetak radnog prostora')
            ->assertSee('Promjena lozinke');
    }

    public function test_user_can_update_name_and_email(): void
    {
        $this->actingAs($this->user)->put(route('profile.update'), ['name' => 'Ana Vuković', 'email' => 'ana@apexflow-crm.test'])
            ->assertRedirect();

        $this->assertSame('Ana Vuković', $this->user->fresh()->name);
        $this->assertSame('ana@apexflow-crm.test', $this->user->fresh()->email);
    }

    public function test_email_must_stay_unique(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->user)->put(route('profile.update'), ['name' => 'Ana', 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_password_can_be_changed_with_current_password(): void
    {
        $this->actingAs($this->user)->put(route('profile.password'), [
            'current_password' => 'pogresna',
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($this->user)->put(route('profile.password'), [
            'current_password' => 'secret-password',
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NovaLozinka123', $this->user->fresh()->password));
    }
}
