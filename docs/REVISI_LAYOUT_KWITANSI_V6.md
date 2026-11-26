# Revisi V6 — Layout PDF Kwitansi SIPACAK

## File yang diubah
- `resources/views/pdf/kwitansi.blade.php`
- `resources/views/pdf/partials/kwitansi-document.blade.php`

## Implementasi layout
- Area cetak DomPDF menggunakan margin kertas 10 mm.
- Konten biasa diberi padding horizontal 10 mm di dalam area cetak, sehingga batas konten efektif menjadi 20 mm (2 cm) dari tepi kertas.
- Tabel rincian biaya dan daftar pengeluaran menggunakan breakout horizontal 10 mm pada kedua sisi, sehingga batas tabel efektif menjadi 10 mm (1 cm) dari tepi kertas.
- Blok tanda tangan tetap berada di area konten biasa (2 cm); tidak ikut melebar bersama tabel.
- Identitas pada bagian Perincian Biaya menggunakan baris teks biasa dengan lebar label dan kolom titik dua yang konsisten, bukan tabel bersarang.
- Kolom tabel memakai lebar tetap yang proporsional dan teks panjang diizinkan membungkus.
- Halaman 1 menggunakan judul terpusat dan logo pada area header kiri; baris nominal "Uang Sebanyak" tidak diberi bold.
- Pembayaran tetap menggunakan nilai dari kolom `Hal` Excel; tahun anggaran mengikuti `tanggal_tugas`.
- Tidak ada baris terpisah "Uraian Perjalanan".
- Identitas PPK pada halaman 2 ditampilkan dalam tiga baris.

## Pemisahan halaman
- Dokumen disusun menjadi tiga bagian: Kwitansi, Perincian Perhitungan Biaya Perjalanan Dinas, dan Daftar Pengeluaran Riil.
- Pemisah halaman eksplisit hanya digunakan di antara ketiga bagian.
- Tidak menggunakan `min-height`, tinggi halaman paksa, atau `overflow:hidden` yang berpotensi memotong teks.
- Kontainer halaman tidak dipaksa `page-break-inside: avoid`, untuk menghindari halaman kosong ketika konten bertambah.

## Hasil pemeriksaan
- `php -l` lulus untuk kedua file Blade yang diubah serta `DocumentService.php` dan `KwitansiController.php` (file controller dan service tidak diubah).
- PDF referensi `docs/references/Kwitansi_Petugas_SIPACAK.pdf` terdeteksi berukuran A4 dan berjumlah 3 halaman.
- PDF lama yang tersimpan di project menunjukkan kasus 4 halaman pada salah satu data, termasuk halaman kosong setelah halaman 1; pemisah halaman lama menjadi salah satu fokus perbaikan.
- Render DomPDF end-to-end belum dapat dijalankan di lingkungan pemeriksaan ini karena ekstensi PHP `ext-dom` tidak tersedia (`Class "DOMImplementation" not found`). Karena itu jumlah halaman dan hasil visual PDF V6 belum dapat dinyatakan lulus render. Lakukan generate ulang melalui instalasi SIPACAK yang memiliki ekstensi PHP yang diperlukan, lalu periksa hasil 3 halaman dan margin secara visual sebelum digunakan sebagai dokumen resmi.
