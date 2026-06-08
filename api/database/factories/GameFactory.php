<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    public function definition(): array
    {
        // 1. Criar datas aleatórias no último mês
        $began = $this->faker->dateTimeBetween('-1 month', 'now');
        $ended = (clone $began)->modify('+' . rand(5, 20) . ' minutes');

        // 2. Pontos (Soma tem de ser 120)
        $p1Points = $this->faker->numberBetween(0, 120);
        $p2Points = 120 - $p1Points;

        // 3. Determinar vencedor (quem tem mais de 60 ganha, ou 60-60 é empate)
        $winnerId = null;
        $loserId = null;
        
        // Vamos buscar 2 users aleatórios da BD para serem os jogadores
        // (Assumindo que já tens users criados)
        $users = User::inRandomOrder()->limit(2)->pluck('id');
        
        // Se não houver users suficientes, usa null (vai dar erro se não tiveres users)
        $p1Id = $users[0] ?? 1;
        $p2Id = $users[1] ?? 2;

        if ($p1Points > 60) {
            $winnerId = $p1Id;
            $loserId = $p2Id;
        } elseif ($p2Points > 60) {
            $winnerId = $p2Id;
            $loserId = $p1Id;
        }
        // Se for 60-60, winner e loser ficam null (Empate)

        return [
            'type' => $this->faker->randomElement(['3', '9']),
            'status' => 'Ended',
            'player1_user_id' => $p1Id,
            'player2_user_id' => $p2Id,
            'winner_user_id' => $winnerId,
            'loser_user_id' => $loserId,
            'player1_points' => $p1Points,
            'player2_points' => $p2Points,
            'began_at' => $began,
            'ended_at' => $ended,
            'total_time' => rand(300, 1200), // Segundos
        ];
    }
}