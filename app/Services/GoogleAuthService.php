<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Daftar/masuk dengan akun Google (web via Socialite, Android via ID token). */
class GoogleAuthService
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id'));
    }

    /**
     * Cari user berdasarkan google_id atau email; hubungkan atau buat akun baru.
     */
    public function findOrCreate(string $googleId, string $email, ?string $name, ?string $avatarUrl = null): User
    {
        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->forceFill(['google_id' => $googleId])->save();
            }

            return $user;
        }

        $user = User::create([
            'name' => $name ?: Str::before($email, '@'),
            'username' => $this->uniqueUsername($email),
            'email' => $email,
        ]);
        $user->forceFill(['google_id' => $googleId, 'email_verified_at' => now()])->save();

        return $user;
    }

    /**
     * Verifikasi ID token dari Credential Manager Android lewat endpoint resmi Google.
     *
     * @return array{sub: string, email: string, name: ?string, picture: ?string}
     *
     * @throws ValidationException
     */
    public function verifyIdToken(string $idToken): array
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        $claims = $response->json() ?? [];

        $allowed = array_filter([config('services.google.client_id'), ...config('services.google.extra_client_ids', [])]);
        $valid = $response->ok()
            && in_array($claims['aud'] ?? null, $allowed, true)
            && in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            && ($claims['email_verified'] ?? null) === 'true'
            && (int) ($claims['exp'] ?? 0) > time();

        if (! $valid || empty($claims['sub']) || empty($claims['email'])) {
            throw ValidationException::withMessages(['id_token' => 'Login Google gagal diverifikasi. Coba lagi.']);
        }

        return [
            'sub' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'] ?? null,
            'picture' => $claims['picture'] ?? null,
        ];
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::of(Str::before($email, '@'))->lower()->replaceMatches('/[^a-z0-9_-]/', '')->limit(24, '')->toString();
        $base = strlen($base) >= 4 ? $base : 'pembaca'.$base;
        $username = $base;

        while (User::where('username', $username)->exists()) {
            $username = $base.random_int(10, 9999);
        }

        return $username;
    }
}
