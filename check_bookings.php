<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT lb.id, p.name as project_name, f.flat_name, lb.payment_status FROM lead_bookings lb JOIN projects p ON lb.project_id = p.id JOIN flats f ON lb.flat_id = f.id WHERE lb.project_id = 42");
if($res && $res->num_rows > 0) { 
    while($row = $res->fetch_assoc()) { 
        print_r($row); 
    } 
} else { 
    echo 'No bookings found for project 42.'; 
}
