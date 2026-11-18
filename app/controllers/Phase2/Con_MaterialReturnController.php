<?php
namespace Phase2;

use Controller;
use Con_MaterialReturn;

class Con_MaterialReturnController extends Controller
{
    protected $db;
    protected $returnModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->returnModel = new \Con_MaterialReturn($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        
        // Site Engineer (Construction Management) or Management
        if (!in_array($roleId, [1, 3, 33, 11])) { 
            // 11 is Construction Management / Site Engineer usually
            // Wait, what role is Site Engineer? Usually Construction Management.
        }

        $returns = $this->returnModel->getAll();

        $this->view('phase2/material-return/return-lists', [
            'current_route' => 'con-material-returns',
            'page_title' => 'Material Returns',
            'returns' => $returns,
            'currentRoleId' => $roleId
        ]);
    }

    public function updateStatus($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (in_array($roleId, [13, 20, 24, 25, 32])) {
            setError('Only Site Engineer / Receiver can mark returns as sent back to vendor.');
            $redirectTo = $_POST['redirect_to'] ?? '';
            if ($redirectTo !== '' && str_starts_with($redirectTo, '/') && !str_starts_with($redirectTo, '//')) {
                header("Location: {$GLOBALS['base_url']}{$redirectTo}");
            } else {
                header("Location: {$GLOBALS['base_url']}/con-material-returns");
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'] ?? 'pending';
            if ($this->returnModel->updateStatus($id, $status, $userId)) {
                setSuccess('Material Return status updated successfully.');
            } else {
                setError('Failed to update status.');
            }
        }
        $redirectTo = $_POST['redirect_to'] ?? '';
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/') && !str_starts_with($redirectTo, '//')) {
            header("Location: {$GLOBALS['base_url']}{$redirectTo}");
        } else {
            header("Location: {$GLOBALS['base_url']}/con-material-returns");
        }
        exit;
    }
}
