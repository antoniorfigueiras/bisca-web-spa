<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * G5: Listar todos os utilizadores (jogadores e administradores).
     * Este bloco permite a monitorização global da plataforma, permitindo filtrar por tipo,
     * estado de bloqueio ou termos de pesquisa específicos.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // NF8: Filtro de Tipo opcional para permitir a visualização de "Todos" ou categorias específicas (A/P).
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        // NF5: Pesquisa avançada para facilitar a localização de contas por nome, nickname ou email.
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // G5: Filtro de utilizadores bloqueados para rápida identificação de contas restritas.
        if ($request->filled('blocked')) {
            $query->where('blocked', $request->query('blocked'));
        }

        // NF8: Ordenação por nome e paginação para otimizar o carregamento da lista no cliente Vue.js.
        $users = $query->orderBy('name')->paginate(10);

        return UserResource::collection($users);
    }

    /**
     * G5: Criar uma nova conta de administrador.
     * Este bloco garante que novos administradores só podem ser criados por outros administradores
     * e não via registo público, cumprindo a restrição de segurança da plataforma.
     */
    public function store(Request $request)
    {
        // NF6/G1: Validação rigorosa dos dados, exigindo unicidade e o mínimo de 3 caracteres na password.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'nickname' => 'required|string|max:20|unique:users,nickname',
            'password' => 'required|string|min:3',
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nickname' => $validated['nickname'],
            'password' => Hash::make($validated['password']),
            'type' => 'A',          // G5: Define explicitamente o tipo como Administrador.
            'coins_balance' => 0,   // G5: Garante que administradores não possuem moedas.
            'blocked' => 0,
        ]);

        return new UserResource($admin);
    }

    /**
     * G5: Bloquear ou desbloquear um jogador.
     * Este bloco permite suspender o acesso de jogadores que infrinjam as regras,
     * protegendo a integridade dos jogos multiplayer.
     */
    public function toggleBlock(User $user)
    {
        // G5: Proteção que impede que administradores bloqueiem outros administradores.
        if ($user->type === 'A') {
            return response()->json([
                'message' => 'Não é permitido bloquear ou desbloquear contas de administradores.'
            ], 403);
        }

        $user->blocked = !$user->blocked;
        $user->save();

        $status = $user->blocked ? 'bloqueado' : 'desbloqueado';

        return response()->json([
            'message' => "O jogador {$user->nickname} foi {$status} com sucesso.",
            'user' => new UserResource($user)
        ]);
    }

    /**
     * G5: Remover conta de utilizador.
     * Este bloco gere a eliminação de contas, aplicando obrigatoriamente "soft-delete" se houver histórico
     * financeiro ou de jogo para preservar a integridade dos dados da plataforma.
     */
    public function destroy(Request $request, User $user)
    {
        // G5: Restrição que impede um administrador de remover a sua própria conta.
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Um administrador não pode remover a sua própria conta.'
            ], 403);
        }

        // G5: Verificação de atividade prévia (transações ou jogos multiplayer).
        $hasActivity = $user->coinTransactions()->exists() ||
            $user->gamesAsPlayer1()->exists() ||
            $user->gamesAsPlayer2()->exists();

        if ($hasActivity) {
            // G5: Aplica soft-delete para manter registos históricos obrigatórios.
            $user->delete();
            return response()->json(['message' => 'Conta de jogador removida logicamente (soft-delete) para preservar o histórico.']);
        }

        // Caso a conta não tenha atividade, procede-se à remoção física da base de dados.
        $user->forceDelete();
        return response()->json(['message' => 'Conta removida permanentemente da plataforma.']);
    }
}
