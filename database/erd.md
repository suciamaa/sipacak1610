# ERD SIPACAK — Tahap 1

```mermaid
erDiagram
    USERS ||--o{ KEGIATAN : "dibuat oleh"
    KEGIATAN ||--o| USERS : "memiliki satu akun petugas"
    KEGIATAN ||--o{ PETUGAS : "mendaftarkan"
    USERS ||--o{ PETUGAS : "akun bersama"
    PETUGAS ||--o{ PROGRESS_HARIAN : "menginput"
    KEGIATAN ||--o{ PROGRESS_HARIAN : "membatasi"
    PROGRESS_HARIAN ||--o{ DETAIL_PROGRESS : "memiliki detail"
    DETAIL_PROGRESS ||--o{ DOKUMENTASI : "memiliki foto"
    PROGRESS_HARIAN ||--o| KWITANSI : "memiliki administrasi"

    KEGIATAN {
      int id_kegiatan PK
      int created_by FK
      varchar nama_kegiatan
      varchar nomor_kegiatan
      date tanggal_mulai
      date tanggal_selesai
      enum status
      enum tujuan_akhir
    }
    USERS {
      int id_user PK
      int id_kegiatan FK_UQ
      varchar username UK
      varchar password
      enum role_user
    }
    PETUGAS {
      int id_petugas PK
      int id_kegiatan FK
      int id_user FK
      varchar nama_petugas
      varchar kecamatan
      timestamp deleted_at
    }
    PROGRESS_HARIAN {
      bigint id_progress PK
      int id_kegiatan FK
      int id_petugas FK
      date tanggal
    }
    DETAIL_PROGRESS {
      bigint id_detail PK
      bigint id_progress FK
      varchar kecamatan
      varchar desa
      varchar sls
      varchar sub_sls
      time jam_mulai
      time jam_selesai
      text uraian_kegiatan
      text permasalahan
      text pemecahan_masalah
      text output_tujuan
    }
    DOKUMENTASI {
      bigint id_dokumentasi PK
      bigint id_detail FK
      varchar nama_file
      varchar path_file
      text keterangan
    }
    KWITANSI {
      bigint id_kwitansi PK
      bigint id_progress FK_UQ
      decimal nominal
      text keperluan
      varchar nomor_surat_tugas
      date tanggal_surat_tugas
      varchar lama_perjalanan
      bigint berangkat
      bigint pulang
      varchar terbilang
      varchar asal_penugasan
      text daerah_tujuan
      varchar bulan_pengajuan_kwitansi
      json metadata_lainnya
    }
```

## Penjelasan relasi dan keputusan teknis

1. **Kegiatan–Users bersifat relasi melingkar secara logis.** `kegiatan.created_by` menunjuk admin pada `users`; sedangkan `users.id_kegiatan` menunjuk kegiatan untuk akun petugas. FK `kegiatan.created_by` ditambahkan setelah tabel `users` dibuat.
2. **Satu kegiatan hanya memiliki satu akun petugas.** UNIQUE pada `users.id_kegiatan` mengizinkan banyak `NULL` untuk admin, tetapi hanya satu akun petugas per kegiatan.
3. **Satu akun petugas dipakai banyak identitas petugas.** `petugas.id_user` dan `petugas.id_kegiatan` memakai composite FK ke `users`, sehingga akun dari kegiatan lain tidak dapat dipasangkan.
4. **Progress membawa `id_kegiatan`.** Kolom ini merupakan tambahan teknis dibanding daftar kolom minimum. Composite FK `(id_petugas,id_kegiatan)` memastikan progress tidak dapat ditautkan ke petugas dari kegiatan berbeda.
5. **Satu progress per petugas per tanggal.** UNIQUE `(id_petugas,tanggal)`. Banyak lokasi/SLS pada tanggal yang sama disimpan sebagai beberapa baris `detail_progress`.
6. **Kwitansi terkait ke `progress_harian`, bukan ke detail.** UNIQUE `kwitansi.id_progress` membatasi satu kwitansi per petugas per tanggal.
7. Referensi wilayah lama tetap disediakan melalui `data/desa_sls.json` dan `data/sub_sls.json`. Kolom wilayah pada `detail_progress` menyimpan nilai yang dipilih. Validasi pilihan wilayah dilakukan backend terhadap referensi JSON. Normalisasi wilayah menjadi tabel referensi tersendiri dapat dilakukan sebagai iterasi berikutnya tanpa mengubah relasi inti.
8. Kolom `daerah_tujuan` pada kwitansi adalah snapshot administrasi. Untuk memenuhi sinkronisasi perubahan progress, generator PDF harus membangun tujuan terbaru dari `detail_progress` saat dokumen dibuat, bukan mempercayai snapshot lama.

**Pemetaan Excel kwitansi:** header `Hal` pada template lama dipetakan ke kolom `kwitansi.keperluan` (bukan disimpan dua kali). `tanggal_pengajuan` tetap disediakan untuk kompatibilitas template lama, sedangkan template terbaru menggunakan `bulan_pengajuan_kwitansi`.
