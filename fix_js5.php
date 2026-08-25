<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$search1 = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    if (!tr.find('.emp-checkbox').is(':checked')) return;
                    
                    var payslipIdStr = tr.attr('data-payslip-id');";
$replace1 = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    var is_excluded = tr.find('.emp-checkbox').is(':checked') ? 0 : 1;
                    
                    var payslipIdStr = tr.attr('data-payslip-id');";
$content = str_replace($search1, $replace1, $content);

$search2 = "                        late_unpaid_absent_days: lateUnpaidAbsentDays,
                        remarks: remarks,
                        attendance_remarks: attRemarks,
                        details: dynamicDetails
                    });";
$replace2 = "                        late_unpaid_absent_days: lateUnpaidAbsentDays,
                        remarks: remarks,
                        attendance_remarks: attRemarks,
                        is_excluded: is_excluded,
                        details: dynamicDetails
                    });";
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Done.";
