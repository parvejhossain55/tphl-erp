<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$db->query("DELETE t1 FROM banks t1 INNER JOIN banks t2 WHERE t1.id > t2.id AND t1.bank_name = t2.bank_name AND t1.branch_name = t2.branch_name");
echo $db->affected_rows . ' rows deleted.';
