<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Auth\Events\PasswordReset;
use Throwable;

class PasswordResetOtpController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const RESEND_WAIT_SECONDS = 60;
    private const MAX_REQUESTS = 3;
    private const REQUEST_WINDOW_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;

    public function create()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Email terdaftar wajib diisi.',
            'email.email' => 'Masukkan alamat email dengan format yang benar.',
        ]);

        $email = Str::lower(trim($validated['email']));
        $request->session()->forget(['password_otp_email', 'password_otp_verified_at']);

        $result = $this->issueOtp($email);

        if ($result === 'limited') {
            return back()->withErrors(['email' => 'Batas permintaan kode tercapai. Silakan coba kembali setelah 15 menit.'])->withInput();
        }
        if ($result === 'wait') {
            return back()->withErrors(['email' => 'Mohon tunggu 60 detik sebelum meminta kode OTP kembali.'])->withInput();
        }
        if ($result === 'sent') {
            $request->session()->put('password_otp_email', $email);
            return redirect()->route('password.otp.verify')->with('status', 'Jika alamat email terdaftar, kode OTP telah dikirim. Periksa kotak masuk atau folder spam.');
        }

        return back()->withErrors(['email' => 'Permintaan belum dapat diproses. Silakan coba kembali beberapa saat lagi.'])->withInput();
    }

    public function showVerify(Request $request)
    {
        abort_unless($request->session()->has('password_otp_email'), 403);
        $email = $request->session()->get('password_otp_email');
        $record = DB::table('password_reset_otps')->where('email', $email)->first();
        $remaining = $record && $record->last_sent_at
            ? max(0, 60 - (now()->timestamp - \Illuminate\Support\Carbon::parse($record->last_sent_at)->timestamp))
            : 60;
        return view('auth.verify-otp', ['email' => $email, 'resendWait' => $remaining]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        $email = $request->session()->get('password_otp_email');
        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Sesi pemulihan berakhir. Silakan minta kode OTP baru.']);
        }

        $valid = DB::transaction(function () use ($email, $request) {
            $record = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            if (!$record || !$record->user_id || !$record->otp_hash || $record->verified_at ||
                !$record->expires_at || now()->greaterThan(\Illuminate\Support\Carbon::parse($record->expires_at)) ||
                $record->attempts >= self::MAX_ATTEMPTS) {
                return false;
            }

            $attempts = $record->attempts + 1;
            $matches = Hash::check($request->input('otp'), $record->otp_hash);
            DB::table('password_reset_otps')->where('id', $record->id)->update([
                'attempts' => $attempts,
                'verified_at' => $matches ? now() : null,
                'updated_at' => now(),
            ]);

            return $matches && $attempts <= self::MAX_ATTEMPTS;
        });

        if (!$valid) {
            return back()->withErrors(['otp' => 'Kode OTP salah, kedaluwarsa, atau sudah tidak berlaku. Silakan periksa kembali atau minta kode baru.']);
        }

        $request->session()->put('password_otp_verified_at', now()->timestamp);
        return redirect()->route('password.otp.reset');
    }

    public function resend(Request $request)
    {
        $email = $request->session()->get('password_otp_email');
        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Sesi pemulihan berakhir. Silakan minta kode OTP baru.']);
        }

        $result = $this->issueOtp($email);
        if ($result === 'sent') {
            $request->session()->forget('password_otp_verified_at');
            return redirect()->route('password.otp.verify')->with('status', 'Jika alamat email terdaftar, kode OTP baru telah dikirim.');
        }
        if ($result === 'wait') {
            return back()->withErrors(['otp' => 'Mohon tunggu hingga 60 detik sebelum mengirim ulang kode.']);
        }
        if ($result === 'limited') {
            return back()->withErrors(['otp' => 'Batas permintaan kode tercapai. Silakan coba kembali setelah 15 menit.']);
        }
        return back()->withErrors(['otp' => 'Kode belum dapat dikirim. Silakan coba kembali beberapa saat lagi.']);
    }

    public function showReset(Request $request)
    {
        $verifiedAt = (int) $request->session()->get('password_otp_verified_at', 0);
        abort_unless($request->session()->has('password_otp_email') &&
            $verifiedAt > 0 && now()->timestamp - $verifiedAt <= 600, 403);

        return view('auth.reset-password-otp');
    }

    public function reset(Request $request)
    {
        $verifiedAt = (int) $request->session()->get('password_otp_verified_at', 0);
        $email = $request->session()->get('password_otp_email');

        if (!$email || $verifiedAt <= 0 || now()->timestamp - $verifiedAt > 600) {
            $request->session()->forget(['password_otp_email', 'password_otp_verified_at']);
            return redirect()->route('password.request')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan ulangi proses lupa password.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(8)->numbers()],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.numbers' => 'Password wajib mengandung minimal 1 angka.',
        ]);

        $user = DB::transaction(function () use ($email, $validated) {
            $record = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            if (!$record || !$record->user_id || !$record->verified_at ||
                now()->greaterThan(\Illuminate\Support\Carbon::parse($record->expires_at))) {
                return null;
            }

            $account = User::whereKey($record->user_id)->lockForUpdate()->first();
            if (!$account || Str::lower($account->email) !== $email) {
                return null;
            }

            $account->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_otps')->where('id', $record->id)->delete();
            return $account;
        });

        if (!$user) {
            $request->session()->forget(['password_otp_email', 'password_otp_verified_at']);
            return redirect()->route('password.request')->withErrors(['email' => 'Kode OTP sudah tidak berlaku. Silakan minta kode baru.']);
        }

        event(new PasswordReset($user));
        $request->session()->forget(['password_otp_email', 'password_otp_verified_at']);
        return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan login menggunakan password baru.');
    }

    private function issueOtp(string $email): string
    {
        $otp = (string) random_int(100000, 999999);
        $now = now();

        $decision = DB::transaction(function () use ($email, $otp, $now) {
            // Hapus hash OTP kedaluwarsa, tetapi pertahankan penghitung rate limit selama jendelanya aktif.
            DB::table('password_reset_otps')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->update(['otp_hash' => null, 'expires_at' => null, 'verified_at' => null, 'updated_at' => $now]);
            DB::table('password_reset_otps')
                ->whereNotNull('updated_at')
                ->where('updated_at', '<', $now->copy()->subDay())
                ->where(function ($query) use ($now) {
                    $query->whereNull('request_window_started_at')
                        ->orWhere('request_window_started_at', '<', $now->copy()->subMinutes(self::REQUEST_WINDOW_MINUTES));
                })
                ->delete();

            $record = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (!$record) {
                $id = DB::table('password_reset_otps')->insertGetId([
                    'email' => $email,
                    'user_id' => $user?->id,
                    'otp_hash' => $user ? Hash::make($otp) : null,
                    'expires_at' => $user ? $now->copy()->addMinutes(self::OTP_TTL_MINUTES) : null,
                    'attempts' => 0,
                    'last_sent_at' => $user ? $now : null,
                    'request_window_started_at' => $now,
                    'request_count' => 1,
                    'verified_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                return $user ? ['sent', $otp] : ['sent', null];
            }

            $windowStart = $record->request_window_started_at
                ? \Illuminate\Support\Carbon::parse($record->request_window_started_at) : null;
            $windowExpired = !$windowStart || $windowStart->addMinutes(self::REQUEST_WINDOW_MINUTES)->lessThanOrEqualTo($now);
            $count = $windowExpired ? 0 : (int) $record->request_count;

            if ($count >= self::MAX_REQUESTS) {
                return ['limited', null];
            }

            if ($record->last_sent_at &&
                \Illuminate\Support\Carbon::parse($record->last_sent_at)->addSeconds(self::RESEND_WAIT_SECONDS)->greaterThan($now)) {
                return ['wait', null];
            }

            DB::table('password_reset_otps')->where('id', $record->id)->update([
                'user_id' => $user?->id,
                'otp_hash' => $user ? Hash::make($otp) : null,
                'expires_at' => $user ? $now->copy()->addMinutes(self::OTP_TTL_MINUTES) : null,
                'attempts' => 0,
                'last_sent_at' => $user ? $now : null,
                'request_window_started_at' => $windowExpired ? $now : $record->request_window_started_at,
                'request_count' => $count + 1,
                'verified_at' => null,
                'updated_at' => $now,
            ]);

            return ['sent', $user ? $otp : null];
        });

        if ($decision[0] !== 'sent') {
            return $decision[0];
        }

        if ($decision[1] !== null) {
            try {
                Mail::to($email)->send(new PasswordResetOtpMail($decision[1]));
            } catch (Throwable $exception) {
                DB::table('password_reset_otps')->where('email', $email)->update([
                    'otp_hash' => null, 'expires_at' => null, 'verified_at' => null, 'updated_at' => now(),
                ]);
                report($exception);
                return 'error';
            }
        }

        return 'sent';
    }
}
