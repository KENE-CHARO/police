<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;

class HistoriqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_historiques_recorded_on_assign_and_status_change()
    {
        $role = Role::create(['name' => 'enqueteur']);

        $enqueteur = User::factory()->create();
        $enqueteur->roles()->attach($role->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$plaignant->id,'reference'=>'R1']);

        Sanctum::actingAs($enqueteur);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201);
        $enqueteId = $res->json('id');

        $update = $this->putJson('/api/enquetes/' . $enqueteId . '/status', ['statut' => 'closed']);
        $update->assertStatus(200);

        $hist = $this->getJson('/api/plaintes/' . $plainte->id . '/historiques');
        $hist->assertStatus(200)->assertJsonCount(2);
    }
}
