<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\CampayService;
use Laravel\Sanctum\Sanctum;

class CampayPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_plainte_persists_pending_and_triggers_stimulation()
    {
        $role = Role::firstOrCreate(['name' => 'citoyen']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        Sanctum::actingAs($user);

        // Mock campay service to return a simulated transaction
        $mock = \Mockery::mock(CampayService::class);
        $mock->shouldReceive('stimulate')->once()->andReturn([
            'success' => true,
            'transaction_id' => 'TXN12345SIM',
            'raw' => ['simulated' => true],
        ]);
        $this->app->instance(CampayService::class, $mock);

        $payload = [
            'titre' => 'Plainte paiement mobile',
            'description' => 'Test Campay',
            'payment_method' => 'mobile',
            'payment_phone' => '770000000',
            'payment_operator' => 'mtn',
            'payment_amount' => 100,
        ];

        $resp = $this->postJson('/api/plaintes', $payload);
        $resp->assertStatus(201)->assertJsonFragment(['titre' => 'Plainte paiement mobile']);

        $this->assertDatabaseHas('plaintes', [
            'titre' => 'Plainte paiement mobile',
            'plaignant_id' => $user->id,
            'payment_txn_id' => 'TXN12345SIM',
            'payment_status' => 'processing',
        ]);
    }

    public function test_webhook_marks_plainte_successful()
    {
        $role = Role::firstOrCreate(['name' => 'citoyen']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        // create plainte with txn id
        $plainte = \App\Models\Plainte::create([
            'reference' => 'TEST-REF',
            'plaignant_id' => $user->id,
            'titre' => 'To confirm',
            'payment_txn_id' => 'TXNCONF',
            'payment_status' => 'processing',
            'paid' => false,
        ]);

        $resp = $this->postJson('/api/payments/webhook', [
            'transaction_id' => 'TXNCONF',
            'status' => 'success',
        ], ['X-Campay-Webhook-Secret' => '']);

        $resp->assertStatus(200);

        $this->assertDatabaseHas('plaintes', [
            'id' => $plainte->id,
            'payment_status' => 'successful',
            'paid' => true,
        ]);
    }
}
