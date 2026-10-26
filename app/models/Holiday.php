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
        $sql = "SELECT h.*, 
                       d.name AS department_name, 
                       e.full_name AS employee_name, 
                       e.employee_id AS employee_code 
                FROM holidays h
                LEFT JOIN departments d ON h.department_id = d.id
                LEFT JOIN employees e ON h.employee_id = e.id
                ORDER BY h.start_date ASC, h.id ASC";
        $stmt = $this->db->prepare($sql);
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
        $appliesTo = in_array($data['applies_to'] ?? '', ['all', 'department', 'employee']) ? $data['applies_to'] : 'all';
        $departmentId = ($appliesTo === 'department' && !empty($data['department_id'])) ? (int)$data['department_id'] : null;
        $employeeId = ($appliesTo === 'employee' && !empty($data['employee_id'])) ? (int)$data['employee_id'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO holidays 
            (title, start_date, end_date, applies_to, department_id, employee_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssii",
            $data['title'],
            $data['start_date'],
            $data['end_date'],
            $appliesTo,
            $departmentId,
            $employeeId
        );

        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $appliesTo = in_array($data['applies_to'] ?? '', ['all', 'department', 'employee']) ? $data['applies_to'] : 'all';
        $departmentId = ($appliesTo === 'department' && !empty($data['department_id'])) ? (int)$data['department_id'] : null;
        $employeeId = ($appliesTo === 'employee' && !empty($data['employee_id'])) ? (int)$data['employee_id'] : null;

        $stmt = $this->db->prepare("
            UPDATE holidays 
            SET title = ?, start_date = ?, end_date = ?, applies_to = ?, department_id = ?, employee_id = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssiii",
            $data['title'],
            $data['start_date'],
            $data['end_date'],
            $appliesTo,
            $departmentId,
            $employeeId,
            $id
        );

        return $stmt->execute();
    }

    /**
     * Get active holidays in date range structured for employee attendance evaluation
     * Returns:
     * - 'global': ['YYYY-MM-DD' => title]
     * - 'department': [dept_id => ['YYYY-MM-DD' => title]]
     * - 'employee': [emp_id => ['YYYY-MM-DD' => title]]
     */
    public function getHolidayMapForRange($startDate, $endDate)
    {
        $sql = "SELECT id, title, start_date, end_date, applies_to, department_id, employee_id
                FROM holidays
                WHERE start_date <= ? AND end_date >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $endDate, $startDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $map = [
            'global' => [],
            'department' => [],
            'employee' => []
        ];

        foreach ($rows as $row) {
            $hStart = max(strtotime($row['start_date']), strtotime($startDate));
            $hEnd   = min(strtotime($row['end_date']), strtotime($endDate));
            $type   = $row['applies_to'] ?? 'all';
            $title  = $row['title'];

            for ($d = $hStart; $d <= $hEnd; $d += 86400) {
                $dateStr = date('Y-m-d', $d);
                if ($type === 'department' && !empty($row['department_id'])) {
                    $deptId = (int)$row['department_id'];
                    $map['department'][$deptId][$dateStr] = $title;
                } elseif ($type === 'employee' && !empty($row['employee_id'])) {
                    $empId = (int)$row['employee_id'];
                    $map['employee'][$empId][$dateStr] = $title;
                } else {
                    $map['global'][$dateStr] = $title;
                }
            }
        }

        return $map;
    }

    public function delete($id)
    {
        $stmt1 = $this->db->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        return true;
    }
}
