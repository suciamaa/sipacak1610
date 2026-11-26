# Revisi Dokumentasi Foto dan Kolom Permasalahan LPD — 20 November 2026

1. Baris dokumentasi foto yang ditambahkan kembali menggunakan label `Pilih Foto` sebagai pembungkus input file. Input tetap memakai `sls[1][dokumentasi_files][]`, sehingga kontrol pilih foto tampil di antara preview dan tombol Hapus.
2. Narasi permasalahan dan pemecahan masalah pada LPD digabung dari seluruh detail/SLS dalam satu uraian harian. Keduanya hanya ditampilkan pada baris kegiatan pertama, tidak diulang pada setiap baris SLS/kegiatan. Isian duplikat dihilangkan.
3. Tidak ada perubahan skema database atau proses penyimpanan progress.

## Revisi lanjutan dokumentasi dan tabel LPD
- Form dokumentasi foto memakai tombol `Pilih Foto` yang tampil di tengah baris antara preview dan tombol `Hapus`; input file transparan menutupi tombol agar kontrol file native tidak tampil sebagai baris terpisah.
- Tabel LPD menggabungkan isi Permasalahan dan Pemecahan Masalah menjadi masing-masing satu sel memanjang untuk seluruh baris timeline.
- Tanda strip pada baris ISHOMA di kolom Permasalahan dan Pemecahan Masalah dihilangkan.
- Perubahan diterapkan pada template PDF Blade dan renderer PDF TCPDF/FPDI.
