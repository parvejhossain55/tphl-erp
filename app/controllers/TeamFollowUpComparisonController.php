<?php

/**
 * TeamFollowUpComparisonController
 * -------------------------------------------------------------------------
 * Handles "Team Follow Up Comparison" (1st to 5th follow-up performance rates,
 * on-time vs overdue vs completed late, team comparison, executive drilldown).
 * 
 * Accessible by: Management, Sales Manager, Sales Head, Chief Executive roles.
 */
class TeamFollowUpComparisonController extends Controller
{
    protected $db;
    protected TeamFollowUpComparisonService $service;

    public const ALLOWED_ROLE_IDS = [1, 3, 5, 9, 33, 34, 35, 38];

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new TeamFollowUpComparisonService($this->db);
    }

    public static function canAccess(): bool
    {
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return in_array($roleId, self::ALLOWED_ROLE_IDS, true)
            || (function_exists('isSalesManager') && isSalesManager())
            || str_contains($roleName, 'chief')
            || str_contains($roleName, 'ceo')
            || str_contains($roleName, 'management')
            || str_contains($roleName, 'sales manager')
            || str_contains($roleName, 'sales team leader')
            || str_contains($roleName, 'sales head')
            || str_contains($roleName, 'head of sales')
            || str_contains($roleName, 'admin');
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
        die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h3>Access denied</h3><p>Team Follow Up Comparison is available to Management, Sales Manager, Sales Head, and Chief Executive roles only.</p></div>');
    }

    protected function readFilters(): array
    {
        $viewMode = $_GET['view'] ?? 'teams';
        if (!in_array($viewMode, ['teams', 'individuals'], true)) {
            $viewMode = 'teams';
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

        $teamId = !empty($_GET['team_id']) ? (int) $_GET['team_id'] : null;
        $scope = function_exists('getSalesFilterScope') ? getSalesFilterScope($this->db) : ['is_global' => true];
        $salesManagerId = (!empty($scope['is_global']) && !empty($_GET['sales_manager_id'])) ? (int) $_GET['sales_manager_id'] : null;
        $projectId = !empty($_GET['project_id']) ? (int) $_GET['project_id'] : null;
        $executiveId = !empty($_GET['executive_id']) ? (int) $_GET['executive_id'] : null;

        return [
            'view' => $viewMode,
            'preset' => $preset,
            'cycle' => $cycle,
            'year' => $year,
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'sales_manager_id' => $salesManagerId,
            'team_id' => $teamId,
            'project_id' => $projectId,
            'executive_id' => $executiveId,
            'search' => trim((string) ($_GET['search'] ?? '')),
            'sort' => $_GET['sort'] ?? 'lowest',
        ];
    }

    public function index()
    {
        $this->guard();
        $filters = $this->readFilters();

        $period = $this->service->resolvePeriod(
            $filters['cycle'] ?: null,
            $filters['year'],
            $filters['month'],
            $filters['from'],
            $filters['to'],
            $filters['preset']
        );

        if (!empty($period['preset'])) {
            $filters['preset'] = $period['preset'];
            $filters['from'] = $period['start_date'];
            $filters['to'] = $period['end_date'];
        }

        $comparisonData = $this->service->build($period, $filters);

        // Scope dropdowns for Sales Team Leader (5) & Sales Manager
        [$scopedTeams, $scopedProjects, $scopedUsers, $scope] = filterSalesDropdowns(
            $this->db,
            $this->service->getTeams(),
            $this->service->getProjects(),
            $this->service->getSalesUsers()
        );

        // CSV export
        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($period, $filters, $comparisonData);
            return;
        }

        return $this->view('sales-performance/team-followup-comparison', [
            'current_route' => 'team-followup-comparison',
            'current_page' => 'team-followup-comparison',
            'page_title' => 'Team Follow Up Comparison',
            'period' => $period,
            'filters' => $filters,
            'data' => $comparisonData,
            'cycleOptions' => $this->service->cycleOptions(6),
            'teams' => $scopedTeams,
            'salesUsers' => $scopedUsers,
            'projects' => $scopedProjects,
            'isGlobalViewer' => !empty($scope['is_global']),
            'salesManagers' => !empty($scope['is_global']) ? getSalesManagersWithTeams($this->db) : [],
        ]);
    }

    /**
     * AJAX endpoint for lead drilldown modal ("See leads").
     */
    public function ajaxLeads()
    {
        $this->guard(true);
        $filters = $this->readFilters();

        $period = $this->service->resolvePeriod(
            $filters['cycle'] ?: null,
            $filters['year'],
            $filters['month'],
            $filters['from'],
            $filters['to'],
            $filters['preset']
        );

        $drillFilters = [
            'sales_manager_id' => $filters['sales_manager_id'],
            'team_id' => !empty($_GET['team_id']) ? (int)$_GET['team_id'] : null,
            'executive_id' => !empty($_GET['executive_id']) ? (int)$_GET['executive_id'] : null,
            'project_id' => !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null,
            'step' => !empty($_GET['step']) ? (int)$_GET['step'] : null,
            'status' => !empty($_GET['status']) ? trim($_GET['status']) : null,
        ];

        $result = $this->service->getDrilldownLeads($period, $drillFilters, 250);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Export comparison as CSV.
     */
    protected function exportCsv(array $period, array $filters, array $data): void
    {
        $filename = 'team-followup-comparison-' . $period['start_date'] . '_to_' . $period['end_date'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Team Follow Up Comparison', $period['label']]);
        fputcsv($out, []);

        if ($filters['view'] === 'individuals') {
            fputcsv($out, ['Executive', 'Role', 'Team', 'Total Leads', '1st Rate %', '1st On-Time/Due', '2nd Rate %', '2nd On-Time/Due', '3rd Rate %', '3rd On-Time/Due', '4th Rate %', '4th On-Time/Due', '5th Rate %', '5th On-Time/Due', 'Overall Rate %', 'Overall On-Time/Due', 'Open Overdue', 'Done Late']);
            foreach ($data['executives'] as $e) {
                $m = $e['metrics'];
                fputcsv($out, [
                    $e['name'],
                    $e['role_name'],
                    $e['team_name'],
                    $e['total_leads'],
                    $m['steps'][1]['rate'] !== null ? $m['steps'][1]['rate'] . '%' : 'N/A',
                    $m['steps'][1]['ontime'] . ' / ' . $m['steps'][1]['due'],
                    $m['steps'][2]['rate'] !== null ? $m['steps'][2]['rate'] . '%' : 'N/A',
                    $m['steps'][2]['ontime'] . ' / ' . $m['steps'][2]['due'],
                    $m['steps'][3]['rate'] !== null ? $m['steps'][3]['rate'] . '%' : 'N/A',
                    $m['steps'][3]['ontime'] . ' / ' . $m['steps'][3]['due'],
                    $m['steps'][4]['rate'] !== null ? $m['steps'][4]['rate'] . '%' : 'N/A',
                    $m['steps'][4]['ontime'] . ' / ' . $m['steps'][4]['due'],
                    $m['steps'][5]['rate'] !== null ? $m['steps'][5]['rate'] . '%' : 'N/A',
                    $m['steps'][5]['ontime'] . ' / ' . $m['steps'][5]['due'],
                    $m['overall']['rate'] !== null ? $m['overall']['rate'] . '%' : 'N/A',
                    $m['overall']['ontime'] . ' / ' . $m['overall']['due'],
                    $m['overall']['overdue'],
                    $m['overall']['late'],
                ]);
            }
        } else {
            fputcsv($out, ['Team', 'Executives', 'Total Leads', '1st Rate %', '1st On-Time/Due', '2nd Rate %', '2nd On-Time/Due', '3rd Rate %', '3rd On-Time/Due', '4th Rate %', '4th On-Time/Due', '5th Rate %', '5th On-Time/Due', 'Overall Rate %', 'Overall On-Time/Due', 'Open Overdue', 'Done Late']);
            foreach ($data['teams'] as $t) {
                $m = $t['metrics'];
                fputcsv($out, [
                    $t['name'],
                    $t['exec_count'],
                    $t['total_leads'],
                    $m['steps'][1]['rate'] !== null ? $m['steps'][1]['rate'] . '%' : 'N/A',
                    $m['steps'][1]['ontime'] . ' / ' . $m['steps'][1]['due'],
                    $m['steps'][2]['rate'] !== null ? $m['steps'][2]['rate'] . '%' : 'N/A',
                    $m['steps'][2]['ontime'] . ' / ' . $m['steps'][2]['due'],
                    $m['steps'][3]['rate'] !== null ? $m['steps'][3]['rate'] . '%' : 'N/A',
                    $m['steps'][3]['ontime'] . ' / ' . $m['steps'][3]['due'],
                    $m['steps'][4]['rate'] !== null ? $m['steps'][4]['rate'] . '%' : 'N/A',
                    $m['steps'][4]['ontime'] . ' / ' . $m['steps'][4]['due'],
                    $m['steps'][5]['rate'] !== null ? $m['steps'][5]['rate'] . '%' : 'N/A',
                    $m['steps'][5]['ontime'] . ' / ' . $m['steps'][5]['due'],
                    $m['overall']['rate'] !== null ? $m['overall']['rate'] . '%' : 'N/A',
                    $m['overall']['ontime'] . ' / ' . $m['overall']['due'],
                    $m['overall']['overdue'],
                    $m['overall']['late'],
                ]);
            }
        }

        fclose($out);
        exit;
    }
}
