<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$q = "SELECT poc.*, p.name AS project_name, cb.full_name AS created_by_name, qc.full_name AS qc_name, mb.full_name AS management_name, pb.full_name AS payment_by_name FROM project_other_costs poc LEFT JOIN projects p ON poc.project_id = p.id LEFT JOIN users cb ON poc.created_by_id = cb.id LEFT JOIN users qc ON poc.qc_audit_approved_id = qc.id LEFT JOIN users mb ON poc.management_apporved_by_id = mb.id LEFT JOIN users pb ON poc.payment_by_id = pb.id ORDER BY poc.id DESC";
print_r($c->query($q)->fetch_all(MYSQLI_ASSOC));
