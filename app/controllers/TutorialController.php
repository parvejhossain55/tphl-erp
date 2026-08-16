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
            1  => 'https://drive.google.com/file/d/1eAv78W2wGI0e3WCoKzYMKLxZrctWcYav/view?usp=sharing',                       // Admin
            2  => 'https://drive.google.com/drive/folders/placeholder_employee',                    // Employee
            3  => 'https://drive.google.com/file/d/1eAv78W2wGI0e3WCoKzYMKLxZrctWcYav/view?usp=sharing',                 // super Admin
            4  => 'https://drive.google.com/file/d/11MARWakxK07MS3Yj7Yyv3RD7u17YTNik/view?usp=sharing',      //Done HR
            5  => 'https://drive.google.com/file/d/1_3o5AyBYVJ6EDrVBQDCq7VNlRDRWJ41j/view?usp=sharing',      //Done Sales Manager
            // 6  => 'https://drive.google.com/file/d/1Nre2UfwHHH7d2615ebEABTf-splwvMu4/view?usp=sharing',                     //Old Accounts
            6  => 'https://drive.google.com/file/d/1ruFmbEAsbua3P1LA4q4DdqPeCiV4EFcF/view?usp=sharing',                     //New Accounts
            30  => 'https://drive.google.com/file/d/1ruFmbEAsbua3P1LA4q4DdqPeCiV4EFcF/view?usp=sharing',                     //New Accounts
            7  => 'https://drive.google.com/file/d/1jZuBi9lwPEeGP98luiMg4347hv-hLP_4/view?usp=sharing',           // Clients Experiment
            8  => 'https://drive.google.com/file/d/1N1hnMRkxeqak_Z3h6elD_ijb1F2rc_GT/view?usp=sharing',             //Done Sales Executive
            9  => 'https://drive.google.com/file/d/1_3o5AyBYVJ6EDrVBQDCq7VNlRDRWJ41j/view?usp=sharing',             //Done Sales Team Head
            10 => 'https://drive.google.com/file/d/1QG4c3IctwwV6nTmGYa_FYwrWOkjFOGn4/view',   // Head Of Digital Marketing Team
            11 => 'https://drive.google.com/file/d/1QG4c3IctwwV6nTmGYa_FYwrWOkjFOGn4/view', // Executive Of Digital Marketing Team
            12 => 'https://drive.google.com/file/d/1JtzuDBlMIkwpxqAj5v_0-fWSR12V7oD6/view',               //done Site Engineer
            13 => 'https://drive.google.com/file/d/1XUtxjYAOl2MYr_hHEM03Sco2Y7f2m6C3/view?usp=sharing',          // Quality Controller(QC)
            14 => 'https://drive.google.com/drive/folders/placeholder_land_manager',                 // Land Manager
            15 => 'https://drive.google.com/file/d/143RBv-E3xqc0HEtHzp_c4_DbHkh-ilfz/view?usp=sharing',             // Project Manager
            16 => 'https://drive.google.com/file/d/12mGDl3tPgS4HiykGLZ2fwjbhb5XxpZsw/view?usp=sharing',                   //Done Draftsman
            17 => 'https://drive.google.com/file/d/17W0ZcKN0yyqpk-d0Tz80spT0la-cCG0G/view?usp=sharing',        //Done Purchase team leader
            18 => 'https://drive.google.com/file/d/1Ax9o02RolpSNLL4zw_WVIGkLbLM-a6fa/view?usp=sharing',        //Done  Project Architecture
            19 => 'https://drive.google.com/file/d/1kps7u3LuQ_JXVrQhC45CqTY7Hv29YTlX/view?usp=sharing',                   //Done Estimator
            20 => 'https://drive.google.com/file/d/1puJd2dXsnIBI2o5t9mOWEP6VAPw3BOzp/view?usp=sharing',               // Site Operator
            21 => 'https://drive.google.com/drive/folders/placeholder_structure_designer',          // Structure designer
            22 => 'https://drive.google.com/file/d/143RBv-E3xqc0HEtHzp_c4_DbHkh-ilfz/view?usp=sharing',        // Project Manager Head
            23 => 'https://drive.google.com/file/d/11Fv6kK549-qEWnAR3uBqX7zyC88i6Cu3/view?usp=sharing',          // Purchase executive
            24 => 'https://drive.google.com/file/d/1hEUILFg99leifHJr_7nXkFyqhcsn84ZY/view?usp=sharing',      // QC & Internal Audit Head
            25 => 'https://drive.google.com/drive/folders/placeholder_qc_internal_audit_executive', // QC & Internal Audit Executive
            33 => 'https://drive.google.com/file/d/1eAv78W2wGI0e3WCoKzYMKLxZrctWcYav/view?usp=sharing', // Chief Officer (Management)
        ];

        // Default fallback link if no role-specific link is defined
        $defaultLink = 'https://drive.google.com/drive/folders/placeholder_default_fallback';

        $redirectUrl = $links[$roleId] ?? $defaultLink;

        // Perform clean client-side redirect stripping HTTP referrers so Google Drive opens smoothly
        echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="referrer" content="no-referrer">';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">';
        echo '<script>window.location.replace(' . json_encode($redirectUrl) . ');</script>';
        echo '</head><body>';
        echo '<p style="font-family:sans-serif; text-align:center; margin-top:50px;">Redirecting to tutorial video... <br><br>';
        echo '<a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '" rel="noreferrer" style="color:#007bff; text-decoration:underline;">Click here if you are not redirected automatically</a>.</p>';
        echo '</body></html>';
        exit;
    }

    /**
     * Redirects the user to the Sales Head tutorial video (specifically for Chief Officer)
     */
    public function redirectSales()
    {
        $redirectUrl = 'https://drive.google.com/file/d/1_3o5AyBYVJ6EDrVBQDCq7VNlRDRWJ41j/view?usp=sharing'; // Sales Head

        // Perform clean client-side redirect stripping HTTP referrers so Google Drive opens smoothly
        echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="referrer" content="no-referrer">';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">';
        echo '<script>window.location.replace(' . json_encode($redirectUrl) . ');</script>';
        echo '</head><body>';
        echo '<p style="font-family:sans-serif; text-align:center; margin-top:50px;">Redirecting to tutorial video... <br><br>';
        echo '<a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '" rel="noreferrer" style="color:#007bff; text-decoration:underline;">Click here if you are not redirected automatically</a>.</p>';
        echo '</body></html>';
        exit;
    }
}

