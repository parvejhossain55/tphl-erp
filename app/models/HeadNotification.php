<?php
class HeadNotification
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO head_notifications 
            (user_id, type, title, message, url)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['title'],
            $data['message'],
            $data['url'] ?? null
        ]);
    }

    public function unreadCount($userId)
    {

    return $this->db->query("SELECT COUNT(*) FROM head_notifications WHERE user_id = $userId AND is_read = 0");
        // return $this->db->query("
        //     SELECT COUNT(*) 
        //     FROM head_notifications 
        //     WHERE user_id = $userId AND is_read = 0
        // ")->fetchColumn();
    }

    public function latest($userId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM head_notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function markAsRead($id, $userId)
    {
        $stmt = $this->db->prepare("
            UPDATE head_notifications
            SET is_read = 1
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }
}
