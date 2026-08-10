<?php

class SwitchRoleController extends Controller
{
    protected $db;
    protected $userModel;
    protected $roleModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->roleModel = new Role($this->db);
    }

    public function switchRole()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || empty($_SESSION['user']['secondary_role_id'])) {
            header("Location: {$GLOBALS['base_url']}");
            exit;
        }

        $currentRoleId = $_SESSION['user']['role_id'];
        $primaryRoleId = $_SESSION['user']['primary_role_id'];
        $secondaryRoleId = $_SESSION['user']['secondary_role_id'];

        // Determine which role to switch to
        $newRoleId = ($currentRoleId == $primaryRoleId) ? $secondaryRoleId : $primaryRoleId;

        // Fetch the role details to get the role name
        $roleInfo = $this->roleModel->getRoleById($newRoleId);
        
        if ($roleInfo) {
            $_SESSION['user']['role_id'] = $newRoleId;
            $_SESSION['user']['role_name'] = strtolower($roleInfo['name'] ?? '');
            
            // Re-fetch permissions for the new role
            $_SESSION['user']['permissions'] = $this->userModel->getPermissionsByRole($newRoleId);
        }

        redirectByRole();
    }
}
