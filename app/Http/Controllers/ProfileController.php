<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
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

        return back()->with('success', __('Profil berhasil diperbarui.'));
    }

    public function password(Request $request): RedirectResponse
    {
        $hasPassword = filled($request->user()->password);

        // Akun dari Google belum punya password: boleh langsung membuat password baru.
        $data = $request->validateWithBag('password', [
            'current_password' => $hasPassword ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', $hasPassword ? __('Password berhasil diganti.') : __('Password berhasil dibuat. Sekarang kamu juga bisa masuk dengan email/username.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('deletion', [
            'password' => filled($request->user()->password) ? ['required', 'current_password'] : ['nullable'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('Akunmu sudah dihapus.'));
    }
}
