<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$db->query("INSERT INTO tipsoi_email_logs (approver_name, approver_email, pending_level, request_details, status, error_message) VALUES ('Test User', 'test@example.com', '1st Approval', 'Leave Request for Jane on 2026-08-23', 'Success', '')");
echo "Inserted.";
