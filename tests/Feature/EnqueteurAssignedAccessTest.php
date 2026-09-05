<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use App\Models\Enquete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class EnqueteurAssignedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_enqueteur_can_only_view_assigned_plainte()
    {
        $roleEnqueteur = Role::firstOrCreate(['name' => 'enqueteur']);
        $roleCitoyen = Role::firstOrCreate(['name' => 'citoyen']);

        // create enqueteur
        $enq = User::factory()->create(['name' => 'Enq', 'email' => 'enq1@police.local', 'is_active' => true]);
        $enq->roles()->attach($roleEnqueteur->id);

        // create two citoyens and plaintes
        $plaignant1 = User::factory()->create(['email' => 'p1@police.local']);
        $plaignant1->roles()->attach($roleCitoyen->id);
        $plainte1 = Plainte::factory()->create(['plaignant_id' => $plaignant1->id]);

        $plaignant2 = User::factory()->create(['email' => 'p2@police.local']);
        $plaignant2->roles()->attach($roleCitoyen->id);
        $plainte2 = Plainte::factory()->create(['plaignant_id' => $plaignant2->id]);

        // assign only plainte1 to enqueteur
        Enquete::create(['plainte_id' => $plainte1->id, 'enqueteur_id' => $enq->id]);

        Sanctum::actingAs($enq);

        // should be allowed for assigned
        $res1 = $this->getJson('/api/plaintes/' . $plainte1->id);
        $res1->assertStatus(200);

        // should be forbidden for unassigned
        $res2 = $this->getJson('/api/plaintes/' . $plainte2->id);
        $res2->assertStatus(403);
    }
}
