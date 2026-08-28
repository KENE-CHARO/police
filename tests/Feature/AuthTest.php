<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_user_and_token()
    {
        $payload = [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id','email','name'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
    }

    public function test_login_returns_token()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id','email'], 'token']);
    }
}
