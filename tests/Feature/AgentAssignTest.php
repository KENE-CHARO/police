<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use App\Notifications\EnqueteAssigned;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class AgentAssignTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_accueil_can_assign_plainte_to_enqueteur()
    {
        $agentRole = Role::firstOrCreate(['name' => 'agent_accueil']);
        $enqueteurRole = Role::firstOrCreate(['name' => 'enqueteur']);

        $agent = User::factory()->create();
        $agent->roles()->attach($agentRole->id);

        $enqueteur = User::factory()->create();
        $enqueteur->roles()->attach($enqueteurRole->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['reference' => 'REF-AGENT-1', 'plaignant_id' => $plaignant->id, 'titre' => 'Assign test', 'description' => 'Desc']);

        Sanctum::actingAs($agent);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201)->assertJsonFragment(['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);

        $this->assertDatabaseHas('enquetes', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);

        // check notification record exists for enqueteur and content
        $this->assertDatabaseHas('notifications', ['user_id' => $enqueteur->id, 'type' => EnqueteAssigned::class]);

        $notif = DB::table('notifications')->where('user_id', $enqueteur->id)->first();
        $this->assertNotNull($notif);
        $data = json_decode($notif->data, true);
        $this->assertEquals($plainte->id, $data['plainte_id']);
    }
}
