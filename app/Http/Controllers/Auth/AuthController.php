<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.index', ['tab' => 'login']);
    }

    public function showRegister(): View
    {
        return view('auth.index', ['tab' => 'register']);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('success', __('Selamat datang kembali!'));
        }

        return back()
            ->withInput($request->only('login', 'remember'))
            ->with('loginError', __('Email/username atau password salah.'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(static::registerRules());

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', __('Registrasi berhasil! Selamat berbelanja di kyread.'));
    }

    /** Aturan pendaftaran; dipakai juga oleh pengecekan langsung di form. */
    public static function registerRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:30', 'unique:users'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /** Cek langsung username/email di form daftar: {ok, message}. */
    public function check(Request $request): JsonResponse
    {
        $field = $request->query('field');
        abort_unless(in_array($field, ['username', 'email'], true), 404);

        $validator = Validator::make([$field => (string) $request->query('value')], [$field => static::registerRules()[$field]]);

        return response()->json([
            'ok' => $validator->passes(),
            'message' => $validator->passes() ? __('Bisa dipakai') : $validator->errors()->first($field),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('Kamu sudah keluar.'));
    }
}
