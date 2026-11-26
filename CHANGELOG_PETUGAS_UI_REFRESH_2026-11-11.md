# SIPACAK — Petugas UI Refresh (11 November 2026)

## Cakupan
- Dashboard petugas / daftar penugasan.
- Halaman riwayat progress per penugasan.
- Form tambah dan edit progress.
- Tabel daftar dokumen petugas yang menggunakan card table-responsive.

## Perubahan
- Menambahkan class body `petugas-app` khusus akun petugas agar perubahan visual tidak memengaruhi area admin.
- Menyelaraskan header, latar halaman, lebar konten, tipografi, card, tombol, field, dan jarak antarbagian dengan palet biru SIPACAK yang lebih tenang.
- Menyederhanakan hero dashboard agar lebih ringkas dan tidak terasa seperti banner promosi.
- Mengurutkan ulang tabel penugasan: Nama Kegiatan, Wilayah Kerja, Role, Periode Penugasan, Status, Aksi.
- Menetapkan lebar kolom, padding, tinggi header, vertical alignment, zebra rows, dan hover state agar tabel lebih simetris dan mudah dipindai.
- Menyeragamkan tabel riwayat progress dan tabel dokumen.
- Tombol utama petugas menggunakan primary blue; status tetap menggunakan warna semantik yang lembut.
- Memperbarui cache-busting stylesheet pada layout.

## Batas perubahan
- Tidak mengubah controller, route, query, database, validasi, atau proses penyimpanan.
- Mempertahankan perbaikan sebelumnya pada form progress, termasuk nilai Kecamatan ketika menambah detail lokasi.
- `vendor` dan `.env` tidak disertakan dalam arsip.
