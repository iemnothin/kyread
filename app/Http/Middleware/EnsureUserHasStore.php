<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasStore
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->store) {
            return redirect()->route('seller.store.create')
                ->with('info', __('Buka toko dulu sebelum mulai berjualan di kyreadSeller.'));
        }

        return $next($request);
    }
}
