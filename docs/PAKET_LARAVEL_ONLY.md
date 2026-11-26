# Catatan Paket Laravel Only

Paket ini dibentuk ulang dari project SIPACAK dan perubahan adaptasi SE2026 dengan mempertahankan aplikasi Laravel 12 sebagai satu-satunya aplikasi.

Yang disertakan: `app`, `bootstrap`, `config`, `database` (migrations, seeders, dan JSON referensi wilayah), `public`, `resources`, `routes`, `storage`, `tests`, `vendor`, Composer files, dan `artisan`.

Yang sengaja tidak disertakan: halaman PHP Native lama (`login.php`, `index.php`, `account.php`, `admin/`, `petugas/` versi Native), `includes/` Native, `legacy_native/`, `legacy_reference/`, `mysqli` bootstrap, serta dump database lama. Sumber Native tidak diperlukan untuk menjalankan aplikasi Laravel dan hanya menjadi referensi saat inspeksi.

Seluruh endpoint aplikasi berada di `routes/web.php` dan seluruh halaman berada di Blade. Entry point web Laravel adalah `public/index.php`.
