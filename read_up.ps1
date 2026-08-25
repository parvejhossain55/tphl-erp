Get-Content c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php | Select-String -Pattern "UPDATE payslips SET" -Context 3,3
