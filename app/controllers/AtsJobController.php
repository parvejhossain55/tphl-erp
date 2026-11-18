<?php

class AtsJobController extends Controller
{
    protected $db;
    protected $jobModel;
    protected $candidateModel;
    protected $departmentModel;
    protected $dropdownModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->jobModel = new AtsJobPosting($this->db);
        $this->candidateModel = new AtsCandidate($this->db);
        $this->departmentModel = new Department($this->db);
        $this->dropdownModel = new AtsDropdownOption($this->db);
    }

    private function checkAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['user'])) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh and login again.']);
                exit;
            }

            header("Location: " . $GLOBALS['base_url'] . "/admin-login");
            exit;
        }
    }

    private function sendCorsHeaders(string $methods = 'GET, POST, OPTIONS'): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: {$methods}");
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * HR Panel: Job Postings Management Page
     */
    public function index()
    {
        $this->checkAuth();

        $filters = [
            'status'          => trim($_GET['status'] ?? ''),
            'department_id'   => trim($_GET['department_id'] ?? ''),
            'employment_type' => trim($_GET['employment_type'] ?? ''),
            'search'          => trim($_GET['q'] ?? ''),
        ];

        $jobs = $this->jobModel->getAll($filters);
        $stats = $this->jobModel->getStats();

        if (!empty($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'jobs'    => $jobs,
                'stats'   => $stats,
            ]);
            exit;
        }

        $departments = $this->departmentModel->getActive() ?: [];

        $this->view('hrm/ats/job-postings', [
            'current_route' => 'ats-jobs',
            'page_title'    => 'ATS - Job Postings',
            'jobs'          => $jobs,
            'stats'         => $stats,
            'departments'   => $departments,
            'filters'       => $filters,
        ]);
    }

    /**
     * HR Panel: Create Job Posting (AJAX)
     */
    public function store()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $payload = $this->extractJobPayload();
        if (empty($payload['title'])) {
            echo json_encode(['success' => false, 'message' => 'Job title is required.']);
            exit;
        }

        $payload['created_by'] = $_SESSION['user']['id'] ?? null;
        $id = $this->jobModel->create($payload);

        if ($id) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'create',
                'description' => "Created job posting '{$payload['title']}' ({$payload['status']})",
                'details'     => ['job_id' => $id, 'department' => $payload['department_name'], 'status' => $payload['status']]
            ]);
            echo json_encode(['success' => true, 'message' => 'Job posting created successfully!', 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create job posting.']);
        }
        exit;
    }

    /**
     * HR Panel: Get Single Job Posting (AJAX)
     */
    public function getDetails($id)
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $job = $this->jobModel->getById((int) $id);
        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'Job posting not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'job' => $job]);
        exit;
    }

    /**
     * HR Panel: Update Job Posting (AJAX)
     */
    public function update($id)
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $jobId = (int) $id;
        $existing = $this->jobModel->getById($jobId);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Job posting not found.']);
            exit;
        }

        $payload = $this->extractJobPayload();
        if (empty($payload['title'])) {
            echo json_encode(['success' => false, 'message' => 'Job title is required.']);
            exit;
        }

        if ($this->jobModel->update($jobId, $payload)) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'update',
                'description' => "Updated job posting '{$payload['title']}'",
                'details'     => ['job_id' => $jobId, 'status' => $payload['status']]
            ]);
            echo json_encode(['success' => true, 'message' => 'Job posting updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update job posting.']);
        }
        exit;
    }

    /**
     * HR Panel: Quick Status Toggle (AJAX)
     */
    public function updateStatus()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $existing = $this->jobModel->getById($id);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Job posting not found.']);
            exit;
        }

        if ($this->jobModel->updateStatus($id, $status)) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'update',
                'description' => "Changed job posting '{$existing['title']}' status to {$status}",
                'details'     => ['job_id' => $id, 'old_status' => $existing['status'], 'new_status' => $status]
            ]);
            echo json_encode(['success' => true, 'message' => "Status updated to {$status}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid status or update failed.']);
        }
        exit;
    }

    /**
     * HR Panel: Delete Job Posting (AJAX)
     */
    public function delete()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $existing = $this->jobModel->getById($id);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Job posting not found.']);
            exit;
        }

        if ($this->jobModel->delete($id)) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'delete',
                'description' => "Deleted job posting '{$existing['title']}'",
                'details'     => ['job_id' => $id]
            ]);
            echo json_encode(['success' => true, 'message' => 'Job posting deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete job posting.']);
        }
        exit;
    }

    /**
     * Public Website API: List Published Jobs
     * GET /api/jobs?department_id=&employment_type=
     */
    public function publicJobs()
    {
        $this->sendCorsHeaders('GET, OPTIONS');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $filters = [
            'department_id'   => $_GET['department_id'] ?? '',
            'department'      => $_GET['department'] ?? '',
            'employment_type' => $_GET['employment_type'] ?? '',
        ];

        $jobs = $this->jobModel->getPublished($filters);
        foreach ($jobs as &$job) {
            $job['id'] = (int) $job['id'];
            $job['department_id'] = $job['department_id'] !== null ? (int) $job['department_id'] : null;
            $job['vacancies'] = (int) $job['vacancies'];
            $job['deadline_formatted'] = !empty($job['deadline'])
                ? date('M d, Y', strtotime($job['deadline']))
                : null;
        }
        unset($job);

        echo json_encode([
            'success' => true,
            'count'   => count($jobs),
            'data'    => $jobs,
        ]);
        exit;
    }

    /**
     * Public Website API: Submit Job Application
     * POST /api/jobs/apply (supports multipart/form-data with PDF/DOC/DOCX or application/json)
     */
    public function publicApply()
    {
        $this->sendCorsHeaders('POST, OPTIONS');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input = $_POST;
        if (empty($input) && empty($_FILES)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $jobId = (int) ($input['job_id'] ?? 0);
        $fullName = trim($input['full_name'] ?? $input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $resumeLink = trim($input['resume_link'] ?? $input['cv_drive_link'] ?? '');
        $coverNote = trim($input['cover_note'] ?? $input['remarks'] ?? '');

        if ($fullName === '' || $email === '' || $phone === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Full name, email address, and phone number are required.'
            ]);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $job = null;
        if ($jobId > 0) {
            $job = $this->jobModel->getById($jobId);
            if (!$job || $job['status'] !== 'Published') {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'This job position is no longer accepting applications.']);
                exit;
            }

            if (!empty($job['deadline']) && $job['deadline'] < date('Y-m-d')) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'The application deadline for this position has passed.']);
                exit;
            }
        }

        // Handle optional local file upload (PDF, DOC, DOCX)
        $uploadedFile = $_FILES['resume_file'] ?? $_FILES['cv_file'] ?? $_FILES['file'] ?? null;
        $cvFilePath = null;

        if ($uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Failed to upload resume file. Please try again.']);
                exit;
            }
            if (($uploadedFile['size'] ?? 0) > 10 * 1024 * 1024) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Resume file size must be 10 MB or less.']);
                exit;
            }
            $cvFilePath = $this->handlePublicResumeUpload($uploadedFile);
            if (!$cvFilePath) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed formats: PDF, DOC, DOCX.']);
                exit;
            }
        }

        if ($cvFilePath === null && $resumeLink === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Please provide a resume/portfolio link or attach a PDF/DOC/DOCX file.'
            ]);
            exit;
        }

        if ($jobId > 0 && $this->jobModel->hasCandidateApplied($jobId, $email, $phone)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'You have already applied for this position.'
            ]);
            exit;
        }

        $positionTitle = $job ? $job['title'] : (trim($input['position'] ?? '') ?: 'General Application');
        $departmentName = $job ? ($job['department_name'] ?: 'General') : (trim($input['department'] ?? '') ?: 'General');
        $this->dropdownModel->ensureOptionExists('position', $positionTitle, null);
        $this->dropdownModel->ensureOptionExists('department', $departmentName, null);
        $this->dropdownModel->ensureOptionExists('source', 'Website Career Page', null);

        $cvSource = $cvFilePath ? 'file' : 'drive_link';
        $candidateId = $this->candidateModel->create([
            'job_id'        => $jobId > 0 ? $jobId : null,
            'name'          => $fullName,
            'email'         => $email,
            'phone'         => $phone,
            'position'      => $positionTitle,
            'department'    => $departmentName,
            'source'        => 'Website Career Page',
            'remarks'       => $coverNote !== '' ? $coverNote : null,
            'cv_source'     => $cvSource,
            'cv_file_path'  => $cvFilePath,
            'cv_drive_link' => $resumeLink !== '' ? $resumeLink : null,
            'entry_mode'    => 'website',
        ]);

        if (!$candidateId) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not submit application. Please try again later.']);
            exit;
        }

        $logDescription = $jobId > 0
            ? "Website application received for '{$positionTitle}' from '{$fullName}'"
            : "Website general resume drop received from '{$fullName}'";

        AtsActivityLog::log($this->db, [
            'action_type'    => 'create',
            'candidate_id'   => $candidateId,
            'candidate_name' => $fullName,
            'description'    => $logDescription,
            'details'        => ['job_id' => $jobId > 0 ? $jobId : null, 'source' => 'Website Career Page', 'cv_source' => $cvSource]
        ]);

        http_response_code(201);
        echo json_encode([
            'success'      => true,
            'message'      => 'Application submitted successfully!',
            'candidate_id' => $candidateId,
        ]);
        exit;
    }

    private function extractJobPayload(): array
    {
        $deptId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        $deptName = trim($_POST['department_name'] ?? '');

        if ($deptId && $deptName === '') {
            $dept = $this->departmentModel->getById($deptId);
            if ($dept) {
                $deptName = $dept['name'];
            }
        }

        return [
            'title'           => trim($_POST['title'] ?? ''),
            'department_id'   => $deptId,
            'department_name' => $deptName,
            'employment_type' => trim($_POST['employment_type'] ?? 'Full Time'),
            'location'        => trim($_POST['location'] ?? 'Gulshan, Dhaka'),
            'salary_range'    => trim($_POST['salary_range'] ?? ''),
            'deadline'        => trim($_POST['deadline'] ?? ''),
            'vacancies'       => (int) ($_POST['vacancies'] ?? 1),
            'description'     => trim($_POST['description'] ?? ''),
            'requirements'    => trim($_POST['requirements'] ?? ''),
            'status'          => trim($_POST['status'] ?? 'Draft'),
        ];
    }

    private function handlePublicResumeUpload(array $file): ?string
    {
        $targetDir = BASE_PATH . '/public/uploads/ats_cvs/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $fileName = 'cv_web_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/ats_cvs/' . $fileName;
        }

        return null;
    }
}
