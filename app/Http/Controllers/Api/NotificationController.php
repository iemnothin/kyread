<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\OrderUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = $request->user()->notifications()->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'title' => OrderUpdated::titleFor($n->data),
                'body' => OrderUpdated::bodyFor($n->data),
                'order_code' => $n->data['order_code'] ?? null,
                'audience' => $n->data['audience'] ?? 'buyer',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }
}
