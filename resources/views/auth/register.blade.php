@extends('auth.layout')
@section('title', 'Daftar Petugas · SIPACAK')
@section('heading', 'Daftar akun petugas')
@section('subheading', 'Kegiatan dan wilayah kerja ditetapkan admin setelah Anda mendaftar.')
@section('wide', 'true')
@section('auth_body')
@php($punyaAsal = old('kecamatan_asal_id') || old('desa_kelurahan_asal_id') || $errors->has('kecamatan_asal_id') || $errors->has('desa_kelurahan_asal_id'))
<form method="POST" action="{{ route('register.store') }}" class="au-form">@csrf
    <div class="au-grid">
        <div class="au-field au-full"><label for="nama_lengkap">Nama lengkap</label><div class="au-input"><i class="bi bi-person"></i><input id="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror" name="nama_lengkap" value="{{ old('nama_lengkap') }}" placeholder="Nama sesuai identitas" autocomplete="name" required autofocus></div>@error('nama_lengkap')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
        <div class="au-field"><label for="username">Username</label><div class="au-input"><i class="bi bi-at"></i><input id="username" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" minlength="8" maxlength="80" pattern="(?=.*[0-9]).{8,}" title="Minimal 8 karakter dan wajib mengandung angka." placeholder="Username" autocomplete="username" required></div>@error('username')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
        <div class="au-field"><label for="nomor_whatsapp">Nomor WhatsApp</label><div class="au-input"><i class="bi bi-whatsapp"></i><input id="nomor_whatsapp" class="form-control @error('nomor_whatsapp') is-invalid @enderror" name="nomor_whatsapp" value="{{ old('nomor_whatsapp') }}" inputmode="tel" placeholder="08xxxxxxxxxx" autocomplete="tel" required></div>@error('nomor_whatsapp')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
        <div class="au-field au-full"><label for="email">Email</label><div class="au-input"><i class="bi bi-envelope"></i><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" aria-describedby="email-hint" required></div><div class="au-hint" id="email-hint"><span>Mitra: pakai email Sobat. Pegawai BPS: email aktif.</span></div>@error('email')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
        <div class="au-field"><label for="password">Password</label><div class="au-input"><i class="bi bi-lock"></i><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" minlength="8" pattern="(?=.*[0-9]).{8,}" title="Minimal 8 karakter dan wajib mengandung angka." placeholder="Password" autocomplete="new-password" required><button class="au-eye" type="button" data-password-toggle="password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div>@error('password')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
        <div class="au-field"><label for="password_confirmation">Ulangi password</label><div class="au-input"><i class="bi bi-shield-lock"></i><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" minlength="8" placeholder="Ulangi" autocomplete="new-password" required><button class="au-eye" type="button" data-password-toggle="password_confirmation" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button></div></div>
        <div class="au-hint au-full"><i class="bi bi-info-circle"></i><span>Username dan password minimal 8 karakter serta mengandung angka.</span></div>
        <details class="auth-optional" @if($punyaAsal) open @endif>
            <summary><i class="bi bi-geo-alt"></i>Asal wilayah <em>(opsional)</em></summary>
            <div class="au-grid">
                <div class="au-field"><label for="kecamatan_asal_id">Kecamatan</label><div class="au-input"><i class="bi bi-geo-alt"></i><select id="kecamatan_asal_id" class="form-select @error('kecamatan_asal_id') is-invalid @enderror" name="kecamatan_asal_id"><option value="">Pilih kecamatan</option>@foreach($kecamatan as $k)<option value="{{ $k->id }}" @selected(old('kecamatan_asal_id')==$k->id)>{{ $k->nama_kecamatan }}</option>@endforeach</select></div>@error('kecamatan_asal_id')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
                <div class="au-field"><label for="desa_kelurahan_asal_id">Desa/Kelurahan</label><div class="au-input"><i class="bi bi-pin-map"></i><select id="desa_kelurahan_asal_id" class="form-select @error('desa_kelurahan_asal_id') is-invalid @enderror" name="desa_kelurahan_asal_id" disabled><option value="">Pilih kecamatan dulu</option></select></div>@error('desa_kelurahan_asal_id')<div class="au-error" role="alert">{{ $message }}</div>@enderror</div>
            </div>
        </details>
    </div>
    <button class="au-btn" type="submit">Buat akun</button>
</form>
<div class="au-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></div>
@push('scripts')
<script>
(function () {
    var data = @json($desaByKecamatan);
    var selected = @json((string) old('desa_kelurahan_asal_id'));
    var kec = document.getElementById('kecamatan_asal_id');
    var desa = document.getElementById('desa_kelurahan_asal_id');
    if (!kec || !desa) { return; }

    function option(value, text) {
        var el = document.createElement('option');
        el.value = value;
        el.textContent = text;
        return el;
    }

    function fill(keep) {
        var list = data[kec.value] || [];
        desa.innerHTML = '';
        if (!kec.value) {
            desa.appendChild(option('', 'Pilih kecamatan dulu'));
        } else if (!list.length) {
            desa.appendChild(option('', 'Belum ada data'));
        } else {
            desa.appendChild(option('', 'Pilih desa/kelurahan'));
            list.forEach(function (row) { desa.appendChild(option(String(row.id), row.nama)); });
        }
        desa.disabled = !list.length;
        if (keep) { desa.value = keep; }
    }

    kec.addEventListener('change', function () { fill(''); });
    fill(selected);
})();
</script>
@endpush
@endsection
