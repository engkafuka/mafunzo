<?php

namespace App\Support;

use Carbon\CarbonInterface;

class CertificateDateFormatter
{
    public static function longEnglish(?CarbonInterface $date = null): string
    {
        $date = $date ?? now();

        $day = self::ordinalDay((int) $date->format('j'));
        $month = $date->format('F');
        $year = self::yearInWords((int) $date->format('Y'));

        return "On the {$day} day of {$month} in the Year {$year}.";
    }

    private static function ordinalDay(int $day): string
    {
        $words = [
            1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
            6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
            11 => 'Eleventh', 12 => 'Twelfth', 13 => 'Thirteenth', 14 => 'Fourteenth', 15 => 'Fifteenth',
            16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth', 19 => 'Nineteenth', 20 => 'Twentieth',
            21 => 'Twenty first', 22 => 'Twenty second', 23 => 'Twenty third', 24 => 'Twenty fourth',
            25 => 'Twenty fifth', 26 => 'Twenty sixth', 27 => 'Twenty seventh', 28 => 'Twenty eighth',
            29 => 'Twenty ninth', 30 => 'Thirtieth', 31 => 'Thirty first',
        ];

        return $words[$day] ?? (string) $day;
    }

    private static function yearInWords(int $year): string
    {
        if ($year < 2000 || $year > 2099) {
            return (string) $year;
        }

        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        ];
        $teens = [
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        $rest = $year - 2000;
        if ($rest === 0) {
            return 'Two Thousand';
        }

        if ($rest < 10) {
            return 'Two Thousand '.$ones[$rest];
        }

        if ($rest < 20) {
            return 'Two Thousand '.$teens[$rest];
        }

        $t = intdiv($rest, 10);
        $o = $rest % 10;

        return 'Two Thousand '.$tens[$t].($o ? ' '.$ones[$o] : '');
    }
}
