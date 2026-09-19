<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$db->query("UPDATE project_extra_assets SET mgmt_status = 'pending', mgmt_by = NULL, mgmt_at = NULL, mgmt_note = NULL, qc_audit_status = 'pending', dept_head_status = 'pending', overall_status = 'pending' WHERE id = 26");
$db->query("UPDATE approval_requests SET status = 'pending', approved_amount = NULL, approved_by = NULL, approved_at = NULL WHERE source_table = 'project_extra_assets' AND source_id = 26");
echo "DB Fixed\n";
