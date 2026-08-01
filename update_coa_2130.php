<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$db->query("UPDATE chart_of_accounts SET account_name = 'Employee Advances & Fund Payable' WHERE account_code = '2130'");
echo "COA 2130 updated to 'Employee Advances & Fund Payable'\n";
