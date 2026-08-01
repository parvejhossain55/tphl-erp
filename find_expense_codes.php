<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT id, account_code, account_name, root_type, account_type FROM chart_of_accounts WHERE account_type LIKE '%Expense%' OR root_type LIKE '%Expense%' OR account_code LIKE '5%' OR account_code LIKE '6%'");
while($row = $res->fetch_assoc()) {
    echo "[{$row['account_code']}] {$row['account_name']} ({$row['account_type']})\n";
}
