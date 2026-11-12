<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);
        $unreadIds = $notifications->getCollection()->whereNull('read_at')->pluck('id');

        // Tampilkan dulu sebagai "baru", lalu tandai dibaca.
        $view = view('notifications.index', ['notifications' => $notifications, 'unreadIds' => $unreadIds]);
        $request->user()->unreadNotifications()->whereKey($unreadIds)->update(['read_at' => now()]);

        return $view;
    }

    /** Buka notifikasi: tandai dibaca lalu arahkan ke pesanan terkait. */
    public function open(Request $request, string $id): RedirectResponse
    {
        /** @var DatabaseNotification $notification */
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $code = $notification->data['order_code'] ?? null;

        return match (true) {
            $code === null => redirect()->route('notifications.index'),
            ($notification->data['audience'] ?? null) === 'seller' => redirect()->route('seller.orders.show', $code),
            default => redirect()->route('orders.show', $code),
        };
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
