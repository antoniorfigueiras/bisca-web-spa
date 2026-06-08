<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBlocked
{
    /**
     * G5/NF7: Verificação de Estado de Bloqueio.
     * Este bloco de lógica existe para garantir que utilizadores marcados como bloqueados pela administração
     * não consigam realizar operações na plataforma, como participar em jogos ou movimentar moedas.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        /**
         * G5: Verificação do campo 'blocked'.
         * O sistema consulta o estado do utilizador na base de dados para validar se este tem permissão
         * ativa de acesso ao sistema.
         */
        if ($user && $user->blocked) {

            /**
             * NF7: Segurança e Revogação de Acesso.
             * Este bloco garante que, assim que um utilizador é bloqueado, o seu token de autenticação (Sanctum)
             * é invalidado imediatamente, forçando a saída da Single-Page Application (SPA).
             */
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            /**
             * NF2/NF5: Resposta Uniforme da API.
             * Retorna um erro 403 (Forbidden) para que o cliente Vue.js possa informar o utilizador
             * e redirecioná-lo para a página de login.
             */
            return response()->json([
                'message' => 'A sua conta encontra-se bloqueada. Por favor, contacte a administração.',
                'error' => 'user_blocked'
            ], 403);
        }

        /**
         * Se o utilizador não estiver bloqueado, o pedido prossegue para o controlador pretendido.
         */
        return $next($request);
    }
}
