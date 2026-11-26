# Catatan Tahap 2

Modul fondasi (autentikasi, dashboard, kegiatan, petugas, dan import petugas) telah digabung dengan modul progress, dokumentasi, LPD, dan kwitansi pada paket final. Lihat `CHANGELOG_FINAL.md` untuk daftar implementasi dan catatan pengujian.


## 2026-11-06 – Perbaikan form progress petugas
- Ditambahkan master desa/kelurahan Ogan Ilir (16 kecamatan, 227 desa, 14 kelurahan) beserta tabel master SLS dan Sub-SLS.
- Dropdown desa/kelurahan, SLS, dan Sub-SLS dibuat bertingkat serta dibatasi berdasarkan kecamatan penugasan; validasi backend memeriksa relasi setiap pilihan.
- Jam mulai/selesai default `00:00`; pilihan uraian dan kendala dipisahkan serta textarea lainnya dibuat kondisional.
- Dashboard petugas menampilkan status kegiatan dari database dan kolom Aksi; tombol kembali, hapus baris SLS tambahan, serta tombol form dirapikan.
- Sumber SLS/Sub-SLS memakai `data/desa_sls.json` dan `data/sub_sls.json` yang sudah ada pada proyek. Data yang nama desanya tidak cocok dengan master tidak dipaksakan dipetakan; seeder memberi peringatan.

## 6 November 2026 — Refactor Login dan Navigasi Admin
- Menyesuaikan urutan elemen login: username, password, Ingat Saya dan Lupa Password dalam satu baris, tombol Masuk berikon, serta tautan Registrasi Sekarang di bawah tombol.
- Menampilkan identitas SIPACAK dan logo BPS pada halaman login menggunakan aset logo yang sudah tersedia di template dokumen.
- Mengganti navigasi Admin menjadi sidebar responsif dengan tiga menu utama: Dashboard, Kegiatan, dan Administrasi.
- Mengarahkan Admin ke Dashboard setelah login (mempertahankan redirect berbasis role yang sudah ada).
- Memfokuskan Dashboard pada statistik dan monitoring aktual, termasuk visual progress per kegiatan; memindahkan pengelolaan kegiatan ke submenu Kegiatan.
- Menambahkan alur Administrasi: pilih kegiatan, lalu masuk ke pengelolaan LPD dan/atau Kwitansi sesuai tujuan akhir kegiatan.
- Mengubah aksi tabel kegiatan menjadi tombol Kelola Kegiatan dan mempertahankan fitur CRUD, Assign Petugas, Monitoring, LPD, serta Kwitansi.
- Menambahkan route dan controller Administrasi yang tetap berada dalam middleware `verified` dan `role:admin`.
