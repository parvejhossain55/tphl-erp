<?php
namespace Phase2;

use Controller;
use Con_Vendor;

class Con_VendorController extends Controller
{
    protected $db;
    protected $vendorModel;

    public function __construct($db)
    {
        $this->db          = $db;
        $this->vendorModel = new Con_Vendor($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($currentPage < 1) $currentPage = 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $totalRecords = $this->vendorModel->countAll();
        $totalPages = ceil($totalRecords / $perPage);

        $vendors = $this->vendorModel->getPaginated($perPage, $offset);

        $this->view('phase2/vendors/vendor-lists', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendors & Suppliers',
            'vendors'       => $vendors,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'offset'        => $offset,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/vendors/create-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Add Vendor',
        ]);
    }

    /**
     * Upload a vendor document (NID or Trade License) as image or PDF.
     * Returns ['path' => string|null, 'error' => string|null].
     */
    private function uploadVendorDocument(string $fieldName, string $prefix, bool $required = false): array
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                return ['path' => null, 'error' => ucfirst(str_replace('_', ' ', $fieldName)) . ' document (Image/PDF) is required.'];
            }
            return ['path' => null, 'error' => null];
        }

        $file = $_FILES[$fieldName];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Failed to upload ' . str_replace('_', ' ', $fieldName) . '.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($ext, $allowedExts, true)) {
            return ['path' => null, 'error' => 'Invalid file format for ' . str_replace('_', ' ', $fieldName) . '. Allowed: JPG, JPEG, PNG, WEBP, PDF.'];
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return ['path' => null, 'error' => 'File size for ' . str_replace('_', ' ', $fieldName) . ' must not exceed 10MB.'];
        }

        $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/vendor-documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['path' => null, 'error' => 'Could not save uploaded ' . str_replace('_', ' ', $fieldName) . ' file.'];
        }

        return ['path' => 'uploads/vendor-documents/' . $filename, 'error' => null];
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $companyName        = trim($_POST['company_name'] ?? '');
        $name               = trim($_POST['name'] ?? '');
        $phone              = trim($_POST['phone'] ?? '');
        $email              = trim($_POST['email'] ?? '');
        $address            = trim($_POST['address'] ?? '');
        $nidNumber          = trim($_POST['nid_number'] ?? '');
        $tradeLicenseNumber = trim($_POST['trade_license_number'] ?? '');

        if ($companyName === '') {
            setError('Business / Company Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }
        if ($name === '') {
            setError('Contact Person / Designated Person Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }
        if ($phone === '') {
            setError('Phone Number is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setError('A valid Email Address is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        // Prevent duplicate active/pending business name
        $dupStmt = $this->db->prepare("SELECT id FROM vendors WHERE LOWER(TRIM(company_name)) = LOWER(TRIM(?)) AND status IN ('active', 'pending_approval') LIMIT 1");
        if ($dupStmt) {
            $dupStmt->bind_param('s', $companyName);
            $dupStmt->execute();
            if ($dupStmt->get_result()->num_rows > 0) {
                $dupStmt->close();
                setError("A vendor with Business Name \"{$companyName}\" already exists or is pending approval.");
                header("Location: {$GLOBALS['base_url']}/con-vendor-create");
                exit;
            }
            $dupStmt->close();
        }

        $nidUpload = $this->uploadVendorDocument('nid_file', 'vendor_nid', true);
        if ($nidUpload['error']) {
            setError($nidUpload['error']);
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        $tlUpload = $this->uploadVendorDocument('trade_license_file', 'vendor_tl', true);
        if ($tlUpload['error']) {
            setError($tlUpload['error']);
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        $data = [
            'name'                 => $name,
            'company_name'         => $companyName,
            'phone'                => $phone,
            'email'                => $email,
            'address'              => $address !== '' ? $address : null,
            'nid_number'           => $nidNumber !== '' ? $nidNumber : null,
            'trade_license_number' => $tradeLicenseNumber !== '' ? $tradeLicenseNumber : null,
            'nid_file'             => $nidUpload['path'],
            'trade_license_file'   => $tlUpload['path'],
            'rating'               => 5.00,
            'status'               => 'pending_approval',
            'created_by'           => $_SESSION['user']['id'] ?? null,
        ];

        $vendorId = $this->vendorModel->create($data);
        if ($vendorId) {
            require_once dirname(dirname(__DIR__)) . '/models/EnrollmentApproval.php';
            $enrollmentApproval = new \EnrollmentApproval($this->db);
            $initialStep = $enrollmentApproval->initiateApproval('vendor', $vendorId, $data['created_by']);
            $stepLabel = $enrollmentApproval->getStepLabel($initialStep);
            
            setSuccess("Vendor enrollment request submitted successfully! It is now pending approval from: {$stepLabel}.");
            header("Location: {$GLOBALS['base_url']}/vendor-enrollment-requests?tab=pending");
            exit;
        }

        setError('Failed to add vendor.');
        header("Location: {$GLOBALS['base_url']}/con-vendor-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $vendor = $this->vendorModel->find($id);

        if (!$vendor) {
            setError('Vendor not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3], true);
        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $vEditRevSvc  = new \RequisitionRevisionService($this->db);
        $vEditOpenRev = $vEditRevSvc->getOpenRevision('vendors', (int)$id);
        if (($vendor['status'] ?? '') === 'pending_approval' && !$isAdmin) {
            $hasAnyApproval = ($vendor['dept_head_status'] ?? '') === 'approved'
                || ($vendor['purchase_head_status'] ?? '') === 'approved'
                || ($vendor['role_20_status'] ?? '') === 'approved'
                || ($vendor['role_38_status'] ?? '') === 'approved'
                || ($vendor['management_status'] ?? '') === 'approved';
            if ($hasAnyApproval && empty($vEditOpenRev)) {
                setError('This vendor enrollment request has already been approved at one or more stages and can no longer be edited.');
                header("Location: {$GLOBALS['base_url']}/con-vendors");
                exit;
            }
            if ((int)($vendor['created_by'] ?? 0) > 0 && (int)$vendor['created_by'] !== $userId) {
                setError('Only the creator or Admin can edit a pending vendor enrollment request before approval.');
                header("Location: {$GLOBALS['base_url']}/con-vendors");
                exit;
            }
        }

        $this->view('phase2/vendors/edit-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Edit Vendor',
            'vendor'        => $vendor,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $existing = $this->vendorModel->find($id);
        if (!$existing) {
            setError('Vendor not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3], true);
        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $vRevSvc = new \RequisitionRevisionService($this->db);
        $vOpenRev = $vRevSvc->getOpenRevision('vendors', (int)$id);

        if (($existing['status'] ?? '') === 'pending_approval' && !$isAdmin) {
            $hasAnyApproval = ($existing['dept_head_status'] ?? '') === 'approved'
                || ($existing['purchase_head_status'] ?? '') === 'approved'
                || ($existing['role_20_status'] ?? '') === 'approved'
                || ($existing['role_38_status'] ?? '') === 'approved'
                || ($existing['management_status'] ?? '') === 'approved';
            if ($hasAnyApproval && empty($vOpenRev)) {
                setError('This vendor enrollment request has already been approved at one or more stages and can no longer be edited.');
                header("Location: {$GLOBALS['base_url']}/con-vendors");
                exit;
            }
            if ((int)($existing['created_by'] ?? 0) > 0 && (int)$existing['created_by'] !== $userId) {
                setError('Only the creator or Admin can edit a pending vendor enrollment request before approval.');
                header("Location: {$GLOBALS['base_url']}/con-vendors");
                exit;
            }
        }

        $companyName        = trim($_POST['company_name'] ?? '');
        $name               = trim($_POST['name'] ?? '');
        $phone              = trim($_POST['phone'] ?? '');
        $email              = trim($_POST['email'] ?? '');
        $address            = trim($_POST['address'] ?? '');
        $nidNumber          = trim($_POST['nid_number'] ?? '');
        $tradeLicenseNumber = trim($_POST['trade_license_number'] ?? '');

        if ($companyName === '') {
            setError('Business / Company Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }
        if ($name === '') {
            setError('Contact Person / Designated Person Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }
        if ($phone === '') {
            setError('Phone Number is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setError('A valid Email Address is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }

        $nidUpload = $this->uploadVendorDocument('nid_file', 'vendor_nid', false);
        if ($nidUpload['error']) {
            setError($nidUpload['error']);
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }

        $tlUpload = $this->uploadVendorDocument('trade_license_file', 'vendor_tl', false);
        if ($tlUpload['error']) {
            setError($tlUpload['error']);
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }

        $data = [
            'name'                 => $name,
            'company_name'         => $companyName,
            'phone'                => $phone,
            'email'                => $email,
            'address'              => $address !== '' ? $address : null,
            'nid_number'           => $nidNumber !== '' ? $nidNumber : null,
            'trade_license_number' => $tradeLicenseNumber !== '' ? $tradeLicenseNumber : null,
            'nid_file'             => $nidUpload['path'] ?? ($existing['nid_file'] ?? null),
            'trade_license_file'   => $tlUpload['path'] ?? ($existing['trade_license_file'] ?? null),
            'status'               => $_POST['status'] ?? ($existing['status'] ?? 'active'),
        ];

        if ($this->vendorModel->update($id, $data)) {
            $vEditSum = \RequisitionRevisionService::buildChangeSummary([
                'Company' => [$existing['company_name'] ?? '', $data['company_name'] ?? ''],
                'Name'    => [$existing['name'] ?? '', $data['name'] ?? ''],
                'Phone'   => [$existing['phone'] ?? '', $data['phone'] ?? ''],
                'Email'   => [$existing['email'] ?? '', $data['email'] ?? ''],
            ]);
            $vRevSvc->logEdit('vendors', (int)$id, $vEditSum);
            if (!empty($vOpenRev)) {
                $vRevSvc->markResubmitted('vendors', (int)$id, null, trim($_POST['resubmitted_note'] ?? ''));
                setSuccess("Vendor updated & resubmitted directly to {$vOpenRev['return_stage_label']}.");
            } else {
                setSuccess('Vendor updated successfully!');
            }
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-vendors");
        exit;
    }

    public function show($id)
    {
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            setError('Vendor not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        $stats             = $this->vendorModel->getStats($id);
        $penalties         = $this->vendorModel->getPenalties($id);
        $poHistory         = $this->vendorModel->getPurchaseHistory($id);
        $officeProcHistory = $this->vendorModel->getOfficeProcurementHistory($id);

        $this->view('phase2/vendors/show-vendor', [
            'current_route'     => 'con-vendors',
            'page_title'        => 'Vendor Profile — ' . Con_Vendor::displayName($vendor),
            'vendor'            => $vendor,
            'stats'             => $stats,
            'penalties'         => $penalties,
            'poHistory'         => $poHistory,
            'officeProcHistory' => $officeProcHistory,
        ]);
    }

    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->vendorModel->delete($id)) {
            setSuccess('Vendor deleted.');
        } else {
            setError('Failed to delete vendor.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendors");
        exit;
    }

    // ─── CSV Import Method (Disabled to enforce multi-step enrollment approval) ──
    public function importCsv() {
        setError('CSV import is disabled. Please use Add Vendor to submit an enrollment request with required documents.');
        header('Location: ' . $GLOBALS['base_url'] . '/con-vendors');
        exit;
    }

    // =========================================================================
    // VENDOR LEDGER & FINANCIAL DASHBOARD
    // =========================================================================

    /**
     * Vendor Ledger View: Supports master summary list and deep single vendor running balance ledger
     */
    public function ledger($vendorId = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        // Allowed: Management (1, 3, 33, 34, 35, 38, 20, 32), Purchase (17, 22, 23, 31), Accounts (6, 30)
        $allowedRoles = [1, 3, 6, 17, 20, 22, 23, 30, 31, 32, 33, 34, 35, 38];
        if (!in_array($userRole, $allowedRoles, true) && !hasPermission(['Vendor Manage', 'Construction Accounts', 'Financial Reports', 'Purchase Manage'])) {
            setError('You do not have permission to access the Vendor Ledger.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $selectedVendorId = $vendorId !== null ? (int)$vendorId : (!empty($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0);
        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $period = trim($_GET['period'] ?? 'all_time');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $search = trim($_GET['search'] ?? '');

        // Resolve quick periods
        if ($period === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_month') {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        } elseif ($period === 'this_year') {
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        } elseif ($period === 'last_30_days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
        }

        $filters = [
            'vendor_id'  => $selectedVendorId > 0 ? $selectedVendorId : null,
            'project_id' => $selectedProjectId > 0 ? $selectedProjectId : null,
            'start_date' => !empty($startDate) ? $startDate : null,
            'end_date'   => !empty($endDate) ? $endDate : null,
            'search'     => !empty($search) ? $search : null,
            'period'     => $period,
        ];

        // All active projects for filter dropdown
        $projectsQ = @$this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        if (!$projectsQ || $projectsQ->num_rows === 0) {
            $projectsQ = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        }
        $projects = $projectsQ ? $projectsQ->fetch_all(MYSQLI_ASSOC) : [];

        // All active vendors for filter dropdown
        $allVendorsQ = @$this->db->query("SELECT id, COALESCE(NULLIF(TRIM(company_name), ''), name) AS name FROM vendors WHERE LOWER(status) = 'active' ORDER BY name ASC");
        if (!$allVendorsQ || $allVendorsQ->num_rows === 0) {
            $allVendorsQ = $this->db->query("SELECT id, COALESCE(NULLIF(TRIM(company_name), ''), name) AS name FROM vendors ORDER BY name ASC");
        }
        $allVendors = $allVendorsQ ? $allVendorsQ->fetch_all(MYSQLI_ASSOC) : [];

        // Dynamic KPI metrics matching current filter
        $kpiMetrics = $this->vendorModel->getVendorLedgerKpis($filters);

        $selectedVendor = null;
        $projectBreakdown = [];
        $ledgerEntries = [];
        $vendorsSummary = [];
        $currentPage = 1;
        $totalPages = 1;
        $totalRecords = 0;
        $offset = 0;
        $perPage = 20;

        if ($selectedVendorId > 0) {
            // Single Vendor Detailed Ledger Mode
            $selectedVendor = $this->vendorModel->find($selectedVendorId);
            if (!$selectedVendor) {
                setError('Vendor not found.');
                header("Location: " . $GLOBALS['base_url'] . "/con-vendor-ledger");
                exit;
            }

            $projectBreakdown = $this->vendorModel->getVendorProjectBreakdown($selectedVendorId, $filters);
            $ledgerEntries = $this->vendorModel->getDetailedVendorLedger($selectedVendorId, $filters);
        } else {
            // Master Vendors Financial Summary List Mode
            $currentPage = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(10, (int)($_GET['limit'] ?? 20)));
            $offset = ($currentPage - 1) * $perPage;

            $totalRecords = $this->vendorModel->getVendorLedgerCount($filters);
            $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $perPage) : 1;
            if ($currentPage > $totalPages && $totalPages > 0) {
                $currentPage = $totalPages;
                $offset = ($currentPage - 1) * $perPage;
            }

            $vendorsSummary = $this->vendorModel->getVendorLedgerSummary($filters, $perPage, $offset);
        }

        $this->view('phase2/vendors/vendor-ledger', [
            'current_route'      => 'con-vendor-ledger',
            'page_title'         => $selectedVendor ? 'Vendor Ledger — ' . Con_Vendor::displayName($selectedVendor) : 'Vendor Financial Ledger & Dashboard',
            'kpiMetrics'         => $kpiMetrics,
            'selectedVendorId'   => $selectedVendorId,
            'selectedVendor'     => $selectedVendor,
            'selectedProjectId'  => $selectedProjectId,
            'projectBreakdown'   => $projectBreakdown,
            'ledgerEntries'      => $ledgerEntries,
            'vendorsSummary'     => $vendorsSummary,
            'projects'           => $projects,
            'allVendors'         => $allVendors,
            'filters'            => $filters,
            'period'             => $period,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'search'             => $search,
            'currentPage'        => $currentPage,
            'totalPages'         => $totalPages,
            'totalRecords'       => $totalRecords,
            'perPage'            => $perPage,
            'offset'             => $offset,
        ]);
    }

    /**
     * AJAX endpoint for live filter switching
     */
    public function ajaxLedgerData()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $vendorId = !empty($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $period = trim($_GET['period'] ?? 'all_time');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $search = trim($_GET['search'] ?? '');

        if ($period === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_month') {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        } elseif ($period === 'this_year') {
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        } elseif ($period === 'last_30_days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
        }

        $filters = [
            'vendor_id'  => $vendorId,
            'project_id' => $projectId,
            'start_date' => $startDate ?: null,
            'end_date'   => $endDate ?: null,
            'search'     => $search ?: null,
            'period'     => $period,
        ];

        $kpis = $this->vendorModel->getVendorLedgerKpis($filters);
        echo json_encode([
            'status' => 'success',
            'kpis'   => $kpis,
        ]);
        exit;
    }

    /**
     * Printable Vendor Ledger Statement
     */
    public function printLedger($vendorId = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoles = [1, 3, 6, 17, 20, 22, 23, 30, 31, 32, 33, 34, 35, 38];
        if (!in_array($userRole, $allowedRoles, true) && !hasPermission(['Vendor Manage', 'Construction Accounts', 'Financial Reports'])) {
            die('Unauthorized access.');
        }

        $selectedVendorId = $vendorId !== null ? (int)$vendorId : (!empty($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0);
        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');

        $filters = [
            'vendor_id'  => $selectedVendorId > 0 ? $selectedVendorId : null,
            'project_id' => $selectedProjectId > 0 ? $selectedProjectId : null,
            'start_date' => !empty($startDate) ? $startDate : null,
            'end_date'   => !empty($endDate) ? $endDate : null,
        ];

        $vendor = null;
        $projectBreakdown = [];
        $ledgerEntries = [];
        $vendorsSummary = [];
        $kpiMetrics = $this->vendorModel->getVendorLedgerKpis($filters);

        // Project Name
        $projectName = 'All Projects';
        if ($selectedProjectId > 0) {
            $pQ = $this->db->query("SELECT name FROM projects WHERE id = {$selectedProjectId}");
            if ($pRow = $pQ->fetch_assoc()) {
                $projectName = $pRow['name'];
            }
        }

        if ($selectedVendorId > 0) {
            $vendor = $this->vendorModel->find($selectedVendorId);
            if (!$vendor) {
                die('Vendor not found.');
            }
            $projectBreakdown = $this->vendorModel->getVendorProjectBreakdown($selectedVendorId, $filters);
            $ledgerEntries = $this->vendorModel->getDetailedVendorLedger($selectedVendorId, $filters);
        } else {
            $vendorsSummary = $this->vendorModel->getVendorLedgerSummary($filters, 1000, 0);
        }

        $viewData = [
            'vendor'           => $vendor,
            'selectedVendorId' => $selectedVendorId,
            'selectedProjectId'=> $selectedProjectId,
            'projectName'      => $projectName,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'kpiMetrics'       => $kpiMetrics,
            'projectBreakdown' => $projectBreakdown,
            'ledgerEntries'    => $ledgerEntries,
            'vendorsSummary'   => $vendorsSummary,
        ];

        extract($viewData);
        include __DIR__ . '/../../views/phase2/vendors/vendor-ledger-print.php';
        exit;
    }
}