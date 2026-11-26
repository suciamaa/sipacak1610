-- Migrasi aman untuk melengkapi metadata kwitansi dari Excel.
-- Backup database terlebih dahulu. Jalankan satu kali pada database SIPACAK.
-- Tidak menghapus atau mengubah data kwitansi/progress yang sudah ada.
ALTER TABLE kwitansi
  ADD COLUMN tanggal_surat_tugas DATE NULL AFTER nomor_surat_tugas,
  ADD COLUMN lama_perjalanan VARCHAR(100) NULL AFTER tanggal_surat_tugas,
  ADD COLUMN asal_penugasan VARCHAR(255) NULL AFTER lama_perjalanan,
  ADD COLUMN daerah_tujuan TEXT NULL AFTER asal_penugasan,
  ADD COLUMN berangkat BIGINT UNSIGNED NULL AFTER daerah_tujuan,
  ADD COLUMN pulang BIGINT UNSIGNED NULL AFTER berangkat,
  ADD COLUMN terbilang VARCHAR(500) NULL AFTER pulang;
