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
    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);
        $this->leadSource = new LeadSource($this->db);
        $this->leadBookedModel = new BookingLead($this->db);
        $this->locationModel = new Location($this->db);
        $this->leadSourceModel = new LeadSource($this->db);
    }
    public function index()
    {
        $assignedUsers = $this->userModel->getAllRoleUser();
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $owners = $this->userModel->getAll();
        $leads = $this->leadModel->getAllWithFilter($year, $month);
        $leadCounts = count($leads);
        $bookingCount = $this->leadBookedModel->getBookingCountByLead();
        $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $totalTodayFollowUps = array_sum($todayFollowUp);
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $paymentCount = (new PaymentLeadBooking($this->db))->getpaymentCount();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = "";

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

        ]);
    }

public function assignMultipleLeads()
{
    $userId = (int)($_POST['user_id'] ?? 0);
    $leadIds = $_POST['lead_ids'] ?? '';

    if (!$userId || !$leadIds) {
        setError("Please select user and leads!") ;
        header("Location: " . $GLOBALS['base_url'] . "/lead-lists");
        exit;
    }

    $ids = array_map('intval', explode(',', $leadIds));
    $idsStr = implode(',', $ids);

    $this->db->query("UPDATE leads SET assigned_id=$userId WHERE id IN ($idsStr)");

    $userName = $this->db->query("SELECT full_name FROM users WHERE id=$userId")->fetch_assoc()['full_name'] ?? '';
 setSuccess("Leads assigned successfully to $userName!") ;
    header("Location: " . $GLOBALS['base_url'] . "/lead-lists");
    exit;
}



    public function showOnboardClient()
    {
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $leads = $this->leadBookedModel->getAllOnboardClient($year, $month);
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
        $assignedUsers = $this->userModel->getAllRoleUser();
        $sources = $this->leadSource->getAll();
        $locations = $this->locationModel->getAll();

        $this->view("sales-developments/leads/create-lead", [
            "current_route"   => "create-lead",
            "active"          => "create-lead",
            "page_title"      => "Add lead",
            "show_breadcrumb" => true,
            "assignedUsers" => $assignedUsers,
            "sources" => $sources,
            "locations" => $locations,

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
            'project_type' => $_POST['project_type'] ?? null,
            'interest_on' => $_POST['interest_on'] ?? null,
            'pref_flat_size' => $_POST['pref_flat_size'] ?? null,
            'communication_type' => $_POST['communication_type'] ?? null,
            'tags' => $_POST['tags'] ?? null,
            'description' => $_POST['description'] ?? null,
            'visibility' => $_POST['visibility'] ?? 'public',
            'status' => 'active',
            'lead_quality' => $_POST['lead_quality'] ?? 'Pending',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];


        $loggedInRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);

        if ($this->leadModel->create($leadData)) {

            setSuccess("Lead created successfully!");

            if ($loggedInRoleId === 5) {
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
        $allowedActions = ['not_called', 'booked', 'called', 'visited','canceled'];

        if (!$leadId || !in_array($lastAction, $allowedActions)) {
            setError("Invalid lead action selected.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $this->leadModel->updateLastLeadAction($leadId, $lastAction);

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
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            return;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            return;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {

            $header = fgetcsv($handle); // first row → header

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {

                //  CSV → DB column mapping
                $data = array_combine($header, $row);
                $data['source_type_id'] = (int)($data['source_type_id'] ?? 0);
                $data['status'] = (int)($data['status'] ?? 0);
                $data['visibility'] = $data['visibility'] ?? 'public';

                $this->leadModel->create($data);
            }

            fclose($handle);
            setSuccess("CSV imported successfully");
            header("Location: {$GLOBALS['base_url']}/lead-lists");
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
        setError( "Failed to create lead source.");
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
