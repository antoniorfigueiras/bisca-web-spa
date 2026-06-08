<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\GlobalStatsController;
use App\Http\Controllers\Api\Store\PurchaseController;
use App\Http\Controllers\Api\Store\TransactionController;
use App\Http\Controllers\Api\Game\GameController;
use App\Http\Controllers\Api\Game\GameMatchController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\FileController;

/*
|--------------------------------------------------------------------------
| Public Routes (Acesso sem Token)
|--------------------------------------------------------------------------
*/
// G1: Autenticação e Registo
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/**
 * G1: Rota para recuperar conta desativada (Soft Delete).
 * Essencial para permitir que utilizadores que "apagaram" a conta possam voltar
 * sem criar um novo registo, preservando o histórico (Requisito NF7).
 */
Route::post('auth/restore', [AuthController::class, 'restore']);

// G4/G6: Rankings e Estatísticas Públicas
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
Route::get('/stats/summary', [GlobalStatsController::class, 'summary']);

/**
 * G1: Visualização de Avatares.
 * Rota pública para servir imagens, garantindo que o Frontend pode renderizar
 * as fotos dos jogadores em qualquer contexto.
 */
Route::get('/files/photos_avatars/{filename}', [FileController::class, 'show']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Required - NF7)
|--------------------------------------------------------------------------
| Todas as rotas abaixo exigem um Token Sanctum válido e que o utilizador
| não esteja bloqueado pela administração (Middleware 'not_blocked').
*/
Route::middleware(['auth:sanctum', 'not_blocked'])->group(function () {

    /**
     * G3: Integração com WebSockets (Node.js).
     * Rotas protegidas para comunicação interna e gestão de estado do jogo.
     */
    Route::prefix('games')->group(function () {
        Route::post('/init', [GameController::class, 'initGame']);
        Route::post('/{game}/join', [GameController::class, 'join']);
        Route::post('/{game}/complete', [GameController::class, 'completeGame']);
    });

    // --- Perfil e Sessão (G1) ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users/me', [AuthController::class, 'me']);
    Route::apiResource('users', UserController::class)->only(['show', 'update', 'destroy']);

    // G1: Upload independente de foto para pré-visualização
    Route::post('/files/photos_avatars', [FileController::class, 'store']);

    // --- Economia e Carteira (G2/C3) ---
    Route::post('/purchases', [PurchaseController::class, 'store']); // Compra de moedas via Gateway
    Route::get('/my/transactions', [TransactionController::class, 'index']); // Histórico pessoal

    // --- Histórico e Jogos (G3 / G4) ---
    Route::get('/games/history', [GameController::class, 'userHistory']);

    /**
     * Gestão de Jogos e Partidas.
     * apiResource gera automaticamente as rotas GET, POST, PUT e PATCH necessárias.
     */
    Route::apiResource('games', GameController::class)->except(['destroy', 'index']);
    Route::apiResource('matches', GameMatchController::class)->except(['destroy']);

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes (G5 / G6)
    |--------------------------------------------------------------------------
    | Rotas exclusivas para o tipo 'A'. Protegidas pelo middleware 'isAdmin'.
    */
    Route::middleware(['isAdmin'])->prefix('admin')->group(function () {

        // G5: Gestão de Utilizadores
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']); // Criar Admin ou Jogador
        Route::patch('/users/{user}/block', [UserManagementController::class, 'toggleBlock']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']); // Soft/Hard Delete

        // G6: Painel de Controlo e Monitorização
        Route::get('/stats', [GlobalStatsController::class, 'index']);
        Route::get('/transactions', [TransactionController::class, 'indexAdmin']); // Auditoria financeira
        Route::get('/games', [GameController::class, 'globalHistory']); // Histórico total de jogos
    });
});
