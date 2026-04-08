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
        $stmt = $this->db->prepare("SELECT * FROM holidays ORDER BY start_date ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function holidayCount($year = null, $month = null)
    {
        if (!$year) $year = date('Y');

        // Month ভিত্তিক বা Year ভিত্তিক range
        if ($month) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end   = date("Y-m-t", strtotime($start));
        } else {
            $start = "$year-01-01";
            $end   = "$year-12-31";
        }

        // সব overlapping holiday আনো
        $sql = "SELECT start_date, end_date 
            FROM holidays 
            WHERE start_date <= ? 
            AND end_date >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $end, $start);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        //  Unique days count করার জন্য
        $dates = [];

        foreach ($result as $row) {

            $hStart = max(strtotime($row['start_date']), strtotime($start));
            $hEnd   = min(strtotime($row['end_date']), strtotime($end));

            for ($d = $hStart; $d <= $hEnd; $d += 86400) {
                $dates[date('Y-m-d', $d)] = true;
            }
        }

        return count($dates);
    }

    public function holidayCountCurrentMonth($year = null, $month = null)
    {
        if (!$year) $year = date('Y');

        if ($month) {
            $start = "$year-$month-01";
            $end   = date("Y-m-t", strtotime($start));
        } else {
            $start = "$year-01-01";
            $end   = "$year-12-31";
        }

        $sql = "SELECT COUNT(*) as total 
            FROM holidays 
            WHERE start_date <= ? 
            AND end_date >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $end, $start);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total'];
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO holidays 
        (title, start_date, end_date)
        VALUES (?, ?, ?)
    ");

        $stmt->bind_param(
            "sss",
            $data['title'],
            $data['start_date'],
            $data['end_date']
        );

        return $stmt->execute();
    }
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
        UPDATE holidays 
        SET title = ?, start_date = ?, end_date = ?
        WHERE id = ?
    ");

        $stmt->bind_param(
            "sssi",
            $data['title'],
            $data['start_date'],
            $data['end_date'],
            $id
        );

        return $stmt->execute();
    }

    public function delete($id)
    {
        // 1 Delete all designations linked to this department
        $stmt1 = $this->db->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        return true;
    }
}
