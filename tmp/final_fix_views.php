<?php

function fix_file($path, $search, $replace) {
    if (!file_exists($path)) {
        echo "Error: File $path not found\n";
        return;
    }
    $content = file_get_contents($path);
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($path, $content);
        echo "Success: Updated $path\n";
    } else {
        echo "Error: Search string not found in $path\n";
        // Let's try to be even more robust for edit-employee.php
        if (basename($path) == 'edit-employee.php') {
            // Try to find the Basic Salary label and add gross salary before its parent div
            $pattern = '/<div class="col-lg-6">\s*<label>Basic Salary <span>\*<\/span><\/label>/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '<div class="col-lg-6"><label>Gross Salary <span>*</span></label><input class="form-control" name="gross_salary" id="gross_salary" type="number" value="<?= $employee[\'gross_salary\'] ?>"></div><div class="col-lg-6"><label>Basic Salary <span>*</span></label>', $content);
                file_put_contents($path, $content);
                echo "Success: Updated $path using regex\n";
            } else {
                echo "Error: Regex also failed for $path\n";
            }
        }
    }
}

// 1. Fix create-payslip.php
$payslip_path = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\create-payslip.php';

// Fix data-basic-salary to include gross-salary
$payslip_search1 = 'data-basic-salary="<?= $emp[\'basic_salary\'] ?>">';
$payslip_replace1 = 'data-basic-salary="<?= $emp[\'basic_salary\'] ?>" data-gross-salary="<?= $emp[\'gross_salary\'] ?>">';
fix_file($payslip_path, $payslip_search1, $payslip_replace1);

// Fix the display field (typo + layout)
$payslip_search2 = '<div class="col-12 mb-2">
                                           <label class="form-label">Groass Salary</label>
                                           <input id="grossSalary" type="number" class="form-control earning" value="0" readonly>
                                       </div>';
$payslip_replace2 = '<div class="col-12 mb-2 text-center">
                                           <label class="form-label" style="font-weight: bold; color: #333;">Gross Salary</label>
                                           <input id="grossSalary" type="number" class="form-control text-center" value="0" readonly 
                                                  style="font-weight: 700; background-color: #f8f9fa; border: 2px solid #dee2e6;">
                                       </div>';
fix_file($payslip_path, $payslip_search2, $payslip_replace2);


// 2. Fix edit-employee.php
$edit_path = 'c:\xampp\htdocs\TPHL-ERP\app\views\hrm\employees\edit-employee.php';
// This one has been tricky, I'll use a very specific string from the last view_file output
$edit_search = '                  <div class="col-lg-6">
                      <label>Basic Salary <span>*</span></label>
                      <input class="form-control" name="basic_salary" id="basic_salary" type="number"
                          value="<?= $employee[\'basic_salary\'] ?>">
                  </div>';
$edit_replace = '                  <!-- Gross Salary -->
                  <div class="col-lg-6">
                      <label>Gross Salary <span>*</span></label>
                      <input class="form-control" name="gross_salary" id="gross_salary" type="number"
                          value="<?= $employee[\'gross_salary\'] ?>">
                  </div>
                  <!-- Basic Salary -->
                  <div class="col-lg-6">
                      <label>Basic Salary <span>*</span></label>
                      <input class="form-control" name="basic_salary" id="basic_salary" type="number"
                          value="<?= $employee[\'basic_salary\'] ?>">
                  </div>';
fix_file($edit_path, $edit_search, $edit_replace);

?>
