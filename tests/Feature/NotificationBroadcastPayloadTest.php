<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Event;
use App\Events\NotificationCreated;

class NotificationBroadcastPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_payload_contains_expected_keys()
    {
        Event::fake();

        $role = Role::create(['name' => 'enqueteur']);
        $enqueteur = User::factory()->create();
        $enqueteur->roles()->attach($role->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$plaignant->id,'reference'=>'R1']);

        Sanctum::actingAs($enqueteur);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201);

        Event::assertDispatched(NotificationCreated::class, function ($e) use ($enqueteur, $plainte) {
            return $e->userId === $enqueteur->id && isset($e->payload['enquete_id']) && $e->payload['plainte_id'] === $plainte->id;
        });
    }
}
