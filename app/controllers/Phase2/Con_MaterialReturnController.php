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
            'returns' => $returns
        ]);
    }

    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'] ?? 'pending';
            if ($this->returnModel->updateStatus($id, $status)) {
                setSuccess('Material Return status updated successfully.');
            } else {
                setError('Failed to update status.');
            }
        }
        header("Location: {$GLOBALS['base_url']}/con-material-returns");
        exit;
    }
}
