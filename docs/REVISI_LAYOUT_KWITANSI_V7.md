# Revisi Layout PDF Kwitansi SIPACAK — V7 (16 November 2026)

## File yang diubah
1. `resources/views/pdf/kwitansi.blade.php`
   - Menyatukan aturan layout dokumen tunggal dengan layout PDF gabungan.
   - Area cetak A4 menggunakan margin halaman 10 mm dan padding konten 10 mm tambahan.
   - Konten biasa efektif berada 20 mm dari tepi kertas.
   - Tabel menggunakan lebar fisik 190 mm dengan breakout 10 mm di kiri, sehingga batas tabel berada 10 mm dari tepi kertas.
   - Logo berada di area 10 mm kiri; judul kwitansi tetap terpusat.
   - Blok tanda tangan halaman pertama kembali ke alur normal dokumen, bukan posisi absolut.

2. `resources/views/pdf/kwitansi-bundle.blade.php`
   - Menggunakan CSS yang sama persis dengan generator kwitansi tunggal.
   - Mencegah layout unduhan PDF gabungan berbeda dari PDF satuan.
   - Pemisah halaman eksplisit hanya di antara dokumen kwitansi, perincian, dan daftar pengeluaran riil.

3. `resources/views/pdf/partials/kwitansi-document.blade.php`
   - Menata ulang baris identitas halaman 2 dengan label dan titik dua pada posisi tetap; nilai berada pada area tersendiri sehingga baris lanjutan tetap sejajar.
   - Mengatur ulang proporsi kolom tabel halaman 2 dan 3.
   - Mempertahankan sumber pembayaran dari kolom `Hal`, tahun dari tanggal tugas, terbilang otomatis, dan tanpa baris Uraian Perjalanan terpisah.
   - Menjaga blok tanda tangan dan PPK berada di area konten 2 cm.
   - Menambahkan mode vertikal lebih rapat secara otomatis untuk halaman 2 bila nama atau tujuan sangat panjang, agar blok PPK tidak terdorong menjadi halaman tambahan.

## Aturan ukuran
- Kertas: A4 portrait.
- Margin halaman DomPDF: 10 mm.
- Padding konten: 10 mm per sisi.
- Batas konten biasa: 20 mm dari tepi kertas.
- Lebar tabel: 190 mm; offset kiri -10 mm dari area konten, sehingga batas tabel fisik 10 mm dari tepi kertas.
- Tidak menggunakan tinggi halaman tetap atau `overflow:hidden`.

## Pengujian yang dilakukan
- PDF referensi dan PDF bermasalah dibuka dan diperiksa secara visual; keduanya berukuran A4 dan 3 halaman.
- Fixture HTML yang merepresentasikan tiga halaman dirender menjadi PDF menggunakan WeasyPrint untuk pemeriksaan visual lintas halaman.
- Render fixture normal menghasilkan tepat 3 halaman.
- Render fixture stres (nama, tujuan, dan paragraf lebih panjang) menghasilkan tepat 3 halaman setelah mode compact halaman 2 diterapkan.
- Posisi batas konten dan tabel diperiksa pada hasil render: konten dimulai 20 mm dari tepi; tabel dimulai 10 mm dari tepi dan berakhir 10 mm sebelum tepi kanan.
- File PHP layanan/controller terkait diperiksa dengan `php -l`; semuanya lulus.

## Batasan validasi lingkungan
Render end-to-end menggunakan DomPDF belum dapat dijalankan di lingkungan pemeriksaan ini. PHP CLI yang tersedia tidak memiliki ekstensi `ext-dom` dan `mbstring`; `php artisan view:cache` berhenti pada pemanggilan `mb_split()` yang tidak tersedia. Karena itu hasil render visual di atas adalah render fixture HTML dengan WeasyPrint, bukan bukti hasil DomPDF produksi. Uji akhir DomPDF tetap perlu dilakukan pada instalasi SIPACAK yang memiliki ekstensi PHP tersebut. Jangan menganggap hasil fixture sebagai PDF resmi hasil database.

## Catatan kompatibilitas
Tidak ada perubahan database, import Excel, perhitungan nominal, relasi, hak akses, route, controller, service, atau mekanisme download. Tidak ada dependensi baru.


## Koreksi logo halaman 1 (V7.1) – 16 November 2026

Logo BPS pada halaman pertama digeser masuk ke area kertas agar tidak menempel/keluar dari tepi kiri. Posisi logo diatur relatif terhadap area `.page` dengan `left: 7mm` dan `top: 4mm`, serta lebar `21mm`. Koreksi diterapkan konsisten pada stylesheet PDF satuan dan PDF bundle; struktur dokumen dan data tidak diubah.
