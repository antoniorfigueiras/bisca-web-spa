<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * G1: Registo de novos utilizadores.
     * Este bloco lida com a criação de contas de jogadores, garantindo a unicidade de emails e nicknames,
     * e atribuindo automaticamente o bónus de boas-vindas.
     * Route: POST /api/register
     */
    public function register(Request $request)
    {
        /**
         * G5/G1: Verificação de contas removidas logicamente (soft-delete).
         * Se um utilizador tentar registar-se com um email que já existe mas foi "apagado",
         * o sistema oferece a opção de recuperação para manter a integridade do histórico.
         */
        $user = User::withTrashed()->where('email', $request->email)->first();

        if ($user && $user->trashed()) {
            return response()->json([
                'message' => 'Esta conta foi eliminada. Queres recuperá-la?',
                'restore_available' => true,
                'email' => $user->email
            ], 409);
        }

        /**
         * NF6: Validação de inputs.
         * Garante que a password tem o mínimo de 3 caracteres e que os campos
         * obrigatórios respeitam o modelo de dados da plataforma.
         */
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'nickname'   => 'required|string|max:20|unique:users,nickname',
            'password'   => 'required|string|min:3|confirmed',
            'photo_file' => 'nullable|image|max:4096',
        ]);

        /**
         * G1: Gestão opcional de avatar.
         * Armazena a imagem de perfil no sistema de ficheiros se o utilizador a fornecer.
         */
        $photoFilename = null;
        if ($request->hasFile('photo_file')) {
            $path = $request->file('photo_file')->store('photos_avatars', 'public');
            $photoFilename = basename($path);
        }

        /**
         * NF8: Transação de Base de Dados.
         * Este bloco garante atomicidade: o utilizador só é criado se o bónus de 10 moedas
         * for registado com sucesso no histórico de transações.
         */
        $user = DB::transaction(function () use ($validated, $photoFilename) {

            // Criação do perfil do jogador ('P') com o bónus inicial definido nas regras.
            $newUser = User::create([
                'name'                  => $validated['name'],
                'email'                 => $validated['email'],
                'nickname'              => $validated['nickname'],
                'password'              => Hash::make($validated['password']),
                'type'                  => 'P',
                'coins_balance'         => 10,
                'blocked'               => 0,
                'photo_avatar_filename' => $photoFilename,
            ]);

            /**
             * G2: Registo da transação de bónus.
             * Mantém o histórico imutável para consulta posterior pelo utilizador ou administrador.
             */
            $bonusType = CoinTransactionType::firstOrCreate(
                ['name' => 'bonus'],
                ['type' => 'C']
            );

            CoinTransaction::create([
                'user_id'                  => $newUser->id,
                'coin_transaction_type_id' => $bonusType->id,
                'transaction_datetime'     => now(),
                'coins'                    => 10,
                'custom'                   => ['description' => 'Welcome Bonus'],
            ]);

            return $newUser;
        });

        // NF7: Geração de token de acesso para autenticação SPA via Sanctum.
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ], 201);
    }

    /**
     * G1: Recuperação de conta (Restore).
     * Este bloco permite reativar uma conta que tenha sofrido soft-delete,
     * validando novamente as credenciais para segurança.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::withTrashed()->where('email', $request->email)->first();

        if (!$user || !$user->trashed() || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais incorretas para recuperação.'], 401);
        }

        // Restaura o acesso e preserva o histórico anterior de jogos e moedas.
        $user->restore();

        return response()->json(['message' => 'Conta recuperada com sucesso!']);
    }

    /**
     * G1: Autenticação de utilizadores (Login).
     * Valida as credenciais (email e password) e verifica o estado de bloqueio da conta.
     * Route: POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        /**
         * NF7: Verificação de credenciais.
         * Protege os dados do utilizador contra acessos não autorizados.
         */
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais introduzidas estão incorretas.'],
            ]);
        }

        /**
         * G5: Verificação de bloqueio administrativo.
         * Impede que utilizadores marcados como bloqueados iniciem sessão na plataforma.
         */
        if ($user->blocked) {
            return response()->json([
                'message' => 'A sua conta encontra-se bloqueada. Contacte a administração.'
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ]);
    }

    /**
     * G1: Obter perfil do utilizador autenticado.
     * Retorna os detalhes do utilizador atual para preencher o perfil na SPA Vue.js.
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * G1: Terminar sessão (Logout).
     * Revoga o token atual para garantir que o acesso é encerrado de forma segura.
     * Route: POST /api/logout
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Sessão terminada com sucesso.'
        ]);
    }
}
