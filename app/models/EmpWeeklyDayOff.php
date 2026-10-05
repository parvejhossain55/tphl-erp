<?php

class EmpWeeklyDayOff extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    /**
     * Get an array of days off for a specific employee
     * Returns e.g. ['Saturday', 'Sunday']
     */
    public function getDaysOffByEmployeeId($employee_id)
    {
        $stmt = $this->db->prepare("SELECT day_off_name FROM emp_weekly_day_off WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $days = [];
        while ($row = $result->fetch_assoc()) {
            $days[] = $row['day_off_name'];
        }

        return $days;
    }

    /**
     * Update the days off for a specific employee
     * $days is an array of strings e.g. ['Friday', 'Saturday']
     */
    public function updateEmployeeDaysOff($employee_id, $days)
    {
        // 1. Delete existing days off for this employee
        $stmt = $this->db->prepare("DELETE FROM emp_weekly_day_off WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();

        // 2. Insert new days off
        if (!empty($days)) {
            $insert_query = "INSERT INTO emp_weekly_day_off (employee_id, day_off_name) VALUES (?, ?)";
            $stmt = $this->db->prepare($insert_query);
            
            foreach ($days as $day) {
                // Ensure $day is a safe string (just basic validation)
                $day = trim($day);
                if (!empty($day)) {
                    $stmt->bind_param("is", $employee_id, $day);
                    $stmt->execute();
                }
            }
        }
        
        return true;
    }

    /**
     * Get a list of all employees and their assigned days off for the data table
     */
    public function getEmployeeOffDaysList()
    {
        $sql = "SELECT e.id, e.employee_id, e.full_name, 
                       desg.name AS designation_name,
                       dept.name AS department_name,
                       GROUP_CONCAT(w.day_off_name SEPARATOR ', ') as days_off
                FROM employees e
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN emp_weekly_day_off w ON e.id = w.employee_id
                WHERE e.deleted_at IS NULL
                GROUP BY e.id
                ORDER BY e.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
