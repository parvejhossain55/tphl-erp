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
            $rawJson = trim($data['data']);
            $parsedPayload = json_decode($rawJson, true);
            if (!is_array($parsedPayload)) {
                $parsedPayload = json_decode(stripslashes($rawJson), true);
            }
            if (is_array($parsedPayload)) {
                $data = array_merge($data, $parsedPayload);
            }
        }

        // Support direct options array or options JSON from $_POST or $_REQUEST
        if (empty($data['options'])) {
            if (!empty($_POST['options'])) {
                $data['options'] = $_POST['options'];
            } elseif (!empty($_REQUEST['options'])) {
                $data['options'] = $_REQUEST['options'];
            }
        }

        return $data;
    }

    /**
     * Handle Image Upload for banner or option
     */
    protected function handleImageUpload($inputName = 'image', $subfolder = '')
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

        $subpath = 'uploads/polls/' . ($subfolder ? trim($subfolder, '/') . '/' : '');
        $uploadDir = dirname(dirname(__DIR__)) . '/public/' . $subpath;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $prefix = $subfolder ? 'opt_' : 'poll_';
        $fileName = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload image.");
        }

        return $subpath . $fileName;
    }

    /**
     * Handle Option Image upload for a specific index
     * Supports:
     * - option_image_0, option_image_1 ...
     * - option_images[0], option_images[1] ...
     */
    protected function handleOptionImageUpload($index)
    {
        // 1. Check named option_image_{index}
        $singleKey = "option_image_{$index}";
        if (!empty($_FILES[$singleKey]) && $_FILES[$singleKey]['error'] === UPLOAD_ERR_OK) {
            return $this->handleImageUpload($singleKey, 'options');
        }

        // 2. Check array option_images[index]
        if (isset($_FILES['option_images']['name'][$index]) && $_FILES['option_images']['error'][$index] === UPLOAD_ERR_OK) {
            $name = $_FILES['option_images']['name'][$index];
            $tmpName = $_FILES['option_images']['tmp_name'][$index];
            $size = $_FILES['option_images']['size'][$index];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid image format for option #{$index}. Allowed: " . implode(', ', $allowed));
            }

            if ($size > 10 * 1024 * 1024) {
                throw new Exception("Option image size must be less than 10MB.");
            }

            $subpath = 'uploads/polls/options/';
            $uploadDir = dirname(dirname(__DIR__)) . '/public/' . $subpath;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = 'opt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                throw new Exception("Failed to upload option image #{$index}.");
            }

            return $subpath . $fileName;
        }

        return null;
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
            $rawOptions = $data['options'] ?? [];
            if (is_string($rawOptions)) {
                $decoded = json_decode($rawOptions, true);
                if (!is_array($decoded)) {
                    $decoded = json_decode(stripslashes($rawOptions), true);
                }
                if (is_array($decoded)) {
                    $rawOptions = $decoded;
                } else {
                    $rawOptions = array_filter(array_map('trim', explode(',', $rawOptions)));
                }
            }

            if (empty($rawOptions) || count($rawOptions) < 2) {
                return $this->jsonResponse(false, "At least 2 options are required.", null, 400);
            }

            // Normalize options with optional image
            $options = [];
            foreach ($rawOptions as $idx => $opt) {
                $optText = is_array($opt) ? trim($opt['option_text'] ?? '') : trim($opt);
                if ($optText === '') continue;

                $optImage = null;
                // Check if uploaded via file input
                $uploadedOptImg = $this->handleOptionImageUpload($idx);
                if ($uploadedOptImg) {
                    $optImage = $uploadedOptImg;
                } elseif (is_array($opt) && !empty($opt['image'])) {
                    $optImage = trim($opt['image']);
                }

                $options[] = [
                    'option_text' => $optText,
                    'image'       => $optImage,
                    'sort_order'  => is_array($opt) && isset($opt['sort_order']) ? (int)$opt['sort_order'] : (int)$idx
                ];
            }

            if (count($options) < 2) {
                return $this->jsonResponse(false, "At least 2 non-empty options are required.", null, 400);
            }

            if (empty($data['title'])) {
                return $this->jsonResponse(false, "Poll title is required.", null, 400);
            }

            $pollId = $this->pollModel->createPoll($data, $options);
            $poll = $this->pollModel->getPollById($pollId, null, true);

            // Dispatch Firebase Push Notification to Cloud Run service
            $this->sendFirebasePushNotification($poll);

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
                $rawOptions = $data['options'];
                if (is_string($rawOptions)) {
                    $decoded = json_decode($rawOptions, true);
                    if (!is_array($decoded)) {
                        $decoded = json_decode(stripslashes($rawOptions), true);
                    }
                    if (is_array($decoded)) {
                        $rawOptions = $decoded;
                    } else {
                        $rawOptions = array_filter(array_map('trim', explode(',', $rawOptions)));
                    }
                }

                if (is_array($rawOptions)) {
                    $options = [];
                    foreach ($rawOptions as $idx => $opt) {
                        $optText = is_array($opt) ? trim($opt['option_text'] ?? '') : trim($opt);
                        if ($optText === '') continue;

                        $optImage = null;
                        $uploadedOptImg = $this->handleOptionImageUpload($idx);
                        if ($uploadedOptImg) {
                            $optImage = $uploadedOptImg;
                        } elseif (is_array($opt) && !empty($opt['image'])) {
                            $optImage = trim($opt['image']);
                        }

                        $options[] = [
                            'option_text' => $optText,
                            'image'       => $optImage,
                            'sort_order'  => is_array($opt) && isset($opt['sort_order']) ? (int)$opt['sort_order'] : (int)$idx
                        ];
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

    /**
     * GET /api/polls/{id}/voters
     * Optional ?option_id=X to filter by option
     */
    public function getVoters($pollId)
    {
        try {
            $pollId = (int)$pollId;
            $data = $this->getRequestData();
            $optionId = !empty($data['option_id']) ? (int)$data['option_id'] : null;

            $voters = $this->pollModel->getPollVoters($pollId, $optionId);
            return $this->jsonResponse(true, "Voters retrieved successfully.", $voters);
        } catch (Exception $e) {
            return $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Dispatch Firebase Push Notification via Cloud Run on Poll creation
     */
    protected function sendFirebasePushNotification($poll)
    {
        try {
            $url = 'https://sendprojectnoticepush-408578905351.asia-southeast1.run.app';

            // projectId null or empty passes 'all', which triggers 'all_users' topic in Cloud Run
            $projectId   = !empty($poll['project_id']) ? (string)$poll['project_id'] : 'all';
            $projectName = !empty($poll['project_name']) ? (string)$poll['project_name'] : 'TPHL Projects';
            $title       = !empty($poll['title']) ? (string)$poll['title'] : 'নতুন ভোটিং';
            $body        = !empty($poll['description']) 
                            ? (string)$poll['description'] 
                            : 'নতুন পোলে আপনার মূল্যবান মতামত বা ভোট দিন!';

            $payload = [
                'secret'       => 'TPHL_Secure_Secret_549320abcedf184759',
                'type'         => 'poll',
                'poll_id'      => (string)($poll['id'] ?? ''),
                'project_id'   => $projectId,
                'project_name' => $projectName,
                'title'        => $title,
                'body'         => $body,
            ];

            if (!empty($poll['image_url'])) {
                $payload['project_image'] = (string)$poll['image_url'];
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-tphl-secret: TPHL_Secure_Secret_549320abcedf184759'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode >= 400) {
                error_log("Firebase Push Notification error [Poll #{$poll['id']}]: HTTP {$httpCode} - {$curlError} - Response: {$response}");
            }
        } catch (\Throwable $e) {
            // Fail-safe: Notification issue will not disrupt poll creation
            error_log("Firebase Push Notification exception [Poll #" . ($poll['id'] ?? '') . "]: " . $e->getMessage());
        }
    }
}
