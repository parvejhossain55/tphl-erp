<?php

function robust_replace($path, $pattern, $replacement) {
    if (!file_exists($path)) {
        echo "Error: File $path not found\n";
        return false;
    }
    $content = file_get_contents($path);
    $new_content = preg_replace($pattern, $replacement, $content);
    if ($new_content !== null && $new_content !== $content) {
        file_put_contents($path, $new_content);
        echo "Success: Updated " . basename($path) . "\n";
        return true;
    } else {
        echo "Error: Pattern not found or no change in " . basename($path) . "\n";
        return false;
    }
}

// 1. Fix create-payslip.php
$payslipFile = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\create-payslip.php';

// Add data-gross-salary to employee selection dropdown
$p1 = '/data-basic-salary="\s*<?= \$emp\[\'basic_salary\'\] ?>\s*">/';
$r1 = 'data-basic-salary="<?= $emp[\'basic_salary\'] ?>" data-gross-salary="<?= $emp[\'gross_salary\'] ?>">';
robust_replace($payslipFile, $p1, $r1);

// Add Gross Salary display above Basic Salary
$p2 = '/<div class="col-12 mb-2">\s*<label class="form-label">Basic Salary<\/label>\s*<input id="basicSalary" type="number" class="form-control earning" value="0" readonly>\s*<\/div>/';
$r2 = '<div class="col-12 mb-2 text-center">
                                           <label class="form-label" style="font-weight: bold; color: #333;">Gross Salary</label>
                                           <input id="grossSalary" type="number" class="form-control text-center" value="0" readonly 
                                                  style="font-weight: 700; background-color: #f8f9fa; border: 2px solid #dee2e6;">
                                       </div>
                                       <div class="col-12 mb-2">
                                           <label class="form-label">Basic Salary</label>
                                           <input id="basicSalary" type="number" class="form-control earning" value="0" readonly>
                                       </div>';
robust_replace($payslipFile, $p2, $r2);

// Fix the case where basic_salary might not be pulling correctly (ensure the ID matches)
// JavaScript should already be fine, but let's double check the selector

// 2. Fix edit-employee.php
$editFile = 'c:\xampp\htdocs\TPHL-ERP\app\views\hrm\employees\edit-employee.php';

// Add Gross Salary before Basic Salary in edit form
$p3 = '/<div class="col-lg-6">\s*<label>Basic Salary <span>\*<\/span><\/label>\s*<input class="form-control" name="basic_salary" id="basic_salary" type="number"\s*value="<?= \$employee\[\'basic_salary\'\] ?>">\s*<\/div>/i';
$r3 = '<div class="col-lg-6">
                      <label>Gross Salary <span>*</span></label>
                      <input class="form-control" name="gross_salary" id="gross_salary" type="number"
                          value="<?= $employee[\'gross_salary\'] ?>">
                  </div>
                  <div class="col-lg-6">
                      <label>Basic Salary <span>*</span></label>
                      <input class="form-control" name="basic_salary" id="basic_salary" type="number"
                          value="<?= $employee[\'basic_salary\'] ?>">
                  </div>';
robust_replace($editFile, $p3, $r3);

?>
