Get-Content c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php | Select-String -Pattern "function calculateFooterTotals" -Context 0,50
