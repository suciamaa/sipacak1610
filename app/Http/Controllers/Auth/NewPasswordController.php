<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class NewPasswordController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->numbers()],
        ], [
            'email.required' => 'Email terdaftar wajib diisi.',
            'email.email' => 'Masukkan alamat email dengan format yang benar.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.numbers' => 'Password wajib mengandung minimal 1 angka.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan login menggunakan password baru.');
        }

        $message = $status === Password::INVALID_TOKEN
            ? 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.'
            : 'Email atau permintaan reset password tidak dapat diproses. Silakan periksa kembali data Anda.';

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
