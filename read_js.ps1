Get-Content c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php | Select-String -Pattern "payslipData.push" -Context 2,15
