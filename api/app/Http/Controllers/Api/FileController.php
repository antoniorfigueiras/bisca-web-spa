<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class FileController extends Controller
{
    /**
     * G1: Upload de foto/avatar de forma independente.
     * Este bloco permite carregar uma imagem antes da submissão final do perfil,
     * facilitando a experiência de pré-visualização na SPA Vue.js.
     * Route: POST /api/files/photos_avatars
     */
    public function store(Request $request)
    {
        /**
         * NF6: Validação rigorosa do ficheiro.
         * Limita o tamanho a 4MB e restringe os formatos a tipos de imagem comuns
         * para garantir a performance e segurança do servidor.
         */
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        /**
         * Armazenamento no disco público.
         * Os ficheiros são guardados na pasta 'photos_avatars' conforme a estrutura
         * definida na base de dados (coluna photo_avatar_filename).
         */
        $path = $request->file('photo')->store('photos_avatars', 'public');

        return response()->json([
            'message' => 'Upload realizado com sucesso.',
            'filename' => basename($path),
            'url' => asset('storage/' . $path)
        ], 201);
    }

    /**
     * G1: Serviço de entrega de imagens de avatar.
     * Este bloco permite à aplicação servir as fotos guardadas, garantindo que o
     * navegador interpreta corretamente o tipo de ficheiro (Content-Type).
     * Route: GET /api/files/photos_avatars/{filename}
     */
    public function show($filename)
    {
        /**
         * NF7: Proteção contra Directory Traversal.
         * Este bloco de lógica impede que utilizadores malintencionados tentem
         * aceder a ficheiros fora da pasta de avatares usando caminhos relativos.
         */
        if (preg_match('/\.\.+/', $filename) || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return response()->json(['message' => 'Nome de ficheiro inválido.'], 400);
        }

        $path = 'photos_avatars/' . $filename;

        /**
         * Verificação de existência no sistema de ficheiros.
         * Garante que o servidor não tenta processar ficheiros inexistentes,
         * retornando o erro 404 apropriado.
         */
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Imagem não encontrada.'], 404);
        }

        /**
         * Resolução do caminho físico no servidor.
         * Necessário para que a resposta HTTP possa ler o conteúdo do ficheiro
         * a partir do armazenamento definido.
         */
        $path = Storage::disk('public')->path('photos_avatars/' . $filename);

        if (!Storage::disk('public')->exists('photos_avatars/' . $filename)) {
            return response()->json(['message' => 'Imagem não encontrada.'], 404);
        }

        /**
         * NF5: Resposta com o ficheiro binário.
         * Devolve a imagem diretamente ao cliente Vue.js com os headers adequados.
         */
        return Response::file($path);
    }
}
