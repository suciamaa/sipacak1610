# Revisi PDF Kwitansi: FPDI + TCPDF (timpa teks berdasarkan koordinat)

## Cara kerja
1. `templates/kwitansi_template.pdf` = layout kwitansi terbaru (A4 595.28 x 841.89) TANPA teks: hanya logo BPS dan garis tabel hal. 2 dan 3.
2. `App\Services\KwitansiPdfRenderer` meng-import tiap halaman template (FPDI) lalu menimpa semua teks (TCPDF, Times 9 pt) pada koordinat X dan baseline Y.
3. Satu PDF = 3 halaman per petugas/tanggal. Dipakai oleh `DocumentService::kwitansi()` dan unduhan gabungan di `DokumenController`.

## Revisi terakhir
- Template dan koordinat disesuaikan dengan PDF `SIPACAK_Kwitansi_Suci_Amalia_2026_09_17_2.pdf` (label huruf kecil "Uang sebanyak"/"Untuk pembayaran", nominal "Rp 170.000", "Terbilang : ...").
- Nama Bendahara Pengeluaran, PPK, dan petugas selalu bergaris bawah di semua halaman. Garis digambar eksplisit sepanjang teks (tidak bergantung style font).
- Halaman 2, Tempat Tugas: ukuran font tetap 9 pt; teks panjang dibungkus ke baris berikutnya (maks. 4 baris, baris di bawahnya ikut turun). Nama petugas juga dibungkus (maks. 3 baris).
- Kolom Excel "Bulan Pengajuan Kwitansi" + tahun dicetak di sebelah "Indralaya," pada halaman 1, 2, dan 3, mis. `Indralaya,   Oktober 2026`. Tahun = tahun tanggal tugas. Isian boleh nama bulan, angka 1-12, atau tanggal (2026-10-05); bila isian sudah memuat tahun, tahun tidak ditambah lagi.

## Koordinat
Satuan pt, asal kiri-atas. X = sisi kiri teks, Y = baseline. Bila seluruh teks naik/turun sedikit, ubah `BASELINE_FIX`. Jarak garis bawah: `UNDERLINE_GAP`.

## Pengujian
FPDI/TCPDF tidak bisa diunduh di lingkungan kerja, jadi renderer diuji dengan mock FPDI yang dirender ulang di atas template (data normal dan data sangat panjang), ditambah `php -l`.
