<?php

namespace App\Support;

class TerbilangRupiah
{
    private const ANGKA = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam',
        'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
    ];

    public static function buat(int $nilai): string
    {
        if ($nilai < 0) {
            return 'Minus ' . self::buat(abs($nilai));
        }

        if ($nilai === 0) {
            return 'Nol Rupiah';
        }

        return trim(self::bilang($nilai)) . ' Rupiah';
    }

    private static function bilang(int $n): string
    {
        if ($n < 12) return self::ANGKA[$n];
        if ($n < 20) return self::bilang($n - 10) . ' Belas';
        if ($n < 100) return self::bilang(intdiv($n, 10)) . ' Puluh ' . self::bilang($n % 10);
        if ($n < 200) return 'Seratus ' . self::bilang($n - 100);
        if ($n < 1000) return self::bilang(intdiv($n, 100)) . ' Ratus ' . self::bilang($n % 100);
        if ($n < 2000) return 'Seribu ' . self::bilang($n - 1000);
        if ($n < 1000000) return self::bilang(intdiv($n, 1000)) . ' Ribu ' . self::bilang($n % 1000);
        if ($n < 1000000000) return self::bilang(intdiv($n, 1000000)) . ' Juta ' . self::bilang($n % 1000000);
        if ($n < 1000000000000) return self::bilang(intdiv($n, 1000000000)) . ' Miliar ' . self::bilang($n % 1000000000);

        return self::bilang(intdiv($n, 1000000000000)) . ' Triliun ' . self::bilang($n % 1000000000000);
    }
}
