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

        $vendors = $this->vendorModel->getAll();

        $this->view('phase2/vendors/vendor-lists', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendors & Suppliers',
            'vendors'       => $vendors,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'phone'      => $_POST['phone'] ?? null,
            'email'      => $_POST['email'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'rating'     => 5.00,
            'status'     => 'active',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Vendor name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        if ($this->vendorModel->create($data)) {
            setSuccess('Vendor added successfully!');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        setError('Failed to add vendor.');
        header("Location: {$GLOBALS['base_url']}/con-vendor-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
}