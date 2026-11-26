# Perbaikan validasi Jenis Kegiatan — 24 November 2026

- Menghapus validasi wajib pada field lama `kegiatan_jam_mulai`, `kegiatan_jam_selesai`, dan `kegiatan_uraian` yang sudah tidak dikirim oleh form.
- Memvalidasi setiap item pada struktur dinamis `kegiatan_items` (jam mulai, jam selesai, dan uraian).
- Menambahkan pesan validasi berbahasa Indonesia untuk field kegiatan dan output agar pesan internal `validation.required` tidak tampil kepada pengguna.
- Tidak mengubah struktur database, alur penyimpanan, maupun tampilan form.
