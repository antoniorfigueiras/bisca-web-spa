<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\CoinPurchase;

class GlobalStatsController extends Controller
{
    /**
     * G6: Fornece estatísticas agregadas e anonimizadas.
     * Este bloco existe para permitir que utilizadores anónimos e jogadores vejam dados genéricos da plataforma.
     */
    public function summary()
    {
        return response()->json([
            // Conta todos os utilizadores do tipo Player ('P').
            'total_players' => User::where('type', 'P')->count(),
            // Filtra apenas jogos multiplayer (onde existe um segundo jogador).
            'total_multiplayer_games' => Game::whereNotNull('player2_user_id')->count(),
            // Calcula o total de moedas geradas com base na taxa de conversão €1 = 10 moedas.
            'total_revenue_coins' => (int) (CoinPurchase::sum('euros') * 10),
            // Monitoriza jogos que estão atualmente a decorrer no servidor.
            'active_games' => Game::whereIn('status', ['PL', 'Playing'])->count(),
        ]);
    }

    /**
     * G6: Painel administrativo com acesso completo e não anonimizado.
     * Este bloco garante que apenas administradores ('A') acedam a métricas detalhadas e séries temporais.
     */
    public function index(Request $request)
    {
        // NF7: Verificação de autorização para proteger dados sensíveis da plataforma.
        if ($request->user()->type !== 'A') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json([
            'summary' => $this->getSummaryStats(),
            'charts' => [
                'purchases_by_day' => $this->getPurchasesTimeSeries(),
                'games_by_variant' => $this->getGamesByVariant(),
                'user_registrations' => $this->getRegistrationStats()
            ]
        ]);
    }

    /**
     * Reutiliza a lógica de sumário para o painel administrativo.
     */
    private function getSummaryStats()
    {
        return [
            'total_players' => User::where('type', 'P')->count(),
            'total_multiplayer_games' => Game::whereNotNull('player2_user_id')->count(),
            'total_revenue_coins' => (int) (CoinPurchase::sum('euros') * 10),
            'active_games' => Game::whereIn('status', ['PL', 'Playing'])->count(),
        ];
    }

    /**
     * G6: Série temporal de compras por período.
     * Este bloco agrupa transações financeiras dos últimos 30 dias para visualização em gráficos.
     */
    private function getPurchasesTimeSeries()
    {
        return CoinPurchase::select(
            DB::raw('DATE(purchase_datetime) as date'),
            DB::raw('SUM(euros) as total_euros'),
            DB::raw('COUNT(*) as transactions_count')
        )
            ->where('purchase_datetime', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * G6: Volume de jogos por variante ("Bisca de 3" vs "Bisca de 9").
     * Permite à administração analisar qual a variante mais popular entre os jogadores.
     */
    private function getGamesByVariant()
    {
        return Game::select('type', DB::raw('count(*) as count'))
            ->whereNotNull('player2_user_id')
            ->groupBy('type')
            ->get();
    }

    /**
     * G6: Estatísticas de crescimento de utilizadores.
     * Implementado com suporte multi-base de dados para garantir portabilidade entre SQLite (testes) e MySQL (produção).
     */
    private function getRegistrationStats()
    {
        // NF8: Otimização de recursos ao delegar a formatação de datas para o motor da base de dados.
        $driver = DB::connection()->getDriverName();
        $format = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        return User::select(
            DB::raw("$format as month"),
            DB::raw('count(*) as count')
        )
            ->where('type', 'P')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
