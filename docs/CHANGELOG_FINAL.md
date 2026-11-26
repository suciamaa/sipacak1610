# Changelog Final - SIPACAK Laravel

## Perbaikan Admin
- Statistik dashboard ditampilkan konsisten pada halaman Data Kegiatan.
- Tabel Kegiatan Terbaru diperbarui tanpa menghapus informasinya.
- Master uraian LPD dipisah menurut tipe penguasa wilayah dan aktivitas.
- Template dan import Excel kwitansi diselaraskan dengan struktur 12 kolom serta pencocokan progress yang dibatasi ke kegiatan aktif yang sedang dikelola.
- Monitoring progress diringkas per petugas dan detail menampilkan seluruh progress harian petugas.

Implementasi utama berada pada source Laravel (`app`, `resources/views`, `routes`, `database/migrations`). Source legacy tetap dipertahankan dan tidak menjadi target perubahan.
