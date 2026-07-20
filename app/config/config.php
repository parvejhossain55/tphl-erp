<?php

// 1 = Local Development Server
// 2 = Project staging Server
// 3 = Production Server

$current_server = 1; 
// Local Development Server
/////////////////////////////////
////////////////////////////////
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
        "tipsoi_password" => "Tphl2025#",

        // Freshsales CRM Integration Configuration
        "freshsales_webhook_secret" => "TphlFwSecret2026@#",
        "freshsales_api_key" => "ACw9JyfiG2qLPQdN9AhUhg",
        "freshsales_domain" => "premiumhomesltd"
    ];
}

// CREATE USER 'tphl_read_only'@'localhost' IDENT IFIED BY 'ReadOnlyPass123!';
// GRANT SELECT ON `tphl_db`.* TO 'tphl_read_only'@'localhost';
// FLUSH PRIVILEGES;
///////////////////////////

if ($current_server == 2) {
    return [
        "db_host" => "localhost",

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
        "tipsoi_password" => "Tphl2025#",
        // Freshsales CRM Integration Configuration
        "freshsales_webhook_secret" => "TphlFwSecret2026@#",
        "freshsales_api_key" => "ACw9JyfiG2qLPQdN9AhUhg",
        "freshsales_domain" => "premiumhomesltd"
    ];
}

// Production Serve

if ($current_server == 3) {
    return [
        "db_host" => "localhost",
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
        "tipsoi_password" => "Tphl2025#",

        // Freshsales CRM Integration Configuration
        "freshsales_webhook_secret" => "TphlFwSecret2026@#",
        "freshsales_api_key" => "ACw9JyfiG2qLPQdN9AhUhg",
        "freshsales_domain" => "premiumhomesltd"
    ];
}




