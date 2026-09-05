<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Commissariat;
use Laravel\Sanctum\Sanctum;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_and_remove_role()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/admin/users/' . $user->id . '/roles', ['role' => 'enqueteur']);
        $res->assertStatus(200)->assertJsonFragment(['name' => 'enqueteur']);

        $res2 = $this->deleteJson('/api/admin/users/' . $user->id . '/roles', ['role' => 'enqueteur']);
        $res2->assertStatus(200);
    }

    public function test_non_admin_cannot_assign_role()
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/admin/users/' . $target->id . '/roles', ['role' => 'enqueteur']);
        $res->assertStatus(403);
    }

    public function test_staff_registers_with_pending_validation()
    {
        Commissariat::create([
            'nom' => 'Commissariat central',
            'adresse' => 'Centre-ville',
            'telephone' => '+237000000000',
        ]);

        $payload = [
            'name' => 'Alice Citizen',
            'email' => 'alice.citizen@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'agent_accueil',
            'commissariat_id' => 1,
        ];

        $res = $this->postJson('/api/auth/register/staff', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('user.email', 'alice.citizen@example.com');

        $this->assertDatabaseHas('role_user', [
            'user_id' => $res->json('user.id'),
        ]);

        $user = User::find($res->json('user.id'));
        $this->assertTrue($user->roles()->where('name', 'agent_accueil')->exists());
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_staff_requires_commissariat_on_registration()
    {
        $payload = [
            'name' => 'No Commissariat',
            'email' => 'no.commissariat@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'agent_accueil',
        ];

        $res = $this->postJson('/api/auth/register/staff', $payload);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['commissariat_id']);
    }

    public function test_admin_can_activate_staff_account()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $staff = User::factory()->create([
            'email' => 'staff.pending@example.com',
            'is_active' => false,
        ]);
        $staff->roles()->attach(Role::firstOrCreate(['name' => 'agent_accueil'])->id);

        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/admin/users/' . $staff->id . '/activate');

        $res->assertStatus(200)
            ->assertJsonPath('user.is_active', true);

        $this->assertTrue((bool) $staff->fresh()->is_active);
    }

    public function test_admin_can_delete_user_account()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $user = User::factory()->create([
            'email' => 'delete.user@example.com',
        ]);

        Sanctum::actingAs($admin);

        $res = $this->deleteJson('/api/admin/users/' . $user->id);

        $res->assertStatus(200)
            ->assertJsonFragment(['message' => 'Compte utilisateur supprimé avec succès.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
