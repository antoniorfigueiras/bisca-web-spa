<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionMonitorController extends Controller
{
    /**
     * G2/G5: Visualização global de todas as transações da plataforma.
     * Este bloco permite aos administradores monitorizar o fluxo financeiro total (bónus, compras e jogos).
     * Implementa o requisito de acesso estritamente "Read-Only" para administradores.
     * Route: GET /api/admin/transactions
     */
    public function index(Request $request)
    {
        /**
         * G5/NF7: Verificação de perfil administrativo.
         * Garante que apenas utilizadores do tipo 'A' (Administrador) acedam ao histórico global.
         */
        if ($request->user()->type !== 'A') {
            return response()->json(['message' => 'Acesso negado. Funcionalidade exclusiva para administradores.'], 403);
        }

        /**
         * NF8: Eager Loading de relações.
         * Otimiza a performance ao carregar os dados relacionados (user, game, etc.) numa única consulta, evitando o problema N+1.
         */
        $query = CoinTransaction::with(['user', 'transaction_type', 'game', 'gameMatch']);

        /**
         * G5: Filtro por Utilizador.
         * Permite a filtragem por nickname ou ID para facilitar a auditoria de contas específicas conforme exigido em G5.
         */
        if ($request->filled('user')) {
            $searchTerm = $request->query('user');
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('nickname', 'like', "%{$searchTerm}%")
                  ->orWhere('id', $searchTerm);
            });
        }

        /**
         * G2: Filtro por Tipo de Transação.
         * Permite distinguir entre bónus de boas-vindas, compras de moedas ou taxas de jogo.
         */
        if ($request->filled('type')) {
            $query->whereHas('transaction_type', function ($q) use ($request) {
                $q->where('name', $request->query('type'));
            });
        }

        /**
         * G6: Filtro Temporal.
         * Essencial para gerar relatórios de utilização e estatísticas por período para a administração.
         */
        if ($request->filled('date')) {
            $query->whereDate('transaction_datetime', $request->query('date'));
        }

        /**
         * NF5/NF8: Ordenação e Paginação.
         * Melhora a usabilidade ao mostrar os dados mais recentes primeiro e protege o servidor de sobrecarga de memória.
         */
        $transactions = $query->orderBy('transaction_datetime', 'desc')
                              ->paginate(30);

        return TransactionResource::collection($transactions);
    }

    /**
     * G2/G5: Detalhes de uma transação específica.
     * Este bloco fornece acesso aos metadados detalhados, incluindo referências de pagamento externo se aplicável.
     */
    public function show(CoinTransaction $transaction)
    {
        /**
         * NF7: Reforço de autorização via Gates.
         * Garante uma camada extra de segurança para proteger a privacidade dos dados financeiros dos jogadores.
         */
        if (Gate::denies('view-global-history')) {
            return response()->json(['message' => 'Ação não autorizada.'], 403);
        }

        /**
         * Carregamento de detalhes da compra (purchase).
         * Necessário para validar referências MBWAY, IBAN, etc., em caso de auditoria.
         */
        $transaction->load(['user', 'transaction_type', 'purchase', 'game', 'gameMatch']);

        return new TransactionResource($transaction);
    }

    /**
     * Bloqueio de Edição (G2/G5/NF2).
     * Estes blocos existem para garantir que os administradores não possam criar, modificar ou apagar transações.
     * Retornar o código 405 (Method Not Allowed) assegura a integridade imutável dos registos financeiros da plataforma.
     */
    public function store() { return response()->json(['message' => 'Acesso de leitura exclusiva (Read-only).'], 405); }
    public function update() { return response()->json(['message' => 'As transações são imutáveis.'], 405); }
    public function destroy() { return response()->json(['message' => 'Não é permitido apagar registos financeiros.'], 405); }
}
