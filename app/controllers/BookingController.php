<?php
class BookingController extends Controller
{
    protected $db;
    protected $userModel;
    protected $leadModel;
    protected $bookingModel;
    protected $projectModel;
    protected $flatModel;
    protected $approvalRequestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);

        $this->bookingModel = new BookingLead($this->db);
        $this->projectModel = new Project($this->db);
        $this->flatModel = new FlatModel($this->db);
        $this->approvalRequestModel = new ApprovalRequest($this->db);
    }

    // Index page: show leads for follow-up
    public function index($lead_id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($lead_id) {

            $lead = $this->leadModel->find($lead_id);

            // Optional: booking records for this lead
            $bookingRecords = $this->bookingModel->getByLead($lead_id);
        } else {
            $lead = null;
            $bookingRecords = [];
        }

        $projects = $this->projectModel->getAll();
        $flats = []; // empty initially

        $this->view("sales-developments/booking-leads/bookingLeads", [
            "lead" => $lead,
            "projects" => $projects,
            "flats" => $flats,
            "bookingRecords" => $bookingRecords,
            "selected_lead_id" => $lead_id,
            "current_route" => "booking-leads",
            "page_title" => "Booking Lead Page",
        ]);
    }

    // AJAX: Get flats by project
    public function getFlatsByProject($projectId)
    {
        if (ob_get_length()) ob_clean(); // clear accidental output

        $flats = $this->flatModel->getFlatsWithUnitByProject($projectId);

        header('Content-Type: application/json');
        echo json_encode($flats);
        exit;
    }
    // Store new follow-up
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $flatId = $_POST['flat_id'] ?? null;
        if (!$flatId) {
            setError("Please select a flat/unit!");
            $leadId = $_POST['lead_id'] ?? 0;
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        $discount = $_POST['discount_amount'] ?? 0;

        //  Get assigned_by from leads table
        $lead = $this->leadModel->find($_POST['lead_id']);
        $salesById = $lead['assigned_id'] ?? ''; // fallback to current user if null

        $data = [
            'lead_id' => $_POST['lead_id'],
            'project_id' => $_POST['project_id'],
            'flat_id' => $flatId,
            'created_by' => $_SESSION['user']['id'],
            'agreement_value' => $_POST['agreement_value'],
            'booking_date' => $_POST['booking_date'],
            'booking_status' => 'booked',
            'discount_amount' => $discount,
            'approval_status' => $discount > 0 ? 'pending' : 'not_required',
            'sales_by_id' => $salesById, //  auto set
        ];

        $bookingId = $this->bookingModel->create($data);

        // Discount approval logic (same as before)
        if ($discount > 0) {
            $this->approvalRequestModel->create([
                'department'     => 'Sales',
                'request_type'   => 'discount',
                'title'          => 'Lead Booking Discount Approval',
                'source_table'   => 'lead_bookings',
                'source_id'      => $bookingId,
                'request_amount' => $discount,
                'requested_by'  => $_SESSION['user']['id'],
                'approve_role'  => 'management',
                'meta_data'     => json_encode([
                    'lead_id' => $_POST['lead_id'],
                    'flat_id' => $flatId
                ])
            ]);
        }

        if ($bookingId) {
            $leadId = $_POST['lead_id'];
            setSuccess("Lead onboarded successfully!");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        } else {
            setError("Failed to onboard lead!");
            $leadId = $_POST['lead_id'];
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }
    }

    public function update()
    {
        $id = (int)($_POST['id'] ?? 0);
        $flatId = ($_POST['flat_id'] ?? '') === '' ? null : (int)$_POST['flat_id'];

        $bookingData = [
            'lead_id' => (int)($_POST['lead_id'] ?? 0),
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'flat_id' => $flatId,
            'agreement_value' => $_POST['agreement_value'] ?? 0,
            'booking_date' => $_POST['booking_date'] ?? date('Y-m-d'),
            'booking_status' => $_POST['booking_status'] ?? 'booked',
            'created_by' => $_SESSION['user']['id'] ?? 1
        ];

        // Update lead_bookings table
        if ($this->bookingModel->update($bookingData, $id)) {

            // Removed automatic update of lead_payments because there can be multiple payments
            // and this would incorrectly overwrite them with the full agreement value.

            setSuccess("Booking updated successfully!");
        } else {
            setError("Failed to update booking!");
        }

        $leadId = $_POST['lead_id'] ?? 0;
        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    }

    // Delete Employee
    public function delete($id) {}

    public function importCsvBulkBookings()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {
            $header = fgetcsv($handle);
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            $header = array_map(function ($value) { return trim($value); }, $header);

            $successCount = 0;
            
            $leadsCache = [];
            $projectsCache = [];
            $flatsCache = [];
            $usersCache = [];

            $errors = [];
            $rowNum = 1;
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;
                if (count($header) !== count($row)) {
                    $errors[] = "Row $rowNum: Column mismatch";
                    continue;
                }

                $data = array_combine($header, $row);
                
                // 1. Resolve lead_id from lead_phone
                $leadPhone = trim($data['lead_phone'] ?? '');
                if (empty($leadPhone)) {
                    $errors[] = "Row $rowNum: Lead phone empty";
                    continue;
                }

                $leadId = null;
                if (isset($leadsCache[$leadPhone])) {
                    $leadId = $leadsCache[$leadPhone];
                } else {
                    $stmt = $this->db->prepare("SELECT id, assigned_id FROM leads WHERE phone = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("s", $leadPhone);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $leadsCache[$leadPhone] = ['id' => $res['id'], 'assigned_id' => $res['assigned_id']];
                        $leadId = $res['id'];
                    }
                }
                if (!$leadId) {
                    $errors[] = "Row $rowNum: Lead not found for phone '$leadPhone'";
                    continue;
                }
                $data['lead_id'] = $leadId;
                
                // Handle Sales Agent
                $salesAgentInput = trim($data['sales_agent'] ?? '');
                $salesById = null;
                $salesAgentOld = null;

                if (!empty($salesAgentInput)) {
                    if (isset($usersCache[$salesAgentInput])) {
                        $salesById = $usersCache[$salesAgentInput];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? OR full_name = ? LIMIT 1");
                        $stmt->bind_param("ss", $salesAgentInput, $salesAgentInput);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        
                        if ($res) {
                            $salesById = $res['id'];
                            $usersCache[$salesAgentInput] = $salesById;
                        } else {
                            $salesAgentOld = $salesAgentInput;
                        }
                    }
                } else {
                    // Fall back to lead's assigned user
                    $salesById = $leadsCache[$leadPhone]['assigned_id'] ?? null;
                }

                $data['sales_by_id'] = $salesById;
                $data['sales_agent_old'] = $salesAgentOld;

                // 2. Resolve project_id from project_name
                $projectName = trim($data['project_name'] ?? '');
                if (empty($projectName)) {
                    $errors[] = "Row $rowNum: Project name empty";
                    continue;
                }

                $projectId = null;
                if (isset($projectsCache[$projectName])) {
                    $projectId = $projectsCache[$projectName];
                } else {
                    $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("s", $projectName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $projectId = $res['id'];
                        $projectsCache[$projectName] = $projectId;
                    }
                }
                if (!$projectId) {
                    $errors[] = "Row $rowNum: Project not found '$projectName'";
                    continue;
                }
                $data['project_id'] = $projectId;

                // 3. Resolve flat_id from flat_name and project_id
                $flatName = trim($data['flat_name'] ?? '');
                if (empty($flatName)) {
                    $errors[] = "Row $rowNum: Flat name empty";
                    continue;
                }

                $flatCacheKey = $projectId . '_' . $flatName;
                $flatId = null;
                if (isset($flatsCache[$flatCacheKey])) {
                    $flatId = $flatsCache[$flatCacheKey];
                } else {
                    $stmt = $this->db->prepare("SELECT id FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("is", $projectId, $flatName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $flatId = $res['id'];
                        $flatsCache[$flatCacheKey] = $flatId;
                    }
                }
                if (!$flatId) {
                    $errors[] = "Row $rowNum: Flat not found '$flatName' in project '$projectName'";
                    continue;
                }
                $data['flat_id'] = $flatId;

                $data['created_by'] = $_SESSION['user']['id'] ?? null;
                
                // Approval status logic
                if (!empty($data['approved_amount']) && (float)$data['approved_amount'] > 0) {
                    $data['approval_status'] = 'approved';
                }

                try {
                    $this->bookingModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Row $rowNum: DB Error - " . $e->getMessage();
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);

            if ($successCount > 0) {
                $msg = "$successCount Bookings imported successfully.";
                if (count($errors) > 0) {
                    $msg .= " Failed rows: " . implode("; ", array_slice($errors, 0, 3));
                    if (count($errors) > 3) $msg .= " and " . (count($errors) - 3) . " more errors.";
                }
                setSuccess($msg);
            } else {
                $msg = "0 Bookings imported.";
                if (count($errors) > 0) {
                    $msg .= " Errors: " . implode("; ", array_slice($errors, 0, 3));
                    if (count($errors) > 3) $msg .= " and " . (count($errors) - 3) . " more errors.";
                }
                setError($msg);
            }

            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            setError("Unable to read CSV file");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}
