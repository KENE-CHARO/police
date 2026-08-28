<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_create_plainte_for_another_user()
    {
        // create role
        $role = Role::create(['name' => 'agent_accueil']);

        $agent = User::factory()->create();
        $agent->roles()->attach($role->id);

        $plaignant = User::factory()->create();

        Sanctum::actingAs($agent);

        $payload = [
            'titre' => 'Plainte agent',
            'description' => 'Créée par agent',
            'plaignant_id' => $plaignant->id,
        ];

        $res = $this->postJson('/api/agent/plaintes', $payload);
        $res->assertStatus(201)->assertJsonFragment(['titre' => 'Plainte agent', 'plaignant_id' => $plaignant->id]);
    }
}
