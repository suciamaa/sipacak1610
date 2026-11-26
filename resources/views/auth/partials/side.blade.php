{{-- Panel kiri halaman auth. Parameter: $headline, $lead, $steps [[icon,judul,keterangan]], $current (opsional) --}}
<aside class="au-side" aria-label="Tentang SIPACAK">
    <a class="au-brand" href="{{ route('login') }}">
        <img src="{{ asset('assets/images/logo-bps.svg') }}" alt="Logo BPS">
        <span><strong>SIPACAK</strong><small class="au-brand-full">Sistem Laporan Lapangan dan Administrasi Cepat Akurat</small><small class="au-brand-org">BPS Kabupaten Ogan Ilir</small></span>
    </a>
    <div class="au-hero">
        <h1>{{ $headline }}</h1>
        <p>{{ $lead }}</p>
        @isset($steps)
        <ol class="au-steps">
            @foreach($steps as $i => [$icon, $title, $text])
            <li @if(isset($current) && $current === $i) aria-current="step" @endif>
                <span class="dot"><i class="bi {{ $icon }}"></i></span>
                <span><strong>{{ $title }}</strong><small>{{ $text }}</small></span>
            </li>
            @endforeach
        </ol>
        @endisset
    </div>
    <div class="au-foot">Sistem Laporan Lapangan dan Administrasi Cepat Akurat</div>
</aside>
