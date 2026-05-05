<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db'); 
$result = $db->query("SHOW FULL COLUMNS FROM work_types"); 
while($row = $result->fetch_assoc()) { 
    echo $row['Field'].' - '.$row['Collation']."\n"; 
}
