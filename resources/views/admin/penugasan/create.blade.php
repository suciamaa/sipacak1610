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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div><h6 class="mb-0">Pilih petugas</h6><small class="text-secondary">Buka daftar untuk mencari dan mencentang petugas.</small></div>
                    <span class="selected-count small text-secondary">0 dipilih</span>
                </div>
                <div class="staff-dropdown">
                    <button type="button" class="staff-dropdown-toggle" aria-expanded="false">
                        <span class="staff-dropdown-label"><i class="bi bi-people me-2"></i>Pilih petugas</span>
                        <span class="d-flex align-items-center gap-2"><span class="staff-dropdown-count">0 dipilih</span><i class="bi bi-chevron-down staff-dropdown-chevron"></i></span>
                    </button>
                    <div class="staff-dropdown-menu" hidden>
                        <div class="staff-dropdown-tools">
                            <div class="staff-search-wrap"><i class="bi bi-search"></i><input type="search" class="staff-search" placeholder="Cari nama atau email..." autocomplete="off" aria-label="Cari petugas berdasarkan nama atau email"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary select-all-staff">Pilih semua</button>
                        </div>
                        <div class="staff-picker">
                            @forelse($petugas as $p)
                                <label class="staff-option">
                                    <input class="form-check-input staff-checkbox" type="checkbox" name="assignments[0][petugas_ids][]" value="{{ $p->id }}">
                                    <span class="staff-option-check"><i class="bi bi-check2"></i></span>
                                    <span class="staff-option-info"><strong>{{ $p->nama_lengkap }}</strong><small>{{ $p->user->email }}</small></span>
                                </label>
                            @empty
                                <div class="text-secondary small p-3">Belum ada akun petugas yang tersedia.</div>
                            @endforelse
                        </div>
                        <div class="staff-search-empty" hidden>Tidak ada petugas yang cocok dengan pencarian.</div>
                    </div>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><div><h6 class="mb-0">Pilih petugas</h6><small class="text-secondary">Buka daftar untuk mencari dan mencentang petugas.</small></div><span class="selected-count small text-secondary">0 dipilih</span></div>
        <div class="staff-dropdown"><button type="button" class="staff-dropdown-toggle" aria-expanded="false"><span class="staff-dropdown-label"><i class="bi bi-people me-2"></i>Pilih petugas</span><span class="d-flex align-items-center gap-2"><span class="staff-dropdown-count">0 dipilih</span><i class="bi bi-chevron-down staff-dropdown-chevron"></i></span></button><div class="staff-dropdown-menu" hidden><div class="staff-dropdown-tools"><div class="staff-search-wrap"><i class="bi bi-search"></i><input type="search" class="staff-search" placeholder="Cari nama atau email..." autocomplete="off" aria-label="Cari petugas berdasarkan nama atau email"></div><button type="button" class="btn btn-sm btn-outline-secondary select-all-staff">Pilih semua</button></div><div class="staff-picker">@forelse($petugas as $p)<label class="staff-option"><input class="form-check-input staff-checkbox" type="checkbox" name="assignments[__INDEX__][petugas_ids][]" value="{{ $p->id }}"><span class="staff-option-check"><i class="bi bi-check2"></i></span><span class="staff-option-info"><strong>{{ $p->nama_lengkap }}</strong><small>{{ $p->user->email }}</small></span></label>@empty<div class="text-secondary small p-3">Belum ada akun petugas yang tersedia.</div>@endforelse</div><div class="staff-search-empty" hidden>Tidak ada petugas yang cocok dengan pencarian.</div></div></div>
    </section>
</template>
@endsection
@push('styles')
<style>
.assignment-builder-card{border:1px solid #f0e3d6;border-radius:20px;background:#fff;box-shadow:0 8px 28px rgba(80,52,25,.045)}
.assignment-group{border-color:#eee2d6!important;background:#fffdfa}
.assignment-step{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#fff0e2;color:#bd5d16;font-weight:800}
.staff-dropdown{position:relative}
.staff-dropdown-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 15px;border:1px solid #e8ded3;border-radius:12px;background:#fff;color:#382f27;text-align:left;transition:border-color .15s,box-shadow .15s}
.staff-dropdown-toggle:hover,.staff-dropdown.is-open .staff-dropdown-toggle{border-color:#e8791b;box-shadow:0 0 0 3px rgba(232,121,27,.09)}
.staff-dropdown-label{font-weight:600}.staff-dropdown-count{font-size:.78rem;color:#8b7d70;background:#f7f2ec;border-radius:20px;padding:4px 9px}.staff-dropdown-chevron{transition:transform .18s;color:#8b7d70}.staff-dropdown.is-open .staff-dropdown-chevron{transform:rotate(180deg)}
.staff-dropdown-menu{position:absolute;z-index:30;top:calc(100% + 7px);left:0;right:0;padding:10px;border:1px solid #eadfd3;border-radius:14px;background:#fff;box-shadow:0 14px 38px rgba(55,39,23,.14)}
.staff-dropdown-menu[hidden]{display:none!important}.staff-dropdown-tools{display:flex;align-items:center;gap:9px;margin-bottom:9px}.staff-search-wrap{position:relative;flex:1;min-width:0}.staff-search-wrap>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9b8d80}.staff-search{width:100%;height:38px;padding:7px 12px 7px 35px;border:1px solid #e9e0d7;border-radius:9px;outline:0;color:#382f27;background:#fff}.staff-search:focus{border-color:#e8791b;box-shadow:0 0 0 3px rgba(232,121,27,.1)}
.staff-picker{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;max-height:260px;overflow:auto;padding:2px}.staff-option{display:flex;align-items:center;gap:10px;min-width:0;padding:10px 11px;border:1px solid #eee5dc;border-radius:10px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s}.staff-option:hover{border-color:#e8b98e;background:#fffaf5}.staff-option:has(.staff-checkbox:checked){border-color:#e8791b;background:#fff5e9}.staff-option[hidden]{display:none!important}.staff-option .staff-checkbox{position:absolute;opacity:0;pointer-events:none}.staff-option-check{display:grid;place-items:center;width:19px;height:19px;flex:0 0 19px;border:1.5px solid #c9bdb1;border-radius:5px;color:transparent}.staff-checkbox:checked+.staff-option-check{background:#e8791b;border-color:#e8791b;color:#fff}.staff-option-info{display:flex;flex-direction:column;min-width:0;line-height:1.35}.staff-option-info strong{font-size:.87rem;color:#332b24;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.staff-option-info small{font-size:.74rem;color:#8b8075;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.staff-search-empty{padding:18px 10px;text-align:center;color:#8b8075;font-size:.84rem}.assignment-summary{display:flex;gap:4px;padding:12px 14px;border-radius:12px;background:#f7f8f6;color:#66736a;font-size:.84rem}
@media(max-width:767px){.staff-picker{grid-template-columns:1fr;max-height:230px}.staff-dropdown-menu{position:relative;top:auto;margin-top:7px;box-shadow:0 8px 20px rgba(55,39,23,.08)}.staff-dropdown-tools{align-items:stretch}.staff-dropdown-tools .btn{white-space:nowrap}.assignment-group{padding:14px!important}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const container = document.getElementById('assignment-groups');
    const template = document.getElementById('assignment-group-template');
    const form = document.getElementById('assignment-form');
    if (!container || !template || !form) return;
    let nextIndex = 1;

    const updateGroup = (group) => {
        const checks = [...group.querySelectorAll('.staff-checkbox')];
        const count = checks.filter((checkbox) => checkbox.checked).length;
        const countText = `${count} dipilih`;
        group.querySelector('.selected-count').textContent = countText;
        group.querySelector('.staff-dropdown-count').textContent = countText;
        group.querySelector('.staff-dropdown-label').innerHTML = count
            ? `<i class="bi bi-people me-2"></i>${count} petugas dipilih`
            : '<i class="bi bi-people me-2"></i>Pilih petugas';
        const visibleChecks = checks.filter((checkbox) => !checkbox.closest('.staff-option').hidden);
        const selectAll = group.querySelector('.select-all-staff');
        selectAll.textContent = visibleChecks.length && visibleChecks.every((checkbox) => checkbox.checked)
            ? 'Batalkan pilihan' : 'Pilih semua';
    };

    const filterStaff = (group) => {
        const query = group.querySelector('.staff-search').value.trim().toLocaleLowerCase();
        const options = [...group.querySelectorAll('.staff-option')];
        let visible = 0;
        options.forEach((option) => {
            const text = option.textContent.toLocaleLowerCase();
            const match = text.includes(query);
            option.hidden = !match;
            if (match) visible++;
        });
        group.querySelector('.staff-search-empty').hidden = visible !== 0;
        updateGroup(group);
    };

    const closeDropdowns = (except = null) => {
        container.querySelectorAll('.staff-dropdown.is-open').forEach((dropdown) => {
            if (dropdown === except) return;
            dropdown.classList.remove('is-open');
            dropdown.querySelector('.staff-dropdown-menu').hidden = true;
            dropdown.querySelector('.staff-dropdown-toggle').setAttribute('aria-expanded', 'false');
        });
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

    container.addEventListener('input', (event) => {
        if (event.target.matches('.staff-search')) filterStaff(event.target.closest('.assignment-group'));
    });
    container.addEventListener('change', (event) => {
        if (event.target.matches('.staff-checkbox')) updateGroup(event.target.closest('.assignment-group'));
    });
    container.addEventListener('click', (event) => {
        const toggle = event.target.closest('.staff-dropdown-toggle');
        if (toggle) {
            const dropdown = toggle.closest('.staff-dropdown');
            const opening = !dropdown.classList.contains('is-open');
            closeDropdowns(dropdown);
            dropdown.classList.toggle('is-open', opening);
            dropdown.querySelector('.staff-dropdown-menu').hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));
            if (opening) dropdown.querySelector('.staff-search').focus();
            return;
        }
        const selectAll = event.target.closest('.select-all-staff');
        if (selectAll) {
            const group = selectAll.closest('.assignment-group');
            const visibleChecks = [...group.querySelectorAll('.staff-option:not([hidden]) .staff-checkbox')];
            const shouldCheck = visibleChecks.some((checkbox) => !checkbox.checked);
            visibleChecks.forEach((checkbox) => checkbox.checked = shouldCheck);
            updateGroup(group);
            return;
        }
        const remove = event.target.closest('.remove-assignment-group');
        if (remove && container.querySelectorAll('.assignment-group').length > 1) {
            remove.closest('.assignment-group').remove();
            updateSteps();
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#assignment-groups .staff-dropdown')) closeDropdowns();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeDropdowns();
    });
    form.addEventListener('submit', (event) => {
        const invalid = [...container.querySelectorAll('.assignment-group')].find((group) => !group.querySelector('.staff-checkbox:checked'));
        if (invalid) {
            event.preventDefault();
            alert('Pilih minimal satu petugas untuk setiap wilayah penugasan.');
            const dropdown = invalid.querySelector('.staff-dropdown');
            dropdown.classList.add('is-open');
            dropdown.querySelector('.staff-dropdown-menu').hidden = false;
            dropdown.querySelector('.staff-dropdown-toggle').setAttribute('aria-expanded', 'true');
            invalid.scrollIntoView({behavior:'smooth', block:'center'});
        }
    });
    updateSteps();
})();
</script>
@endpush
