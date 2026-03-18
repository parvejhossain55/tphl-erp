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
