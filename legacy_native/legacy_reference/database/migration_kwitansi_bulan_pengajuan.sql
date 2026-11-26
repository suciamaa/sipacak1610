-- Jalankan setelah backup database SIPACAK.
-- Menambah penyimpanan teks bulan pengajuan tanpa menghapus/mengubah data tanggal_pengajuan lama.
SET @db_name = DATABASE();
SET @table_exists = (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'kwitansi'
);
SET @column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'kwitansi'
    AND COLUMN_NAME = 'bulan_pengajuan_kwitansi'
);
SET @sql = IF(
  @table_exists = 0,
  'SELECT ''Tabel kwitansi tidak ditemukan. Jalankan migrasi kwitansi terlebih dahulu.'' AS pesan',
  IF(
    @column_exists = 0,
    'ALTER TABLE `kwitansi` ADD COLUMN `bulan_pengajuan_kwitansi` VARCHAR(80) NULL AFTER `tanggal_pengajuan`',
    'SELECT ''Kolom bulan_pengajuan_kwitansi sudah tersedia; tidak ada perubahan yang diperlukan.'' AS pesan'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
