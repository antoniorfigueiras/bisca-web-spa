<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Game;
use App\Models\CoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * G1: Consulta de Perfil.
     * Este bloco devolve os dados de um utilizador específico.
     * O uso de UserResource garante que campos sensíveis, como a password, não sejam expostos.
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * G1: Atualização de Perfil.
     * Permite ao utilizador autenticado alterar o seu email, nickname, nome, foto e password.
     * Este bloco também lida com permissões administrativas para alteração de tipos de conta.
     */
    public function update(UpdateProfileRequest $request, User $user)
    {
        /**
         * NF7: Autorização de Segurança.
         * Garante que um utilizador apenas atualiza o seu próprio perfil, a menos que seja um Administrador.
         */
        $this->authorize('update', $user);

        $validated = $request->validated();

        /**
         * G1: Gestão de Foto/Avatar.
         * Implementa a lógica de substituição ou remoção física do ficheiro no disco se o utilizador optar por apagar a foto.
         */
        if ($request->boolean('delete_photo') || $request->hasFile('photo_file')) {
            if ($user->photo_avatar_filename) {
                Storage::disk('public')->delete('photos_avatars/' . $user->photo_avatar_filename);
                $user->photo_avatar_filename = null;
            }
        }

        if ($request->hasFile('photo_file')) {
            $path = $request->file('photo_file')->store('photos_avatars', 'public');
            $user->photo_avatar_filename = basename($path);
        }

        // Atualização dos campos fundamentais do perfil.
        $user->fill([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'nickname' => $validated['nickname'] ?? null,
        ]);

        // Encriptação da nova password se esta tiver sido fornecida no pedido.
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        /**
         * G5: Gestão Administrativa.
         * Permite que administradores alterem o tipo de conta ('A' ou 'P'), impedindo que removam os seus próprios privilégios.
         */
        if ($request->user()->type === 'A' && $request->has('type')) {
            $newType = $request->input('type');
            if ($user->id === $request->user()->id && $newType !== 'A') {
                return response()->json(['message' => 'Não pode remover os seus próprios privilégios.'], 403);
            }
            if (in_array($newType, ['A', 'P'])) {
                $user->type = $newType;
            }
        }

        $user->save();

        return new UserResource($user);
    }

    /**
     * G1/G5: Remoção de Conta.
     * Este bloco implementa a eliminação de contas com proteção contra perda de histórico.
     * Se houver atividade financeira ou de jogo, a conta é apenas "desativada" (soft-delete).
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        /**
         * G5: Restrição de Administrador.
         * Impede que um administrador apague a sua própria conta através deste endpoint.
         */
        if ($user->type === 'A' && $request->user()->id === $user->id) {
            return response()->json(['message' => 'Administradores não podem apagar a própria conta.'], 403);
        }

        /**
         * G1: Confirmação de Segurança.
         * Exige a password atual para confirmar a eliminação voluntária da conta, protegendo contra acessos indevidos.
         */
        if ($request->user()->id === $user->id) {
            $request->validate(['password' => 'required|string']);
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'A password introduzida está incorreta.',
                ], 422);
            }
        }

        /**
         * G5: Verificação de Integridade.
         * Verifica se o utilizador já realizou transações ou jogou partidas multiplayer.
         */
        $hasActivity = CoinTransaction::where('user_id', $user->id)->exists() ||
                       Game::where('player1_user_id', $user->id)->orWhere('player2_user_id', $user->id)->exists();

        return DB::transaction(function () use ($user, $hasActivity) {
            if ($hasActivity) {
                /**
                 * G1/G5: Remoção Lógica (Soft-Delete).
                 * O utilizador perde as moedas, mas os registos históricos são preservados para integridade da plataforma.
                 */
                $user->update(['coins_balance' => 0]);
                $user->delete();

                return response()->json(['message' => 'A conta foi desativada com sucesso. O histórico foi preservado.']);
            }

            /**
             * Remoção Física (Hard-Delete).
             * Se não houver atividade, todos os dados e ficheiros (foto) são removidos permanentemente.
             */
            if ($user->photo_avatar_filename) {
                Storage::disk('public')->delete('photos_avatars/' . $user->photo_avatar_filename);
            }
            $user->forceDelete();

            return response()->json(['message' => 'A conta e todos os dados associados foram eliminados permanentemente.']);
        });
    }
}
