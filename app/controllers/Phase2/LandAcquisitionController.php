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

        // Fetch accounts paid amounts & pending pipeline from land_fund_requisitions
        $paidLandStats = $this->db->query("
            SELECT 
                SUM(CASE WHEN requisition_type IN ('Bayana', 'Registration') AND accounts_status = 'Paid' THEN requested_amount ELSE 0 END) as total_paid_land,
                SUM(CASE WHEN requisition_type IN ('Extra Cost', 'Other') AND accounts_status = 'Paid' THEN requested_amount ELSE 0 END) as total_paid_extra,
                SUM(CASE WHEN accounts_status = 'Paid' THEN requested_amount ELSE 0 END) as total_paid_all,
                SUM(CASE WHEN mgmt_status = 'Pending' THEN requested_amount ELSE 0 END) as pending_mgmt_amount,
                COUNT(CASE WHEN mgmt_status = 'Pending' THEN id END) as pending_mgmt_count,
                SUM(CASE WHEN mgmt_status = 'Approved' AND accounts_status = 'Pending' THEN requested_amount ELSE 0 END) as pending_accounts_amount,
                COUNT(CASE WHEN mgmt_status = 'Approved' AND accounts_status = 'Pending' THEN id END) as pending_accounts_count
            FROM land_fund_requisitions
        ")->fetch_assoc();

        $stats['total_paid_land'] = (float)($paidLandStats['total_paid_land'] ?? 0);
        $stats['total_paid_extra'] = (float)($paidLandStats['total_paid_extra'] ?? 0);
        $stats['total_paid_all'] = (float)($paidLandStats['total_paid_all'] ?? 0);
        $stats['total_land_due'] = max(0, (float)($stats['total_value'] ?? 0) - $stats['total_paid_land']);
        $stats['pending_mgmt_amount'] = (float)($paidLandStats['pending_mgmt_amount'] ?? 0);
        $stats['pending_mgmt_count'] = (int)($paidLandStats['pending_mgmt_count'] ?? 0);
        $stats['pending_accounts_amount'] = (float)($paidLandStats['pending_accounts_amount'] ?? 0);
        $stats['pending_accounts_count'] = (int)($paidLandStats['pending_accounts_count'] ?? 0);

        // Fetch total bayana paid (legacy manual entries)
        $bayanaPaid = $this->db->query("SELECT SUM(payment_amount) as total FROM land_bayana_payments")->fetch_assoc()['total'] ?? 0;
        
        // Fetch total registration cost paid
        $regCost = $this->db->query("SELECT SUM(registration_cost) as total FROM land_registrations")->fetch_assoc()['total'] ?? 0;

        $stats['total_bayana_paid'] = $bayanaPaid;
        $stats['total_reg_cost'] = $regCost;

        // Fetch land list
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $where = "1=1";
        if (!in_array($roleId, [1, 3, 14, 15, 20, 22, 32, 33, 6, 30])) {
            $where = "(p.landman_id = $userId OR la.created_by = $userId)";
        }

        $selectedProjectId = (int)($_GET['project_id'] ?? 0);
        if ($selectedProjectId > 0) {
            $where .= " AND la.project_id = $selectedProjectId";
        }

        $result = $this->db->query("
            SELECT la.*, 
                   COALESCE(NULLIF(TRIM(la.land_project_name), ''), p.name, 'Tentative Land Deal') as effective_project_name,
                   p.name as project_name, 
                   u.full_name as creator_name,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' AND lfr.requisition_type IN ('Bayana', 'Registration') THEN lfr.requested_amount ELSE 0 END), 0) as paid_land_amount,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' AND lfr.requisition_type IN ('Extra Cost', 'Other') THEN lfr.requested_amount ELSE 0 END), 0) as paid_extra_cost,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' THEN lfr.requested_amount ELSE 0 END), 0) as total_paid_deal,
                   COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_mgmt_amount,
                   COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_accounts_amount,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Pending' AND lfr.mgmt_status != 'Rejected' THEN lfr.requested_amount ELSE 0 END), 0) as total_pending_deal,
                   COUNT(DISTINCT lfr.id) as req_count,
                   COUNT(DISTINCT CASE WHEN lfr.accounts_status = 'Paid' THEN lfr.id END) as paid_req_count,
                   COUNT(DISTINCT CASE WHEN lfr.mgmt_status = 'Pending' THEN lfr.id END) as pending_mgmt_count,
                   COUNT(DISTINCT CASE WHEN lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending' THEN lfr.id END) as pending_accounts_count
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN users u ON la.created_by = u.id
            LEFT JOIN land_fund_requisitions lfr ON la.id = lfr.land_acquisition_id
            WHERE $where
            GROUP BY la.id
            ORDER BY la.id DESC
        ");
        $acquisitions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // All active projects from project lists, prioritizing Planning status
        $projects = $this->db->query("
            SELECT id, name, project_status 
            FROM projects 
            WHERE deleted_at IS NULL 
            ORDER BY (CASE WHEN project_status = 'Planning' THEN 0 ELSE 1 END), name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_acquisition/index', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Land Acquisitions Portfolio',
            'stats' => $stats,
            'acquisitions' => $acquisitions,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId
        ]);
    }

    public function create() {
        $projects = $this->db->query("
            SELECT id, name, project_status 
            FROM projects 
            WHERE deleted_at IS NULL 
            ORDER BY (CASE WHEN project_status = 'Planning' THEN 0 ELSE 1 END), name ASC
        ")->fetch_all(MYSQLI_ASSOC);
        $this->view('phase2/land_acquisition/create', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Add Land Acquisition',
            'projects' => $projects
        ]);
    }

    public function store() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 14, 15, 22, 33])) {
            setError('Unauthorized. Only Land Officers or Admin can add land acquisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-acquisitions");
            exit;
        }

        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $land_project_name = trim($_POST['land_project_name'] ?? '');
        if (empty($land_project_name) && !empty($project_id)) {
            $pRow = $this->db->query("SELECT name FROM projects WHERE id = $project_id LIMIT 1")->fetch_assoc();
            $land_project_name = $pRow['name'] ?? '';
        }
        $land_owner_name = trim($_POST['land_owner_name'] ?? '');
        $land_owner_phone = !empty($_POST['land_owner_phone']) ? trim($_POST['land_owner_phone']) : null;
        $land_receiver_name = !empty($_POST['land_receiver_name']) ? trim($_POST['land_receiver_name']) : null;
        $media_broker_info = !empty($_POST['media_broker_info']) ? trim($_POST['media_broker_info']) : null;
        $power_of_attorney = !empty($_POST['power_of_attorney']) ? trim($_POST['power_of_attorney']) : null;
        $total_area = (float)($_POST['total_area'] ?? 0);
        $area_unit = $_POST['area_unit'] ?? 'decimal';
        $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
        $total_amount = $total_area * $price_per_unit;
        $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
        $status = 'selected'; // Default initial status
        $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
        $created_by = (int)$_SESSION['user']['id'];

        $stmt = $this->db->prepare("
            INSERT INTO land_acquisitions 
            (project_id, land_project_name, land_owner_name, land_owner_phone, land_receiver_name, media_broker_info, power_of_attorney, total_area, area_unit, price_per_unit, total_amount, location, status, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssssdsddsssi", $project_id, $land_project_name, $land_owner_name, $land_owner_phone, $land_receiver_name, $media_broker_info, $power_of_attorney, $total_area, $area_unit, $price_per_unit, $total_amount, $location, $status, $remarks, $created_by);

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

        // Fetch all requisitions for this land
        $requisitions = $this->db->query("
            SELECT lfr.*, 
                   u_req.full_name as requester_name,
                   u_mgmt.full_name as mgmt_name,
                   u_acc.full_name as accounts_name
            FROM land_fund_requisitions lfr
            LEFT JOIN users u_req ON lfr.requested_by = u_req.id
            LEFT JOIN users u_mgmt ON lfr.mgmt_by = u_mgmt.id
            LEFT JOIN users u_acc ON lfr.accounts_by = u_acc.id
            WHERE lfr.land_acquisition_id = $id
            ORDER BY lfr.id ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // Calculate running land valuation payments and extra costs
        $cumLandPaid = 0;
        $cumExtraPaid = 0;
        $totalLandValuation = (float)$acquisition['total_amount'];

        foreach ($requisitions as &$req) {
            $isPaid = ($req['accounts_status'] === 'Paid');
            $reqAmt = (float)$req['requested_amount'];
            $isLandCost = in_array($req['requisition_type'], ['Bayana', 'Registration']);

            if ($isPaid) {
                if ($isLandCost) {
                    $cumLandPaid += $reqAmt;
                } else {
                    $cumExtraPaid += $reqAmt;
                }
            }
            $req['cum_land_paid'] = $cumLandPaid;
            $req['remaining_land_due'] = max(0, $totalLandValuation - $cumLandPaid);
            $req['is_land_cost'] = $isLandCost;
        }
        unset($req);

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

        $projects = $this->db->query("
            SELECT id, name, project_status 
            FROM projects 
            WHERE deleted_at IS NULL 
            ORDER BY (CASE WHEN project_status = 'Planning' THEN 0 ELSE 1 END), name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        $totalBayanaBudget = $cumLandPaid;
        $totalBayanaManualPaid = array_sum(array_column($bayanas, 'payment_amount'));
        $bayanaPaidFull = $totalBayanaBudget > 0 && $totalBayanaManualPaid >= $totalBayanaBudget;

        $this->view('phase2/land_acquisition/edit', [
            'current_route' => 'con-land-acquisitions',
            'page_title' => 'Manage Land Acquisition',
            'acquisition' => $acquisition,
            'requisitions' => $requisitions,
            'cumLandPaid' => $cumLandPaid,
            'cumExtraPaid' => $cumExtraPaid,
            'remainingLandDue' => max(0, $totalLandValuation - $cumLandPaid),
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
        if (!in_array($roleId, [1, 3, 33, 14])) {
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
            $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $land_project_name = trim($_POST['land_project_name'] ?? '');
            if (empty($land_project_name) && !empty($project_id)) {
                $pRow = $this->db->query("SELECT name FROM projects WHERE id = $project_id LIMIT 1")->fetch_assoc();
                $land_project_name = $pRow['name'] ?? '';
            }
            $land_owner_name = trim($_POST['land_owner_name'] ?? '');
            $land_owner_phone = !empty($_POST['land_owner_phone']) ? trim($_POST['land_owner_phone']) : null;
            $land_receiver_name = !empty($_POST['land_receiver_name']) ? trim($_POST['land_receiver_name']) : null;
            $media_broker_info = !empty($_POST['media_broker_info']) ? trim($_POST['media_broker_info']) : null;
            $power_of_attorney = !empty($_POST['power_of_attorney']) ? trim($_POST['power_of_attorney']) : null;
            $total_area = (float)($_POST['total_area'] ?? 0);
            $area_unit = $_POST['area_unit'] ?? 'decimal';
            $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
            $total_amount = $total_area * $price_per_unit;
            $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
            $status = $_POST['status'] ?? 'selected';
            $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

            $bayana_status = $_POST['bayana_status'] ?? 'pending';
            $bayana_complete_date = !empty($_POST['bayana_complete_date']) ? $_POST['bayana_complete_date'] : null;
            $registration_status = $_POST['registration_status'] ?? 'pending';
            $registration_complete_date = !empty($_POST['registration_complete_date']) ? $_POST['registration_complete_date'] : null;

            $stmt = $this->db->prepare("
                UPDATE land_acquisitions 
                SET project_id = ?, land_project_name = ?, land_owner_name = ?, land_owner_phone = ?, land_receiver_name = ?, media_broker_info = ?, power_of_attorney = ?, total_area = ?, area_unit = ?, price_per_unit = ?, total_amount = ?, location = ?, status = ?, remarks = ?, bayana_status = ?, bayana_complete_date = ?, registration_status = ?, registration_complete_date = ?
                WHERE id = ?
            ");
            $stmt->bind_param("issssssdsddsssssssi", $project_id, $land_project_name, $land_owner_name, $land_owner_phone, $land_receiver_name, $media_broker_info, $power_of_attorney, $total_area, $area_unit, $price_per_unit, $total_amount, $location, $status, $remarks, $bayana_status, $bayana_complete_date, $registration_status, $registration_complete_date, $id);

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
        if (!in_array($roleId, [1, 3, 33])) {
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
        if (!in_array($roleId, [1, 3, 33, 14])) {
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
            'land_project_name',
            'land_owner_name',
            'land_owner_phone',
            'land_receiver_name',
            'media_broker_info',
            'power_of_attorney',
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
            'Project No 60 (New)',
            'Mr. Farzana Bhuiyan',
            '01910703190',
            'Alfesani Shuvo',
            'Broker Jamal (01700000000), 2% Commission',
            'Power of Attorney registered deed #1234, dated 10/05/2026',
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
        if (!in_array($roleId, [1, 3, 33, 14])) {
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

        $header = array_map(function($h) {
            $h = trim($h);
            $h = ltrim($h, "\xEF\xBB\xBF");
            return strtolower(str_replace(' ', '_', $h));
        }, $header);

        $normalizeText = function($str) {
            if ($str === null) return '';
            $str = str_replace(array("\xC2\xA0", "\xA0"), ' ', $str);
            $str = preg_replace('/\s+/', ' ', $str);
            return trim($str);
        };

        // Helper to parse numbers safely even if formatted with commas or currency symbols (e.g. "1,200,000")
        $parseNumber = function($val) {
            if ($val === null) return 0.0;
            $str = trim((string)$val);
            if ($str === '' || $str === '-' || strtolower($str) === 'n/a' || strtolower($str) === 'null') return 0.0;
            $cleaned = preg_replace('/[^\d.]/', '', $str);
            return is_numeric($cleaned) ? (float)$cleaned : 0.0;
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

        // Helper to lookup user by email, username, full name, or ID
        $getUserIdentifier = function($val) use (&$usersCache) {
            if (empty($val)) return null;
            $cleanVal = trim($val);
            if (isset($usersCache[$cleanVal])) return $usersCache[$cleanVal];
            
            $stmtUser = $this->db->prepare("SELECT id FROM users WHERE email = ? OR user_name = ? OR full_name = ? OR id = ? LIMIT 1");
            $stmtUser->bind_param("ssss", $cleanVal, $cleanVal, $cleanVal, $cleanVal);
            $stmtUser->execute();
            $resUser = $stmtUser->get_result()->fetch_assoc();
            if ($resUser) {
                $usersCache[$cleanVal] = (int)$resUser['id'];
                return (int)$resUser['id'];
            }
            return null;
        };

        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if (empty(array_filter($data))) continue;

            if (count($header) !== count($data)) {
                $validationErrors[] = [
                    'row' => $rowNum,
                    'landowner' => '—',
                    'project' => '—',
                    'location' => '—',
                    'reason' => "Column count mismatch (expected " . count($header) . ", got " . count($data) . "). Please check for extra/missing commas."
                ];
                continue;
            }

            $row = array_combine($header, $data);
            $row = array_map('trim', $row);
            
            // Clean/normalize text fields
            $projectName = $normalizeText($row['project_name'] ?? $row['project'] ?? '');
            $landProjectName = trim($row['land_project_name'] ?? $row['tentative_project_name'] ?? '');
            $landOwnerName = trim($row['land_owner_name'] ?? $row['landowner'] ?? '');
            $landReceiverName = trim($row['land_receiver_name'] ?? $row['receiver_name'] ?? '');
            $mediaBrokerInfo = trim($row['media_broker_info'] ?? $row['broker_info'] ?? $row['media'] ?? '');
            $powerOfAttorney = trim($row['power_of_attorney'] ?? $row['poa'] ?? '');
            $totalArea = $parseNumber($row['total_area'] ?? $row['area'] ?? 0);
            $pricePerUnit = $parseNumber($row['price_per_unit'] ?? $row['rate'] ?? 0);
            $landDeptEmail = trim($row['land_dept_email'] ?? $row['uploaded_by'] ?? $row['created_by'] ?? '');
            $location = trim($row['location'] ?? '');
            
            // Skip trailing blank row if all key identifiers are empty
            if (empty($projectName) && empty($landOwnerName) && empty($landProjectName)) {
                continue;
            }

            $rowIssues = [];

            // Validate Required Fields
            if (empty($projectName) && empty($landProjectName)) {
                $rowIssues[] = "Either 'project_name' or 'land_project_name' is required";
            }
            if (empty($landOwnerName)) {
                $rowIssues[] = "'land_owner_name' is required";
            }
            if ($totalArea <= 0) {
                $rowIssues[] = "'total_area' must be greater than 0";
            }
            
            // If price_per_unit is empty, derive from bayana payment or allow 0 instead of failing
            if ($pricePerUnit <= 0) {
                $bayanaAmt = $parseNumber($row['bayana_payment_amount'] ?? 0);
                if ($bayanaAmt > 0 && $totalArea > 0) {
                    $pricePerUnit = round($bayanaAmt / $totalArea, 2);
                    $row['price_per_unit'] = $pricePerUnit;
                }
            }

            // Verify Project Name in ERP (if matched, link project_id; if tentative, keep land_project_name)
            $row['project_id'] = null;
            $row['land_project_name'] = !empty($landProjectName) ? $landProjectName : $projectName;
            $row['land_receiver_name'] = !empty($landReceiverName) ? $landReceiverName : null;
            $row['media_broker_info'] = !empty($mediaBrokerInfo) ? $mediaBrokerInfo : null;
            $row['power_of_attorney'] = !empty($powerOfAttorney) ? $powerOfAttorney : null;

            if (!empty($projectName)) {
                $projKey = strtolower(trim($projectName));
                if (isset($projectsCache[$projKey])) {
                    $row['project_id'] = $projectsCache[$projKey]['id'];
                    $row['project_name'] = $projectsCache[$projKey]['name'];
                    if (empty($landProjectName)) {
                        $row['land_project_name'] = $projectsCache[$projKey]['name'];
                    }
                } else {
                    $cleanProjName = trim($projectName);

                    // Expand common acronyms used in Bangladesh real estate spreadsheets:
                    // TP -> The Premium, TPSS -> The Premium Studio Suite, Vall -> Valley, Lakeview -> Lake View, South Point -> Southpoint
                    $expandedProjName = preg_replace('/\bTPSS\b/i', 'The Premium Studio Suite', $cleanProjName);
                    $expandedProjName = preg_replace('/\bTP\b/i', 'The Premium', $expandedProjName);
                    $expandedProjName = preg_replace('/\bVall\b/i', 'Valley', $expandedProjName);
                    $expandedProjName = preg_replace('/\bLakeview\b/i', 'Lake View', $expandedProjName);
                    $expandedProjName = preg_replace('/\bSouth Point\b/i', 'Southpoint', $expandedProjName);
                    $expandedProjName = preg_replace('/\bSuits\b/i', 'Suite', $expandedProjName);
                    $expandedProjName = preg_replace('/[-\s]+/', ' ', $expandedProjName);
                    $expandedProjName = trim($expandedProjName);

                    $normalizedSearch = '%' . str_replace(' ', '%', $cleanProjName) . '%';
                    $normalizedSearchExp = '%' . str_replace(' ', '%', $expandedProjName) . '%';

                    $stmtProj = $this->db->prepare("
                        SELECT id, name FROM projects 
                        WHERE (LOWER(TRIM(name)) = LOWER(?) 
                            OR LOWER(TRIM(name)) = LOWER(?) 
                            OR LOWER(name) LIKE LOWER(?)
                            OR LOWER(name) LIKE LOWER(?)) 
                          AND deleted_at IS NULL 
                        ORDER BY (CASE 
                            WHEN LOWER(TRIM(name)) = LOWER(?) THEN 0 
                            WHEN LOWER(TRIM(name)) = LOWER(?) THEN 1 
                            ELSE 2 END) 
                        LIMIT 1
                    ");
                    $stmtProj->bind_param("ssssss", $cleanProjName, $expandedProjName, $normalizedSearch, $normalizedSearchExp, $cleanProjName, $expandedProjName);
                    $stmtProj->execute();
                    $resProj = $stmtProj->get_result()->fetch_assoc();
                    if ($resProj) {
                        $projectsCache[$projKey] = $resProj;
                        $row['project_id'] = (int)$resProj['id'];
                        $row['project_name'] = $resProj['name'];
                        if (empty($landProjectName)) {
                            $row['land_project_name'] = $resProj['name'];
                        }
                    } else {
                        // Project not in ERP projects table: since project is tentative/planning, set project_id = null and keep land_project_name
                        $row['project_id'] = null;
                        if (empty($landProjectName)) {
                            $row['land_project_name'] = $projectName;
                        }
                    }
                }
            }

            // Verify Land Dept / Uploaded By User (Graceful fallback to logged-in user)
            if (!empty($landDeptEmail)) {
                $uid = $getUserIdentifier($landDeptEmail);
                $row['land_dept_id'] = $uid ?: (int)($_SESSION['user']['id'] ?? 1);
            } else {
                $row['land_dept_id'] = (int)($_SESSION['user']['id'] ?? 1);
            }

            // Verify Mgmt Email (Optional, fallback to current user)
            $mgmtEmail = trim($row['management_email'] ?? $row['approved_by'] ?? '');
            if (!empty($mgmtEmail)) {
                $uid = $getUserIdentifier($mgmtEmail);
                $row['management_id'] = $uid ?: (int)($_SESSION['user']['id'] ?? 1);
            } else {
                $row['management_id'] = (int)($_SESSION['user']['id'] ?? 1);
            }

            // Verify Account Verify Email (Optional, fallback to current user)
            $accEmail = trim($row['account_verify_email'] ?? $row['paid_by'] ?? '');
            if (!empty($accEmail)) {
                $uid = $getUserIdentifier($accEmail);
                $row['account_id'] = $uid ?: (int)($_SESSION['user']['id'] ?? 1);
            } else {
                $row['account_id'] = (int)($_SESSION['user']['id'] ?? 1);
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

        // Helper to format any date string (Excel serial, DD/MM/YYYY, YYYY-MM-DD, textual months, multi-line deeds, etc.) to MySQL-compatible format
        $formatDateForDb = function($dateStr, $includeTime = false, $default = null) {
            if ($dateStr === null) return $default;
            $dateStr = trim((string)$dateStr);
            if ($dateStr === '' || $dateStr === '-' || strtolower($dateStr) === 'null' || strtolower($dateStr) === 'n/a' || strtolower($dateStr) === 'not found') {
                return $default;
            }

            // 1. If Excel numeric serial date (e.g. 45487 for July 15, 2024)
            if (is_numeric($dateStr) && (float)$dateStr > 20000 && (float)$dateStr < 80000) {
                $unixTimestamp = ((float)$dateStr - 25569) * 86400;
                return $includeTime ? gmdate('Y-m-d H:i:s', (int)$unixTimestamp) : gmdate('Y-m-d', (int)$unixTimestamp);
            }

            // 2. Pre-clean typos like missing slash before year "22/102025" -> "22/10/2025"
            $candidate = preg_replace('/(\d{1,2})[\.\/-](\d{1,2})(20\d{2})/', '$1/$2/$3', $dateStr);

            // Pre-extract first date if cell contains multiple dates or multi-line (e.g. "28.09.2025/29.09.2025" or multiple deeds)
            if (preg_match('/(\d{1,2})[\.\/-](\d{1,2}|[a-zA-Z]{3,9})[\.\/-](\d{2,4})/', $candidate, $m)) {
                $yr = (int)$m[3];
                if ($yr < 100) {
                    $yr += 2000;
                }
                $candidate = $m[1] . '-' . $m[2] . '-' . $yr;
            }

            // 3. Normalize dots/underscores to dashes
            $cleanDate = str_replace(['.', '_'], '-', $candidate);

            $formats = [
                'd/m/Y', 'd-m-Y', 'd/m/Y H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i',
                'j/n/Y', 'j-n-Y', 'j/n/Y H:i:s', 'j-n-Y H:i:s',
                'Y-m-d', 'Y/m/d', 'Y-m-d H:i:s', 'Y/m/d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i',
                'd-M-Y', 'd M Y', 'd-M-Y H:i:s', 'd M Y H:i:s',
                'd-F-Y', 'd F Y', 'd-F-Y H:i:s', 'd F Y H:i:s',
                'M d, Y', 'F d, Y', 'M d Y', 'F d Y',
                'm/d/Y', 'm-d-Y', 'm/d/Y H:i:s', 'm-d-Y H:i:s',
                'd/m/y', 'd-m-y', 'j/n/y', 'j-n-y',
                'd-M-y', 'd M y', 'd-F-y', 'd F y'
            ];

            foreach ($formats as $fmt) {
                $dt = DateTime::createFromFormat('!' . $fmt, $cleanDate);
                $err = DateTime::getLastErrors();
                if ($dt !== false && empty($err['warning_count']) && empty($err['error_count'])) {
                    if ((int)$dt->format('Y') < 100) {
                        $dt->modify('+2000 years');
                    }
                    if ((int)$dt->format('Y') > 1990 && (int)$dt->format('Y') < 2100) {
                        return $includeTime ? $dt->format('Y-m-d H:i:s') : $dt->format('Y-m-d');
                    }
                }
            }

            // 4. Fallback to standard strtotime with / to - swap
            $time = strtotime($cleanDate);
            if ($time === false || $time <= 0) {
                $normalized = str_replace('/', '-', $dateStr);
                $time = strtotime($normalized);
            }

            if ($time !== false && $time > 0 && (int)date('Y', $time) > 1990) {
                return $includeTime ? date('Y-m-d H:i:s', $time) : date('Y-m-d', $time);
            }

            return $default;
        };

        // All rows are valid, start database transaction for insertion
        $this->db->begin_transaction();
        $importedCount = 0;

        try {
            foreach ($rowsData as $row) {
                $projectId = !empty($row['project_id']) ? (int)$row['project_id'] : null;
                $landProjectName = !empty($row['land_project_name']) ? trim($row['land_project_name']) : null;
                $landOwnerName = trim($row['land_owner_name']);
                $landOwnerPhone = !empty($row['land_owner_phone']) ? trim($row['land_owner_phone']) : null;
                $landReceiverName = !empty($row['land_receiver_name']) ? trim($row['land_receiver_name']) : null;
                $mediaBrokerInfo = !empty($row['media_broker_info']) ? trim($row['media_broker_info']) : null;
                $powerOfAttorney = !empty($row['power_of_attorney']) ? trim($row['power_of_attorney']) : null;
                $totalArea = $parseNumber($row['total_area']);
                $areaUnit = !empty($row['area_unit']) ? trim($row['area_unit']) : 'decimal';
                $pricePerUnit = $parseNumber($row['price_per_unit']);
                $totalAmount = $totalArea * $pricePerUnit;
                $location = !empty($row['location']) ? trim($row['location']) : null;
                $remarks = !empty($row['remarks']) ? trim($row['remarks']) : null;
                $landDeptId = (int)$row['land_dept_id'];
                $mgmtId = (int)$row['management_id'];
                $accId = (int)$row['account_id'];

                $bayanaAmount = $parseNumber($row['bayana_payment_amount'] ?? 0);
                
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
                $regCost = $parseNumber($row['registration_cost'] ?? 0);
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
                    (project_id, land_project_name, land_owner_name, land_owner_phone, land_receiver_name, media_broker_info, power_of_attorney, total_area, area_unit, price_per_unit, total_amount, location, status, remarks, created_by, bayana_status, bayana_complete_date, registration_status, registration_complete_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtAcq->bind_param(
                    "issssssdsddssssssss",
                    $projectId, $landProjectName, $landOwnerName, $landOwnerPhone, $landReceiverName, $mediaBrokerInfo, $powerOfAttorney, $totalArea, $areaUnit, $pricePerUnit, $totalAmount, $location, $status, $remarks, $landDeptId, $bayanaStatus, $bayanaDate, $registrationStatus, $regDate
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
                    $reqBId = $stmtReqB->insert_id;

                    // Auto-post Payment Journal to Accounts General Journal
                    if (!class_exists('AutoPostingService')) {
                        require_once __DIR__ . '/../../services/AutoPostingService.php';
                    }
                    $autoPoster = new \AutoPostingService($this->db);
                    $autoPoster->postLandRequisitionJournal($reqBId);

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
                    $regPaidAt = !empty($regTimestamp) ? $regTimestamp : (!empty($regDate) ? ($regDate . ' 00:00:00') : date('Y-m-d H:i:s'));
                    $stmtReqR = $this->db->prepare("
                        INSERT INTO land_fund_requisitions 
                        (land_acquisition_id, requisition_type, requested_amount, purpose, requested_by, mgmt_status, mgmt_by, mgmt_note, mgmt_approved_at, accounts_status, accounts_by, accounts_note, accounts_paid_at, payment_method, transaction_no, attachment_path)
                        VALUES (?, 'Registration', ?, ?, ?, 'Approved', ?, 'Auto-approved during bulk import', ?, 'Paid', ?, 'Auto-paid during bulk import', ?, 'Bank', ?, ?)
                    ");
                    $purposeR = "Historic Registration Cost for " . $landOwnerName;
                    $stmtReqR->bind_param(
                        "idsiisisss",
                        $landAcqId, $regCost, $purposeR, $landDeptId, $mgmtId, $regPaidAt, $accId, $regPaidAt, $regNo, $deedAttachment
                    );
                    if (!$stmtReqR->execute()) {
                        throw new Exception("Failed to insert Registration Requisition: " . $stmtReqR->error);
                    }
                    $reqRId = $stmtReqR->insert_id;

                    // Auto-post Registration Payment Journal to Accounts General Journal
                    if (!class_exists('AutoPostingService')) {
                        require_once __DIR__ . '/../../services/AutoPostingService.php';
                    }
                    $autoPoster = new \AutoPostingService($this->db);
                    $autoPoster->postLandRequisitionJournal($reqRId);

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
