<?php

// 1 = Local Development Server
// 2 = Project staging Server
// 3 = Production Server

$current_server = 1; 
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
    ];
}


// CREATE USER 'tphl_read_only'@'localhost' IDENTIFIED BY 'ReadOnlyPass123!';
// GRANT SELECT ON `tphl_db`.* TO 'tphl_read_only'@'localhost';
// FLUSH PRIVILEGES;

// Project Testing Server

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
    ];
}

// Production Server

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
    ];
}




