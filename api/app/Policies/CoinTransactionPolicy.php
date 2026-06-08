<?php

namespace App\Policies;

use App\Models\CoinTransaction;
use App\Models\User;

class CoinTransactionPolicy
{
    /**
     * G2: Determina se o utilizador pode visualizar a lista de transações.
     * Este bloco permite o acesso inicial ao endpoint, onde a lógica do controlador
     * filtrará os dados pertencentes ao utilizador autenticado.
     */
    public function viewAny(User $user): bool
    {
        // Jogadores podem ver a sua própria lista e Admins podem aceder à vista global.
        return true;
    }

    /**
     * G2/G5: Determina se o utilizador pode ver os detalhes de uma transação específica.
     * Regra: Garante que um jogador só acede aos seus movimentos, enquanto Administradores
     * têm permissão de leitura total para auditoria.
     */
    public function view(User $user, CoinTransaction $coinTransaction): bool
    {
        // NF7: Proteção de privacidade e dados financeiros.
        return $user->id === $coinTransaction->user_id || $user->type === 'A';
    }

    /**
     * G5: Determina quem pode visualizar o histórico global de moedas da plataforma.
     * Este bloco valida o requisito de monitorização centralizada, exclusivo para o perfil administrativo.
     */
    public function viewGlobalHistory(User $user): bool
    {
        // Administradores ('A') têm acesso read-only a todas as transações da plataforma.
        return $user->type === 'A';
    }

    /**
     * G2: Bloqueio de Criação Direta.
     * Este bloco impede a criação manual de moedas. As transações devem ser
     * geradas automaticamente por bónus, compras ou resultados de jogo.
     */
    public function create(User $user): bool
    {
        // Impede injeções arbitrárias de saldo via API para garantir a integridade económica.
        return false;
    }

    /**
     * G2/NF7: Imutabilidade dos registos financeiros.
     * Uma vez registada, uma transação de moedas nunca pode ser alterada ou apagada.
     * Nem mesmo administradores têm permissão de escrita nestes registos (Read-only).
     */
    public function update(User $user, CoinTransaction $coinTransaction): bool
    {
        // Garante a auditabilidade total e evita fraudes no saldo de moedas.
        return false;
    }

    /**
     * Bloqueio de Remoção Física.
     * Requisito G5: Registos de transações não podem ser eliminados para preservar a integridade histórica.
     */
    public function delete(User $user, CoinTransaction $coinTransaction): bool
    {
        return false;
    }
}
