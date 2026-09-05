<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;

class PlainteRecevableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_status_in_list_but_cannot_view_details()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $plaignant = User::factory()->create();

        $plainte = Plainte::create([
            'reference' => 'TEST-REF-1',
            'plaignant_id' => $plaignant->id,
            'titre' => 'Test plainte',
            'description' => 'Description',
        ]);

        Sanctum::actingAs($admin);

        $list = $this->getJson('/api/plaintes');
        $list->assertStatus(200);
        $this->assertEquals('nouveau', data_get($list->json(), 'data.0.statut'));

        $show = $this->getJson('/api/plaintes/' . $plainte->id);
        $show->assertStatus(403);
    }

    public function test_admin_cannot_set_recevable()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $plaignant = User::factory()->create();

        $plainte = Plainte::create([
            'reference' => 'TEST-REF-2',
            'plaignant_id' => $plaignant->id,
            'titre' => 'Test plainte 2',
            'description' => 'Description',
        ]);

        Sanctum::actingAs($admin);

        $res = $this->putJson('/api/plaintes/' . $plainte->id . '/recevabilite', ['recevable' => true]);
        $res->assertStatus(403);
    }

    public function test_agent_can_set_recevable()
    {
        $role = Role::firstOrCreate(['name' => 'agent_accueil']);
        $agent = User::factory()->create();
        $agent->roles()->attach($role->id);

        $plaignant = User::factory()->create();

        $plainte = Plainte::create([
            'reference' => 'TEST-REF-3',
            'plaignant_id' => $plaignant->id,
            'titre' => 'Test plainte 3',
            'description' => 'Description',
        ]);

        Sanctum::actingAs($agent);

        $res = $this->putJson('/api/plaintes/' . $plainte->id . '/recevabilite', ['recevable' => true]);
        $res->assertStatus(200);

        $this->assertDatabaseHas('plaintes', ['id' => $plainte->id, 'recevable' => 1]);
    }
}
