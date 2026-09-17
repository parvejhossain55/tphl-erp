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

    /**
     * Resolve user ID and details from various inputs (email, username, full name, or ID)
     */
    protected function resolveUserByInput($input)
    {
        $clean = strtolower(trim((string)$input));
        if ($clean === '') {
            return null;
        }

        // 1. Direct ID if numeric
        if (is_numeric($clean)) {
            $stmt = $this->db->prepare("SELECT id, team_id, full_name, email FROM users WHERE id = ? LIMIT 1");
            if ($stmt) {
                $uid = (int)$clean;
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($res) return $res;
            }
        }

        // 2. Exact match on email in users
        $stmt = $this->db->prepare("SELECT id, team_id, full_name, email FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $clean);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) return $res;
        }

        // 3. Exact username or full name in users
        $stmt = $this->db->prepare("SELECT id, team_id, full_name, email FROM users WHERE (user_name IS NOT NULL AND LOWER(TRIM(user_name)) = ?) OR (full_name IS NOT NULL AND LOWER(TRIM(full_name)) = ?) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $clean, $clean);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) return $res;
        }

        // 4. Check employees table email (returns user_id)
        $stmt = $this->db->prepare("SELECT user_id AS id, team_id, full_name, email FROM employees WHERE LOWER(TRIM(email)) = ? AND user_id IS NOT NULL AND user_id > 0 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $clean);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) return $res;
        }

        // 5. Prefix match on email in users
        $likeEmail = $clean . '%';
        $stmt = $this->db->prepare("SELECT id, team_id, full_name, email FROM users WHERE LOWER(TRIM(email)) LIKE ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $likeEmail);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) return $res;
        }

        return null;
    }

    /**
     * Flexible case-insensitive column extraction from CSV row data
     */
    protected function getCsvValue($rowArray, ...$keys)
    {
        if (!is_array($rowArray)) return '';

        // 1. Direct check
        foreach ($keys as $k) {
            if (isset($rowArray[$k]) && trim((string)$rowArray[$k]) !== '') {
                return trim((string)$rowArray[$k]);
            }
        }

        // 2. Normalized check (lowercase, replace spaces and special characters with underscores)
        $normalized = [];
        foreach ($rowArray as $k => $v) {
            $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim((string)$k)));
            $cleanKey = trim(preg_replace('/_+/', '_', $cleanKey), '_');
            $normalized[$cleanKey] = $v;
        }

        foreach ($keys as $k) {
            $cleanSearch = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim((string)$k)));
            $cleanSearch = trim(preg_replace('/_+/', '_', $cleanSearch), '_');
            if (isset($normalized[$cleanSearch]) && trim((string)$normalized[$cleanSearch]) !== '') {
                return trim((string)$normalized[$cleanSearch]);
            }
        }

        return '';
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

        $managementUsers = [];
        $res = $this->db->query("SELECT id, full_name FROM users WHERE role_id IN (1, 3, 33)");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $managementUsers[] = $row;
            }
        }

        $salesUsers = [];
        $salesRes = $this->db->query("SELECT u.id, u.full_name, r1.name as role_name, r2.name as sec_role_name 
                                      FROM users u 
                                      LEFT JOIN roles r1 ON u.role_id = r1.id 
                                      LEFT JOIN roles r2 ON u.secondary_role_id = r2.id 
                                      WHERE r1.name LIKE '%sale%' OR r1.name LIKE '%sales%' 
                                         OR r2.name LIKE '%sale%' OR r2.name LIKE '%sales%'
                                      ORDER BY u.full_name ASC");
        if ($salesRes) {
            while ($row = $salesRes->fetch_assoc()) {
                $salesUsers[] = $row;
            }
        }

        $this->view("sales-developments/booking-leads/bookingLeads", [
            "lead" => $lead,
            "projects" => $projects,
            "flats" => $flats,
            "managementUsers" => $managementUsers,
            "salesUsers" => $salesUsers,
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

        $discount = (float)($_POST['discount_amount'] ?? 0);
        
        $isAlreadyApproved = isset($_POST['is_already_approved']) && $_POST['is_already_approved'] == '1';
        $approvalAttachmentPath = null;
        
        $uploadedFile = null;
        if (isset($_FILES['discount_attachment']) && $_FILES['discount_attachment']['error'] === 0) {
            $uploadedFile = $_FILES['discount_attachment'];
        } elseif (isset($_FILES['approval_attachment']) && $_FILES['approval_attachment']['error'] === 0) {
            $uploadedFile = $_FILES['approval_attachment'];
        }

        if ($uploadedFile) {
            $physicalDir = __DIR__ . '/../../public/uploads/approvals/';
            if (!is_dir($physicalDir)) {
                mkdir($physicalDir, 0777, true);
            }
            $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($uploadedFile['name']));
            $fileName = time() . '_' . $cleanName;
            $targetFilePath = $physicalDir . $fileName;
            if (move_uploaded_file($uploadedFile['tmp_name'], $targetFilePath)) {
                $approvalAttachmentPath = 'public/uploads/approvals/' . $fileName;
            }
        }

        //  Get assigned_by from leads table
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $lead = $this->leadModel->find($leadId);
        $salesById = $lead['assigned_id'] ?? ''; // fallback to current user if null

        // Check if sales_agent / sales_agent_email was explicitly submitted
        $submittedAgent = trim($_POST['sales_agent'] ?? $_POST['sales_agent_email'] ?? $_POST['sales_by_id'] ?? '');
        if (!empty($submittedAgent)) {
            $matchedUser = $this->resolveUserByInput($submittedAgent);
            if ($matchedUser) {
                $salesById = $matchedUser['id'];
                $now = date('Y-m-d H:i:s');
                $teamId = $matchedUser['team_id'] ?? null;
                if (!empty($teamId)) {
                    $stmtLead = $this->db->prepare("UPDATE leads SET assigned_id = ?, team_id = COALESCE(?, team_id), assigned_at = COALESCE(assigned_at, ?), last_lead_action = 'booked' WHERE id = ?");
                    if ($stmtLead) {
                        $stmtLead->bind_param("iisi", $salesById, $teamId, $now, $leadId);
                        $stmtLead->execute();
                        $stmtLead->close();
                    }
                } else {
                    $stmtLead = $this->db->prepare("UPDATE leads SET assigned_id = ?, assigned_at = COALESCE(assigned_at, ?), last_lead_action = 'booked' WHERE id = ?");
                    if ($stmtLead) {
                        $stmtLead->bind_param("isi", $salesById, $now, $leadId);
                        $stmtLead->execute();
                        $stmtLead->close();
                    }
                }
            }
        } elseif ($leadId) {
            $stmtLead = $this->db->prepare("UPDATE leads SET last_lead_action = 'booked' WHERE id = ?");
            if ($stmtLead) {
                $stmtLead->bind_param("i", $leadId);
                $stmtLead->execute();
                $stmtLead->close();
            }
        }
        
        $approvalStatus = $discount > 0 ? 'pending' : 'not_required';
        $approvedAmount = null;
        
        if ($discount > 0 && $isAlreadyApproved) {
            $approvalStatus = 'approved';
            $approvedAmount = $discount;
        }

        $data = [
            'lead_id' => $_POST['lead_id'],
            'project_id' => $_POST['project_id'],
            'flat_id' => $flatId,
            'created_by' => $_SESSION['user']['id'],
            'agreement_value' => $_POST['agreement_value'],
            'booking_date' => $_POST['booking_date'],
            'booking_status' => 'booked',
            'discount_amount' => $discount,
            'approval_status' => $approvalStatus,
            'approved_amount' => $approvedAmount,
            'past_approval_attachment' => $approvalAttachmentPath,
            'sales_by_id' => $salesById, //  auto set
        ];

        $bookingId = $this->bookingModel->create($data);

        // Discount approval logic (same as before)
        if ($discount > 0) {
            $approvalData = [
                'department'     => 'Sales',
                'request_type'   => 'discount',
                'title'          => 'Lead Booking Discount Approval',
                'source_table'   => 'lead_bookings',
                'source_id'      => $bookingId,
                'request_amount' => $discount,
                'requested_by'   => $_SESSION['user']['id'],
                'approve_role'   => 'management',
                'attachment'     => $approvalAttachmentPath,
                'meta_data'      => json_encode([
                    'lead_id'    => $_POST['lead_id'],
                    'flat_id'    => $flatId,
                    'attachment' => $approvalAttachmentPath
                ])
            ];
            
            if ($isAlreadyApproved) {
                $approvalData['status'] = 'approved';
                $approvalData['approved_amount'] = $discount;
                $approvalData['approved_by'] = $_POST['approved_by'] ?? null;
                $approvalData['approved_at'] = date('Y-m-d H:i:s');
                $approvalData['attachment'] = $approvalAttachmentPath;
            }
            
            $this->approvalRequestModel->create($approvalData);
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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)($_POST['booking_id'] ?? $_POST['id'] ?? 0);
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $flatId = ($_POST['flat_id'] ?? '') === '' ? null : (int)$_POST['flat_id'];

        if (!$id) {
            setError("Invalid Booking ID!");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        $isManagementOrSalesHead = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 9, 20, 33]);
        if (!$isManagementOrSalesHead && $this->bookingModel->hasVerifiedPayment($id)) {
            setError("Cannot edit booking because payments have already been verified by Accounts!");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        $discount = (float)($_POST['discount_amount'] ?? 0);
        $approvalStatus = $discount > 0 ? 'pending' : 'not_required';
        $approvedAmount = null;

        $bookingData = [
            'lead_id'         => $leadId,
            'project_id'      => (int)($_POST['project_id'] ?? 0),
            'flat_id'         => $flatId,
            'agreement_value' => (float)($_POST['agreement_value'] ?? 0),
            'booking_date'    => $_POST['booking_date'] ?? date('Y-m-d'),
            'discount_amount' => $discount,
            'approval_status' => $approvalStatus,
            'approved_amount' => $approvedAmount,
        ];

        $uploadedFile = null;
        if (isset($_FILES['discount_attachment']) && $_FILES['discount_attachment']['error'] === 0) {
            $uploadedFile = $_FILES['discount_attachment'];
        } elseif (isset($_FILES['approval_attachment']) && $_FILES['approval_attachment']['error'] === 0) {
            $uploadedFile = $_FILES['approval_attachment'];
        }

        if ($uploadedFile) {
            $physicalDir = __DIR__ . '/../../public/uploads/approvals/';
            if (!is_dir($physicalDir)) {
                mkdir($physicalDir, 0777, true);
            }
            $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($uploadedFile['name']));
            $fileName = time() . '_' . $cleanName;
            $targetFilePath = $physicalDir . $fileName;
            if (move_uploaded_file($uploadedFile['tmp_name'], $targetFilePath)) {
                $bookingData['past_approval_attachment'] = 'public/uploads/approvals/' . $fileName;
            }
        }
        
        if (isset($_POST['sales_by_id'])) {
            $bookingData['sales_by_id'] = (int)$_POST['sales_by_id'];
        }

        $res = $this->bookingModel->updateBooking($id, $bookingData);
        if ($res['success']) {
            setSuccess("Booking updated successfully!");
        } else {
            setError($res['message'] ?? "Failed to update booking!");
        }

        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $bookingId = (int)$id;

        if (!$bookingId) {
            setError("Invalid Booking ID!");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        $res = $this->bookingModel->deleteCascade($bookingId);
        if ($res['success']) {
            setSuccess("Booking and related unverified records deleted successfully! Flat is now available.");
            $leadId = $res['lead_id'] ?? 0;
            if ($leadId > 0) {
                header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            } else {
                header("Location: {$GLOBALS['base_url']}/onboard-leads");
            }
            exit;
        } else {
            setError($res['message'] ?? "Failed to delete booking!");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }
    }

    public function quickRefund()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? null;
        $userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 0;

        if (!$userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/onboard-leads");
            exit;
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$bookingId) {
            setError("Invalid Booking ID.");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        if (empty($reason)) {
            setError("Please provide a reason for the refund.");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        // Fetch booking details
        $bStmt = $this->db->prepare("SELECT lb.*, l.full_name as client_name, f.flat_name, p.name as project_name 
                                     FROM lead_bookings lb 
                                     LEFT JOIN leads l ON lb.lead_id = l.id
                                     LEFT JOIN flats f ON lb.flat_id = f.id
                                     LEFT JOIN projects p ON lb.project_id = p.id
                                     WHERE lb.id = ? LIMIT 1");
        $bStmt->bind_param("i", $bookingId);
        $bStmt->execute();
        $booking = $bStmt->get_result()->fetch_assoc();

        if (!$booking) {
            setError("Booking record not found.");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        $leadId = (int)$booking['lead_id'];

        // Authorization check: Salesperson who made the sale (sales_by_id) OR roles [1, 3, 9, 20, 33]
        $isSalesperson = (!empty($booking['sales_by_id']) && (int)$booking['sales_by_id'] === (int)$userId);
        $hasBackupRole = in_array($userRole, [1, 3, 9, 20, 33]);

        if (!$isSalesperson && !$hasBackupRole) {
            setError("You are not authorized to initiate refund for this booking. Only the assigned salesperson or authorized management can initiate.");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        if (in_array($booking['booking_status'], ['refunded', 'cancelled'])) {
            setError("This booking has already been " . $booking['booking_status'] . ".");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        // Calculate total verified paid
        $pStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM lead_payments WHERE booking_id = ? AND verification_status = 'verified'");
        $pStmt->bind_param("i", $bookingId);
        $pStmt->execute();
        $pRow = $pStmt->get_result()->fetch_assoc();
        $totalPaid = (float)($pRow['total_paid'] ?? 0);

        // Execute changes inside a transaction
        $this->db->begin_transaction();
        try {
            // 1. Release the flat: status = 'available', is_booked = 0
            if (!empty($booking['flat_id'])) {
                $flatId = (int)$booking['flat_id'];
                $fStmt = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
                $fStmt->bind_param("i", $flatId);
                $fStmt->execute();
            }

            // 2. Update booking: booking_status = 'refunded', landshare_status = 'cancelled'
            $lbStmt = $this->db->prepare("UPDATE lead_bookings SET booking_status = 'refunded', landshare_status = 'cancelled' WHERE id = ?");
            $lbStmt->bind_param("i", $bookingId);
            $lbStmt->execute();

            // 3. Update lead: last_lead_action = 'cancel'
            if ($leadId > 0) {
                $lStmt = $this->db->prepare("UPDATE leads SET last_lead_action = 'cancel' WHERE id = ?");
                $lStmt->bind_param("i", $leadId);
                $lStmt->execute();
            }

            // 4. Cancel pending EMI schedules
            $emiStmt = $this->db->prepare("UPDATE emi_schedules SET status = 'cancelled' WHERE lead_booking_id = ? AND status IN ('pending', 'missed')");
            $emiStmt->bind_param("i", $bookingId);
            $emiStmt->execute();

            // 5. Create/update client_refund_requests record
            // Check if active refund already exists
            $checkRefund = $this->db->prepare("SELECT id FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' LIMIT 1");
            $checkRefund->bind_param("i", $bookingId);
            $checkRefund->execute();
            $existingRefund = $checkRefund->get_result()->fetch_assoc();

            $refundId = null;
            if ($existingRefund) {
                $refundId = (int)$existingRefund['id'];
                $rfUpdate = $this->db->prepare("UPDATE client_refund_requests SET total_paid = ?, refund_amount = ?, reason = ?, requested_by = ?, status = 'pending' WHERE id = ?");
                $rfUpdate->bind_param("ddsii", $totalPaid, $totalPaid, $reason, $userId, $refundId);
                $rfUpdate->execute();
            } else {
                $rfInsert = $this->db->prepare("INSERT INTO client_refund_requests 
                    (booking_id, lead_id, project_id, flat_id, total_paid, retention_amount, refund_amount, reason, requested_by, status) 
                    VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, 'pending')");
                $flatId = (int)($booking['flat_id'] ?? 0);
                $projId = (int)($booking['project_id'] ?? 0);
                $rfInsert->bind_param("iiiiddsi", $bookingId, $leadId, $projId, $flatId, $totalPaid, $totalPaid, $reason, $userId);
                $rfInsert->execute();
                $refundId = $rfInsert->insert_id;
            }

            // 6. Add/Update approval_requests for management approval (Roles 3, 9, 20, 33)
            if ($refundId) {
                $delAr = $this->db->prepare("DELETE FROM approval_requests WHERE source_table = 'client_refund_requests' AND source_id = ?");
                $delAr->bind_param("i", $refundId);
                $delAr->execute();

                $this->approvalRequestModel->create([
                    'department'     => 'accounts',
                    'request_type'   => 'client_refund',
                    'title'          => 'Client Refund — ' . ($booking['client_name'] ?? '') . ' | ' . ($booking['flat_name'] ?? '') . ' | ' . ($booking['project_name'] ?? ''),
                    'source_table'   => 'client_refund_requests',
                    'source_id'      => $refundId,
                    'requested_days' => 0,
                    'requested_by'   => $userId,
                    'approve_role'   => 'management',
                    'meta_data'      => null,
                    'request_amount' => $totalPaid,
                ]);
            }

            $this->db->commit();
            setSuccess("Refund request submitted successfully! Flat unit has been released to available, and the request is sent for management approval.");
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("quickRefund failed: " . $e->getMessage());
            setError("Failed to process refund: " . $e->getMessage());
        }

        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    }

    public function importCsvBulkBookings()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Move to temp location
        $uploadDir = __DIR__ . '/../../public/uploads/csv_imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempName = 'bookings_import_' . date('YmdHis') . '_' . uniqid() . '.csv';
        $tempPath = $uploadDir . $tempName;

        if (!move_uploaded_file($fileTmp, $tempPath)) {
            setError("Failed to upload file.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if (($handle = fopen($tempPath, "r")) !== false) {
            $header = fgetcsv($handle);
            if (!empty($header[0])) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }
            $header = array_map(function ($value) { return trim($value); }, $header);

            $normalizeText = function($str) {
                if ($str === null) return '';
                $str = str_replace(array("\xC2\xA0", "\xA0"), ' ', $str);
                $str = preg_replace('/\s+/', ' ', $str);
                return trim($str);
            };

            $validCount = 0;
            
            $leadsCache    = [];
            $projectsCache = [];
            $flatsCache    = [];
            $usersCache    = [];

            $skippedRows = []; // structured for view table: [row, lead_phone, project, flat, reason]
            $rowNum      = 1;

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;

                $partialData = (count($header) === count($row))
                    ? array_combine($header, $row)
                    : array_combine(array_slice($header, 0, count($row)), $row);

                $errPhone   = $this->getCsvValue($partialData, 'lead_phone', 'phone', 'phone_number', 'lead_number') ?: '—';
                $errProject = $this->getCsvValue($partialData, 'project_name', 'project') ?: '—';
                $errFlat    = $this->getCsvValue($partialData, 'flat_name', 'flat', 'unit_name', 'unit') ?: '—';

                if (count($header) !== count($row)) {
                    $reason = "Column mismatch (expected " . count($header) . ", got " . count($row) . ")";
                    $skippedRows[] = ['row' => $rowNum, 'lead_phone' => $errPhone, 'project' => $errProject, 'flat' => $errFlat, 'reason' => $reason];
                    continue;
                }

                $data      = $partialData;
                
                // Clean commas and invalid chars from numeric fields to ensure valid float/decimal parsing
                $numericFields = ['agreement_value', 'discount_amount', 'approved_amount', 'service_fee_balance'];
                foreach ($numericFields as $field) {
                    if (isset($data[$field])) {
                        $val = str_replace(',', '', trim($data[$field]));
                        // If it's not numeric after removing commas, clear it to prevent truncation errors
                        if ($val !== '' && !is_numeric($val)) {
                            $val = preg_replace('/[^0-9.-]/', '', $val);
                        }
                        $data[$field] = $val === '' ? null : $val;
                    }
                }

                $rowIssues = [];
                
                // 1. Resolve lead
                $leadPhone = $this->getCsvValue($data, 'lead_phone', 'phone', 'phone_number', 'lead_number');
                if (empty($leadPhone)) {
                    $rowIssues[] = "lead_phone is empty";
                } else {
                    if (!isset($leadsCache[$leadPhone])) {
                        // Extract ONLY digits
                        $cleanPhone = preg_replace('/[^0-9]/', '', $leadPhone);
                        
                        if (empty($cleanPhone)) {
                            $rowIssues[] = "Lead not found for phone '$leadPhone'";
                        } else {
                            $leadFoundId = null;
                            
                            // 1. Try exact match on cleaned database columns
                            $stmt = $this->db->prepare("SELECT id FROM leads WHERE (
                                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '\'', ''), '\"', '') = ? OR 
                                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, '-', ''), ' ', ''), '+', ''), '\'', ''), '\"', '') = ?
                            ) AND deleted_at IS NULL LIMIT 1");
                            $stmt->bind_param("ss", $cleanPhone, $cleanPhone);
                            $stmt->execute();
                            $res = $stmt->get_result()->fetch_assoc();
                            if ($res) {
                                $leadFoundId = $res['id'];
                            } else {
                                // 2. Try last 9 digits wildcard matching
                                $last9 = (strlen($cleanPhone) >= 9) ? substr($cleanPhone, -9) : $cleanPhone;
                                $likePattern = '%';
                                for ($i = 0; $i < strlen($last9); $i++) {
                                    $likePattern .= $last9[$i] . '%';
                                }
                                
                                $stmtLike = $this->db->prepare("SELECT id FROM leads WHERE (phone LIKE ? OR phone_number LIKE ?) AND deleted_at IS NULL LIMIT 1");
                                $stmtLike->bind_param("ss", $likePattern, $likePattern);
                                $stmtLike->execute();
                                $resLike = $stmtLike->get_result()->fetch_assoc();
                                if ($resLike) {
                                    $leadFoundId = $resLike['id'];
                                }
                            }
                            
                            if ($leadFoundId) {
                                $leadsCache[$leadPhone] = ['id' => $leadFoundId];
                            } else {
                                $rowIssues[] = "Lead not found for phone '$leadPhone'";
                            }
                        }
                    }
                }

                // 2. Resolve sales agent
                $salesAgentInput = $this->getCsvValue($data, 'sales_agent', 'sales_agent_email', 'sales_email', 'agent_email', 'sales_person', 'sales_by', 'agent');
                if (!empty($salesAgentInput)) {
                    if (!isset($usersCache[$salesAgentInput])) {
                        $agentUser = $this->resolveUserByInput($salesAgentInput);
                        if ($agentUser) {
                            $usersCache[$salesAgentInput] = $agentUser;
                        } else {
                            $usersCache[$salesAgentInput] = null;
                            $rowIssues[] = "Sales agent not found for email '$salesAgentInput'";
                        }
                    } elseif ($usersCache[$salesAgentInput] === null) {
                        $rowIssues[] = "Sales agent not found for email '$salesAgentInput'";
                    }
                }

                // 3. Resolve project
                $projectName = $normalizeText($this->getCsvValue($data, 'project_name', 'project'));
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
                            // Try case-insensitive and relaxed space matching as a fallback
                            $normalizedSearch = '%' . str_replace(' ', '%', $projectName) . '%';
                            $stmtLike = $this->db->prepare("SELECT id FROM projects WHERE name LIKE ? LIMIT 1");
                            $stmtLike->bind_param("s", $normalizedSearch);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $projectId = $resLike['id'];
                                $projectsCache[$projectName] = $projectId;
                            } else {
                                $rowIssues[] = "Project not found '$projectName'";
                            }
                        }
                    }
                }

                // 4. Resolve flat
                $flatName = $normalizeText($this->getCsvValue($data, 'flat_name', 'flat', 'unit_name', 'unit'));
                if (empty($flatName)) {
                    $rowIssues[] = "flat_name is empty";
                } elseif ($projectId) {
                    $flatCacheKey = $projectId . '_' . $flatName;
                    
                    if (!isset($flatsCache[$flatCacheKey])) {
                        // First try exact match
                        $stmt = $this->db->prepare("SELECT id, is_booked, status FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatsCache[$flatCacheKey] = [
                                'id' => $res['id'],
                                'is_booked' => $res['is_booked'],
                                'status' => $res['status']
                            ];
                        } else {
                            // Try relaxed search by replacing spaces
                            $normalizedFlat = '%' . str_replace(' ', '%', $flatName) . '%';
                            $stmtLike = $this->db->prepare("SELECT id, is_booked, status FROM flats WHERE project_id = ? AND flat_name LIKE ? AND deleted_at IS NULL LIMIT 1");
                            $stmtLike->bind_param("is", $projectId, $normalizedFlat);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $flatsCache[$flatCacheKey] = [
                                    'id' => $resLike['id'],
                                    'is_booked' => $resLike['is_booked'],
                                    'status' => $resLike['status']
                                ];
                            } else {
                                $flatsCache[$flatCacheKey] = null;
                            }
                        }
                    }

                    if (isset($flatsCache[$flatCacheKey]) && $flatsCache[$flatCacheKey] !== null) {
                        $flatData = $flatsCache[$flatCacheKey];
                        if ($flatData['is_booked'] == 1 || $flatData['status'] === 'booked') {
                            $rowIssues[] = "Flat '$flatName' is already booked";
                        } else {
                            // Valid flat found, mark in cache to prevent duplicate booking in same CSV
                            $flatsCache[$flatCacheKey]['is_booked'] = 1;
                            $flatsCache[$flatCacheKey]['status'] = 'booked';
                        }
                    } else {
                        $rowIssues[] = "Flat '$flatName' not found in project '$projectName'";
                    }
                } else {
                    $rowIssues[] = "Cannot lookup flat — project not resolved";
                }

                if (!empty($rowIssues)) {
                    $reason = implode(' | ', $rowIssues);
                    $skippedRows[] = ['row' => $rowNum, 'lead_phone' => $leadPhone ?: $errPhone, 'project' => $projectName ?: $errProject, 'flat' => $flatName ?: $errFlat, 'reason' => $reason];
                    continue;
                }

                $validCount++;
            }
            fclose($handle);

            $_SESSION['bookings_csv_import_file'] = $tempPath;
            $_SESSION['bookings_csv_import_filename'] = $fileName;
            
            // Need to pass to view
            $this->view("sales-developments/leads/bulk-bookings-import-preview", [
                "current_route" => "bulk-bookings-import-csv",
                "page_title"    => "Bookings CSV Import Preview - " . $fileName,
                "skippedRows"   => $skippedRows,
                "valid_count"   => $validCount,
                "filename"      => $fileName
            ]);

        } else {
            setError("Unable to read CSV file");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    public function executeCsvBulkBookings()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        $tempFile = $_SESSION['bookings_csv_import_file'] ?? null;

        if (!$tempFile || !file_exists($tempFile)) {
            setError("Import session expired or file not found. Please re-upload.");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        if (($handle = fopen($tempFile, "r")) !== false) {
            $header = fgetcsv($handle);
            if (!empty($header[0])) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }
            $header = array_map(function ($value) { return trim($value); }, $header);

            $normalizeText = function($str) {
                if ($str === null) return '';
                $str = str_replace(array("\xC2\xA0", "\xA0"), ' ', $str);
                $str = preg_replace('/\s+/', ' ', $str);
                return trim($str);
            };

            $successCount = 0;
            $importErrors = [];
            
            $leadsCache    = [];
            $projectsCache = [];
            $flatsCache    = [];
            $usersCache    = [];

            $rowNum = 1;

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;
                if (count($header) !== count($row)) continue;

                $data = array_combine($header, $row);
                
                // Clean commas and invalid chars from numeric fields to ensure valid float/decimal parsing
                $numericFields = ['agreement_value', 'discount_amount', 'approved_amount', 'service_fee_balance'];
                foreach ($numericFields as $field) {
                    if (isset($data[$field])) {
                        $val = str_replace(',', '', trim($data[$field]));
                        if ($val !== '' && !is_numeric($val)) {
                            $val = preg_replace('/[^0-9.-]/', '', $val);
                        }
                        $data[$field] = $val === '' ? null : $val;
                    }
                }

                $rowIssues = [];
                
                // 1. Resolve lead
                $leadPhone = $this->getCsvValue($data, 'lead_phone', 'phone', 'phone_number', 'lead_number');
                $leadId = null;
                if (empty($leadPhone)) {
                    $rowIssues[] = "lead_phone is empty";
                } else {
                    if (isset($leadsCache[$leadPhone])) {
                        $leadId = $leadsCache[$leadPhone]['id'];
                    } else {
                        // Extract ONLY digits
                        $cleanPhone = preg_replace('/[^0-9]/', '', $leadPhone);
                        
                        if (empty($cleanPhone)) {
                            $rowIssues[] = "Lead not found";
                        } else {
                            $leadFoundId = null;
                            $assignedId = null;
                            
                            // 1. Try exact match on cleaned database columns
                            $stmt = $this->db->prepare("SELECT id, assigned_id FROM leads WHERE (
                                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '\'', ''), '\"', '') = ? OR 
                                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, '-', ''), ' ', ''), '+', ''), '\'', ''), '\"', '') = ?
                            ) AND deleted_at IS NULL LIMIT 1");
                            $stmt->bind_param("ss", $cleanPhone, $cleanPhone);
                            $stmt->execute();
                            $res = $stmt->get_result()->fetch_assoc();
                            if ($res) {
                                $leadFoundId = $res['id'];
                                $assignedId = $res['assigned_id'];
                            } else {
                                // 2. Try last 9 digits wildcard matching
                                $last9 = (strlen($cleanPhone) >= 9) ? substr($cleanPhone, -9) : $cleanPhone;
                                $likePattern = '%';
                                for ($i = 0; $i < strlen($last9); $i++) {
                                    $likePattern .= $last9[$i] . '%';
                                }
                                
                                $stmtLike = $this->db->prepare("SELECT id, assigned_id FROM leads WHERE (phone LIKE ? OR phone_number LIKE ?) AND deleted_at IS NULL LIMIT 1");
                                $stmtLike->bind_param("ss", $likePattern, $likePattern);
                                $stmtLike->execute();
                                $resLike = $stmtLike->get_result()->fetch_assoc();
                                if ($resLike) {
                                    $leadFoundId = $resLike['id'];
                                    $assignedId = $resLike['assigned_id'];
                                }
                            }
                            
                            if ($leadFoundId) {
                                $leadsCache[$leadPhone] = ['id' => $leadFoundId, 'assigned_id' => $assignedId];
                                $leadId = $leadFoundId;
                            } else {
                                $rowIssues[] = "Lead not found";
                            }
                        }
                    }
                }

                // 2. Resolve sales agent
                $salesAgentInput = $this->getCsvValue($data, 'sales_agent', 'sales_agent_email', 'sales_email', 'agent_email', 'sales_person', 'sales_by', 'agent');
                $salesById   = null;
                $salesTeamId = null;

                if (!empty($salesAgentInput)) {
                    if (isset($usersCache[$salesAgentInput])) {
                        if ($usersCache[$salesAgentInput]) {
                            $salesById   = $usersCache[$salesAgentInput]['id'];
                            $salesTeamId = $usersCache[$salesAgentInput]['team_id'] ?? null;
                        } else {
                            $rowIssues[] = "Sales agent not found for email '$salesAgentInput'";
                        }
                    } else {
                        $agentUser = $this->resolveUserByInput($salesAgentInput);
                        if ($agentUser) {
                            $usersCache[$salesAgentInput] = $agentUser;
                            $salesById   = $agentUser['id'];
                            $salesTeamId = $agentUser['team_id'] ?? null;
                        } else {
                            $usersCache[$salesAgentInput] = null;
                            $rowIssues[] = "Sales agent not found for email '$salesAgentInput'";
                        }
                    }
                } else {
                    if ($leadId && isset($leadsCache[$leadPhone])) {
                        $salesById = $leadsCache[$leadPhone]['assigned_id'] ?? null;
                    }
                }

                // 3. Resolve project
                $projectName = $normalizeText($this->getCsvValue($data, 'project_name', 'project'));
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
                            // Try case-insensitive and relaxed space matching as a fallback
                            $normalizedSearch = '%' . str_replace(' ', '%', $projectName) . '%';
                            $stmtLike = $this->db->prepare("SELECT id FROM projects WHERE name LIKE ? LIMIT 1");
                            $stmtLike->bind_param("s", $normalizedSearch);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $projectId = $resLike['id'];
                                $projectsCache[$projectName] = $projectId;
                            } else {
                                $rowIssues[] = "Project not found '$projectName'";
                            }
                        }
                    }
                }

                // 4. Resolve flat
                $flatName = $normalizeText($this->getCsvValue($data, 'flat_name', 'flat', 'unit_name', 'unit'));
                $flatId   = null;
                if (empty($flatName)) {
                    $rowIssues[] = "flat_name is empty";
                } elseif ($projectId) {
                    $flatCacheKey = $projectId . '_' . $flatName;
                    
                    if (!isset($flatsCache[$flatCacheKey])) {
                        // First try exact match
                        $stmt = $this->db->prepare("SELECT id, is_booked, status FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatsCache[$flatCacheKey] = [
                                'id' => $res['id'],
                                'is_booked' => $res['is_booked'],
                                'status' => $res['status']
                            ];
                        } else {
                            // Try relaxed search by replacing spaces
                            $normalizedFlat = '%' . str_replace(' ', '%', $flatName) . '%';
                            $stmtLike = $this->db->prepare("SELECT id, is_booked, status FROM flats WHERE project_id = ? AND flat_name LIKE ? AND deleted_at IS NULL LIMIT 1");
                            $stmtLike->bind_param("is", $projectId, $normalizedFlat);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $flatsCache[$flatCacheKey] = [
                                    'id' => $resLike['id'],
                                    'is_booked' => $resLike['is_booked'],
                                    'status' => $resLike['status']
                                ];
                            } else {
                                $flatsCache[$flatCacheKey] = null;
                            }
                        }
                    }

                    if (isset($flatsCache[$flatCacheKey]) && $flatsCache[$flatCacheKey] !== null) {
                        $flatData = $flatsCache[$flatCacheKey];
                        if ($flatData['is_booked'] == 1 || $flatData['status'] === 'booked') {
                            $rowIssues[] = "Flat '$flatName' is already booked";
                        } else {
                            $flatId = $flatData['id'];
                            // Mark as booked in cache to prevent duplicate processing
                            $flatsCache[$flatCacheKey]['is_booked'] = 1;
                            $flatsCache[$flatCacheKey]['status'] = 'booked';
                        }
                    } else {
                        $rowIssues[] = "Flat '$flatName' not found in project '$projectName'";
                    }
                }

                if (!empty($rowIssues)) {
                    $importErrors[] = "Row $rowNum skipped: " . implode(', ', $rowIssues);
                    continue; // Skip this row in execution, it was already shown as an error in preview
                }

                $data['lead_id']        = $leadId;
                $data['project_id']     = $projectId;
                $data['flat_id']        = $flatId;
                $data['sales_by_id']    = $salesById;
                $data['sales_agent_old'] = null;
                $data['created_by']     = $_SESSION['user']['id'] ?? null;
                $data['uploaded_from']  = 'From Company csv';
                
                if (!empty($data['approved_amount']) && (float)$data['approved_amount'] > 0) {
                    $data['approval_status'] = 'approved';
                }

                if (!empty($data['booking_date'])) {
                    $dateStr = str_replace('/', '-', $data['booking_date']);
                    $timestamp = strtotime($dateStr);
                    if ($timestamp !== false) {
                        $data['booking_date'] = date('Y-m-d', $timestamp);
                    } else {
                        $data['booking_date'] = date('Y-m-d');
                    }
                } else {
                    $data['booking_date'] = date('Y-m-d');
                }

                try {
                    $bookingId = $this->bookingModel->createFromCsvBulk($data);

                    // Update leads table: assigned_id, assigned_at, team_id, last_lead_action = 'booked'
                    if ($leadId) {
                        $now = date('Y-m-d H:i:s');
                        if ($salesById) {
                            if (!empty($salesTeamId)) {
                                $stmtLead = $this->db->prepare("
                                    UPDATE leads 
                                    SET assigned_id = ?, 
                                        team_id = COALESCE(?, team_id), 
                                        assigned_at = COALESCE(assigned_at, ?), 
                                        last_lead_action = 'booked' 
                                    WHERE id = ?
                                ");
                                if ($stmtLead) {
                                    $stmtLead->bind_param("iisi", $salesById, $salesTeamId, $now, $leadId);
                                    $stmtLead->execute();
                                    $stmtLead->close();
                                }
                            } else {
                                $stmtLead = $this->db->prepare("
                                    UPDATE leads 
                                    SET assigned_id = ?, 
                                        assigned_at = COALESCE(assigned_at, ?), 
                                        last_lead_action = 'booked' 
                                    WHERE id = ?
                                ");
                                if ($stmtLead) {
                                    $stmtLead->bind_param("isi", $salesById, $now, $leadId);
                                    $stmtLead->execute();
                                    $stmtLead->close();
                                }
                            }
                        } else {
                            $stmtLead = $this->db->prepare("UPDATE leads SET last_lead_action = 'booked' WHERE id = ?");
                            if ($stmtLead) {
                                $stmtLead->bind_param("i", $leadId);
                                $stmtLead->execute();
                                $stmtLead->close();
                            }
                        }
                    }

                    if ($flatId) {
                        $stmtFlat = $this->db->prepare("UPDATE flats SET is_booked = 1, status = 'booked' WHERE id = ?");
                        if ($stmtFlat) {
                            $stmtFlat->bind_param("i", $flatId);
                            $stmtFlat->execute();
                            $stmtFlat->close();
                        }
                    }

                    $successCount++;
                } catch (Exception $e) {
                    $importErrors[] = "Row $rowNum insert failed: " . $e->getMessage();
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);
            
            // cleanup
            unlink($tempFile);
            unset($_SESSION['bookings_csv_import_file']);
            unset($_SESSION['bookings_csv_import_filename']);

            if (!empty($importErrors)) {
                $errStr = "Import completed with " . count($importErrors) . " error(s). " . $successCount . " successfully imported. First few errors:\n" . implode("\n", array_slice($importErrors, 0, 5));
                setError($errStr);
            } else {
                setSuccess("$successCount Bookings imported successfully.");
            }
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        } else {
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }
    }
}