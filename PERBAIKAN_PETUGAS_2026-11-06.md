# Catatan Perbaikan SIPACAK – 6 November 2026

## Perubahan
- Login menggunakan username dan password untuk akun Admin maupun Petugas; email tetap dipakai untuk registrasi, verifikasi, dan reset password.
- Registrasi menambahkan username unik (minimal 8 karakter dan minimal 1 angka). Password minimal 8 karakter dan minimal 1 angka.
- Migration baru mengisi username akun lama dari bagian email sebelum `@` yang dinormalisasi dan ditambah ID pengguna, agar akun lama tetap dapat login.
- Dashboard Petugas merapikan alignment tabel, menampilkan status aktual kegiatan, dan memberi warna badge role sesuai palet SIPACAK.
- Riwayat Progress merapikan tombol kembali, alignment kolom, tombol Edit/Hapus, serta menyediakan menu download Kwitansi dan LPD per tanggal atau seluruh periode.
- Form progress membatasi tanggal pada periode kegiatan dan memvalidasinya kembali di backend.
- Form diubah menjadi satu card lengkap untuk setiap SLS: perjalanan, uraian, permasalahan, pemecahan, dan dokumentasi. Penambahan/penghapusan SLS bekerja per kelompok.
- Relasi uraian dan permasalahan per SLS disimpan pada tabel baru, sementara tabel ringkasan lama tetap disinkronkan agar kompatibel dengan proses pembentukan LPD.
- Template LPD tetap dipertahankan dan menampilkan uraian/permasalahan yang terkait dengan masing-masing SLS.

## Langkah penerapan
1. Backup database dan folder aplikasi terlebih dahulu.
2. Salin perubahan ke project Laravel. Pertahankan file `.env` milik server/lokal; file tersebut tidak disertakan dalam arsip pembaruan.
3. Jalankan:

   ```bash
   php artisan migrate
   php artisan optimize:clear
   ```

4. Lihat username akun lama/admin:

   ```bash
   php artisan tinker
   ```

   ```php
   App\\Models\\User::select('name', 'email', 'username', 'role')->get();
   ```

5. Untuk fitur unduh seluruh periode (arsip ZIP), aktifkan ekstensi PHP `zip` pada PHP yang dipakai web server. Fitur unduh satu tanggal tidak memerlukan ekstensi tersebut.

## Catatan kompatibilitas
- Email verifikasi tidak dihapus. Pengguna baru tetap harus memverifikasi email sebelum dapat login.
- Data uraian/permasalahan lama yang sebelumnya tersimpan di level progress akan ditampilkan sebagai nilai awal pada SLS pertama ketika progress lama diedit. Setelah disimpan, nilai tersebut masuk ke relasi per-SLS.
- Dokumen yang tersedia untuk diunduh adalah dokumen yang sudah dibuat oleh proses Admin sebelumnya; perubahan ini memindahkan akses download ke halaman Progress dan tidak mengganti proses pembuatan dokumen.
