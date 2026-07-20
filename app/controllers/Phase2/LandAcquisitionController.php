<?php
namespace Phase2;

use Controller;

class LandAcquisitionController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        
        // Fetch stats
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total_lands,
                SUM(CASE WHEN status='selected' THEN 1 ELSE 0 END) as selected_count,
                SUM(CASE WHEN status='bayana' THEN 1 ELSE 0 END) as bayana_count,
                SUM(CASE WHEN status='registered' THEN 1 ELSE 0 END) as registered_count,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(total_amount) as total_value,
                SUM(total_area) as total_area
            FROM land_acquisitions
        ")->fetch_assoc();

        // Fetch total bayana paid
        $bayanaPaid = $this->db->query("SELECT SUM(payment_amount) as total FROM land_bayana_payments")->fetch_assoc()['total'] ?? 0;
        
        // Fetch total registration cost paid
        $regCost = $this->db->query("SELECT SUM(registration_cost) as total FROM land_registrations")->fetch_assoc()['total'] ?? 0;

        $stats['total_bayana_paid'] = $bayanaPaid;
        $stats['total_reg_cost'] = $regCost;

        // Fetch land list
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $where = "1=1";
        if (!in_array($roleId, [1, 3, 6])) {
            $where = "(p.landman_id = $userId OR la.created_by = $userId)";
        }

        $result = $this->db->query("
            SELECT la.*, p.name as project_name, u.full_name as creator_name
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN users u ON la.created_by = u.id
            WHERE $where
            ORDER BY la.id DESC
        ");
        $acquisitions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // For modals / forms
        $projectFilter = in_array($roleId, [1, 3, 6]) ? "landman_id IS NOT NULL" : "landman_id = $userId";
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $projectFilter ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_acquisition/index', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Land Acquisitions Portfolio',
            'stats' => $stats,
            'acquisitions' => $acquisitions,
            'projects' => $projects
        ]);
    }

    public function create() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $projectFilter = in_array($roleId, [1, 3, 6]) ? "landman_id IS NOT NULL" : "landman_id = $userId";
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $projectFilter ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $this->view('phase2/land_acquisition/create', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Add Land Acquisition',
            'projects' => $projects
        ]);
    }

    public function store() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 14])) {
            setError('Unauthorized. Only Land Officers or Admin can add land acquisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $project_id = (int)($_POST['project_id'] ?? 0);
        $land_owner_name = $_POST['land_owner_name'] ?? '';
        $land_owner_phone = $_POST['land_owner_phone'] ?? null;
        $total_area = (float)($_POST['total_area'] ?? 0);
        $area_unit = $_POST['area_unit'] ?? 'decimal';
        $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
        $total_amount = $total_area * $price_per_unit;
        $location = $_POST['location'] ?? null;
        $status = $_POST['status'] ?? 'selected';
        $remarks = $_POST['remarks'] ?? null;
        $created_by = (int)$_SESSION['user']['id'];

        $stmt = $this->db->prepare("
            INSERT INTO land_acquisitions 
            (project_id, land_owner_name, land_owner_phone, total_area, area_unit, price_per_unit, total_amount, location, status, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdsddsssi", $project_id, $land_owner_name, $land_owner_phone, $total_area, $area_unit, $price_per_unit, $total_amount, $location, $status, $remarks, $created_by);

        if ($stmt->execute()) {
            setSuccess("Land Acquisition recorded successfully.");
        } else {
            setError("Failed to record Land Acquisition: " . $this->db->error);
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
        exit;
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $acquisition = $this->db->query("
            SELECT la.*, p.name as project_name
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            WHERE la.id = $id
        ")->fetch_assoc();

        if (!$acquisition) {
            setError("Land Acquisition not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        // Fetch bayana payments
        $bayanas = $this->db->query("
            SELECT lb.*, u.full_name as creator_name
            FROM land_bayana_payments lb
            LEFT JOIN users u ON lb.created_by = u.id
            WHERE lb.land_acquisition_id = $id
            ORDER BY lb.payment_date DESC
        ")->fetch_all(MYSQLI_ASSOC);

        // Fetch registration details
        $registration = $this->db->query("
            SELECT lr.*, u.full_name as creator_name
            FROM land_registrations lr
            LEFT JOIN users u ON lr.created_by = u.id
            WHERE lr.land_acquisition_id = $id
        ")->fetch_assoc();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $projectFilter = in_array($roleId, [1, 3, 6]) ? "landman_id IS NOT NULL" : "landman_id = $userId";
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $projectFilter ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        // Calculate total bayana budget approved by accounts (Paid requisitions of type Bayana)
        $bayanaReqResult = $this->db->query("
            SELECT SUM(requested_amount) as total 
            FROM land_fund_requisitions 
            WHERE land_acquisition_id = $id 
              AND requisition_type = 'Bayana' 
              AND accounts_status = 'Paid'
        ");
        $totalBayanaBudget = (float)($bayanaReqResult ? $bayanaReqResult->fetch_assoc()['total'] ?? 0 : 0);

        // Calculate total bayana manually paid by land manager
        $totalBayanaManualPaid = array_sum(array_column($bayanas, 'payment_amount'));

        // If fully paid (manual payments >= approved budget & budget > 0), hide the Add Payment button
        $bayanaPaidFull = $totalBayanaBudget > 0 && $totalBayanaManualPaid >= $totalBayanaBudget;

        $this->view('phase2/land_acquisition/edit', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Manage Land Acquisition',
            'acquisition' => $acquisition,
            'bayanas' => $bayanas,
            'registration' => $registration,
            'projects' => $projects,
            'totalBayanaBudget' => $totalBayanaBudget,
            'totalBayanaManualPaid' => $totalBayanaManualPaid,
            'bayanaPaidFull' => $bayanaPaidFull,
        ]);
    }

    public function update() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 14])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? 'update_info';

        if ($action === 'add_bayana') {
            $payment_amount = (float)($_POST['payment_amount'] ?? 0);
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $payment_method = $_POST['payment_method'] ?? 'Bank';
            $transaction_no = $_POST['transaction_no'] ?? null;
            $bayana_document_link = $_POST['bayana_document_link'] ?? null;
            $remarks = $_POST['remarks'] ?? null;
            $created_by = (int)$_SESSION['user']['id'];

            $stmt = $this->db->prepare("
                INSERT INTO land_bayana_payments 
                (land_acquisition_id, payment_amount, payment_date, payment_method, transaction_no, bayana_document_link, remarks, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("idsssssi", $id, $payment_amount, $payment_date, $payment_method, $transaction_no, $bayana_document_link, $remarks, $created_by);
            if ($stmt->execute()) {
                // Update main land status to bayana
                $this->db->query("UPDATE land_acquisitions SET status = 'bayana' WHERE id = $id AND status = 'selected'");
                setSuccess("Bayana Payment added successfully.");
            } else {
                setError("Failed to add Bayana Payment: " . $this->db->error);
            }
        } elseif ($action === 'update_registration') {
            $registration_no = $_POST['registration_no'] ?? '';
            $registration_date = $_POST['registration_date'] ?? date('Y-m-d');
            $sub_registry_office = $_POST['sub_registry_office'] ?? null;
            $registration_cost = (float)($_POST['registration_cost'] ?? 0);
            $mutation_status = $_POST['mutation_status'] ?? 'Pending';
            $mutation_date = !empty($_POST['mutation_date']) ? $_POST['mutation_date'] : null;
            $deed_document_link = $_POST['deed_document_link'] ?? null;
            $remarks = $_POST['remarks'] ?? null;
            $created_by = (int)$_SESSION['user']['id'];

            // Check if registration exists
            $exists = $this->db->query("SELECT id FROM land_registrations WHERE land_acquisition_id = $id")->fetch_assoc();

            if ($exists) {
                $stmt = $this->db->prepare("
                    UPDATE land_registrations 
                    SET registration_no = ?, registration_date = ?, sub_registry_office = ?, registration_cost = ?, mutation_status = ?, mutation_date = ?, deed_document_link = ?, remarks = ?
                    WHERE land_acquisition_id = ?
                ");
                $stmt->bind_param("sssdssssi", $registration_no, $registration_date, $sub_registry_office, $registration_cost, $mutation_status, $mutation_date, $deed_document_link, $remarks, $id);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO land_registrations 
                    (land_acquisition_id, registration_no, registration_date, sub_registry_office, registration_cost, mutation_status, mutation_date, deed_document_link, remarks, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssdssssi", $id, $registration_no, $registration_date, $sub_registry_office, $registration_cost, $mutation_status, $mutation_date, $deed_document_link, $remarks, $created_by);
            }

            if ($stmt->execute()) {
                // Update main land status to registered
                $this->db->query("UPDATE land_acquisitions SET status = 'registered' WHERE id = $id");
                setSuccess("Registration details updated successfully.");
            } else {
                setError("Failed to update registration: " . $this->db->error);
            }
        } else {
            // Update basic info
            $project_id = (int)($_POST['project_id'] ?? 0);
            $land_owner_name = $_POST['land_owner_name'] ?? '';
            $land_owner_phone = $_POST['land_owner_phone'] ?? null;
            $total_area = (float)($_POST['total_area'] ?? 0);
            $area_unit = $_POST['area_unit'] ?? 'decimal';
            $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
            $total_amount = $total_area * $price_per_unit;
            $location = $_POST['location'] ?? null;
            $status = $_POST['status'] ?? 'selected';
            $remarks = $_POST['remarks'] ?? null;

            $bayana_status = $_POST['bayana_status'] ?? 'pending';
            $bayana_complete_date = !empty($_POST['bayana_complete_date']) ? $_POST['bayana_complete_date'] : null;
            $registration_status = $_POST['registration_status'] ?? 'pending';
            $registration_complete_date = !empty($_POST['registration_complete_date']) ? $_POST['registration_complete_date'] : null;

            $stmt = $this->db->prepare("
                UPDATE land_acquisitions 
                SET project_id = ?, land_owner_name = ?, land_owner_phone = ?, total_area = ?, area_unit = ?, price_per_unit = ?, total_amount = ?, location = ?, status = ?, remarks = ?, bayana_status = ?, bayana_complete_date = ?, registration_status = ?, registration_complete_date = ?
                WHERE id = ?
            ");
            $stmt->bind_param("issdsddsssssssi", $project_id, $land_owner_name, $land_owner_phone, $total_area, $area_unit, $price_per_unit, $total_amount, $location, $status, $remarks, $bayana_status, $bayana_complete_date, $registration_status, $registration_complete_date, $id);

            if ($stmt->execute()) {
                setSuccess("Land Acquisition details updated successfully.");
            } else {
                setError("Failed to update Land Acquisition: " . $this->db->error);
            }
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions/edit?id=" . $id);
        exit;
    }

    public function delete() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            setError('Unauthorized. Only Management or Super Admin can delete records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM land_bayana_payments WHERE land_acquisition_id = $id");
            $this->db->query("DELETE FROM land_registrations WHERE land_acquisition_id = $id");
            $this->db->query("DELETE FROM land_fund_requisitions WHERE land_acquisition_id = $id");
            $this->db->query("DELETE FROM land_acquisitions WHERE id = $id");
            setSuccess("Land Acquisition and all related payments/registrations deleted.");
        } else {
            setError("Invalid record ID.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
        exit;
    }

    public function downloadSampleCsv() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 14])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=land_acquisitions_bulk_import_sample.csv');
        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, [
            'project_name',
            'land_owner_name',
            'land_owner_phone',
            'total_area',
            'area_unit',
            'price_per_unit',
            'location',
            'remarks',
            'land_dept_email',
            'management_email',
            'account_verify_email',
            'req_attachment_filename',
            'bayana_payment_amount',
            'bayana_payment_date',
            'bayana_payment_method',
            'bayana_transaction_no',
            'bayana_document_link',
            'bayana_document_filename',
            'registration_no',
            'registration_date',
            'sub_registry_office',
            'registration_cost',
            'deed_document_link',
            'deed_document_filename',
            'mutation_status',
            'mutation_date'
        ]);

        // Sample Data Row
        fputcsv($output, [
            'The Premium Green Valley',
            'Mr. Farzana Bhuiyan',
            '01910703190',
            '15.5',
            'decimal',
            '1000000',
            'Ashuliya',
            'Selected Ashuliya plot',
            'landman@premium.com',
            'mgmt@premium.com',
            'accounts@premium.com',
            'req_agreement.pdf',
            '500000',
            '2026-06-09',
            'Bank',
            '76544',
            'https://drive.google.com/drive/folders/...',
            'bayana_receipt.pdf',
            'Deed-90231',
            '2026-07-01',
            'Savar Registry Office',
            '1200000',
            'https://drive.google.com/drive/folders/...',
            'deed_copy.pdf',
            'Completed',
            '2026-07-15'
        ]);

        fclose($output);
        exit;
    }

    public function executeImport() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 14])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if ($handle === FALSE) {
            setError('Failed to open CSV file.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            setError('CSV file is empty.');
            fclose($handle);
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $header = array_map('trim', $header);
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM

        $normalizeText = function($str) {
            if ($str === null) return '';
            $str = str_replace(array("\xC2\xA0", "\xA0"), ' ', $str);
            $str = preg_replace('/\s+/', ' ', $str);
            return trim($str);
        };

        // Reconstruct multiple uploaded files array to a clean lookup map
        $uploadedFilesMap = [];
        if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $cleanName = trim($name);
                    $uploadedFilesMap[$cleanName] = [
                        'name' => $name,
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                        'error' => $_FILES['attachments']['error'][$i]
                    ];
                }
            }
        }

        $validationErrors = [];
        $rowsData = [];
        $rowNum = 1;

        // Cache users and projects for faster lookup and validation
        $usersCache = [];
        $projectsCache = [];

        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if (empty(array_filter($data))) continue;

            if (count($header) !== count($data)) {
                $validationErrors[] = "Row $rowNum: Column count mismatch (expected " . count($header) . ", got " . count($data) . ").";
                continue;
            }

            $row = array_combine($header, $data);
            $row = array_map('trim', $row);
            
            // Clean/normalize text fields
            $projectName = $normalizeText($row['project_name'] ?? '');
            $landOwnerName = trim($row['land_owner_name'] ?? '');
            $totalArea = (float)($row['total_area'] ?? 0);
            $pricePerUnit = (float)($row['price_per_unit'] ?? 0);
            $landDeptEmail = trim($row['land_dept_email'] ?? '');
            $location = trim($row['location'] ?? '');
            
            $rowIssues = [];

            // Validate Required Fields
            if (empty($projectName)) {
                $rowIssues[] = "'project_name' is required";
            }
            if (empty($landOwnerName)) {
                $rowIssues[] = "'land_owner_name' is required";
            }
            if ($totalArea <= 0) {
                $rowIssues[] = "'total_area' must be greater than 0";
            }
            if ($pricePerUnit <= 0) {
                $rowIssues[] = "'price_per_unit' must be greater than 0";
            }
            if (empty($landDeptEmail)) {
                $rowIssues[] = "'land_dept_email' is required";
            }

            // Verify Project Name in ERP (case-insensitive + relaxed spaces matching)
            if (!empty($projectName)) {
                if (isset($projectsCache[$projectName])) {
                    $row['project_id'] = $projectsCache[$projectName];
                } else {
                    $normalizedSearch = '%' . str_replace(' ', '%', $projectName) . '%';
                    $stmtProj = $this->db->prepare("SELECT id FROM projects WHERE (name = ? OR name LIKE ?) AND deleted_at IS NULL LIMIT 1");
                    $stmtProj->bind_param("ss", $projectName, $normalizedSearch);
                    $stmtProj->execute();
                    $resProj = $stmtProj->get_result()->fetch_assoc();
                    if ($resProj) {
                        $projectsCache[$projectName] = $resProj['id'];
                        $row['project_id'] = $resProj['id'];
                    } else {
                        $rowIssues[] = "Project '$projectName' not found in ERP";
                    }
                }
            }

            // Helper to lookup user by email
            $getUserByEmail = function($email) use (&$usersCache) {
                if (empty($email)) return null;
                if (isset($usersCache[$email])) return $usersCache[$email];
                
                $stmtUser = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtUser->bind_param("s", $email);
                $stmtUser->execute();
                $resUser = $stmtUser->get_result()->fetch_assoc();
                if ($resUser) {
                    $usersCache[$email] = $resUser['id'];
                    return $resUser['id'];
                }
                return null;
            };

            // Verify Land Dept Email
            if (!empty($landDeptEmail)) {
                $uid = $getUserByEmail($landDeptEmail);
                if ($uid) {
                    $row['land_dept_id'] = $uid;
                } else {
                    $rowIssues[] = "Land officer email '$landDeptEmail' not found in ERP";
                }
            }

            // Verify Mgmt Email (Optional, fallback to current user)
            $mgmtEmail = trim($row['management_email'] ?? '');
            if (!empty($mgmtEmail)) {
                $uid = $getUserByEmail($mgmtEmail);
                if ($uid) {
                    $row['management_id'] = $uid;
                } else {
                    $rowIssues[] = "Management email '$mgmtEmail' not found in ERP";
                }
            } else {
                $row['management_id'] = (int)$_SESSION['user']['id'];
            }

            // Verify Account Verify Email (Optional, fallback to current user)
            $accEmail = trim($row['account_verify_email'] ?? '');
            if (!empty($accEmail)) {
                $uid = $getUserByEmail($accEmail);
                if ($uid) {
                    $row['account_id'] = $uid;
                } else {
                    $rowIssues[] = "Accountant email '$accEmail' not found in ERP";
                }
            } else {
                $row['account_id'] = (int)$_SESSION['user']['id'];
            }

            if (!empty($rowIssues)) {
                $validationErrors[] = [
                    'row' => $rowNum,
                    'landowner' => $landOwnerName ?: '—',
                    'project' => $projectName ?: '—',
                    'location' => $location ?: '—',
                    'reason' => implode(' | ', $rowIssues)
                ];
            }

            $rowsData[] = $row;
        }
        fclose($handle);

        // If there are validation errors, fail the entire import and display them
        if (!empty($validationErrors)) {
            $_SESSION['land_csv_import_skipped'] = $validationErrors;
            setError("Import failed! " . count($validationErrors) . " row(s) had errors. None were imported.");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        // Helper to save attachment and return public relative path
        $saveAttachment = function($fileName) use ($uploadedFilesMap) {
            if (empty($fileName)) return null;
            $fileName = trim($fileName);
            if (!isset($uploadedFilesMap[$fileName])) return null;

            $tempFile = $uploadedFilesMap[$fileName]['tmp_name'];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueName = 'land_imported_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0777, true);
            }

            if (move_uploaded_file($tempFile, $destDir . $uniqueName)) {
                return 'uploads/receipts/' . $uniqueName;
            }
            return null;
        };

        // Helper to format date string to MySQL-compatible format
        $formatDateForDb = function($dateStr, $includeTime = false, $default = null) {
            if (empty($dateStr)) return $default;
            $dateStr = trim($dateStr);
            $time = strtotime($dateStr);
            if ($time === false || $time <= 0) {
                $normalized = str_replace('/', '-', $dateStr);
                $time = strtotime($normalized);
            }
            if ($time !== false && $time > 0) {
                return $includeTime ? date('Y-m-d H:i:s', $time) : date('Y-m-d', $time);
            }
            return $default;
        };

        // All rows are valid, start database transaction for insertion
        $this->db->begin_transaction();
        $importedCount = 0;

        try {
            foreach ($rowsData as $row) {
                $projectId = (int)$row['project_id'];
                $landOwnerName = trim($row['land_owner_name']);
                $landOwnerPhone = !empty($row['land_owner_phone']) ? trim($row['land_owner_phone']) : null;
                $totalArea = (float)$row['total_area'];
                $areaUnit = !empty($row['area_unit']) ? trim($row['area_unit']) : 'decimal';
                $pricePerUnit = (float)$row['price_per_unit'];
                $totalAmount = $totalArea * $pricePerUnit;
                $location = !empty($row['location']) ? trim($row['location']) : null;
                $remarks = !empty($row['remarks']) ? trim($row['remarks']) : null;
                $landDeptId = (int)$row['land_dept_id'];
                $mgmtId = (int)$row['management_id'];
                $accId = (int)$row['account_id'];

                $bayanaAmount = (float)($row['bayana_payment_amount'] ?? 0);
                
                $bayanaDateRaw = !empty($row['bayana_payment_date']) ? trim($row['bayana_payment_date']) : '';
                $bayanaDate = $formatDateForDb($bayanaDateRaw, false, date('Y-m-d'));
                $bayanaTimestamp = $formatDateForDb($bayanaDateRaw, true, date('Y-m-d H:i:s'));
                
                $bayanaMethod = !empty($row['bayana_payment_method']) ? trim($row['bayana_payment_method']) : 'Bank';
                $bayanaTxn = !empty($row['bayana_transaction_no']) ? trim($row['bayana_transaction_no']) : null;
                $bayanaDocLink = !empty($row['bayana_document_link']) ? trim($row['bayana_document_link']) : null;
                $bayanaRemarks = !empty($row['bayana_remarks']) ? trim($row['bayana_remarks']) : null;

                $regNo = !empty($row['registration_no']) ? trim($row['registration_no']) : null;
                
                $regDateRaw = !empty($row['registration_date']) ? trim($row['registration_date']) : '';
                $regDate = $formatDateForDb($regDateRaw, false, null);
                $regTimestamp = $formatDateForDb($regDateRaw, true, null);
                
                $regOffice = !empty($row['sub_registry_office']) ? trim($row['sub_registry_office']) : null;
                $regCost = (float)($row['registration_cost'] ?? 0);
                $deedDocLink = !empty($row['deed_document_link']) ? trim($row['deed_document_link']) : null;
                $regRemarks = !empty($row['registration_remarks']) ? trim($row['registration_remarks']) : null;
                $mutationStatus = !empty($row['mutation_status']) ? trim($row['mutation_status']) : 'Pending';
                
                $mutationDateRaw = !empty($row['mutation_date']) ? trim($row['mutation_date']) : '';
                $mutationDate = $formatDateForDb($mutationDateRaw, false, null);

                // Resolve initial status
                $status = 'selected';
                $bayanaStatus = 'pending';
                $registrationStatus = 'pending';

                if ($regCost > 0 || !empty($regNo)) {
                    $status = 'registered';
                    $bayanaStatus = 'complete';
                    $registrationStatus = 'complete';
                    if (empty($regDate)) $regDate = date('Y-m-d');
                } elseif ($bayanaAmount > 0) {
                    $status = 'bayana';
                    $bayanaStatus = 'complete';
                }

                // 1. Insert into land_acquisitions
                $stmtAcq = $this->db->prepare("
                    INSERT INTO land_acquisitions 
                    (project_id, land_owner_name, land_owner_phone, total_area, area_unit, price_per_unit, total_amount, location, status, remarks, created_by, bayana_status, bayana_complete_date, registration_status, registration_complete_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtAcq->bind_param(
                    "issdsddssssssss",
                    $projectId, $landOwnerName, $landOwnerPhone, $totalArea, $areaUnit, $pricePerUnit, $totalAmount, $location, $status, $remarks, $landDeptId, $bayanaStatus, $bayanaDate, $registrationStatus, $regDate
                );
                if (!$stmtAcq->execute()) {
                    throw new Exception("Failed to insert land acquisition: " . $stmtAcq->error);
                }
                $landAcqId = $this->db->insert_id;

                // 2. If Bayana Payment is defined
                if ($bayanaAmount > 0) {
                    $reqAttachment = $saveAttachment($row['req_attachment_filename'] ?? '');
                    $bayanaAttachment = $saveAttachment($row['bayana_document_filename'] ?? '');

                    // Create Requisition of type Bayana (Approved & Paid)
                    $stmtReqB = $this->db->prepare("
                        INSERT INTO land_fund_requisitions 
                        (land_acquisition_id, requisition_type, requested_amount, purpose, req_attachment_path, requested_by, mgmt_status, mgmt_by, mgmt_note, mgmt_approved_at, accounts_status, accounts_by, accounts_note, accounts_paid_at, payment_method, transaction_no, attachment_path)
                        VALUES (?, 'Bayana', ?, ?, ?, ?, 'Approved', ?, 'Auto-approved during bulk import', ?, 'Paid', ?, 'Auto-paid during bulk import', ?, ?, ?, ?)
                    ");
                    $purposeB = "Historic Bayana Payment for " . $landOwnerName;
                    $stmtReqB->bind_param(
                        "idssiisissss",
                        $landAcqId, $bayanaAmount, $purposeB, $reqAttachment, $landDeptId, $mgmtId, $bayanaTimestamp, $accId, $bayanaTimestamp, $bayanaMethod, $bayanaTxn, $bayanaAttachment
                    );
                    if (!$stmtReqB->execute()) {
                        throw new Exception("Failed to insert Bayana Requisition: " . $stmtReqB->error);
                    }

                    // Insert into land_bayana_payments
                    $stmtPay = $this->db->prepare("
                        INSERT INTO land_bayana_payments 
                        (land_acquisition_id, payment_amount, payment_date, payment_method, transaction_no, bayana_document_link, remarks, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtPay->bind_param("idsssssi", $landAcqId, $bayanaAmount, $bayanaDate, $bayanaMethod, $bayanaTxn, $bayanaDocLink, $bayanaRemarks, $landDeptId);
                    if (!$stmtPay->execute()) {
                        throw new Exception("Failed to insert Bayana Payment: " . $stmtPay->error);
                    }
                }

                // 3. If Registration Cost / Deed is defined
                if ($regCost > 0 || !empty($regNo)) {
                    $deedAttachment = $saveAttachment($row['deed_document_filename'] ?? '');

                    // Create Requisition of type Registration (Approved & Paid)
                    $stmtReqR = $this->db->prepare("
                        INSERT INTO land_fund_requisitions 
                        (land_acquisition_id, requisition_type, requested_amount, purpose, requested_by, mgmt_status, mgmt_by, mgmt_note, mgmt_approved_at, accounts_status, accounts_by, accounts_note, accounts_paid_at, payment_method, transaction_no, attachment_path)
                        VALUES (?, 'Registration', ?, ?, ?, 'Approved', ?, 'Auto-approved during bulk import', ?, 'Paid', ?, 'Auto-paid during bulk import', ?, 'Bank', ?, ?)
                    ");
                    $purposeR = "Historic Registration Cost for " . $landOwnerName;
                    $stmtReqR->bind_param(
                        "idsiisisss",
                        $landAcqId, $regCost, $purposeR, $landDeptId, $mgmtId, $regTimestamp, $accId, $regTimestamp, $regNo, $deedAttachment
                    );
                    if (!$stmtReqR->execute()) {
                        throw new Exception("Failed to insert Registration Requisition: " . $stmtReqR->error);
                    }

                    // Insert into land_registrations
                    $stmtReg = $this->db->prepare("
                        INSERT INTO land_registrations 
                        (land_acquisition_id, registration_no, registration_date, sub_registry_office, registration_cost, mutation_status, mutation_date, deed_document_link, remarks, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtReg->bind_param(
                        "isssdssssi",
                        $landAcqId, $regNo, $regDate, $regOffice, $regCost, $mutationStatus, $mutationDate, $deedDocLink, $regRemarks, $landDeptId
                    );
                    if (!$stmtReg->execute()) {
                        throw new Exception("Failed to insert Land Registration record: " . $stmtReg->error);
                    }
                }

                $importedCount++;
            }

            $this->db->commit();
            setSuccess("Successfully imported $importedCount Land Purchase portfolios.");
        } catch (Exception $e) {
            $this->db->rollback();
            setError("Failed to import CSV records. Transaction rolled back. Error: " . $e->getMessage());
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
        exit;
    }
}
