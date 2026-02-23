<?php

require_once __DIR__ . '/../models/ProjectCashout.php';

class ProjectCashoutController extends Controller
{
    protected $db;
    protected $cashoutModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cashoutModel = new ProjectCashout($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int) $_SESSION['user']['role_id'] : null;

        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    public function index()
    {
        $period_type = $_GET['period_type'] ?? 'monthly';
        $allowed_periods = ['monthly', '6months', 'yearly', 'custom'];
        if (!in_array($period_type, $allowed_periods)) {
            $period_type = 'monthly';
        }

        $year       = isset($_GET['year'])       ? (int) $_GET['year']                   : (int) date('Y');
        $month      = isset($_GET['month'])      ? sprintf('%02d', (int) $_GET['month']) : date('m');
        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id']             : 0;
        $start_date = $_GET['start_date'] ?? '';
        $end_date   = $_GET['end_date']   ?? '';

        if ($period_type === 'custom') {
            if (!$start_date || !strtotime($start_date)) {
                $start_date = date('Y-m-01');
            }
            if (!$end_date || !strtotime($end_date)) {
                $end_date = date('Y-m-d');
            }
            if ($end_date < $start_date) {
                $end_date = $start_date;
            }
        }

        $filters = [
            'period_type' => $period_type,
            'year'        => $year,
            'month'       => $month,
            'project_id'  => $project_id ?: '',
            'start_date'  => $start_date,
            'end_date'    => $end_date,
        ];

        $projects = $this->cashoutModel->getProjects();
        $result   = $this->cashoutModel->getCashoutData($filters);

        $statement_data  = $result['data'];
        $overall_stats   = $result['overall'];
        $payment_details = $result['payment_details'];
        $period_label    = $result['label'];
        $filter_start    = $result['start_date'];
        $filter_end      = $result['end_date'];

        $selected_project_name = 'All Projects';
        if ($project_id) {
            foreach ($projects as $p) {
                if ($p['id'] == $project_id) {
                    $selected_project_name = $p['name'];
                    break;
                }
            }
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCSV($statement_data, $payment_details, $period_label, $result['period_type'], $selected_project_name);
            return;
        }

        if (isset($_GET['print']) && $_GET['print'] === 'true') {
            extract([
                'period_type'           => $period_type,
                'period_label'          => $period_label,
                'selected_project_name' => $selected_project_name,
                'filter_start'          => $filter_start,
                'filter_end'            => $filter_end,
                'statement_data'        => $statement_data,
                'overall_stats'         => $overall_stats,
                'payment_details'       => $payment_details,
            ]);
            require __DIR__ . '/../views/accounts//cashout/projects-cashout-print.php';
            exit;
        }

        $this->view("accounts/cashout/projects-cashout", [
            "current_route"         => "projects-cashout",
            "page_title"            => "Project Cashout Statement",
            "page"                  => "Accounts",
            "period_type"           => $period_type,
            "year"                  => $year,
            "month"                 => $month,
            "project_id"            => $project_id,
            "start_date"            => $start_date,
            "end_date"              => $end_date,
            "projects"              => $projects,
            "statement_data"        => $statement_data,
            "overall_stats"         => $overall_stats,
            "payment_details"       => $payment_details,
            "period_label"          => $period_label,
            "selected_project_name" => $selected_project_name,
            "filter_start"          => $filter_start,
            "filter_end"            => $filter_end,
            "userRole"              => $this->userRole
        ]);
    }

    private function exportCSV($data, $payment_details, $label, $period_type, $project_name)
    {
        $filename = 'projects-cashout-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, ["Project Cashout Statement - {$label}", $project_name]);
        fputcsv($out, []);

        if ($period_type === 'yearly') {
            fputcsv($out, ['Month', 'Total Cashout (BDT)', 'Transactions', 'Running Total (BDT)']);
            foreach ($data as $row) {
                fputcsv($out, [
                    $row['month'],
                    number_format($row['total_amount'], 2, '.', ''),
                    $row['transaction_count'],
                    number_format($row['running'], 2, '.', ''),
                ]);
            }
        } else {
            fputcsv($out, ['Date', 'Day', 'Total Cashout (BDT)', 'Transactions', 'Running Total (BDT)', 'Purpose / Paid To', 'Amount', 'Payment Method', 'Reference']);
            foreach ($data as $row) {
                $date    = $row['date'];
                $details = $payment_details[$date] ?? [];

                if (empty($details)) {
                    fputcsv($out, [
                        $row['formatted_date'],
                        $row['day_name'],
                        number_format($row['total_amount'], 2, '.', ''),
                        $row['transaction_count'],
                        number_format($row['running'], 2, '.', ''),
                        '', '', '', ''
                    ]);
                } else {
                    $first = true;
                    foreach ($details as $d) {
                        fputcsv($out, [
                            $first ? $row['formatted_date']                          : '',
                            $first ? $row['day_name']                                : '',
                            $first ? number_format($row['total_amount'], 2, '.', '') : '',
                            $first ? $row['transaction_count']                       : '',
                            $first ? number_format($row['running'], 2, '.', '')      : '',
                            $d['purpose_paid_to']  ?? '',
                            number_format($d['amount'] ?? 0, 2, '.', ''),
                            $d['payment_method']   ?? 'cash',
                            $d['transaction_ref']  ?? '',
                        ]);
                        $first = false;
                    }
                }
            }
        }

        fclose($out);
        exit;
    }

    private function isAdminUser()
    {
        $adminRoles = [1, 3, 4, 5, 6];
        return in_array($this->userRole, $adminRoles);
    }
}
?>