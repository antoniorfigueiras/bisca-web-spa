<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     * O registo é uma operação pública aberta a qualquer visitante que pretenda criar uma conta de jogador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * G1/NF6: Regras de validação para o registo de novos utilizadores.
     * Implementa os requisitos de unicidade, formato e as restrições de segurança obrigatórias.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // O nome real do utilizador é obrigatório para o perfil.
            'name' => 'required|string|max:255',

            // O email deve ser único na plataforma para servir como credencial de autenticação.
            'email' => 'required|string|email|max:255|unique:users,email',

            // O nickname deve ser único e é limitado a 20 caracteres para exibição em jogos e leaderboards.
            'nickname' => 'required|string|max:20|unique:users,nickname',

            // A password deve ter um comprimento mínimo de 3 caracteres conforme especificado.
            'password' => 'required|string|min:3|confirmed',

            // O upload de uma foto ou avatar é opcional no momento do registo.
            'photo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ];
    }

    /**
     * NF5: Mensagens de erro personalizadas em Português de Portugal.
     * Este bloco melhora a usabilidade ao fornecer feedback claro e contextualizado ao utilizador.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O endereço de email é obrigatório.',
            'email.unique' => 'Este endereço de email já se encontra registado na plataforma.',
            'nickname.required' => 'O nickname é obrigatório para o ranking e jogos.',
            'nickname.unique' => 'Este nickname já está em uso por outro jogador.',
            'nickname.max' => 'O nickname não pode ter mais de 20 caracteres.',
            'password.required' => 'A password é obrigatória.',
            'password.min' => 'A password deve ter pelo menos 3 caracteres conforme as regras.',
            'password.confirmed' => 'A confirmação da password não coincide.',
            'photo_file.image' => 'O ficheiro selecionado deve ser uma imagem válida.',
            'photo_file.max' => 'A imagem não pode ultrapassar os 4MB.',
        ];
    }
}
