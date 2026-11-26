# Pemetaan akun PHP Native ke Laravel

Database Native lama memakai akun bersama per kegiatan (`users.username`) dan satu user lama dapat mempunyai beberapa baris `petugas`. Database Laravel baru memakai satu akun email untuk satu profil petugas. Karena itu migrasi akun tidak aman dilakukan otomatis tanpa pemetaan pemilik akun.

Sebelum migrasi data:
1. Backup database Native dan folder upload.
2. Jalankan Laravel pada database terpisah `sipacak_laravel`.
3. Salin `pemetaan-akun-lama.csv` dan isi satu baris untuk setiap `id_petugas` dengan email aktif yang sudah diverifikasi pemiliknya serta nomor WhatsApp.
4. Periksa duplikasi email dan nama petugas yang sama pada kecamatan berbeda.
5. Petugas harus menerima tautan verifikasi/reset password pada alamat yang dipetakan. Jangan memakai username lama sebagai email tanpa konfirmasi.
6. Cocokkan data kegiatan, penugasan, tanggal progress, detail SLS, dokumentasi, dan metadata kwitansi sebelum memindahkan data produksi.

File CSV contoh hanya ilustrasi format, bukan data pengguna yang telah diverifikasi. Migrasi produksi belum dijalankan.
