# Revisi Wilayah Kerja dan Form Progress – 19 November 2026

## Perubahan
- Admin memilih jenis data wilayah pada saat membuat kegiatan: SLS (SLS + Sub-SLS) atau Blok Sensus.
- Pilihan jenis wilayah tersimpan pada data kegiatan dan menjadi acuan seluruh petugas.
- Form progress petugas hanya menampilkan pilihan wilayah sesuai jenis data kegiatan.
- Mode SLS meminta Desa/Kelurahan, SLS, dan Sub-SLS; Sub-SLS wajib dipilih.
- Mode Blok Sensus meminta Desa/Kelurahan dan Blok Sensus.
- Kecamatan tetap mengikuti kecamatan penugasan dan tidak dapat diubah petugas.
- Jenis data wilayah kegiatan tidak dapat diubah setelah kegiatan memiliki progress.
- Layout Uraian kegiatan dipindahkan ke bawah Jam Mulai dan Jam Selesai.
- Ditambahkan petunjuk pada bagian Permasalahan dan Solusi.
- Dokumentasi diubah menjadi daftar baris foto: preview, tombol Pilih Foto, dan Hapus, dengan batas 250 KB per foto serta JPG/JPEG/PNG.

## Database
Jalankan migrasi setelah menempatkan project:

```bash
php artisan migrate
```

Migrasi `2026_11_19_000015_add_wilayah_mode_to_kegiatan.php` menambahkan kolom `kegiatan.jenis_data_wilayah` dengan default `sls`. Kegiatan lama yang sudah memiliki detail Blok Sensus akan dipetakan ke mode `blok_sensus`.

## Catatan verifikasi
- Pemeriksaan sintaks PHP untuk controller, model, dan migration berhasil.
- Pemeriksaan sintaks JavaScript form berhasil.
- `php artisan view:cache` belum dapat dijalankan pada lingkungan pemeriksaan ini karena ekstensi PHP `mbstring` tidak tersedia (`Illuminate\\Support\\mb_split()` undefined). Jalankan kembali di Laragon setelah memastikan ekstensi `mbstring` aktif.
