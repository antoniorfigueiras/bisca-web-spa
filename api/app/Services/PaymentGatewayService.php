<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class PaymentGatewayService
{
    /**
     * URL base do serviço de pagamentos simulado fornecido para o projeto.
     * Este endpoint centraliza as operações de débito e crédito para MBWAY, PAYPAL, etc.
     */
    protected string $baseUrl = 'https://dad-payments-api.vercel.app/api';

    /**
     * C3: Efetua um pedido de débito (compra) ao Gateway Externo.
     * Esta comunicação é obrigatória para validar pagamentos e carregar a conta do utilizador.
     *
     * @param string $type MBWAY, IBAN, MB, VISA ou PAYPAL
     * @param string $reference A referência validada previamente no PurchaseCoinRequest
     * @param int $value Valor em Euros
     * @return array Resposta normalizada para o controlador
     */
    public function processDebit(string $type, string $reference, int $value): array
    {
        try {
            /**
             * NF8: Robustez e Performance.
             * Define um timeout de 10 segundos para garantir que a aplicação não fica bloqueada
             * por falhas de rede externa, cumprindo os requisitos de disponibilidade.
             */
            $response = Http::timeout(10)
                ->withOptions(['verify' => false]) // Necessário para evitar erros de certificados em certos ambientes
                ->post("{$this->baseUrl}/debit", [
                    'type'      => $type,
                    'reference' => $reference,
                    'value'     => (int)$value,
                ]);

            /**
             * Resposta de Sucesso (200/201).
             * O gateway confirmou a existência da conta e a disponibilidade de fundos.
             */
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                ];
            }

            /**
             * Tratamento de Erros de Negócio.
             * Lida com cenários como "Saldo insuficiente" ou "Referência inexistente",
             * devolvendo a mensagem original do gateway para o utilizador (NF5).
             */
            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => $response->json()['message'] ?? 'O pagamento foi recusado pelo serviço externo.',
                'errors'  => $response->json()['errors'] ?? null
            ];

        } catch (Exception $e) {
            /**
             * NF5: Feedback amigável.
             * Em caso de falha de DNS ou timeout, informa o utilizador sem expor detalhes técnicos.
             */
            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Não foi possível contactar o servidor de pagamentos. Por favor, tente mais tarde.',
            ];
        }
    }

    /**
     * Efetua um pedido de crédito (Refund/Payout).
     * Embora o foco inicial seja a compra de moedas, este método permite realizar reembolsos
     * automáticos se um processo interno falhar após a cobrança externa.
     */
    public function processCredit(string $type, string $reference, int $value): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/credit", [
                'type'      => $type,
                'reference' => $reference,
                'value'     => (int)$value,
            ]);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha na conexão ao gateway para operação de crédito.'
            ];
        }
    }
}
