# Revisi Form Progress dan Narasi LPD — 19 November 2026

- Semua input jam Perjalanan, Koordinasi, dan Jenis Kegiatan menggunakan nilai awal `00:00`, termasuk kartu wilayah yang ditambahkan secara dinamis.
- Field Output Kegiatan mengikuti role penugasan: PPL mengisi jumlah responden; PML/Supervisor mengisi jumlah responden yang diawasi dan jumlah dokumen yang diperiksa.
- Validasi dan penyimpanan menggunakan kolom jumlah output yang sudah tersedia pada tabel `detail_progress`; tidak memerlukan migration baru.
- Bagian Dokumentasi menggunakan input `sls[0][dokumentasi_files][]`, tetap dinormalisasi ke alur validasi dan penyimpanan foto yang sudah ada. Tiga baris foto ditampilkan saat form baru dibuka.
- Narasi LPD disusun sesuai urutan Perjalanan, Koordinasi Penguasa Wilayah, Jenis Kegiatan, dan output; setiap bagian menyertakan rentang jam yang diinput.
- Nama Desa/Kelurahan dan Kecamatan ditampilkan mengikuti penulisan master wilayah. Jenis Desa/Kelurahan diambil dari `jenis_wilayah` pada master.
- Lebar field tanggal dibuat penuh; area uraian diberi jarak dan tinggi yang lebih lega; contoh permasalahan ditampilkan per baris.

Data master `database/data/wilayah_ogan_ilir.json` pada ZIP sumber sudah memuat 16 kecamatan beserta daftar desa/kelurahan yang diberikan, sehingga tidak diubah.


## Revisi input dokumentasi – 20 November 2026
- Input Pilih Foto pada `petugas/penugasan/progress/create` menggunakan elemen `<input type="file">` langsung tanpa pembungkus label/tombol.
- Indeks upload dokumentasi disesuaikan dari `sls[0]` menjadi `sls[1]` dan controller membaca key yang sama agar foto tetap tervalidasi dan tersimpan.
