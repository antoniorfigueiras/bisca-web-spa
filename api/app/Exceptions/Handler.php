<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Lista de inputs que nunca serão incluídos em logs ou sessões por segurança.
     * Este bloco existe para cumprir o requisito de segurança NF7, protegendo dados sensíveis do utilizador.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registo de callbacks de reporte.
     * Este método é o local padrão da framework para definir comportamentos de log personalizados para exceções específicas.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Renderiza as exceções em respostas JSON uniformes.
     * Este bloco centraliza o tratamento de erros para garantir que a API responda de forma consistente com os princípios RESTful (NF2),
     * facilitando a integração com o cliente Vue.js e melhorando a usabilidade (NF5).
     */
    public function render($request, Throwable $e)
    {
        // Verifica se o pedido é direcionado à API ou se o cliente espera uma resposta JSON.
        if ($request->is('api/*') || $request->wantsJson()) {

            /**
             * Erros de Validação (422).
             * Necessário para validar entradas de utilizador em registos, compras (NF6) e gestão de perfil (G1).
             */
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Os dados fornecidos são inválidos.',
                    'errors' => $e->errors(),
                ], 422);
            }

            /**
             * Erros de Autenticação (401).
             * Garante que apenas utilizadores autenticados acedam a funcionalidades restritas, conforme definido no grupo G1.
             */
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Sessão não iniciada ou expirada.',
                ], 401);
            }

            /**
             * Erros de Autorização (403).
             * Bloqueia ações não permitidas, como um jogador tentar aceder a ferramentas de administração (G5)
             * ou violar a privacidade de dados (NF7).
             */
            if ($e instanceof AccessDeniedHttpException) {
                return response()->json([
                    'message' => 'Não tem permissão para realizar esta ação.',
                ], 403);
            }

            /**
             * Recurso Não Encontrado (404).
             * Informa o cliente de forma elegante quando um ID de jogo, partida ou transação não existe na base de dados.
             */
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'O recurso solicitado não foi encontrado.',
                ], 404);
            }

            /**
             * Método Não Permitido (405).
             * Impede o uso de verbos HTTP incorretos em endpoints específicos, mantendo a integridade da API REST.
             */
            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'message' => 'Método HTTP não permitido para este endpoint.',
                ], 405);
            }

            /**
             * Erro Genérico do Servidor (500).
             * Em ambiente de produção, este bloco oculta detalhes técnicos da infraestrutura para evitar exposição de vulnerabilidades (NF8).
             */
            if (!config('app.debug')) {
                return response()->json([
                    'message' => 'Ocorreu um erro interno no servidor. Por favor, tente mais tarde.',
                ], 500);
            }
        }

        /**
         * Fallback para o comportamento padrão da framework.
         * Garante que exceções não tratadas especificamente acima ainda sejam processadas pelo Laravel.
         */
        return parent::render($request, $e);
    }
}
