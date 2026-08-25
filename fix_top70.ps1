$file = "c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php"
$content = Get-Content $file -Raw
$content = $content -replace "top: 60px; /\* Offset for top navbar \*/", "top: 70px; /* Offset for top navbar */"
Set-Content $file $content
echo "Sticky top offset adjusted to 70px"
