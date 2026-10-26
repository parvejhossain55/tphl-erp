<?php

class CedPollController extends Controller
{
    protected $db;
    protected $pollModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pollModel = new CedPoll($this->db);
    }

    /**
     * Render the CED Project Polls page in ERP
     */
    public function viewPage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (function_exists('isClientsCommunicated') && isClientsCommunicated()) {
            header("Location: {$GLOBALS['base_url']}/client-wise-due-tracking");
            exit;
        }

        // Fetch active projects from database
        $projects = [];
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        if ($res) {
            $projects = $res->fetch_all(MYSQLI_ASSOC);
        }

        $this->view("ced/ced-project-polls", [
            "current_route" => "ced-project-polls",
            "page_title"    => "Project Polls",
            "projects"      => $projects,
        ]);
    }

    /**
     * Send standard JSON response
     */
    protected function jsonResponse($success, $message, $data = null, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Api-Key');

        $response = [
            'success' => (bool)$success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Extract request payload from JSON or Form-Data
     */
    protected function getRequestData()
    {
        $raw = file_get_contents('php://input');
        $jsonData = json_decode($raw, true) ?: [];
        $data = array_merge($_GET, $_POST, $jsonData);

        // If 'data' parameter contains a JSON string (common in multipart form-data)
        if (!empty($data['data']) && is_string($data['data'])) {
            $parsedPayload = json_decode($data['data'], true);
            if (is_array($parsedPayload)) {
                $data = array_merge($data, $parsedPayload);
            }
        }

        return $data;
    }

    /**
     * Handle Image Upload
     */
    protected function handleImageUpload($inputName = 'image')
    {
        if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$inputName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid image format. Allowed: " . implode(', ', $allowed));
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            throw new Exception("Image size must be less than 10MB.");
        }

        $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/polls/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'poll_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload image.");
        }

        return 'uploads/polls/' . $fileName;
    }

    /**
     * GET /api/polls or POST /api/polls
     */
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->create();
        }

        try {
            $data = $this->getRequestData();
            $filters = [];

            if (!empty($data['project_id'])) {
                $filters['project_id'] = (int)$data['project_id'];
            }
            if (!empty($data['status'])) {
                $filters['status'] = $data['status'];
            }

            $clientId = !empty($data['client_id']) ? (int)$data['client_id'] : null;
            $isAdmin = !empty($data['is_admin']) && in_array($data['is_admin'], [1, '1', true, 'true'], true);

            $polls = $this->pollModel->getAllPolls($filters, $clientId, $isAdmin);
            return $this->jsonResponse(true, "Polls retrieved successfully.", $polls);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/polls/{id}
     */
    public function show($id)
    {
        try {
            $data = $this->getRequestData();
            $clientId = !empty($data['client_id']) ? (int)$data['client_id'] : null;
            $isAdmin = !empty($data['is_admin']) && in_array($data['is_admin'], [1, '1', true, 'true'], true);

            $poll = $this->pollModel->getPollById((int)$id, $clientId, $isAdmin);
            if (!$poll) {
                return $this->jsonResponse(false, "Poll not found.", null, 404);
            }

            return $this->jsonResponse(true, "Poll details retrieved.", $poll);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/polls/project/{projectId}
     */
    public function getByProject($projectId)
    {
        try {
            $data = $this->getRequestData();
            $filters = [
                'project_id' => (int)$projectId,
            ];
            if (!empty($data['status'])) {
                $filters['status'] = $data['status'];
            }

            $clientId = !empty($data['client_id']) ? (int)$data['client_id'] : null;
            $isAdmin = !empty($data['is_admin']) && in_array($data['is_admin'], [1, '1', true, 'true'], true);

            $polls = $this->pollModel->getAllPolls($filters, $clientId, $isAdmin);
            return $this->jsonResponse(true, "Project polls retrieved.", $polls);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * POST /api/polls/create or POST /api/polls
     */
    public function create()
    {
        try {
            $data = $this->getRequestData();

            // Handle image upload if submitted as file
            $uploadedImage = $this->handleImageUpload('image');
            if ($uploadedImage) {
                $data['image'] = $uploadedImage;
            }

            if (empty($data['created_by'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $data['created_by'] = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
            }

            // Parse options (supports array or JSON-string)
            $options = $data['options'] ?? [];
            if (is_string($options)) {
                $decoded = json_decode($options, true);
                if (is_array($decoded)) {
                    $options = $decoded;
                } else {
                    $options = array_filter(array_map('trim', explode(',', $options)));
                }
            }

            if (empty($options) || count($options) < 2) {
                return $this->jsonResponse(false, "At least 2 options are required.", null, 400);
            }

            if (empty($data['title'])) {
                return $this->jsonResponse(false, "Poll title is required.", null, 400);
            }

            $pollId = $this->pollModel->createPoll($data, $options);
            $poll = $this->pollModel->getPollById($pollId, null, true);

            return $this->jsonResponse(true, "Poll created successfully.", $poll, 201);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/polls/update/{id}
     */
    public function update($id)
    {
        try {
            $id = (int)$id;
            $data = $this->getRequestData();

            // Handle optional new image upload
            $uploadedImage = $this->handleImageUpload('image');
            if ($uploadedImage) {
                $data['image'] = $uploadedImage;
            }

            // Parse options if provided
            $options = null;
            if (isset($data['options'])) {
                $options = $data['options'];
                if (is_string($options)) {
                    $decoded = json_decode($options, true);
                    if (is_array($decoded)) {
                        $options = $decoded;
                    } else {
                        $options = array_filter(array_map('trim', explode(',', $options)));
                    }
                }
            }

            $updated = $this->pollModel->updatePoll($id, $data, $options);
            return $this->jsonResponse(true, "Poll updated successfully.", $updated);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/polls/delete/{id} or DELETE /api/polls/{id}
     */
    public function delete($id)
    {
        try {
            $id = (int)$id;
            $this->pollModel->deletePoll($id);
            return $this->jsonResponse(true, "Poll deleted successfully.");
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/polls/vote
     * Allows voting or updating previous vote. Returns refreshed poll stats.
     */
    public function vote($pollId = null)
    {
        try {
            $data = $this->getRequestData();

            $pId = !empty($pollId) ? (int)$pollId : (int)($data['poll_id'] ?? 0);
            $optionId = (int)($data['option_id'] ?? 0);
            $clientId = (int)($data['client_id'] ?? 0);
            $remarks = !empty($data['remarks']) ? trim($data['remarks']) : null;

            if (!$pId) {
                return $this->jsonResponse(false, "poll_id is required.", null, 400);
            }
            if (!$optionId) {
                return $this->jsonResponse(false, "option_id is required.", null, 400);
            }
            if (!$clientId) {
                return $this->jsonResponse(false, "client_id is required.", null, 400);
            }

            $pollWithResults = $this->pollModel->castVote($pId, $optionId, $clientId, $remarks);

            return $this->jsonResponse(true, "Vote recorded successfully.", $pollWithResults);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 400);
        }
    }
}
