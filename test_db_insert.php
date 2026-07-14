<?php

/**
 * Direct Database Insert Test Script
 *
 * This script inserts dummy leads directly into the freshsales_leads_cache table,
 * bypassing any local webserver hostname/port issues.
 *
 * Run this script from your browser:
 * URL: http://your-local-url/TPHL-ERP/test_db_insert.php
 * Or from terminal: php test_db_insert.php
 */

$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

$payloads = [
    [
        "freshsales_id" => "FS_MOCK_101",
        "campaign_name" => "Premium Condominium Gulshan Phase 3",
        "platform" => "facebook",
        "which_apartment_size_are_you_interested_in" => "1800 sqft",
        "which_floor_are_you_for_shop" => "N/A",
        "when_are_you_planning_to_buy" => "Within 1 Month",
        "would_you_like_to_schedule_a_site_visit" => "Yes",
        "when_is_the_best_time_to_contact_you" => "Afternoon (3 PM - 6 PM)",
        "full_name" => "Rahat Khan",
        "job_title" => "Developer",
        "company_name" => "Tech Solution",
        "phone_number" => "+8801711223344",
        "city" => "Dhaka",
        "country" => "Bangladesh"
    ],
    [
        "freshsales_id" => "FS_MOCK_102",
        "campaign_name" => "Studio Apartments Uttara Promotion",
        "platform" => "google",
        "which_apartment_size_are_you_interested_in" => "1200 sqft",
        "which_floor_are_you_for_shop" => "N/A",
        "when_are_you_planning_to_buy" => "Immediately",
        "would_you_like_to_schedule_a_site_visit" => "No",
        "when_is_the_best_time_to_contact_you" => "Morning (9 AM - 12 PM)",
        "full_name" => "Mehedi Hasan",
        "job_title" => "Marketing Manager",
        "company_name" => "TPHL",
        "phone_number" => "+8801911223344",
        "city" => "Dhaka",
        "country" => "Bangladesh"
    ],
    [
        "freshsales_id" => "FS_MOCK_103",
        "campaign_name" => "Commercial Shop Space Project",
        "platform" => "whatsapp",
        "which_apartment_size_are_you_interested_in" => "N/A",
        "which_floor_are_you_for_shop" => "Ground Floor",
        "when_are_you_planning_to_buy" => "Within 6 Months",
        "would_you_like_to_schedule_a_site_visit" => "Yes",
        "when_is_the_best_time_to_contact_you" => "Evening (6 PM - 9 PM)",
        "full_name" => "Anik Sarker",
        "job_title" => "Businessman",
        "company_name" => "Sarker Trade",
        "phone_number" => "+8801811223344",
        "city" => "Chittagong",
        "country" => "Bangladesh"
    ]
];

echo "<h3>Inserting dummy leads directly into Database...</h3>";

$stmt = $db->prepare("
    INSERT INTO freshsales_leads_cache 
        (freshsales_id, campaign_name, platform, 
         which_apartment_size_are_you_interested_in, which_floor_are_you_for_shop, 
         when_are_you_planning_to_buy, would_you_like_to_schedule_a_site_visit, 
         when_is_the_best_time_to_contact_you, full_name, job_title, company_name, 
         phone_number, city, country, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        full_name = VALUES(full_name),
        campaign_name = VALUES(campaign_name)
");

if (!$stmt) {
    die("Statement prepare failed: " . $db->error);
}

foreach ($payloads as $idx => $lead) {
    $altPhone = null;
    $country = $lead['country'] ?? 'Bangladesh';
    $city = $lead['city'] ?? null;
    $jobTitle = $lead['job_title'] ?? null;
    $companyName = $lead['company_name'] ?? null;
    $shopFloor = $lead['which_floor_are_you_for_shop'] ?? null;

    $stmt->bind_param('ssssssssssssss', 
        $lead['freshsales_id'], 
        $lead['campaign_name'], 
        $lead['platform'], 
        $lead['which_apartment_size_are_you_interested_in'], 
        $shopFloor, 
        $lead['when_are_you_planning_to_buy'], 
        $lead['would_you_like_to_schedule_a_site_visit'], 
        $lead['when_is_the_best_time_to_contact_you'], 
        $lead['full_name'], 
        $jobTitle, 
        $companyName, 
        $lead['phone_number'], 
        $city, 
        $country
    );

    if ($stmt->execute()) {
        echo "Successfully inserted Lead: <strong>" . htmlspecialchars($lead['full_name']) . "</strong><br>";
    } else {
        echo "Failed to insert Lead: " . htmlspecialchars($lead['full_name']) . ". Error: " . $stmt->error . "<br>";
    }
}

$stmt->close();
echo "<br><strong>Database population complete! Go to the Freshsales Leads page in your ERP to see the data.</strong>";
