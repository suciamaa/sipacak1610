# Perbaikan Generate LPD – 20 November 2026

## Perubahan
- Penyusunan timeline LPD sekarang memakai jam perjalanan, koordinasi, dan kegiatan dari detail progress masing-masing SLS.
- ISHOMA ditetapkan hanya pada pukul 12:00–13:00 WIB; jendela lama pukul 18:00–19:00 dihapus.
- ISHOMA disisipkan satu kali untuk satu LPD harian, termasuk ketika jeda terjadi di antara dua SLS.
- Kegiatan yang melintasi ISHOMA dipecah: uraian jenis kegiatan sebelum pukul 12:00 dan uraian output SLS terkait setelah pukul 13:00.
- Output responden dihitung dari `jumlah_responden` pada detail SLS yang bersangkutan, bukan agregat semua SLS.
- Perjalanan, koordinasi, dan kegiatan ditata menurut jam mulai. Benturan antar-tahap dirapikan hanya pada representasi LPD; data progress di database tidak diubah.
- Progress lama yang belum memiliki jam per tahap tetap mendapat narasi kompatibilitas tanpa membuat jam perjalanan/koordinasi baru.
- Versi dokumen LPD dinaikkan agar dokumen lama otomatis digenerate ulang saat diakses melalui alur unduh yang sudah ada.

## File Laravel yang diubah
- `app/Services/LpdContentBuilder.php`
- `app/Services/DocumentService.php`

Tidak ada perubahan pada migration, database, form progress, template kolom, tanda tangan, dokumentasi foto, atau modul kwitansi.

## Pengujian logika timeline
- 09:00–11:00: tidak ada ISHOMA.
- 09:00–12:00: tidak ada ISHOMA.
- 09:00–14:00: 09:00–12:00, ISHOMA 12:00–13:00, output 13:00–14:00.
- 13:00–17:00: tidak ada ISHOMA jika tidak ada tahap lain sebelum pukul 13:00.
- Dua SLS: perjalanan, koordinasi, dan kegiatan tiap SLS berurutan; ISHOMA hanya sekali; output tiap SLS memakai jumlah responden detailnya.
- Pemeriksaan otomatis tambahan memastikan waktu pada timeline tidak tumpang tindih.

Catatan lingkungan pengujian: logika builder lulus seluruh skenario di atas. Percobaan render PDF end-to-end pada container kerja terhenti karena ekstensi PHP cURL tidak tersedia (`CURLOPT_CONNECTTIMEOUT` tidak terdefinisi); ini bukan error pada kode LPD atau template. PDF tetap perlu diverifikasi pada lingkungan Laravel lokal yang memiliki ekstensi PHP proyek secara lengkap.
