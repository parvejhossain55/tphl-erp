<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$res = $conn->query("DESCRIBE approval_requests");
while($row = $res->fetch_assoc()) echo $row['Field'].' : '.$row['Type']."\n";
?>
