# SIPACAK — Laravel 12 (Adaptasi Progress, LPD, dan Kwitansi SE2026)

Project ini adalah **aplikasi Laravel 12**. Seluruh fitur aplikasi dijalankan melalui Laravel: route, controller, model Eloquent, migration, service, middleware, Blade, Laravel Storage, dan DomPDF.

Tidak ada modul PHP Native, halaman `.php` mandiri, koneksi `mysqli`, atau query database manual yang menjadi bagian aplikasi ini. Project PHP Native lama hanya digunakan sebagai referensi analisis di luar paket aplikasi.

## Stack
- PHP 8.2+
- Laravel 12
- MySQL / MariaDB melalui konfigurasi Laravel
- Blade
- Eloquent ORM
- Laravel Storage
- DomPDF (`barryvdh/laravel-dompdf`)
- Laravel Excel (`maatwebsite/excel`)

## Modul utama
- Autentikasi, verifikasi email, reset password, role Admin dan Petugas.
- Manajemen Kegiatan dan Penugasan.
- Input Progress Harian dengan banyak detail lokasi/SLS dalam satu tanggal.
- Assignment keluarga, usaha (BKU), dan usaha dalam keluarga.
- Uraian aktivitas dan permasalahan per detail.
- Dokumentasi foto melalui Laravel Storage.
- Pembentukan LPD otomatis melalui Laravel Service dan Blade PDF.
- Pembentukan Kwitansi berdasarkan progress yang sama dan administrasi hasil import Excel.
- Monitoring dan pengelolaan dokumen melalui controller Laravel.

## Setup lokal
1. Gunakan PHP 8.2+ beserta ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `zip`, `ctype`.
2. Install Composer 2.
3. Jalankan `composer install` (folder `vendor` juga disertakan sebagai kemudahan, tetapi Composer tetap dapat digunakan untuk sinkronisasi dependensi).
4. Salin `.env.example` menjadi `.env`.
5. Atur `APP_URL`, koneksi MySQL (`DB_*`), dan konfigurasi mail pada `.env`.
6. Buat database kosong sesuai nama pada `DB_DATABASE`.
7. Jalankan:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   php artisan sipacak:make-admin admin@domain.id "Nama Admin"
   php artisan optimize:clear
   ```

8. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

9. Untuk hosting Apache/Nginx, arahkan document root ke folder `public/` Laravel. Jangan arahkan document root ke file PHP Native lama.

## Adaptasi SE2026
- Input assignment keluarga, usaha (BKU), dan usaha dalam keluarga per detail progress.
- Pembentukan uraian LPD berdasarkan aktivitas, lokasi, dan nilai assignment.
- Penyusunan timeline aktivitas dan penyisipan ISHOMA pada rentang yang dikonfigurasi di service LPD.
- Kwitansi tetap memakai sumber Progress Harian yang sama, dengan pencocokan data administrasi Excel.
- Perubahan progress menandai dokumen terkait agar perlu dibuat ulang sesuai implementasi service.

Migration adaptasi: `database/migrations/2026_11_11_000007_add_se2026_assignment_fields_to_detail_progress.php`.

## Struktur penting
- `app/Http/Controllers/` — controller Admin dan Petugas.
- `app/Models/` — model Eloquent.
- `app/Services/` — logika pembentukan dokumen dan uraian LPD.
- `resources/views/` — Blade UI dan template PDF.
- `routes/web.php` — seluruh route aplikasi.
- `database/migrations/` — skema database Laravel.
- `database/data/` — referensi wilayah yang dipakai oleh seeder/controller.
- `public/` — entry point dan asset publik Laravel.
- `storage/` — penyimpanan privat dokumen dan dokumentasi.

## Catatan keamanan dan pengujian
- File `.env` asli tidak disertakan. Buat `.env` sendiri dari `.env.example`.
- Jangan jalankan `migrate:fresh` pada database berisi data.
- Jalankan migration pada database development terlebih dahulu dan verifikasi hasil PDF serta matching Excel sebelum dipakai operasional.
- Pengujian sintaks PHP dilakukan pada file aplikasi, migration, seeder, dan route. Pengujian integrasi database dan render PDF perlu dijalankan pada lingkungan lokal dengan ekstensi PHP lengkap dan database tersedia.
# sipacak1610
