<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$tables = ['fund_requisitions', 'project_other_costs', 'con_labor_requisitions', 'vendor_payment_requests', 'project_extra_assets', 'cashout_requests', 'expense_adjustments', 'land_fund_requisitions'];
foreach($tables as $t) { 
    echo $t.': '; 
    $res=$db->query('SHOW COLUMNS FROM '.$t); 
    while($r=$res->fetch_assoc()){ 
        if(strpos(strtolower($r['Field']), 'account') !== false || strpos(strtolower($r['Field']), 'paid') !== false || strpos(strtolower($r['Field']), 'payment') !== false) 
            echo $r['Field'].' '; 
    } 
    echo "\n"; 
}
