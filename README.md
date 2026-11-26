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

# sipacak1610
