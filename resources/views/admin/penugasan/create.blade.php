@extends('layouts.app')
@section('title', 'Assign Petugas')
@section('page_title', 'Kegiatan')
@section('content')
<div class="mb-3"><a class="back-link" href="{{ route('admin.kegiatan.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Detail Kegiatan</a></div>
<div class="page-heading">
    <div>
        <div class="page-eyebrow">Pengaturan wilayah dan personel</div>
        <h2>Assign Petugas</h2>
        <p>{{ $kegiatan->nama_kegiatan }} · {{ $petugas->count() }} petugas tersedia</p>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger"><strong>Penugasan belum dapat disimpan.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card p-3 p-lg-4 assignment-builder-card">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <span class="page-eyebrow">Alur penugasan</span>
            <h5 class="mb-1">Pilih wilayah, lalu centang petugas</h5>
            <p class="text-secondary mb-0">Tentukan kecamatan dan role untuk setiap wilayah. Setelah itu, pilih satu atau beberapa petugas sekaligus.</p>
        </div>
        <span class="badge rounded-pill text-bg-light">{{ $petugas->count() }} petugas</span>
    </div>

    <form method="POST" action="{{ route('admin.penugasan.store', $kegiatan) }}" id="assignment-form">
        @csrf
        <div id="assignment-groups" class="d-grid gap-3">
            <section class="assignment-group border rounded-4 p-3 p-lg-4" data-index="0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2"><span class="assignment-step">1</span><div><h6 class="mb-0">Wilayah penugasan</h6><small class="text-secondary">Pilih kecamatan dan role</small></div></div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-assignment-group" aria-label="Hapus wilayah" title="Hapus wilayah" disabled><i class="bi bi-trash"></i></button>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-7"><label class="form-label" for="kecamatan-0">Kecamatan</label><select class="form-select kecamatan-select" id="kecamatan-0" name="assignments[0][kecamatan_id]" required><option value="">Pilih kecamatan penugasan</option>@foreach($kecamatan as $k)<option value="{{ $k->id }}">{{ $k->nama_kecamatan }}</option>@endforeach</select></div>
                    <div class="col-md-5"><label class="form-label" for="role-0">Role penugasan</label><select class="form-select" id="role-0" name="assignments[0][role]" required><option value="">Pilih role</option>@foreach($kegiatan->roles as $role)<option value="{{ $role->role }}">{{ $role->role }}</option>@endforeach</select></div>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><div><h6 class="mb-0">Pilih petugas</h6><small class="text-secondary">Centang semua petugas yang ditempatkan di kecamatan ini.</small></div><div class="d-flex align-items-center gap-2"><span class="selected-count small text-secondary">0 dipilih</span><button type="button" class="btn btn-sm btn-outline-secondary select-all-staff">Pilih semua</button></div></div>
                <div class="staff-picker">
                    @forelse($petugas as $p)
                        <label class="staff-option">
                            <input class="form-check-input staff-checkbox" type="checkbox" name="assignments[0][petugas_ids][]" value="{{ $p->id }}">
                            <span class="staff-option-check"><i class="bi bi-check2"></i></span>
                            <span class="staff-option-info"><strong>{{ $p->nama_lengkap }}</strong><small>{{ $p->user->username ?: $p->user->email }}</small></span>
                        </label>
                    @empty
                        <div class="text-secondary small p-3">Belum ada akun petugas yang tersedia.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <button type="button" class="btn btn-outline-primary mt-3" id="add-assignment-group"><i class="bi bi-plus-lg me-1"></i>Tambah wilayah penugasan</button>
        <div class="assignment-summary mt-4"><i class="bi bi-info-circle me-2"></i><span>Petugas yang sama dapat dipilih pada wilayah berbeda. Penugasan yang sudah ada pada kombinasi kegiatan, kecamatan, dan petugas yang sama akan dilewati.</span></div>
        <hr class="my-4">
        <div class="d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Simpan Penugasan</button><a class="btn btn-outline-secondary" href="{{ route('admin.kegiatan.show', $kegiatan) }}">Batal</a></div>
    </form>
</div>

<template id="assignment-group-template">
    <section class="assignment-group border rounded-4 p-3 p-lg-4" data-index="__INDEX__">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><div class="d-flex align-items-center gap-2"><span class="assignment-step">__STEP__</span><div><h6 class="mb-0">Wilayah penugasan</h6><small class="text-secondary">Pilih kecamatan dan role</small></div></div><button type="button" class="btn btn-sm btn-outline-danger remove-assignment-group" aria-label="Hapus wilayah" title="Hapus wilayah"><i class="bi bi-trash"></i></button></div>
        <div class="row g-3 mb-3"><div class="col-md-7"><label class="form-label" for="kecamatan-__INDEX__">Kecamatan</label><select class="form-select kecamatan-select" id="kecamatan-__INDEX__" name="assignments[__INDEX__][kecamatan_id]" required><option value="">Pilih kecamatan penugasan</option>@foreach($kecamatan as $k)<option value="{{ $k->id }}">{{ $k->nama_kecamatan }}</option>@endforeach</select></div><div class="col-md-5"><label class="form-label" for="role-__INDEX__">Role penugasan</label><select class="form-select" id="role-__INDEX__" name="assignments[__INDEX__][role]" required><option value="">Pilih role</option>@foreach($kegiatan->roles as $role)<option value="{{ $role->role }}">{{ $role->role }}</option>@endforeach</select></div></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><div><h6 class="mb-0">Pilih petugas</h6><small class="text-secondary">Centang semua petugas untuk kecamatan ini.</small></div><div class="d-flex align-items-center gap-2"><span class="selected-count small text-secondary">0 dipilih</span><button type="button" class="btn btn-sm btn-outline-secondary select-all-staff">Pilih semua</button></div></div>
        <div class="staff-picker">@foreach($petugas as $p)<label class="staff-option"><input class="form-check-input staff-checkbox" type="checkbox" name="assignments[__INDEX__][petugas_ids][]" value="{{ $p->id }}"><span class="staff-option-check"><i class="bi bi-check2"></i></span><span class="staff-option-info"><strong>{{ $p->nama_lengkap }}</strong><small>{{ $p->user->username ?: $p->user->email }}</small></span></label>@endforeach</div>
    </section>
</template>
@endsection
@push('styles')
<style>
.assignment-builder-card{border:1px solid #f0e3d6;border-radius:20px;background:#fff;box-shadow:0 8px 28px rgba(80,52,25,.045)}
.assignment-group{border-color:#eee2d6!important;background:#fffdfa}
.assignment-step{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#fff0e2;color:#bd5d16;font-weight:800}
.staff-picker{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
.staff-option{display:flex;align-items:center;gap:11px;padding:11px 13px;border:1px solid #eee5dc;border-radius:12px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s}
.staff-option:has(.staff-checkbox:checked){border-color:#e8791b;background:#fff6ed}
.staff-option .staff-checkbox{position:absolute;opacity:0;pointer-events:none}
.staff-option-check{display:grid;place-items:center;width:20px;height:20px;flex:0 0 20px;border:1.5px solid #c9bdb1;border-radius:6px;color:transparent}
.staff-checkbox:checked+.staff-option-check{background:#e8791b;border-color:#e8791b;color:#fff}
.staff-option-info{display:flex;flex-direction:column;min-width:0;line-height:1.35}.staff-option-info strong{font-size:.9rem;color:#332b24}.staff-option-info small{font-size:.76rem;color:#8b8075;overflow:hidden;text-overflow:ellipsis}
.assignment-summary{display:flex;gap:4px;padding:12px 14px;border-radius:12px;background:#f7f8f6;color:#66736a;font-size:.84rem}
@media(max-width:767px){.staff-picker{grid-template-columns:1fr}.assignment-group{padding:14px!important}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const container = document.getElementById('assignment-groups');
    const template = document.getElementById('assignment-group-template');
    let nextIndex = 1;
    const updateGroup = (group) => {
        const checks = [...group.querySelectorAll('.staff-checkbox')];
        const count = checks.filter((checkbox) => checkbox.checked).length;
        group.querySelector('.selected-count').textContent = `${count} dipilih`;
        group.querySelector('.select-all-staff').textContent = count === checks.length ? 'Batalkan semua' : 'Pilih semua';
    };
    const updateSteps = () => {
        const groups = [...container.querySelectorAll('.assignment-group')];
        groups.forEach((group, index) => {
            group.querySelector('.assignment-step').textContent = index + 1;
            group.querySelector('.remove-assignment-group').disabled = groups.length === 1;
            updateGroup(group);
        });
    };
    document.getElementById('add-assignment-group').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', nextIndex).replaceAll('__STEP__', nextIndex + 1);
        container.insertAdjacentHTML('beforeend', html);
        nextIndex++;
        updateSteps();
        container.lastElementChild.scrollIntoView({behavior:'smooth', block:'nearest'});
    });
    container.addEventListener('change', (event) => {
        if (event.target.matches('.staff-checkbox')) updateGroup(event.target.closest('.assignment-group'));
    });
    container.addEventListener('click', (event) => {
        const selectAll = event.target.closest('.select-all-staff');
        if (selectAll) {
            const group = selectAll.closest('.assignment-group');
            const checks = [...group.querySelectorAll('.staff-checkbox')];
            const shouldCheck = checks.some((checkbox) => !checkbox.checked);
            checks.forEach((checkbox) => checkbox.checked = shouldCheck);
            updateGroup(group);
        }
        const remove = event.target.closest('.remove-assignment-group');
        if (remove && container.querySelectorAll('.assignment-group').length > 1) {
            remove.closest('.assignment-group').remove();
            updateSteps();
        }
    });
    document.getElementById('assignment-form').addEventListener('submit', (event) => {
        const invalid = [...container.querySelectorAll('.assignment-group')].find((group) => !group.querySelector('.staff-checkbox:checked'));
        if (invalid) {
            event.preventDefault();
            alert('Pilih minimal satu petugas untuk setiap wilayah penugasan.');
            invalid.scrollIntoView({behavior:'smooth', block:'center'});
        }
    });
    updateSteps();
})();
</script>
@endpush
