# Pengembangan Fitur Kwitansi SIPACAK

## Perubahan pada patch ini
- Nomor Kegiatan ditambahkan pada form tambah/edit kegiatan dan disimpan pada tabel `kegiatan`.
- Identitas Bendahara Pengeluaran dan PPK kini mencakup Nama serta NIP.
- Template Excel memuat data progress harian per petugas dan tanggal, termasuk kolom Terbilang.
- Upload Excel memvalidasi header, tanggal, nominal, identitas perjalanan, kecocokan nama petugas + tanggal + desa/kecamatan, dan duplikasi kombinasi petugas/tanggal.
- Jika ada kesalahan validasi, seluruh upload dibatalkan dan rincian baris ditampilkan; data lama tidak ditimpa.
- Upload valid menggantikan set data Excel kegiatan sebelumnya, menghapus PDF lama yang terkait, lalu membuat ulang PDF kwitansi untuk setiap pasangan penugasan/tanggal yang cocok.
- PDF mengikuti tiga halaman template: Kwitansi, Perincian Perhitungan Biaya Perjalanan Dinas, dan Daftar Pengeluaran Riil.
- Petugas mendapat tautan daftar kwitansi harian pada halaman progress. Data dan unduhan dibatasi pada penugasan milik akun petugas.

## Migrasi database
Setelah backup database, jalankan:

```bash
php artisan migrate
```

Migrasi `2026_11_15_000009_add_kwitansi_officials_and_activity_number.php` menambahkan:
- `kegiatan.nomor_kegiatan`
- `administrasi_kwitansi.nip_bendahara_pengeluaran`
- `administrasi_kwitansi.nip_pejabat_pembuat_komitmen`

Kegiatan yang sudah ada dan menggunakan Kwitansi perlu dilengkapi Nomor Kegiatan melalui form Edit Kegiatan. Identitas nama dan NIP pejabat perlu dilengkapi kembali pada halaman Kelola Kwitansi sebelum upload final.

## Catatan kompatibilitas
- Struktur utama tetap Laravel, memakai Laravel Excel dan Dompdf yang sudah tercantum pada `composer.json`.
- Tidak ada perubahan pada mekanisme input progress atau generator LPD.
- Project referensi `se2026-lanjutan` digunakan untuk pola template/import, pencocokan petugas dan tanggal, pengelompokan harian, serta pembaruan data tanpa menggandakan kwitansi.
