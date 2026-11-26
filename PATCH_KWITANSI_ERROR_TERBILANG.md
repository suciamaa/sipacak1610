# Patch Kwitansi: error import/download dan Terbilang otomatis

## Perubahan
1. Menambahkan migrasi kolom `file_excel_original_name` yang idempotent. Error `Unknown column` terjadi karena kode aplikasi menyimpan nama file asli tetapi skema database belum memiliki kolom tersebut.
2. Menghapus kolom `Terbilang` dari template Excel.
3. Import tidak lagi mewajibkan kolom `terbilang`.
4. Terbilang dihitung otomatis dari `Jumlah Nominal` dalam bahasa Indonesia dan digunakan pada PDF, termasuk dokumen lama yang digabungkan.
5. Tidak mengubah nominal sumber; nilai terbilang hanya representasi teks dari `jumlah_nominal`.

## Wajib dijalankan setelah mengganti file
Dari folder project Laravel:
```bash
php artisan migrate --force
php artisan optimize:clear
```

Pastikan migration `2026_11_16_000011_add_original_excel_name_to_administrasi_kwitansi` tercatat sukses. Setelah itu unduh ulang template Excel terbaru, isi `Jumlah Nominal`, lalu import kembali. File template lama yang masih memiliki kolom Terbilang tetap dapat dibaca; kolom tersebut akan diabaikan.

## Catatan
Stack trace yang diberikan terjadi saat POST `/admin/kegiatan/1/kwitansi/import`, tepat ketika menyimpan `file_excel_original_name`. Jadi proses import gagal sebelum dataset baru disimpan dan PDF kwitansi dibuat. Setelah migrasi dijalankan, import dapat dilanjutkan. Jika dokumen lama belum memiliki file PDF, lakukan import ulang atau generate ulang dari halaman Admin.
