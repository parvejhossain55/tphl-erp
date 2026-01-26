<?php
class ChatService {

    public static function saveMessage($db, $role_id, $message, $reply) {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_role, message, reply) VALUES (?, ?, ?)");
        $stmt->execute([$role_id, $message, $reply]);
    }
}
