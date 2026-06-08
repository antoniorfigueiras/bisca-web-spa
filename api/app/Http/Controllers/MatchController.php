<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Http\Resources\GameResource;

class MatchController extends Controller
{
    // POST /api/matches
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:3,9', // Bisca de 3 ou 9
        ]);

        $user = $request->user();

        // 1. Criar a Partida (Contentor)
        $match = GameMatch::create([
            'status' => 'PL', // Playing
            'type' => $validated['type'],
            'player1_user_id' => $user->id,
            'player1_marks' => 0,
            'player2_marks' => 0,
            'total_games' => 0
        ]);

        // 2. Criar o 1.º Jogo dessa Partida
        // Isto é fundamental para aparecer no Lobby!
        $firstGame = Game::create([
            'type' => $validated['type'],
            'status' => 'PE', // Pending -> Aparece no Lobby
            'player1_user_id' => $user->id,
            'match_id' => $match->id,
            'began_at' => now(),
            'player1_points' => 0,
            'player2_points' => 0
        ]);

        // Carrega a relação para o frontend
        $firstGame->load('player1');

        // Retornamos o GAME, porque é isso que o socket precisa para o Lobby
        return new GameResource($firstGame);
    }

    // PUT /api/matches/{id} - Chamado quando a partida acaba totalmente
    public function update(Request $request, GameMatch $match)
    {
        // Aqui também validamos com os nomes corretos
        $data = $request->only([
            'status',
            'winner_user_id',
            'player1_marks',
            'player2_marks'
        ]);

        $match->update($data);

        return response()->json(['message' => 'Match updated', 'data' => $match]);
    }
}
