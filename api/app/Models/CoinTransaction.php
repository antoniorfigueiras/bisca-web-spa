<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoinTransaction extends Model
{
    use HasFactory;

    /**
     * Tabela associada ao modelo conforme o esquema oficial fornecido.
     */
    protected $table = 'coin_transactions';

    /**
     * Desativação dos timestamps padrão do Laravel.
     * A tabela utiliza a coluna 'transaction_datetime' para registar o momento exato do movimento.
     */
    public $timestamps = false;

    /**
     * Atributos preenchíveis em massa (Mass Assignment).
     * Este bloco permite registar bónus, taxas de jogo e prémios de forma eficiente.
     */
    protected $fillable = [
        'user_id',                  // ID do proprietário da transação
        'coin_transaction_type_id', // Tipo de movimento (Ex: 'Bonus', 'Game payout')
        'transaction_datetime',     // Data e hora da ocorrência
        'coins',                    // G2: Créditos (positivos) ou débitos (negativos)
        'game_id',                  // G3: Ligação a um jogo individual
        'match_id',                 // G3: Ligação a uma partida completa
        'custom',                   // JSON para dados adicionais ou metadados da equipa
    ];

    /**
     * Conversão de tipos (Casts).
     * Garante que os valores inteiros de moedas e campos JSON sejam tratados corretamente pelo PHP.
     */
    protected function casts(): array
    {
        return [
            'transaction_datetime' => 'datetime',
            'coins' => 'integer',
            'custom' => 'array',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * O utilizador proprietário da transação.
     * NF7/G5: O uso de withTrashed() garante que o registo financeiro é preservado mesmo
     * após o soft-delete da conta do jogador.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Define a natureza da transação (Crédito ou Débito).
     * Permite identificar a origem do movimento (ex: Welcome Bonus) conforme as regras de negócio.
     */
    public function transaction_type(): BelongsTo
    {
        return $this->belongsTo(CoinTransactionType::class, 'coin_transaction_type_id');
    }

    /**
     * G2: Ligação aos detalhes de pagamento externo.
     * Se a transação for do tipo 'Coin purchase', este bloco fornece acesso aos dados de euros e referências.
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(CoinPurchase::class, 'coin_transaction_id');
    }

    /**
     * G3: O jogo individual associado ao movimento.
     * Utilizado para registar taxas de entrada (2 moedas) ou prémios de vitória (3, 4 ou 6 moedas).
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * G3: A partida (match) associada ao movimento.
     * Utilizado para registar o stake da partida e o payout final após as 4 marcas.
     */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
