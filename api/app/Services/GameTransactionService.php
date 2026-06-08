<?php

namespace App\Services;

use App\Models\User;
use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class GameTransactionService
{
    /**
     * G2: Processamento Atómico de Transações.
     * Este método garante que o saldo do utilizador e o registo histórico são atualizados
     * em simultâneo. Utiliza "Pessimistic Locking" para evitar Race Conditions.
     */
    public function process(User $user, string $typeName, int $amount, ?int $gameId = null, ?int $matchId = null, ?array $customData = null): CoinTransaction
    {
        // SEGURANÇA: Trabalhamos sempre com o valor absoluto para evitar erros de sinal.
        // A natureza (Crédito ou Débito) é definida pela configuração do tipo na BD.
        $absAmount = abs($amount);

        return DB::transaction(function () use ($user, $typeName, $absAmount, $gameId, $matchId, $customData) {

            // 1. Validação do Tipo de Transação
            $type = CoinTransactionType::where('name', $typeName)->first();

            if (!$type) {
                throw new Exception("Tipo de transação '{$typeName}' não encontrado.");
            }

            /**
             * 2. Atomicidade e Bloqueio (Concurrency Control).
             * lockForUpdate() impede que outros processos alterem o saldo deste utilizador
             * até que esta transação termine (Requisito NF7).
             */
            $userRefresh = User::where('id', $user->id)->lockForUpdate()->first();

            if ($userRefresh->type === 'A') {
                throw new Exception("Administradores não podem realizar transações de jogo.");
            }

            // 3. Lógica de Saldo (Débito vs Crédito)
            $isDebit = ($type->type === 'D');

            if ($isDebit) {
                if ($userRefresh->coins_balance < $absAmount) {
                    throw new Exception("Saldo insuficiente.");
                }
                $userRefresh->coins_balance -= $absAmount;
            } else {
                $userRefresh->coins_balance += $absAmount;
            }

            $userRefresh->save();

            /**
             * 4. Registo Histórico Imutável (G2).
             * Persiste o movimento com o sinal correto para facilitar consultas de extrato.
             */
            return CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $type->id,
                'transaction_datetime' => Carbon::now(),
                'coins' => $isDebit ? -$absAmount : $absAmount,
                'game_id' => $gameId,
                'match_id' => $matchId,
                'custom' => $customData
            ]);
        });
    }

    /**
     * G1: Atribuição de Bónus de Boas-vindas.
     * Chamado automaticamente após o registo bem-sucedido de um novo jogador.
     */
    public function awardWelcomeBonus(User $user): void
    {
        $this->process($user, 'Bonus', 10, null, null, ['desc' => 'Bónus Inicial']);
    }

    /**
     * G2: Reembolso por Empate (60-60 pontos).
     * Devolve uma moeda ao jogador como consolação conforme as regras de negócio.
     */
    public function refundDraw(User $user, int $gameId): void
    {
        $this->process($user, 'Bonus', 1, $gameId, null, ['desc' => 'Draw Refund']);
    }

    /**
     * G2/G3: Atribuição de Prémio ao Vencedor da Match.
     * O prémio é o dobro do stake menos a taxa de serviço (1 moeda) da plataforma.
     */
    public function awardMatchWinner(User $user, int $totalStake, int $matchId): void
    {
        $prize = ($totalStake * 2) - 1;

        if ($prize > 0) {
            $this->process($user, 'Match payout', $prize, null, $matchId);
        }
    }

    /**
     * G3: Cobrança de Aposta (Stake).
     * Retira o valor da aposta do saldo do utilizador no momento em que a Match começa.
     */
    public function collectMatchStake(User $user, int $stakeAmount, int $matchId): void
    {
        $this->process($user, 'Match stake', $stakeAmount, null, $matchId);
    }
}
