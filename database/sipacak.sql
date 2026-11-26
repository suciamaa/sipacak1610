-- ============================================================
-- SIPACAK - DATABASE TAHAP 1
-- Database: sipacak
-- Engine: InnoDB | Charset: utf8mb4
-- Fresh setup: backup database sipacak terlebih dahulu jika sudah ada.
-- Catatan: script ini menghapus tabel SIPACAK dengan nama yang sama,
-- tetapi tidak menghapus database lain.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `sipacak`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sipacak`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `dokumentasi`;
DROP TABLE IF EXISTS `kwitansi`;
DROP TABLE IF EXISTS `detail_progress`;
DROP TABLE IF EXISTS `progress_harian`;
DROP TABLE IF EXISTS `petugas`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `kegiatan`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. KEGIATAN
CREATE TABLE `kegiatan` (
  `id_kegiatan` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by` INT UNSIGNED NULL,
  `nama_kegiatan` VARCHAR(150) NOT NULL,
  `nomor_kegiatan` VARCHAR(150) NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `deskripsi` TEXT NULL,
  `tujuan_akhir` ENUM('lpd','kwitansi','keduanya') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kegiatan`),
  KEY `idx_kegiatan_created_by` (`created_by`),
  KEY `idx_kegiatan_status` (`status`),
  KEY `idx_kegiatan_tanggal_mulai` (`tanggal_mulai`),
  KEY `idx_kegiatan_tanggal_selesai` (`tanggal_selesai`),
  CONSTRAINT `chk_kegiatan_periode` CHECK (`tanggal_selesai` >= `tanggal_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. USERS
-- id_kegiatan NULL untuk admin; satu kegiatan hanya boleh memiliki satu akun petugas.
CREATE TABLE `users` (
  `id_user` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_kegiatan` INT UNSIGNED NULL,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role_user` ENUM('admin','petugas') NOT NULL DEFAULT 'petugas',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_id_kegiatan` (`id_kegiatan`),
  UNIQUE KEY `uq_users_id_user_kegiatan` (`id_user`,`id_kegiatan`),
  CONSTRAINT `fk_users_kegiatan` FOREIGN KEY (`id_kegiatan`)
    REFERENCES `kegiatan` (`id_kegiatan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relasi balik ditambahkan setelah users tersedia (relasi kegiatan <-> akun).
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `fk_kegiatan_created_by` FOREIGN KEY (`created_by`)
  REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 3. PETUGAS
CREATE TABLE `petugas` (
  `id_petugas` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_kegiatan` INT UNSIGNED NOT NULL,
  `id_user` INT UNSIGNED NOT NULL,
  `nama_petugas` VARCHAR(150) NOT NULL,
  `kecamatan` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_petugas`),
  UNIQUE KEY `uq_petugas_id_kegiatan` (`id_petugas`,`id_kegiatan`),
  UNIQUE KEY `uq_petugas_nama_kegiatan` (`id_kegiatan`,`nama_petugas`),
  KEY `idx_petugas_kegiatan` (`id_kegiatan`),
  KEY `idx_petugas_user` (`id_user`),
  KEY `idx_petugas_nama` (`nama_petugas`),
  KEY `idx_petugas_kecamatan` (`kecamatan`),
  CONSTRAINT `fk_petugas_kegiatan` FOREIGN KEY (`id_kegiatan`)
    REFERENCES `kegiatan` (`id_kegiatan`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_petugas_user_kegiatan` FOREIGN KEY (`id_user`,`id_kegiatan`)
    REFERENCES `users` (`id_user`,`id_kegiatan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PROGRESS_HARIAN
-- id_kegiatan ditambahkan sebagai FK pengaman multi-kegiatan.
-- Satu induk progress per petugas per tanggal; banyak lokasi disimpan di detail_progress.
CREATE TABLE `progress_harian` (
  `id_progress` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_kegiatan` INT UNSIGNED NOT NULL,
  `id_petugas` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_progress`),
  UNIQUE KEY `uq_progress_petugas_tanggal` (`id_petugas`,`tanggal`),
  UNIQUE KEY `uq_progress_id_kegiatan` (`id_progress`,`id_kegiatan`),
  KEY `idx_progress_tanggal` (`tanggal`),
  KEY `idx_progress_kegiatan_tanggal` (`id_kegiatan`,`tanggal`),
  CONSTRAINT `fk_progress_petugas_kegiatan` FOREIGN KEY (`id_petugas`,`id_kegiatan`)
    REFERENCES `petugas` (`id_petugas`,`id_kegiatan`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_progress_tanggal` CHECK (`tanggal` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. DETAIL_PROGRESS
CREATE TABLE `detail_progress` (
  `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_progress` BIGINT UNSIGNED NOT NULL,
  `kecamatan` VARCHAR(150) NOT NULL,
  `desa` VARCHAR(150) NOT NULL,
  `sls` VARCHAR(150) NOT NULL,
  `sub_sls` VARCHAR(150) NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `uraian_kegiatan` TEXT NULL,
  `permasalahan` TEXT NULL,
  `pemecahan_masalah` TEXT NULL,
  `output_tujuan` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `idx_detail_progress` (`id_progress`),
  KEY `idx_detail_desa` (`desa`),
  KEY `idx_detail_sls` (`sls`),
  CONSTRAINT `fk_detail_progress` FOREIGN KEY (`id_progress`)
    REFERENCES `progress_harian` (`id_progress`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `chk_detail_jam` CHECK (`jam_selesai` > `jam_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. DOKUMENTASI
CREATE TABLE `dokumentasi` (
  `id_dokumentasi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detail` BIGINT UNSIGNED NOT NULL,
  `nama_file` VARCHAR(255) NOT NULL,
  `path_file` VARCHAR(500) NOT NULL,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumentasi`),
  KEY `idx_dokumentasi_detail` (`id_detail`),
  CONSTRAINT `fk_dokumentasi_detail` FOREIGN KEY (`id_detail`)
    REFERENCES `detail_progress` (`id_detail`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. KWITANSI
-- Satu kwitansi untuk satu progress harian (petugas + tanggal).
-- Daerah tujuan disimpan sebagai snapshot awal, tetapi generator sebaiknya
-- menghitung ulang dari detail_progress agar mengikuti perubahan progress terbaru.
CREATE TABLE `kwitansi` (
  `id_kwitansi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_progress` BIGINT UNSIGNED NOT NULL,
  `nominal` DECIMAL(15,2) NOT NULL,
  `keperluan` TEXT NOT NULL,
  `nomor_surat_tugas` VARCHAR(150) NULL,
  `tanggal_surat_tugas` DATE NULL,
  `lama_perjalanan` VARCHAR(100) NULL,
  `berangkat` BIGINT UNSIGNED NULL,
  `pulang` BIGINT UNSIGNED NULL,
  `terbilang` VARCHAR(500) NULL,
  `asal_penugasan` VARCHAR(255) NULL,
  `daerah_tujuan` TEXT NULL,
  `tanggal_pengajuan` DATE NULL,
  `bulan_pengajuan_kwitansi` VARCHAR(80) NULL,
  `metadata_lainnya` JSON NULL,
  `dibuat_oleh` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kwitansi`),
  UNIQUE KEY `uq_kwitansi_progress` (`id_progress`),
  KEY `idx_kwitansi_dibuat_oleh` (`dibuat_oleh`),
  CONSTRAINT `fk_kwitansi_progress` FOREIGN KEY (`id_progress`)
    REFERENCES `progress_harian` (`id_progress`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_kwitansi_user` FOREIGN KEY (`dibuat_oleh`)
    REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `chk_kwitansi_nominal` CHECK (`nominal` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA DUMMY PENGUJIAN
-- Password akun dummy:
-- admin / admin123
-- revisit_petugas / petugas123
-- lapangan_petugas / petugas123
-- monitoring_petugas / petugas123
-- Hash dibuat dengan password_hash() PHP.
-- ============================================================

INSERT INTO `users`
(`id_user`,`id_kegiatan`,`username`,`password`,`role_user`) VALUES
(1,NULL,'admin','$2y$12$ow3FgYeTXsxckhKEphIKn.JaIrvUkPpbovXUVe8XhsaIEM5q5828a','admin');

INSERT INTO `kegiatan`
(`id_kegiatan`,`created_by`,`nama_kegiatan`,`nomor_kegiatan`,`tanggal_mulai`,`tanggal_selesai`,`status`,`deskripsi`,`tujuan_akhir`) VALUES
(1,1,'Revisit SE','054.01.GG.2902.FAN.ZZ1.051.C.524113','2026-09-17','2026-09-30','aktif','Contoh kegiatan dengan LPD dan kwitansi.','keduanya'),
(2,1,'Pendataan Lapangan','PL-2026-001','2026-10-01','2026-12-31','aktif','Contoh kegiatan dengan kwitansi saja.','kwitansi'),
(3,1,'Monitoring','MON-2026-001','2026-11-01','2026-11-30','aktif','Contoh kegiatan dengan LPD saja.','lpd');

INSERT INTO `users`
(`id_user`,`id_kegiatan`,`username`,`password`,`role_user`) VALUES
(2,1,'revisit_petugas','$2y$12$AJLl.h.WvCb22IJZF9stsenKJ2qFytPKOq8lcn5h6vf/gYCMuMFQ.','petugas'),
(3,2,'lapangan_petugas','$2y$12$AJLl.h.WvCb22IJZF9stsenKJ2qFytPKOq8lcn5h6vf/gYCMuMFQ.','petugas'),
(4,3,'monitoring_petugas','$2y$12$AJLl.h.WvCb22IJZF9stsenKJ2qFytPKOq8lcn5h6vf/gYCMuMFQ.','petugas');

INSERT INTO `petugas`
(`id_petugas`,`id_kegiatan`,`id_user`,`nama_petugas`,`kecamatan`) VALUES
(1,1,2,'Ayu','Indralaya'),
(2,1,2,'Armiana','Tanjung Raja'),
(3,2,3,'Siti','Indralaya'),
(4,3,4,'Budi','Indralaya Utara');

INSERT INTO `progress_harian` (`id_progress`,`id_kegiatan`,`id_petugas`,`tanggal`) VALUES
(1,1,1,'2026-09-19'),
(2,2,3,'2026-10-05'),
(3,3,4,'2026-11-03');

-- Progress Ayu tanggal 19 September memiliki dua detail SLS dalam satu progress.
INSERT INTO `detail_progress`
(`id_detail`,`id_progress`,`kecamatan`,`desa`,`sls`,`sub_sls`,`jam_mulai`,`jam_selesai`,`uraian_kegiatan`,`permasalahan`,`pemecahan_masalah`,`output_tujuan`) VALUES
(1,1,'Indralaya','Timbangan','RT 001 DUSUN 1',NULL,'08:00:00','11:30:00','Pemeriksaan dan pemutakhiran hasil pendataan.','','','Data terverifikasi.'),
(2,1,'Indralaya','Timbangan','RT 002 DUSUN 1','[01] RT 002 DUSUN 1','13:00:00','16:00:00','Pemeriksaan lanjutan pada Sub SLS.','','','Pemeriksaan selesai.'),
(3,2,'Indralaya','Tanjung Seteko','RT 009 DUSUN 5',NULL,'08:00:00','15:00:00',NULL,NULL,NULL,NULL),
(4,3,'Indralaya Utara','Pulau Kabal','SLS 001',NULL,'08:00:00','15:00:00','Monitoring kegiatan lapangan.','','','Laporan monitoring tersedia.');

INSERT INTO `kwitansi`
(`id_kwitansi`,`id_progress`,`nominal`,`keperluan`,`nomor_surat_tugas`,`tanggal_surat_tugas`,`lama_perjalanan`,`berangkat`,`pulang`,`asal_penugasan`,`daerah_tujuan`,`bulan_pengajuan_kwitansi`,`dibuat_oleh`) VALUES
(1,1,170000,'Perjalanan dinas kegiatan Revisit SE','ST-001/2026','2026-09-16','1 hari',85000,85000,'Ibukota Kecamatan Indralaya','Desa Timbangan','September 2026',1);

-- Pastikan AUTO_INCREMENT berlanjut setelah data dummy.
ALTER TABLE `users` AUTO_INCREMENT = 5;
ALTER TABLE `kegiatan` AUTO_INCREMENT = 4;
ALTER TABLE `petugas` AUTO_INCREMENT = 5;
ALTER TABLE `progress_harian` AUTO_INCREMENT = 4;
ALTER TABLE `detail_progress` AUTO_INCREMENT = 5;
ALTER TABLE `kwitansi` AUTO_INCREMENT = 2;
