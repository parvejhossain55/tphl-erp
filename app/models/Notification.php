<?php

class Notification
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? ($GLOBALS['db'] ?? null);
    }

    /**
     * Static one-liner helper for controllers (TaskV2Controller, MyWorkController).
     */
    public static function dispatch($db, $recipientUserIds, $actorUserId, string $actionType, string $message, $taskId = null, ?string $targetUrl = null, string $module = 'task', bool $sendEmail = true): int
    {
        $instance = new self($db);
        return $instance->send($recipientUserIds, $actorUserId, $actionType, $message, $taskId, $targetUrl, $module, $sendEmail);
    }

    /**
     * Send a notification to one or more recipient user IDs (users.id).
     * Automatically skips self-notifications and duplicates.
     * Optionally dispatches a branded HTML email to each recipient via sendGenericMail().
     */
    public function send($recipientUserIds, $actorUserId, string $actionType, string $message, $taskId = null, ?string $targetUrl = null, string $module = 'task', bool $sendEmail = true): int
    {
        if (!$this->db) {
            return 0;
        }

        if (!is_array($recipientUserIds)) {
            $recipientUserIds = [$recipientUserIds];
        }

        $actorId = (int)$actorUserId;
        $cleanRecipients = [];
        foreach ($recipientUserIds as $uid) {
            $uid = (int)$uid;
            if ($uid > 0 && $uid !== $actorId) {
                $cleanRecipients[$uid] = $uid;
            }
        }

        if (empty($cleanRecipients)) {
            return 0;
        }

        $msg = trim($message);
        if (function_exists('mb_substr')) {
            $msg = mb_substr($msg, 0, 255, 'UTF-8');
        } else {
            $msg = substr($msg, 0, 255);
        }

        $mod = substr(trim($module ?: 'task'), 0, 50);
        $act = substr(trim($actionType), 0, 50);
        $url = $targetUrl !== null ? substr(trim($targetUrl), 0, 255) : null;
        $tid = ($taskId !== null && (int)$taskId > 0) ? (int)$taskId : null;
        $actorParam = $actorId > 0 ? $actorId : null;

        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, task_id, assigned_by, message, module, action_type, target_url, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())"
        );
        if (!$stmt) {
            return 0;
        }

        $inserted = 0;
        foreach ($cleanRecipients as $recipientId) {
            $stmt->bind_param("iiissss", $recipientId, $tid, $actorParam, $msg, $mod, $act, $url);
            if ($stmt->execute()) {
                $inserted++;
            }
        }
        $stmt->close();

        if ($sendEmail && $inserted > 0) {
            $this->sendEmailNotifications(array_values($cleanRecipients), $actorId, $act, $msg, $tid, $url);
        }

        return $inserted;
    }

    /**
     * Send branded HTML email notifications for task actions to recipients.
     * Failures are logged and never interrupt the main request.
     */
    private function sendEmailNotifications(array $recipientIds, int $actorId, string $actionType, string $message, ?int $taskId, ?string $targetUrl): void
    {
        if (empty($recipientIds) || !function_exists('sendGenericMail')) {
            return;
        }

        $actionLabels = [
            'task_assigned'      => 'New Task Assigned',
            'task_reassigned'    => 'Task Reassigned',
            'collaborator_added' => 'Added as Task Collaborator',
            'task_mentioned'     => 'You Were Mentioned in a Task',
            'task_accepted'      => 'Task Accepted',
            'status_changed'     => 'Task Status Updated',
            'progress_updated'   => 'Task Progress Updated',
            'subtask_updated'    => 'Task Checklist Updated',
            'task_completed'     => 'Task Completed',
            'task_rated'         => 'Task Rated & Reviewed',
            'task_comment'       => 'New Comment on Task',
            'task_edited'        => 'Task Updated',
            'task_deleted'       => 'Task Deleted',
        ];
        $actionLabel = $actionLabels[strtolower($actionType)] ?? 'Task Notification';
        $accentColor = (strtolower($actionType) === 'task_deleted') ? '#dc2626' : '#006837';

        $actorName = !empty($_SESSION['user']['full_name']) ? trim((string)$_SESSION['user']['full_name']) : '';
        if ($actorName === '' && $actorId > 0) {
            $stActor = $this->db->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
            if ($stActor) {
                $stActor->bind_param("i", $actorId);
                $stActor->execute();
                $aRow = $stActor->get_result()->fetch_assoc();
                $stActor->close();
                $actorName = trim((string)($aRow['full_name'] ?? ''));
            }
        }
        if ($actorName === '') {
            $actorName = 'TPHL ERP';
        }

        // Build absolute link (omitted for deleted tasks)
        $actionLink = '';
        if (strtolower($actionType) !== 'task_deleted') {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 0) == 443);
            $protocol = $isHttps ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'thepremiumhomesltd.com';
            $base = $GLOBALS['base_url'] ?? '';
            if ($taskId !== null && $taskId > 0) {
                $actionLink = $protocol . $host . $base . '/my-work-dashboard?task_id=' . $taskId;
            } elseif (!empty($targetUrl)) {
                $actionLink = preg_match('#^https?://#i', $targetUrl)
                    ? $targetUrl
                    : ($protocol . $host . $base . '/' . ltrim($targetUrl, '/'));
            } else {
                $actionLink = $protocol . $host . $base . '/notifications';
            }
        }

        // Fetch recipient emails in one query
        $placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
        $types = str_repeat('i', count($recipientIds));
        $stUsers = $this->db->prepare("SELECT id, full_name, email FROM users WHERE id IN ($placeholders) AND email IS NOT NULL AND TRIM(email) != ''");
        if (!$stUsers) {
            return;
        }
        $stUsers->bind_param($types, ...$recipientIds);
        $stUsers->execute();
        $users = $stUsers->get_result()->fetch_all(MYSQLI_ASSOC);
        $stUsers->close();

        if (empty($users)) {
            return;
        }

        $subject = '[TPHL ERP] ' . $actionLabel . ($taskId ? " (T-{$taskId})" : '');
        $safeAction = htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8');
        $safeActor  = htmlspecialchars($actorName, ENT_QUOTES, 'UTF-8');
        $safeMsg    = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $timestamp  = date('d M Y, h:i A');

        $buttonHtml = $actionLink !== ''
            ? "<div style='text-align:center;margin:24px 0 8px;'>
                    <a href='" . htmlspecialchars($actionLink, ENT_QUOTES, 'UTF-8') . "' style='background-color:{$accentColor};color:#ffffff;padding:11px 24px;text-decoration:none;border-radius:7px;font-weight:600;font-size:14px;display:inline-block;'>View Task Details &rarr;</a>
               </div>"
            : '';

        foreach ($users as $u) {
            $email = trim((string)($u['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $recipientName = htmlspecialchars(trim((string)($u['full_name'] ?? 'Team Member')), ENT_QUOTES, 'UTF-8');

            $body = "
            <div style='font-family:Arial,Helvetica,sans-serif;background:#f4f7f5;padding:24px;'>
                <div style='max-width:580px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #d1e7dd;'>
                    <div style='background:{$accentColor};padding:18px 24px;color:#ffffff;'>
                        <div style='font-size:12px;text-transform:uppercase;letter-spacing:0.6px;opacity:0.9;'>The Premium Homes Ltd. · ERP</div>
                        <div style='font-size:18px;font-weight:700;margin-top:4px;'>{$safeAction}</div>
                    </div>
                    <div style='padding:24px;color:#1e293b;font-size:14px;line-height:1.6;'>
                        <p style='margin:0 0 12px;'>Hello <strong>{$recipientName}</strong>,</p>
                        <div style='background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid {$accentColor};border-radius:8px;padding:14px 16px;margin:14px 0;font-size:14.5px;color:#0f172a;'>
                            {$safeMsg}
                        </div>
                        <p style='margin:10px 0 0;font-size:12.5px;color:#64748b;'>
                            <strong>Action By:</strong> {$safeActor} &nbsp;|&nbsp; <strong>Time:</strong> {$timestamp}
                        </p>
                        {$buttonHtml}
                    </div>
                    <div style='background:#f8faf9;padding:12px 24px;text-align:center;border-top:1px solid #e2eae5;font-size:11.5px;color:#64748b;'>
                        This is an automated task notification from TPHL ERP.
                    </div>
                </div>
            </div>";

            try {
                sendGenericMail($email, $subject, $body);
            } catch (\Throwable $e) {
                error_log("Notification Email Error ({$email}): " . $e->getMessage());
            }
        }
    }

    /**
     * Fetch paginated, filtered notifications for a specific user (users.id).
     */
    public function getForUser(int $userId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        if (!$this->db || $userId <= 0) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 1];
        }

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["n.user_id = ?"];
        $types = "i";
        $params = [$userId];

        $status = strtolower(trim((string)($filters['status'] ?? 'all')));
        if ($status === 'unread') {
            $where[] = "n.is_read = 0";
        } elseif ($status === 'read') {
            $where[] = "n.is_read = 1";
        }

        $category = strtolower(trim((string)($filters['category'] ?? 'all')));
        $categoryMap = [
            'assigned'  => ['task_assigned', 'task_reassigned', 'collaborator_added', 'task_mentioned'],
            'progress'  => ['progress_updated', 'status_changed', 'task_accepted', 'subtask_updated'],
            'completed' => ['task_completed'],
            'rated'     => ['task_rated'],
            'comment'   => ['task_comment', 'task_mentioned'],
            'edited'    => ['task_edited', 'task_deleted'],
        ];
        if ($category !== 'all' && isset($categoryMap[$category])) {
            $placeholders = implode(',', array_fill(0, count($categoryMap[$category]), '?'));
            $where[] = "n.action_type IN ($placeholders)";
            foreach ($categoryMap[$category] as $at) {
                $types .= "s";
                $params[] = $at;
            }
        } elseif ($category !== 'all' && $category !== '') {
            $where[] = "n.action_type = ?";
            $types .= "s";
            $params[] = $category;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = "(n.message LIKE ? OR u.full_name LIKE ? OR n.action_type LIKE ?)";
            $types .= "sss";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = "DATE(n.created_at) >= ?";
            $types .= "s";
            $params[] = $dateFrom;
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = "DATE(n.created_at) <= ?";
            $types .= "s";
            $params[] = $dateTo;
        }

        $whereSql = implode(' AND ', $where);

        // Total count
        $countSql = "SELECT COUNT(*) AS total
                     FROM notifications n
                     LEFT JOIN users u ON n.assigned_by = u.id
                     WHERE $whereSql";
        $stmtCount = $this->db->prepare($countSql);
        $total = 0;
        if ($stmtCount) {
            $stmtCount->bind_param($types, ...$params);
            $stmtCount->execute();
            $row = $stmtCount->get_result()->fetch_assoc();
            $total = (int)($row['total'] ?? 0);
            $stmtCount->close();
        }

        // Data rows
        $dataSql = "SELECT n.*,
                           u.full_name AS actor_name,
                           e.photo AS actor_photo,
                           CASE
                               WHEN n.action_type = 'task_deleted' THEN 0
                               WHEN n.task_id IS NOT NULL AND t.id IS NULL THEN 0
                               ELSE 1
                           END AS task_exists
                    FROM notifications n
                    LEFT JOIN tasks t ON n.task_id = t.id
                    LEFT JOIN users u ON n.assigned_by = u.id
                    LEFT JOIN employees e ON e.user_id = u.id AND e.deleted_at IS NULL
                    WHERE $whereSql
                    ORDER BY n.created_at DESC, n.id DESC
                    LIMIT ? OFFSET ?";
        $stmtData = $this->db->prepare($dataSql);
        $items = [];
        if ($stmtData) {
            $dataTypes = $types . "ii";
            $dataParams = array_merge($params, [$perPage, $offset]);
            $stmtData->bind_param($dataTypes, ...$dataParams);
            $stmtData->execute();
            $items = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtData->close();
        }

        $totalPages = max(1, (int)ceil($total / $perPage));

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        if (!$this->db || $userId <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get summary counts for filter pills on the dedicated notifications page.
     */
    public function getSummaryCounts(int $userId): array
    {
        $defaults = [
            'all'       => 0,
            'unread'    => 0,
            'assigned'  => 0,
            'progress'  => 0,
            'completed' => 0,
            'rated'     => 0,
            'comment'   => 0,
            'edited'    => 0,
        ];
        if (!$this->db || $userId <= 0) {
            return $defaults;
        }

        $sql = "SELECT
                    COUNT(*) AS cnt_all,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS cnt_unread,
                    SUM(CASE WHEN action_type IN ('task_assigned','task_reassigned','collaborator_added','task_mentioned') THEN 1 ELSE 0 END) AS cnt_assigned,
                    SUM(CASE WHEN action_type IN ('progress_updated','status_changed','task_accepted','subtask_updated') THEN 1 ELSE 0 END) AS cnt_progress,
                    SUM(CASE WHEN action_type = 'task_completed' THEN 1 ELSE 0 END) AS cnt_completed,
                    SUM(CASE WHEN action_type = 'task_rated' THEN 1 ELSE 0 END) AS cnt_rated,
                    SUM(CASE WHEN action_type IN ('task_comment','task_mentioned') THEN 1 ELSE 0 END) AS cnt_comment,
                    SUM(CASE WHEN action_type IN ('task_edited','task_deleted') THEN 1 ELSE 0 END) AS cnt_edited
                FROM notifications
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return $defaults;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return $defaults;
        }

        return [
            'all'       => (int)($row['cnt_all'] ?? 0),
            'unread'    => (int)($row['cnt_unread'] ?? 0),
            'assigned'  => (int)($row['cnt_assigned'] ?? 0),
            'progress'  => (int)($row['cnt_progress'] ?? 0),
            'completed' => (int)($row['cnt_completed'] ?? 0),
            'rated'     => (int)($row['cnt_rated'] ?? 0),
            'comment'   => (int)($row['cnt_comment'] ?? 0),
            'edited'    => (int)($row['cnt_edited'] ?? 0),
        ];
    }

    /**
     * Get latest unread notifications for lightweight preview / polling.
     */
    public function getLatestUnread(int $userId, int $limit = 5): array
    {
        if (!$this->db || $userId <= 0) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            "SELECT n.id, n.task_id, n.message, n.module, n.action_type, n.target_url, n.created_at,
                    u.full_name AS actor_name,
                    CASE
                        WHEN n.action_type = 'task_deleted' THEN 0
                        WHEN n.task_id IS NOT NULL AND t.id IS NULL THEN 0
                        ELSE 1
                    END AS task_exists
             FROM notifications n
             LEFT JOIN tasks t ON n.task_id = t.id
             LEFT JOIN users u ON n.assigned_by = u.id
             WHERE n.user_id = ? AND n.is_read = 0
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT ?"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Mark a single notification as read for the given user.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        if (!$this->db || $notificationId <= 0 || $userId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW())
             WHERE id = ? AND user_id = ?"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ii", $notificationId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Mark all unread notifications as read for the given user.
     */
    public function markAllAsRead(int $userId): int
    {
        if (!$this->db || $userId <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE user_id = ? AND is_read = 0"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return max(0, (int)$affected);
    }
}
