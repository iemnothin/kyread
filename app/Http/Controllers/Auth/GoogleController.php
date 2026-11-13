<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        if (! GoogleAuthService::enabled()) {
            return redirect()->route('login')->with('error', __('Login dengan Google belum diaktifkan.'));
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, GoogleAuthService $google): RedirectResponse
    {
        try {
            $account = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('loginError', __('Login dengan Google dibatalkan atau gagal. Coba lagi.'));
        }

        if (blank($account->getEmail())) {
            return redirect()->route('login')->with('loginError', __('Akun Google kamu tidak memiliki email.'));
        }

        $user = $google->findOrCreate($account->getId(), $account->getEmail(), $account->getName(), $account->getAvatar());

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', __('Halo, :name! Kamu masuk dengan Google.', ['name' => $user->display_name]));
    }
}
