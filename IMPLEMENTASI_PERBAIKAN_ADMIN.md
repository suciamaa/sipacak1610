# Implementasi Perbaikan Admin SIPACAK (Laravel)

Perubahan diterapkan pada aplikasi Laravel di `app/`, `resources/views/`, `routes/`, dan `database/migrations/`. Direktori `legacy_native/` dan `legacy_reference/` tidak dijadikan target implementasi.

## Perubahan
- Dashboard Admin mempertahankan delapan statistik. Halaman Data Kegiatan memuat partial statistik yang sama dan controller mengirimkan nilai statistik dari database.
- Tombol "Lihat semua" di dashboard dihapus. Tabel Kegiatan Terbaru tetap tersedia; tujuan ditampilkan sebagai LPD, Kwitansi, atau LPD dan Kwitansi; aksi menjadi "Kelola Kegiatan".
- Kelola Uraian LPD dipisah menjadi Menemui Penguasa Wilayah dan Aktivitas yang Dilakukan. Migration menyiapkan default global Kepala Desa, Lurah, Ketua SLS, Penelusuran Wilayah, dan Aktivitas Lainnya. Aktivitas khusus kegiatan dapat ditambah, diedit, diaktif/nonaktifkan.
- Template Excel kwitansi memiliki 12 heading sesuai permintaan, tanpa kolom No. Nama petugas, tanggal tugas, dan wilayah digenerate dari progress dalam kegiatan terkait; kolom administrasi lainnya kosong.
- Import Excel mencocokkan nama petugas, tanggal, dan gabungan desa/kelurahan + kecamatan hanya dalam kegiatan yang sedang dikelola. Pesan kesalahan menyebut baris dan penyebab pencocokan.
- Tombol daftar kwitansi menggunakan teks "Download PDF".
- Monitoring menampilkan ringkasan/checklist petugas. Tombol "Lihat Progress" membuka seluruh progress harian petugas tersebut dalam halaman detail berpaginasi, bukan hanya satu hari terbaru.

## File utama
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/KegiatanController.php`
- `app/Http/Controllers/Admin/LpdMasterController.php`
- `app/Http/Controllers/Admin/KwitansiController.php`
- `app/Http/Controllers/Admin/MonitoringController.php`
- `app/Exports/KwitansiTemplateExport.php`
- `app/Imports/KwitansiImport.php`
- `resources/views/admin/`
- `routes/web.php`
- `database/migrations/2026_11_05_000003_normalize_default_lpd_uraian.php`

## Pemeriksaan
Seluruh file PHP di `app/`, `database/migrations/`, dan `routes/` lulus `php -l`. Pengujian runtime Laravel, koneksi MySQL, rendering PDF, serta round-trip unduh/import Excel perlu dilakukan pada lingkungan aplikasi dengan ekstensi PHP dan database yang sesuai.


## Catatan pembaruan master wilayah petugas (6 November 2026)
Jalankan `php artisan migrate` kemudian `php artisan db:seed` untuk membuat dan mengisi master wilayah. Seeder membaca `database/data/wilayah_ogan_ilir.json`, `database/data/desa_sls.json`, dan `database/data/sub_sls.json`. Pastikan file-file tersebut ikut terunggah saat deployment.
