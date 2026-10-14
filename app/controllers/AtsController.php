<?php

class AtsController extends Controller
{
    protected $db;
    protected $candidateModel;
    protected $dropdownModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->candidateModel = new AtsCandidate($this->db);
        $this->dropdownModel = new AtsDropdownOption($this->db);
    }

    /**
     * Check if user is logged in
     */
    private function checkAuth()
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

    /**
     * Main Spreadsheet Tracker View
     */
    public function index()
    {
        $this->checkAuth();

        $filters = [
            'search' => $_GET['q'] ?? '',
            'tab' => $_GET['tab'] ?? 'all',
            'department' => $_GET['department'] ?? '',
            'position' => $_GET['position'] ?? '',
            'source' => $_GET['source'] ?? '',
            'invitation_status' => $_GET['invitation_status'] ?? '',
            'interview_attendance' => $_GET['interview_attendance'] ?? '',
            'hired' => $_GET['hired'] ?? '',
            'date_type' => $_GET['date_type'] ?? 'interview_date',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 25)));

        $result = $this->candidateModel->getFiltered($filters, $page, $perPage);
        $options = $this->dropdownModel->getAllActiveGrouped();

        $data = [
            'page_title' => 'HR ATS - Candidate Tracker',
            'candidates' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => $result['total_pages'],
            'tabCounts' => $result['counts'],
            'filters' => $filters,
            'options' => $options,
        ];

        $this->view('hrm/ats/tracker', $data);
    }

    /**
     * Single Candidate Store
     */
    public function store()
    {
        $this->checkAuth();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($phone) || empty($email)) {
            $_SESSION['error'] = 'Candidate Name, Phone, and Email are mandatory.';
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        // Handle "Other" inputs
        $department = trim($_POST['department'] ?? '');
        if ($department === 'Other' && !empty($_POST['department_other'])) {
            $department = trim($_POST['department_other']);
            $this->dropdownModel->ensureOptionExists('department', $department, $_SESSION['user']['id'] ?? null);
        }

        $position = trim($_POST['position'] ?? '');
        if ($position === 'Other' && !empty($_POST['position_other'])) {
            $position = trim($_POST['position_other']);
            $this->dropdownModel->ensureOptionExists('position', $position, $_SESSION['user']['id'] ?? null);
        }

        $source = trim($_POST['source'] ?? '');
        if ($source === 'Other' && !empty($_POST['source_other'])) {
            $source = trim($_POST['source_other']);
            $this->dropdownModel->ensureOptionExists('source', $source, $_SESSION['user']['id'] ?? null);
        }

        $location = trim($_POST['interview_location'] ?? '');
        if ($location === 'Other' && !empty($_POST['interview_location_other'])) {
            $location = trim($_POST['interview_location_other']);
            $this->dropdownModel->ensureOptionExists('interview_location', $location, $_SESSION['user']['id'] ?? null);
        }

        // Handle CV upload
        $cvSource = $_POST['cv_source'] ?? 'file';
        $cvFilePath = null;
        $cvDriveLink = !empty($_POST['cv_drive_link']) ? trim($_POST['cv_drive_link']) : null;

        if ($cvSource === 'file' && isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $cvFilePath = $this->handleCvUpload($_FILES['cv_file']);
        }

        $candidateData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'position' => $position,
            'department' => $department,
            'source' => $source,
            'invitation_status' => $_POST['invitation_status'] ?? 'Pending',
            'referred_by' => $_POST['referred_by'] ?? null,
            'remarks' => $_POST['remarks'] ?? null,
            'interview_call_date' => !empty($_POST['interview_call_date']) ? $_POST['interview_call_date'] : null,
            'interview_date' => !empty($_POST['interview_date']) ? $_POST['interview_date'] : null,
            'interview_time' => !empty($_POST['interview_time']) ? $_POST['interview_time'] : null,
            'interview_location' => $location,
            'email_sent' => $_POST['email_sent'] ?? '0',
            'sms_sent' => $_POST['sms_sent'] ?? '0',
            'interview_attendance' => $_POST['interview_attendance'] ?? 'pending',
            'cv_source' => $cvSource,
            'cv_file_path' => $cvFilePath,
            'cv_drive_link' => $cvDriveLink,
            'hired' => $_POST['hired'] ?? 'pending',
            'joining_date' => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
            'onboarding_salary' => !empty($_POST['onboarding_salary']) ? $_POST['onboarding_salary'] : null,
            'entry_mode' => 'single',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        $insertedId = $this->candidateModel->create($candidateData);

        if ($insertedId) {
            $_SESSION['success'] = "Candidate '{$name}' created successfully.";
        } else {
            $_SESSION['error'] = "Failed to create candidate. Please try again.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
        exit;
    }

    /**
     * Get candidate details for JSON modal
     */
    public function getDetails($id)
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $candidate = $this->candidateModel->getById((int)$id);
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $candidate]);
        exit;
    }

    /**
     * Update Candidate
     */
    public function update($id)
    {
        $this->checkAuth();

        $candidate = $this->candidateModel->getById((int)$id);
        if (!$candidate) {
            $_SESSION['error'] = 'Candidate not found.';
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($phone) || empty($email)) {
            $_SESSION['error'] = 'Candidate Name, Phone, and Email are mandatory.';
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        // Handle "Other" inputs
        $department = trim($_POST['department'] ?? '');
        if ($department === 'Other' && !empty($_POST['department_other'])) {
            $department = trim($_POST['department_other']);
            $this->dropdownModel->ensureOptionExists('department', $department, $_SESSION['user']['id'] ?? null);
        }

        $position = trim($_POST['position'] ?? '');
        if ($position === 'Other' && !empty($_POST['position_other'])) {
            $position = trim($_POST['position_other']);
            $this->dropdownModel->ensureOptionExists('position', $position, $_SESSION['user']['id'] ?? null);
        }

        $source = trim($_POST['source'] ?? '');
        if ($source === 'Other' && !empty($_POST['source_other'])) {
            $source = trim($_POST['source_other']);
            $this->dropdownModel->ensureOptionExists('source', $source, $_SESSION['user']['id'] ?? null);
        }

        $location = trim($_POST['interview_location'] ?? '');
        if ($location === 'Other' && !empty($_POST['interview_location_other'])) {
            $location = trim($_POST['interview_location_other']);
            $this->dropdownModel->ensureOptionExists('interview_location', $location, $_SESSION['user']['id'] ?? null);
        }

        // Handle CV upload
        $cvSource = $_POST['cv_source'] ?? $candidate['cv_source'];
        $cvFilePath = $candidate['cv_file_path'];
        $cvDriveLink = !empty($_POST['cv_drive_link']) ? trim($_POST['cv_drive_link']) : null;

        if ($cvSource === 'file' && isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $cvFilePath = $this->handleCvUpload($_FILES['cv_file']);
        }

        $updateData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'position' => $position,
            'department' => $department,
            'source' => $source,
            'invitation_status' => $_POST['invitation_status'] ?? 'Pending',
            'referred_by' => $_POST['referred_by'] ?? null,
            'remarks' => $_POST['remarks'] ?? null,
            'interview_call_date' => !empty($_POST['interview_call_date']) ? $_POST['interview_call_date'] : null,
            'interview_date' => !empty($_POST['interview_date']) ? $_POST['interview_date'] : null,
            'interview_time' => !empty($_POST['interview_time']) ? $_POST['interview_time'] : null,
            'interview_location' => $location,
            'email_sent' => $_POST['email_sent'] ?? '0',
            'sms_sent' => $_POST['sms_sent'] ?? '0',
            'interview_attendance' => $_POST['interview_attendance'] ?? 'pending',
            'cv_source' => $cvSource,
            'cv_file_path' => $cvFilePath,
            'cv_drive_link' => $cvDriveLink,
            'hired' => $_POST['hired'] ?? 'pending',
            'joining_date' => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
            'onboarding_salary' => !empty($_POST['onboarding_salary']) ? $_POST['onboarding_salary'] : null,
        ];

        $updated = $this->candidateModel->update((int)$id, $updateData);

        if ($updated) {
            $_SESSION['success'] = "Candidate record updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update candidate record.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
        exit;
    }

    /**
     * Fast 1-Click Quick Update (AJAX)
     */
    public function quickUpdate()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        $field = trim($_POST['field'] ?? '');
        $value = trim($_POST['value'] ?? '');

        if (!$id || empty($field)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $updated = $this->candidateModel->updateField($id, $field, $value);

        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }

    /**
     * Bulk Action Update (AJAX / POST)
     */
    public function bulkUpdate()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $ids = $_POST['ids'] ?? [];
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No candidates selected']);
            exit;
        }

        $action = trim($_POST['action'] ?? '');
        $updates = [];

        if ($action === 'delete') {
            $affected = $this->candidateModel->bulkDelete($ids);
            echo json_encode([
                'success' => true,
                'message' => "Successfully deleted {$affected} candidate(s)."
            ]);
            exit;
        } elseif ($action === 'communication') {
            if (isset($_POST['email_sent'])) $updates['email_sent'] = (int)$_POST['email_sent'];
            if (isset($_POST['sms_sent'])) $updates['sms_sent'] = (int)$_POST['sms_sent'];
        } elseif ($action === 'invitation_status' && !empty($_POST['invitation_status'])) {
            $updates['invitation_status'] = $_POST['invitation_status'];
        } elseif ($action === 'interview_attendance' && !empty($_POST['interview_attendance'])) {
            $updates['interview_attendance'] = $_POST['interview_attendance'];
        } elseif ($action === 'schedule') {
            if (!empty($_POST['interview_date'])) $updates['interview_date'] = $_POST['interview_date'];
            if (!empty($_POST['interview_location'])) $updates['interview_location'] = $_POST['interview_location'];
        } elseif ($action === 'hired' && !empty($_POST['hired'])) {
            $updates['hired'] = $_POST['hired'];
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No update values provided']);
            exit;
        }

        $affected = $this->candidateModel->bulkUpdate($ids, $updates);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated {$affected} candidate(s)."
        ]);
        exit;
    }

    /**
     * Delete Candidate (AJAX / POST)
     */
    public function delete()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
            exit;
        }

        $candidate = $this->candidateModel->getById($id);
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            exit;
        }

        // Clean up uploaded CV file if exists
        if (!empty($candidate['cv_file_path'])) {
            $filePath = BASE_PATH . '/public/' . $candidate['cv_file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $deleted = $this->candidateModel->delete($id);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => "Candidate '{$candidate['name']}' deleted successfully."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete candidate.']);
        }
        exit;
    }

    /**
     * Batch CSV Upload
     */
    public function csvUpload()
    {
        $this->checkAuth();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please upload a valid CSV file.';
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($fileTmp, "r");

        if (!$handle) {
            $_SESSION['error'] = 'Could not open uploaded file.';
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        // Read header
        $header = fgetcsv($handle, 1000, ",");
        if (!$header) {
            $_SESSION['error'] = 'CSV file is empty.';
            fclose($handle);
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        // Clean headers to lowercase
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $col))));
            $headerMap[$clean] = $idx;
        }

        $requiredCols = ['name', 'phone', 'email', 'position', 'department'];
        foreach ($requiredCols as $req) {
            if (!isset($headerMap[$req])) {
                $_SESSION['error'] = "Missing required column in CSV: '{$req}'. Please download the sample CSV template.";
                fclose($handle);
                header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
                exit;
            }
        }

        $successCount = 0;
        $skippedCount = 0;

        while (($row = fgetcsv($handle, 2000, ",")) !== false) {
            $name = trim($row[$headerMap['name']] ?? '');
            $phone = trim($row[$headerMap['phone']] ?? '');
            $email = trim($row[$headerMap['email']] ?? '');
            $position = trim($row[$headerMap['position']] ?? 'Not Specified');
            $department = trim($row[$headerMap['department']] ?? 'General');

            if (empty($name) || empty($phone) || empty($email)) {
                $skippedCount++;
                continue;
            }

            $source = isset($headerMap['source']) ? trim($row[$headerMap['source']] ?? '') : 'Walk-in';
            $invitationStatus = isset($headerMap['invitation_status']) ? trim($row[$headerMap['invitation_status']] ?? '') : 'Pending';
            $interviewDate = isset($headerMap['interview_date']) && !empty($row[$headerMap['interview_date']]) ? date('Y-m-d', strtotime($row[$headerMap['interview_date']])) : null;
            $interviewLocation = isset($headerMap['interview_location']) ? trim($row[$headerMap['interview_location']] ?? '') : null;
            $remarks = isset($headerMap['remarks']) ? trim($row[$headerMap['remarks']] ?? '') : null;

            $candidateData = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'position' => $position,
                'department' => $department,
                'source' => $source,
                'invitation_status' => !empty($invitationStatus) ? $invitationStatus : 'Pending',
                'interview_date' => $interviewDate,
                'interview_location' => $interviewLocation,
                'remarks' => $remarks,
                'entry_mode' => 'csv',
                'created_by' => $_SESSION['user']['id'] ?? null,
            ];

            if ($this->candidateModel->create($candidateData)) {
                $successCount++;
            } else {
                $skippedCount++;
            }
        }

        fclose($handle);

        $_SESSION['success'] = "CSV Upload completed: {$successCount} candidates imported successfully" . ($skippedCount > 0 ? " ({$skippedCount} skipped due to missing mandatory fields)." : ".");
        header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
        exit;
    }

    /**
     * Download Sample CSV Template
     */
    public function downloadSampleCsv()
    {
        $this->checkAuth();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tphl_ats_candidates_sample.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Phone', 'Email', 'Position', 'Department', 'Source', 'Invitation_Status', 'Interview_Date', 'Interview_Location', 'Remarks']);

        // Sample Rows
        fputcsv($output, ['Md. Rakib Hasan', '01712345678', 'rakib@example.com', 'Site Engineer', 'Engineering, Construction & Design', 'Walk-in', 'Yes', date('Y-m-d', strtotime('+3 days')), 'Ashulia Site', 'Walk-in applicant']);
        fputcsv($output, ['Sabbir Ahmed', '01812345678', 'sabbir@example.com', 'Accountant', 'Finance & Accounts', 'Bdjobs', 'Didn\'t Pick', '', '', 'Follow up tomorrow']);

        fclose($output);
        exit;
    }

    /**
     * Master Data Settings Page (Self-service for HR)
     */
    public function dropdownSettings()
    {
        $this->checkAuth();

        $groupedOptions = $this->dropdownModel->getAllForManagement();

        $data = [
            'page_title' => 'HR ATS - Dropdown Settings',
            'groupedOptions' => $groupedOptions,
        ];

        $this->view('hrm/ats/dropdown-settings', $data);
    }

    /**
     * Save/Create a Dropdown Option
     */
    public function saveDropdownOption()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $category = trim($_POST['category'] ?? '');
        $optionValue = trim($_POST['option_value'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);

        if (empty($category) || empty($optionValue)) {
            echo json_encode(['success' => false, 'message' => 'Category and option value are required.']);
            exit;
        }

        $res = $this->dropdownModel->create($category, $optionValue, $displayOrder, $_SESSION['user']['id'] ?? null);

        if ($res) {
            echo json_encode(['success' => true, 'message' => 'Option added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add option.']);
        }
        exit;
    }

    /**
     * Toggle Option Status (Active / Retired)
     */
    public function toggleDropdownStatus()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid option ID']);
            exit;
        }

        $res = $this->dropdownModel->toggleStatus($id);

        if ($res) {
            echo json_encode(['success' => true, 'message' => 'Option status updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
        }
        exit;
    }

    /**
     * Permanently Delete Dropdown Option (AJAX)
     */
    public function deleteDropdownOption()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid option ID']);
            exit;
        }

        $res = $this->dropdownModel->delete($id);

        if ($res) {
            echo json_encode(['success' => true, 'message' => 'Option deleted permanently.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete option.']);
        }
        exit;
    }

    /**
     * Recruitment Analytics Dashboard
     */
    public function dashboard()
    {
        $this->checkAuth();

        $range = $_GET['range'] ?? 'this_month';
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        if ($range === 'this_week') {
            $fromDate = date('Y-m-d', strtotime('monday this week'));
            $toDate = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($range === 'this_month') {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-t');
        } elseif ($range === 'last_month') {
            $fromDate = date('Y-m-01', strtotime('first day of last month'));
            $toDate = date('Y-m-t', strtotime('last day of last month'));
        } elseif ($range === 'this_year') {
            $fromDate = date('Y-01-01');
            $toDate = date('Y-12-31');
        } elseif ($range === 'all') {
            $fromDate = null;
            $toDate = null;
        }

        $metrics = $this->candidateModel->getDashboardMetrics($fromDate, $toDate);

        $data = [
            'page_title' => 'HR ATS - Recruitment Dashboard',
            'metrics' => $metrics,
            'range' => $range,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ];

        $this->view('hrm/ats/dashboard', $data);
    }

    /**
     * Helper to handle CV file uploads
     */
    private function handleCvUpload(array $file): ?string
    {
        $targetDir = BASE_PATH . '/public/uploads/ats_cvs/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            return null;
        }

        $fileName = 'cv_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/ats_cvs/' . $fileName;
        }

        return null;
    }

    /**
     * Export Candidates to PDF with active filters
     */
    public function exportPdf()
    {
        $this->checkAuth();

        $filters = [
            'search' => $_GET['q'] ?? '',
            'tab' => $_GET['tab'] ?? 'all',
            'department' => $_GET['department'] ?? '',
            'position' => $_GET['position'] ?? '',
            'source' => $_GET['source'] ?? '',
            'invitation_status' => $_GET['invitation_status'] ?? '',
            'interview_attendance' => $_GET['interview_attendance'] ?? '',
            'hired' => $_GET['hired'] ?? '',
            'date_type' => $_GET['date_type'] ?? 'interview_date',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $candidates = $this->candidateModel->getAllFiltered($filters);
        $totalCandidates = count($candidates);

        if (!class_exists('\\Dompdf\\Dompdf')) {
            if (defined('BASE_PATH') && file_exists(BASE_PATH . '/vendor/autoload.php')) {
                require_once BASE_PATH . '/vendor/autoload.php';
            } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
        }

        // Prepare applied filter labels for PDF header summary
        $appliedFilterStrings = [];
        if (!empty($filters['tab']) && $filters['tab'] !== 'all') {
            $appliedFilterStrings[] = "Tab: " . ucfirst(str_replace('_', ' ', $filters['tab']));
        }
        if (!empty($filters['search'])) {
            $appliedFilterStrings[] = 'Search: "' . htmlspecialchars($filters['search']) . '"';
        }
        if (!empty($filters['department'])) {
            $appliedFilterStrings[] = 'Dept: ' . htmlspecialchars($filters['department']);
        }
        if (!empty($filters['position'])) {
            $appliedFilterStrings[] = 'Pos: ' . htmlspecialchars($filters['position']);
        }
        if (!empty($filters['invitation_status'])) {
            $appliedFilterStrings[] = 'Call: ' . htmlspecialchars($filters['invitation_status']);
        }
        if (!empty($filters['interview_attendance'])) {
            $appliedFilterStrings[] = 'Attendance: ' . ucfirst($filters['interview_attendance']);
        }
        if (!empty($filters['hired'])) {
            $appliedFilterStrings[] = 'Hired: ' . ucfirst($filters['hired']);
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $appliedFilterStrings[] = 'Date (' . htmlspecialchars($filters['date_type']) . '): ' . ($filters['date_from'] ?: 'Start') . ' to ' . ($filters['date_to'] ?: 'End');
        }

        $filterSummary = !empty($appliedFilterStrings) ? implode(' | ', $appliedFilterStrings) : 'All Candidates (No Filter)';
        $printedBy = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : (!empty($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'HR Admin');
        $generatedAt = date('d M Y, h:i A');

        // Build HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>ATS Candidate Report</title>
            <style>
                @page { margin: 18px 20px 22px 20px; }
                body {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 9px;
                    color: #1e293b;
                    margin: 0;
                    padding: 0;
                }
                .report-header {
                    text-align: center;
                    border-bottom: 2px solid #078134;
                    padding-bottom: 8px;
                    margin-bottom: 10px;
                }
                .company-name {
                    font-size: 18px;
                    font-weight: bold;
                    color: #078134;
                    letter-spacing: 0.5px;
                    margin: 0 0 3px 0;
                    text-transform: uppercase;
                }
                .report-title {
                    font-size: 12px;
                    font-weight: bold;
                    color: #1e293b;
                    margin: 0 0 5px 0;
                }
                .meta-bar {
                    background-color: #f1f5f9;
                    border: 1px solid #e2e8f0;
                    padding: 5px 10px;
                    margin-bottom: 10px;
                    border-radius: 4px;
                    font-size: 8.5px;
                }
                .meta-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .meta-table td {
                    padding: 0;
                    border: none;
                }
                .table-candidates {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 4px;
                }
                .table-candidates th {
                    background-color: #078134;
                    color: #ffffff;
                    font-weight: bold;
                    font-size: 8.5px;
                    padding: 6px 4px;
                    border: 1px solid #056828;
                    text-align: left;
                    text-transform: uppercase;
                }
                .table-candidates td {
                    border: 1px solid #cbd5e1;
                    padding: 5px 4px;
                    font-size: 8.5px;
                    vertical-align: top;
                }
                .table-candidates tr:nth-child(even) td {
                    background-color: #f8fafc;
                }
                .badge {
                    display: inline-block;
                    padding: 2px 5px;
                    font-size: 7.5px;
                    font-weight: bold;
                    border-radius: 3px;
                    text-align: center;
                }
                .badge-success { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
                .badge-danger { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
                .badge-warning { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                .badge-info { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
                .badge-secondary { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
                .text-muted { color: #64748b; }
                .fw-bold { font-weight: bold; }
                .nowrap { white-space: nowrap; }
                .footer {
                    margin-top: 15px;
                    font-size: 8px;
                    color: #94a3b8;
                    text-align: right;
                    border-top: 1px dashed #cbd5e1;
                    padding-top: 4px;
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div class="company-name">The Premium Homes Ltd</div>
                <div class="report-title">Human Resource Department &mdash; ATS Candidate Tracker Report</div>
            </div>

            <div class="meta-bar">
                <table class="meta-table">
                    <tr>
                        <td style="width: 60%;">
                            <b>Applied Filter:</b> <?= htmlspecialchars($filterSummary) ?>
                        </td>
                        <td style="width: 25%; text-align: center;">
                            <b>Total Candidates:</b> <?= number_format($totalCandidates) ?>
                        </td>
                        <td style="width: 15%; text-align: right;">
                            <b>Generated:</b> <?= $generatedAt ?>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="table-candidates">
                <thead>
                    <tr>
                        <th style="width: 20px; text-align: center;">#</th>
                        <th style="width: 130px;">Candidate Details</th>
                        <th style="width: 105px;">Department &amp; Position</th>
                        <th style="width: 55px;">Source</th>
                        <th style="width: 75px;">Call Status</th>
                        <th style="width: 70px;">Interview Schedule</th>
                        <th style="width: 55px; text-align: center;">Attendance</th>
                        <th style="width: 55px; text-align: center;">Hired Status</th>
                        <th style="width: 55px; text-align: right;">Salary</th>
                        <th>Remarks / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 25px; color: #64748b;">
                                No candidate records found matching the specified filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($candidates as $index => $c): 
                            $sl = $index + 1;
                            $interviewDate = !empty($c['interview_date']) ? date('d M Y', strtotime($c['interview_date'])) : '-';
                            $interviewTime = !empty($c['interview_time']) ? date('h:i A', strtotime($c['interview_time'])) : '';
                            
                            // Attendance badge class
                            $att = strtolower($c['interview_attendance'] ?? 'pending');
                            $attBadge = 'badge-secondary';
                            if ($att === 'present') $attBadge = 'badge-success';
                            elseif ($att === 'absent') $attBadge = 'badge-danger';

                            // Hired badge class
                            $hired = strtolower($c['hired'] ?? 'pending');
                            $hiredBadge = 'badge-secondary';
                            if ($hired === 'yes') $hiredBadge = 'badge-success';
                            elseif ($hired === 'no') $hiredBadge = 'badge-danger';
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold; color: #64748b;"><?= $sl ?></td>
                                <td>
                                    <div class="fw-bold" style="color: #0f172a; font-size: 9px;"><?= htmlspecialchars($c['name'] ?? '') ?></div>
                                    <div class="text-muted" style="font-size: 8px;">Phone: <?= htmlspecialchars($c['phone'] ?? '-') ?></div>
                                    <?php if (!empty($c['email'])): ?>
                                        <div class="text-muted" style="font-size: 7.5px;"><?= htmlspecialchars($c['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['position'] ?? '-') ?></div>
                                    <div class="text-muted" style="font-size: 7.5px;"><?= htmlspecialchars($c['department'] ?? '-') ?></div>
                                </td>
                                <td><?= htmlspecialchars($c['source'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-warning"><?= htmlspecialchars($c['invitation_status'] ?? 'Pending') ?></span>
                                </td>
                                <td>
                                    <div class="nowrap fw-bold"><?= $interviewDate ?></div>
                                    <?php if ($interviewTime): ?>
                                        <div class="text-muted nowrap" style="font-size: 7.5px;"><?= $interviewTime ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge <?= $attBadge ?>"><?= ucfirst($att) ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge <?= $hiredBadge ?>"><?= ($hired === 'yes') ? 'Hired' : (($hired === 'no') ? 'Rejected' : 'Pending') ?></span>
                                </td>
                                <td style="text-align: right; font-size: 8px;">
                                    <?= !empty($c['onboarding_salary']) ? number_format((float)$c['onboarding_salary']) : '-' ?>
                                </td>
                                <td style="font-size: 7.5px; color: #475569;">
                                    <?= !empty($c['remarks']) ? htmlspecialchars($c['remarks']) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                Report Printed By: <?= htmlspecialchars($printedBy) ?> | Confidential &mdash; The Premium Homes Ltd
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "ATS_Candidates_Report_" . date('Ymd_His') . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }
}
