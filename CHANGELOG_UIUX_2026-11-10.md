# Catatan Implementasi SIPACAK — 10 November 2026

## Perubahan utama

- Branding halaman login desktop dan mobile disusun dengan logo BPS sejajar dengan nama SIPACAK, diikuti kepanjangan SIPACAK dan identitas BPS Kabupaten Ogan Ilir.
- Latar halaman autentikasi dan halaman lupa password diberi elemen dekoratif yang tetap menjaga fokus pada form.
- Dashboard petugas dan ringkasan progress diberi petunjuk horizontal scrolling. Pada mobile, kolom identitas penugasan (Nama Kegiatan dan Wilayah Kerja) serta kolom Tanggal dibuat sticky.
- Form progress dibagi menjadi kelompok visual yang berbeda untuk perjalanan, uraian kegiatan, permasalahan, pemecahan masalah, dan dokumentasi.
- Pilihan checkbox ditampilkan vertikal. Pilihan dengan kata "Lainnya" diurutkan paling bawah dan keterangan tambahannya diwajibkan saat dipilih.
- Form mendukung pilihan data wilayah SLS atau Blok Sensus. Referensi Blok Sensus disimpan di `database/data/blok_sensus_ogan_ilir.json` dan dibaca sesuai kecamatan penugasan.
- Validasi backend dan frontend membatasi dokumentasi menjadi maksimal lima foto JPG/PNG, masing-masing maksimal 500 KB. Foto tersimpan tetap dipertahankan saat edit dan dapat dihapus secara eksplisit.
- Setiap progress wajib memiliki minimal satu detail wilayah. Tombol hapus detail tersedia pada seluruh detail, tetapi detail terakhir tidak dapat dihapus.
- Menu unduh Kwitansi dan LPD tetap menyediakan unduh tanggal tertentu serta ZIP seluruh periode, ditambah opsi PDF gabungan.
- Email verifikasi dan reset password menggunakan template HTML responsif dan versi teks untuk email client.

## Data Blok Sensus

File referensi memuat 1.206 baris sumber dari 16 kecamatan, termasuk baris berulang sebagaimana tercantum dalam daftar sumber. Pilihan di form mengelompokkan kode yang sama agar tidak menampilkan opsi duplikat identik.

Nama `TALANG TENGAH` pada daftar sumber Kecamatan Lubuk Keliat tidak ditemukan sebagai nama yang sama di master desa/kelurahan proyek. Nama sumber dan kode Blok Sensus tetap disediakan; relasi master desa/kelurahan dibiarkan kosong agar sistem tidak menebak pemetaan wilayah.

## Langkah penerapan

1. Salin seluruh file hasil perbaikan ke direktori proyek Laravel.
2. Pertahankan file `.env` lokal yang sudah berisi konfigurasi database dan SMTP. File `.env` tidak disertakan dalam arsip hasil perbaikan.
3. Jalankan migrasi baru:

   ```bash
   php artisan migrate
   ```

4. Pastikan ekstensi PHP `mbstring`, `dom`, `gd`, `fileinfo`, dan `zip` aktif pada konfigurasi PHP/XAMPP yang digunakan.
5. Pastikan `APP_URL` di `.env` menggunakan alamat aplikasi yang dapat dijangkau perangkat penerima email. Untuk penggunaan lokal pada komputer yang sama, sesuaikan dengan alamat lokal yang benar; untuk akses dari perangkat lain, gunakan alamat LAN atau domain yang dapat diakses.
6. Pastikan konfigurasi Gmail SMTP di `.env` tetap valid. Jangan memasukkan kredensial SMTP ke file source atau membagikan file `.env`.

## Catatan pengujian

- Seluruh file PHP yang diubah telah diperiksa dengan `php -l`.
- Seluruh 36 template Blade berhasil dikompilasi dan hasil kompilasinya lolos pemeriksaan sintaks PHP.
- Route registry berhasil dimuat dan route Blok Sensus, preview dokumentasi, serta download dokumen terdaftar.
- JavaScript form progress lolos pemeriksaan sintaks Node.js dan stylesheet lolos pemeriksaan struktur CSS.
- Pengujian browser end-to-end, pengiriman Gmail nyata, akses database MySQL, unggah foto melalui browser, dan rendering PDF belum dapat dijalankan di lingkungan pemeriksaan ini karena ekstensi PHP `mbstring` dan `dom` tidak aktif serta tidak tersedia server database/browser aplikasi. Lakukan uji akhir setelah proyek dijalankan pada XAMPP dengan ekstensi yang dibutuhkan aktif.
