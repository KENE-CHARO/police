<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Commissariat;
use Laravel\Sanctum\Sanctum;

class AdminCommissariatTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_commissariat()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        Sanctum::actingAs($admin);

        $payload = [
            'nom' => 'Commissariat Test',
            'adresse' => 'Rue Test 123',
            'telephone' => '+221770000000',
        ];

        $res = $this->postJson('/api/admin/commissariats', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('commissariat.nom', 'Commissariat Test');

        $this->assertDatabaseHas('commissariats', ['nom' => 'Commissariat Test']);
    }

    public function test_non_admin_cannot_create_commissariat()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'nom' => 'Illegal',
        ];

        $res = $this->postJson('/api/admin/commissariats', $payload);
        $res->assertStatus(403);
    }
}
