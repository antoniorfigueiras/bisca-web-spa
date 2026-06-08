<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_bonus(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Teste User',
            'email' => 'teste@example.com',
            'nickname' => 'tester',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'teste@example.com', 'coins_balance' => 10]);
    }

    public function test_blocked_user_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create(['blocked' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/user');

        $response->assertStatus(403)
                 ->assertJson(['error' => 'user_blocked']);
    }
}
