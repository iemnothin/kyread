<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /** Ganti bahasa antarmuka, simpan di session + cookie (1 tahun), lalu kembali ke halaman sebelumnya. */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::supports($locale), 404);

        $request->session()->put(Locales::COOKIE, $locale);
        Cookie::queue(Locales::COOKIE, $locale, 60 * 24 * 365);

        return redirect()->back(fallback: route('home'));
    }
}
