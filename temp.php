<?php
require_once 'app/config/database.php';
$res = $conn->query('DESCRIBE employees');
while ($row = $res->fetch_assoc())
    echo $row['Field'] . ' | ';
?>