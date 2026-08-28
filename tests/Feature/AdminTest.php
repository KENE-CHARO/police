<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
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
}
