<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const array REVENUE_STATUSES = ['paid', 'processing', 'shipped', 'completed'];

    public function __invoke(Request $request): View
    {
        $store = $request->user()->store;
        $orders = $store->orders();

        $revenueOrders = (clone $orders)->whereIn('status', self::REVENUE_STATUSES);

        // Penjualan 14 hari terakhir untuk grafik.
        $from = now()->subDays(13)->startOfDay();
        $daily = (clone $revenueOrders)
            ->where('paid_at', '>=', $from)
            ->get(['paid_at', 'total'])
            ->groupBy(fn ($o) => $o->paid_at->toDateString())
            ->map->sum('total');

        $chart = collect(range(0, 13))->map(function (int $i) use ($from, $daily) {
            $date = $from->copy()->addDays($i)->toDateString();

            return ['date' => Carbon::parse($date)->translatedFormat('d M'), 'total' => (int) ($daily[$date] ?? 0)];
        });

        return view('seller.dashboard', [
            'store' => $store,
            'stats' => [
                'revenue' => (clone $revenueOrders)->sum('total'),
                'orders' => (clone $revenueOrders)->count(),
                'to_process' => (clone $orders)->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])->count(),
                'products' => $store->products()->count(),
                'sold' => $store->products()->sum('sold_count'),
                'low_stock' => $store->products()->where('stock', '<=', 5)->count(),
            ],
            'chart' => $chart,
            'recentOrders' => (clone $orders)->with('user')->latest()->take(6)->get(),
            'topProducts' => $store->products()->orderByDesc('sold_count')->take(5)->get(),
            'lowStock' => $store->products()->where('stock', '<=', 5)->orderBy('stock')->take(5)->get(),
        ]);
    }
}
