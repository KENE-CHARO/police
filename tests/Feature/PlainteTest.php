<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plainte;
use Laravel\Sanctum\Sanctum;

class PlainteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_plainte()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'titre' => 'Vol de vélo',
            'description' => 'Le vélo a été volé la nuit.',
        ];

        $response = $this->postJson('/api/plaintes', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['titre' => 'Vol de vélo']);

        $this->assertDatabaseHas('plaintes', ['titre' => 'Vol de vélo', 'plaignant_id' => $user->id]);
    }
}
