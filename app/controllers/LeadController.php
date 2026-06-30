<?php
class LeadController extends Controller
{
    protected $db;

    protected $userModel;
    protected $leadModel;
    protected $leadSource;
    protected $leadBookedModel;
    protected $locationModel;
    protected $leadSourceModel;
    protected $projectModel;
    protected $followUpModel;
    protected $teamsModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);
        $this->leadSource = new LeadSource($this->db);
        $this->leadBookedModel = new BookingLead($this->db);
        $this->locationModel = new Location($this->db);
        $this->leadSourceModel = new LeadSource($this->db);
        $this->projectModel = new Project($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->teamsModel = new Team($this->db);
    }
    public function index()
    {

        // $assignedUsers = $this->userModel->getAllRoleUser();
        $userRoleId = $_SESSION['user']['role_id'] ?? null;
        $userTeamId = $_SESSION['user']['team_id'] ?? null;
        $isCoLeader = isSalesCoLeader();

        if (in_array($userRoleId, [5, 9]) || $isCoLeader) {

            if (!empty($userTeamId)) {
                // team wise users
                $assignedUsers = $this->userModel->getUsersByTeamAndRole($userTeamId);
            } else {
                // no team → global sales users
                $assignedUsers = $this->userModel->getAllRoleUser();
            }
        } else {
            // admin / others
            $assignedUsers = $this->userModel->getAllRoleUser();
        }
        $date = $_GET['date'] ?? null;
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $lastAction = $_GET['last_action'] ?? null;
        $assigned   = $_GET['assigned']    ?? null; // new filter for assigned leads
        $assigned_user = $_GET['assigned_user'] ?? null; // new filter for assigned user
        $owners = $this->userModel->getAll();

        $bookingCount = $this->leadBookedModel->getBookingCountByLead();
        $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $totalTodayFollowUps = array_sum($todayFollowUp);
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $paymentCount = (new PaymentLeadBooking($this->db))->getpaymentCount();



        $limit = 50;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $offset = ($currentPage - 1) * $limit;

        // Get total count for pagination
        // Co-leader gets team-scoped data same as role_id=5 (Sales Manager)
        $effectiveRoleId = ($isCoLeader && !in_array($userRoleId, [1, 3, 5, 9])) ? 5 : $userRoleId;
        $leadCounts = $this->leadModel->countAllWithFilter($year, $month, $lastAction, $effectiveRoleId, $userTeamId, $assigned, $assigned_user, $date);

        // Fetch paginated records
        $leads = $this->leadModel->getAllWithFilter($year, $month, $lastAction, $effectiveRoleId, $userTeamId, $assigned, $assigned_user, $date, $limit, $offset);

        $totalPages = ceil($leadCounts / $limit);

        // Build pagination route query string (excluding 'page')
        $queryArr = $_GET;
        unset($queryArr['page'], $queryArr['url']); // 'url' is sometimes set by routing
        $queryString = http_build_query($queryArr);
        $paginationRoute = $GLOBALS['base_url'] . '/lead-lists' . ($queryString ? '?' . $queryString : '');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = "";
        $prevPeriodLeads = $this->leadModel->getAllByDate(
            $year ? ($month ? "$year-$month-01" : "$year-01-01") : null,
            $year ? ($month ? date("Y-m-t", strtotime("$year-$month-01")) : "$year-12-31") : null
        );
        $prevCount = count($prevPeriodLeads);

        if ($prevCount == 0) {
            $leadsPercentChange = $leadCounts > 0 ? 100 : 0;
        } else {
            $leadsPercentChange = round((($leadCounts - $prevCount) / $prevCount) * 100, 2);
        }


        $totalBookingCount = $this->leadBookedModel->getTotalBookings();

        $todayFollowUpCount = $this->followUpModel->getTodayFollowUpCountByLead();
        $totalTodayFollowUps = array_sum($todayFollowUpCount);
        $todayPendingFollowUpCount = $this->followUpModel->getTodayFollowUpPendingCount();
        $totalTodayPendingFollowUps = array_sum($todayPendingFollowUpCount);

        //  Conversion Rate
        $conversionRate = $leadCounts > 0 ? ($totalBookingCount / $leadCounts) * 100 : 0;
        $totalHotLeads = $this->leadModel->getTotalHotLeads();
        $this->view("sales-developments/leads/lead-lists", [
            "current_route" => "lead-lists",
            "page_title" => "Lead List",
            "owners" => $owners,
            "leads" => $leads,
            "message" => $message,
            "followUpCounts" => $followUpCounts,
            "bookingCount" => $bookingCount,
            "paymentCount" => $paymentCount,
            "leadCounts" => $leadCounts,
            "totalTodayFollowUps" => $totalTodayFollowUps,
            "assignedUsers" => $assignedUsers,
            "leadsPercentChange" => $leadsPercentChange,
            "totalBookingCount" => $totalBookingCount,
            "totalTodayPendingFollowUps" => $totalTodayPendingFollowUps,
            "conversionRate" => $conversionRate,
            "totalHotLeads" => $totalHotLeads,
            "selectedAction" => $lastAction,
            "selectedAssigned" => $assigned,
            "selectedAssignedUser" => $assigned_user,
            "currentPage" => $currentPage,
            "totalPages" => $totalPages,
            "paginationRoute" => $paginationRoute,
        ]);
    }


    // ====== TEMPORARY DEBUG - DELETE AFTER FIX ======
    public function debugLeadsAjax()
    {
        $start_time = microtime(true);
        set_time_limit(120);
        ini_set('memory_limit', '256M');
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        header('Content-Type: text/html; charset=utf-8');

        echo "<h2>Lead AJAX Debug</h2>";
        echo "<p>PHP: " . PHP_VERSION . " | max_exec: " . ini_get('max_execution_time') . "s | memory: " . ini_get('memory_limit') . "</p>";

        // 1. Count
        $t1 = microtime(true);
        $countQuery = $this->db->query("SELECT COUNT(*) AS total FROM leads WHERE deleted_at IS NULL");
        $total = $countQuery ? $countQuery->fetch_assoc()['total'] : 'ERROR: ' . $this->db->error;
        echo "<p><b>Total Leads:</b> $total | Time: " . round((microtime(true) - $t1) * 1000) . "ms</p>";

        // 2. Simple paginated query
        $t2 = microtime(true);
        $r2 = $this->db->query("SELECT l.id FROM leads l WHERE l.deleted_at IS NULL ORDER BY l.created_at DESC LIMIT 10");
        $rows2 = $r2 ? $r2->num_rows : 0;
        echo "<p><b>Simple LIMIT 10 query:</b> $rows2 rows | Time: " . round((microtime(true) - $t2) * 1000) . "ms</p>";

        // 3. Follow-up count ALL (old method)
        $t3 = microtime(true);
        $r3 = $this->db->query("SELECT lead_id, COUNT(*) AS c FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id");
        $rows3 = $r3 ? $r3->num_rows : 'ERROR: ' . $this->db->error;
        echo "<p><b>getAllFollowUpCounts (OLD - slow):</b> $rows3 rows | Time: " . round((microtime(true) - $t3) * 1000) . "ms</p>";

        // 4. Booking count ALL (old method)
        $t4 = microtime(true);
        $r4 = $this->db->query("SELECT lb.lead_id, COUNT(DISTINCT lb.id) AS c FROM lead_bookings lb INNER JOIN lead_payments lp ON lp.booking_id = lb.id GROUP BY lb.lead_id");
        $rows4 = $r4 ? $r4->num_rows : 'ERROR: ' . $this->db->error;
        echo "<p><b>getAllBookingCounts (OLD - slow):</b> $rows4 rows | Time: " . round((microtime(true) - $t4) * 1000) . "ms</p>";

        // 5. Test WITH new method (only 10 IDs)
        $t5 = microtime(true);
        $ids = [];
        $r5tmp = $this->db->query("SELECT id FROM leads WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 10");
        while ($row = $r5tmp->fetch_assoc()) $ids[] = $row['id'];
        $idStr = implode(',', $ids);
        $r5 = $this->db->query("SELECT lead_id, COUNT(*) AS c FROM lead_follow_ups WHERE deleted_at IS NULL AND lead_id IN ($idStr) GROUP BY lead_id");
        $rows5 = $r5 ? $r5->num_rows : 'ERROR';
        echo "<p><b>getFollowUpCounts for 10 IDs (NEW - fast):</b> $rows5 rows | Time: " . round((microtime(true) - $t5) * 1000) . "ms</p>";

        echo "<hr><p><b>Total time:</b> " . round((microtime(true) - $start_time) * 1000) . "ms</p>";
        echo "<p style='color:red'><b>⚠️ DELETE leads-ajax-debug route after testing!</b></p>";
        exit;
    }
    // ====== END TEMPORARY DEBUG ======

    public function getLeadsAjax()
    {
        // Server e 3000+ leads এর complex JOIN query র জন্য timeout বাড়ানো হলো
        set_time_limit(120);
        ini_set('memory_limit', '256M');
        header('Content-Type: application/json');

        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
        $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
        $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : null;

        // Get filters from GET
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $lastAction = $_GET['last_action'] ?? null;
        $assigned   = $_GET['assigned']    ?? null;
        $assigned_user = $_GET['assigned_user'] ?? null;
        $date = $_GET['date'] ?? null;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleId = $_SESSION['user']['role_id'] ?? null;
        $userTeamId = $_SESSION['user']['team_id'] ?? null;
        // Co-leader gets same team-scoped access as Sales Manager (role_id=5)
        $effectiveRoleId = (isSalesCoLeader() && !in_array($userRoleId, [1, 3, 5, 9])) ? 5 : $userRoleId;

        $totalRecords = $this->leadModel->countAllWithFilter($year, $month, $lastAction, $effectiveRoleId, $userTeamId, $assigned, $assigned_user, $date);

        if (!empty($search)) {
            $filteredRecords = $this->leadModel->countAllWithFilter($year, $month, $lastAction, $effectiveRoleId, $userTeamId, $assigned, $assigned_user, $date, $search);
        } else {
            $filteredRecords = $totalRecords;
        }

        $leads = $this->leadModel->getAllWithFilter($year, $month, $lastAction, $effectiveRoleId, $userTeamId, $assigned, $assigned_user, $date, $length, $start, $search);

        // FIXED: শুধু current page এর lead IDs দিয়ে count করা হচ্ছে
        $leadIds = array_column($leads, 'id');
        $followUpCounts = !empty($leadIds) ? (new FollowUp($this->db))->getFollowUpCountByLeadIds($leadIds) : [];
        $bookingCounts  = !empty($leadIds) ? $this->leadBookedModel->getBookingCountByLeadIds($leadIds) : [];
        $bookingCount = $bookingCounts;

        // ── MOBILE FORMAT: return raw data for JS card rendering ─────────
        if (!empty($_GET['mobile'])) {
            $mobileData = [];
            foreach ($leads as $lead) {
                $mobileData[] = [
                    'id'               => $lead['id'],
                    'full_name'        => $lead['full_name'] ?? '',
                    'phone'            => $lead['phone_number'] ?? $lead['phone'] ?? '',
                    'address'          => !empty($lead['address']) ? $lead['address'] : ($lead['city'] ?? ''),
                    'campaign_name'    => $lead['campaign_name'] ?? $lead['project_name'] ?? '-',
                    'last_lead_action' => $lead['last_lead_action'] ?? '',
                    'next_follow_up'   => $lead['next_follow_up_date'] ?? '',
                    'agent_name'       => $lead['agent_name'] ?? '',
                    'follow_up_count'  => $followUpCounts[$lead['id']] ?? 0,
                    'booking_count'    => $bookingCounts[$lead['id']] ?? 0,
                    'created_at'       => $lead['created_at'] ?? '',
                ];
            }
            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $mobileData,
            ]);
            exit;
        }
        // ── END MOBILE FORMAT ─────────────────────────────────────────────

        $data = [];
        foreach ($leads as $key => $lead) {
            $row = [];

            $row[] = '
                <input type="checkbox" class="me-2 selectedId" data-id="' . $lead['id'] . '">
                ' . ($start + $key + 1) . '
            ';

            $row[] = $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '';

            $row[] = '<strong>' . htmlspecialchars($lead['full_name'] ?? '') . '</strong>';

            // Address column (fallback to city)
            $addr = !empty($lead['address']) ? $lead['address'] : ($lead['city'] ?? '—');
            $row[] = '<span class="fs-12">' . htmlspecialchars($addr) . '</span>';

            $row[] = '<span class="bd-badge bg-success" style="white-space: nowrap;">' . htmlspecialchars($lead['uploaded_from'] ?? '') . '</span>';

            $phone = $lead['phone_number'] ?? $lead['phone'];
            $clean_phone = preg_replace('/[^0-9]/', '', $phone ?? '');
            $row[] = '<a href="https://wa.me/' . $clean_phone . '" target="_blank">' . htmlspecialchars($phone ?? '') . '</a>';

            $displayName = !empty($lead['campaign_name']) ? $lead['campaign_name'] : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
            $plainText = strip_tags($displayName);
            $truncatedDisplayName = (mb_strlen($plainText) > 15) ? mb_substr($plainText, 0, 15) . '...' : $displayName;
            $campaignHtml = '<div class="d-flex align-items-center gap-2 flex-nowrap">
                <span class="project-badge mb-0">
                    <i class="bi bi-building"></i> ' . htmlspecialchars($truncatedDisplayName) . '
                </span>';
            if (mb_strlen($plainText) > 15) {
                $campaignHtml .= '<button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0 viewCampaignBtnDynamic" data-description="' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '" data-bs-toggle="modal" data-bs-target="#viewProjectCampaignName">View</button>';
            }
            $campaignHtml .= '</div>';
            $row[] = $campaignHtml;

            $last_action = $lead['last_lead_action'] ?? 'not_called';
            $base_url = $GLOBALS['base_url'];
            $statusHtml = '
            <form method="POST" action="' . $base_url . '/update-last_leadAction/' . $lead['id'] . '" class="m-0 p-0">
                <select name="last_lead_action" class="form-select form-select-sm last-action-select action-' . $last_action . '" onchange="this.form.submit()">
                    <option value=""> Update action </option>
                    <option value="new" ' . ($last_action == 'new' ? 'selected' : '') . '>New</option>
                    <option value="booked" ' . ($last_action == 'booked' ? 'selected' : '') . '>Booked</option>
                    <option value="follow up" ' . ($last_action == 'follow up' ? 'selected' : '') . '>Follow Up</option>
                    <option value="visited" ' . ($last_action == 'visited' ? 'selected' : '') . '>Visited</option>
                    <option value="cancel" ' . ($last_action == 'cancel' ? 'selected' : '') . '>Cancel</option>
                </select>
            </form>';
            $row[] = $statusHtml;

            $next_date = (!empty($lead['next_follow_up_date']) && $lead['next_follow_up_date'] !== '0000-00-00') ? date('d-M-Y', strtotime($lead['next_follow_up_date'])) : 'No Schedule';
            $row[] = '<div>' . $next_date . '</div>';

            $agent_name = htmlspecialchars($lead['agent_name'] ?? 'No Agent Assigned');
            $noteHtml = '<div class="d-flex align-items-center gap-2 flex-nowrap">
                <span class="bd-badge bg-primary text-truncate" style="max-width: 150px;">' . $agent_name . '</span>
                <span class="bd-badge bg-success">';
            if (!empty($lead['call_note'])) {
                $noteHtml .= '<button type="button" class="btn btn-sm p-0  note-view-btn" style="font-size:11px; vertical-align:baseline; color:#fff;" data-note="' . htmlspecialchars($lead['call_note'], ENT_QUOTES, 'UTF-8') . '" data-bs-toggle="modal" data-bs-target="#viewNoteModal">View Note</button>';
            } else {
                $noteHtml .= 'No Note';
            }
            $noteHtml .= '</span></div>';
            $row[] = $noteHtml;

            $actionHtml = '';
            if ((isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], [1, 3, 5, 9])) || isSalesCoLeader()) {
                $fu_count = !empty($followUpCounts[$lead['id']]) ? '<span class="action-badge">' . $followUpCounts[$lead['id']] . '</span>' : '';
                $bk_count = !empty($bookingCount[$lead['id']]) ? '<span class="action-badge">' . $bookingCount[$lead['id']] . '</span>' : '';
                $bk_text = !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking';

                $actionHtml = '
                <div class="d-flex align-items-center gap-1">
                    <a href="' . $base_url . '/follow-up-leads/' . $lead['id'] . '" class="btn btn-primary btn-sm position-relative" style="white-space:nowrap;">Follow Up ' . $fu_count . '</a>
                    <a href="' . $base_url . '/booking-leads/' . $lead['id'] . '" class="btn btn-secondary btn-sm position-relative" style="white-space:nowrap;">' . $bk_text . ' ' . $bk_count . '</a>
                    <div class="dropdown">
                        <button class="action-dot-btn btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                            <li><a class="dropdown__item" href="' . $base_url . '/edit-lead/' . $lead['id'] . '"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit</a></li>
                            <li><a class="dropdown__item" href="' . $base_url . '/view-lead/' . $lead['id'] . '"><i class="bi bi-eye me-2 text-success"></i> View</a></li>
                            <li><button class="dropdown__item text-danger removeBtnCust w-50 text-start d-flex align-items-center" data-name="' . htmlspecialchars($lead['full_name'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-action="lead-delete" data-id="' . $lead['id'] . '"><i class="bi bi-trash me-2"></i> Delete</button></li>
                        </ul>
                    </div>
                </div>';
            } else {
                $fu_count = $followUpCounts[$lead['id']] ?? 0;
                $bk_count = $bookingCount[$lead['id']] ?? 0;
                $bk_text = !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking';

                $actionHtml = '
                <div class="d-flex align-items-center gap-1 flex-nowrap">
                    <button class="btn btn-success btn-sm position-relative" disabled>Follow Up <span class="action-badge">' . $fu_count . '</span></button>
                    <button class="btn btn-secondary btn-sm position-relative" disabled>' . $bk_text . ' <span class="action-badge">' . $bk_count . '</span></button>
                </div>';
            }
            $row[] = $actionHtml;

            $row['DT_RowAttr'] = ['data-id' => $lead['id']];

            $data[] = $row;
        }

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $filteredRecords,
            "data" => $data
        ]);
        exit;
    }

    public function assignMultipleLeads()
    {
        $userId      = (int)($_POST['user_id'] ?? 0);
        $leadIds     = $_POST['lead_ids'] ?? '';
        $assignedDate = $_POST['assigned_date'] ?? date('Y-m-d');
        $assignedBy   = $_SESSION['user']['id'] ?? 0;

        if (!$userId || !$leadIds) {
            setError("Please select user and leads!");
            header("Location: " . $GLOBALS['base_url'] . "/lead-lists");
            exit;
        }

        // Clean IDs
        $ids = array_filter(array_map('intval', explode(',', $leadIds)));

        if (empty($ids)) {
            setError("Invalid lead selection!");
            header("Location: " . $GLOBALS['base_url'] . "/lead-lists");
            exit;
        }

        $idsStr = implode(',', $ids);

        // Final assigned datetime
        $assignedAt = $assignedDate . ' ' . date('H:i:s');

        //  Update with assigned_at + assigned_by
        $sql = "
        UPDATE leads 
        SET 
            assigned_id = $userId,
            assigned_at = '$assignedAt',
            created_by = $assignedBy
        WHERE id IN ($idsStr)
    ";

        $this->db->query($sql);

        // Get user name
        $result = $this->db->query("SELECT full_name FROM users WHERE id=$userId");
        $userName = $result ? ($result->fetch_assoc()['full_name'] ?? '') : '';

        setSuccess("Leads assigned successfully to $userName!");
        header("Location: " . $GLOBALS['base_url'] . "/lead-lists");
        exit;
    }



    public function showOnboardClient()
    {
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $leads = $this->leadBookedModel->getAllOnboardClientBooking($year, $month);
        $leadCounts = count($leads);
        $bookingCount = $this->leadBookedModel->getBookingCountByLead();
        $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $totalTodayFollowUps = array_sum($todayFollowUp);
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $paymentCount = (new PaymentLeadBooking($this->db))->getpaymentCount();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $this->view("sales-developments/leads/onboard-clients", [
            "current_route" => "onboarding-clients",
            "page_title" => "All Onboard Clients",
            "followUpCounts" => $followUpCounts,
            "bookingCount" => $bookingCount,
            "paymentCount" => $paymentCount,
            "leadCounts" => $leadCounts,
            "totalTodayFollowUps" => $totalTodayFollowUps,
            "leads" => $leads,

        ]);
    }

    public function create()
    {
        $teams = $this->teamsModel->getAll();
        $assignedUsers = $this->userModel->getAllRoleUser();
        $sources = $this->leadSource->getAll();
        $locations = $this->locationModel->getAll();
        $projects = $this->projectModel->getAll();
        $this->view("sales-developments/leads/create-lead", [
            "current_route"   => "create-lead",
            "active"          => "create-lead",
            "page_title"      => "Add lead",
            "show_breadcrumb" => true,
            "assignedUsers" => $assignedUsers,
            "sources" => $sources,
            "locations" => $locations,
            "projects" => $projects,
            "teams" => $teams,

        ]);
    }

    // Handle form submission
    // controllers/EmployeeController.php
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $leadData = [
            // CSV / new fields
            'campaign_name' => $_POST['campaign_name'] ?? null,
            'platform' => $_POST['platform'] ?? null,
            'which_apartment_size_are_you_interested_in' => $_POST['which_apartment_size_are_you_interested_in'] ?? null,
            'which_floor_are_you_for_shop' => $_POST['which_floor_are_you_for_shop'] ?? null,
            'when_are_you_planning_to_buy' => $_POST['when_are_you_planning_to_buy'] ?? null,
            'would_you_like_to_schedule_a_site_visit' => $_POST['would_you_like_to_schedule_a_site_visit'] ?? null,
            'when_is_the_best_time_to_contact_you' => $_POST['when_is_the_best_time_to_contact_you'] ?? null,
            'job_title' => $_POST['job_title'] ?? null,
            'city' => $_POST['city'] ?? null,

            // Existing fields
            'full_name' => $_POST['full_name'] ?? null,
            'lead_type' => $_POST['lead_type'] ?? null,
            'father_name' => $_POST['father_name'] ?? null,
            'mother_name' => $_POST['mother_name'] ?? null,
            'dob' => $_POST['dob'] ?? null,
            'permanent_address' => $_POST['permanent_address'] ?? null,
            'nid_or_passport' => $_POST['nid_or_passport'] ?? null,
            'company_name' => $_POST['company_name'] ?? null,
            'phone_number' => $_POST['phone_number'] ?? null,
            'phone' => $_POST['phone_number'] ?? null,
            'alt_phone_number' => $_POST['alt_phone_number'] ?? null,
            'email' => $_POST['email'] ?? null,
            'source_type_id' => $_POST['source_type_id'] ?? null,
            'assigned_id' => $_POST['assigned_id'] ?? null,
            'address' => $_POST['address'] ?? null,
            'pref_location_id' => $_POST['pref_location_id'] ?? null,
            'designation' => $_POST['designation'] ?? null,
            'project_id' => $_POST['project_id'] ?? null,
            'interest_on' => $_POST['interest_on'] ?? null,
            'pref_flat_size' => $_POST['pref_flat_size'] ?? null,
            'communication_type' => $_POST['communication_type'] ?? null,
            'tags' => $_POST['tags'] ?? null,
            'description' => $_POST['description'] ?? null,
            'visibility' => $_POST['visibility'] ?? 'public',
            'status' => 'active',
            'lead_quality' => $_POST['lead_quality'] ?? 'Pending',
            'created_by' => $_SESSION['user']['id'] ?? null,
            'team_id' => $_POST['team_id'] ?? null,
        ];


        $loggedInRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);

        if ($this->leadModel->create($leadData)) {

            setSuccess("Lead created successfully!");

            if ($loggedInRoleId === 1 || $loggedInRoleId === 5 || $loggedInRoleId === 3 || $loggedInRoleId === 9) {
                header("Location: {$GLOBALS['base_url']}/lead-lists");
            } else {
                header("Location: {$GLOBALS['base_url']}/today-leads");
            }

            exit;
        } else {
            header("Location: {$GLOBALS['base_url']}/create-lead");
            exit;
        }
    }


    public function show($id)
    {
        $leadData = $this->leadModel->find($id);
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $followUpProjects = (new FollowUp($this->db))->getByLead($id);
        $bookingCount = $this->leadBookedModel->getBookingCountByLead();

        $this->view("sales-developments/leads/view-lead", [
            "current_route" => "view-lead",
            "page_title"  => "View Lead",
            "leadData" => $leadData,
            "followUpCounts" => $followUpCounts,
            "followUpProjects" => $followUpProjects,
            "bookingCount" => $bookingCount,
        ]);
    }
    // Edit Employee Page
    public function edit($id)
    {
        $assignedUsers = $this->userModel->getAll();
        $leadData = $this->leadModel->find($id);
        $sources = $this->leadSource->getAll();
        $this->view("sales-developments/leads/edit-lead", [
            "current_route"   => "edit-lead",
            "page_title"      => "Edit Lead",
            "leadData"        => $leadData,
            "assignedUsers"        => $assignedUsers,
            "sources"        => $sources,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Collect form data from update form
        $leadData = [
            // Personal Info
            'full_name' => $_POST['full_name'] ?? null,
            'lead_name_bn' => $_POST['lead_name_bn'] ?? null,
            'father_name' => $_POST['father_name'] ?? null,
            'father_name_bn' => $_POST['father_name_bn'] ?? null,
            'mother_name' => $_POST['mother_name'] ?? null,
            'mother_name_bn' => $_POST['mother_name_bn'] ?? null,
            'dob' => $_POST['dob'] ?? null,
            'nid_or_passport' => $_POST['nid_or_passport'] ?? null,
            'identity_type' => $_POST['identity_type'] ?? null,
            'permanent_address' => $_POST['permanent_address'] ?? null,
            'permanent_add_bn' => $_POST['permanent_add_bn'] ?? null,
            'address' => $_POST['address'] ?? null,
            'present_add_bn' => $_POST['present_add_bn'] ?? null,
            'designation' => $_POST['designation'] ?? null,
            'profession_bn' => $_POST['profession'] ?? null,
            'nationality' => $_POST['nationality'] ?? 'Bangladeshi',
            'religion' => $_POST['religion'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'lead_image' => $_FILES['lead_image']['name'] ?? null, // handle image upload separately

            // Lead Meta
            // 'lead_type' => $_POST['lead_type'] ?? null,
            // 'company' => $_POST['company'] ?? null,
            // 'currency' => $_POST['currency'] ?? 'BDT',
            // 'source_type_id' => $_POST['source_type_id'] ?? null,
            // 'assigned_id' => $_POST['assigned_id'] ?? null,
            // 'pref_location_id' => $_POST['pref_location_id'] ?? null,
            // 'project_type' => $_POST['project_type'] ?? null,
            // 'pref_flat_size' => $_POST['pref_flat_size'] ?? null,
            // 'communication_type' => $_POST['communication_type'] ?? null,
            // 'tags' => $_POST['tags'] ?? null,
            'description' => $_POST['description'] ?? null,
            // 'visibility' => $_POST['visibility'] ?? 'public',
            // 'status' => $_POST['status'] ?? 'active',
            // 'lead_quality' => $_POST['lead_quality'] ?? 'Pending',

            // Nominee Info
            'nominee_name' => $_POST['nominee_name'] ?? null,
            'relation_with_lead' => $_POST['relation_with_lead'] ?? null,
            'nomi_father_name' => $_POST['nomi_father_name'] ?? null,
            'nomi_mother_name' => $_POST['nomi_mother_name'] ?? null,
            'nomi_profession' => $_POST['nomi_profession'] ?? null,
            'nomi_dob' => $_POST['nomi_dob'] ?? null,
            'nomi_phone' => $_POST['nomi_phone'] ?? null,
            'nomi_email' => $_POST['nomi_email'] ?? null,
            'nomi_nid_passport' => $_POST['nomi_nid_passport'] ?? null,
            'nomi_present_add' => $_POST['nomi_present_address'] ?? null,
            'nomi_permanent_address' => $_POST['nomi_permanent_address'] ?? null,
            'nomi_image' => $_FILES['nomi_image']['name'] ?? null, // handle upload separately
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        // Update lead
        try {
            $this->leadModel->update($id, $leadData);
            setSuccess("Lead updated successfully!");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        } catch (Exception $e) {
            setError($e->getMessage());
            $_SESSION['old'] = $_POST;
            header("Location: {$GLOBALS['base_url']}/edit-lead/{$id}");
            exit;
        }
    }

    public function updateLastleadAction($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $leadId     = (int) $id;
        $lastAction = $_POST['last_lead_action'] ?? null;

        // Allowed enum values (important for security)
        $allowedActions = ['new', 'booked', 'follow up', 'visited', 'cancel'];

        if (!$leadId || !in_array($lastAction, $allowedActions)) {
            setError("Invalid lead action selected.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // 🔒 Prevent update if already booked
        $stmt = $this->db->prepare("SELECT last_lead_action FROM leads WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $lead = $result->fetch_assoc();

        if (!$lead) {
            setError("Lead not found.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if ($lead['last_lead_action'] === 'booked') {
            setError("Action cannot be changed because this lead is already booked.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        //  Update the lead action
        try {
            $stmtUpdate = $this->db->prepare("UPDATE leads SET last_lead_action = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $lastAction, $leadId);
            $stmtUpdate->execute();

            setSuccess("Lead last action updated successfully!");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } catch (Exception $e) {
            setError($e->getMessage());
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

// form DMT executive dashboard CSV import
    public function importCsv()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            return;
        }

        // FORM থেকে team_id
        $team_id = $_POST['team_id'] ?? null;

        if (!$team_id) {
            setError("Please select a team");
            return;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            return;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {

            //  HEADER read + encoding fix
            $header = fgetcsv($handle);
            $header = array_map(function ($value) {
                return mb_convert_encoding(trim($value), 'UTF-8', 'Windows-1252');
            }, $header);

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {

                //  ROW encoding fix
                $row = array_map(function ($value) {
                    return mb_convert_encoding(trim($value), 'UTF-8', 'Windows-1252');
                }, $row);

                // skip empty row
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                // header mismatch safe check
                if (count($header) !== count($row)) {
                    continue;
                }

                $data = array_combine($header, $row);

                // default / required fields
                $data['source_type_id'] = (int)($data['source_type_id'] ?? 0);
                $data['status'] = 'active';
                $data['visibility'] = $data['visibility'] ?? 'public';
                $data['team_id'] = (int)$team_id;
                $data['created_by'] = $_SESSION['user']['id'] ?? null;

                try {
                    $this->leadModel->create($data);
                } catch (Exception $e) {
                    // optional: log error instead of breaking পুরো import
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }

            fclose($handle);

            setSuccess("CSV imported successfully");
            header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
            exit;
        } else {
            setError("Unable to read CSV file");
            return;
        }
    }
// form lead list
    public function importCsvBulkLeads()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        // Move to temp location
        $uploadDir = __DIR__ . '/../../public/uploads/csv_imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempName = 'leads_import_' . date('YmdHis') . '_' . uniqid() . '.csv';
        $tempPath = $uploadDir . $tempName;

        if (!move_uploaded_file($fileTmp, $tempPath)) {
            setError("Failed to upload file.");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        if (($handle = fopen($tempPath, "r")) !== false) {
            $header = fgetcsv($handle);
            
            // Clean BOM from first header if present
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            $header = array_map(function ($value) {
                return trim($value);
            }, $header);

            $duplicates = [];
            $validCount = 0;
            $rowIndex = 1; // start from row 1 (excluding header)

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $rowIndex++;
                if (count(array_filter($row)) === 0) continue; // skip empty rows
                if (count($header) !== count($row)) continue;

                $data = array_combine($header, $row);
                $name = $data['full_name'] ?? '';
                $phone = $data['phone'] ?? $data['phone_number'] ?? '';

                if ($name && $phone) {
                    $existing = $this->leadModel->findDuplicate($name, $phone);
                    if ($existing) {
                        $duplicates[] = [
                            'csv_row' => $rowIndex,
                            'csv_data' => $data,
                            'db_data' => $existing
                        ];
                    } else {
                        $validCount++;
                    }
                } else {
                    $validCount++;
                }
            }
            fclose($handle);

            $_SESSION['leads_csv_import_file'] = $tempPath;
            $_SESSION['leads_csv_import_filename'] = $fileName;
            $_SESSION['ignored_csv_rows'] = [];

            $this->view("sales-developments/leads/bulk-leads-import-preview", [
                "current_route" => "bulk-leads-import-csv",
                "page_title"    => "Leads CSV Import Preview - " . $fileName,
                "duplicates"    => $duplicates,
                "valid_count"   => $validCount,
                "filename"      => $fileName
            ]);
        } else {
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }
    }

    public function executeCsvBulkLeads()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        $tempFile = $_SESSION['leads_csv_import_file'] ?? null;
        $ignoredRows = $_SESSION['ignored_csv_rows'] ?? [];

        if (!$tempFile || !file_exists($tempFile)) {
            setError("Import session expired or file not found. Please re-upload.");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        if (($handle = fopen($tempFile, "r")) !== false) {
            $header = fgetcsv($handle);
            
            // Clean BOM from first header if present
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            $header = array_map(function ($value) {
                return trim($value);
            }, $header);

            $successCount = 0;
            $rowIndex = 1;

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $rowIndex++;
                if (in_array($rowIndex, $ignoredRows)) continue; // skipped by user
                
                if (count(array_filter($row)) === 0) continue; // skip empty rows
                if (count($header) !== count($row)) continue;

                $data = array_combine($header, $row);
                
                // Set default status and visibility
                $data['status'] = 'active';
                $data['visibility'] = 'public';
                $data['created_by'] = $_SESSION['user']['id'] ?? null;
                $data['uploaded_from'] = 'From Company csv'; // CSV bulk import tag
                
                try {
                    $this->leadModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);
            
            // cleanup
            unlink($tempFile);
            unset($_SESSION['leads_csv_import_file']);
            unset($_SESSION['leads_csv_import_filename']);
            unset($_SESSION['ignored_csv_rows']);

            setSuccess("$successCount Leads imported successfully from CSV");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        } else {
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
            exit;
        }
    }

    public function ignoreCsvLeadAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rowId = isset($_POST['row_id']) ? (int)$_POST['row_id'] : 0;
            if ($rowId > 0) {
                if (!isset($_SESSION['ignored_csv_rows'])) {
                    $_SESSION['ignored_csv_rows'] = [];
                }
                if (!in_array($rowId, $_SESSION['ignored_csv_rows'])) {
                    $_SESSION['ignored_csv_rows'][] = $rowId;
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Invalid row id']);
        exit;
    }

    public function deleteLeadAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id > 0) {
                try {
                    $deleted = $this->leadModel->softDelete($id);
                    if ($deleted) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                } catch (\Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            }
            echo json_encode(['success' => false, 'message' => 'Invalid Lead ID']);
            exit;
        }
    }


    // Get single lead by ID


    // Optional: get all leads
    public function getAll()
    {
        $result = $this->db->query("SELECT * FROM leads ORDER BY created_at DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function storeLocation()
    {
        $data = [
            'name'   => $_POST['name'] ?? '',
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];


        if ($this->locationModel->create($data)) {
            setSuccess('Location Create successfully');
            header("Location: {$GLOBALS['base_url']}/create-lead");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to create Locations </h3>";
        }
    }


    public function storeSource()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Form data 
        $name = $_POST['name'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$name) {
            setError("Source name is required!");
            header("Location: {$GLOBALS['base_url']}/create-lead");
            exit;
        }

        // Helper unique slug generate 
        $slug = generateUniqueSlug($this->db, $name);

        $data = [
            'name' => $name,
            'slug' => $slug,
            'is_active' => $is_active
        ];

        // Model call
        if ($this->leadSourceModel->create($data)) {
            setSuccess("Lead source created successfully!");
        } else {
            setError("Failed to create lead source.");
        }

        header("Location: {$GLOBALS['base_url']}/create-lead");
        exit;
    }
    // Delete Employee
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setError("Invalid Request");
            header("Location:{$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            setError("Invalid Lead ID");
            header("Location:{$GLOBALS['base_url']}/lead-lists");
            exit;
        }

        try {
            $leadDelete = $this->leadModel->softDelete($id);

            if ($leadDelete) {
                setSuccess("Lead deleted Successfully");
            } else {
                setError("Something Went Wrong");
            }
        } catch (\Throwable $th) {
            setError("Error: " . $th->getMessage());
        }

        header("Location:{$GLOBALS['base_url']}/lead-lists");
        exit;
    }
}