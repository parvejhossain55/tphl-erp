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
            $where = "p.landman_id = $userId";
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
}
