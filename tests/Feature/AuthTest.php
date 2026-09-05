<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Commissariat;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_user_and_token()
    {
        $payload = [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'email', 'name'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
    }

    public function test_login_returns_token()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_login_returns_user_roles_for_admin_account()
    {
        $role = \App\Models\Role::create(['name' => 'admin']);
        $user = User::factory()->create([
            'email' => 'admin@police.local',
            'password' => 'secret123',
        ]);
        $user->roles()->sync([$role->id]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@police.local',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.roles.0.name', 'admin');
    }

    public function test_citizen_registration_creates_active_account()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Citoyen Test',
            'email' => 'citoyen@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'citoyen',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.roles.0.name', 'citoyen');

        $this->assertDatabaseHas('users', [
            'email' => 'citoyen@test.com',
            'is_active' => true,
        ]);
    }

    public function test_staff_registration_requires_admin_validation()
    {
        Commissariat::create([
            'nom' => 'Commissariat central',
            'adresse' => 'Centre-ville',
            'telephone' => '+237000000000',
        ]);

        $response = $this->postJson('/api/auth/register/staff', [
            'name' => 'Agent Test',
            'email' => 'agent@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'agent_accueil',
            'commissariat_id' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.roles.0.name', 'agent_accueil');

        $this->assertDatabaseHas('users', [
            'email' => 'agent@test.com',
            'is_active' => false,
        ]);
    }

    public function test_inactive_staff_cannot_login()
    {
        $role = \App\Models\Role::create(['name' => 'agent_accueil']);
        $user = User::factory()->create([
            'email' => 'staff.inactive@test.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);
        $user->roles()->sync([$role->id]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'staff.inactive@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Votre compte est en attente de validation par l\'administrateur.']);
    }

    public function test_authenticated_user_can_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'profil@test.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => 'Nouveau Nom',
            'email' => 'profil.new@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Nouveau Nom')
            ->assertJsonPath('email', 'profil.new@test.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
            'email' => 'profil.new@test.com',
        ]);
    }

    public function test_change_password_requires_current_password()
    {
        $user = User::factory()->create([
            'email' => 'password@test.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($user)->putJson('/api/auth/password', [
            'current_password' => 'wrong-password',
            'password' => 'newSecret456',
            'password_confirmation' => 'newSecret456',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Le mot de passe actuel est incorrect.']);
    }

    public function test_authenticated_user_can_change_email_address()
    {
        $user = User::factory()->create([
            'name' => 'Email User',
            'email' => 'old@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => 'Email User',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_avatar_url()
    {
        $user = User::factory()->create([
            'email' => 'avatar@test.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('avatar_url', 'https://example.com/avatar.png');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar_url' => 'https://example.com/avatar.png',
        ]);
    }

    public function test_authenticated_user_can_delete_own_account()
    {
        $user = User::factory()->create([
            'email' => 'delete@test.com',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/auth/account');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Compte supprimé avec succès.');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
