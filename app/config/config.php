<?php

$isLocalhost = (
    (DIRECTORY_SEPARATOR === '\\') ||
    (isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    ))
);
$current_server = $isLocalhost ? 1 : 3;

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
        "notices_api_base_url" => "https://api.dpremiumhomes.com/api",

        // Tipsoi API Configuration
        "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@dpremium@2026",
        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "734f99f126704a937debae6c0ce52516",
        "automas_sms_sender_id" => "TPHL",
        "sms_testing_mode" => false,

        // SMTP Email Configuration for Local Testing (Gmail)
        "smtp_host" => "smtp.gmail.com",
        "smtp_user" => "no-reply@dpremiumhomes.com",
        "smtp_pass" => "djcdawaxdbmdotlj",
        "smtp_secure" => "ssl",
        "smtp_port" => 465,
    ];
}
// CREATE USER 'tphl_read_only'@'localhost' IDENT IFIED BY 'ReadOnlyPass123!';

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
        "notices_api_base_url" => "https://api.dpremiumhomes.com/api",

        // Tipsoi API Configuration
        "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@dpremium@2026",

        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "734f99f126704a937debae6c0ce52516",
        "automas_sms_sender_id" => "TPHL",
        "sms_testing_mode" => false,

        // SMTP Email Configuration (Dummy/Placeholder)
        "smtp_host" => "smtp.gmail.com",
        "smtp_user" => "your_email@gmail.com",
        "smtp_pass" => "your_app_password",
        "smtp_secure" => "tls", // tls or ssl
        "smtp_port" => 587,
        "smtp_from" => "no-reply@dpremiumhomes.com",
        "smtp_from_name" => "TPHL ERP Notification",
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
        "notices_api_base_url" => "https://api.dpremiumhomes.com/api",

        // Tipsoi API Configuration
        "tipsoi_base_url" => "https://app.tipsoi.ai/inovace-client/api/v1/",
        "tipsoi_email" => "info@dpremiumhomes.com",
        "tipsoi_password" => "HRD@dpremium@2026",

        // Automas Bulk SMS API Configuration
        "automas_sms_api_url" => "https://api.automas.com.bd/smsapiv4",
        "automas_sms_api_key" => "734f99f126704a937debae6c0ce52516",
        "automas_sms_sender_id" => "TPHL",
        "sms_testing_mode" => false,

        "smtp_host" => "smtp.gmail.com",
        "smtp_user" => "no-reply@dpremiumhomes.com",
        "smtp_pass" => "djcdawaxdbmdotlj",
        "smtp_secure" => "ssl",
        "smtp_port" => 465,
    ];

}


