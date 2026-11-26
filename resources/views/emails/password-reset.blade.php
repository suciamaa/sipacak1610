<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Atur Ulang Password SIPACAK</title>
</head>
<body style="margin:0;padding:0;background-color:#F4F8FB;font-family:Arial,Helvetica,sans-serif;color:#1F3445;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F4F8FB;padding:28px 12px;">
        <tr><td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #E1EBF1;border-radius:16px;overflow:hidden;">
                <tr><td style="padding:27px 32px;background:#005AA9;color:#ffffff;">
                    <div style="font-size:11px;font-weight:bold;letter-spacing:2px;color:#C7EFF1;">BADAN PUSAT STATISTIK KABUPATEN OGAN ILIR</div>
                    <div style="margin-top:7px;font-size:29px;font-weight:800;letter-spacing:-1px;color:#ffffff;">SIPACAK</div>
                    <div style="margin-top:4px;font-size:13px;line-height:1.5;color:#E7F7F8;">Sistem Laporan Lapangan dan Administrasi Cepat Akurat</div>
                </td></tr>
                <tr><td style="padding:34px 32px 30px;">
                    <div style="font-size:12px;font-weight:bold;letter-spacing:1.5px;color:#008B98;">PEMULIHAN AKUN</div>
                    <h1 style="margin:10px 0 18px;font-size:23px;line-height:1.3;color:#17364D;">Atur ulang password Anda</h1>
                    <p style="margin:0 0 14px;font-size:14px;line-height:1.75;color:#536F80;">Halo {{ $userName }},</p>
                    <p style="margin:0 0 22px;font-size:14px;line-height:1.75;color:#536F80;">Kami menerima permintaan untuk mengatur ulang password akun SIPACAK Anda. Klik tombol di bawah untuk membuat password baru.</p>
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;"><tr><td align="center" bgcolor="#005AA9" style="border-radius:10px;">
                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 23px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;border-radius:10px;">Atur Ulang Password</a>
                    </td></tr></table>
                    <p style="margin:0 0 10px;font-size:12px;line-height:1.7;color:#758A98;">Tautan ini berlaku selama {{ $expireMinutes }} menit. Jika tombol tidak dapat dibuka, salin tautan berikut ke browser Anda:</p>
                    <p style="margin:0 0 22px;word-break:break-all;font-size:12px;line-height:1.6;"><a href="{{ $resetUrl }}" style="color:#005AA9;">{{ $resetUrl }}</a></p>
                    <div style="padding:14px 16px;border:1px solid #E1EBF1;border-radius:10px;background:#F4F8FB;font-size:12px;line-height:1.65;color:#758A98;">Jika Anda tidak meminta pengaturan ulang password, abaikan email ini. Password akun Anda tidak akan berubah.</div>
                </td></tr>
                <tr><td style="padding:17px 32px;background:#F4F8FB;border-top:1px solid #E1EBF1;font-size:11px;line-height:1.6;color:#8798A4;">Email otomatis SIPACAK · BPS Kabupaten Ogan Ilir<br>Mohon tidak membalas email ini.</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
