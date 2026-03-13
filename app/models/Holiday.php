<?php
class Holiday
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getHoliday()
    {
        $stmt = $this->db->prepare("SELECT * holidays ");
    }

    public function holidayCount($year = null, $month = null)
    {
        // If no year provided, use current year
        if (!$year) $year = date('Y');

        // Base query
        $sql = "SELECT COUNT(*) as total FROM holidays WHERE YEAR(holiday_date) = ?";
        $params = [$year];
        $types = "i";

        // If month is provided, count only holidays in that month
        if ($month) {
            $sql .= " AND MONTH(holiday_date) = ?";
            $params[] = $month;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total'];
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO holidays 
            ( title, holiday_date)
            VALUES (?,  ?)
        ");
        $stmt->bind_param(
            "ss"
        );
        return $stmt->execute([

            $data['title'],
            $data['holiday_date']
        ]);
    }
}
