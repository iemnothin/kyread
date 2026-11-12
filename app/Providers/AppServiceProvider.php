<?php

namespace App\Providers;

use App\Services\AiService;
use App\Services\GoogleAuthService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // @rupiah($angka) -> Rp 73.000
        Blade::directive('rupiah', fn (string $expression) => "<?php echo 'Rp '.number_format((int) ({$expression}), 0, ',', '.'); ?>");

        // Fitur opsional yang bergantung pada kredensial di .env.
        View::share('aiEnabled', AiService::enabled());
        View::share('googleEnabled', GoogleAuthService::enabled());

        // Jumlah item keranjang untuk badge di header.
        View::composer('partials.header', function ($view): void {
            $view->with('cartCount', auth()->check() ? (int) auth()->user()->cartItems()->sum('quantity') : 0);
        });
    }
}
