<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoinTransaction;
use App\Http\Resources\TransactionResource;

class TransactionController extends Controller
{
    /**
     * G2: Histórico de transações do Jogador
     * Este bloco recupera todos os movimentos financeiros (ganhos em jogos, compras de moedas, taxas de entrada) do utilizador autenticado.
     * Os jogadores podem visualizar apenas o seu próprio histórico.
     */
    public function index(Request $request)
    {
        // NF8: Permite definir um limite de resultados por página, otimizando o tráfego de rede.
        $limit = $request->query('limit', 10);

        $transactions = CoinTransaction::where('user_id', $request->user()->id)
            // NF8: Eager loading do tipo de transação (ex: "Bonus", "Game payout") para evitar consultas N+1.
            ->with(['transaction_type'])
            // NF5: Ordenação cronológica descendente para facilitar a leitura dos movimentos mais recentes.
            ->orderBy('transaction_datetime', 'desc')
            ->paginate($limit);

        return TransactionResource::collection($transactions);
    }

    /**
     * G5: Auditoria Global (Apenas Administradores).
     * Este bloco permite aos administradores visualizar todos os movimentos de moedas realizados na plataforma.
     * O acesso é estritamente de leitura (read-only), não sendo permitido criar ou modificar transações.
     */
    public function indexAdmin(Request $request)
    {
        /**
         * NF7: Verificação de Autorização.
         * Garante que apenas utilizadores do tipo 'A' (Administrador) acedem a esta funcionalidade.
         */
        if ($request->user()->type !== 'A') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $query = CoinTransaction::with(['user', 'transaction_type'])
            ->orderBy('transaction_datetime', 'desc');

        /**
         * G5: Filtro por utilizador.
         * Permite que o administrador refine a pesquisa de transações para um jogador específico através do ID.
         */
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // NF8: Paginação definida para 30 registos por página no painel de administração para melhor escalabilidade.
        return TransactionResource::collection($query->paginate(30));
    }
}
