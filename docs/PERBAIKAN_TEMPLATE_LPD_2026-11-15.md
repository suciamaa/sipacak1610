# Perbaikan template PDF LPD

Perbaikan dilakukan pada template Blade yang menjadi sumber pembentukan PDF Dompdf, bukan mengedit PDF hasil jadi.

- `resources/views/pdf/partials/lpd-document.blade.php` tetap menjadi sumber data dinamis untuk identitas, timeline kegiatan, tanda tangan, dokumentasi, lokasi, dan tanggal.
- `resources/views/pdf/lpd.blade.php` dan `resources/views/pdf/lpd-bundle.blade.php` diselaraskan pada ukuran tabel A4 (185 mm), hierarki Arial, posisi tanda tangan, serta tata letak dokumentasi dua kolom.
- Lebar tabel menggunakan proporsi kolom yang telah dipetakan dari PDF referensi: 8,3 / 24 / 81,4 / 32,4 / 38,9 mm.
- Posisi tanda tangan digeser ke area kanan sebagaimana referensi; jarak tanda tangan untuk laporan ringkas disesuaikan.
- Halaman dokumentasi memakai jarak atas, lebar foto, tinggi bingkai, dan caption yang mengikuti halaman 2–3 referensi. Penomoran foto tetap dimulai dari 1 pada setiap halaman dokumentasi, sesuai PDF referensi.
- `DocumentService::LPD_LAYOUT_VERSION` dinaikkan ke `2026-11-15-v14` agar mekanisme SIPACAK mengenali dan membentuk ulang PDF lama menggunakan template terbaru.

Tidak ada perubahan pada struktur database, sumber data progress, maupun aturan pembentukan isi laporan.


## Revisi v18 (15 November 2026)
- Memperpendek jarak kolom label ke tanda titik dua pada identitas petugas.
- Menambah jarak antara judul laporan dan identitas petugas.
- Header “Uraian Laporan Kegiatan” ditampilkan satu baris.
- Kolom Permasalahan dan Pemecahan Masalah dibiarkan kosong bila tidak ada isi; tidak mengisi tanda strip otomatis.
- Judul dokumentasi dibuat tebal dan tetap rata tengah.
