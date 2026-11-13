<?php

use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function (OrderService $orders) {
    $count = $orders->expireUnpaid();
    $this->info("{$count} pesanan belum dibayar dibatalkan otomatis.");
})->purpose('Batalkan pesanan yang belum dibayar lebih dari 24 jam');

Schedule::command('orders:expire')->hourly();
