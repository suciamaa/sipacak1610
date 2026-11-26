# Penyempurnaan Layout PDF Kwitansi — 16 November 2026

## Perubahan
- Logo BPS pada generator PDF dipindahkan ke area header dan diperbesar secara proporsional agar tidak memasuki area baris identitas.
- Judul dan awal blok isi halaman 1 diselaraskan kembali dengan template A4.
- Seluruh nilai pada halaman 1 sekarang dibungkus berdasarkan lebar aktual kolom nilai yang sama; baris lanjutan tetap sejajar dengan isi, bukan kembali ke kolom label.
- Blok tanda tangan halaman 1 dinaikkan agar jarak antara isi kwitansi dan tanda tangan lebih mendekati PDF acuan.
- Blade PDF Laravel diberi ruang header untuk logo, kolom label/nilai yang konsisten, dan aturan pemenggalan teks agar tidak meluber.
- Spasi, padding tabel, dan perilaku page-break pada view Laravel dirapikan.

## Catatan implementasi
- Generator PDF vektor yang sudah ada dipertahankan. FPDI/TCPDF tidak ditambahkan karena paket tersebut belum tersedia di `vendor` dan lingkungan kerja ini tidak menyediakan proses instalasi Composer dari internet. Tidak ada dependensi baru yang dipaksakan ke instalasi SIPACAK.
- File referensi PDF resmi tetap tidak diubah.

## Validasi
- `php -l kwitansi_pdf_sipacak.php`: lulus.
- Perubahan view Blade diperiksa secara statis.
- Render PDF end-to-end dengan data database aktif belum dapat dilakukan di lingkungan ini; lakukan uji generate pada server SIPACAK dan bandingkan hasil tiga halaman dengan PDF referensi sebelum dipakai untuk dokumen resmi.
