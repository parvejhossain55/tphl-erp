<?php 
class Model 
{
    protected $db;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $config = require __DIR__ . "/../config/config.php";
            $database = new Database($config);
            $this->db = $database->conn;
        }
    }

    public function getDb()
    {
        return $this->db;
    }
    public function getCycleDateRange($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        
        // Special historical transition rules for 2026:
        // May 2026: 1 May - 31 May
        // June 2026: 1 June - 30 June
        // July 2026: 1 July - 25 July
        // August 2026 onward: 26th of previous month to 25th of month (e.g. 26 July - 25 August)
        if ($year === 2026) {
            if ($month === 5) {
                return ['start' => '2026-05-01 00:00:00', 'end' => '2026-05-31 23:59:59'];
            }
            if ($month === 6) {
                return ['start' => '2026-06-01 00:00:00', 'end' => '2026-06-30 23:59:59'];
            }
            if ($month === 7) {
                return ['start' => '2026-07-01 00:00:00', 'end' => '2026-07-25 23:59:59'];
            }
        }
        
        $prevMonth = $month - 1;
        $prevYear = $year;
        
        if ($prevMonth == 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $startDate = sprintf('%04d-%02d-26 00:00:00', $prevYear, $prevMonth);
        $endDate   = sprintf('%04d-%02d-25 23:59:59', $year, $month);

        return ['start' => $startDate, 'end' => $endDate];
    }
    
    public function getCycleForDate($date)
    {
        $ts = strtotime($date);
        $day = (int)date('d', $ts);
        $month = (int)date('m', $ts);
        $year = (int)date('Y', $ts);
        
        // Special historical transition rules for 2026:
        if ($year === 2026) {
            if ($month === 5) {
                return '2026-05'; // 1-31 May
            }
            if ($month === 6) {
                return '2026-06'; // 1-30 June
            }
            if ($month === 7 && $day <= 25) {
                return '2026-07'; // 1-25 July
            }
            if ($month === 7 && $day >= 26) {
                return '2026-08'; // 26-31 July starts August cycle
            }
        }
        
        if ($day >= 26) {
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }
        
        return sprintf('%04d-%02d', $year, $month);
    }
}
?>
