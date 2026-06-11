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
            
            $leadsCache    = [];
            $projectsCache = [];
            $flatsCache    = [];
            $usersCache    = [];

            $errors      = [];
            $skippedRows = []; // structured for view table: [row, lead_phone, project, flat, reason]
            $rowNum      = 1;

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;

                // Partial data for nicer error messages even on column-count mismatch
                $partialData = (count($header) === count($row))
                    ? array_combine($header, $row)
                    : array_combine(array_slice($header, 0, count($row)), $row);

                $errPhone   = trim($partialData['lead_phone']    ?? '—');
                $errProject = trim($partialData['project_name']  ?? '—');
                $errFlat    = trim($partialData['flat_name']     ?? '—');

                if (count($header) !== count($row)) {
                    $reason = "Column mismatch (expected " . count($header) . ", got " . count($row) . ")";
                    $errors[]      = "Row $rowNum: $reason";
                    $skippedRows[] = ['row' => $rowNum, 'lead_phone' => $errPhone, 'project' => $errProject, 'flat' => $errFlat, 'reason' => $reason];
                    continue;
                }

                $data      = $partialData;
                $rowIssues = []; // collect all issues for this row before skipping
                
                // ═══════════════════════════════════════════════════════
                // 1. Resolve lead_id from lead_phone
                // ═══════════════════════════════════════════════════════
                $leadPhone = trim($data['lead_phone'] ?? '');
                $leadId    = null;
                if (empty($leadPhone)) {
                    $rowIssues[] = "lead_phone is empty";
                } else {
                    if (isset($leadsCache[$leadPhone])) {
                        $leadId = $leadsCache[$leadPhone]['id'];
                    } else {
                        $stmt = $this->db->prepare("SELECT id, assigned_id FROM leads WHERE (phone = ? OR phone_number = ?) AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("ss", $leadPhone, $leadPhone);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $leadsCache[$leadPhone] = ['id' => $res['id'], 'assigned_id' => $res['assigned_id']];
                            $leadId = $res['id'];
                        } else {
                            $rowIssues[] = "Lead not found for phone '$leadPhone'";
                        }
                    }
                }

                // ═══════════════════════════════════════════════════════
                // 2. Resolve sales agent from email (sales_agent column)
                // ═══════════════════════════════════════════════════════
                $salesAgentEmail = trim($data['sales_agent'] ?? '');
                $salesById       = null;
                $salesAgentOld   = null;

                if (!empty($salesAgentEmail)) {
                    if (isset($usersCache[$salesAgentEmail])) {
                        $salesById = $usersCache[$salesAgentEmail];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                        $stmt->bind_param("s", $salesAgentEmail);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $salesById = $res['id'];
                            $usersCache[$salesAgentEmail] = $salesById;
                        } else {
                            $rowIssues[] = "Sales agent not found for email '$salesAgentEmail'";
                        }
                    }
                } else {
                    // Fall back to lead's assigned user
                    if ($leadId && isset($leadsCache[$leadPhone])) {
                        $salesById = $leadsCache[$leadPhone]['assigned_id'] ?? null;
                    }
                }

                // ═══════════════════════════════════════════════════════
                // 3. Resolve project_id from project_name
                // ═══════════════════════════════════════════════════════
                $projectName = trim($data['project_name'] ?? '');
                $projectId   = null;
                if (empty($projectName)) {
                    $rowIssues[] = "project_name is empty";
                } else {
                    if (isset($projectsCache[$projectName])) {
                        $projectId = $projectsCache[$projectName];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? LIMIT 1");
                        $stmt->bind_param("s", $projectName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $projectId = $res['id'];
                            $projectsCache[$projectName] = $projectId;
                        } else {
                            $rowIssues[] = "Project not found '$projectName'";
                        }
                    }
                }

                // ═══════════════════════════════════════════════════════
                // 4. Resolve flat_id from flat_name + project_id
                // ═══════════════════════════════════════════════════════
                $flatName = trim($data['flat_name'] ?? '');
                $flatId   = null;
                if (empty($flatName)) {
                    $rowIssues[] = "flat_name is empty";
                } elseif ($projectId) {
                    $flatCacheKey = $projectId . '_' . $flatName;
                    if (isset($flatsCache[$flatCacheKey])) {
                        $flatId = $flatsCache[$flatCacheKey];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatId = $res['id'];
                            $flatsCache[$flatCacheKey] = $flatId;
                        } else {
                            $rowIssues[] = "Flat '$flatName' not found in project '$projectName'";
                        }
                    }
                } else {
                    $rowIssues[] = "Cannot lookup flat — project not resolved";
                }

                // ═══════════════════════════════════════════════════════
                // If ANY required field is missing, skip with details
                // ═══════════════════════════════════════════════════════
                if (!empty($rowIssues)) {
                    $reason = implode(' | ', $rowIssues);
                    $errors[]      = "Row $rowNum: $reason";
                    $skippedRows[] = ['row' => $rowNum, 'lead_phone' => $leadPhone ?: $errPhone, 'project' => $projectName ?: $errProject, 'flat' => $flatName ?: $errFlat, 'reason' => $reason];
                    continue;
                }

                // All resolved — prepare data
                $data['lead_id']        = $leadId;
                $data['project_id']     = $projectId;
                $data['flat_id']        = $flatId;
                $data['sales_by_id']    = $salesById;
                $data['sales_agent_old'] = $salesAgentOld;
                $data['created_by']     = $_SESSION['user']['id'] ?? null;
                $data['uploaded_from']  = 'From Company csv';
                
                // Approval status logic
                if (!empty($data['approved_amount']) && (float)$data['approved_amount'] > 0) {
                    $data['approval_status'] = 'approved';
                }

                // Date formatting for booking_date
                if (!empty($data['booking_date'])) {
                    $dateStr = str_replace('/', '-', $data['booking_date']);
                    $timestamp = strtotime($dateStr);
                    if ($timestamp !== false) {
                        $data['booking_date'] = date('Y-m-d', $timestamp);
                    } else {
                        $data['booking_date'] = null;
                    }
                }

                try {
                    $bookingId = $this->bookingModel->createFromCsvBulk($data);

                    // ── After successful insert: update leads.assigned_id ──
                    if ($salesById && $leadId) {
                        $stmtLead = $this->db->prepare("UPDATE leads SET assigned_id = ? WHERE id = ?");
                        $stmtLead->bind_param("ii", $salesById, $leadId);
                        $stmtLead->execute();
                    }

                    // ── After successful insert: set flat is_booked = 1 and status = 'booked' ──
                    if ($flatId) {
                        $stmtFlat = $this->db->prepare("UPDATE flats SET is_booked = 1, status = 'booked' WHERE id = ?");
                        $stmtFlat->bind_param("i", $flatId);
                        $stmtFlat->execute();
                    }

                    $successCount++;
                } catch (Exception $e) {
                    $reason = "DB Error - " . $e->getMessage();
                    $errors[]      = "Row $rowNum: $reason";
                    $skippedRows[] = ['row' => $rowNum, 'lead_phone' => $leadPhone, 'project' => $projectName, 'flat' => $flatName, 'reason' => $reason];
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);

            // Store structured skipped rows in session for the view to render
            $_SESSION['booking_csv_import_skipped'] = $skippedRows;

            // ═══════════════════════════════════════════════════════
            // Build detailed result message
            // ═══════════════════════════════════════════════════════
            $totalRows   = $rowNum - 1; // exclude header
            $failedCount = count($skippedRows);

            if ($successCount > 0) {
                $msg = " $successCount / $totalRows Bookings imported successfully.";
                if ($failedCount > 0) {
                    $msg .= " ⚠️ $failedCount row(s) skipped — see details below.";
                }
                setSuccess($msg);
            } else {
                $msg = "❌ 0 / $totalRows Bookings imported.";
                if ($failedCount > 0) {
                    $msg .= " $failedCount error(s) — see details below.";
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