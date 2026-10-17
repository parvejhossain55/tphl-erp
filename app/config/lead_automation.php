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
        'url'     => 'https://docs.google.com/spreadsheets/d/1btVkSlBIJmLfi54Sz2nQ1wlM3VSbCQUp3hu1c4nW-4w/edit?usp=sharing',
        'timeout' => 30,
    ],

    // ------------------------------------------------------------------------
    // 4. Project to Team Mapping (Round-Robin Distribution)
    // ------------------------------------------------------------------------
    'projects' => [
        // TPRC Projects (Equally distributed to Team Cannibal [7], Vhagar [12], Meraxes [13])
        'crown' => [
            'name'       => 'TPRC Crown Tower',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
        'imperial' => [
            'name'       => 'TPRC Imperial Tower',
            'project_id' => 29,
            'teams'      => [7, 12, 13],
        ],
        'majesty' => [
            'name'       => 'TPRC Majesty Tower',
            'project_id' => 28,
            'teams'      => [7, 12, 13],
        ],
        'tprc commercial' => [
            'name'       => 'TPRC Commercial',
            'project_id' => 66,
            'teams'      => [7, 12, 13],
        ],

        // Ashulia Projects
        'velvet' => [
            'name'       => 'The Premium Velvet Heights',
            'project_id' => 51,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'velvet heights' => [
            'name'       => 'The Premium Velvet Heights',
            'project_id' => 51,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'maple' => [
            'name'       => 'The Maple Heights',
            'project_id' => 6,
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
        'crown pavilion' => [
            'name'       => 'The Crown Pavilion',
            'project_id' => 9,
            'teams'      => [4, 8, 9, 17, 18, 19],
        ],
        'green paradise' => [
            'name'       => 'The Premium Green Paradise',
            'project_id' => 65,
            'teams'      => [4, 9, 17], // Saleh Rafi (4), Feroz Ahmed (9), Munshi Aiman (17)
        ],
        'legacy residence' => [
            'name'       => 'The Legacy Residence',
            'project_id' => 5,
            'teams'      => [4, 8, 9, 19],
        ],
        'legacy' => [
            'name'       => 'The Legacy Residence',
            'project_id' => 5,
            'teams'      => [4, 8, 9, 19],
        ],
        'urban residence' => [
            'name'       => 'The Premium Urban Residence',
            'project_id' => 36,
            'teams'      => [4],
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
            'teams'      => [8],
        ],
        'skypoint' => [
            'name'       => 'The Premium Skypoint Villa',
            'project_id' => 27,
            'teams'      => [8],
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

        // Gulshan Team Allocations
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
        'serenity' => [
            'name'       => 'The Premium Serenity',
            'project_id' => 61,
            'teams'      => [14],
        ],
        'phoenix' => [
            'name'       => 'The Premium Phoenix',
            'project_id' => 71,
            'teams'      => [15],
        ],
        'florence' => [
            'name'       => 'The Premium Florence',
            'project_id' => 60,
            'teams'      => [15],
        ],
        'bayanno' => [
            'name'       => 'The Premium Bayanno',
            'project_id' => 73,
            'teams'      => [11],
        ],
        'novera heights' => [
            'name'       => 'The Premium Novera Heights',
            'project_id' => 72,
            'teams'      => [1],
        ],
        'novera' => [
            'name'       => 'The Premium Novera Heights',
            'project_id' => 72,
            'teams'      => [1],
        ],
        'lumina' => [
            'name'       => 'The Premium Lumina',
            'project_id' => 67,
            'teams'      => [1],
        ],
        'novus' => [
            'name'       => 'The Premium Novus',
            'project_id' => 69,
            'teams'      => [10],
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

        // Additional / Common Projects
        'twelfth heights' => [
            'name'       => 'The Premium Twelfth Heights',
            'project_id' => 20,
            'teams'      => [3, 10],
        ],
        'velinor' => [
            'name'       => 'The Premium Velinor',
            'project_id' => 14,
            'teams'      => [5, 6],
        ],
        'condominium' => [
            'name'       => 'Condominium (TPRC)',
            'project_id' => 12,
            'teams'      => [7, 12, 13],
        ],
    ],

    'default_project' => [
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
