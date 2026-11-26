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
    <link href="{{ asset('assets/css/sipacak-design.css') }}?v=20261117-design-v15" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-petugas-mobile.css') }}?v=20261125-petugas-mobile-v3" rel="stylesheet">
    <link href="{{ asset('assets/css/sipacak-profile-menu.css') }}?v=20261118-profile-menu-v2" rel="stylesheet">
    @stack('styles')
</head>
@php($isAuth = request()->routeIs('login', 'register', 'password.request', 'password.reset', 'password.otp.verify', 'password.otp.reset'))
<body class="{{ $isAuth ? 'auth-body' : (auth()->check() && auth()->user()->role === 'petugas' ? 'petugas-app' : '') }}">
@if(auth()->check() && auth()->user()->role === 'admin')
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-sidebar-brand" href="{{ route('admin.dashboard') }}">
            <span class="admin-brand-mark"><i class="bi bi-bar-chart-line-fill"></i></span>
            <span><strong>SIPACAK</strong><small>Administrator</small></span>
        </a>
        <div class="admin-nav-caption">Menu utama</div>
        <nav class="admin-side-nav" aria-label="Navigasi Admin">
            <a class="admin-side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
            <a class="admin-side-link {{ request()->routeIs('admin.kegiatan.*', 'admin.penugasan.*', 'admin.monitoring.*') ? 'active' : '' }}" href="{{ route('admin.kegiatan.index') }}"><i class="bi bi-calendar2-check"></i><span>Kegiatan</span></a>
            <a class="admin-side-link {{ request()->routeIs('admin.administrasi.*', 'admin.lpd.*', 'admin.kwitansi.*') ? 'active' : '' }}" href="{{ route('admin.administrasi.index') }}"><i class="bi bi-folder2-open"></i><span>Administrasi</span></a>
        </nav>
        <div class="admin-sidebar-bottom">
            <div class="admin-sidebar-user"><span class="admin-user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->username ?: (auth()->user()->name ?? 'A'), 0, 1)) }}</span><span class="min-w-0"><strong>{{ auth()->user()->username ?: auth()->user()->name }}</strong><small>Administrator</small></span></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="admin-logout" type="submit"><i class="bi bi-box-arrow-left"></i> Keluar</button></form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" data-admin-sidebar-toggle aria-label="Buka menu"><i class="bi bi-list"></i></button>
            <div><span class="admin-topbar-kicker">SIPACAK admin</span><strong>@yield('page_title', request()->routeIs('admin.kegiatan.*', 'admin.penugasan.*', 'admin.monitoring.*') ? 'Kegiatan' : (request()->routeIs('admin.lpd.*', 'admin.kwitansi.*', 'admin.administrasi.*') ? 'Administrasi' : 'Panel Admin'))</strong></div>
            <span class="admin-topbar-date"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('d F Y') }}</span>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-admin-sidebar-toggle]');
    const sidebar = document.getElementById('adminSidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('is-open'));
        document.addEventListener('click', (event) => {
            if (window.innerWidth < 992 && sidebar.classList.contains('is-open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) sidebar.classList.remove('is-open');
        });
    }
});
</script>
</body>
</html>
