<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * G4/G6: Retorna o Top 10 de jogadores.
     * Este bloco centraliza o acesso aos rankings globais, visíveis para todos os utilizadores.
     * Suporta filtragem por tipo de conquista e variante do jogo (Bisca de 3 ou 9).
     * Route: GET /api/leaderboard?type=wins|matches|capotes|bandeiras|coins&variant=all|3|9
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'wins');
        $variant = $request->query('variant', 'all');
        $limit = $request->query('limit', 10);

        // NF6: Validação de tipos permitidos para garantir a integridade da consulta.
        if (!in_array($type, ['wins', 'matches', 'capotes', 'bandeiras', 'coins'])) {
            $type = 'wins';
        }

        return match ($type) {
            'wins' => $this->getGamesLeaderboard($limit, $variant),
            'matches' => $this->getMatchesLeaderboard($limit, $variant),
            'capotes' => $this->getSpecialWinsLeaderboard($limit, 'capote', $variant),
            'bandeiras' => $this->getSpecialWinsLeaderboard($limit, 'bandeira', $variant),
            'coins' => $this->getCoinsLeaderboard($limit),
        };
    }

    /**
     * G4/G6: Ranking por Vitórias em Jogos (Games).
     * Este bloco contabiliza vitórias apenas em jogos multiplayer entre humanos.
     */
    private function getGamesLeaderboard($limit, $variant)
    {
        $query = DB::table('users')
            ->join('games', 'users.id', '=', 'games.winner_user_id')
            ->where('users.type', 'P') // Apenas jogadores participam no ranking.
            ->whereNull('users.deleted_at') // NF7: Exclui utilizadores removidos logicamente.
            ->where('games.status', "Ended")
            ->whereNotNull('games.player2_user_id');

        // G4: Segmentação por variante (Bisca de 3 ou 9).
        if ($variant !== 'all') {
            $query->where('games.type', $variant);
        }

        $data = $query->select(
            'users.nickname',
            'users.photo_avatar_filename',
            DB::raw('count(games.id) as value'),
            DB::raw('max(games.ended_at) as last_achievement')
        )
            ->groupBy('users.id', 'users.nickname', 'users.photo_avatar_filename')
            ->orderByDesc('value')
            // G4: Em caso de empate, quem alcançou a marca primeiro fica acima.
            ->orderBy('last_achievement', 'asc')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * G4/G6: Ranking por Vitórias em Partidas (Matches).
     * Contabiliza as vitórias em conjuntos de jogos (matches) até 4 marcas.
     */
    private function getMatchesLeaderboard($limit, $variant)
    {
        $query = DB::table('users')
            ->join('matches', 'users.id', '=', 'matches.winner_user_id')
            ->where('users.type', 'P')
            ->whereNull('users.deleted_at')
            ->where('matches.status', 'Ended');

        if ($variant !== 'all') {
            $query->where('matches.type', $variant);
        }

        $data = $query->select(
            'users.nickname',
            'users.photo_avatar_filename',
            DB::raw('count(matches.id) as value'),
            DB::raw('max(matches.ended_at) as last_achievement')
        )
            ->groupBy('users.id', 'users.nickname', 'users.photo_avatar_filename')
            ->orderByDesc('value')
            ->orderBy('last_achievement', 'asc')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * G2/G4: Ranking por Fortuna (Saldo de Moedas).
     * Embora não seja um requisito obrigatório de jogo, ajuda na visualização da economia da plataforma (G6).
     */
    private function getCoinsLeaderboard($limit)
    {
        $data = DB::table('users')
            ->where('type', 'P')
            ->whereNull('deleted_at')
            ->select('nickname', 'photo_avatar_filename', 'coins_balance as value')
            ->orderByDesc('coins_balance')
            ->orderBy('created_at', 'asc') // Desempate pela antiguidade da conta.
            ->limit($limit)
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * G4: Ranking para Capotes e Bandeiras.
     * Este bloco filtra vitórias especiais com base nos pontos capturados no jogo.
     * Capote: 91 a 119 pontos. Bandeira: 120 pontos.
     */
    private function getSpecialWinsLeaderboard($limit, $specialType, $variant)
    {
        $query = DB::table('users')
            ->join('games', 'users.id', '=', 'games.winner_user_id')
            ->where('users.type', 'P')
            ->whereNull('users.deleted_at')
            ->where('games.status', 'Ended')
            ->whereNotNull('games.player2_user_id');

        if ($variant !== 'all') {
            $query->where('games.type', $variant);
        }

        // Lógica baseada nos valores de pontos da Bisca.
        if ($specialType === 'bandeira') {
            $query->where(function ($q) {
                $q->where(DB::raw('CASE WHEN games.winner_user_id = games.player1_user_id THEN games.player1_points ELSE games.player2_points END'), '=', 120);
            });
        } else {
            $query->where(function ($q) {
                $q->where(DB::raw('CASE WHEN games.winner_user_id = games.player1_user_id THEN games.player1_points ELSE games.player2_points END'), '>=', 91)
                    ->where(DB::raw('CASE WHEN games.winner_user_id = games.player1_user_id THEN games.player1_points ELSE games.player2_points END'), '<', 120);
            });
        }

        $data = $query->select(
            'users.nickname',
            'users.photo_avatar_filename',
            DB::raw('count(games.id) as value'),
            DB::raw('max(games.ended_at) as last_achievement')
        )
            ->groupBy('users.id', 'users.nickname', 'users.photo_avatar_filename')
            ->orderByDesc('value')
            ->orderBy('last_achievement', 'asc')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $data]);
    }
}
