# Adaptasi SE2026 ke SIPACAK

Perubahan ini mengadaptasi mekanisme input assignment, pembentukan narasi LPD, pembagian waktu ISHOMA, dan pemakaian data progress yang sama untuk LPD serta kwitansi.

## Perubahan utama
- Menambahkan kolom assignment keluarga, usaha (BKU), usaha dalam keluarga, dan total assignment pada `detail_progress`.
- Form progress kini menyediakan input assignment per detail lokasi dan menghitung total secara langsung.
- Validasi backend menghitung ulang total assignment; nilai dari browser tidak dipercaya.
- LPD membentuk uraian kegiatan dan hasil assignment dari data detail, mengurutkan aktivitas berdasarkan waktu, serta menampilkan ISHOMA 12.00–13.00 dan 18.00–19.00 ketika waktu aktivitas melewati atau mengapit periode tersebut.
- Kwitansi tetap menggunakan hasil matching Excel dan sumber progress harian yang sama. Nama bendahara dan PPK pada PDF kini mengikuti konfigurasi administrasi kegiatan, bukan nama yang di-hardcode.
- Penyimpanan progress pada tanggal yang sudah memiliki induk diarahkan ke halaman edit agar detail lama tidak terhapus oleh form tambah yang kosong.
- Menghapus pemanggilan audit update yang sebelumnya terduplikasi.

## Menjalankan perubahan
```bash
php artisan migrate
php artisan optimize:clear
```

## Catatan
- Migration bersifat additive dan tidak menghapus data lama.
- Assignment lama otomatis bernilai 0.
- PDF LPD dibentuk dari data terbaru saat generator dijalankan. Perubahan progress tetap mengikuti mekanisme invalidasi dokumen SIPACAK yang sudah ada.
- Uji kembali PDF pada data kegiatan sebenarnya, terutama kombinasi aktivitas, foto, dan rentang waktu yang melintasi ISHOMA.


## Revisi v18 (15 November 2026)
- Memperpendek jarak kolom label ke tanda titik dua pada identitas petugas.
- Menambah jarak antara judul laporan dan identitas petugas.
- Header “Uraian Laporan Kegiatan” ditampilkan satu baris.
- Kolom Permasalahan dan Pemecahan Masalah dibiarkan kosong bila tidak ada isi; tidak mengisi tanda strip otomatis.
- Judul dokumentasi dibuat tebal dan tetap rata tengah.
