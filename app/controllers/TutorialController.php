<?php

class TutorialController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/admin-login");
            exit;
        }
    }

    /**
     * Redirects the user to a role-specific Google Drive link for tutorial videos
     */
    public function redirect()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Map role_id to specific Google Drive links
        $links = [
            1  => 'https://drive.google.com/drive/folders/placeholder_admin',                       // Admin
            2  => 'https://drive.google.com/drive/folders/placeholder_employee',                    // Employee
            3  => 'https://drive.google.com/drive/folders/placeholder_super_admin',                 // super Admin
            4  => 'https://drive.google.com/drive/folders/placeholder_hr',                           // HR
            5  => 'https://drive.google.com/drive/folders/placeholder_sales_manager',               // Sales Manager
            6  => 'https://drive.google.com/drive/folders/placeholder_accounts',                     // Accounts
            7  => 'https://drive.google.com/drive/folders/placeholder_clients_experiment',           // Clients Experiment
            8  => 'https://drive.google.com/drive/folders/placeholder_sales_executive',             // Sales Executive
            9  => 'https://drive.google.com/drive/folders/placeholder_sales_team_head',             // Sales Team Head
            10 => 'https://drive.google.com/drive/folders/placeholder_head_of_digital_marketing',   // Head Of Digital Marketing Team
            11 => 'https://drive.google.com/drive/folders/placeholder_executive_of_digital_marketing', // Executive Of Digital Marketing Team
            12 => 'https://drive.google.com/file/d/1JtzuDBlMIkwpxqAj5v_0-fWSR12V7oD6/view',               //done Site Engineer
            13 => 'https://drive.google.com/drive/folders/placeholder_quality_controller',          // Quality Controller(QC)
            14 => 'https://drive.google.com/drive/folders/placeholder_land_manager',                 // Land Manager
            15 => 'https://drive.google.com/drive/folders/placeholder_project_manager',             // Project Manager
            16 => 'https://drive.google.com/file/d/12mGDl3tPgS4HiykGLZ2fwjbhb5XxpZsw/view?usp=sharing',                   //Done Draftsman
            17 => 'https://drive.google.com/drive/folders/placeholder_purchase_team_leader',        // Purchase team leader
            18 => 'https://drive.google.com/file/d/1Ax9o02RolpSNLL4zw_WVIGkLbLM-a6fa/view?usp=sharing',        //Done  Project Architecture
            19 => 'https://drive.google.com/file/d/1kps7u3LuQ_JXVrQhC45CqTY7Hv29YTlX/view?usp=sharing',                   //Done Estimator
            20 => 'https://drive.google.com/drive/folders/placeholder_site_operator',               // Site Operator
            21 => 'https://drive.google.com/drive/folders/placeholder_structure_designer',          // Structure designer
            22 => 'https://drive.google.com/drive/folders/placeholder_project_manager_head',        // Project Manager Head
            23 => 'https://drive.google.com/drive/folders/placeholder_purchase_executive',          // Purchase executive
            24 => 'https://drive.google.com/drive/folders/placeholder_qc_internal_audit_head',      // QC & Internal Audit Head
            25 => 'https://drive.google.com/drive/folders/placeholder_qc_internal_audit_executive', // QC & Internal Audit Executive
        ];

        // Default fallback link if no role-specific link is defined
        $defaultLink = 'https://drive.google.com/drive/folders/placeholder_default_fallback';

        $redirectUrl = $links[$roleId] ?? $defaultLink;

        header("Location: " . $redirectUrl);
        exit;
    }
}
