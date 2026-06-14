<?php
namespace Ai;

use Controller;
use ai\AiBrain;

class ChatController extends Controller {
    
    /**
     * Handles incoming chatbot queries from the frontend dashboard.
     * Routes queries to AiBrain and responds with JSON.
     */
    public function handleMessage() {
        global $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');

        // Initial greeting
        if ($message === 'init') {
            echo json_encode([
                'reply' => 'আসসালামু আলাইকুম! 👋 আমি আপনার AI Assistant। আপনার বিজনেস এবং প্রজেক্টের যেকোনো প্রশ্ন আমাকে জিজ্ঞাসা করতে পারেন।'
            ]);
            return;
        }

        if (empty($message)) {
            echo json_encode(['reply' => 'বার্তাটি খালি! অনুগ্রহ করে কিছু লিখুন।']);
            return;
        }

        $roleId = $_SESSION['user']['role_id'] ?? 0;

        // Route message to Gemini-backed AiBrain
        $reply = AiBrain::handleMessage($message, $roleId, $db);

        echo json_encode(['reply' => $reply]);
    }
}
