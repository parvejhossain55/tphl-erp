<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

$sqls = [
    "ALTER TABLE con_security_incidents ADD COLUMN resolved_by INT NULL",
    "ALTER TABLE con_security_incidents ADD COLUMN resolved_at DATETIME NULL",
    "ALTER TABLE con_security_daily_logs ADD COLUMN status ENUM('Pending', 'Resolved') DEFAULT 'Pending'",
    "ALTER TABLE con_security_daily_logs ADD COLUMN resolved_by INT NULL",
    "ALTER TABLE con_security_daily_logs ADD COLUMN resolved_at DATETIME NULL"
];

foreach ($sqls as $sql) {
    try {
        $conn->query($sql);
    } catch (Exception $e) {}
}
echo "Done.";
?>
