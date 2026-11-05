<?php
namespace Phase2;

use Con_Contractor;
use Controller;

class Con_ContractorController extends Controller
{
    protected $db;
    protected $contractorModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->contractorModel = new Con_Contractor($this->db);
    }

    /* =====================
       List all operators
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPageParam = $_GET['per_page'] ?? '50';
        $isAll = ($perPageParam === 'all');
        $perPage = $isAll ? 0 : (intval($perPageParam) > 0 ? intval($perPageParam) : 50);

        $search = trim($_GET['search'] ?? '');
        $totalRecords = $this->contractorModel->countAll($search);

        if ($isAll || $perPage <= 0) {
            $totalPages = 1;
            $offset = 0;
            $operators = $this->contractorModel->getPaginated(0, 0, $search);
        } else {
            $totalPages = max(1, (int)ceil($totalRecords / $perPage));
            if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $perPage;
            $operators = $this->contractorModel->getPaginated($perPage, $offset, $search);
        }

        $this->view('phase2/contractors/contractor-lists', [
            'current_route' => 'con-contractors',
            'page_title'    => 'Contractors',
            'operators'     => $operators,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'totalRecords'  => $totalRecords,
            'perPage'       => $perPageParam,
            'offset'        => $offset,
            'search'        => $search
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/contractors/create-contractor', [
            'current_route' => 'con-contractor-create',
            'page_title'    => 'Add Operator / Contractor',
        ]);
    }

    /* =====================
       Store
    ====================== */
    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'role'       => $_POST['role'] ?? 'Contractor',
            'trade'      => $_POST['trade'] ?? null,
            'phone'      => $_POST['phone'] ?? null,
            'nid_number' => $_POST['nid_number'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'daily_rate' => $_POST['daily_rate'] ?? null,
            'status'     => 'pending_approval',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Operator name is required.');
            header("Location: {$GLOBALS['base_url']}/con-contractors-create");
            exit;
        }

        $contractorId = $this->contractorModel->create($data);
        if ($contractorId) {
            require_once dirname(dirname(__DIR__)) . '/models/EnrollmentApproval.php';
            $enrollmentApproval = new \EnrollmentApproval($this->db);
            $initialStep = $enrollmentApproval->initiateApproval('contractor', $contractorId, $data['created_by']);
            $stepLabel = $enrollmentApproval->getStepLabel($initialStep);
            
            setSuccess("Contractor enrollment request submitted successfully! It is now pending approval from: {$stepLabel}.");
            header("Location: {$GLOBALS['base_url']}/contractor-enrollment-requests");
            exit;
        }

        setError('Failed to create contractors.');
        header("Location: {$GLOBALS['base_url']}/con-contractors-create");
        exit;
    }

    /* =====================
       Edit Form
    ====================== */
    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $contractors = $this->contractorModel->find($id);
        if (!$contractors) {
            setError('contractors not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractors");
            exit;
        }

        $this->view('phase2/contractors/edit-contractor', [
            'current_route' => 'con-contractors',
            'page_title'    => 'Edit contractors',
            'contractors'   => $contractors,
            'operator'      => $contractors,
        ]);
    }

    /* =====================
       Update
    ====================== */
    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'role'       => $_POST['role'] ?? 'Contractor',
            'trade'      => $_POST['trade'] ?? null,
            'phone'      => $_POST['phone'] ?? null,
            'nid_number' => $_POST['nid_number'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'daily_rate' => $_POST['daily_rate'] ?? null,
            'status'     => $_POST['status'] ?? 'active',
        ];

        if ($this->contractorModel->update($id, $data)) {
            setSuccess('contractors updated successfully!');
            header("Location: {$GLOBALS['base_url']}/con-contractors");
            exit;
        }

        setError('Failed to update contractors.');
        header("Location: {$GLOBALS['base_url']}/con-contractors-edit/$id");
        exit;
    }

    /* =====================
       Delete
    ====================== */
    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = $_POST['id'] ?? 0;
        if ($this->contractorModel->delete($id)) {
            setSuccess('contractors deleted.');
        } else {
            setError('Delete failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractors");
        exit;
    }
    /* =====================
       CSV Import
    ====================== */
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-contractors');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-contractors');
            exit;
        }
        
        // Read entire file as string to properly handle UTF-8 Bengali text
        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $content = ltrim($content, "\xEF\xBB\xBF");
        $content = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        if (empty($lines)) { setError('CSV file is empty.'); header('Location: ' . $GLOBALS['base_url'] . '/con-contractors'); exit; }
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
            
            $name       = trim($resolveKey($row, 'Name'));
            $role       = trim($resolveKey($row, 'Role'));
            $trade      = trim($resolveKey($row, 'Trade'));
            $phone      = trim($resolveKey($row, 'Phone'));
            $nid_number = trim($resolveKey($row, 'NID_Number'));
            $address    = trim($resolveKey($row, 'Address'));
            $daily_rate = trim($resolveKey($row, 'Daily_Rate'));
            
            if (empty($name)) { 
                $errorCount++; $errors[] = "Missing Name"; 
                continue; 
            }
            if (empty($role)) {
                $role = 'Contractor';
            }
            
            $stmt = $this->db->prepare("SELECT id FROM contractors WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { 
                $errors[] = "'$name' already exists"; 
                $errorCount++; 
                continue; 
            }
            
            $insertData = [
                'name'       => $name,
                'role'       => $role,
                'trade'      => !empty($trade) ? $trade : null,
                'phone'      => !empty($phone) ? $phone : null,
                'nid_number' => !empty($nid_number) ? $nid_number : null,
                'address'    => !empty($address) ? $address : null,
                'daily_rate' => is_numeric($daily_rate) ? $daily_rate : null,
                'status'     => 'active',
                'created_by' => $userId
            ];

            if ($this->contractorModel->create($insertData)) { 
                $successCount++; 
            } else { 
                $errorCount++; $errors[] = "Failed to save '$name'"; 
            }
        }
        
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        
        header('Location: ' . $GLOBALS['base_url'] . '/con-contractors');
        exit;
    }
}
