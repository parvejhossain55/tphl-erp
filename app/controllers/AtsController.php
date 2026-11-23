<?php

class AtsController extends Controller
{
    protected $db;
    protected $candidateModel;
    protected $dropdownModel;
    protected $activityModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->candidateModel = new AtsCandidate($this->db);
        $this->dropdownModel = new AtsDropdownOption($this->db);
        $this->activityModel = new AtsActivityLog($this->db);
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

        if (empty($_GET['ajax']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'page_view',
                'description' => 'Viewed Candidate Tracker'
            ]);
        }

        $userId = $_SESSION['user']['id'] ?? 'guest';
        $sessionKey = 'ats_tracker_filters_' . $userId;

        // 1. Explicit Reset Filter Request
        if (isset($_GET['reset_filter']) || isset($_GET['reset'])) {
            unset($_SESSION[$sessionKey]);
            header("Location: " . $GLOBALS['base_url'] . "/ats-tracker");
            exit;
        }

        // 2. Default Filters
        $defaultFilters = [
            'search' => '',
            'tab' => 'all',
            'job_id' => '',
            'department' => [],
            'position' => [],
            'source' => '',
            'invitation_status' => [],
            'interview_attendance' => [],
            'interview_location' => [],
            'hired' => [],
            'cv_uploaded' => '',
            'email_sent' => '',
            'sms_sent' => '',
            'date_type' => 'interview_date',
            'date_from' => '',
            'date_to' => '',
            'per_page' => '50',
            'sort_by' => '',
            'sort_order' => '',
        ];

        // 3. Load Saved Filters from User Session
        $savedFilters = array_merge($defaultFilters, $_SESSION[$sessionKey] ?? []);

        // Helper to normalize multi-select values into array of strings
        $normalizeMulti = function($val) {
            if (is_array($val)) {
                return array_values(array_filter(array_map('trim', $val), fn($v) => $v !== ''));
            }
            if ($val !== null && $val !== '') {
                return [trim((string)$val)];
            }
            return [];
        };

        // 4. Update filters if submitted
        if (isset($_GET['filter_applied'])) {
            // Full filter form was submitted
            $savedFilters['search'] = trim($_GET['q'] ?? '');
            $savedFilters['tab'] = trim($_GET['tab'] ?? ($savedFilters['tab'] ?? 'all'));
            $savedFilters['job_id'] = isset($_GET['job_id']) && $_GET['job_id'] !== '' ? (int)$_GET['job_id'] : '';
            $savedFilters['department'] = isset($_GET['department']) ? $normalizeMulti($_GET['department']) : [];
            $savedFilters['position'] = isset($_GET['position']) ? $normalizeMulti($_GET['position']) : [];
            $savedFilters['source'] = trim($_GET['source'] ?? '');
            $savedFilters['invitation_status'] = isset($_GET['invitation_status']) ? $normalizeMulti($_GET['invitation_status']) : [];
            $savedFilters['interview_attendance'] = isset($_GET['interview_attendance']) ? $normalizeMulti($_GET['interview_attendance']) : [];
            $savedFilters['interview_location'] = isset($_GET['interview_location']) ? $normalizeMulti($_GET['interview_location']) : [];
            $savedFilters['hired'] = isset($_GET['hired']) ? $normalizeMulti($_GET['hired']) : [];
            $savedFilters['cv_uploaded'] = trim($_GET['cv_uploaded'] ?? '');
            $savedFilters['email_sent'] = trim($_GET['email_sent'] ?? '');
            $savedFilters['sms_sent'] = trim($_GET['sms_sent'] ?? '');
            $savedFilters['date_type'] = trim($_GET['date_type'] ?? 'interview_date');
            $savedFilters['date_from'] = trim($_GET['date_from'] ?? '');
            $savedFilters['date_to'] = trim($_GET['date_to'] ?? '');
            if (isset($_GET['per_page'])) $savedFilters['per_page'] = trim($_GET['per_page']);
            if (isset($_GET['sort_by'])) $savedFilters['sort_by'] = trim($_GET['sort_by']);
            if (isset($_GET['sort_order'])) $savedFilters['sort_order'] = trim($_GET['sort_order']);

            $_SESSION[$sessionKey] = $savedFilters;
        } else {
            // Check individual query params
            $hasAnyGet = false;
            if (isset($_GET['job_id'])) {
                $savedFilters = $defaultFilters;
                $savedFilters['job_id'] = $_GET['job_id'] !== '' ? (int)$_GET['job_id'] : '';
                $hasAnyGet = true;
            }
            if (isset($_GET['tab'])) { $savedFilters['tab'] = trim($_GET['tab']); $hasAnyGet = true; }
            if (isset($_GET['q'])) { $savedFilters['search'] = trim($_GET['q']); $hasAnyGet = true; }
            if (isset($_GET['department'])) { $savedFilters['department'] = $normalizeMulti($_GET['department']); $hasAnyGet = true; }
            if (isset($_GET['position'])) { $savedFilters['position'] = $normalizeMulti($_GET['position']); $hasAnyGet = true; }
            if (isset($_GET['invitation_status'])) { $savedFilters['invitation_status'] = $normalizeMulti($_GET['invitation_status']); $hasAnyGet = true; }
            if (isset($_GET['interview_attendance'])) { $savedFilters['interview_attendance'] = $normalizeMulti($_GET['interview_attendance']); $hasAnyGet = true; }
            if (isset($_GET['interview_location'])) { $savedFilters['interview_location'] = $normalizeMulti($_GET['interview_location']); $hasAnyGet = true; }
            if (isset($_GET['hired'])) { $savedFilters['hired'] = $normalizeMulti($_GET['hired']); $hasAnyGet = true; }
            if (isset($_GET['cv_uploaded'])) { $savedFilters['cv_uploaded'] = trim($_GET['cv_uploaded']); $hasAnyGet = true; }
            if (isset($_GET['email_sent'])) { $savedFilters['email_sent'] = trim($_GET['email_sent']); $hasAnyGet = true; }
            if (isset($_GET['sms_sent'])) { $savedFilters['sms_sent'] = trim($_GET['sms_sent']); $hasAnyGet = true; }
            if (isset($_GET['date_type'])) { $savedFilters['date_type'] = trim($_GET['date_type']); $hasAnyGet = true; }
            if (isset($_GET['date_from'])) { $savedFilters['date_from'] = trim($_GET['date_from']); $hasAnyGet = true; }
            if (isset($_GET['date_to'])) { $savedFilters['date_to'] = trim($_GET['date_to']); $hasAnyGet = true; }
            if (isset($_GET['per_page'])) { $savedFilters['per_page'] = trim($_GET['per_page']); $hasAnyGet = true; }
            if (isset($_GET['sort_by'])) { $savedFilters['sort_by'] = trim($_GET['sort_by']); $hasAnyGet = true; }
            if (isset($_GET['sort_order'])) { $savedFilters['sort_order'] = trim($_GET['sort_order']); $hasAnyGet = true; }

            if ($hasAnyGet) {
                $_SESSION[$sessionKey] = $savedFilters;
            }
        }

        // Active filters are the persisted filters
        $filters = $savedFilters;

        $rawPerPage = strtolower(trim($filters['per_page'] ?? '50'));
        if ($rawPerPage === 'all') {
            $perPage = 10000;
        } else {
            $perPage = (int)$rawPerPage;
            if (!in_array($perPage, [10, 20, 50, 100])) {
                $perPage = 50;
                $rawPerPage = '50';
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = $this->candidateModel->getFiltered($filters, $page, $perPage);
        $options = $this->dropdownModel->getAllActiveGrouped();

        $data = [
            'page_title' => 'HR ATS - Candidate Tracker',
            'candidates' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['page'],
            'perPage' => $result['per_page'],
            'rawPerPage' => $rawPerPage,
            'totalPages' => $result['total_pages'],
            'tabCounts' => $result['counts'],
            'filters' => $filters,
            'options' => $options,
            'sortBy' => $filters['sort_by'] ?? '',
            'sortOrder' => $filters['sort_order'] ?? '',
            'trashCount' => $this->candidateModel->getTrashCount(),
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

        if (empty($name) || empty($phone)) {
            $_SESSION['error'] = 'Candidate Name and Phone are mandatory.';
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
            'current_salary' => (isset($_POST['current_salary']) && $_POST['current_salary'] !== '') ? $_POST['current_salary'] : null,
            'expected_salary' => (isset($_POST['expected_salary']) && $_POST['expected_salary'] !== '') ? $_POST['expected_salary'] : null,
            'bottom_line_salary' => (isset($_POST['bottom_line_salary']) && $_POST['bottom_line_salary'] !== '') ? $_POST['bottom_line_salary'] : null,
            'notice_period' => !empty($_POST['notice_period']) ? trim($_POST['notice_period']) : null,
            'notice_period_days' => (isset($_POST['notice_period_days']) && $_POST['notice_period_days'] !== '') ? $_POST['notice_period_days'] : null,
            'hired' => $_POST['hired'] ?? 'Decision Pending',
            'joining_date' => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
            'onboarding_salary' => !empty($_POST['onboarding_salary']) ? $_POST['onboarding_salary'] : null,
            'entry_mode' => 'single',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        $insertedId = $this->candidateModel->create($candidateData);

        if ($insertedId) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'candidate_create',
                'candidate_id' => $insertedId,
                'candidate_name' => $name,
                'description' => "Added new candidate '{$name}' for position '{$position}' ({$department})",
                'details' => ['phone' => $phone, 'position' => $position, 'department' => $department]
            ]);
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

        if (empty($name) || empty($phone)) {
            $_SESSION['error'] = 'Candidate Name and Phone are mandatory.';
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
            'current_salary' => (isset($_POST['current_salary']) && $_POST['current_salary'] !== '') ? $_POST['current_salary'] : null,
            'expected_salary' => (isset($_POST['expected_salary']) && $_POST['expected_salary'] !== '') ? $_POST['expected_salary'] : null,
            'bottom_line_salary' => (isset($_POST['bottom_line_salary']) && $_POST['bottom_line_salary'] !== '') ? $_POST['bottom_line_salary'] : null,
            'notice_period' => !empty($_POST['notice_period']) ? trim($_POST['notice_period']) : null,
            'notice_period_days' => (isset($_POST['notice_period_days']) && $_POST['notice_period_days'] !== '') ? $_POST['notice_period_days'] : null,
            'hired' => $_POST['hired'] ?? 'Decision Pending',
            'joining_date' => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
            'onboarding_salary' => !empty($_POST['onboarding_salary']) ? $_POST['onboarding_salary'] : null,
        ];

        $updated = $this->candidateModel->update((int)$id, $updateData);

        if ($updated) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'candidate_edit',
                'candidate_id' => $id,
                'candidate_name' => $name,
                'description' => "Updated full candidate details for '{$name}'",
                'details' => ['updated_fields' => array_keys($updateData)]
            ]);
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

        $candidate = $this->candidateModel->getById($id);
        $candidateName = $candidate['name'] ?? "Candidate #{$id}";
        $oldValue = $candidate[$field] ?? '';

        $updated = $this->candidateModel->updateField($id, $field, $value);

        if ($updated) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'inline_cell_edit',
                'candidate_id' => $id,
                'candidate_name' => $candidateName,
                'description' => "Changed " . ucfirst(str_replace('_', ' ', $field)) . " to '{$value}' for '{$candidateName}'",
                'details' => ['field' => $field, 'old' => $oldValue, 'new' => $value]
            ]);
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }

    /**
     * Quick Edit Candidate Details (Contact or Position/Dept) via AJAX
     */
    public function quickEditDetails()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        $type = trim($_POST['type'] ?? '');

        if (!$id || empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $candidate = $this->candidateModel->getById($id);
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            exit;
        }

        if ($type === 'contact') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($name) || empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'Name and Phone are required.']);
                exit;
            }

            $updated = $this->candidateModel->updateContactDetails($id, $name, $phone, $email);
            if ($updated) {
                AtsActivityLog::log($this->db, [
                    'action_type' => 'inline_cell_edit',
                    'candidate_id' => $id,
                    'candidate_name' => $name,
                    'description' => "Updated contact info for '{$name}' (Phone: {$phone}, Email: {$email})",
                    'details' => ['name' => $name, 'phone' => $phone, 'email' => $email]
                ]);
                $waPhone = preg_replace('/[^0-9]/', '', $phone);
                echo json_encode([
                    'success' => true,
                    'message' => 'Candidate details updated successfully',
                    'data' => [
                        'id' => $id,
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'wa_phone' => $waPhone
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update candidate details']);
            }
            exit;
        } elseif ($type === 'position_dept') {
            $position = trim($_POST['position'] ?? '');
            $department = trim($_POST['department'] ?? '');

            if ($department === 'Other' && !empty($_POST['department_other'])) {
                $department = trim($_POST['department_other']);
                $this->dropdownModel->ensureOptionExists('department', $department, $_SESSION['user']['id'] ?? null);
            }
            if ($position === 'Other' && !empty($_POST['position_other'])) {
                $position = trim($_POST['position_other']);
                $this->dropdownModel->ensureOptionExists('position', $position, $_SESSION['user']['id'] ?? null);
            }

            if (empty($position) || empty($department)) {
                echo json_encode(['success' => false, 'message' => 'Position and Department cannot be empty.']);
                exit;
            }

            $updated = $this->candidateModel->updatePositionDept($id, $position, $department);
            if ($updated) {
                $cand = $this->candidateModel->getById($id);
                $candName = $cand['name'] ?? "Candidate #{$id}";
                AtsActivityLog::log($this->db, [
                    'action_type' => 'inline_cell_edit',
                    'candidate_id' => $id,
                    'candidate_name' => $candName,
                    'description' => "Updated Position to '{$position}' and Department to '{$department}' for '{$candName}'",
                    'details' => ['position' => $position, 'department' => $department]
                ]);
                echo json_encode([
                    'success' => true,
                    'message' => 'Position & Department updated successfully',
                    'data' => [
                        'id' => $id,
                        'position' => $position,
                        'department' => $department
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update Position & Department']);
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid update type']);
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
            $affected = $this->candidateModel->bulkSoftDelete($ids);
            AtsActivityLog::log($this->db, [
                'action_type' => 'bulk_trash',
                'description' => "Moved {$affected} candidate(s) to Trash",
                'details' => ['candidate_ids' => $ids]
            ]);
            echo json_encode([
                'success' => true,
                'message' => "Successfully moved {$affected} candidate(s) to trash."
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
            if (isset($_POST['interview_date'])) $updates['interview_date'] = trim($_POST['interview_date']);
            if (isset($_POST['interview_time'])) $updates['interview_time'] = trim($_POST['interview_time']);
            if (isset($_POST['interview_location'])) $updates['interview_location'] = trim($_POST['interview_location']);
        } elseif ($action === 'hired' && !empty($_POST['hired'])) {
            $updates['hired'] = $_POST['hired'];
        } elseif ($action === 'position_dept') {
            $position = trim($_POST['position'] ?? '');
            if ($position === 'Other' && !empty($_POST['position_other'])) {
                $position = trim($_POST['position_other']);
                $this->dropdownModel->ensureOptionExists('position', $position, $_SESSION['user']['id'] ?? null);
            }
            if ($position !== '') {
                $updates['position'] = $position;
            }

            $department = trim($_POST['department'] ?? '');
            if ($department === 'Other' && !empty($_POST['department_other'])) {
                $department = trim($_POST['department_other']);
                $this->dropdownModel->ensureOptionExists('department', $department, $_SESSION['user']['id'] ?? null);
            }
            if ($department !== '') {
                $updates['department'] = $department;
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No update values provided']);
            exit;
        }

        $affected = $this->candidateModel->bulkUpdate($ids, $updates);

        AtsActivityLog::log($this->db, [
            'action_type' => 'bulk_edit',
            'description' => "Bulk updated {$affected} candidate(s) with action '{$action}'",
            'details' => ['candidate_ids' => $ids, 'updates' => $updates]
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated {$affected} candidate(s)."
        ]);
        exit;
    }

    /**
     * Soft Delete Candidate (Move to Trash) (AJAX / POST)
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

        $deleted = $this->candidateModel->softDelete($id);

        if ($deleted) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'soft_delete',
                'candidate_id' => $id,
                'candidate_name' => $candidate['name'],
                'description' => "Moved candidate '{$candidate['name']}' to Trash"
            ]);
            echo json_encode(['success' => true, 'message' => "Candidate '{$candidate['name']}' moved to trash."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move candidate to trash.']);
        }
        exit;
    }

    /**
     * Dedicated ATS Trash Page
     */
    public function trash()
    {
        $this->checkAuth();

        AtsActivityLog::log($this->db, [
            'action_type' => 'page_view',
            'description' => 'Viewed Trash / Recycle Bin'
        ]);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $search = trim($_GET['q'] ?? '');

        $filters = [
            'trash' => true,
            'search' => $search
        ];

        $result = $this->candidateModel->getFiltered($filters, $page, $perPage);

        $data = [
            'page_title' => 'HR ATS - Candidate Trash',
            'candidates' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => $result['total_pages'],
            'search' => $search,
        ];

        $this->view('hrm/ats/trash', $data);
    }

    /**
     * Dedicated ATS Activity Log / Audit Trail Page
     */
    public function activityLogs()
    {
        $this->checkAuth();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $search = trim($_GET['q'] ?? '');
        $userId = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $actionType = !empty($_GET['action_type']) ? trim($_GET['action_type']) : null;
        $dateFrom = !empty($_GET['date_from']) ? trim($_GET['date_from']) : null;
        $dateTo = !empty($_GET['date_to']) ? trim($_GET['date_to']) : null;

        $filters = [
            'search' => $search,
            'user_id' => $userId,
            'action_type' => $actionType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $activityModel = new AtsActivityLog($this->db);
        $result = $activityModel->getLogs($filters, $page, $perPage);
        $stats = $activityModel->getStats();
        $usersList = $activityModel->getUsersList();
        $actionTypes = $activityModel->getActionTypes();

        $data = [
            'page_title' => 'HR ATS - Activity Logs & Audit Trail',
            'logs' => $result['data'],
            'total' => $result['total'],
            'currentPage' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => $result['total_pages'],
            'stats' => $stats,
            'usersList' => $usersList,
            'actionTypes' => $actionTypes,
            'filters' => $filters,
        ];

        $this->view('hrm/ats/activity-logs', $data);
    }

    /**
     * Restore Candidate from Trash (AJAX / POST)
     */
    public function restore()
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

        $restored = $this->candidateModel->restore($id);
        if ($restored) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'restore',
                'candidate_id' => $id,
                'candidate_name' => $candidate['name'],
                'description' => "Restored candidate '{$candidate['name']}' from Trash"
            ]);
            echo json_encode(['success' => true, 'message' => "Candidate '{$candidate['name']}' restored successfully."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to restore candidate.']);
        }
        exit;
    }

    /**
     * Bulk Restore Candidates from Trash (AJAX / POST)
     */
    public function bulkRestore()
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

        $affected = $this->candidateModel->bulkRestore($ids);
        AtsActivityLog::log($this->db, [
            'action_type' => 'bulk_restore',
            'description' => "Bulk restored {$affected} candidate(s) from Trash",
            'details' => ['candidate_ids' => $ids]
        ]);
        echo json_encode([
            'success' => true,
            'message' => "Successfully restored {$affected} candidate(s)."
        ]);
        exit;
    }

    /**
     * Force / Hard Delete Candidate permanently from Trash (AJAX / POST)
     */
    public function forceDelete()
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

        $deleted = $this->candidateModel->forceDelete($id);
        if ($deleted) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'force_delete',
                'candidate_id' => $id,
                'candidate_name' => $candidate['name'],
                'description' => "Permanently deleted candidate '{$candidate['name']}'"
            ]);
            echo json_encode(['success' => true, 'message' => "Candidate '{$candidate['name']}' permanently deleted."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to permanently delete candidate.']);
        }
        exit;
    }

    /**
     * Bulk Force Delete Candidates permanently from Trash (AJAX / POST)
     */
    public function bulkForceDelete()
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

        // Clean up CV files for selected candidates
        foreach ($ids as $id) {
            $cand = $this->candidateModel->getById((int)$id);
            if ($cand && !empty($cand['cv_file_path'])) {
                $filePath = BASE_PATH . '/public/' . $cand['cv_file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $affected = $this->candidateModel->bulkForceDelete($ids);
        AtsActivityLog::log($this->db, [
            'action_type' => 'bulk_force_delete',
            'description' => "Bulk permanently deleted {$affected} candidate(s)",
            'details' => ['candidate_ids' => $ids]
        ]);
        echo json_encode([
            'success' => true,
            'message' => "Successfully permanently deleted {$affected} candidate(s)."
        ]);
        exit;
    }

    /**
     * Empty entire Trash permanently (AJAX / POST)
     */
    public function emptyTrash()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        // Fetch all deleted candidates to remove their CV files
        $trashCandidates = $this->candidateModel->getAllFiltered(['trash' => true]);
        foreach ($trashCandidates as $cand) {
            if (!empty($cand['cv_file_path'])) {
                $filePath = BASE_PATH . '/public/' . $cand['cv_file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $affected = $this->candidateModel->emptyTrash();
        AtsActivityLog::log($this->db, [
            'action_type' => 'empty_trash',
            'description' => "Emptied entire Trash ({$affected} records permanently removed)"
        ]);
        echo json_encode([
            'success' => true,
            'message' => "Successfully cleared {$affected} record(s) from trash."
        ]);
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

        $requiredCols = ['name', 'phone'];
        foreach ($requiredCols as $req) {
            if (!isset($headerMap[$req])) {
                $_SESSION['error'] = "Missing required column in CSV: '{$req}'. Please ensure 'Name' and 'Phone' columns exist.";
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

            if (empty($name) || empty($phone)) {
                $skippedCount++;
                continue;
            }

            $email = isset($headerMap['email']) ? trim($row[$headerMap['email']] ?? '') : '';
            $position = (isset($headerMap['position']) && !empty(trim($row[$headerMap['position']] ?? ''))) ? trim($row[$headerMap['position']]) : 'Not Specified';
            $department = (isset($headerMap['department']) && !empty(trim($row[$headerMap['department']] ?? ''))) ? trim($row[$headerMap['department']]) : 'General';
            $source = (isset($headerMap['source']) && !empty(trim($row[$headerMap['source']] ?? ''))) ? trim($row[$headerMap['source']]) : null;
            $invitationStatus = (isset($headerMap['invitation_status']) && !empty(trim($row[$headerMap['invitation_status']] ?? ''))) ? trim($row[$headerMap['invitation_status']]) : 'Pending';
            $interviewDate = (isset($headerMap['interview_date']) && !empty($row[$headerMap['interview_date']])) ? date('Y-m-d', strtotime($row[$headerMap['interview_date']])) : null;
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

        if ($successCount > 0) {
            AtsActivityLog::log($this->db, [
                'action_type' => 'batch_csv_import',
                'description' => "Batch imported {$successCount} candidate(s) via CSV ({$skippedCount} skipped)",
                'details' => ['imported_count' => $successCount, 'skipped_count' => $skippedCount]
            ]);
        }

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
        fputcsv($output, ['Name', 'Phone']);

        // Sample Rows
        fputcsv($output, ['Md. Rakib Hasan', '01712345678']);
        fputcsv($output, ['Sabbir Ahmed', '01812345678']);

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
            AtsActivityLog::log($this->db, [
                'action_type' => 'settings_change',
                'description' => "Added dropdown option '{$optionValue}' to category '{$category}'"
            ]);
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
            AtsActivityLog::log($this->db, [
                'action_type' => 'settings_change',
                'description' => "Deleted dropdown option #{$id}"
            ]);
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
     * Quick Upload CV for Candidate (AJAX)
     */
    public function uploadCv()
    {
        $this->checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['candidate_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
            exit;
        }

        $candidate = $this->candidateModel->getById($id);
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            exit;
        }

        $cvSource = $_POST['cv_source'] ?? 'file';

        if ($cvSource === 'drive_link') {
            $driveLink = trim($_POST['cv_drive_link'] ?? '');
            if (empty($driveLink)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid Google Drive link']);
                exit;
            }
            $this->candidateModel->updateField($id, 'cv_source', 'drive_link');
            $this->candidateModel->updateField($id, 'cv_drive_link', $driveLink);

            AtsActivityLog::log($this->db, [
                'action_type' => 'cv_upload',
                'candidate_id' => $id,
                'candidate_name' => $candidate['name'],
                'description' => "Saved Google Drive CV link for candidate '{$candidate['name']}'"
            ]);

            echo json_encode(['success' => true, 'message' => 'CV Drive link saved successfully!']);
            exit;
        }

        if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid CV file to upload.']);
            exit;
        }

        $filePath = $this->handleCvUpload($_FILES['cv_file']);
        if (!$filePath) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Allowed formats: PDF, DOC, DOCX, JPG, PNG.']);
            exit;
        }

        // Clean up old file if exists
        if (!empty($candidate['cv_file_path'])) {
            $oldFile = BASE_PATH . '/public/' . $candidate['cv_file_path'];
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        $this->candidateModel->updateField($id, 'cv_source', 'file');
        $this->candidateModel->updateField($id, 'cv_file_path', $filePath);

        AtsActivityLog::log($this->db, [
            'action_type' => 'cv_upload',
            'candidate_id' => $id,
            'candidate_name' => $candidate['name'],
            'description' => "Uploaded CV file for candidate '{$candidate['name']}'",
            'details' => ['file_path' => $filePath]
        ]);

        echo json_encode(['success' => true, 'message' => 'CV uploaded successfully!']);
        exit;
    }

    /**
     * Extract normalized request filters from GET
     */
    private function extractRequestFilters(): array
    {
        $normalizeMulti = function($val) {
            if (is_array($val)) {
                return array_values(array_filter(array_map('trim', $val), fn($v) => $v !== ''));
            }
            if ($val !== null && $val !== '') {
                return [trim((string)$val)];
            }
            return [];
        };

        return [
            'search' => trim($_GET['q'] ?? ''),
            'tab' => trim($_GET['tab'] ?? 'all'),
            'department' => isset($_GET['department']) ? $normalizeMulti($_GET['department']) : [],
            'position' => isset($_GET['position']) ? $normalizeMulti($_GET['position']) : [],
            'source' => trim($_GET['source'] ?? ''),
            'invitation_status' => isset($_GET['invitation_status']) ? $normalizeMulti($_GET['invitation_status']) : [],
            'interview_attendance' => isset($_GET['interview_attendance']) ? $normalizeMulti($_GET['interview_attendance']) : [],
            'interview_location' => isset($_GET['interview_location']) ? $normalizeMulti($_GET['interview_location']) : [],
            'hired' => isset($_GET['hired']) ? $normalizeMulti($_GET['hired']) : [],
            'cv_uploaded' => trim($_GET['cv_uploaded'] ?? ''),
            'email_sent' => trim($_GET['email_sent'] ?? ''),
            'sms_sent' => trim($_GET['sms_sent'] ?? ''),
            'date_type' => trim($_GET['date_type'] ?? 'interview_date'),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
        ];
    }

    /**
     * Export Candidates to PDF with active filters
     */
    public function exportPdf()
    {
        $this->checkAuth();

        $filters = $this->extractRequestFilters();

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

    /**
     * Export Candidates to Excel (.xlsx) with active filters
     */
    public function exportExcel()
    {
        $this->checkAuth();

        $filters = $this->extractRequestFilters();

        $candidates = $this->candidateModel->getAllFiltered($filters);

        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            if (defined('BASE_PATH') && file_exists(BASE_PATH . '/vendor/autoload.php')) {
                require_once BASE_PATH . '/vendor/autoload.php';
            } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ATS Candidates');

        $headers = [
            'SL',
            'Candidate Name',
            'Phone',
            'Email',
            'Department',
            'Position',
            'Source',
            'Call Status',
            'Interview Call Date',
            'Interview Date',
            'Interview Time',
            'Interview Location',
            'Attendance',
            'Hired Status',
            'Salary (BDT)',
            'Joining Date',
            'Referred By',
            'Email Sent',
            'SMS Sent',
            'Remarks / Notes',
            'Created Date'
        ];

        // Column widths
        $columnWidths = [
            'A' => 6,   // SL
            'B' => 24,  // Candidate Name
            'C' => 18,  // Phone
            'D' => 25,  // Email
            'E' => 25,  // Department
            'F' => 22,  // Position
            'G' => 16,  // Source
            'H' => 16,  // Call Status
            'I' => 18,  // Call Date
            'J' => 16,  // Interview Date
            'K' => 15,  // Interview Time
            'L' => 22,  // Interview Location
            'M' => 14,  // Attendance
            'N' => 14,  // Hired Status
            'O' => 16,  // Salary
            'P' => 16,  // Joining Date
            'Q' => 18,  // Referred By
            'R' => 12,  // Email Sent
            'S' => 12,  // SMS Sent
            'T' => 30,  // Remarks
            'U' => 18,  // Created Date
        ];

        // Insert Header
        $sheet->fromArray($headers, null, 'A1');

        $rowsData = [];
        foreach ($candidates as $idx => $c) {
            $sl = $idx + 1;
            $interviewDate = !empty($c['interview_date']) ? date('d-M-Y', strtotime($c['interview_date'])) : '';
            $interviewCallDate = !empty($c['interview_call_date']) ? date('d-M-Y', strtotime($c['interview_call_date'])) : '';
            $interviewTime = !empty($c['interview_time']) ? date('h:i A', strtotime($c['interview_time'])) : '';
            $joiningDate = !empty($c['joining_date']) ? date('d-M-Y', strtotime($c['joining_date'])) : '';
            $createdDate = !empty($c['created_at']) ? date('d-M-Y h:i A', strtotime($c['created_at'])) : '';

            $att = ucfirst(strtolower($c['interview_attendance'] ?? 'pending'));
            $hiredRaw = strtolower($c['hired'] ?? 'pending');
            $hired = ($hiredRaw === 'yes') ? 'Hired' : (($hiredRaw === 'no') ? 'Rejected' : 'Pending');

            $emailSent = !empty($c['email_sent']) ? 'Yes' : 'No';
            $smsSent = !empty($c['sms_sent']) ? 'Yes' : 'No';

            $salary = !empty($c['onboarding_salary']) ? (float)$c['onboarding_salary'] : '';

            $rowsData[] = [
                $sl,
                $c['name'] ?? '',
                $c['phone'] ?? '',
                $c['email'] ?? '',
                $c['department'] ?? '',
                $c['position'] ?? '',
                $c['source'] ?? '',
                $c['invitation_status'] ?? '',
                $interviewCallDate,
                $interviewDate,
                $interviewTime,
                $c['interview_location'] ?? '',
                $att,
                $hired,
                $salary,
                $joiningDate,
                $c['referred_by'] ?? '',
                $emailSent,
                $smsSent,
                $c['remarks'] ?? '',
                $createdDate
            ];
        }

        // Insert Rows
        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A2');
        }

        $lastRow = max(2, count($rowsData) + 1);

        // Explicit Column Widths
        foreach ($columnWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Header Styling (Company Green #078134)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '078134']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Thin Borders
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A1:U{$lastRow}")->applyFromArray($borderStyle);

        // Format column C (Phone) as text to preserve leading zero
        $sheet->getStyle("C2:C{$lastRow}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        // Center alignments for dates, statuses, and identifiers
        $centerCols = ['A', 'C', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'P', 'R', 'S', 'U'];
        foreach ($centerCols as $c) {
            $sheet->getStyle("{$c}2:{$c}{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        // Salary alignment right & number format
        $sheet->getStyle("O2:O{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("O2:O{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Freeze Top Row & AutoFilter
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:U1');

        // Clean any output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fileName = 'ATS_Candidates_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Candidates to CSV with active filters
     */
    public function exportCsv()
    {
        $this->checkAuth();

        $filters = $this->extractRequestFilters();

        $candidates = $this->candidateModel->getAllFiltered($filters);

        // Clean any output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fileName = 'ATS_Candidates_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM so Excel opens characters properly
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headers = [
            'SL',
            'Candidate Name',
            'Phone',
            'Email',
            'Department',
            'Position',
            'Source',
            'Call Status',
            'Interview Call Date',
            'Interview Date',
            'Interview Time',
            'Interview Location',
            'Attendance',
            'Hired Status',
            'Salary (BDT)',
            'Joining Date',
            'Referred By',
            'Email Sent',
            'SMS Sent',
            'Remarks / Notes',
            'Created Date'
        ];
        fputcsv($output, $headers);

        foreach ($candidates as $idx => $c) {
            $sl = $idx + 1;
            $interviewDate = !empty($c['interview_date']) ? date('d-M-Y', strtotime($c['interview_date'])) : '';
            $interviewCallDate = !empty($c['interview_call_date']) ? date('d-M-Y', strtotime($c['interview_call_date'])) : '';
            $interviewTime = !empty($c['interview_time']) ? date('h:i A', strtotime($c['interview_time'])) : '';
            $joiningDate = !empty($c['joining_date']) ? date('d-M-Y', strtotime($c['joining_date'])) : '';
            $createdDate = !empty($c['created_at']) ? date('d-M-Y h:i A', strtotime($c['created_at'])) : '';

            $att = ucfirst(strtolower($c['interview_attendance'] ?? 'pending'));
            $hiredRaw = strtolower($c['hired'] ?? 'pending');
            $hired = ($hiredRaw === 'yes') ? 'Hired' : (($hiredRaw === 'no') ? 'Rejected' : 'Pending');

            $emailSent = !empty($c['email_sent']) ? 'Yes' : 'No';
            $smsSent = !empty($c['sms_sent']) ? 'Yes' : 'No';

            // Format phone to preserve leading zero when opened in Excel
            $phone = $c['phone'] ?? '';
            if (!empty($phone) && substr($phone, 0, 1) === '0') {
                $phone = '="' . $phone . '"';
            }

            fputcsv($output, [
                $sl,
                $c['name'] ?? '',
                $phone,
                $c['email'] ?? '',
                $c['department'] ?? '',
                $c['position'] ?? '',
                $c['source'] ?? '',
                $c['invitation_status'] ?? '',
                $interviewCallDate,
                $interviewDate,
                $interviewTime,
                $c['interview_location'] ?? '',
                $att,
                $hired,
                $c['onboarding_salary'] ?? '',
                $joiningDate,
                $c['referred_by'] ?? '',
                $emailSent,
                $smsSent,
                $c['remarks'] ?? '',
                $createdDate
            ]);
        }

        fclose($output);
        exit;
    }
}
