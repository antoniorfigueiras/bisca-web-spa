<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     * O login é uma rota pública necessária para obter o token de acesso inicial.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * G1/NF6: Regras de validação para as credenciais de acesso.
     * Este bloco garante que os dados submetidos cumprem os requisitos mínimos para a autenticação.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // O email é o identificador único obrigatório para a autenticação no sistema.
            'email' => 'required|email|string',

            // A password é obrigatória para validar a identidade do utilizador contra o hash guardado.
            'password' => 'required|string',
        ];
    }

    /**
     * NF5: Mensagens de erro personalizadas para melhorar a usabilidade.
     * Este bloco fornece feedback claro em Português de Portugal, reduzindo o esforço do utilizador em caso de erro.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O endereço de email é obrigatório para iniciar sessão.',
            'email.email'    => 'Por favor, introduza um endereço de email válido.',
            'password.required' => 'A password é necessária para aceder à sua conta.',
        ];
    }
}
