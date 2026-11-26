# Revisi ulang template PDF LPD — 15 November 2026

Revisi ini dimulai ulang dari template PDF Laravel/Dompdf, dengan PDF referensi `LPD_18_September_2026` sebagai acuan visual.

## Perubahan
- Menggunakan `colgroup` eksplisit pada tabel identitas dan tabel LPD agar Dompdf tidak membagi lima kolom secara merata.
- Proporsi tabel LPD: 8.3 mm, 24 mm, 81.4 mm, 32.4 mm, dan 38.9 mm (total 185 mm), mengikuti proporsi pada PDF referensi.
- Ukuran teks isi, identitas, header tabel, tanda tangan, dan caption dokumentasi diseragamkan menggunakan Arial 10 pt; judul tetap 10 pt tebal.
- Jarak blok tanda tangan dibuat konsisten 44 mm setelah tabel agar tidak berubah hanya karena panjang teks.
- Area dokumentasi tetap dua kolom dengan bingkai foto berukuran seragam; foto dirender memenuhi bingkai.
- Data laporan, urutan timeline, identitas petugas, tanggal, lokasi, dan daftar dokumentasi tetap berasal dari data SIPACAK.
- Versi layout dinaikkan menjadi `2026-11-15-v15` agar PDF lama dikenali sebagai versi lama dan dibuat ulang melalui mekanisme yang sudah ada.

## Validasi
- `php -l` berhasil untuk `DocumentService.php` dan `LpdContentBuilder.php`.
- Rendering PDF otomatis belum dapat dijalankan pada lingkungan ini karena ekstensi PHP `mbstring` tidak tersedia (`mb_split()` tidak ditemukan). Lakukan generate ulang dari aplikasi Laragon dengan PHP 8.3 dan periksa hasil PDF di browser/PDF viewer.


## Adaptasi ulang dari `se2026-lanjutan.zip` (v16)

- Acuan struktur kolom dan tipografi diambil dari generator native `pdf_lpd.php` serta aset template `templates/LPD Harian - page 1.png` dan `page 2.png`.
- Pada renderer Laravel/Dompdf, lebar kolom tabel ditetapkan sebagai proporsi persentase pada elemen kolom dan sel, bukan hanya deklarasi mm di `colgroup`. Ini menghindari perilaku Dompdf yang sebelumnya meratakan lebar kolom.
- Ukuran teks isi dan header diselaraskan ke 9 pt seperti renderer native.
- `DocumentService` memakai layout version `2026-11-15-v16-se2026-native` agar PDF lama terdeteksi perlu dibuat ulang.
- Data tetap berasal dari relasi Eloquent SIPACAK; file template native dipakai sebagai acuan layout, bukan mengganti data dinamis.


## Revisi v17 – margin halaman
- Margin PDF dikendalikan melalui padding wrapper halaman, bukan mengandalkan margin @page Dompdf.
- Area isi laporan dan dokumentasi diberi ruang 12 mm atas/bawah dan 12,5 mm kiri/kanan.
- Versi layout dinaikkan agar PDF tersimpan sebelumnya dapat dikenali sebagai versi lama.


## Revisi v18 (15 November 2026)
- Memperpendek jarak kolom label ke tanda titik dua pada identitas petugas.
- Menambah jarak antara judul laporan dan identitas petugas.
- Header “Uraian Laporan Kegiatan” ditampilkan satu baris.
- Kolom Permasalahan dan Pemecahan Masalah dibiarkan kosong bila tidak ada isi; tidak mengisi tanda strip otomatis.
- Judul dokumentasi dibuat tebal dan tetap rata tengah.
