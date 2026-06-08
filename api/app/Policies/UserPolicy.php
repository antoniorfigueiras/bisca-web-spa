<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * G1/G5: Determina se o utilizador pode visualizar os detalhes de um perfil.
     * Este bloco garante que um utilizador pode consultar o seu próprio perfil (G1) ou que um
     * Administrador pode monitorizar qualquer conta da plataforma (G5).
     */
    public function view(User $authenticatedUser, User $targetUser): bool
    {
        // NF7: Proteção da privacidade dos dados dos utilizadores.
        return $authenticatedUser->id === $targetUser->id || $authenticatedUser->type === 'A';
    }

    /**
     * G1/G5: Determina se o utilizador pode atualizar um perfil.
     * Regra: Jogadores têm permissão para editar os seus próprios dados (email, nickname, foto, password).
     * Administradores detêm autoridade para modificar qualquer conta para manutenção.
     */
    public function update(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id || $authenticatedUser->type === 'A';
    }

    /**
     * G1/G5: Determina se o utilizador pode apagar uma conta.
     * Regra Especial: Implementa a restrição de que um administrador não pode remover a sua
     * própria conta (Segurança).
     */
    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        // G5: Impede a auto-eliminação de Administradores para garantir a continuidade da gestão.
        if ($authenticatedUser->id === $targetUser->id && $authenticatedUser->type === 'A') {
            return false;
        }

        // Jogadores podem apagar a sua conta (G1) e Administradores podem remover qualquer jogador (G5).
        return $authenticatedUser->id === $targetUser->id || $authenticatedUser->type === 'A';
    }

    /**
     * G5: Determina se o utilizador pode bloquear ou desbloquear outras contas.
     * Este bloco valida o requisito de que apenas administradores têm controlo sobre o
     * acesso de jogadores à plataforma.
     */
    public function block(User $authenticatedUser): bool
    {
        // NF7: Reforço da autorização baseada em papéis (RBAC).
        return $authenticatedUser->type === 'A';
    }

    /**
     * G5: Determina se pode visualizar a listagem global de utilizadores.
     * Regra: Funcionalidade reservada à equipa de administração para monitorizaçãmo.
     */
    public function viewAny(User $authenticatedUser): bool
    {
        return $authenticatedUser->type === 'A';
    }
}
