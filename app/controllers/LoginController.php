<?php
class LoginController extends Controller
{
    protected $db;

    protected $userModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
    }


    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $message = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                $message = "Email and Password are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid Email Format!";
            } else {

                $user = $this->userModel->getByEmail($email);

                if ($user && password_verify($password, $user['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        "id" => $user['id'],
                        "full_name" => $user['full_name'],
                        "user_name" => $user['user_name'],
                        "email" => $user['email'],
                        "role_id" => $user['role_id'],
                        "team_id" => $user['team_id'] ?? null,
                        "employee_id" => $user['employee_id'] ?? null,
                        "role_name" => strtolower($user['role_name'] ?? ''),
                        "designation_name" => strtolower($user['designation_name'] ?? ''),
                        "department_name" => strtolower($user['department_name'] ?? ''),
                        "department_id" => $user['department_id'] ?? null,
                        "designation_id" => $user['designation_id'] ?? null,
                    ];

                    $_SESSION['user']['permissions'] =
                        $this->userModel->getPermissionsByRole($user['role_id']);

                    redirectByRole();
                } else {
                    $message = "Invalid Email or Password!";
                }
            }
        }

        $this->view("auth/login", [
            "current_route" => "admin-login",
            "page_title" => "Admin Login",
            "message" => $message,
        ], false);
    }


    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            $stmt = $this->db->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }

        // Destroy session
        session_unset();
        session_destroy();

        // Redirect to login page
        header("Location: {$GLOBALS['base_url']}/admin-login");
        exit;
    }
}
