<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email terdaftar wajib diisi.',
            'email.email' => 'Masukkan alamat email dengan format yang benar.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Tautan untuk mengatur ulang password telah dikirim ke email terdaftar. Periksa kotak masuk atau folder spam Gmail Anda.');
        }

        $message = $status === Password::INVALID_USER
            ? 'Email tersebut belum terdaftar pada SIPACAK.'
            : 'Permintaan reset password belum dapat diproses. Silakan coba kembali beberapa saat lagi.';

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
