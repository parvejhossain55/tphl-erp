<?php

class NotificationController extends Controller
{
    private $db;
    private $notificationModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->notificationModel = new Notification($db);
    }

    private function requireAccess(bool $json = false): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            if ($json) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header('Location: ' . ($GLOBALS['base_url'] ?? '') . '/login');
            exit;
        }

        return $userId;
    }

    /**
     * GET /notifications
     * Dedicated Notification Center page for ERP users with 'Notifications' permission.
     */
    public function index()
    {
        $userId = $this->requireAccess(false);

        $filters = [
            'status'    => $_GET['status'] ?? 'all',
            'category'  => $_GET['category'] ?? 'all',
            'search'    => $_GET['search'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        $result = $this->notificationModel->getForUser($userId, $filters, $page, $perPage);
        $counts = $this->notificationModel->getSummaryCounts($userId);

        $isAjax = !empty($_GET['ajax'])
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'      => 'success',
                'items'       => $result['items'],
                'total'       => $result['total'],
                'page'        => $result['page'],
                'per_page'    => $result['per_page'],
                'total_pages' => $result['total_pages'],
                'counts'      => $counts,
            ]);
            exit;
        }

        $this->view('notifications/index', [
            'title'         => 'Notifications',
            'notifications' => $result['items'],
            'total'         => $result['total'],
            'page'          => $result['page'],
            'per_page'      => $result['per_page'],
            'total_pages'   => $result['total_pages'],
            'counts'        => $counts,
            'filters'       => $filters,
        ]);
    }

    /**
     * GET /notifications/unread-count
     * Lightweight JSON endpoint for sidebar/header badge updates.
     */
    public function unreadCount()
    {
        $userId = $this->requireAccess(true);
        header('Content-Type: application/json');

        echo json_encode([
            'status'       => 'success',
            'unread_count' => $this->notificationModel->getUnreadCount($userId),
            'latest'       => $this->notificationModel->getLatestUnread($userId, 5),
        ]);
        exit;
    }

    /**
     * POST /notifications/mark-read
     * Mark a single notification as read.
     */
    public function markRead()
    {
        $userId = $this->requireAccess(true);
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $raw = json_decode(file_get_contents('php://input'), true);
            $id = (int)($raw['id'] ?? 0);
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid notification ID']);
            exit;
        }

        $ok = $this->notificationModel->markAsRead($id, $userId);
        echo json_encode([
            'status'       => $ok ? 'success' : 'error',
            'unread_count' => $this->notificationModel->getUnreadCount($userId),
        ]);
        exit;
    }

    /**
     * POST /notifications/mark-all-read
     * Mark all unread notifications for the logged-in user as read.
     */
    public function markAllRead()
    {
        $userId = $this->requireAccess(true);
        header('Content-Type: application/json');

        $updated = $this->notificationModel->markAllAsRead($userId);
        echo json_encode([
            'status'       => 'success',
            'updated'      => $updated,
            'unread_count' => 0,
        ]);
        exit;
    }
}
