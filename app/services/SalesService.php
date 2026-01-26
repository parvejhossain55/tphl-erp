<?php
class SalesService {

    public static function getOverview($db) {
        $stmt = $db->query("SELECT COUNT(*) AS total_sales, SUM(agreement_value) AS revenue FROM lead_bookings WHERE booking_status='booked'");
            
        // Fetch associative array
        $row = $stmt->fetch_assoc(); // ✅ mysqli
        return $row;
    }
}
