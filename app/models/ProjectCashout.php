<?php

class ProjectCashout extends Model
{
    protected $db;
    protected $table = 'cashout_payments';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getProjects()
    {
        $sql = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
        $result = $this->db->query($sql);

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
    }

    public function getCashoutData($filters = [])
    {
        $period_type = $filters['period_type'] ?? 'monthly';
        $year = $filters['year'] ?? date('Y');
        $month = $filters['month'] ?? date('m');
        $project_id = $filters['project_id'] ?? '';
        $start_date = $filters['start_date'] ?? '';
        $end_date = $filters['end_date'] ?? '';

        [$dateFrom, $dateTo, $label] = $this->resolveDateRange($period_type, $year, $month, $start_date, $end_date);

        $data = ($period_type === 'yearly')
            ? $this->getMonthlyData($dateFrom, $dateTo, $project_id)
            : $this->getDailyData($dateFrom, $dateTo, $project_id);
        $overall = $this->getOverallStats($dateFrom, $dateTo, $project_id);
        $payment_details = $this->getPaymentDetails($dateFrom, $dateTo, $project_id);

        return [
            'data' => $data,
            'period_type' => $period_type,
            'overall' => $overall,
            'payment_details' => $payment_details,
            'label' => $label,
            'start_date' => $dateFrom,
            'end_date' => $dateTo,
        ];
    }

    private function resolveDateRange($period_type, $year, $month, $start_date, $end_date)
    {
        switch ($period_type) {
            case 'monthly':
                $from = "{$year}-{$month}-01";
                $to = date('Y-m-t', strtotime($from));
                $label = date('F Y', strtotime($from));
                break;
            case '6months':
                $endTs = strtotime("{$year}-{$month}-01");
                $to = date('Y-m-t', $endTs);
                $from = date('Y-m-01', strtotime('-5 months', $endTs));
                $label = date('M Y', strtotime($from)) . ' – ' . date('M Y', strtotime($to));
                break;
            case 'yearly':
                $from = "{$year}-01-01";
                $to = "{$year}-12-31";
                $label = "Year {$year}";
                break;
            case 'custom':
                $from = $start_date ?: date('Y-m-01');
                $to = $end_date ?: date('Y-m-d');
                $label = date('d M Y', strtotime($from)) . ' – ' . date('d M Y', strtotime($to));
                break;
            default:
                $from = date('Y-m-01');
                $to = date('Y-m-d');
                $label = date('F Y');
        }

        return [$from, $to, $label];
    }

    /**
     * Get daily aggregated cashout data.
     * Fixed to be compatible with ONLY_FULL_GROUP_BY.
     */
    private function getDailyData($dateFrom, $dateTo, $project_id)
    {
        $sql = "SELECT
                grp.date,
                DATE_FORMAT(grp.date, '%d %b %Y')  AS formatted_date,
                DAYNAME(grp.date)                   AS day_name,
                grp.total_amount,
                grp.transaction_count
            FROM (
                SELECT
                    DATE(cp.payment_date)  AS date,
                    SUM(cp.amount)         AS total_amount,
                    COUNT(cp.id)           AS transaction_count
                FROM {$this->table} cp
                WHERE cp.payment_date BETWEEN ? AND ?
                AND cp.project_id IS NOT NULL
    ";

        if ($project_id) {
            $sql .= " AND cp.project_id = ?";
        }

        $sql .= "   GROUP BY DATE(cp.payment_date)
            ) grp
            ORDER BY grp.date ASC";

        $stmt = $this->db->prepare($sql);

        if ($project_id) {
            $stmt->bind_param("ssi", $dateFrom, $dateTo, $project_id);
        } else {
            $stmt->bind_param("ss", $dateFrom, $dateTo);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        $running = 0;
        while ($row = $result->fetch_assoc()) {
            $running += (float) $row['total_amount'];
            $row['running'] = $running;
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    /**
     * Get monthly aggregated cashout data.
     * Fixed to be compatible with ONLY_FULL_GROUP_BY.
     */
    private function getMonthlyData($dateFrom, $dateTo, $project_id)
    {
        $sql = "SELECT
                    YEAR(cp.payment_date)                  AS year,
                    MONTH(cp.payment_date)                 AS month_num,
                    DATE_FORMAT(
                        DATE(CONCAT(
                            YEAR(cp.payment_date), '-',
                            LPAD(MONTH(cp.payment_date), 2, '0'), '-01'
                        )),
                        '%M %Y'
                    )                                        AS month,                                      AS month,
                    SUM(cp.amount)                          AS total_amount,
                    COUNT(cp.id)                            AS transaction_count
                FROM {$this->table} cp
                WHERE cp.payment_date BETWEEN ? AND ?
                AND cp.project_id IS NOT NULL
        ";

        if ($project_id) {
            $sql .= " AND cp.project_id = ?";
        }

        $sql .= " GROUP BY YEAR(cp.payment_date), MONTH(cp.payment_date)
                  ORDER BY YEAR(cp.payment_date) ASC, MONTH(cp.payment_date) ASC";

        $stmt = $this->db->prepare($sql);

        if ($project_id) {
            $stmt->bind_param("ssi", $dateFrom, $dateTo, $project_id);
        } else {
            $stmt->bind_param("ss", $dateFrom, $dateTo);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        $running = 0;
        while ($row = $result->fetch_assoc()) {
            $running += (float) $row['total_amount'];
            $row['running'] = $running;
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    private function getOverallStats($dateFrom, $dateTo, $project_id)
    {
        $sql = "SELECT
                    COALESCE(SUM(cp.amount), 0)  AS total_cashout,
                    COUNT(cp.id)                  AS total_transactions,
                    COALESCE(AVG(cp.amount), 0)   AS avg_payment,
                    COALESCE(MAX(cp.amount), 0)   AS max_payment,
                    COALESCE(MIN(cp.amount), 0)   AS min_payment
                FROM {$this->table} cp
                WHERE cp.payment_date BETWEEN ? AND ?
                AND cp.project_id IS NOT NULL
        ";

        if ($project_id) {
            $sql .= " AND cp.project_id = ?";
        }

        $stmt = $this->db->prepare($sql);

        if ($project_id) {
            $stmt->bind_param("ssi", $dateFrom, $dateTo, $project_id);
        } else {
            $stmt->bind_param("ss", $dateFrom, $dateTo);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        return $stats ?: [];
    }

    public function getPaymentDetails($dateFrom, $dateTo, $project_id)
    {
        $sql = "SELECT
                    DATE(cp.payment_date)  AS payment_date,
                    cp.purpose_paid_to,
                    cp.amount,
                    cp.payment_method,
                    cp.transaction_ref,
                    cp.office,
                    cp.bank_name,
                    cp.bank_account_no,
                    cp.cheque_no,
                    cp.online_type,
                    p.name AS project_name
                FROM {$this->table} cp
                LEFT JOIN projects p ON p.id = cp.project_id
                WHERE cp.payment_date BETWEEN ? AND ?
                AND cp.project_id IS NOT NULL
        ";

        if ($project_id) {
            $sql .= " AND cp.project_id = ?";
        }

        $sql .= " ORDER BY cp.payment_date ASC, cp.id ASC";

        $stmt = $this->db->prepare($sql);

        if ($project_id) {
            $stmt->bind_param("ssi", $dateFrom, $dateTo, $project_id);
        } else {
            $stmt->bind_param("ss", $dateFrom, $dateTo);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $grouped[$row['payment_date']][] = $row;
        }

        $stmt->close();
        return $grouped;
    }
}
