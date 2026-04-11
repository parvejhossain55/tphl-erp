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

        if (in_array($userRoleId, [5, 9])) {

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



        // echo "Role: " . $_SESSION['user']['role_id'];
        // echo "<br>Team: " . $_SESSION['user']['team_id'];
        // die();
        $leads = $this->leadModel->getAllWithFilter($year, $month, $lastAction, $userRoleId, $userTeamId, $assigned, $assigned_user, $date);
        $leadCounts = count($leads);

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


        ]);
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
