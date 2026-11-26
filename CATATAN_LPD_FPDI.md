# LPD → FPDI + TCPDF (overlay koordinat)

## File
| File | Status |
|---|---|
| app/Services/LpdPdfRenderer.php | BARU – renderer LPD (pola sama dengan KwitansiPdfRenderer) |
| templates/lpd_template.pdf | BARU – template 2 hlm, hanya bagian statis |
| app/Services/DocumentService.php | UBAH – lpd() memakai LpdPdfRenderer; LPD_LAYOUT_VERSION → 20261117-v29-fpdi |
| app/Http/Controllers/Petugas/DokumenController.php | UBAH – LPD gabungan memakai LpdPdfRenderer; import Pdf dihapus |

Setelah deploy: `php artisan optimize:clear`. Karena layout_version naik, semua LPD lama otomatis dibuat ulang saat dibuka/diunduh.

## Template (statis) vs ditimpa (dinamis)
- Template hlm 1: judul, label Nama Pelaksana/Wilayah Tugas/Tanggal + ":", header tabel (No…Pemecahan Masalah, (1)–(5)).
- Template hlm 2: bar "DOKUMENTASI PELAKSANAAN".
- Ditimpa: nilai identitas, isi baris, garis tabel (tegak & bawah-baris), tanda tangan, bingkai+foto+keterangan.

## Template tidak fix
Tinggi tabel mengikuti jumlah baris (tinggi baris = 14 + 10 × jumlah baris teks; ISHOMA = 28). Tepat di bawah baris
terakhir (jarak 30 pt) langsung: `KECAMATAN, tanggal` / `Yang Melaporkan` / `Nama Petugas`, rata tengah di x = 474.
- Tabel lebih dari satu halaman → header diulang, nomor lanjut.
- Baris terakhir yang tidak muat bersama blok tanda tangan dipindah utuh ke halaman berikutnya (tanda tangan tidak yatim).
- Baris yang lebih tinggi dari satu halaman dipotong per baris teks.

## Koordinat utama (pt, asal kiri-atas, Y = baseline) – dari LPD_Petugas.pdf
Identitas x=125.33 | y=72.89 / 85.59 / 98.24. Kolom x: 36 · 60 · 128 · 358 · 450 · 560. Baris pertama mulai y=194.39.
Teks: uraian x=133 (lebar 220, rata kiri-kanan), permasalahan x=363 (82), solusi x=455 (100); baseline pertama = atas baris + 16, pitch 10.
No x=43 & jam (tengah x=94) = atas baris + 14.5. Foto: bingkai 247×147 di x=42/306, y=163.89/369.89/575.89, 6 foto/halaman.
Konstanta ada di bagian atas LpdPdfRenderer.php; BASELINE_FIX untuk koreksi naik/turun global.

## Perubahan perilaku kecil
- Nomor foto dokumentasi sekarang lanjut antarhalaman (sebelumnya mulai dari 1 lagi tiap halaman).
- Foto JPEG diputar sesuai EXIF dan dikecilkan bila > 1800 px sisi terpanjang (butuh ekstensi GD; tanpa GD foto dipakai apa adanya).

## Verifikasi
PHP tidak tersedia di lingkungan pembuatan, jadi renderer PHP belum dijalankan. Logika tata letak diuji lewat port Python
dengan konstanta & algoritma yang sama: semua baseline teks, pemenggalan baris, justifikasi, dan garis tabel halaman 1–2
identik dengan LPD_Petugas.pdf. PREVIEW_simulasi_layout.pdf adalah keluaran simulasi itu (bukan keluaran PHP).
