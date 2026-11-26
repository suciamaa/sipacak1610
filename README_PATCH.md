# Patch UI Form Progress Harian SIPACAK

Patch ini dibuat berdasarkan arsip `sipacak(3).zip` dan hanya mengubah empat file:

1. `resources/views/petugas/progress/form.blade.php`
2. `resources/views/layouts/app.blade.php`
3. `public/assets/css/app.css`
4. `app/Http/Controllers/Petugas/ProgressController.php`

## Perubahan

- Menghapus nomor pada judul Data Perjalanan, Uraian Kegiatan, Permasalahan, dan Pemecahan Masalah.
- Menghapus dropdown Jenis Wilayah dari tampilan. Jenis desa/kelurahan diisi otomatis berdasarkan master wilayah yang dipilih.
- Menampilkan desa/kelurahan yang memang mempunyai data SLS pada kecamatan penugasan.
- Mempertahankan dropdown Jenis Data Wilayah: Blok Sensus atau SLS.
- Menampilkan SLS dan Sub-SLS berdasarkan desa/kelurahan yang dipilih.
- Jam mulai dan jam selesai default `00:00`, termasuk saat menambah detail baru.
- Menghapus panel Assignment Pendataan SE2026 dari form. Nilai assignment lama tetap dipertahankan ketika progress diedit; data baru menggunakan nilai 0.
- Jumlah Responden dan field lain sesuai role tetap dipertahankan.
- Menata pilihan Menemui Penguasa Wilayah dan Aktivitas yang Dilakukan dalam dua kolom.
- Mengubah dokumentasi menjadi daftar input foto satu per baris, dengan preview, tambah, dan hapus foto. Batas 5 foto dan 250 KB per foto tetap dipertahankan.
- Memperbarui gaya form dengan palet hangat/oranye dan hijau lembut, tata letak lebih ringkas, serta responsif.
- Memperbarui versi URL CSS agar perubahan gaya tidak tertahan cache browser.

## Data wilayah

Tidak diperlukan migration baru. Arsip sumber sudah berisi dataset yang cocok dengan daftar yang diberikan:
- 375 baris data SLS/Sub-SLS pada 11 kecamatan.
- 1.206 baris data Blok Sensus pada 16 kecamatan.

Data tersebut berada di `database/data/desa_sls.json`, `database/data/sub_sls.json`, `database/data/blok_sensus_ogan_ilir.json`, dan dimuat melalui `MasterWilayahSlsSeeder`.

Jika tabel master wilayah belum terisi, jalankan:

```bash
php artisan db:seed --class=MasterWilayahSlsSeeder
```

## Cara menerapkan

Ekstrak isi ZIP patch ke root project Laravel SIPACAK dan izinkan file dengan path yang sama untuk ditimpa. Jangan menimpa folder lain.

Kemudian jalankan:

```bash
php artisan optimize:clear
```

Lalu refresh browser dengan `Ctrl + F5`.

## Pemeriksaan

- `ProgressController.php`: PHP lint berhasil.
- `form.blade.php`: Blade `compileString` berhasil.
- JavaScript inline pada form: `node --check` berhasil.
- `php artisan view:cache` belum dapat dijalankan pada runtime pemeriksaan karena ekstensi PHP `DOMDocument` tidak tersedia. Ini bukan hasil pengujian runtime aplikasi.
