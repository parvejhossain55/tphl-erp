<?php

class ChatFormatter
{

    /**
     * Format AI/chat reply based on intent and data
     */
    public static function format($question, $intent, $data)
    {
        if (!$data) {
            return 'দুঃখিত, আমি প্রশ্নটি বুঝতে পারছি না। Employees, Projects, Sales, Cash Flow সম্পর্কে জিজ্ঞাসা করুন।';
        }

        switch ($intent) {
            case 'employee':
                return "মোট Employees সংখ্যা: {$data['total_employees']}";

            case 'project':
                $reply = "মোট প্রজেক্ট সংখ্যা: {$data['total']}\n";
                foreach ($data['projects'] as $name) {
                    $reply .= "- {$name}\n";
                }
                return trim($reply);
            case 'sales':
                return "Sales Overview: Total Sale={$data['total_sales']}, Revenue={$data['revenue']}";

            case 'cash':
                return "Cash Flow: Cash In={$data['cash_in']}, Cash Out={$data['cash_out']}, Net={$data['net_balance']}";

            default:
                return 'দুঃখিত, আমি প্রশ্নটি বুঝতে পারছি না।';
        }
    }

    /**
     * Optional: AI polish / translate / language enhancements
     */
    public static function polish($text)
    {
        // If using OpenAI or any NLP tool
        return $text;
    }
}
