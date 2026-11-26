<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request, $id, $hash)
    {
        // Memastikan tautan memiliki signature yang valid
        abort_unless($request->hasValidSignature(), 403);

        // Mengambil akun berdasarkan ID pada tautan
        $user = User::findOrFail($id);

        // Memastikan hash email sesuai dengan akun
        abort_unless(
            hash_equals(
                sha1($user->getEmailForVerification()),
                $hash
            ),
            403
        );

        // Verifikasi email jika belum terverifikasi
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()
            ->route('login')
            ->with('status', 'Email berhasil diverifikasi. Silakan login.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with(
            'status',
            'Tautan verifikasi telah dikirim ulang.'
        );
    }
}
