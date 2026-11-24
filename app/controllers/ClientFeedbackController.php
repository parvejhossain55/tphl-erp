<?php
require_once __DIR__ . '/../core/Controller.php';

/**
 * Client Feedback Report Controller (Management Portal)
 *
 * - Management-only dashboard, analytics (5-area rating breakdown), filtering,
 *   follow-up status tracking, and CSV export.
 * - Public API endpoint POST /api/client-feedback/submit for reception QR / website form.
 */
class ClientFeedbackController extends Controller
{
    protected $db;

    protected const VALID_RATINGS = ['excellent', 'good', 'average', 'poor'];
    protected const VALID_STATUSES = ['pending', 'reviewed', 'contacted', 'resolved'];
    protected const RATING_WEIGHTS = [
        'excellent' => 4,
        'good'      => 3,
        'average'   => 2,
        'poor'      => 1,
    ];

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Strictly Management access (mirrors sidebar.php $isManagement).
     */
    protected function canAccessManagement(): bool
    {
        $roleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $priRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId    = (int)($_SESSION['user']['id'] ?? 0);

        $isSalesMgr = function_exists('isSalesManager') && isSalesManager();
        $isHeadSales = ($roleId === 9 || $secRoleId === 9) && !$isSalesMgr;
        $isPureSalesLeadership = ($isSalesMgr || $isHeadSales) && !in_array($roleId, [1, 3, 20, 32, 33, 34, 35], true);

        if ($isPureSalesLeadership) {
            return false;
        }

        $mgmtRoles = [1, 3, 33, 34, 35, 38];
        return in_array($roleId, $mgmtRoles, true)
            || in_array($secRoleId, $mgmtRoles, true)
            || in_array($priRoleId, $mgmtRoles, true)
            || $userId === 94;
    }

    protected function requireManagement(bool $isAjax = false): void
    {
        if (!$this->canAccessManagement()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized. Management access required.']);
                exit;
            }
            if (function_exists('setError')) {
                setError('You do not have permission to access Client Feedback Report.');
            }
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }
    }

    /**
     * Main Page: Management Client Feedback Report
     */
    public function index()
    {
        $this->requireManagement(false);

        $this->view('management/client-feedback-report', [
            'current_route' => 'client-feedback-report',
            'page_title'    => 'Client Feedback Report',
        ]);
    }

    /**
     * AJAX: Filtered KPIs, 5-area rating breakdown, and feedback list.
     */
    public function listAjax()
    {
        $this->requireManagement(true);
        header('Content-Type: application/json; charset=utf-8');

        $whereSql = $this->buildWhereClause($_GET);

        $rows = [];
        $res = $this->db->query("
            SELECT
                cf.*,
                COALESCE(NULLIF(TRIM(e.full_name), ''), NULLIF(TRIM(u.full_name), ''), TRIM(u.user_name), '') AS followed_up_by_name
            FROM client_feedbacks cf
            LEFT JOIN users u ON u.id = cf.followed_up_by
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            WHERE {$whereSql}
            ORDER BY cf.id DESC
            LIMIT 500
        ");

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id'                       => (int)$r['id'],
                    'behaviour_attitude'       => $r['behaviour_attitude'],
                    'professionalism_courtesy' => $r['professionalism_courtesy'],
                    'response_time_speed'      => $r['response_time_speed'],
                    'office_environment'       => $r['office_environment'],
                    'overall_experience'       => $r['overall_experience'],
                    'full_name'                => $r['full_name'] ?? '',
                    'mobile_number'            => $r['mobile_number'] ?? '',
                    'comments'                 => $r['comments'] ?? '',
                    'may_contact'              => (int)$r['may_contact'] === 1,
                    'source'                   => $r['source'] ?? 'qr_web',
                    'followup_status'          => $r['followup_status'] ?? 'pending',
                    'followup_note'            => $r['followup_note'] ?? '',
                    'followed_up_by_name'      => $r['followed_up_by_name'] ?? '',
                    'followed_up_at'           => $r['followed_up_at'] ?? null,
                    'created_at'               => $r['created_at'],
                ];
            }
        }

        // Compute KPIs & 5-category breakdown from filtered rows
        $categories = [
            'behaviour_attitude'       => 'Behaviour & Attitude',
            'professionalism_courtesy' => 'Professionalism & Courtesy',
            'response_time_speed'      => 'Response Time / Speed',
            'office_environment'       => 'Office Environment',
            'overall_experience'       => 'Overall Experience',
        ];

        $breakdown = [];
        foreach ($categories as $key => $label) {
            $breakdown[$key] = [
                'key'       => $key,
                'label'     => $label,
                'excellent' => 0,
                'good'      => 0,
                'average'   => 0,
                'poor'      => 0,
                'total'     => 0,
                'avg_score' => 0.0,
            ];
        }

        $total = count($rows);
        $todayStr = date('Y-m-d');
        $todayCount = 0;
        $positiveCount = 0;
        $poorCount = 0;
        $callbackCount = 0;
        $pendingCount = 0;
        $overallScoreSum = 0;

        foreach ($rows as $row) {
            if (substr((string)$row['created_at'], 0, 10) === $todayStr) {
                $todayCount++;
            }
            if (in_array($row['overall_experience'], ['excellent', 'good'], true)) {
                $positiveCount++;
            }
            $hasAnyPoor = false;
            foreach ($categories as $key => $label) {
                $val = $row[$key] ?? '';
                if (isset($breakdown[$key][$val])) {
                    $breakdown[$key][$val]++;
                    $breakdown[$key]['total']++;
                }
                if ($val === 'poor') {
                    $hasAnyPoor = true;
                }
            }
            if ($hasAnyPoor) {
                $poorCount++;
            }
            if ($row['may_contact']) {
                $callbackCount++;
            }
            if ($row['followup_status'] === 'pending') {
                $pendingCount++;
            }
            $overallScoreSum += self::RATING_WEIGHTS[$row['overall_experience']] ?? 0;
        }

        foreach ($breakdown as &$b) {
            if ($b['total'] > 0) {
                $sum = ($b['excellent'] * 4) + ($b['good'] * 3) + ($b['average'] * 2) + ($b['poor'] * 1);
                $b['avg_score'] = round($sum / $b['total'], 2);
            }
        }
        unset($b);

        $kpis = [
            'total'              => $total,
            'today_count'        => $todayCount,
            'positive_count'     => $positiveCount,
            'positive_pct'       => $total > 0 ? round(($positiveCount / $total) * 100, 1) : 0.0,
            'avg_score'          => $total > 0 ? round($overallScoreSum / $total, 2) : 0.0,
            'poor_count'         => $poorCount,
            'callback_requested' => $callbackCount,
            'pending_followup'   => $pendingCount,
        ];

        echo json_encode([
            'success'   => true,
            'kpis'      => $kpis,
            'breakdown' => array_values($breakdown),
            'rows'      => $rows,
        ]);
        exit;
    }

    /**
     * AJAX: Update Management follow-up status & note for a feedback record.
     */
    public function updateStatusAjax()
    {
        $this->requireManagement(true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $status = strtolower(trim((string)($_POST['followup_status'] ?? 'reviewed')));
        $note   = trim((string)($_POST['followup_note'] ?? ''));

        if ($id <= 0 || !in_array($status, self::VALID_STATUSES, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid feedback ID or status.']);
            exit;
        }

        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $noteVal = $note !== '' ? $note : null;

        $stmt = $this->db->prepare("
            UPDATE client_feedbacks
            SET followup_status = ?, followup_note = ?, followed_up_by = ?, followed_up_at = NOW()
            WHERE id = ?
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param('ssii', $status, $noteVal, $userId, $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success'         => (bool)$ok,
            'id'              => $id,
            'followup_status' => $status,
            'followup_note'   => $note,
            'followed_up_at'  => date('Y-m-d H:i:s'),
            'message'         => $ok ? 'Follow-up status updated.' : 'Failed to update status.',
        ]);
        exit;
    }

    /**
     * Export filtered feedback rows as UTF-8 CSV.
     */
    public function exportCsv()
    {
        $this->requireManagement(false);

        $whereSql = $this->buildWhereClause($_GET);
        $res = $this->db->query("
            SELECT
                cf.*,
                COALESCE(NULLIF(TRIM(e.full_name), ''), NULLIF(TRIM(u.full_name), ''), TRIM(u.user_name), '') AS followed_up_by_name
            FROM client_feedbacks cf
            LEFT JOIN users u ON u.id = cf.followed_up_by
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            WHERE {$whereSql}
            ORDER BY cf.id DESC
        ");

        $filename = 'client_feedback_report_' . date('Y_m_d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'ID', 'Submitted At', 'Full Name', 'Mobile Number',
            'Behaviour & Attitude', 'Professionalism & Courtesy',
            'Response Time / Speed', 'Office Environment', 'Overall Experience',
            'May Contact?', 'Comments', 'Follow-up Status', 'Follow-up Note', 'Updated By'
        ]);

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                fputcsv($out, [
                    $r['id'],
                    $r['created_at'],
                    $r['full_name'] ?: 'Anonymous',
                    $r['mobile_number'] ?: '',
                    ucfirst($r['behaviour_attitude']),
                    ucfirst($r['professionalism_courtesy']),
                    ucfirst($r['response_time_speed']),
                    ucfirst($r['office_environment']),
                    ucfirst($r['overall_experience']),
                    ((int)$r['may_contact'] === 1) ? 'Yes' : 'No',
                    $r['comments'] ?? '',
                    ucfirst($r['followup_status'] ?? 'pending'),
                    $r['followup_note'] ?? '',
                    $r['followed_up_by_name'] ?? '',
                ]);
            }
        }
        fclose($out);
        exit;
    }

    /**
     * Public API Endpoint: POST /api/client-feedback/submit
     * Receives client feedback from dpremiumhomes.com/client-feedback (JSON or form-data).
     */
    public function submitApi()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
            exit;
        }

        $input = $_POST;
        $rawBody = file_get_contents('php://input');
        if (empty($input) && !empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $pick = function (array $keys, $default = '') use ($input) {
            foreach ($keys as $k) {
                if (isset($input[$k]) && $input[$k] !== '') {
                    return $input[$k];
                }
            }
            return $default;
        };

        $behaviour       = strtolower(trim((string)$pick(['behaviour_attitude', 'behaviourAttitude'])));
        $professionalism = strtolower(trim((string)$pick(['professionalism_courtesy', 'professionalismCourtesy'])));
        $responseTime    = strtolower(trim((string)$pick(['response_time_speed', 'responseTimeSpeed'])));
        $officeEnv       = strtolower(trim((string)$pick(['office_environment', 'officeEnvironment'])));
        $overall         = strtolower(trim((string)$pick(['overall_experience', 'overallExperience'])));

        $ratingsMap = [
            'behaviour_attitude'       => $behaviour,
            'professionalism_courtesy' => $professionalism,
            'response_time_speed'      => $responseTime,
            'office_environment'       => $officeEnv,
            'overall_experience'       => $overall,
        ];

        foreach ($ratingsMap as $field => $val) {
            if (!in_array($val, self::VALID_RATINGS, true)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => "Invalid or missing rating for '{$field}'. Allowed values: excellent, good, average, poor.",
                ]);
                exit;
            }
        }

        $fullName   = trim((string)$pick(['full_name', 'fullName'], ''));
        $mobile     = trim((string)$pick(['mobile_number', 'mobileNumber', 'phone'], ''));
        $comments   = trim((string)$pick(['comments', 'suggestions', 'comment'], ''));
        $rawContact = $pick(['may_contact', 'mayContact', 'contact_permission'], 0);
        $mayContact = (in_array(strtolower((string)$rawContact), ['1', 'true', 'yes'], true) || $rawContact === true || $rawContact === 1) ? 1 : 0;
        $source     = substr(trim((string)$pick(['source'], 'qr_web')), 0, 50) ?: 'qr_web';
        $ipAddress  = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        $fullNameDb = $fullName !== '' ? mb_substr($fullName, 0, 150) : null;
        $mobileDb   = $mobile !== '' ? mb_substr($mobile, 0, 30) : null;
        $commentsDb = $comments !== '' ? $comments : null;

        $stmt = $this->db->prepare("
            INSERT INTO client_feedbacks (
                behaviour_attitude, professionalism_courtesy, response_time_speed,
                office_environment, overall_experience,
                full_name, mobile_number, comments, may_contact, source, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save feedback.']);
            exit;
        }

        $stmt->bind_param(
            'ssssssssiss',
            $behaviour,
            $professionalism,
            $responseTime,
            $officeEnv,
            $overall,
            $fullNameDb,
            $mobileDb,
            $commentsDb,
            $mayContact,
            $source,
            $ipAddress
        );
        $ok = $stmt->execute();
        $insertId = (int)$stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error saving feedback.']);
            exit;
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id'      => $insertId,
            'message' => 'Thank you! Your feedback has been received.',
        ]);
        exit;
    }

    protected function buildWhereClause(array $params): string
    {
        $where = ['1 = 1'];

        $preset   = trim((string)($params['date_preset'] ?? ''));
        $fromDate = trim((string)($params['from_date'] ?? ''));
        $toDate   = trim((string)($params['to_date'] ?? ''));

        if ($preset === 'today') {
            $where[] = "DATE(cf.created_at) = CURDATE()";
        } elseif ($preset === '7d') {
            $where[] = "cf.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        } elseif ($preset === '30d') {
            $where[] = "cf.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)";
        } elseif ($preset === 'this_month') {
            $where[] = "YEAR(cf.created_at) = YEAR(CURDATE()) AND MONTH(cf.created_at) = MONTH(CURDATE())";
        } else {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
                $f = $this->db->real_escape_string($fromDate);
                $where[] = "DATE(cf.created_at) >= '{$f}'";
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
                $t = $this->db->real_escape_string($toDate);
                $where[] = "DATE(cf.created_at) <= '{$t}'";
            }
        }

        $overall = strtolower(trim((string)($params['overall_rating'] ?? '')));
        if (in_array($overall, self::VALID_RATINGS, true)) {
            $o = $this->db->real_escape_string($overall);
            $where[] = "cf.overall_experience = '{$o}'";
        } elseif ($overall === 'any_poor') {
            $where[] = "(cf.behaviour_attitude = 'poor' OR cf.professionalism_courtesy = 'poor' OR cf.response_time_speed = 'poor' OR cf.office_environment = 'poor' OR cf.overall_experience = 'poor')";
        }

        $mayContact = trim((string)($params['may_contact'] ?? ''));
        if ($mayContact === '1' || $mayContact === '0') {
            $where[] = "cf.may_contact = " . (int)$mayContact;
        }

        if (!empty($params['has_comment']) && $params['has_comment'] === '1') {
            $where[] = "TRIM(COALESCE(cf.comments, '')) != ''";
        }

        $fuStatus = strtolower(trim((string)($params['followup_status'] ?? '')));
        if (in_array($fuStatus, self::VALID_STATUSES, true)) {
            $fs = $this->db->real_escape_string($fuStatus);
            $where[] = "cf.followup_status = '{$fs}'";
        }

        $search = trim((string)($params['search'] ?? ''));
        if ($search !== '') {
            $q = $this->db->real_escape_string($search);
            $where[] = "(COALESCE(cf.full_name, '') LIKE '%{$q}%' OR COALESCE(cf.mobile_number, '') LIKE '%{$q}%' OR COALESCE(cf.comments, '') LIKE '%{$q}%')";
        }

        return implode(' AND ', $where);
    }
}
