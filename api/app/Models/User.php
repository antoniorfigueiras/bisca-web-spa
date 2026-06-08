<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * G1/G5: Atributos preenchíveis em massa.
     * Este bloco define os campos necessários para o registo (G1), gestão de moedas (G2)
     * e o controlo administrativo de bloqueios (G5).
     */
    protected $fillable = [
        'name',                  // Nome completo do utilizador
        'email',                 // Endereço de email único (credencial)
        'password',              // Hashed password
        'nickname',              // Nickname único para jogos e leaderboards
        'type',                  // 'P' para Jogador ou 'A' para Administrador
        'blocked',               // Indica se o acesso está suspenso pela administração
        'photo_avatar_filename', // Nome do ficheiro da foto ou avatar
        'coins_balance',         // G2: Saldo atual de moedas (exclusivo para jogadores)
        'custom',                // Campo JSON para dados adicionais e extensões
    ];

    /**
     * NF7: Atributos omitidos em respostas JSON por segurança.
     * Este bloco garante a proteção de dados sensíveis e credenciais de acesso.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversão de tipos (Casts).
     * Garante a integridade dos dados ao converter valores da BD para tipos nativos do PHP.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked' => 'boolean',
            'coins_balance' => 'integer',
            'custom' => 'array',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * G2: Histórico de transações de moedas.
     * Regista todos os bónus, compras, taxas de jogo e prémios ganhos.
     */
    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class, 'user_id');
    }

    /**
     * G2: Detalhes das compras de moedas.
     * Associa as transações de moedas aos pagamentos reais processados em Euros.
     */
    public function coinPurchases(): HasMany
    {
        return $this->hasMany(CoinPurchase::class, 'user_id');
    }

    /**
     * G4: Jogos iniciados pelo utilizador.
     * Regista jogos multiplayer onde o utilizador foi o criador (Player 1).
     */
    public function gamesAsPlayer1(): HasMany
    {
        return $this->hasMany(Game::class, 'player1_user_id');
    }

    /**
     * G4: Jogos onde participou como convidado.
     * Regista jogos multiplayer onde o utilizador se juntou a um lobby existente (Player 2).
     */
    public function gamesAsPlayer2(): HasMany
    {
        return $this->hasMany(Game::class, 'player2_user_id');
    }

    /**
     * G4: Partidas (sets de jogos) ganhas.
     * Contabiliza as vitórias em partidas completas (onde atingiu as 4 marcas).
     */
    public function wonMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'winner_user_id');
    }

    // --- MÉTODOS AUXILIARES ---

    /**
     * G5: Verifica privilégios administrativos.
     * Essencial para autorizar o acesso a ferramentas de gestão e monitorização global.
     */
    public function isAdmin(): bool
    {
        return $this->type === 'A';
    }

    /**
     * G5: Verifica se o utilizador pode ter saldo de moedas.
     * Administradores estão proibidos de possuir moedas ou jogar.
     */
    public function canHaveCoins(): bool
    {
        return $this->type === 'P';
    }
}
