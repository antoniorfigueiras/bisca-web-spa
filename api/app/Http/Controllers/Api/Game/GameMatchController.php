<?php

namespace App\Http\Controllers\Api\Game;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\GameMatchResource;
use Carbon\Carbon;
use App\Services\GameTransactionService;

class GameMatchController extends Controller
{
    protected $transactionService;

    public function __construct(GameTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * G4: HISTÓRICO DE PARTIDAS.
     * Este bloco recupera o histórico pessoal de partidas terminadas ou interrompidas do utilizador autenticado.
     * Implementa paginação para garantir a performance da interface (NF8).
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $matches = GameMatch::where(function ($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })
            ->whereIn('status', ['Ended', 'Interrupted'])
            ->with(['player1', 'player2', 'winner', 'games'])
            ->orderBy('ended_at', 'desc')
            ->paginate(10);

        return GameMatchResource::collection($matches);
    }

    /**
     * G3: CRIAÇÃO DA PARTIDA.
     * Este bloco lida com o pedido inicial de criação de uma partida no Lobby, definindo o tipo e a aposta (stake).
     * Garante que a transação financeira inicial é processada corretamente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'  => 'required|in:3,9',
            'stake' => 'required|integer|min:3|max:100', // Aposta mínima de 3 moedas conforme as regras.
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $data = [
                'player1_id' => $request->user()->id,
                'player2_id' => null,
                'type'       => $validated['type'],
                'stake'      => $validated['stake']
            ];

            $match = $this->createMatch($data);

            return new GameMatchResource($match->load('player1'));
        });
    }

    /**
     * G3: ATUALIZAÇÃO E PROGRESSO.
     * Este bloco permite atualizar manualmente o estado da partida ou o registo de marcas (marks).
     * Essencial para a gestão administrativa (G5) ou para lidar com entradas tardias de jogadores no sistema.
     */
    public function update(Request $request, GameMatch $gameMatch)
    {
        $validated = $request->validate([
            'status'          => 'nullable|in:Pending,Playing,Ended,Interrupted',
            'player2_user_id' => 'nullable|exists:users,id',
            'winner_user_id'  => 'nullable|exists:users,id',
            'player1_marks'   => 'nullable|integer|max:4',
            'player2_marks'   => 'nullable|integer|max:4',
        ]);

        return DB::transaction(function () use ($validated, $gameMatch) {

            // Lógica para permitir que o segundo jogador se junte à partida.
            if (isset($validated['player2_user_id']) && !$gameMatch->player2_user_id) {
                $player2 = User::findOrFail($validated['player2_user_id']);
                $this->joinMatch($gameMatch, $player2);
            }

            // Verifica se a partida deve ser encerrada e o prémio pago ao vencedor.
            $winnerId = $validated['winner_user_id'] ?? null;
            $newStatus = $validated['status'] ?? $gameMatch->status;

            if ($winnerId && $gameMatch->status !== 'Ended' && $newStatus === 'Ended') {
                $this->processMatchEnd($gameMatch, $winnerId);
            }

            // Atualização manual de marcas (marks), com o limite de 4 para a vitória.
            if (isset($validated['player1_marks'])) $gameMatch->player1_marks = $validated['player1_marks'];
            if (isset($validated['player2_marks'])) $gameMatch->player2_marks = $validated['player2_marks'];

            $gameMatch->status = $newStatus;
            $gameMatch->save();

            return new GameMatchResource($gameMatch->load(['player1', 'player2', 'winner', 'games']));
        });
    }

    /**
     * Lógica de Progressão de Marcas (Marks).
     * Este bloco traduz a pontuação de cada jogo (vaza) em marcas para a partida:
     * 61-90 pontos = 1 marca; 91-119 = 2 marcas (capote); 120 = 4 marcas (bandeira).
     */
    public function updateMatchProgress(Game $game, array $roundResults)
    {
        $match = GameMatch::findOrFail($game->match_id);

        $p1Points = $roundResults['player1_points'];
        $p2Points = $roundResults['player2_points'];

        if ($p1Points > $p2Points) {
            $marks = ($p1Points == 120) ? 4 : (($p1Points >= 91) ? 2 : 1);
            $match->player1_marks += $marks;
        } elseif ($p2Points > $p1Points) {
            $marks = ($p2Points == 120) ? 4 : (($p2Points >= 91) ? 2 : 1);
            $match->player2_marks += $marks;
        }

        // Verifica se algum jogador atingiu ou ultrapassou as 4 marcas necessárias para vencer a partida.
        $matchWinnerId = null;
        if ($match->player1_marks >= 4) {
            $matchWinnerId = $match->player1_user_id;
        } elseif ($match->player2_marks >= 4) {
            $matchWinnerId = $match->player2_user_id;
        }

        if ($matchWinnerId) {
            $this->processMatchEnd($match, $matchWinnerId);
        } else {
            $match->save();
        }
    }

    /**
     * G3: Adesão do Player 2.
     * Garante que o stake (aposta) é cobrado ao segundo jogador ao entrar, iniciando a contagem de tempo oficial.
     */
    public function joinMatch(GameMatch $match, User $player2)
    {
        if ($match->player2_user_id) return;

        // G2: Processa o débito da aposta no saldo do utilizador.
        $this->transactionService->process($player2, 'Match stake', -$match->stake, null, $match->id);

        $match->update([
            'player2_user_id' => $player2->id,
            'status' => 'Playing',
            'began_at' => now(),
        ]);
    }

    /**
     * Lógica Centralizada de Criação.
     * Inicializa a partida com os dois jogadores e cobra as apostas iniciais de ambos.
     */
    public function createMatch(array $data)
    {
        $p1 = User::findOrFail($data['player1_id']);
        $p2 = User::findOrFail($data['player2_id']);

        // G2: Débito automático das moedas de aposta antes do início.
        $this->transactionService->process($p1, 'Match stake', -$data['stake']);
        $this->transactionService->process($p2, 'Match stake', -$data['stake']);

        return GameMatch::create([
            'type'            => $data['type'],
            'player1_user_id' => $p1->id,
            'player2_user_id' => $p2->id,
            'status'          => 'Playing',
            'began_at'        => now(),
            'stake'           => $data['stake'],
            'player1_marks'   => 0,
            'player2_marks'   => 0,
        ]);
    }

    /**
     * Encerramento da Partida e Pagamento de Prémios.
     * Este bloco define o vencedor final e paga o prémio (soma das apostas menos 1 moeda de comissão para a plataforma).
     */
    private function processMatchEnd(GameMatch $match, $winnerId)
    {
        $match->status = 'Ended';
        $match->winner_user_id = $winnerId;
        $match->loser_user_id = ($winnerId == $match->player1_user_id)
            ? $match->player2_user_id
            : $match->player1_user_id;

        $match->ended_at = now();

        if ($match->began_at) {
            $match->total_time = Carbon::parse($match->began_at)->diffInSeconds(now());
        }

        // G2/G3: Cálculo do prémio vencedor = (Stake x 2) - 1 moeda de comissão.
        $prize = ($match->stake * 2) - 1;
        $winner = User::find($winnerId);

        if ($winner && $prize > 0) {
            $this->transactionService->process(
                $winner,
                'Match payout',
                $prize,
                null,
                $match->id,
                ['commission' => 1] // Detalhe técnico da transação para auditoria em G2
            );
        }

        $match->save();
    }
}
