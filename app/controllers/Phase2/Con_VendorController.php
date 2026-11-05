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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/vendors/create-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Add Vendor',
        ]);
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'         => trim($_POST['name'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? ''),
            'phone'        => $_POST['phone'] ?? null,
            'email'        => $_POST['email'] ?? null,
            'address'      => $_POST['address'] ?? null,
            'rating'       => 5.00,
            'status'       => 'pending_approval',
            'created_by'   => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Vendor name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        $vendorId = $this->vendorModel->create($data);
        if ($vendorId) {
            require_once dirname(dirname(__DIR__)) . '/models/EnrollmentApproval.php';
            $enrollmentApproval = new \EnrollmentApproval($this->db);
            $initialStep = $enrollmentApproval->initiateApproval('vendor', $vendorId, $data['created_by']);
            $stepLabel = $enrollmentApproval->getStepLabel($initialStep);
            
            setSuccess("Vendor enrollment request submitted successfully! It is now pending approval from: {$stepLabel}.");
            header("Location: {$GLOBALS['base_url']}/vendor-enrollment-requests");
            exit;
        }

        setError('Failed to add vendor.');
        header("Location: {$GLOBALS['base_url']}/con-vendor-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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

        $this->view('phase2/vendors/edit-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Edit Vendor',
            'vendor'        => $vendor,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'phone'   => $_POST['phone'] ?? null,
            'email'   => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null,
            'status'  => $_POST['status'] ?? 'active',
        ];

        if (empty($data['name'])) {
            setError('Vendor name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }

        if ($this->vendorModel->update($id, $data)) {
            setSuccess('Vendor updated successfully!');
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

        $stats     = $this->vendorModel->getStats($id);
        $penalties = $this->vendorModel->getPenalties($id);
        $poHistory = $this->vendorModel->getPurchaseHistory($id);

        $this->view('phase2/vendors/show-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendor Profile — ' . $vendor['name'],
            'vendor'        => $vendor,
            'stats'         => $stats,
            'penalties'     => $penalties,
            'poHistory'     => $poHistory,
        ]);
    }

    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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

    // ─── CSV Import Method ────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-vendors');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-vendors');
            exit;
        }
        
        // Read entire file as string to properly handle UTF-8 Bengali text
        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $content = ltrim($content, "\xEF\xBB\xBF");
        $content = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        if (empty($lines)) { setError('CSV file is empty.'); header('Location: ' . $GLOBALS['base_url'] . '/con-vendors'); exit; }
        $header = array_map('trim', str_getcsv(array_shift($lines)));
        
        $resolveKey = function($row, $targetKey) {
            $targetLower = strtolower($targetKey);
            foreach ($row as $key => $val) {
                $keyLower = strtolower(trim($key));
                if ($keyLower === $targetLower || strpos($keyLower, $targetLower) === 0) {
                    return $val;
                }
            }
            return '';
        };

        $successCount = 0; $errorCount = 0; $errors = [];
        $userId = $_SESSION['user']['id'] ?? 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $data = array_pad(str_getcsv($line), count($header), '');
            $row  = array_combine($header, $data);
            
            $vendorName = trim($resolveKey($row, 'Vendor_Name'));
            $phone      = trim($resolveKey($row, 'Phone'));
            $email      = trim($resolveKey($row, 'Email'));
            $address    = trim($resolveKey($row, 'Address'));
            
            if (empty($vendorName)) { 
                $errorCount++; $errors[] = "Missing Vendor Name"; 
                continue; 
            }
            
            $stmt = $this->db->prepare("SELECT id FROM vendors WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $vendorName); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { 
                $errors[] = "Vendor '$vendorName' already exists"; 
                $errorCount++; 
                continue; 
            }
            
            $insertData = [
                'name'       => $vendorName,
                'phone'      => !empty($phone) ? $phone : null,
                'email'      => !empty($email) ? $email : null,
                'address'    => !empty($address) ? $address : null,
                'rating'     => 5.00,
                'status'     => 'active',
                'created_by' => $userId
            ];

            if ($this->vendorModel->create($insertData)) { 
                $successCount++; 
            } else { 
                $errorCount++; $errors[] = "Failed to save vendor '$vendorName'"; 
            }
        }
        
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        
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
        // Allowed: Management (1, 3, 33, 34, 35, 20, 32), Purchase (17, 22, 23, 31), Accounts (6, 30)
        $allowedRoles = [1, 3, 6, 17, 20, 22, 23, 30, 31, 32, 33, 34, 35];
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
        $allVendorsQ = @$this->db->query("SELECT id, name FROM vendors WHERE LOWER(status) = 'active' ORDER BY name ASC");
        if (!$allVendorsQ || $allVendorsQ->num_rows === 0) {
            $allVendorsQ = $this->db->query("SELECT id, name FROM vendors ORDER BY name ASC");
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
            'page_title'         => $selectedVendor ? 'Vendor Ledger — ' . $selectedVendor['name'] : 'Vendor Financial Ledger & Dashboard',
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
        $allowedRoles = [1, 3, 6, 17, 20, 22, 23, 30, 31, 32, 33, 34, 35];
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