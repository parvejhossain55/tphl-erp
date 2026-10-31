<?php
/**
 * Lead Automation Configuration
 * Database, Google Sheets Sync & AI Config for TPHL-ERP
 */

// Load main ERP configuration
$erpConfig = [];
if (file_exists(__DIR__ . '/config.php')) {
    $erpConfig = require __DIR__ . '/config.php';
}

$geminiKey = $erpConfig['gemini_api_key'] ?? 'AQ.Ab8RN6KH-h0rRtsg0SIvPvY8GuHa6V3gx5VY0c3SC52cLSqybQ';

return [
    // ------------------------------------------------------------------------
    // 1. Google Gemini AI Configuration
    // ------------------------------------------------------------------------
    'gemini' => [
        'enabled'           => true,
        'api_key'           => $geminiKey,
        'model'             => 'gemini-2.5-flash',
        'timeout'           => 60,
        'fallback_to_local' => true,
    ],

    // ------------------------------------------------------------------------
    // 2. Database Configuration
    // ------------------------------------------------------------------------
    'db' => [
        'host'     => $erpConfig['db_host'] ?? '127.0.0.1',
        'port'     => 3306,
        'database' => $erpConfig['db_name'] ?? 'tphl_db',
        'username' => $erpConfig['db_user'] ?? 'root',
        'password' => $erpConfig['db_pass'] ?? '',
        'charset'  => 'utf8mb4',
    ],

    // ------------------------------------------------------------------------
    // 3. Google Sheets Configuration
    // ------------------------------------------------------------------------
    'google_sheet' => [
        'url'     => 'https://docs.google.com/spreadsheets/d/1Smg3B14dEanyrCf0qRc3wAdgLHi0x-9cDjNINvcMNlY/edit',
        'timeout' => 60,
    ],

    // ------------------------------------------------------------------------
    // 3.1. Repibee WhatsApp Integration Configuration
    // ------------------------------------------------------------------------
    'repibee' => [
        'enabled' => true,
        'api_key' => $erpConfig['repibee_api_key'] ?? 'tphl_repibee_secret_key_2026_x89f2a',
    ],

    // ------------------------------------------------------------------------
    // 4. Project to Team Mapping (Round-Robin Distribution - November Setup)
    // ------------------------------------------------------------------------
    'projects' => [
        // ====================================================================
        // ATI OFFICE (TPRC Projects) -> Teams: Cannibal [7], Vhagar [12], Meraxes [13]
        // ====================================================================
        'tprc commercial' => [
            'name'       => 'TPRC Commercial',
            'project_id' => 66,
            'teams'      => [7, 12, 13],
        ],
        'tprc ll commercial' => [
            'name'       => 'TPRC Commercial',
            'project_id' => 66,
            'teams'      => [7, 12, 13],
        ],
        'crown tower' => [
            'name'       => 'TPRC Crown Tower',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
        'tprc crown' => [
            'name'       => 'TPRC Crown Tower',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
        'crown' => [
            'name'       => 'TPRC Crown Tower',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
        'condominium' => [
            'name'       => 'TPRC Crown Tower',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
        'imperial tower' => [
            'name'       => 'TPRC Imperial Tower',
            'project_id' => 29,
            'teams'      => [7, 12, 13],
        ],
        'tprc imperial' => [
            'name'       => 'TPRC Imperial Tower',
            'project_id' => 29,
            'teams'      => [7, 12, 13],
        ],
        'imperial' => [
            'name'       => 'TPRC Imperial Tower',
            'project_id' => 29,
            'teams'      => [7, 12, 13],
        ],
        'majesty merge' => [
            'name'       => 'TPRC Majesty Tower',
            'project_id' => 28,
            'teams'      => [7, 12, 13],
        ],
        'majesty tower' => [
            'name'       => 'TPRC Majesty Tower',
            'project_id' => 28,
            'teams'      => [7, 12, 13],
        ],
        'tprc majesty' => [
            'name'       => 'TPRC Majesty Tower',
            'project_id' => 28,
            'teams'      => [7, 12, 13],
        ],
        'majesty' => [
            'name'       => 'TPRC Majesty Tower',
            'project_id' => 28,
            'teams'      => [7, 12, 13],
        ],

        // ====================================================================
        // ASHULIA OFFICE PROJECTS -> Teams: Saleh Rafi [4], Rakib Al Hasan [8],
        // Feroz Ahmed [9], Munshi Aiman [17], Kawser [18], Ibrahim Khalil [19]
        // ====================================================================
        'the crown pavilion' => [
            'name'       => 'The Crown Pavilion',
            'project_id' => 9,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'crown pavilion' => [
            'name'       => 'The Crown Pavilion',
            'project_id' => 9,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'amberleaf estate' => [
            'name'       => 'The Amberleaf Estate',
            'project_id' => 63,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'amberleaf' => [
            'name'       => 'The Amberleaf Estate',
            'project_id' => 63,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'amber leaf' => [
            'name'       => 'The Amberleaf Estate',
            'project_id' => 63,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'tpsc commercial' => [
            'name'       => 'TPSC Commercial',
            'project_id' => 62,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'tpsc' => [
            'name'       => 'TPSC Commercial',
            'project_id' => 62,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'maple heights' => [
            'name'       => 'The Maple Heights',
            'project_id' => 6,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'maple' => [
            'name'       => 'The Maple Heights',
            'project_id' => 6,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'velvet heights' => [
            'name'       => 'The Premium Velvet Heights',
            'project_id' => 51,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'velvet' => [
            'name'       => 'The Premium Velvet Heights',
            'project_id' => 51,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'skyline enclave' => [
            'name'       => 'The Premium Skyline Enclave',
            'project_id' => 75,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'skyline' => [
            'name'       => 'The Premium Skyline Enclave',
            'project_id' => 75,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'legacy residence' => [
            'name'       => 'The Legacy Residence',
            'project_id' => 5,
            'teams'      => [8, 9, 19],
        ],
        'legacy' => [
            'name'       => 'The Legacy Residence',
            'project_id' => 5,
            'teams'      => [8, 9, 19],
        ],
        'green paradise' => [
            'name'       => 'The Premium Green Paradise',
            'project_id' => 65,
            'teams'      => [9, 17, 19],
        ],
        'paradise' => [
            'name'       => 'The Premium Green Paradise',
            'project_id' => 65,
            'teams'      => [9, 17, 19],
        ],
        'glory garden' => [
            'name'       => 'The Premium Glory Garden',
            'project_id' => 48,
            'teams'      => [4, 8, 9],
        ],
        'lake view terrace' => [
            'name'       => 'The Premium Lake View Terrace',
            'project_id' => 22,
            'teams'      => [4, 9],
        ],
        'lake view' => [
            'name'       => 'The Premium Lake View Terrace',
            'project_id' => 22,
            'teams'      => [4, 9],
        ],
        'lakeview' => [
            'name'       => 'The Premium Lake View Terrace',
            'project_id' => 22,
            'teams'      => [4, 9],
        ],
        'skypoint villa' => [
            'name'       => 'The Premium Skypoint Villa',
            'project_id' => 27,
            'teams'      => [8, 18],
        ],
        'skypoint' => [
            'name'       => 'The Premium Skypoint Villa',
            'project_id' => 27,
            'teams'      => [8, 18],
        ],
        'sky point' => [
            'name'       => 'The Premium Skypoint Villa',
            'project_id' => 27,
            'teams'      => [8, 18],
        ],
        'sky-point' => [
            'name'       => 'The Premium Skypoint Villa',
            'project_id' => 27,
            'teams'      => [8, 18],
        ],
        'urban residence' => [
            'name'       => 'The Premium Urban Residence',
            'project_id' => 36,
            'teams'      => [19],
        ],
        'luxoria residence' => [
            'name'       => 'The Premium Luxoria Residence',
            'project_id' => 32,
            'teams'      => [9],
        ],
        'luxoria' => [
            'name'       => 'The Premium Luxoria Residence',
            'project_id' => 32,
            'teams'      => [9],
        ],
        'luxaria' => [
            'name'       => 'The Premium Luxoria Residence',
            'project_id' => 32,
            'teams'      => [9],
        ],
        'harmony residence' => [
            'name'       => 'The Premium Harmony Residence',
            'project_id' => 47,
            'teams'      => [17],
        ],

        // ====================================================================
        // GULSHAN OFFICE PROJECTS -> Teams: Sadman Sakib [1], Shammi [10],
        // Shafia [11], Rejwan [14], Fahad [15], General Team (Tanvir) [20]
        // ====================================================================
        'the premium canvas of happiness' => [
            'name'       => 'The Premium Canvas of Happiness',
            'project_id' => 55,
            'teams'      => [1, 10, 11, 14, 15],
        ],
        'canvas of happiness' => [
            'name'       => 'The Premium Canvas of Happiness',
            'project_id' => 55,
            'teams'      => [1, 10, 11, 14, 15],
        ],
        'canvas' => [
            'name'       => 'The Premium Canvas of Happiness',
            'project_id' => 55,
            'teams'      => [1, 10, 11, 14, 15],
        ],
        'symphony of life' => [
            'name'       => 'The Premium Symphony Of Life',
            'project_id' => 76,
            'teams'      => [1, 10, 11, 14, 20],
        ],
        'symphony' => [
            'name'       => 'The Premium Symphony Of Life',
            'project_id' => 76,
            'teams'      => [1, 10, 11, 14, 20],
        ],
        'phoenix' => [
            'name'       => 'The Premium Phoenix',
            'project_id' => 71,
            'teams'      => [10, 14, 15],
        ],
        'serenity' => [
            'name'       => 'The Premium Serenity',
            'project_id' => 61,
            'teams'      => [14, 15],
        ],
        'the premium lumina' => [
            'name'       => 'The Premium Lumina',
            'project_id' => 67,
            'teams'      => [1, 14],
        ],
        'lumina' => [
            'name'       => 'The Premium Lumina',
            'project_id' => 67,
            'teams'      => [1, 14],
        ],
        'novera heights' => [
            'name'       => 'The Premium Novera Heights',
            'project_id' => 72,
            'teams'      => [1, 10],
        ],
        'novera' => [
            'name'       => 'The Premium Novera Heights',
            'project_id' => 72,
            'teams'      => [1, 10],
        ],
        'zenith heights' => [
            'name'       => 'The Premium Zenith Heights',
            'project_id' => 70,
            'teams'      => [10],
        ],
        'zenith' => [
            'name'       => 'The Premium Zenith Heights',
            'project_id' => 70,
            'teams'      => [10],
        ],
        'bayanno' => [
            'name'       => 'The Premium Bayanno',
            'project_id' => 73,
            'teams'      => [11],
        ],

        // ====================================================================
        // NATURE OFFICE -> Team: The Nature CXB [16]
        // ====================================================================
        'the nature' => [
            'name'       => 'The Nature',
            'project_id' => 74,
            'teams'      => [16],
        ],
        'nature' => [
            'name'       => 'The Nature',
            'project_id' => 74,
            'teams'      => [16],
        ],
    ],

    'default_project' => [
        'name'                      => 'Common Leads',
        'project_id'                => null,
        'team_id'                   => null,
        'allow_insert_if_unmatched' => true,
    ],

    // ------------------------------------------------------------------------
    // 5. Duplicate Check Settings
    // ------------------------------------------------------------------------
    'duplicate_check' => [
        'enabled' => true,
        'mode'    => 'phone_campaign', 
    ],

    // ------------------------------------------------------------------------
    // 6. Default Values for New Leads
    // ------------------------------------------------------------------------
    'lead_defaults' => [
        'distribute'       => 0,
        'status'           => 'active',
        'last_lead_action' => 'new',
        'call_status'      => 'not_called',
        'lead_quality'     => 'Pending',
        'currency'         => 'BDT',
        'uploaded_from'    => 'From Campaign',
        'visibility'       => 'public',
        'residency_type'   => 'local',
        'default_country'  => 'Bangladesh',
    ],

    'secret_token' => 'ERP_LEAD_SYNC_SECURE_TOKEN_2026',
    'log_file'     => __DIR__ . '/../../logs/lead_sync.log',
];
