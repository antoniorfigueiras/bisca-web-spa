<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public static $startDate;
    public static $dbInsertBlockSize = 500;

    // public static $seedType = "small";
    //public static $seedType = "full";
    //public static $seedLanguage = "pt_PT";
    public static $seedLanguage = "en_US";

public function run(): void
    {
        // --- 1. CONFIGURAÇÃO INICIAL (NÃO MEXER) ---
        $this->command->info("-----------------------------------------------");
        $this->command->info("START of database seeder");
        $this->command->info("-----------------------------------------------");

        self::$startDate = Carbon::now()->subMonths(14);
        // Podes comentar a pergunta da língua se quiseres ser mais rápido, ou deixar estar.
        // self::$seedLanguage = $this->command->choice('What is the language for users\' names?', ['pt_PT', 'en_US'], 0);
        self::$seedLanguage = 'pt_PT';

        // Desativar chaves estrangeiras para limpar tabelas
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET foreign_key_checks=0');
            DB::statement("SET time_zone = '+00:00'");
        }

        // Limpar Tabelas Antigas
        DB::table('users')->delete();
        DB::table('matches')->delete();
        DB::table('games')->delete();
        DB::table('coin_purchases')->delete();
        DB::table('coin_transactions')->delete();
        DB::table('coin_transaction_types')->delete();

        // Resetar Auto-Increments
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'users'");
            // ... (outros deletes de sequência se necessário)
        } else {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE games AUTO_INCREMENT = 0');
            // ...
        }
        $this->command->info("Tabelas limpas com sucesso.");

        // --- 2. SEEDERS ESSENCIAIS ---

        // ESTE É OBRIGATÓRIO (Cria os tipos 'bonus', 'purchase', etc.)
        $this->call(TransactionTypesSeeder::class);


        // --- 3. A TUA LÓGICA DE TESTE (SUBSTITUI OS OUTROS SEEDERS) ---
        $this->command->info("A inserir dados de teste personalizados...");

        // A. Criar Utilizadores Genéricos (Oponentes para os jogos)
        User::factory(20)->create();

        // B. Criar o Teu User de Teste (Para fazeres Login)
        User::factory()->create([
            'name' => 'Eu Próprio',
            'email' => 'test@example.com', // LOGIN: test@example.com
            'nickname' => 'O_Mestre',
            'password' => bcrypt('password'), // PASSWORD: password
            'type' => 'P', // Jogador
            'coins_balance' => 500, // Saldo inicial
        ]);

        // C. Criar o Admin (Para testares o G5)
        User::factory()->create([
            'name' => 'Administrador Principal',
            'email' => 'admin@example.com', // LOGIN: admin@example.com
            'nickname' => 'AdminBoss',
            'password' => bcrypt('password'), // PASSWORD: password
            'type' => 'A', // Admin
        ]);

        // D. GERAR 50 JOGOS DE TESTE
        // (Isto popula o Histórico e o Leaderboard)
        \App\Models\Game::factory(50)->create();


        // --- 4. COMENTAR OS SEEDERS ANTIGOS ---
        // Comentamos isto porque eles poderiam entrar em conflito com os teus dados
        // ou depender de ficheiros que ainda não tens prontos.

        $this->call(UsersSeeder::class);
        $this->call(InitialTransactionsSeeder::class);
        $this->call(GamesSeeder::class);
        $this->call(GamesTransactionsSeeder::class);


        // --- 5. FINALIZAÇÃO ---
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET foreign_key_checks=1');
        }

        $this->command->info("-----------------------------------------------");
        $this->command->info("END of database seeder");
        $this->command->info("-----------------------------------------------");
    }
}
