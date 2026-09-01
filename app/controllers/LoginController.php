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

    /**
     * Helper to get client real IP address, accounting for reverse proxies
     */
    private function getClientIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return trim($ip);
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
            $ip = $this->getClientIp();

            if (empty($email) || empty($password)) {
                $message = "Email and Password are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid Email Format!";
            } else {
                // Rate Limiting: Check failed attempts in the last 1 minute
                $stmtAttempts = $this->db->prepare("
                    SELECT COUNT(*) AS total 
                    FROM login_attempts 
                    WHERE (ip_address = ? OR username = ?) 
                      AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ");
                $stmtAttempts->bind_param("ss", $ip, $email);
                $stmtAttempts->execute();
                $resultAttempts = $stmtAttempts->get_result()->fetch_assoc();
                $totalAttempts = (int)($resultAttempts['total'] ?? 0);
                $stmtAttempts->close();

                if ($totalAttempts >= 5) {
                    $message = "Too many login attempts. Please try again in 1 minute.";
                } else {
                    $user = $this->userModel->getByEmail($email);

                    if ($user && password_verify($password, $user['password'])) {
                        // Check if employee status is inactive, resigned, or terminated
                        if (!empty($user['employee_status']) && in_array(strtolower($user['employee_status']), ['inactive', 'resigned', 'terminated'])) {
                            $message = "Your employee status is " . ucfirst($user['employee_status']) . ". Access denied!";
                        } else {
                            // Clear login attempts for this IP / email upon successful login
                            $stmtClear = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?");
                            if ($stmtClear !== false) {
                                $stmtClear->bind_param("ss", $ip, $email);
                                $stmtClear->execute();
                                $stmtClear->close();
                            }

                            // Garbage collection: Delete logs older than 1 hour
                            $this->db->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

                            session_regenerate_id(true);

                            // Log user activity session
                            $userId = $user['id'];
                            $sessionId = session_id();
                            $currentTime = date('Y-m-d H:i:s');
                            $stmtAct = $this->db->prepare("INSERT INTO user_activity_logs (user_id, login_time, last_active_time, session_id) VALUES (?, ?, ?, ?)");
                            if ($stmtAct !== false) {
                                $stmtAct->bind_param("isss", $userId, $currentTime, $currentTime, $sessionId);
                                $stmtAct->execute();
                                $_SESSION['activity_log_id'] = $stmtAct->insert_id;
                                $stmtAct->close();
                            }

                            $_SESSION['user'] = [
                                "id" => $user['id'],
                                "full_name" => $user['full_name'],
                                "user_name" => $user['user_name'],
                                "email" => $user['email'],
                                "role_id" => $user['role_id'],
                                "primary_role_id" => $user['role_id'],
                                "secondary_role_id" => $user['secondary_role_id'] ?? null,
                                "team_id" => $user['emp_team_id'] ?? $user['team_id'] ?? null,
                                "employee_id" => $user['employee_id'] ?? null,
                                "role_name" => strtolower($user['role_name'] ?? ''),
                                "designation_name" => strtolower($user['designation_name'] ?? ''),
                                "department_name" => strtolower($user['department_name'] ?? ''),
                                "department_id" => $user['department_id'] ?? null,
                                "designation_id" => $user['designation_id'] ?? null,
                                "is_sales_co_leader" => (int)($user['is_sales_co_leader'] ?? 0),
                            ];

                            // Tipsoi Authentication Sync
                            try {
                                $externalApi = new ExternalApiService($this->db);
                                $tipsoiAuth = $externalApi->authenticateUser($user['email'], $password);
                                if (!empty($tipsoiAuth['success'])) {
                                    $_SESSION['user']['tipsoi_authenticated'] = true;
                                    $_SESSION['user']['tipsoi_token'] = $tipsoiAuth['token'];
                                    $_SESSION['user']['tipsoi_token_expires'] = $tipsoiAuth['expires'];
                                    $_SESSION['user']['tipsoi_id'] = $tipsoiAuth['tipsoi_id'] ?? null;
                                } else {
                                    $_SESSION['user']['tipsoi_authenticated'] = false;
                                }
                            } catch (Exception $e) {
                                error_log("Tipsoi Login Sync Error: " . $e->getMessage());
                                $_SESSION['user']['tipsoi_authenticated'] = false;
                            }

                            $_SESSION['user']['permissions'] =
                                $this->userModel->getPermissionsByRole($user['role_id']);

                            redirectByRole();
                        }
                    } else {
                        // Failed login attempt: Log details
                        $stmtLog = $this->db->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
                        if ($stmtLog !== false) {
                            $stmtLog->bind_param("ss", $ip, $email);
                            $stmtLog->execute();
                            $stmtLog->close();
                        }

                        $message = "Invalid Email or Password!";
                    }
                }
            }
        }

        $this->view("auth/login", [
            "current_route" => "admin-login",
            "page_title" => "Admin Login",
            "message" => $message,
        ], false);
    }

    public function updateActivity()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['activity_log_id'])) {
            $logId = (int)$_SESSION['activity_log_id'];
            $currentTime = date('Y-m-d H:i:s');
            
            $stmt = $this->db->prepare("UPDATE user_activity_logs SET last_active_time = ? WHERE id = ?");
            if ($stmt !== false) {
                $stmt->bind_param("si", $currentTime, $logId);
                $stmt->execute();
                $stmt->close();
            }
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        }

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No activity log session found']);
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['activity_log_id'])) {
            $logId = (int)$_SESSION['activity_log_id'];
            $currentTime = date('Y-m-d H:i:s');
            $stmtActClose = $this->db->prepare("UPDATE user_activity_logs SET last_active_time = ? WHERE id = ?");
            if ($stmtActClose !== false) {
                $stmtActClose->bind_param("si", $currentTime, $logId);
                $stmtActClose->execute();
                $stmtActClose->close();
            }
        }

        if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            $stmt = $this->db->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
            if ($stmt !== false) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Destroy session
        session_unset();
        session_destroy();

        // Redirect to login page
        header("Location: {$GLOBALS['base_url']}/admin-login");
        exit;
    }
}
