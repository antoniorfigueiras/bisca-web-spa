<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     * Este bloco delega a autorização para a Policy ou para o Controller,
     * simplificando a lógica de validação de dados (NF7).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * G1/NF6: Regras de validação para atualização do perfil.
     * Este método permite a alteração de dados sensíveis (email, nickname, password)
     * garantindo que não existem conflitos com outros utilizadores na base de dados.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtemos o ID do utilizador através da rota para permitir que o sistema
        // ignore o registo atual na validação de campos únicos como email e nickname.
        $routeParam = $this->route('user');
        $userId = is_object($routeParam) ? $routeParam->id : $routeParam;

        return [
            // Nome real do utilizador conforme os requisitos de registo (G1).
            'name' => 'required|string|max:255',

            /**
             * Validação de Email.
             * Deve ser um email válido e único na tabela 'users', exceto para o utilizador atual.
             */
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],

            /**
             * Validação de Nickname.
             * Requisito G1: O nickname deve ser único e ter no máximo 20 caracteres.
             */
            'nickname' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($userId),
            ],

            /**
             * Validação de Password.
             * É opcional na edição; se for fornecida, deve cumprir o mínimo de 3 caracteres (G1).
             */
            'password' => 'nullable|string|min:3|confirmed',

            /**
             * Gestão de Avatar.
             * Permite o upload opcional de novas imagens com limite de 4MB para otimização de performance (NF8).
             */
            'photo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',

            /**
             * Remoção de Foto.
             * Flag lógica que permite ao utilizador remover o avatar atual sem carregar um novo ficheiro.
             */
            'delete_photo' => 'nullable|boolean',
        ];
    }

    /**
     * NF5: Mensagens de erro personalizadas em Português de Portugal.
     * Este bloco melhora a usabilidade ao fornecer feedback específico para falhas na atualização.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este endereço de email já está registado noutra conta.',
            'nickname.unique' => 'Este nickname já está a ser utilizado por outro jogador.',
            'password.min' => 'A nova password deve ter, pelo menos, 3 caracteres.',
            'password.confirmed' => 'A confirmação da nova password não coincide.',
            'photo_file.max' => 'A fotografia de perfil não pode ter um tamanho superior a 4MB.',
            'photo_file.image' => 'O ficheiro selecionado deve ser uma imagem válida.',
        ];
    }
}
