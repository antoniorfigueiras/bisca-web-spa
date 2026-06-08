<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCoinRequest extends FormRequest
{
    /**
     * G5: Determina se o utilizador está autorizado a realizar a compra.
     * Os administradores estão impedidos de possuir moedas ou realizar jogos, pelo que a compra
     * é exclusiva para jogadores (tipo 'P').
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'P';
    }

    /**
     * G2/C3: Regras de validação para a compra de moedas.
     * Implementa a validação de formatos de pagamento e as regras específicas do simulador externo.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * C3: Tipos de pagamento aceites pelo Gateway.
             */
            'type' => 'required|string|in:MBWAY,PAYPAL,IBAN,MB,VISA',

            /**
             * C3: O valor deve ser um inteiro positivo entre 1 e 99.
             * Inclui a simulação de insuficiência de fundos baseada em limites por método.
             */
            'value' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');

                    // Limites máximos simulados para desencadear erros de fundos insuficientes
                    $limits = [
                        'MBWAY' => 5,
                        'PAYPAL' => 10,
                        'MB' => 20,
                        'VISA' => 30,
                        'IBAN' => 50
                    ];

                    if (isset($limits[$type]) && $value > $limits[$type]) {
                        $fail("O simulador de gateway rejeita pagamentos $type acima de {$limits[$type]}€ (simulação de falta de saldo).");
                    }
                }
            ],

            /**
             * C3: Validação da Referência baseada no tipo de pagamento.
             * Inclui padrões que o simulador trata como contas inexistentes para testar erros da plataforma.
             */
            'reference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');

                    switch ($type) {
                        case 'MBWAY':
                            // 9 dígitos a começar por 9
                            if (!preg_match('/^9\d{8}$/', $value)) {
                                $fail('A referência MBWAY deve ter 9 dígitos e começar por 9.');
                            }
                            // Simulação de erro: Números começados por "90"
                            if (str_starts_with($value, '90')) {
                                $fail('O simulador trata números começados por "90" como inexistentes.');
                            }
                            break;

                        case 'PAYPAL':
                            // Email válido
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $fail('A referência PAYPAL deve ser um email válido.');
                            }
                            // Simulação de erro: Emails começados por "xx"
                            if (str_starts_with(strtolower($value), 'xx')) {
                                $fail('O simulador trata emails começados por "xx" como inexistentes.');
                            }
                            break;

                        case 'IBAN':
                            // 2 letras e 23 dígitos
                            if (!preg_match('/^[A-Z]{2}\d{23}$/', $value)) {
                                $fail('A referência IBAN deve ter 2 letras seguidas de 23 dígitos.');
                            }
                            // Simulação de erro: IBANs começados por "XX"
                            if (str_starts_with(strtoupper($value), 'XX')) {
                                $fail('O simulador trata IBANs começados por "XX" como inexistentes.');
                            }
                            break;

                        case 'MB':
                            // 5 dígitos (entidade), hífen, 9 dígitos (referência)
                            if (!preg_match('/^\d{5}-\d{9}$/', $value)) {
                                $fail('A referência MB deve estar no formato 00000-000000000.');
                            }
                            // Simulação de erro: Entidades começadas por 9
                            if (str_starts_with($value, '9')) {
                                $fail('O simulador trata entidades começadas por "9" como inexistentes.');
                            }
                            break;

                        case 'VISA':
                            // 16 dígitos a começar por 4
                            if (!preg_match('/^4\d{15}$/', $value)) {
                                $fail('A referência VISA deve ter 16 dígitos e começar por 4.');
                            }
                            // Simulação de erro: Cartões começados por "40"
                            if (str_starts_with($value, '40')) {
                                $fail('O simulador trata cartões começados por "40" como inexistentes.');
                            }
                            break;
                    }
                },
            ],
        ];
    }

    /**
     * NF5: Mensagens de erro em Português de Portugal para melhorar a usabilidade.
     */
    public function messages(): array
    {
        return [
            'type.required'    => 'É obrigatório selecionar um método de pagamento.',
            'type.in'          => 'O método de pagamento selecionado não é suportado.',
            'value.required'   => 'Deves indicar o valor que pretendes comprar.',
            'value.integer'    => 'O valor deve ser um número inteiro (incrementos de 1€).',
            'value.min'        => 'A compra mínima permitida é de 1€.',
            'value.max'        => 'O limite máximo por transação é de 99€.',
            'reference.required' => 'A referência de pagamento é obrigatória.',
            'reference.string'   => 'A referência deve ser um texto válido.',
        ];
    }
}
