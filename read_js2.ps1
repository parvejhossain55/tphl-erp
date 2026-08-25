Get-Content c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php | Select-String -Pattern "tfoot tr\|footData" -Context 3,10
