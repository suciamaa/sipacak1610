@extends('layouts.app')
@push('styles')
<link href="{{ asset('assets/css/sipacak-auth.css') }}?v={{ @filemtime(public_path('assets/css/sipacak-auth.css')) }}" rel="stylesheet">
@endpush
{{--
    Kerangka halaman auth: satu kartu di tengah.
    Section: title, heading, subheading (opsional), auth_body, wide (opsional: 'true' untuk form lebar)
--}}
@section('content')
<div class="auth-shell">
    <section class="auth-intro" aria-label="SIPACAK">
        <a class="auth-brand" href="{{ route('login') }}">
            <img src="{{ asset('assets/images/logo-bps.svg') }}" alt="Logo BPS">
            <strong>SIPACAK</strong>
            <span class="auth-brand-desc">Sistem Laporan Administrasi Cepat Akurat</span>
            <span class="auth-brand-org">BPS Kabupaten Ogan Ilir</span>
        </a>
    </section>
    <section class="auth-main">
        <div class="auth-card @hasSection('wide') is-wide @endif">
            <header class="auth-head">
                <h1>@yield('heading')</h1>
                @hasSection('subheading')<p>@yield('subheading')</p>@endif
            </header>
            @include('auth.partials.notice')
            @yield('auth_body')
        </div>
        <p class="auth-foot">© {{ date('Y') }} BPS Kabupaten Ogan Ilir</p>
    </section>
</div>
@endsection
