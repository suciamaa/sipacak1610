# Changelog - Fitur Kwitansi SIPACAK (15 November 2026)

- Menambahkan Nomor Kegiatan pada kegiatan dan migrasi database.
- Menambahkan NIP Bendahara Pengeluaran dan NIP PPK pada administrasi kwitansi.
- Menambahkan kolom Terbilang pada template Excel.
- Memperketat validasi upload: kesalahan satu baris membatalkan seluruh import, termasuk pencocokan progress dan duplikasi petugas/tanggal.
- Upload valid menggantikan data Excel sebelumnya dan membangun ulang PDF terkait.
- Generate otomatis satu PDF tiga halaman untuk setiap petugas/tanggal yang cocok.
- Menambahkan daftar Kwitansi Harian pada sisi Petugas dengan nominal dan tautan PDF milik sendiri.
- Tidak mengubah controller maupun alur penyimpanan progress dan generator LPD.
