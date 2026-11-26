@extends('auth.layout')
@section('title', 'Atur Password Baru · SIPACAK')
@section('heading', 'Buat password baru')
@section('subheading', 'Minimal 8 karakter dan mengandung angka.')
@section('auth_body')
<form method="POST" action="{{ route('password.otp.update') }}" class="au-form">
    @csrf
    <div class="au-field">
        <label for="password">Password baru</label>
        <div class="au-input"><i class="bi bi-lock"></i><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" minlength="8" pattern="(?=.*[0-9]).{8,}" title="Minimal 8 karakter dan wajib mengandung angka." placeholder="Password baru" autocomplete="new-password" required autofocus><button class="au-eye" type="button" data-password-toggle="password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
        @error('password')<div class="au-error" role="alert">{{ $message }}</div>@enderror
    </div>
    <div class="au-field">
        <label for="password_confirmation">Ulangi password baru</label>
        <div class="au-input"><i class="bi bi-shield-lock"></i><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" minlength="8" placeholder="Ulangi password baru" autocomplete="new-password" required><button class="au-eye" type="button" data-password-toggle="password_confirmation" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
    </div>
    <button class="au-btn" type="submit">Simpan password</button>
</form>
<div class="au-alt"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Kembali masuk</a></div>
@endsection
