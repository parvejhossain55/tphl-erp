<?php
$c = new mysqli('127.0.0.1','root','','tphl_db'); 
$c->query('ALTER TABLE project_other_costs ADD COLUMN qc_audit_approved_at DATETIME NULL AFTER qc_audit_note, ADD COLUMN management_apporved_at DATETIME NULL AFTER management_apporved_note'); 
echo "Error: " . $c->error . "\n";
echo "Done\n";
