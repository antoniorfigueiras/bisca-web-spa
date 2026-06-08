<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinPurchase extends Model
{
    use HasFactory;

    /**
     * Tabela associada ao modelo conforme o esquema oficial da base de dados.
     */
    protected $table = 'coin_purchases';

    /**
     * Desativação dos timestamps padrão do Laravel (created_at/updated_at).
     * A tabela utiliza a coluna 'purchase_datetime' para registar o momento da transação.
     */
    public $timestamps = false;

    /**
     * Atributos preenchíveis em massa (Mass Assignment).
     * Este bloco inclui os campos obrigatórios para o registo detalhado de compras externas (G2).
     */
    protected $fillable = [
        'user_id',             // Identificador do comprador
        'coin_transaction_id', // Ligação à transação de moedas correspondente
        'euros',               // Montante real cobrado em Euros
        'payment_type',        // Método utilizado: MBWAY, PAYPAL, IBAN, MB ou VISA
        'payment_reference',   // Referência de pagamento validada pelo Gateway
        'purchase_datetime',   // Data e hora da operação financeira
        'custom',              // Campo JSON para informações adicionais da equipa
    ];

    /**
     * Conversão de tipos (Casts).
     * Este bloco garante que os valores financeiros (euros) e as datas sejam tratados como tipos nativos do PHP.
     */
    protected function casts(): array
    {
        return [
            'euros' => 'float',
            'purchase_datetime' => 'datetime',
            'custom' => 'array',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * G5: O comprador associado à transação.
     * Este bloco utiliza 'withTrashed' para garantir que o histórico de compras permanece acessível
     * mesmo que o jogador tenha removido a sua conta (Soft-Delete).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * G2: A transação de moedas gerada por esta compra.
     * Este relacionamento é fundamental para cruzar os dados do histórico de moedas com o
     * pagamento real processado pelo Gateway.
     */
    public function coinTransaction(): BelongsTo
    {
        return $this->belongsTo(CoinTransaction::class, 'coin_transaction_id');
    }
}
