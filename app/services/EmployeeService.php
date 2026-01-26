<?php
class EmployeeService {

    public static function getTotalEmployees($db) {
        // Run query
        $result = $db->query("SELECT COUNT(*) AS total_employees FROM employees");

        // Check if query succeeded
        if(!$result) {
            return ['total_employees' => 0]; // fallback
        }

        // Fetch associative array
        $row = $result->fetch_assoc(); //mysqli
        return $row;
    }
}
