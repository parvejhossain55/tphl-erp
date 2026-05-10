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
        $userId = (int)$userId;
        $res = $this->db->query("SELECT COUNT(*) as count FROM head_notifications WHERE user_id = $userId AND is_read = 0");
        if ($res) {
            $row = $res->fetch_assoc();
            return $row['count'] ?? 0;
        }
        return 0;
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
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
