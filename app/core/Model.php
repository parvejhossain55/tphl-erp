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
