<?php

namespace App\Support;

class IndianAmountFormat
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * @return array{amount: string, words: string}
     */
    public static function annualCtc(float $amount): array
    {
        if ($amount <= 0) {
            return ['amount' => '—', 'words' => '—'];
        }

        $rounded = (int) round($amount);

        return [
            'amount' => self::indianNumberFormat($rounded),
            'words' => 'Rupees ' . self::numberInWords($rounded) . ' Only',
        ];
    }

    public static function indianNumberFormat(int $number): string
    {
        $negative = $number < 0;
        $number = abs($number);
        $str = (string) $number;

        if (strlen($str) <= 3) {
            return ($negative ? '-' : '') . $str;
        }

        $lastThree = substr($str, -3);
        $rest = substr($str, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);

        return ($negative ? '-' : '') . $rest . ',' . $lastThree;
    }

    public static function lakhsInWords(float $lakhs): string
    {
        $whole = (int) floor($lakhs);
        $fraction = (int) round(($lakhs - $whole) * 100);

        $words = self::numberInWords($whole) . ' Lakh';
        if ($whole !== 1) {
            $words .= 's';
        }

        if ($fraction > 0) {
            $words .= ' and ' . self::numberInWords($fraction) . ' Paise';
        }

        return $words;
    }

    public static function numberInWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        if ($number < 0) {
            return 'Minus ' . self::numberInWords(abs($number));
        }

        $parts = [];

        $crores = intdiv($number, 10000000);
        if ($crores > 0) {
            $parts[] = self::chunkUnderThousand($crores) . ' Crore';
        }
        $number %= 10000000;

        $lakhs = intdiv($number, 100000);
        if ($lakhs > 0) {
            $parts[] = self::chunkUnderThousand($lakhs) . ' Lakh';
        }
        $number %= 100000;

        $thousands = intdiv($number, 1000);
        if ($thousands > 0) {
            $parts[] = self::chunkUnderThousand($thousands) . ' Thousand';
        }
        $number %= 1000;

        if ($number > 0) {
            $parts[] = self::chunkUnderThousand($number);
        }

        return implode(' ', $parts);
    }

    private static function chunkUnderThousand(int $number): string
    {
        $words = [];

        $hundreds = intdiv($number, 100);
        if ($hundreds > 0) {
            $words[] = self::ONES[$hundreds] . ' Hundred';
        }
        $number %= 100;

        if ($number > 0) {
            if ($hundreds > 0) {
                $words[] = 'and';
            }

            if ($number < 20) {
                $words[] = self::ONES[$number];
            } else {
                $words[] = trim(self::TENS[intdiv($number, 10)] . ' ' . self::ONES[$number % 10]);
            }
        }

        return implode(' ', $words);
    }
}
