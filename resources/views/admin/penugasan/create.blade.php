@extends('layouts.app')
@section('title', 'Assign Petugas')
@section('page_title', 'Kegiatan')
@section('content')
@php
    $opsiPetugas = function () use ($petugas, $assigned) {
        $html = '<option value="">Pilih petugas</option>';
        foreach ($petugas as $p) {
            $label = $p->nama_lengkap.' ('.($p->user->username ?: $p->user->email).')'.(in_array($p->id, $assigned) ? ' · sudah ditugaskan' : '');
            $html .= '<option value="'.$p->id.'">'.e($label).'</option>';
        }
        return $html;
    };
@endphp
<div class="mb-3"><a class="back-link" href="{{ route('admin.kegiatan.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Detail Kegiatan</a></div>
<div class="page-heading">
    <div>
        <div class="page-eyebrow">Penugasan petugas</div>
        <h2>Assign Petugas</h2>
        <p>{{ $kegiatan->nama_kegiatan }} · {{ $petugas->count() }} akun petugas tersedia</p>
    </div>
</div>

<div class="card p-3 p-lg-4">
    <form method="POST" action="{{ route('admin.penugasan.store', $kegiatan) }}">
        @csrf
        <div id="assignments">
            <div class="row g-2 align-items-end assignment-row mb-2">
                <div class="col-md-4"><label class="form-label">Petugas</label><select class="form-select" name="assignments[0][petugas_id]" required>{!! $opsiPetugas() !!}</select></div>
                <div class="col-md-4"><label class="form-label">Kecamatan Kerja</label><select class="form-select" name="assignments[0][kecamatan_id]" required><option value="">Pilih kecamatan</option>@foreach($kecamatan as $k)<option value="{{ $k->id }}">{{ $k->nama_kecamatan }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Role</label><select class="form-select" name="assignments[0][role]" required>@foreach($kegiatan->roles as $role)<option>{{ $role->role }}</option>@endforeach</select></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row w-100" title="Hapus baris" aria-label="Hapus baris"><i class="bi bi-trash"></i></button></div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary mt-2" id="add-row"><i class="bi bi-plus-lg me-1"></i>Tambah Baris</button>
        <hr>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Simpan Penugasan</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.kegiatan.show', $kegiatan) }}">Batal</a>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
let idx = 1;
document.getElementById('add-row').addEventListener('click', () => {
    const row = document.querySelector('.assignment-row').cloneNode(true);
    row.querySelectorAll('select').forEach((s, i) => {
        s.name = `assignments[${idx}][${i === 0 ? 'petugas_id' : i === 1 ? 'kecamatan_id' : 'role'}]`;
        s.selectedIndex = 0;
    });
    document.getElementById('assignments').appendChild(row);
    idx++;
});
document.getElementById('assignments').addEventListener('click', e => {
    const btn = e.target.closest('.remove-row');
    if (btn && document.querySelectorAll('.assignment-row').length > 1) btn.closest('.assignment-row').remove();
});
</script>
@endpush
