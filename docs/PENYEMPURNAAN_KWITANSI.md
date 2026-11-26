# Penyempurnaan Fitur Kwitansi SIPACAK

## Perubahan pada paket ini

1. Penentuan tujuan perjalanan dinas sekarang menggunakan master `master_desa_kelurahan` yang terhubung ke kecamatan penugasan.
2. Setiap nama tujuan ditampilkan dengan jenis wilayah yang benar (`Desa` atau `Kelurahan`), digabung dengan koma dan kata `dan` sebelum tujuan terakhir.
3. Proses import menolak nama wilayah yang tidak ditemukan di master atau berasal dari kecamatan yang berbeda; sistem tidak menebak jenis maupun kecamatan.
4. Validasi nominal memastikan `Jumlah Nominal = 2 x Berangkat`, sesuai uraian transport pulang-pergi yang dicetak pada daftar pengeluaran riil.
5. Daftar Admin hanya menampilkan progress yang berada di dalam periode kegiatan.
6. Dokumen kwitansi yang diunduh Petugas diberi nama file berdasarkan nama Petugas dan tanggal.
7. Tampilan dasar PDF disesuaikan lebih dekat dengan referensi: A4 portrait, margin lebih rapat, tipografi serif, dan tata letak tanda tangan.
8. Nama asli file Excel yang diunggah ikut disimpan dan ditampilkan pada halaman Admin.
9. Template Word dan contoh PDF yang disediakan untuk pekerjaan ini disimpan sebagai referensi di `templates/Kwitansi_SIPACAK.docx` dan `docs/references/Kwitansi_Petugas_SIPACAK.pdf`.

## Master wilayah

Seeder yang sudah ada memuat 16 kecamatan dan 241 wilayah (227 desa dan 14 kelurahan) dari `database/data/wilayah_ogan_ilir.json`. Jalankan seeder tersebut setelah migrasi jika database belum memiliki master wilayah:

```bash
php artisan db:seed --class=MasterWilayahSlsSeeder
```

Migrasi tambahan `2026_11_16_000011_add_original_excel_name_to_administrasi_kwitansi.php` menambahkan kolom nama asli file Excel yang diunggah.\n\n## Penerapan

1. Backup database dan folder `storage/app/private`.
2. Pastikan ekstensi PHP yang diperlukan Laravel, Laravel Excel, dan Dompdf aktif (termasuk `mbstring`, `zip`, dan `gd`).
3. Jalankan:

```bash
php artisan migrate --force
php artisan db:seed --class=MasterWilayahSlsSeeder
php artisan optimize:clear
```

4. Edit kegiatan dan isi Nomor Kegiatan/Akun POK.
5. Buka Kelola Kwitansi, unduh template Excel terbaru, lengkapi seluruh kolom, isi identitas Bendahara dan PPK, lalu upload.
6. Periksa ringkasan validasi dan daftar dokumen; unduh sampel PDF untuk pemeriksaan manual sebelum digunakan.

## Batasan verifikasi paket

ZIP yang diterima berisi project SIPACAK Laravel serta folder `legacy_reference` dan `legacy_native/legacy_reference`. Tidak ditemukan arsip terpisah bernama `se2026-lanjutan.zip`; karena itu folder referensi legacy di dalam SIPACAK menjadi satu-satunya referensi mekanisme Excel lama yang tersedia.

Sintaks PHP untuk file yang diubah sudah diperiksa dengan `php -l`. Pengujian Laravel end-to-end belum dapat dijalankan pada lingkungan pemeriksaan ini karena ekstensi PHP `mbstring` tidak tersedia (`Illuminate\Support\mb_split()` tidak ditemukan). PDF Dompdf juga belum dapat dibandingkan hasil render barunya secara langsung di lingkungan ini. Penyesuaian CSS mendekatkan tampilan ke PDF contoh, tetapi identitas visual piksel-per-piksel belum boleh dianggap terverifikasi. Untuk persyaratan identik secara ketat, hasil render harus diuji pada server deployment dengan font Times New Roman/Arial yang sesuai dan dibandingkan per halaman dengan PDF referensi.
