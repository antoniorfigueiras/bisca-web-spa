<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class GameResource extends JsonResource
{
    /**
     * G4/NF2: Transforma o modelo Game num array JSON formatado.
     * Este bloco garante a consistência dos dados para o frontend em Vue.js, convertendo tipos da BD para formatos padrão.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificador único do jogo.
            'id' => $this->id,

            /** * Variante da Bisca.
             * '3' para Bisca de 3 (mão de 3 cartas) ou '9' para Bisca de 9 (mão de 9 cartas).
             */
            'type' => (string) $this->type,

            /** * Estado atual do jogo.
             * Pode assumir valores como 'Pending', 'Playing', 'Ended' ou 'Interrupted'.
             */
            'status' => $this->status,

            /** * Relacionamentos protegidos via UserResource (NF7).
             * Inclui os jogadores participantes, o vencedor e o perdedor.
             */
            'player1' => new UserResource($this->whenLoaded('player1')),
            'player2' => new UserResource($this->whenLoaded('player2')),
            'winner' => new UserResource($this->whenLoaded('winner')),
            'loser' => new UserResource($this->whenLoaded('loser')),

            /** * Pontuação dos jogadores.
             * A soma dos pontos deve totalizar sempre 120 (pontos totais do baralho).
             */
            'player1_points' => (int) $this->player1_points,
            'player2_points' => (int) $this->player2_points,

            /** * Indicador de empate.
             * Ocorre quando ambos os jogadores terminam com 60 pontos.
             */
            'is_draw' => (bool) $this->is_draw,

            /** * Metadados temporais (G4).
             * Regista os momentos de início e fim do jogo para o histórico.
             */
            'began_at' => $this->began_at ? Carbon::parse($this->began_at)->format('Y-m-d H:i:s') : null,
            'ended_at' => $this->ended_at ? Carbon::parse($this->ended_at)->format('Y-m-d H:i:s') : null,

            /** * Duração total do jogo em segundos (G4).
             * Se o jogo terminou, usa o valor persistido; caso contrário, calcula em tempo real.
             */
            'total_time' => $this->total_time ? (float) $this->total_time : $this->calculateCurrentDuration(),

            /** * Identificador da partida (Match).
             * Atribuído apenas se o jogo fizer parte de um conjunto (até 4 marcas).
             */
            'match_id' => $this->match_id,

            /** * Campo JSON para informações adicionais personalizadas.
             */
            'custom' => $this->custom,
        ];
    }

    /**
     * Calcula a duração em tempo real para jogos em progresso (NF5).
     * Este método melhora a usabilidade ao fornecer o tempo de jogo atualizado na interface.
     */
    private function calculateCurrentDuration()
    {
        if ($this->began_at && !$this->ended_at) {
            return Carbon::parse($this->began_at)->diffInSeconds(now());
        }
        return 0;
    }
}
