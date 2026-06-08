<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_purchase_credits_coins(): void
    {
        $user = User::factory()->create(['coins_balance' => 0, 'type' => 'P']);

        // Mock do Gateway Externo (C3)
        Http::fake([
            'https://dad-payments-api.vercel.app/api/debit' => Http::response(['status' => 'success'], 200),
        ]);

        $response = $this->actingAs($user)
                         ->postJson('/api/purchases', [
                             'type' => 'MBWAY',
                             'reference' => '912345678',
                             'value' => 5, // 5€ = 50 moedas
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'coins_balance' => 50
        ]);
    }
}
