<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinTransactionType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabela associada ao modelo conforme o esquema oficial da base de dados.
     */
    protected $table = 'coin_transaction_types';

    /**
     * Esta tabela utiliza a coluna 'deleted_at' para suportar soft-delete e manter a integridade histórica.
     * Os timestamps padrão (created_at/updated_at) não são exigidos pelo esquema fornecido.
     */
    public $timestamps = false;

    /**
     * Atributos preenchíveis em massa.
     * Este bloco define as propriedades fundamentais para categorizar movimentos financeiros.
     */
    protected $fillable = [
        'name',   // Exemplos: 'Bonus', 'Coin purchase', 'Game fee', 'Match stake'.
        'type',   // 'C' para Crédito (aumenta o saldo) ou 'D' para Débito (reduz o saldo).
        'custom', // Campo JSON para armazenar informações adicionais conforme a necessidade da equipa.
    ];

    /**
     * Conversão de tipos (Casts).
     * Garante que os metadados JSON sejam convertidos em arrays e as datas em objetos Carbon.
     */
    protected function casts(): array
    {
        return [
            'custom' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    // --- RELACIONAMENTOS ---

    /**
     * G2: Todas as transações financeiras associadas a este tipo específico.
     * Este relacionamento permite listar todos os movimentos de uma determinada categoria.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class, 'coin_transaction_type_id');
    }

    // --- MÉTODOS AUXILIARES ---

    /**
     * G2: Verifica se o tipo representa um aumento no saldo do jogador ('C').
     * Útil para validar bónus, compras e prémios de vitória (payouts).
     */
    public function isCredit(): bool
    {
        return $this->type === 'C';
    }

    /**
     * G2: Verifica se o tipo representa uma redução no saldo do jogador ('D').
     * Útil para validar taxas de entrada (fees) e apostas (stakes).
     */
    public function isDebit(): bool
    {
        return $this->type === 'D';
    }
}
