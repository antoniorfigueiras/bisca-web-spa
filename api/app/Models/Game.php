<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    /**
     * O projeto exige o uso de Timestamps manuais para o domínio do jogo (G3).
     * Conforme o esquema oficial, usamos 'began_at' e 'ended_at' para o ciclo de vida do jogo.
     */
    public $timestamps = true;

    /**
     * G3/G4: Atributos preenchíveis em massa.
     * Reflete a estrutura oficial da tabela 'games' para suportar o histórico de jogos multiplayer.
     */
    protected $fillable = [
        'type',            // G3: Variante do jogo ('3' para Bisca de 3 ou '9' para Bisca de 9).
        'status',          // G3: Estado (Pending, Playing, Ended, Interrupted).
        'began_at',        // G4: Registo do momento de início para histórico e estatísticas.
        'ended_at',        // G4: Registo do momento de conclusão.
        'player1_user_id', // G3: ID do utilizador que criou o jogo (Player 1).
        'player2_user_id', // G3: ID do adversário humano (Player 2).
        'winner_user_id',  // G3: ID do vencedor após atingir 61 ou mais pontos.
        'loser_user_id',   // G3: ID do perdedor para registos de histórico.
        'player1_points',  // G3: Total de pontos capturados pelo P1 (máx 120).
        'player2_points',  // G3: Total de pontos capturados pelo P2 (máx 120).
        'total_time',      // G4: Duração total da partida em segundos.
        'match_id',        // G3: Referência se o jogo pertencer a uma Partida (Match).
        'custom',          // NF7: Campo JSON para metadados adicionais da equipa.
        'is_draw',         // G3: Identificador de empate (60-60 pontos).
    ];

    /**
     * Conversão de tipos (Casts) para garantir que a lógica de negócio opera com tipos nativos.
     */
    protected function casts(): array
    {
        return [
            'began_at' => 'datetime',
            'ended_at' => 'datetime',
            'player1_points' => 'integer',
            'player2_points' => 'integer',
            'total_time' => 'float',
            'is_draw' => 'boolean',
            'custom' => 'array',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * G4/G5: Relacionamentos com Users.
     * NF7: O uso de withTrashed() é obrigatório para preservar o histórico de jogos multiplayer
     * mesmo após a remoção (soft-delete) de uma conta.
     */
    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_user_id')->withTrashed();
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_user_id')->withTrashed();
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id')->withTrashed();
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_user_id')->withTrashed();
    }

    /**
     * G3: Relação com a Partida (Match).
     * Permite identificar a que conjunto de jogos esta vaza pertence (até atingir 4 marcas).
     */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /**
     * G2: Transações financeiras geradas por este jogo.
     * Associa o jogo a taxas de entrada, reembolsos por empate ou prémios de vitória.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class, 'game_id');
    }
}
