<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_sent_and_retrievable()
    {
        $role = Role::create(['name' => 'enqueteur']);
        $enqueteur = User::factory()->create();
        $enqueteur->roles()->attach($role->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$plaignant->id,'reference'=>'R1']);

        Sanctum::actingAs($enqueteur);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201);

        // enqueteur should have notifications
        $notifs = $this->getJson('/api/notifications');
        $notifs->assertStatus(200)->assertJsonCount(1);
    }
}
