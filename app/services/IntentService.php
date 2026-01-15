<?php
class IntentService {

    public static function detectIntent($question) {
        $q = strtolower($question);

        if (str_contains($q, 'employee') || str_contains($q, 'কর্মচারী')) return 'employee';
        if (str_contains($q, 'project') || str_contains($q, 'প্রজেক্ট')) return 'project';
        if (str_contains($q, needle: 'sales') || str_contains($q, 'বিক্রয়')) return 'sales';
        if (str_contains($q, 'cash') || str_contains($q, 'টাকা')) return 'cash';

        return 'unknown';
    }
}
