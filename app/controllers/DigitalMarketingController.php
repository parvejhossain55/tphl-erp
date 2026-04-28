<?php
class DigitalMarketingController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $teamsModel;
    protected $leadSourceModel;
    protected $platformBudgetModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->teamsModel = new Team($this->db);
        $this->leadSourceModel = new LeadSource($this->db);
        $this->platformBudgetModel = new PlatformBudget($this->db);
    }
 public function index()
{
    $stats = $this->leadModel->getDigitalMarketingStats();
    $teams = $this->teamsModel->getAll();
    $leads = [];
    $teamId = $_GET['team_id'] ?? null;

    // New Filters
    $dateAge = $_GET['date_age'] ?? '7days';
    $sortOrder = $_GET['sort'] ?? 'newest';
    $duplicatesOnly = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';

    $sqlSort = ($sortOrder === 'oldest') ? 'ASC' : 'DESC';

    $currentMonthFilter = $_GET['budget_month'] ?? date('Y-m') . '-01'; 
    if (strlen($currentMonthFilter) === 7) { 
        $currentMonthFilter .= '-01';
    }

    $sources = $this->leadSourceModel->getAll();
    
    // Get platform budgets using the updated model (no source join needed)
    $platformBudgets = $this->platformBudgetModel->getAllWithPlatforms($currentMonthFilter);
    $budgetSummary = $this->platformBudgetModel->getMonthlySummary($currentMonthFilter);

    // --- NEW LOGIC FOR CPL ---
    $platformLeadCounts = $this->leadModel->getMarketingPlatformLeadCounts($currentMonthFilter);
    
    // Define the broad grouping
    $platformMapping = [
        'fb' => 'Meta',
        'whatsapp' => 'Meta',
        'instagram' => 'Meta',
        'ig' => 'Meta',
        'google' => 'Google',
        'youtube' => 'Google',
        'linkedin' => 'LinkedIn'
    ];
    
    $cplData = [];
    foreach ($platformBudgets as $pb) {
        $parentName = $pb['platform_name'];
        $cplData[$parentName] = [
            'budget' => $pb['budget'],
            'spent' => $pb['spent'],
            'total_leads' => 0,
            'cpl' => 0,
            'sources' => []
        ];
    }
    
    foreach ($platformLeadCounts as $plc) {
        $raw = strtolower(trim($plc['raw_platform']));
        $parent = $platformMapping[$raw] ?? 'Other';
        
        if (isset($cplData[$parent])) {
            $cplData[$parent]['total_leads'] += $plc['total_leads'];
            $cplData[$parent]['sources'][] = [
                'raw_platform' => $raw,
                'leads' => $plc['total_leads']
            ];
        } else {
            // Keep track of 'Other' unbudgeted platforms safely
            if (!isset($cplData['Other'])) {
                $cplData['Other'] = ['budget' => 0, 'spent' => 0, 'total_leads' => 0, 'cpl' => 0, 'sources' => []];
            }
            $cplData['Other']['total_leads'] += $plc['total_leads'];
            $cplData['Other']['sources'][] = [
                'raw_platform' => $raw,
                'leads' => $plc['total_leads']
            ];
        }
    }
    
    // Calculate final CPL per parent platform
    foreach ($cplData as $parent => &$data) {
        if ($data['total_leads'] > 0) {
            $computed_cpl = $data['spent'] / $data['total_leads'];
            // formatting beautifully
            $data['cpl'] = (floor($computed_cpl) == $computed_cpl) ? number_format($computed_cpl, 0) : number_format($computed_cpl, 2);
        }
    }
    unset($data);

    if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10) {
        $leads = $this->leadModel->getMarketingLeadsFlexible(
            null,
            $teamId, 
            $dateAge, 
            $sqlSort
        );
    }

    // Calculate duplicate frequencies
    $phoneFreq = [];
    foreach ($leads as $l) {
        $p = $l['phone_number'] ?? '';
        if (!empty($p)) {
            $phoneFreq[$p] = ($phoneFreq[$p] ?? 0) + 1;
        }
    }
    
    // Filter for duplicates if requested
    if ($duplicatesOnly) {
        $leads = array_filter($leads, function($l) use ($phoneFreq) {
            $p = $l['phone_number'] ?? '';
            return !empty($p) && ($phoneFreq[$p] ?? 0) > 1;
        });
    }

    $duplicateLeadsCount = 0;
    foreach ($phoneFreq as $p => $count) {
        if ($count > 1) {
            $duplicateLeadsCount += $count;
        }
    }

    return $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
        "current_route"       => "digital-marketing-dashboard",
        "page_title"          => "Digital Marketing Dashboard",
        "teams"               => $teams,
        "stats"               => $stats,
        "leads"               => $leads,
        "selected_team_id"    => $teamId,
        "sources"             => $sources,
        "platformBudgets"     => $platformBudgets,  // Changed from $platformBudgets (commented)
        "budgetSummary"       => $budgetSummary,
        "cplData"             => $cplData,
        "budget_month"        => substr($currentMonthFilter, 0, 7),
        "phoneFreq"           => $phoneFreq,
        "duplicateLeadsCount" => $duplicateLeadsCount,
        "date_age"            => $dateAge,
        "sort_order"          => $sortOrder,
        "duplicates_only"     => $duplicatesOnly
    ]);
}
public function storeBudget()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $platform_name = trim($_POST['platform_name']);
        $month = trim($_POST['month']) . '-01';
        $budget = (float)$_POST['budget'];
        $spent = (float)$_POST['spent'];
        
        // Validate platform name
        $allowed_platforms = ['Meta', 'Google', 'LinkedIn'];
        if (!in_array($platform_name, $allowed_platforms)) {
            $_SESSION['error'] = "Invalid platform selected. Please choose Meta, Google, or LinkedIn.";
            header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
            exit;
        }
        
        // Check if budget already exists for this platform and month
        $existing = $this->platformBudgetModel->getByPlatformAndMonth($platform_name, $month);
        
        if ($existing) {
            $_SESSION['error'] = "Budget for {$platform_name} in this month already exists!";
            header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
            exit;
        }
        
        $data = [
            'platform_name' => $platform_name,
            'month'         => $month,
            'budget'        => $budget,
            'spent'         => $spent,
            'created_by'    => $_SESSION['user']['id'] ?? 0
        ];
        
        if ($this->platformBudgetModel->create($data)) {
            $_SESSION['success'] = "Budget for {$platform_name} added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add budget.";
        }
        
        header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
        exit;
    }
}

// Optional: Add update and delete methods
public function updateBudget()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];
        $budget = (float)$_POST['budget'];
        $spent = (float)$_POST['spent'];
        
        $data = [
            'budget' => $budget,
            'spent' => $spent
        ];
        
        if ($this->platformBudgetModel->update($id, $data)) {
            $_SESSION['success'] = "Budget updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update budget.";
        }
        
        header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
        exit;
    }
}

public function deleteBudget($id)
{
    if ($this->platformBudgetModel->delete($id)) {
        $_SESSION['success'] = "Budget deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete budget.";
    }
    
    header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
    exit;
}

    public function executiveDashboard()
    {
        $stats = $this->leadModel->getDigitalMarketingStats();
        $teams = $this->teamsModel->getAll();
        $leads = [];
        $teamId = $_GET['team_id'] ?? null;
        
        // Filters
        $dateAge = $_GET['date_age'] ?? '7days'; // default to last 7 days
        $sortOrder = $_GET['sort'] ?? 'newest';
        $duplicatesOnly = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';
        $contactStatusFilter = $_GET['contact_status'] ?? ''; // 'contacted', 'not_contacted', or ''

        $sqlSort = ($sortOrder === 'oldest') ? 'ASC' : 'DESC';

        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 11) {
            $leads = $this->leadModel->getMarketingLeadsFlexible(
                $_SESSION['user']['id'], 
                $teamId, 
                $dateAge, 
                $sqlSort
            );
        }

        // Calculate duplicate frequencies (before any filtering)
        $phoneFreq = [];
        foreach ($leads as $l) {
            $p = $l['phone_number'] ?? '';
            if (!empty($p)) {
                $phoneFreq[$p] = ($phoneFreq[$p] ?? 0) + 1;
            }
        }
        
        // Filter for duplicates if requested
        if ($duplicatesOnly) {
            $leads = array_filter($leads, function($l) use ($phoneFreq) {
                $p = $l['phone_number'] ?? '';
                return !empty($p) && ($phoneFreq[$p] ?? 0) > 1;
            });
            $leads = array_values($leads);
        }

        // Contact status summary counts (based on full unfiltered leads)
        $totalContacted = 0;
        $totalNotContacted = 0;
        foreach ($leads as $l) {
            if (($l['contact_status'] ?? '') === 'Contacted') {
                $totalContacted++;
            } else {
                $totalNotContacted++;
            }
        }

        $duplicateLeadsCount = 0;
        foreach ($phoneFreq as $p => $count) {
            if ($count > 1) {
                $duplicateLeadsCount += $count;
            }
        }

        return $this->view("digital-marketing-team/digital-marketing-executive", [
            "current_route"        => "digital-marketing-executive-dashboard",
            "page_title"           => "Digital Marketing Executive Dashboard",
            "teams"                => $teams,
            "stats"                => $stats,
            "leads"                => $leads,
            "selected_team_id"     => $teamId,
            "phoneFreq"            => $phoneFreq,
            "duplicateLeadsCount"  => $duplicateLeadsCount,
            "date_age"             => $dateAge,
            "sort_order"           => $sortOrder,
            "duplicates_only"      => $duplicatesOnly,
            "contact_status_filter"=> $contactStatusFilter,
            "totalContacted"       => $totalContacted,
            "totalNotContacted"    => $totalNotContacted,
        ]);
    }

    public function getLeadsByTeam()
{
    $teamId = $_GET['team_id'] ?? null;

    if (!$teamId) {
        echo json_encode([]);
        return;
    }

    $leads = $this->leadModel->getLeadsByTeamId($teamId);

    header('Content-Type: application/json');
    echo json_encode($leads);
}

    public function deletePermanent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Invalid Request";
            header("Location:{$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = "Invalid Lead ID";
            header("Location:{$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
            exit;
        }

        try {
            $leadDelete = $this->leadModel->permanentDelete($id);

            if ($leadDelete) {
                $_SESSION['success'] = "Lead permanently deleted successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong while deleting.";
            }
        } catch (\Throwable $th) {
            $_SESSION['error'] = "Error: " . $th->getMessage();
        }

        header("Location:{$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
        exit;
    }

    public function deletePermanentMultiple()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error'] = "Invalid Request";
        header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
        exit;
    }

    $ids = $_POST['ids'] ?? [];

    if (empty($ids)) {
        $_SESSION['error'] = "No leads selected";
        header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
        exit;
    }

    try {
        foreach ($ids as $id) {
            $this->leadModel->permanentDelete((int)$id);
        }

        $_SESSION['success'] = "Selected leads deleted successfully!";
    } catch (\Throwable $th) {
        $_SESSION['error'] = "Error: " . $th->getMessage();
    }

    header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
    exit;
}
}