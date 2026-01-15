<?php
namespace Ai;

use Controller;
use IntentService;
use EmployeeService;
use ProjectService;
use SalesService;
use AccountService;
use ChatFormatter;
use ChatService;

class ChatController extends Controller {
    
public function handleMessage() {
    global $db;
    if (session_status() === PHP_SESSION_NONE) session_start();

    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    // Initial greeting
    if($message === 'init'){
        echo json_encode(['reply'=>'হ্যালো! 👋 আমি আপনার AI Assistant। আপনার dashboard সম্পর্কে যেকোনো প্রশ্ন জিজ্ঞাসা করতে পারেন।']);
        return;
    }

    if (!$message) {
        echo json_encode(['reply' => 'Message empty!']);
        return;
    }

    $role_id = $_SESSION['user']['role_id'] ?? 0;

    $reply = 'দুঃখিত, আমি আপনার প্রশ্নটি বুঝতে পারিনি।'; // default
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
    // reply already default
}

echo json_encode(['reply'=>$reply]);

}

}

