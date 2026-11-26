# Audit Source Code `se2026-lanjutan`

## Ringkasan inventaris
ZIP berisi 519 entri termasuk metadata Git. Di luar `.git`, teridentifikasi 106 file proyek. Struktur utamanya meliputi:
- `admin/`: dashboard, monitoring progress, kwitansi, Excel import.
- `petugas/`: dashboard, progress, unduh kwitansi.
- `config/`, `includes/`: koneksi PDO, autentikasi, fungsi umum, layout.
- `database/`: SQL awal dan sejumlah migrasi.
- `data/`: referensi desa/SLS dan Sub SLS.
- `templates/`: PDF LPD, DOCX kwitansi, logo.
- `uploads/dokumentasi/`: foto lapangan.

## Temuan teknis
1. **Backend:** Native PHP dengan PDO. Koneksi mengaktifkan exception, associative fetch, dan native prepared statements (`ATTR_EMULATE_PREPARES=false`).
2. **Autentikasi lama:** Role `admin` dan `kecamatan`; sesi kecamatan membatasi akses berdasarkan `kecamatan_id`. Ini berbeda dari rancangan SIPACAK baru yang menggunakan role `admin` dan akun bersama `petugas` per kegiatan.
3. **Database lama:** `se2026_lengkap.sql` membuat database `se2026_dokumentasi`, bukan `sipacak`. Skema lama memuat `kecamatan`, `petugas`, `users`, `progress_harian`, `progress_sls`, dan `dokumentasi`. Tabel `kegiatan` dan `kwitansi` ditambahkan melalui migrasi terpisah.
4. **Progress multi-SLS:** `progress_harian` menjadi induk per petugas/tanggal; `progress_sls` menyimpan rincian desa, SLS, Sub SLS, assignment, jam, dan catatan. Ini menjadi dasar rancangan `progress_harian` + `detail_progress`.
5. **Referensi wilayah:** `data/desa_sls.json` memetakan kecamatan → desa → daftar SLS. `data/sub_sls.json` memetakan kecamatan → desa → SLS → daftar Sub SLS. File Excel referensi wilayah juga tersedia.
6. **Dokumentasi:** Foto terkait ke detail SLS melalui `progress_sls_id`; nama file unik dan file fisik tersimpan di `uploads/dokumentasi/`. README lama menyebut batas 250 KB dan format JPG/JPEG/PNG.
7. **LPD:** `pdf_lpd.php` merupakan generator PDF berbasis PHP yang menyusun dokumen dari data progress, dan template `templates/LPD Harian.pdf` tersedia. Generator ini terikat pada konstanta periode serta autentikasi kecamatan sehingga query, otorisasi, dan pemetaan datanya perlu diadaptasi untuk multi-kegiatan.
8. **Kwitansi:** `kwitansi_pdf.php` membangkitkan PDF dinamis dari data kwitansi dan progress. Template DOCX `Kwitansi Petugas RevisitSE2026.docx`, `Kwitansi Pertemuan Petugas SE2026.docx`, dan logo tersedia.
9. **Excel:** `admin/kwitansi_excel.php` membangun/membaca XLSX melalui OOXML dan `ZipArchive`; tidak ditemukan folder `vendor`, `composer.json`, PhpSpreadsheet, TCPDF, FPDF, atau Dompdf dalam ZIP.
10. **Kwitansi existing:** Migrasi memperlihatkan kolom administrasi yang sudah dipakai, antara lain nomor/tanggal surat tugas, lama perjalanan, berangkat, pulang, terbilang, asal penugasan, daerah tujuan, tanggal/bulan pengajuan, nominal, keperluan, dan `hal`.

## Bagian yang dapat diadaptasi
- Fungsi pemformatan angka/terbilang dan logika administrasi kwitansi setelah dipisahkan dari asumsi kegiatan lama.
- Pengukuran posisi teks, pembentukan halaman, dan penggabungan PDF LPD setelah query data serta kontrol akses diganti.
- Template PDF LPD, DOCX kwitansi, logo, serta format Excel sebagai referensi visual dan struktur dokumen.
- Referensi wilayah JSON sebagai sumber pilihan wilayah, dengan validasi backend.

## Bagian yang harus dibangun ulang
- Model akun dan role: dari akun kecamatan ke akun petugas per kegiatan.
- Relasi multi-kegiatan: kegiatan, akun petugas, identitas petugas, progress, dan dokumen.
- Otorisasi backend agar petugas tidak dapat membaca/mengubah identitas petugas lain hanya dengan mengganti ID URL.
- Periode dan status: hilangkan konstanta periode tetap 17–30 September 2026 dari alur umum.
- Query admin dan monitoring agar seluruhnya dibatasi atau diagregasi berdasarkan `id_kegiatan`.
- Import Excel agar pencocokan memakai progress unik (ID progress atau identitas petugas + tanggal + kegiatan yang tervalidasi).
- Status LPD/kwitansi dihitung dari konfigurasi dan kelengkapan data kegiatan.

## Catatan audit
Pemeriksaan dilakukan terhadap struktur ZIP, README, SQL utama/migrasi, konfigurasi koneksi, autentikasi, halaman progress, halaman Excel kwitansi, generator LPD dan kwitansi, serta file referensi wilayah dan template. Tidak ada dependency manager atau folder library vendor yang disertakan. Karena itu, jangan menganggap library PDF/Excel eksternal sudah terpasang; proyek lama memiliki implementasi XLSX berbasis OOXML dan generator PDF PHP sendiri.
