<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPACAK')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}?v=20261111-admin-lpd-spacing-auto-v2" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-design.css') }}?v=20261126-design-v16" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-petugas-mobile.css') }}?v=20261125-petugas-mobile-v3" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-profile-menu.css') }}?v=20261118-profile-menu-v2" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-orange-theme.css') }}?v=20261127-v2" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('assets/css/sipacak-final-polish.css') }}?v=20261127-v2" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-petugas-refresh.css') }}?v=20261127-v2" rel="stylesheet">
</head>
@php($isAuth = request()->routeIs('login', 'register', 'password.request', 'password.reset', 'password.otp.verify', 'password.otp.reset'))
<body class="{{ $isAuth ? 'auth-body' : (auth()->check() && auth()->user()->role === 'petugas' ? 'petugas-app' : '') }}">
@if(auth()->check() && auth()->user()->role === 'admin')
<div class="admin-shell admin-shell-no-sidebar">
    <div class="admin-main admin-main-full">
        <header class="admin-topbar">
            <div class="admin-topbar-brand"><span class="admin-brand-mark"><i class="bi bi-bar-chart-line-fill"></i></span><div><span class="admin-topbar-kicker">SIPACAK admin</span><strong>@yield('page_title', 'Dashboard')</strong></div></div>
            <div class="admin-topbar-actions">
                <span class="admin-topbar-date"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('d F Y') }}</span>
                <span class="admin-topbar-user"><i class="bi bi-person-circle"></i> {{ auth()->user()->username ?: auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="admin-topbar-logout" type="submit"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></button></form>
            </div>
        </header>
        <main class="app-container admin-content">
            @include('layouts.partials.flash-messages')
            @yield('content')
            <footer class="app-footer">© {{ date('Y') }} SIPACAK, BPS Kabupaten Ogan Ilir</footer>
        </main>
    </div>
</div>
@else
@if(!$isAuth)
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container">
        <a class="navbar-brand app-brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
            <span class="app-brand-mark"><i class="bi bi-grid-1x2-fill"></i></span>
            <span>SIPACAK<small>Laporan lapangan dan administrasi BPS Kabupaten Ogan Ilir</small></span>
        </a>
        @auth
        <div class="ms-auto profile-menu dropdown">
            <button type="button" class="profile-trigger" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-haspopup="true">
                <span class="profile-text">
                    <span class="profile-name">{{ auth()->user()->name }}</span>
                    <span class="profile-role">{{ auth()->user()->role }}</span>
                </span>
                <span class="profile-avatar"><i class="bi bi-person-fill"></i></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                <div class="profile-dropdown-head">
                    <small>Login sebagai:</small>
                    <strong>{{ auth()->user()->username ?: auth()->user()->email }}</strong>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
                    <button type="submit" class="profile-logout"><i class="bi bi-box-arrow-right"></i><span>Keluar Sistem</span></button>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>
@endif
@if(!$isAuth)
    @yield('before_main')
@endif
<main class="{{ $isAuth ? 'auth-layout' : 'app-container' }}">
    @if(!$isAuth)
        @include('layouts.partials.flash-messages')
    @endif
    @yield('content')
    <footer class="app-footer">© {{ date('Y') }} SIPACAK, BPS Kabupaten Ogan Ilir</footer>
</main>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}?v=20261111-brand-gradient-v1" defer></script>
@stack('scripts')
</body>
</html>
