<?php

namespace App\Http\Controllers\Api\Game;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\GameResource;
use App\Services\GameTransactionService;

class GameController extends Controller
{
    protected $transactionService;

    public function __construct(GameTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * G4: HISTÓRICO UNIFICADO
     * Este bloco recupera tanto jogos isolados (standalones) como partidas completas (matches) para o utilizador autenticado.
     * Implementa a lógica de paginação manual para combinar diferentes modelos de dados numa única lista cronológica.
     */
    public function userHistory(Request $request)
    {
        $userId = $request->user()->id;

        // Recupera matches onde o utilizador participou e que já terminaram.
        $matches = GameMatch::where(function ($q) use ($userId) {
            $q->where('player1_user_id', $userId)->orWhere('player2_user_id', $userId);
        })
            ->with(['player1', 'player2', 'winner', 'games'])
            ->where('status', 'Ended')
            ->get()
            ->map(function ($match) {
                $match->is_match_entry = true;
                // NF3: Garante a consistência dos dados calculando marcas se estas não estiverem persistidas.
                if (is_null($match->player1_marks) || is_null($match->player2_marks)) {
                    $match->player1_marks = $match->games->where('winner_user_id', $match->player1_user_id)->count();
                    $match->player2_marks = $match->games->where('winner_user_id', $match->player2_user_id)->count();
                }
                return $match;
            });

        // Recupera jogos isolados (não pertencentes a matches) terminados.
        $standalones = Game::whereNull('match_id')
            ->where(function ($q) use ($userId) {
                $q->where('player1_user_id', $userId)->orWhere('player2_user_id', $userId);
            })
            ->with(['player1', 'player2', 'winner'])
            ->where('status', 'Ended')
            ->get()
            ->map(function ($game) {
                $game->is_match_entry = false;
                return $game;
            });

        // Combina e ordena por data de início (mais recentes primeiro).
        $fullHistory = $matches->concat($standalones)->sortByDesc(function ($item) {
            return $item->began_at;
        })->values();

        // NF5/NF8: Lógica de paginação manual para manter a performance da SPA.
        $perPage = 10;
        $currentPage = (int) $request->query('page', 1);
        $total = $fullHistory->count();
        $lastPage = (int) ceil($total / $perPage);
        $paginatedItems = $fullHistory->forPage($currentPage, $perPage)->values();
        $from = $total ? ($currentPage - 1) * $perPage + 1 : 0;
        $to = $total ? min($currentPage * $perPage, $total) : 0;

        return response()->json([
            'data' => $paginatedItems,
            'meta' => [
                'total' => $total,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage
            ]
        ]);
    }

    /**
     * G3: INICIALIZAÇÃO DE JOGO
     * Este bloco valida a criação de um novo jogo, gerindo a cobrança de taxas em moedas
     * e distinguindo entre os modos Singleplayer (bot) e Multiplayer.
     */
    public function initGame(Request $request)
    {
        // NF6: Validação rigorosa dos parâmetros de entrada conforme as regras de negócio.
        $validated = $request->validate([
            'player1_id' => 'required|exists:users,id',
            'player2_id' => 'nullable|exists:users,id',
            'type' => 'required|in:3,9',
            'stake' => 'required|integer|min:2|max:100',
            'is_match' => 'required|boolean',
            'mode' => 'required|in:single,multi',
        ]);

        return DB::transaction(function () use ($validated) {
            $p1 = User::findOrFail($validated['player1_id']);
            $matchId = null;
            $p2Id = $validated['player2_id'] ?? null;
            $gameStatus = 'Playing';

            if ($validated['is_match']) {
                // G3: Lógica para MATCH (Sempre multiplayer e com aposta mínima).
                $matchController = app(GameMatchController::class);
                $match = $matchController->createMatch($validated);
                $matchId = $match->id;
                $p2Id = $validated['player2_id'];
            } else {
                // G3: Lógica para Jogo Standalone (Isolado).
                if ($validated['mode'] === 'multi') {
                    // G2: Cobra 2 moedas por jogo multiplayer standalone.
                    $this->transactionService->process($p1, 'Game fee', -2);

                    if ($p2Id) {
                        $p2 = User::findOrFail($p2Id);
                        $this->transactionService->process($p2, 'Game fee', -2);
                    } else {
                        // Se não houver adversário imediato, o jogo fica a aguardar no Lobby.
                        $gameStatus = 'Pending';
                    }
                }
                // Nota: Jogos singleplayer contra bot não têm custo em moedas.
            }

            $game = Game::create([
                'type' => $validated['type'],
                'status' => $gameStatus,
                'player1_user_id' => $p1->id,
                'player2_user_id' => $p2Id,
                'match_id' => $matchId,
                'began_at' => now(),
            ]);

            return response()->json([
                'id' => $game->id,
                'match_id' => $matchId,
                'message' => 'Jogo iniciado com sucesso.'
            ], 201);
        });
    }

    /**
     * G3: ADESÃO A JOGO (Player 2 entra)
     * Este bloco permite que um segundo jogador se junte a um jogo multiplayer que esteja em estado 'Pending'.
     */
    public function join(Request $request, Game $game)
    {
        $user = $request->user();

        // NF7: Impede o acesso de utilizadores a jogos singleplayer já em curso.
        if ($game->status === 'Playing' && $game->player2_user_id === null) {
            return response()->json(['message' => 'Não podes entrar neste jogo.'], 403);
        }

        if ($game->player2_user_id !== null) {
            return response()->json(['message' => 'Este jogo já está cheio.'], 409);
        }

        if ($game->player1_user_id == $user->id) {
            return response()->json(['message' => 'Não podes jogar contra ti próprio.'], 400);
        }

        return DB::transaction(function () use ($game, $user) {
            if ($game->match_id) {
                // Delega a lógica de entrada em partidas para o controlador específico.
                $match = GameMatch::findOrFail($game->match_id);
                app(GameMatchController::class)->joinMatch($match, $user);
            } else {
                // G2: Cobra a taxa de entrada de 2 moedas ao segundo jogador.
                $this->transactionService->process($user, 'Game fee', -2);
            }

            $game->update([
                'player2_user_id' => $user->id,
                'status' => 'Playing'
            ]);

            return response()->json([
                'message' => 'Entraste no jogo com sucesso.',
                'game' => new GameResource($game)
            ]);
        });
    }

    /**
     * G3: FINALIZAÇÃO E ATRIBUIÇÃO DE RESULTADOS
     * Este bloco regista o fim de um jogo, calcula durações e aciona o pagamento de prémios.
     */
    public function completeGame(Request $request, Game $game)
    {
        $validated = $request->validate([
            'winner_id' => 'nullable|exists:users,id',
            'player1_points' => 'required|integer|min:0|max:120',
            'player2_points' => 'required|integer|min:0|max:120',
            'end_reason' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated, $game) {
            if ($game->status === 'Ended') {
                return response()->json(['message' => 'Jogo já finalizado.'], 400);
            }

            $endTime = now();
            $totalSeconds = $game->began_at ? $game->began_at->diffInSeconds($endTime) : 0;

            // NF3: Atualiza o registo do jogo com as pontuações e estado final.
            $game->update([
                'status' => 'Ended',
                'winner_user_id' => $validated['winner_id'],
                'loser_user_id' => $this->getLoserId($game, $validated['winner_id']),
                'player1_points' => $validated['player1_points'],
                'player2_points' => $validated['player2_points'],
                'is_draw' => ($validated['player1_points'] == 60 && $validated['player2_points'] == 60),
                'ended_at' => $endTime,
                'total_time' => $totalSeconds,
            ]);

            if ($game->match_id) {
                // Atualiza o progresso da partida (marks/riscas).
                app(GameMatchController::class)->updateMatchProgress($game, $validated);
            } else {
                // Gere os pagamentos de moedas para jogos isolados.
                $this->handleStandalonePayouts($game);
            }

            return new GameResource($game->load(['player1', 'player2', 'winner']));
        });
    }

    /**
     * G2: CÁLCULO DE PAGAMENTOS STANDALONE
     * Atribui prémios com base na pontuação final: 3 (vitória básica), 4 (capote) ou 6 (bandeira).
     */
    private function handleStandalonePayouts(Game $game)
    {
        // NF8: Deteta se o jogo foi singleplayer (contra bot) e ignora pagamentos.
        if ($game->player2_user_id === null) {
            return;
        }

        // G2: Em caso de empate, reembolsa 1 moeda a cada jogador.
        if ($game->is_draw) {
            $this->transactionService->refundDraw($game->player1, $game->id);
            $this->transactionService->refundDraw($game->player2, $game->id);
            return;
        }

        if (!$game->winner_user_id) return;

        // Determina o valor do prémio conforme a pontuação do vencedor.
        $winnerPoints = ($game->winner_user_id == $game->player1_user_id) ? $game->player1_points : $game->player2_points;
        $prize = ($winnerPoints == 120) ? 6 : (($winnerPoints >= 91) ? 4 : 3);

        $this->transactionService->process($game->winner, 'Game payout', $prize, $game->id);
    }

    /**
     * Auxiliar para identificar o perdedor com base no ID do vencedor.
     */
    private function getLoserId($model, $winnerId)
    {
        if (!$winnerId) return null;
        return ($winnerId == $model->player1_user_id) ? $model->player2_user_id : $model->player1_user_id;
    }

    /**
     * G5: HISTÓRICO GLOBAL (Administração)
     * Permite aos administradores visualizar todos os jogos e matches terminados na plataforma.
     */
    public function globalHistory(Request $request)
    {
        // Recupera todos os matches finalizados na plataforma.
        $matches = GameMatch::with(['player1', 'player2', 'winner', 'games'])
            ->where('status', 'Ended')
            ->orderBy('began_at', 'desc')
            ->get()
            ->map(function ($match) {
                $match->is_match_entry = true;
                if (is_null($match->player1_marks) || is_null($match->player2_marks)) {
                    $match->player1_marks = $match->games->where('winner_user_id', $match->player1_user_id)->count();
                    $match->player2_marks = $match->games->where('winner_user_id', $match->player2_user_id)->count();
                }
                return $match;
            });

        // Recupera todos os jogos isolados finalizados na plataforma.
        $standalones = Game::whereNull('match_id')
            ->with(['player1', 'player2', 'winner'])
            ->where('status', 'Ended')
            ->orderBy('began_at', 'desc')
            ->get()
            ->map(function ($game) {
                $game->is_match_entry = false;
                return $game;
            });

        // Combina as coleções para auditoria administrativa unificada.
        $fullHistory = $matches->concat($standalones)->sortByDesc(function ($item) {
            return $item->began_at;
        })->values();

        // NF8: Paginação manual para visualização eficiente de grandes volumes de dados.
        $perPage = 10;
        $currentPage = (int) $request->query('page', 1);
        $total = $fullHistory->count();
        $lastPage = (int) ceil($total / $perPage);
        $paginatedItems = $fullHistory->forPage($currentPage, $perPage)->values();
        $from = $total ? ($currentPage - 1) * $perPage + 1 : 0;
        $to = $total ? min($currentPage * $perPage, $total) : 0;

        return response()->json([
            'data' => $paginatedItems,
            'meta' => [
                'total' => $total,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage
            ]
        ]);
    }
}
