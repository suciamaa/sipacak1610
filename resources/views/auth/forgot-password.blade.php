@extends('auth.layout')
@section('title', 'Lupa Password · SIPACAK')
@section('heading', 'Lupa password')
@section('subheading', 'Kami kirim kode OTP ke email akun Anda.')
@section('auth_body')
<form method="POST" action="{{ route('password.email') }}" class="au-form">
    @csrf
    <div class="au-field">
        <label for="email">Email</label>
        <div class="au-input"><i class="bi bi-envelope"></i><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus></div>
        @error('email')<div class="au-error" role="alert">{{ $message }}</div>@enderror
    </div>
    <button class="au-btn" type="submit">Kirim kode OTP</button>
</form>
<div class="au-alt"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Kembali masuk</a></div>
@endsection
