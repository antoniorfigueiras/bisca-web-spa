<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TransactionResource extends JsonResource
{
    /**
     * G2/NF2: Transforma o modelo CoinTransaction num array JSON formatado.
     * Este bloco garante a visibilidade de bónus, compras e gastos em jogos.
     * Segue os princípios de serviços RESTful para uma integração clara com a SPA Vue.js.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificador único da transação.
            'id' => $this->id,

            /**
             * Relacionamento com o utilizador.
             * G5: Essencial para a auditoria administrativa, onde administradores podem visualizar o histórico de todos os jogadores.
             */
            'user' => new UserResource($this->whenLoaded('user')),

            /**
             * G2: Tipo de Transação detalhado.
             * Identifica se o movimento é um "Bonus", "Coin purchase", "Game fee", "Match stake", "Game payout" ou "Match payout".
             */
            'type' => $this->transaction_type ? $this->transaction_type->name : 'desconhecido',

            /**
             * Indicador de Débito/Crédito.
             * 'C' = Crédito (aumenta o saldo); 'D' = Débito (diminui o saldo).
             */
            'type_label' => $this->transaction_type ? $this->transaction_type->type : null,

            /**
             * G2: Valor das moedas.
             * Valor positivo indica crédito e valor negativo indica débito no balanço do utilizador.
             */
            'coins' => (int) $this->coins,

            /**
             * Data e hora exata do movimento.
             * Formatado para garantir consistência visual no histórico de transações da SPA.
             */
            'datetime' => $this->transaction_datetime
                ? Carbon::parse($this->transaction_datetime)->format('Y-m-d H:i:s')
                : null,

            /**
             * G2/C3: Detalhes específicos de compras externas em Euros.
             * Inclui o montante real cobrado e o método (MBWAY, PAYPAL, IBAN, MB ou VISA).
             * As referências são validadas conforme os formatos específicos do Payment Gateway.
             */
            'purchase_details' => $this->when($this->purchase, function() {
                return [
                    'euros' => (float) $this->purchase->euros,
                    'payment_type' => $this->purchase->payment_type,
                    'payment_reference' => $this->purchase->payment_reference,
                ];
            }),

            /**
             * Referências cruzadas com a atividade de jogo.
             * Permite associar o gasto ou ganho a um jogo específico ou a uma partida (Match).
             */
            'game_id' => $this->game_id,
            'match_id' => $this->match_id,

            /**
             * NF7: Campo para dados adicionais ou justificações personalizadas via JSON.
             */
            'custom' => $this->custom,
        ];
    }
}
