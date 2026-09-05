<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AgentListEnqueteursTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_accueil_can_list_only_active_enqueteurs()
    {
        // create roles
        $roleAgent = Role::firstOrCreate(['name' => 'agent_accueil']);
        $roleEnqueteur = Role::firstOrCreate(['name' => 'enqueteur']);

        // create agent_accueil user
        $agent = User::factory()->create(['name' => 'Agent Test', 'email' => 'accueil_test@police.local']);
        $agent->roles()->attach($roleAgent->id);

        // create enqueteur active
        $enqActive = User::factory()->create(['name' => 'Enq Actif', 'email' => 'enq_active@police.local', 'is_active' => true]);
        $enqActive->roles()->attach($roleEnqueteur->id);

        // create enqueteur inactive
        $enqInactive = User::factory()->create(['name' => 'Enq Inactif', 'email' => 'enq_inactive@police.local', 'is_active' => false]);
        $enqInactive->roles()->attach($roleEnqueteur->id);

        // create unrelated user
        $other = User::factory()->create(['name' => 'Citoyen', 'email' => 'citoyen@police.local']);

        Sanctum::actingAs($agent);

        $res = $this->getJson('/api/admin/enqueteurs');

        $res->assertStatus(200);

        $data = $res->json();
        // endpoint returns array
        $this->assertIsArray($data);

        $emails = array_column($data, 'email');

        $this->assertContains('enq_active@police.local', $emails);
        $this->assertNotContains('enq_inactive@police.local', $emails);
        $this->assertNotContains('citoyen@police.local', $emails);

        // each returned user should include roles and one role should be 'enqueteur'
        foreach ($data as $userPayload) {
            $this->assertArrayHasKey('roles', $userPayload);
            $this->assertIsArray($userPayload['roles']);
            $roleNames = array_column($userPayload['roles'], 'name');
            $this->assertContains('enqueteur', $roleNames);
        }
    }
}
