@extends('auth.layout')
@section('title', 'Masuk · SIPACAK')
@section('heading', 'Masuk')
@section('subheading', 'Gunakan akun petugas atau admin Anda.')
@section('auth_body')
<form method="POST" action="{{ route('login.store') }}" class="au-form">
    @csrf
    <div class="au-field">
        <label for="username">Username</label>
        <div class="au-input"><i class="bi bi-person"></i><input id="username" class="form-control @error('username') is-invalid @enderror" type="text" name="username" value="{{ old('username') }}" placeholder="Username" autocomplete="username" required autofocus></div>
        @error('username')<div class="au-error" role="alert">{{ $message }}</div>@enderror
    </div>
    <div class="au-field">
        <label for="password">Password</label>
        <div class="au-input"><i class="bi bi-lock"></i><input id="password" class="form-control" type="password" name="password" placeholder="Password" autocomplete="current-password" required><button class="au-eye" type="button" data-password-toggle="password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>
    </div>
    <div class="au-row">
        <label class="au-check"><input type="checkbox" name="remember" value="1"><span>Ingat saya</span></label>
        <a href="{{ route('password.request') }}">Lupa password?</a>
    </div>
    <button class="au-btn" type="submit">Masuk</button>
</form>
<div class="au-alt">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></div>
@endsection
