# SIPACAK — Status Migrasi Laravel 12

Pembaruan: 4 November 2026

## Ringkasan arsitektur
Aplikasi Laravel 12 ditempatkan pada direktori proyek `SIPACAK_Tahap2`. Source PHP Native, SQL lama, template PDF/DOCX, logo, dan referensi wilayah dipertahankan sebagai referensi/migrasi. Web server harus menggunakan `public/` sebagai document root. Database Laravel target **harus database terpisah** (`sipacak_laravel`), bukan database PHP Native `sipacak`.

## Modul yang telah diimplementasikan pada source
- Autentikasi: registrasi petugas, verifikasi email Laravel, login/logout, reset password, middleware role, command pembuatan admin pertama.
- Admin: dashboard statistik, CRUD kegiatan, konfigurasi tujuan akhir dan role, detail kegiatan, assign petugas terverifikasi per kecamatan/role, pencegahan duplikasi, audit log.
- Petugas: dashboard sesuai penugasan, input progress harian, beberapa detail SLS per tanggal, edit/hapus progress, validasi tanggal/jam/output sesuai role, pilihan uraian/permasalahan, keterangan Aktivitas Lainnya/Masalah Lainnya, upload maksimal lima foto per detail dan preview.
- Privasi file: foto dan PDF disimpan pada disk privat; endpoint download memeriksa kepemilikan penugasan.
- Monitoring admin: daftar progress dengan filter tanggal/nama petugas, koreksi detail, akses dokumentasi, audit perubahan.
- Konfigurasi LPD: master uraian/permasalahan global dan per kegiatan, pilihan default global tidak dapat diubah dari kegiatan, pilihan per kegiatan dapat dikelola/nonaktifkan.
- LPD: generate PDF per penugasan/tanggal dari kumpulan detail SLS, foto dokumentasi dimasukkan, status dibuat hanya setelah file PDF disimpan. Perubahan progress/admin menghapus dokumen lama agar tidak mengunduh dokumen kedaluwarsa.
- Kwitansi: template XLSX menggunakan 13 header dari template lama, import melalui Laravel Excel, validasi header/nilai, pencocokan nama petugas + tanggal + wilayah bila dibutuhkan, penanganan nama ambigu, konfigurasi bendahara dan PPK, daftar status, generate PDF tiga halaman, download petugas dengan otorisasi.
- Template kwitansi lama dan logo digunakan sebagai referensi desain; source PDF Native lama dipertahankan.

## Struktur database target
Migration membuat users, petugas, kecamatan, kegiatan, kegiatan_roles, penugasan, progress_harian, detail_progress, master_uraian_lpd, master_permasalahan_lpd, progress_uraian, progress_permasalahan, dokumentasi, lpd, kwitansi, administrasi_kwitansi, data_excel_kwitansi, password_reset_tokens, sessions, dan activity_logs. Unique constraint mencegah penugasan ganda dan dokumen ganda per penugasan/tanggal.

## Hal yang belum dapat dinyatakan selesai/terverifikasi
1. **Migrasi data produksi Native ke Laravel belum dijalankan.** SQL lama memiliki model akun bersama per kegiatan dan `users.username`, sedangkan target memakai akun email mandiri dan satu profil petugas per user. Dibutuhkan pemetaan email/nomor WhatsApp yang sah untuk setiap `id_petugas`; jangan otomatis menganggap username lama sebagai email.
2. Rendering PDF dan layout belum dibandingkan secara visual dengan template PDF/DOCX lama. LPD saat ini memakai Blade/DomPDF dengan data progress dan dokumentasi; kwitansi sudah tiga halaman dengan struktur konten referensi, tetapi belum ada bukti pixel-perfect terhadap DOCX/PDF sumber.
3. Import XLSX, SMTP/verifikasi email, migration MySQL, dan alur browser belum dapat dijalankan di lingkungan penyusunan karena tidak tersedia `vendor/`, Composer, PDO MySQL, ZipArchive, dan ekstensi terkait.
4. Pengujian keamanan/IDOR, multi-user, beberapa kecamatan dengan nama petugas sama, serta pengujian end-to-end tetap wajib dilakukan pada lingkungan lokal.

## Persiapan lokal (Windows/Laragon)
1. PHP 8.2+ dengan `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `zip`, `ctype`, dan GD bila diperlukan.
2. Composer 2.
3. Dari folder `SIPACAK_Tahap2`, jalankan `composer install`.
4. Salin `.env.example` menjadi `.env`; gunakan database kosong `sipacak_laravel`, bukan database lama `sipacak`.
5. Isi SMTP agar verifikasi email dan reset password benar-benar terkirim.
6. Jalankan `php artisan key:generate`.
7. Buat database MySQL `sipacak_laravel`.
8. Jalankan `php artisan migrate --seed`.
9. Buat admin: `php artisan sipacak:make-admin admin@domain.id "Nama Admin"`.
10. Arahkan Apache/Nginx ke `SIPACAK_Tahap2/public`.
11. Jalankan uji fitur, cocokkan PDF dengan template sumber, dan lakukan migrasi data lama setelah mapping identitas disetujui.

**Larangan:** jangan menjalankan `migrate:fresh`, menjalankan SQL lama yang berisi `DROP TABLE`, atau menghapus `legacy_native/`, `legacy_reference/`, `templates/`, maupun `database/sipacak.sql` pada database produksi.
