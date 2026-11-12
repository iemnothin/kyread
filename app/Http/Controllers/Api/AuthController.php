<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['login'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'android');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:30', 'unique:users'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create($data);

        return $this->tokenResponse($user, $data['device_name'] ?? 'android', 201);
    }

    /** Login/daftar dengan ID token Google dari Credential Manager Android. */
    public function google(Request $request, GoogleAuthService $google): JsonResponse
    {
        if (! GoogleAuthService::enabled()) {
            return response()->json(['message' => 'Login dengan Google belum diaktifkan.'], 503);
        }

        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $claims = $google->verifyIdToken($data['id_token']);
        $user = $google->findOrCreate($claims['sub'], $claims['email'], $claims['name'], $claims['picture']);

        return $this->tokenResponse($user, $data['device_name'] ?? 'android');
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(ProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'user' => new UserResource($user),
        ], $status);
    }
}
