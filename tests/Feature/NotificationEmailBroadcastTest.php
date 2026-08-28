<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use App\Mail\EnqueteAssignedMail;
use App\Events\NotificationCreated;

class NotificationEmailBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_queued_and_event_broadcasted_on_assign()
    {
        Mail::fake();
        Event::fake();

        $role = Role::create(['name' => 'enqueteur']);
        $enqueteur = User::factory()->create();
        $enqueteur->roles()->attach($role->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$plaignant->id,'reference'=>'R1']);

        Sanctum::actingAs($enqueteur);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201);

        Mail::assertQueued(EnqueteAssignedMail::class);
        Event::assertDispatched(NotificationCreated::class);

        $this->assertDatabaseHas('notifications', ['user_id' => $enqueteur->id]);
    }
}
