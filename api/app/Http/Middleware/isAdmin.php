<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * G5/NF7: Verificação de Privilégios de Administrador.
     * Este middleware é fundamental para proteger as rotas de gestão (G5) e estatísticas
     * globais (G6), garantindo que apenas utilizadores com perfil de administrador
     * possam aceder a dados sensíveis de outros utilizadores ou da plataforma.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        /**
         * G5: Validação do Tipo de Utilizador.
         * 1. Verifica se existe um utilizador autenticado (via Sanctum).
         * 2. Confirma se o campo 'type' na base de dados corresponde a 'A' (Administrator).
         */
        if (!$user || $user->type !== 'A') {

            /**
             * NF5/NF7: Resposta de Segurança.
             * Se o utilizador for um jogador comum ('P') ou não estiver autenticado,
             * o acesso é bloqueado com um código 403 (Forbidden).
             */
            return response()->json([
                'message' => 'Acesso negado. Esta funcionalidade é exclusiva para administradores.',
                'error' => 'admin_required'
            ], 403);
        }

        /**
         * Se o utilizador for administrador, o pedido avança para o controlador.
         */
        return $next($request);
    }
}
