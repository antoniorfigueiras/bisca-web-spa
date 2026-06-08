<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * G1/NF2: Transforma o modelo User num array JSON estruturado.
     * Este bloco assegura que o frontend recebe apenas os dados necessários, omitindo campos
     * sensíveis como a 'password' ou 'remember_token' para cumprir o requisito NF7.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Identificador único do utilizador (BIGINT) conforme definido no esquema da base de dados.
             */
            'id' => $this->id,

            /**
             * Dados básicos de perfil (G1): Nome, Email e Nickname único.
             */
            'name' => $this->name,
            'email' => $this->email,
            'nickname' => $this->nickname,

            /**
             * Tipo de Utilizador (G5): 'A' para Administrador ou 'P' para Jogador.
             * Administradores têm permissões especiais de gestão, mas não podem jogar ou ter moedas.
             */
            'type' => $this->type,

            /**
             * Estado de Bloqueio (G5): Indica se o acesso do utilizador foi suspenso pela administração.
             */
            'blocked' => (bool) $this->blocked,

            /**
             * Saldo de Moedas (G2): Saldo atual disponível para jogos multiplayer e entradas em matches.
             * Jogadores novos começam com um bónus de 10 moedas.
             */
            'coins_balance' => (int) $this->coins_balance,

            /**
             * Transformação do Avatar (G1):
             * Se o utilizador tiver um ficheiro de imagem, gera o URL público para o frontend.
             * Caso contrário, utiliza um serviço externo para gerar um avatar dinâmico baseado no nickname (NF5).
             */
            'photo_url' => $this->photo_avatar_filename
                ? asset('storage/photos_avatars/' . $this->photo_avatar_filename)
                : "https://ui-avatars.com/api/?name=" . urlencode($this->nickname) . "&background=random",

            /**
             * Metadados de Auditoria (G4): Regista a data de criação e, se aplicável, a data de remoção lógica (Soft-Delete).
             */
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,

            /**
             * Campo JSON (NF7): Para armazenar informações adicionais personalizadas pela equipa de desenvolvimento.
             */
            'custom' => $this->custom,
        ];
    }
}
