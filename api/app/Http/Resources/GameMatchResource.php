<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class GameMatchResource extends JsonResource
{
    /**
     * G4/NF2: Transforma o modelo GameMatch num array JSON estruturado.
     * Este bloco garante que o cliente Vue.js recebe tipos de dados consistentes (Integers e Floats),
     * facilitando a renderização de pontuações e históricos na SPA.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificador único da partida.
            'id' => $this->id,

            /** * Variante da Bisca: '3' (Bisca de 3) ou '9' (Bisca de 9).
             * Essencial para cumprir o suporte obrigatório a ambas as variantes.
             */
            'type' => (string) $this->type,

            /**
             * Estado da Partida: 'Pending', 'Playing', 'Ended' ou 'Interrupted'.
             * Permite ao Frontend gerir o fluxo de navegação e exibição no Lobby.
             */
            'status' => $this->status,

            /**
             * G3: Valor da aposta individual (Stake).
             * Representa o montante que cada jogador apostou para entrar na partida.
             */
            'stake' => (int) $this->stake,

            /**
             * Relacionamentos: Utiliza UserResource para expor os perfis dos jogadores.
             * Garante que o coins_balance e o nickname estão atualizados na interface.
             */
            'player1' => new UserResource($this->whenLoaded('player1')),
            'player2' => new UserResource($this->whenLoaded('player2')),
            'winner' => new UserResource($this->whenLoaded('winner')),
            'loser' => new UserResource($this->whenLoaded('loser')),

            /**
             * G3/G4: Marcas (Marks ou Riscas).
             * O vencedor da partida é o primeiro a atingir 4 marcas.
             */
            'player1_marks' => (int) ($this->player1_marks ?? 0),
            'player2_marks' => (int) ($this->player2_marks ?? 0),

            /**
             * Pontuação Acumulada.
             * Soma total de pontos capturados em todos os jogos que compõem a partida.
             */
            'player1_points' => (int) ($this->player1_points ?? 0),
            'player2_points' => (int) ($this->player2_points ?? 0),

            /**
             * G4: Dados Temporais para Histórico.
             * Formatação uniforme para visualização de cronologia e duração total.
             */
            'began_at' => $this->began_at ? Carbon::parse($this->began_at)->format('Y-m-d H:i:s') : null,
            'ended_at' => $this->ended_at ? Carbon::parse($this->ended_at)->format('Y-m-d H:i:s') : null,
            'total_time' => $this->total_time ? (float) $this->total_time : 0,

            /**
             * Detalhes dos Jogos Individuais.
             * Permite listar cada vaza ou jogo isolado que ocorreu dentro desta partida.
             */
            'games' => GameResource::collection($this->whenLoaded('games')),

            /**
             * NF7: Metadados Customizáveis.
             * Campo JSON para armazenar informações extra definidas pela equipa.
             */
            'custom' => $this->custom,
        ];
    }
}
