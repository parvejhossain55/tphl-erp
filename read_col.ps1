Get-Content c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php | Select-String -Pattern "<th>" -Context 0,0 | Measure-Object | Select-Object -ExpandProperty Count
