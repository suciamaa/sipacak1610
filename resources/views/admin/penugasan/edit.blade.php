@extends('layouts.app')
@section('title', 'Edit Penugasan')
@section('page_title', 'Kegiatan')
@section('content')
<div class="mb-3"><a class="back-link" href="{{ route('admin.kegiatan.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Detail Kegiatan</a></div>
<div class="page-heading">
    <div>
        <div class="page-eyebrow">Penugasan petugas</div>
        <h2>Edit Penugasan</h2>
        <p>{{ $kegiatan->nama_kegiatan }}</p>
    </div>
</div>

<div class="card p-3 p-lg-4">
    @if($punyaProgress)
    <div class="alert alert-info py-2 small"><i class="bi bi-lock me-1"></i>Petugas dan kecamatan dikunci karena penugasan ini sudah memiliki progress. Role dan status masih bisa diubah.</div>
    @endif
    <form method="POST" action="{{ route('admin.penugasan.update', $penugasan) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="petugas_id">Petugas</label>
                <select id="petugas_id" class="form-select @error('petugas_id') is-invalid @enderror" name="petugas_id" @disabled($punyaProgress) required>
                    @foreach($petugas as $p)
                        <option value="{{ $p->id }}" @selected((int) old('petugas_id', $penugasan->petugas_id) === $p->id)>{{ $p->nama_lengkap }} ({{ $p->user->email }})</option>
                    @endforeach
                </select>
                @error('petugas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="kecamatan_id">Kecamatan Kerja</label>
                <select id="kecamatan_id" class="form-select @error('kecamatan_id') is-invalid @enderror" name="kecamatan_id" @disabled($punyaProgress) required>
                    @foreach($kecamatan as $k)
                        <option value="{{ $k->id }}" @selected((int) old('kecamatan_id', $penugasan->kecamatan_id) === $k->id)>{{ $k->nama_kecamatan }}</option>
                    @endforeach
                </select>
                @error('kecamatan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">Role</label>
                <select id="role" class="form-select @error('role') is-invalid @enderror" name="role" required>
                    @foreach($kegiatan->roles as $role)
                        <option value="{{ $role->role }}" @selected(old('role', $penugasan->role) === $role->role)>{{ $role->role }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status_penugasan">Status</label>
                <select id="status_penugasan" class="form-select @error('status_penugasan') is-invalid @enderror" name="status_penugasan" required>
                    @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status_penugasan', $penugasan->status_penugasan) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status_penugasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <hr>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Simpan Perubahan</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.kegiatan.show', $kegiatan) }}">Batal</a>
        </div>
    </form>
</div>
@endsection
