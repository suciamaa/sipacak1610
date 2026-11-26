# Perbaikan Registrasi dan Reset Password OTP — 15 November 2026

## Perubahan
- Registrasi Petugas tidak lagi memicu email verifikasi; akun baru langsung aktif (`email_verified_at` diisi saat pembuatan).
- Role registrasi ditetapkan server-side sebagai `petugas`; form tidak menerima role dari pengguna.
- Login tidak lagi menolak akun karena status verifikasi email.
- Middleware `verified` dihapus dari rute dashboard Admin dan Petugas.
- Fitur Lupa Password diganti dengan OTP email 6 digit, masa berlaku 5 menit, maksimal 5 percobaan, jeda kirim ulang 60 detik, dan maksimal 3 permintaan per 15 menit.
- OTP disimpan sebagai hash, diikat ke email/pengguna, diverifikasi melalui session, dan dihapus setelah reset berhasil.
- Email OTP menggunakan Laravel Mail dan konfigurasi SMTP dari `.env`.
- Ditambahkan pembersihan hash OTP yang kedaluwarsa ketika permintaan OTP berikutnya diproses.

## File diubah
- `routes/web.php`
- `app/Models/User.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`

## File ditambahkan
- `app/Http/Controllers/Auth/PasswordResetOtpController.php`
- `app/Mail/PasswordResetOtpMail.php`
- `database/migrations/2026_11_15_000010_create_password_reset_otps_table.php`
- `resources/views/auth/verify-otp.blade.php`
- `resources/views/auth/reset-password-otp.blade.php`
- `resources/views/emails/password-reset-otp.blade.php`

## Langkah setelah upload
1. Pastikan konfigurasi SMTP Gmail yang sudah ada di `.env` tetap benar dan `MAIL_MAILER=smtp`.
2. Jalankan `php artisan migrate`.
3. Jalankan `php artisan optimize:clear` jika aplikasi menggunakan cache route/config/view.
4. Uji registrasi, login, pengiriman OTP, OTP salah/kedaluwarsa, resend, reset password, dan akses Admin/Petugas.

## Status verifikasi teknis
- `php -l` berhasil untuk seluruh 63 file PHP di `app`, `routes`, dan `database/migrations`.
- Pemeriksaan `php artisan route:list` belum dapat dijalankan di lingkungan pemeriksaan ini karena ekstensi PHP `mbstring` tidak tersedia.
- Migration dan pengujian integrasi database/email belum dijalankan karena lingkungan pemeriksaan tidak memiliki driver PDO database dan akses SMTP.
- Karena itu, pengujian end-to-end dan kompatibilitas fitur berbasis database/email perlu diselesaikan pada server SIPACAK setelah migration.
