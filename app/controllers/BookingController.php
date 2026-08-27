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

        $managementUsers = [];
        $res = $this->db->query("SELECT id, full_name FROM users WHERE role_id IN (1, 3, 33)");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $managementUsers[] = $row;
            }
        }

        $this->view("sales-developments/booking-leads/bookingLeads", [
            "lead" => $lead,
            "projects" => $projects,
            "flats" => $flats,
            "managementUsers" => $managementUsers,
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
        
        $isAlreadyApproved = isset($_POST['is_already_approved']) && $_POST['is_already_approved'] == '1';
        $approvalAttachmentPath = null;
        
        if ($isAlreadyApproved && isset($_FILES['approval_attachment']) && $_FILES['approval_attachment']['error'] === 0) {
            $uploadDir = 'public/uploads/approvals/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['approval_attachment']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['approval_attachment']['tmp_name'], $targetFilePath)) {
                $approvalAttachmentPath = $targetFilePath;
            }
        }

        //  Get assigned_by from leads table
        $lead = $this->leadModel->find($_POST['lead_id']);
        $salesById = $lead['assigned_id'] ?? ''; // fallback to current user if null
        
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
                'requested_by'  => $_SESSION['user']['id'],
                'approve_role'  => 'management',
                'meta_data'     => json_encode([
                    'lead_id' => $_POST['lead_id'],
                    'flat_id' => $flatId
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
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
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

                $errPhone   = trim($partialData['lead_phone']    ?? '—');
                $errProject = trim($partialData['project_name']  ?? '—');
                $errFlat    = trim($partialData['flat_name']     ?? '—');

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
                $leadPhone = trim($data['lead_phone'] ?? '');
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
                $salesAgentInput = trim($data['sales_agent'] ?? '');
                if (!empty($salesAgentInput)) {
                    if (!isset($usersCache[$salesAgentInput])) {
                        $cleanInput = strtolower($salesAgentInput);
                        $likeEmail  = $cleanInput . '%';
                        $stmt = $this->db->prepare("
                            SELECT id FROM users 
                            WHERE LOWER(TRIM(email)) = ? 
                               OR LOWER(TRIM(email)) LIKE ? 
                               OR (user_name IS NOT NULL AND LOWER(TRIM(user_name)) = ?)
                               OR (full_name IS NOT NULL AND LOWER(TRIM(full_name)) = ?)
                            LIMIT 1
                        ");
                        if ($stmt) {
                            $stmt->bind_param("ssss", $cleanInput, $likeEmail, $cleanInput, $cleanInput);
                            $stmt->execute();
                            $res = $stmt->get_result()->fetch_assoc();
                            $stmt->close();

                            if ($res) {
                                $usersCache[$salesAgentInput] = $res['id'];
                            } else {
                                $rowIssues[] = "Sales agent not found for email '$salesAgentInput'";
                            }
                        } else {
                            error_log("Failed to prepare sales agent query in BookingController: " . $this->db->error);
                            $rowIssues[] = "Sales agent lookup error for '$salesAgentInput'";
                        }
                    }
                }

                // 3. Resolve project
                $projectName = $normalizeText($data['project_name'] ?? '');
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
                $flatName = $normalizeText($data['flat_name'] ?? '');
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
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
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
                $leadPhone = trim($data['lead_phone'] ?? '');
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
                $salesAgentInput = trim($data['sales_agent'] ?? '');
                $salesById       = null;
                if (!empty($salesAgentInput)) {
                    if (isset($usersCache[$salesAgentInput])) {
                        $salesById = $usersCache[$salesAgentInput];
                    } else {
                        $cleanInput = strtolower($salesAgentInput);
                        $likeEmail  = $cleanInput . '%';
                        $stmt = $this->db->prepare("
                            SELECT id FROM users 
                            WHERE LOWER(TRIM(email)) = ? 
                               OR LOWER(TRIM(email)) LIKE ? 
                               OR (user_name IS NOT NULL AND LOWER(TRIM(user_name)) = ?)
                               OR (full_name IS NOT NULL AND LOWER(TRIM(full_name)) = ?)
                            LIMIT 1
                        ");
                        if ($stmt) {
                            $stmt->bind_param("ssss", $cleanInput, $likeEmail, $cleanInput, $cleanInput);
                            $stmt->execute();
                            $res = $stmt->get_result()->fetch_assoc();
                            $stmt->close();

                            if ($res) {
                                $salesById = $res['id'];
                                $usersCache[$salesAgentInput] = $salesById;
                            } else {
                                $rowIssues[] = "Sales agent not found";
                            }
                        } else {
                            error_log("Failed to prepare sales agent query in BookingController: " . $this->db->error);
                            $rowIssues[] = "Sales agent lookup error";
                        }
                    }
                } else {
                    if ($leadId && isset($leadsCache[$leadPhone])) {
                        $salesById = $leadsCache[$leadPhone]['assigned_id'] ?? null;
                    }
                }

                // 3. Resolve project
                $projectName = $normalizeText($data['project_name'] ?? '');
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
                $flatName = $normalizeText($data['flat_name'] ?? '');
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

                    if ($salesById && $leadId) {
                        $stmtLead = $this->db->prepare("UPDATE leads SET assigned_id = ? WHERE id = ?");
                        $stmtLead->bind_param("ii", $salesById, $leadId);
                        $stmtLead->execute();
                    }

                    if ($flatId) {
                        $stmtFlat = $this->db->prepare("UPDATE flats SET is_booked = 1, status = 'booked' WHERE id = ?");
                        $stmtFlat->bind_param("i", $flatId);
                        $stmtFlat->execute();
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