<?php

class AtsActivityLog extends Model
{
    protected $table = 'ats_activity_logs';

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Static helper to log an activity seamlessly from anywhere
     *
     * @param mysqli $db
     * @param array $data ['action_type', 'candidate_id', 'candidate_name', 'description', 'details']
     * @return bool
     */
    public static function log($db, array $data): bool
    {
        if (!$db) {
            return false;
        }

        try {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $userName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['user_name'] ?? 'System');
            $userEmail = $_SESSION['user']['email'] ?? null;

            $actionType = substr(trim($data['action_type'] ?? 'general'), 0, 50);
            $candidateId = !empty($data['candidate_id']) ? (int)$data['candidate_id'] : null;
            $candidateName = !empty($data['candidate_name']) ? substr(trim($data['candidate_name']), 0, 150) : null;
            $description = trim($data['description'] ?? '');

            $details = null;
            if (isset($data['details'])) {
                $details = is_array($data['details']) ? json_encode($data['details'], JSON_UNESCAPED_UNICODE) : (string)$data['details'];
            }

            // Client IP
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            if (strpos($ipAddress, ',') !== false) {
                $parts = explode(',', $ipAddress);
                $ipAddress = trim($parts[0]);
            }
            $ipAddress = substr($ipAddress, 0, 45);

            // User Agent
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $stmt = $db->prepare("INSERT INTO ats_activity_logs 
                (user_id, user_name, user_email, action_type, candidate_id, candidate_name, description, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param(
                "isssisssss",
                $userId,
                $userName,
                $userEmail,
                $actionType,
                $candidateId,
                $candidateName,
                $description,
                $details,
                $ipAddress,
                $userAgent
            );

            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Throwable $e) {
            error_log("AtsActivityLog Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch paginated logs with filters
     */
    public function getLogs(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = max(10, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = ["1=1"];
        $params = [];
        $types = "";

        // 1. Search Query
        if (!empty($filters['search'])) {
            $search = "%" . trim($filters['search']) . "%";
            $conditions[] = "(candidate_name LIKE ? OR user_name LIKE ? OR description LIKE ? OR action_type LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        // 2. User filter
        if (!empty($filters['user_id'])) {
            $conditions[] = "user_id = ?";
            $params[] = (int)$filters['user_id'];
            $types .= "i";
        }

        // 3. Action type filter
        if (!empty($filters['action_type'])) {
            $conditions[] = "action_type = ?";
            $params[] = trim($filters['action_type']);
            $types .= "s";
        }

        // 4. Candidate ID
        if (!empty($filters['candidate_id'])) {
            $conditions[] = "candidate_id = ?";
            $params[] = (int)$filters['candidate_id'];
            $types .= "i";
        }

        // 5. Date From
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = trim($filters['date_from']);
            $types .= "s";
        }

        // 6. Date To
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = trim($filters['date_to']);
            $types .= "s";
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM ats_activity_logs WHERE {$whereClause}";
        $stmtCount = $this->db->prepare($countSql);
        if ($types && !empty($params)) {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $totalResult = $stmtCount->get_result()->fetch_assoc();
        $total = (int)($totalResult['total'] ?? 0);
        $stmtCount->close();

        // Fetch logs
        $sql = "SELECT * FROM ats_activity_logs WHERE {$whereClause} ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $fetchParams = $params;
        $fetchTypes = $types . "ii";
        $fetchParams[] = $perPage;
        $fetchParams[] = $offset;

        $stmt->bind_param($fetchTypes, ...$fetchParams);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $totalPages = (int)ceil($total / $perPage);

        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, $totalPages)
        ];
    }

    /**
     * Get summary metrics for the Activity Log Dashboard
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');

        // Total today
        $stmt = $this->db->prepare("SELECT COUNT(*) as total_today FROM ats_activity_logs WHERE DATE(created_at) = ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $totalToday = (int)($stmt->get_result()->fetch_assoc()['total_today'] ?? 0);
        $stmt->close();

        // Total all time
        $resAll = $this->db->query("SELECT COUNT(*) as total_all FROM ats_activity_logs");
        $totalAll = (int)($resAll->fetch_assoc()['total_all'] ?? 0);

        // Most active user today
        $stmtUser = $this->db->prepare("SELECT user_name, COUNT(*) as actions_count 
            FROM ats_activity_logs 
            WHERE DATE(created_at) = ? AND user_name IS NOT NULL 
            GROUP BY user_name 
            ORDER BY actions_count DESC 
            LIMIT 1");
        $stmtUser->bind_param("s", $today);
        $stmtUser->execute();
        $topUserRow = $stmtUser->get_result()->fetch_assoc();
        $topUser = $topUserRow ? $topUserRow['user_name'] . " (" . $topUserRow['actions_count'] . " actions)" : 'None yet';
        $stmtUser->close();

        // Action distribution today
        $stmtDist = $this->db->prepare("SELECT action_type, COUNT(*) as count 
            FROM ats_activity_logs 
            WHERE DATE(created_at) = ? 
            GROUP BY action_type 
            ORDER BY count DESC 
            LIMIT 5");
        $stmtDist->bind_param("s", $today);
        $stmtDist->execute();
        $dist = $stmtDist->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtDist->close();

        return [
            'total_today' => $totalToday,
            'total_all' => $totalAll,
            'top_user' => $topUser,
            'distribution' => $dist
        ];
    }

    /**
     * Get distinct users who have performed actions
     */
    public function getUsersList(): array
    {
        $res = $this->db->query("SELECT DISTINCT user_id, user_name, user_email 
            FROM ats_activity_logs 
            WHERE user_name IS NOT NULL AND user_name != '' 
            ORDER BY user_name ASC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get distinct action types
     */
    public function getActionTypes(): array
    {
        $res = $this->db->query("SELECT DISTINCT action_type 
            FROM ats_activity_logs 
            WHERE action_type IS NOT NULL AND action_type != '' 
            ORDER BY action_type ASC");
        return $res ? array_column($res->fetch_all(MYSQLI_ASSOC), 'action_type') : [];
    }
}
