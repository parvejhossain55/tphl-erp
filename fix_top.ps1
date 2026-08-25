$file = "c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php"
$content = Get-Content $file -Raw
$content = $content -replace "top: 0;", "top: 60px; /* Offset for top navbar */"
Set-Content $file $content
echo "Sticky top offset set to 60px"
