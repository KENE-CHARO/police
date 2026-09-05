<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use App\Models\Enquete;
use App\Models\Historique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class EnqueteurHistoriquesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_enqueteur_can_only_access_historiques_for_assigned_plainte()
    {
        $roleEnqueteur = Role::firstOrCreate(['name' => 'enqueteur']);
        $roleCitoyen = Role::firstOrCreate(['name' => 'citoyen']);

        // create enqueteur
        $enq = User::factory()->create(['name' => 'Enq Historique', 'email' => 'enq_hist@police.local']);
        $enq->roles()->attach($roleEnqueteur->id);

        // create plaintes
        $plaignant1 = User::factory()->create(['email' => 'hp1@police.local']);
        $plaignant1->roles()->attach($roleCitoyen->id);
        $plainte1 = Plainte::factory()->create(['plaignant_id' => $plaignant1->id]);

        $plaignant2 = User::factory()->create(['email' => 'hp2@police.local']);
        $plaignant2->roles()->attach($roleCitoyen->id);
        $plainte2 = Plainte::factory()->create(['plaignant_id' => $plaignant2->id]);

        // create historique entries for both plaintes
        Historique::create([
            'subject_type' => Plainte::class,
            'subject_id' => $plainte1->id,
            'user_id' => $plaignant1->id,
            'action' => 'created',
            'details' => json_encode(['info' => 'first']),
        ]);

        Historique::create([
            'subject_type' => Plainte::class,
            'subject_id' => $plainte2->id,
            'user_id' => $plaignant2->id,
            'action' => 'created',
            'details' => json_encode(['info' => 'second']),
        ]);

        // assign only plainte1 to enqueteur
        Enquete::create(['plainte_id' => $plainte1->id, 'enqueteur_id' => $enq->id]);

        Sanctum::actingAs($enq);

        // should be allowed to fetch historiques for assigned plainte
        $res1 = $this->getJson('/api/plaintes/' . $plainte1->id . '/historiques');
        $res1->assertStatus(200);
        $data1 = $res1->json();
        $this->assertNotEmpty($data1);
        $this->assertEquals(Plainte::class, $data1[0]['subject_type']);

        // should be forbidden for unassigned plainte
        $res2 = $this->getJson('/api/plaintes/' . $plainte2->id . '/historiques');
        $res2->assertStatus(403);
    }
}
