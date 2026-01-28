<?php
namespace ai;

use EmployeeService;
use ProjectService;
use SalesService;
use AccountService;
use ChatFormatter;
use ChatService;
use IntentService;

class AiBrain {
    public static function handleMessage(string $message, int $role_id, \mysqli $db): string {
        $reply = 'দুঃখিত, আমি আপনার প্রশ্নটি বুঝতে পারিনি।';

        try {
            $intent = IntentService::detectIntent($message);

            switch ($intent) {
                case 'employee': $data = EmployeeService::getTotalEmployees($db); break;
                case 'project': $data = ProjectService::getAllProjects($db); break;
                case 'sales': $data = SalesService::getOverview($db); break;
                case 'cash': $data = AccountService::getCashFlow($db); break;
                default: $data = null;
            }

            $reply = ChatFormatter::format($message, $intent, $data);

            ChatService::saveMessage($db, $role_id, $message, $reply);

        } catch(\Throwable $e) {
            $logFile = dirname(__DIR__, 2) . '/logs/chat_error.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }

        return $reply;
    }
}
