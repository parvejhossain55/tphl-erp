<?php

// 1 = Local Development Server
// 2 = Project staging Server
// 3 = Production Server


$current_server = 3; 
// Local Development Server
if ($current_server == 1) {
    return [
        "db_host" => "127.0.0.1",
        "db_user" => "root",
        "db_pass" => "",
        "db_name" => "tphl_db",
        // AI Assistant Read-Only database connection
        "db_ai_user" => "tphl_read_only",
        "db_ai_pass" => "ReadOnlyPass123!",
        // Google Gemini API Key
        "gemini_api_key" => "AQ.Ab8RN6KH-h0rRtsg0SIvPvY8GuHa6V3gx5VY0c3SC52cLSqybQ",
        
        // Premium Homes API Configuration
        "premium_homes_api_url" => "https://premium-api.dvalleybd.com/api/erp",
        "premium_homes_api_key" => "tphl_erp_key_9f8e7d6c5b4a392817065",

        // Tipsoi API Configuration
        "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@tphl@1274",

        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "aa4717fdd571e46b240ce35014a5d144",
        "automas_sms_sender_id" => "8809617635127",
        "sms_testing_mode" => false,
    ];
}

// CREATE USER 'tphl_read_only'@'localhost' IDENT IFIED BY 'ReadOnlyPass123!';
// GRANT SELECT ON `tphl_db`.* TO 'tphl_read_only'@'localhost';
// FLUSH PRIVILEGES;
if ($current_server == 2) {
    return [
        "db_host" => "127.0.0.1",

        "db_user" => "erp_staging_user",
        "db_pass" => "erp_staging_db@#5672dhKL%sd10",
        "db_name" => "erp_staging_db",

         // AI Assistant Read-Only database connection
        "db_ai_user" => "tphl_read_only",
        "db_ai_pass" => "ReadOnlyPass123!",

        // Google Gemini API Key
        "gemini_api_key" => "AQ.Ab8RN6KH-h0rRtsg0SIvPvY8GuHa6V3gx5VY0c3SC52cLSqybQ",
        
        // Premium Homes API Configuration
        "premium_homes_api_url" => "http://127.0.0.1:8000/api/erp",
        "premium_homes_api_key" => "tphl_erp_key_9f8e7d6c5b4a392817065",

        // Tipsoi API Configuration
         "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@tphl@1274",

        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "aa4717fdd571e46b240ce35014a5d144",
        "automas_sms_sender_id" => "8809617635127",
        "sms_testing_mode" => false,
    ];
}

// Production Serve
if ($current_server == 3) {
    return [
        "db_host" => "127.0.0.1",
        "db_user" => "final_erp_user",
        "db_pass" => "finaltPhl@#5672dhKL%sd10",
        "db_name" => "final_erp_db",

         // AI Assistant Read-Only database connection
        "db_ai_user" => "tphl_read_only",
        "db_ai_pass" => "ReadOnlyPass123!",

        // Google Gemini API Key
        "gemini_api_key" => "AQ.Ab8RN6KH-h0rRtsg0SIvPvY8GuHa6V3gx5VY0c3SC52cLSqybQ",
        
        // Premium Homes API Configuration
        "premium_homes_api_url" => "http://127.0.0.1:8000/api/erp",
        "premium_homes_api_key" => "tphl_erp_key_9f8e7d6c5b4a392817065",

        // Tipsoi API Configuration
        "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@tphl@1274",

        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "aa4717fdd571e46b240ce35014a5d144",
        "automas_sms_sender_id" => "8809617635127",
        "sms_testing_mode" => false,
    ];

}


/// if emi data uploaded wrong then deleted with


// -- ধাপ ১: EMI শিডিউল ডিলিট করা
// DELETE FROM emi_schedules 
// WHERE lead_payment_id IN (
//     SELECT lp.id 
//     FROM lead_payments lp
//     INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
//     WHERE lb.project_id = 45 AND lp.payment_type IN ('emi', 'emi_due_settlement', 'development_charge')
// );

// -- ধাপ ২: মূল পেমেন্ট হিস্ট্রি ডিলিট করা
// DELETE lp FROM lead_payments lp
// INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
// WHERE lb.project_id = 45 AND lp.payment_type IN ('emi', 'emi_due_settlement', 'development_charge');

