<?php
namespace ai;

class IntentDetector {

    public static function detect(string $q): string
    {
        $q = mb_strtolower($q);

        if (str_contains($q, 'employee') || str_contains($q, 'কর্মচারী'))
            return 'employee';

        if (str_contains($q, 'project') || str_contains($q, 'প্রজেক্ট'))
            return 'project';

        if (str_contains($q, 'sale') || str_contains($q, 'বিক্রি'))
            return 'sales';

        if (str_contains($q, 'cash') || str_contains($q, 'balance'))
            return 'cash';

        return 'unknown';
    }
}
