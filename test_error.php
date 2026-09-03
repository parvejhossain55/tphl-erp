<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$q = "SELECT poc.*, p.name AS project_name, cb.name AS created_by_name FROM project_other_costs poc LEFT JOIN projects p ON poc.project_id = p.id LEFT JOIN users cb ON poc.created_by_id = cb.id WHERE poc.created_by_id = 1 ORDER BY poc.id DESC";
$r = $c->query($q);
if (!$r) {
    echo "ERROR: " . $c->error;
} else {
    echo "SUCCESS";
}
