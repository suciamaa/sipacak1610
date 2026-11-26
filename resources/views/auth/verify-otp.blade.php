@extends('auth.layout')
@section('title', 'Verifikasi OTP · SIPACAK')
@section('heading', 'Masukkan kode OTP')
@section('subheading')Kode 6 digit dikirim ke <strong>{{ $email }}</strong>. Berlaku 5 menit.@endsection
@section('auth_body')
<form method="POST" action="{{ route('password.otp.check') }}" class="au-form">
    @csrf
    <div class="au-field">
        <label for="otp">Kode OTP</label>
        <div class="au-input"><i class="bi bi-shield-lock"></i><input id="otp" class="form-control au-otp @error('otp') is-invalid @enderror" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" placeholder="000000" required autofocus></div>
        @error('otp')<div class="au-error" role="alert">{{ $message }}</div>@enderror
    </div>
    <button class="au-btn" type="submit">Verifikasi</button>
</form>
<form method="POST" action="{{ route('password.otp.resend') }}" class="au-form mt-2">
    @csrf
    <button class="au-btn is-ghost" id="resendOtpButton" type="submit" disabled><span id="resendOtpLabel">Kirim ulang kode ({{ $resendWait }} detik)</span></button>
</form>
@error('email')<div class="au-error mt-2" role="alert">{{ $message }}</div>@enderror
<div class="au-alt"><a href="{{ route('password.request') }}"><i class="bi bi-arrow-left me-1"></i>Pakai email lain</a></div>
<script>
(function () {
    const button = document.getElementById('resendOtpButton');
    const label = document.getElementById('resendOtpLabel');
    if (!button || !label) return;
    let remaining = {{ (int) $resendWait }};
    const tick = () => {
        if (remaining > 0) {
            label.textContent = `Kirim ulang kode (${remaining} detik)`;
            remaining -= 1;
            window.setTimeout(tick, 1000);
        } else {
            label.textContent = 'Kirim ulang kode';
            button.disabled = false;
        }
    };
    tick();
})();
</script>
@endsection
