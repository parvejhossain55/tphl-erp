<?php

// 1 = Local Development Server
// 2 = Project Testing Server
// 3 = Production Server

$current_server = 3; 

///////////////////////////////////////////////
// Local Development Server

if ($current_server == 1) {
    return [
        "db_host" => "localhost",
        "db_user" => "root",
        "db_pass" => "",
        "db_name" => "tphl_db",
    ];
}


///////////////////////////////////////////////
// Project Testing Server

if ($current_server == 2) {
    return [
        "db_host" => "localhost",
        "db_user" => "dvalleyb_tphl_db",
        "db_pass" => "UI+,&%e]1u,UKxMS",
        "db_name" => "dvalleyb_tphl_db",
    ];

}

///////////////////////////////////////////////
// Production Server

if ($current_server == 3) {
    return [
        "db_host" => "localhost",
        "db_user" => "final_erp_user",
        "db_pass" => "finaltPhl@#5672dhKL%sd10",
        "db_name" => "final_erp_db",
    ];
}
