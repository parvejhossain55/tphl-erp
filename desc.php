<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('DESC design_types');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
