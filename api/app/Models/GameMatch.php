<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    /**
     * O nome da tabela é 'matches' conforme definido no esquema oficial.
     */
    protected $table = 'matches';

    /**
     * Atributos preenchíveis em massa.
     * Este bloco inclui os campos necessários para o sistema de marcas (riscas) e o stake (aposta) acordado.
     */
    protected $fillable = [
        'type',            // G3: Variante '3' ou '9' cartas utilizada em toda a partida.
        'status',          // G3: Estado da partida: 'Pending', 'Playing', 'Ended', 'Interrupted'.
        'began_at',        // G4: Registo do início da partida para histórico.
        'ended_at',        // G4: Registo do fim da partida para histórico.
        'player1_user_id', // G3: ID do utilizador que criou a partida.
        'player2_user_id', // G3: ID do utilizador que aceitou o desafio.
        'winner_user_id',  // G3: Vencedor final da partida.
        'loser_user_id',   // G3: Jogador derrotado na partida.
        'player1_marks',   // G3: Marcas (riscas) acumuladas pelo P1; 4 marcas garantem a vitória.
        'player2_marks',   // G3: Marcas (riscas) acumuladas pelo P2.
        'player1_points',  // G4: Pontuação acumulada por P1 ao longo de todos os jogos da partida.
        'player2_points',  // G4: Pontuação acumulada por P2 ao longo de todos os jogos da partida.
        'total_time',      // G4: Duração total da partida em segundos.
        'stake',           // G2/G3: Valor da aposta por jogador (mínimo 3, máximo 100 moedas).
        'custom',          // NF7: Campo JSON para armazenar dados adicionais personalizados.
    ];

    /**
     * Conversão de tipos (Casts).
     * Garante que os valores numéricos, datas e campos JSON sejam tratados corretamente pelo PHP.
     */
    protected function casts(): array
    {
        return [
            'began_at' => 'datetime',
            'ended_at' => 'datetime',
            'player1_marks' => 'integer',
            'player2_marks' => 'integer',
            'player1_points' => 'integer',
            'player2_points' => 'integer',
            'stake' => 'integer',
            'total_time' => 'float',
            'custom' => 'array',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * G3: Uma partida (Match) contém vários jogos (Games).
     * A partida prossegue através de vários jogos até um jogador atingir as 4 marcas.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'match_id');
    }

    /**
     * G1/G5: Relacionamentos com os utilizadores.
     * NF7: O uso de withTrashed() é fundamental para preservar o histórico de partidas multiplayer
     * mesmo que uma conta de jogador seja removida (soft-delete).
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

    /**
     * G2: Transações financeiras desta partida.
     * Este relacionamento permite auditar o fluxo de moedas, incluindo o pagamento do stake
     * e o prémio final ao vencedor (Soma dos Stakes - 1 moeda de comissão).
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class, 'match_id');
    }
}
