<?php

/**
 * Sales Performance dashboard for Management & Chief Executive roles.
 * Tabs: Overview | Team performance | Executive performance
 * All numbers come from SalesPerformanceService so the three tabs reconcile.
 */
class SalesPerformanceController extends Controller
{
    protected $db;
    protected SalesPerformanceService $service;

    /** Management (Admin, Super Admin, Chief Officer, Management Viewer, SEAL CEO) */
    public const ALLOWED_ROLE_IDS = [1, 3, 33, 34, 35];

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new SalesPerformanceService($this->db);
    }

    public static function canAccess(): bool
    {
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return in_array($roleId, self::ALLOWED_ROLE_IDS, true)
            || str_contains($roleName, 'chief')
            || str_contains($roleName, 'ceo')
            || str_contains($roleName, 'management');
    }

    protected function guard(bool $json = false): void
    {
        if (self::canAccess()) {
            return;
        }
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h3>Access denied</h3><p>Sales Performance is available to Management and Chief Executive roles only.</p></div>');
    }

    protected function readFilters(): array
    {
        $tab = $_GET['tab'] ?? 'overview';
        if (!in_array($tab, ['overview', 'team', 'executive', 'inactive-executive'], true)) {
            $tab = 'overview';
        }
        $preset = trim((string) ($_GET['preset'] ?? ''));
        $cycle = $_GET['cycle'] ?? '';
        if (str_starts_with($cycle, 'preset:')) {
            $preset = substr($cycle, 7);
        }
        $year = $month = null;
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $cycle, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        }
        $from = !empty($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : null;
        $to = !empty($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']) ? $_GET['to'] : null;

        return [
            'tab' => $tab,
            'preset' => $preset,
            'cycle' => $cycle,
            'year' => $year,
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'project_id' => !empty($_GET['project_id']) ? (int) $_GET['project_id'] : null,
            'team_id' => !empty($_GET['team_id']) ? (int) $_GET['team_id'] : null,
            'executive_id' => !empty($_GET['executive_id']) ? (int) $_GET['executive_id'] : null,
            'focus_team' => $_GET['focus_team'] ?? null,
        ];
    }

    public function index()
    {
        $this->guard();
        $f = $this->readFilters();

        $period = $this->service->resolvePeriod($f['cycle'] ?: null, $f['year'], $f['month'], $f['from'], $f['to'], $f['preset']);
        if (!empty($period['preset'])) {
            $f['preset'] = $period['preset'];
            $f['from'] = $period['start_date'];
            $f['to'] = $period['end_date'];
        }
        $data = $this->service->build($period, $f);

        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($f['tab'], $period, $data);
            return;
        }

        $quality = $f['tab'] === 'overview' ? $this->service->dataQuality() : null;
        $data['current_route'] = 'sales-performance';

        return $this->view('sales-performance/index', [
            'current_route' => 'sales-performance',
            'current_page' => 'sales-performance',
            'page_title' => 'Sales Performance',
            'tab' => $f['tab'],
            'filters' => $f,
            'period' => $period,
            'data' => $data,
            'quality' => $quality,
            'cycleOptions' => $this->service->cycleOptions(12),
            'teams' => $this->service->getTeams(),
            'salesUsers' => $this->service->getSalesUsers(),
            'projects' => $this->service->getProjects(),
        ]);
    }

    /** JSON drill-down for KPI cards, sales funnel stages, queues, and data health checks. */
    public function gapDetails()
    {
        $this->guard(true);
        $key = preg_replace('/[^a-z0-9_]/', '', $_GET['key'] ?? '');
        $f = $this->readFilters();
        if (!empty($_GET['team_key'])) {
            $f['team_key'] = $_GET['team_key'];
        }
        $period = $this->service->resolvePeriod($f['cycle'] ?: null, $f['year'], $f['month'], $f['from'], $f['to'], $f['preset']);
        if (!empty($period['preset'])) {
            $f['preset'] = $period['preset'];
            $f['from'] = $period['start_date'];
            $f['to'] = $period['end_date'];
        }

        if (($_GET['export'] ?? '') === 'csv') {
            $drill = $this->service->drillDown($key, $period, $f, 5000);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="drilldown-' . $key . '-' . date('Ymd-His') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Lead / Record', 'Phone', 'Executive', 'Team', 'Detail', 'Date', 'Status']);
            foreach ($drill['rows'] as $r) {
                fputcsv($out, [
                    $r['lead_name'] ?? '',
                    $r['phone'] ?? '',
                    $r['owner_name'] ?? '',
                    $r['team_name'] ?? '',
                    $r['info'] ?? '',
                    substr($r['event_date'] ?? '', 0, 10),
                    $r['status_badge'] ?? ''
                ]);
            }
            fclose($out);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->service->drillDown($key, $period, $f), JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function exportCsv(string $tab, array $period, array $data): void
    {
        $file = 'sales-performance-' . $tab . '-' . $period['start_date'] . '_to_' . $period['end_date'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Sales Performance', $period['label']]);
        fputcsv($out, []);

        $cols = [
            'Leads received' => 'stage_1', 'Contacted' => 'stage_2', 'Contact rate %' => 'contact_rate',
            'No response' => 'no_response', 'No response %' => 'no_response_rate',
            'Visit scheduled' => 'stage_3', 'Visited' => 'stage_4', 'Booked' => 'stage_5',
            'Conversion rate %' => 'conversion_rate',
            'Follow-ups logged' => 'follow_ups', 'Visits done (period)' => 'visits_done',
            'Bookings (period)' => 'bookings', 'Booking value' => 'booking_value',
            'Confirmed sales (period)' => 'confirmed_sales', 'Collection' => 'collection', 'Onboarded' => 'onboarded',
            'Overdue follow-ups (today)' => 'overdue', 'Booking target' => 'target_bookings', 'Target achievement %' => 'target_achievement',
        ];

        if ($tab === 'executive' || $tab === 'inactive-executive') {
            $list = $tab === 'inactive-executive' ? ($data['inactive_executives'] ?? []) : ($data['executives'] ?? []);
            fputcsv($out, array_merge(['Executive', 'Role', 'Team', 'Status'], array_keys($cols)));
            foreach ($list as $e) {
                $row = [$e['name'], $e['role'], $e['team_name'], $e['active'] ? 'Active' : ucfirst($e['employee_status'] ?? 'Inactive')];
                foreach ($cols as $k) {
                    $row[] = $e[$k] ?? '';
                }
                fputcsv($out, $row);
            }
        } else {
            fputcsv($out, array_merge(['Team', 'Active members', 'Leaders'], array_keys($cols)));
            foreach ($data['teams'] as $t) {
                $row = [$t['name'], $t['member_count'], implode(', ', $t['leaders'])];
                foreach ($cols as $k) {
                    $row[] = $t[$k];
                }
                fputcsv($out, $row);
            }
            $row = ['TOTAL', '', ''];
            foreach ($cols as $k) {
                $row[] = $data['overall'][$k];
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
