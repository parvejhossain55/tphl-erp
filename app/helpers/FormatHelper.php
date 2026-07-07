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
     * Formats integer amount in words.
     */
    public static function amountInWords($amount)
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount = round((float)$amount);

        if (!class_exists('NumberFormatter')) {
            return '';
        }

        $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $words = ucwords($formatter->format($amount));

        return $words . " Taka Only";
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
