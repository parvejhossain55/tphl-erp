<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT id, when_are_you_planning_to_buy, would_you_like_to_schedule_a_site_visit, which_apartment_size_are_you_interested_in 
                   FROM leads 
                   WHERE (when_are_you_planning_to_buy IS NOT NULL AND when_are_you_planning_to_buy != '') 
                      OR (would_you_like_to_schedule_a_site_visit IS NOT NULL AND would_you_like_to_schedule_a_site_visit != '') 
                      OR (which_apartment_size_are_you_interested_in IS NOT NULL AND which_apartment_size_are_you_interested_in != '') 
                   LIMIT 5");
while($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n--- DISTINCT SAMPLES ---\n";
$r1 = $db->query("SELECT DISTINCT when_are_you_planning_to_buy FROM leads WHERE when_are_you_planning_to_buy != '' LIMIT 10");
while($row = $r1->fetch_assoc()) {
    echo "Planning to buy: [{$row['when_are_you_planning_to_buy']}]\n";
}

$r2 = $db->query("SELECT DISTINCT would_you_like_to_schedule_a_site_visit FROM leads WHERE would_you_like_to_schedule_a_site_visit != '' LIMIT 10");
while($row = $r2->fetch_assoc()) {
    echo "Site visit: [{$row['would_you_like_to_schedule_a_site_visit']}]\n";
}

$r3 = $db->query("SELECT DISTINCT which_apartment_size_are_you_interested_in FROM leads WHERE which_apartment_size_are_you_interested_in != '' LIMIT 10");
while($row = $r3->fetch_assoc()) {
    echo "Apartment size: [{$row['which_apartment_size_are_you_interested_in']}]\n";
}
