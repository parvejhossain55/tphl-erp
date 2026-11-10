<?php

class FormatHelper
{
    /**
     * Formats date to 'd F, Y'.
     */
    public static function formatDate($date)
    {
        if (!$date || $date === '0000-00-00' || strtotime($date) === false) {
            return '';
        }
        return date('d F, Y', strtotime($date));
    }

    /**
     * Formats time to 'H:i'.
     */
    public static function formatPunchTime($time)
    {
        if (!$time || $time == '0000-00-00 00:00:00') {
            return '-';
        }
        return date('H:i', strtotime($time));
    }

    /**
     * Formats numbers to BDT (৳) representation with Indian-style comma formatting.
     */
    public static function formatBdt($number)
    {
        $number = round((float)$number, 2);

        $parts = explode('.', number_format($number, 2, '.', ''));
        $integer = $parts[0];
        $decimal = '.' . $parts[1];

        $last3 = substr($integer, -3);
        $rest  = substr($integer, 0, -3);

        if ($rest != '') {
            $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
            return $rest . ',' . $last3 . $decimal;
        }

        return $last3 . $decimal;
    }

    /**
     * Formats sqft (area) cleanly.
     * Whole numbers are printed without decimals (e.g., 1550.0000 -> 1550).
     * Decimals are formatted to 2 decimal places (e.g., 1550.5000 -> 1550.50).
     */
    public static function formatSqft($val, $withCommas = false)
    {
        if ($val === null || $val === '' || !is_numeric($val)) {
            return '';
        }

        $num = (float)$val;
        if (floor($num) == $num) {
            return $withCommas ? number_format($num, 0) : (string)(int)$num;
        }

        return $withCommas ? number_format($num, 2) : number_format($num, 2, '.', '');
    }

    /**
     * Formats number to words in Bangladeshi / South Asian numbering format (Crore, Lakh, Thousand, Hundred).
     */
    public static function amountInWords($amount)
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount = round((float)$amount, 2);
        if ($amount <= 0) {
            return 'Zero Taka Only';
        }

        $intPart = (int) floor($amount);
        $fraction = (int) round(($amount - $intPart) * 100);

        $words = self::convertNumberToWords($intPart) . ' Taka';
        if ($fraction > 0) {
            $words .= ' and ' . self::convertNumberToWords($fraction) . ' Paisa';
        }

        return $words . ' Only';
    }

    private static function convertNumberToWords($number)
    {
        $dictionary = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
            60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
            100 => 'Hundred', 1000 => 'Thousand', 100000 => 'Lakh', 10000000 => 'Crore'
        ];

        $number = (int) $number;
        if ($number < 0) {
            return 'Negative ' . self::convertNumberToWords(abs($number));
        }

        switch (true) {
            case $number < 21:
                return $dictionary[$number];
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                return $dictionary[$tens] . ($units ? ' ' . $dictionary[$units] : '');
            case $number < 1000:
                $hundreds = (int) ($number / 100);
                $remainder = $number % 100;
                return $dictionary[$hundreds] . ' Hundred' . ($remainder ? ' ' . self::convertNumberToWords($remainder) : '');
            case $number < 100000:
                $thousands = (int) ($number / 1000);
                $remainder = $number % 1000;
                return self::convertNumberToWords($thousands) . ' Thousand' . ($remainder ? ' ' . self::convertNumberToWords($remainder) : '');
            case $number < 10000000:
                $lakhs = (int) ($number / 100000);
                $remainder = $number % 100000;
                return self::convertNumberToWords($lakhs) . ' Lakh' . ($remainder ? ' ' . self::convertNumberToWords($remainder) : '');
            default:
                $crores = (int) ($number / 10000000);
                $remainder = $number % 10000000;
                return self::convertNumberToWords($crores) . ' Crore' . ($remainder ? ' ' . self::convertNumberToWords($remainder) : '');
        }
    }

    /**
     * Truncates text.
     */
    public static function truncateText($text, $limit = 50)
    {
        $text = strip_tags($text);
        if (strlen($text) > $limit) {
            return substr($text, 0, $limit) . '...';
        }
        return $text;
    }

    /**
     * Calculates Bengali read time for content.
     */
    public static function calculateReadTime($content, $wpm = 180)
    {
        if (empty($content)) {
            return '1 min read';
        }

        $text = trim(strip_tags((string) $content));
        $wordCount = preg_match_all('/\p{Bengali}+/u', $text);
        $minutes = max(1, ceil($wordCount / $wpm));

        return $minutes . ' min read';
    }

    /**
     * Short scale BDT format (K, L, Cr).
     */
    public static function cpFmt($n)
    {
        $n = (float) $n;
        if ($n >= 10000000)
            return '৳' . number_format($n / 10000000, 2) . ' Cr';
        if ($n >= 100000)
            return '৳' . number_format($n / 100000, 2) . ' L';
        if ($n >= 1000)
            return '৳' . number_format($n / 1000, 1) . 'K';
        return '৳' . number_format($n, 0);
    }
}
