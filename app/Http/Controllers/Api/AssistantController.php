<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Asisten belanja "Kyra". Riwayat percakapan disimpan di klien dan dikirim setiap kali bertanya. */
class AssistantController extends Controller
{
    public function __invoke(Request $request, AiService $ai): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:30'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        // Ambil 12 pesan terakhir dan pastikan diawali pesan pengguna.
        $history = collect($data['messages'])->take(-12)->values();
        while ($history->isNotEmpty() && $history->first()['role'] !== 'user') {
            $history->shift();
        }
        if ($history->isEmpty() || $history->last()['role'] !== 'user') {
            return response()->json(['message' => __('Pesan terakhir harus dari pengguna.')], 422);
        }

        try {
            $result = $ai->chat($history->map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']])->all());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json([
            'reply' => $result['reply'],
            'products' => ProductResource::collection($result['products']),
        ]);
    }
}
