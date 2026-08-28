<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnqueteAssignedMail;

class NotificationMailContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enquete_assigned_mail_contains_plainte_and_enquete_id()
    {
        Mail::fake();

        $role = Role::create(['name' => 'enqueteur']);
        $enqueteur = User::factory()->create(['email' => 'enq@example.com']);
        $enqueteur->roles()->attach($role->id);

        $plaignant = User::factory()->create();
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$plaignant->id,'reference'=>'R1']);

        Sanctum::actingAs($enqueteur);

        $res = $this->postJson('/api/enquetes/assign', ['plainte_id' => $plainte->id, 'enqueteur_id' => $enqueteur->id]);
        $res->assertStatus(201);

        Mail::assertQueued(EnqueteAssignedMail::class, function ($mail) use ($plainte) {
            return $mail->enquete->plainte_id === $plainte->id;
        });
    }
}
