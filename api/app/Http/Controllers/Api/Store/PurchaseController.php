<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\PurchaseCoinRequest;
use App\Services\PaymentGatewayService;
use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use App\Models\CoinPurchase;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    protected $paymentService;

    /**
     * Injeção do serviço de Gateway de Pagamento.
     * Este serviço lida com a comunicação HTTP para a API externa (C3/C6).
     */
    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * G2: Processamento de Compra de Moedas.
     * Este bloco coordena a validação, a transação externa e a atualização do saldo local.
     * Route: POST /api/purchase/coins
     */
    public function store(PurchaseCoinRequest $request)
    {
        // NF6: Validação de inputs (tipo, referência e valor) via FormRequest dedicada.
        $validated = $request->validated();
        $user = $request->user();

        /**
         * C3: Comunicação com o Payment Gateway Service.
         * Envia o pedido de débito para o simulador externo conforme as especificações.
         */
        $payment = $this->paymentService->processDebit(
            $validated['type'],
            $validated['reference'],
            $validated['value']
        );

        /**
         * NF5/G2: Tratamento de Erros do Gateway.
         * Se o serviço externo retornar erro (ex: falta de fundos ou referência inválida),
         * a transação local é interrompida.
         */
        if (!$payment['success']) {
            return response()->json([
                'message' => $payment['message'],
                'errors' => $payment['errors'] ?? null
            ], $payment['status'] ?? 400);
        }

        /**
         * G2/NF8: Persistência Atómica da Compra.
         * Este bloco garante que o saldo do utilizador só aumenta se todos os registos
         * (transação e detalhes da compra) forem gravados com sucesso.
         */
        $updatedUser = DB::transaction(function () use ($user, $validated) {

            // Taxa de conversão fixa: €1 = 10 moedas.
            $coinsEarned = (int) ($validated['value'] * 10);
            $type = CoinTransactionType::where('name', 'Coin purchase')->first();

            /**
             * G2: Registo no histórico de movimentos.
             * Cria um registo na tabela coin_transactions para auditoria e histórico.
             */
            $transaction = CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $type ? $type->id : 1,
                'transaction_datetime' => now(),
                'coins' => $coinsEarned,
            ]);

            /**
             * G2: Registo dos detalhes financeiros.
             * Guarda os metadados do pagamento (tipo e referência) para consulta administrativa.
             */
            CoinPurchase::create([
                'user_id' => $user->id,
                'coin_transaction_id' => $transaction->id,
                'euros' => $validated['value'],
                'payment_type' => $validated['type'],
                'payment_reference' => $validated['reference'],
                'purchase_datetime' => now(),
            ]);

            // Atualização do saldo de moedas no perfil do utilizador.
            $user->coins_balance += $coinsEarned;
            $user->save();

            return $user;
        });

        // NF5: Resposta de sucesso com o estado atualizado do utilizador para a SPA.
        return response()->json([
            'message' => 'Compra concluída!',
            'user' => new UserResource($updatedUser),
        ], 201);
    }
}
