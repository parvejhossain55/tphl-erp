<?php
 = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
 = file_get_contents();
preg_match_all('/var payslipData = \[\];(.*?)url: .save-workspace-payslips/s', , );
if (isset([1][0])) {
    echo substr([1][0], 0, 1000);
} else {
    echo "NO MATCH";
}
