<?php

/**
 * Test Script for Freshsales Webhook
 *
 * Run this script from your browser or console to insert dummy leads
 * into your freshsales_leads_cache table via the local webhook route.
 *
 * URL: http://localhost/TPHL-ERP/test_freshsales_webhook.php
 */

$webhookUrl = 'http://localhost/TPHL-ERP/freshsales-webhook';
$secret = 'TphlFwSecret2026@#'; // Matching freshsales_webhook_secret in config.php

// Dummy payloads representing Facebook and Google leads
$payloads = [
    [
        "event" => "lead.created",
        "lead" => [
            "id" => "1000293021",
            "first_name" => "Rahat",
            "last_name" => "Khan",
            "mobile_number" => "+8801711223344",
            "email" => "rahat.khan@gmail.com",
            "city" => "Dhaka",
            "country" => "Bangladesh",
            "job_title" => "Developer",
            "company_name" => "Tech Solution",
            "campaign_name" => "Premium Condominium Gulshan Phase 3",
            "lead_source" => [
                "name" => "Facebook Lead Ads"
            ],
            "custom_field" => [
                "which_apartment_size_are_you_interested_in" => "1800 sqft",
                "which_floor_are_you_for_shop" => "N/A",
                "when_are_you_planning_to_buy" => "Within 1 Month",
                "would_you_like_to_schedule_a_site_visit" => "Yes",
                "when_is_the_best_time_to_contact_you" => "Afternoon (3 PM - 6 PM)"
            ]
        ]
    ],
    [
        "event" => "lead.created",
        "lead" => [
            "id" => "1000293022",
            "first_name" => "Mehedi",
            "last_name" => "Hasan",
            "mobile_number" => "+8801911223344",
            "email" => "mehedihjony@gmail.com",
            "city" => "Dhaka",
            "country" => "Bangladesh",
            "job_title" => "Marketing Manager",
            "company_name" => "TPHL",
            "campaign_name" => "Studio Apartments Uttara Promotion",
            "lead_source" => [
                "name" => "Google Ads"
            ],
            "custom_field" => [
                "which_apartment_size_are_you_interested_in" => "1200 sqft",
                "which_floor_are_you_for_shop" => "N/A",
                "when_are_you_planning_to_buy" => "Immediately",
                "would_you_like_to_schedule_a_site_visit" => "No",
                "when_is_the_best_time_to_contact_you" => "Morning (9 AM - 12 PM)"
            ]
        ]
    ],
    [
        "event" => "lead.created",
        "lead" => [
            "id" => "1000293023",
            "first_name" => "Anik",
            "last_name" => "Sarker",
            "mobile_number" => "+8801811223344",
            "email" => "anik.sarker@gmail.com",
            "city" => "Chittagong",
            "country" => "Bangladesh",
            "job_title" => "Businessman",
            "company_name" => "Sarker Trade",
            "campaign_name" => "Commercial Shop Space Project",
            "lead_source" => [
                "name" => "WhatsApp Business"
            ],
            "custom_field" => [
                "which_apartment_size_are_you_interested_in" => "N/A",
                "which_floor_are_you_for_shop" => "Ground Floor",
                "when_are_you_planning_to_buy" => "Within 6 Months",
                "would_you_like_to_schedule_a_site_visit" => "Yes",
                "when_is_the_best_time_to_contact_you" => "Evening (6 PM - 9 PM)"
            ]
        ]
    ]
];

echo "<h3>Sending dummy Webhook payloads to local ERP...</h3>";

foreach ($payloads as $idx => $payload) {
    $dataString = json_encode($payload);
    $signature = hash_hmac('sha256', $dataString, $secret);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Freshsales-Signature: ' . $signature,
        'Content-Length: ' . strlen($dataString)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Payload " . ($idx + 1) . " status: <strong>HTTP $httpCode</strong>. Response: <pre>" . htmlspecialchars($response) . "</pre><br>";
}

echo "<strong>Webhook testing completed! Go to the Freshsales Leads page in your ERP to see the data.</strong>";
