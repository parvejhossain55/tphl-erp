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
    $teamId = $_GET['team_id'] ?? null;

    // Filters (for form state only — leads load via AJAX)
    $dateAge        = $_GET['date_age']      ?? 'all';
    $sortOrder      = $_GET['sort']           ?? 'newest';
    $duplicatesOnly = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';

    $currentMonthFilter = $_GET['budget_month'] ?? date('Y-m') . '-01';
    if (strlen($currentMonthFilter) === 7) {
        $currentMonthFilter .= '-01';
    }

    $sources        = $this->leadSourceModel->getAll();
    $platformBudgets = $this->platformBudgetModel->getAllWithPlatforms($currentMonthFilter);
    $budgetSummary  = $this->platformBudgetModel->getMonthlySummary($currentMonthFilter);

    // CPL calculation
    $platformLeadCounts = $this->leadModel->getMarketingPlatformLeadCounts($currentMonthFilter);
    $platformMapping = [
        'fb' => 'Meta', 'whatsapp' => 'Meta', 'instagram' => 'Meta', 'ig' => 'Meta',
        'google' => 'Google', 'youtube' => 'Google',
        'linkedin' => 'LinkedIn'
    ];
    $cplData = [];
    foreach ($platformBudgets as $pb) {
        $cplData[$pb['platform_name']] = ['budget' => $pb['budget'], 'spent' => $pb['spent'], 'total_leads' => 0, 'cpl' => 0, 'sources' => []];
    }
    foreach ($platformLeadCounts as $plc) {
        $raw    = strtolower(trim($plc['raw_platform']));
        $parent = $platformMapping[$raw] ?? 'Other';
        if (isset($cplData[$parent])) {
            $cplData[$parent]['total_leads'] += $plc['total_leads'];
            $cplData[$parent]['sources'][] = ['raw_platform' => $raw, 'leads' => $plc['total_leads']];
        } else {
            if (!isset($cplData['Other'])) $cplData['Other'] = ['budget' => 0, 'spent' => 0, 'total_leads' => 0, 'cpl' => 0, 'sources' => []];
            $cplData['Other']['total_leads'] += $plc['total_leads'];
            $cplData['Other']['sources'][] = ['raw_platform' => $raw, 'leads' => $plc['total_leads']];
        }
    }
    foreach ($cplData as $parent => &$data) {
        if ($data['total_leads'] > 0) {
            $computed_cpl = $data['spent'] / $data['total_leads'];
            $data['cpl'] = (floor($computed_cpl) == $computed_cpl) ? number_format($computed_cpl, 0) : number_format($computed_cpl, 2);
        }
    }
    unset($data);

    // Lightweight duplicate count only
    $dupStmt = $this->db->query("
        SELECT COUNT(*) AS total FROM (
            SELECT phone_number FROM leads
            WHERE deleted_at IS NULL AND phone_number IS NOT NULL AND phone_number != ''
            GROUP BY phone_number HAVING COUNT(*) > 1
        ) dupes
    ");
    $duplicateLeadsCount = $dupStmt ? (int)($dupStmt->fetch_assoc()['total'] ?? 0) : 0;

    // Total lead count
    $totalLeads = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge);

    return $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
        "current_route"       => "digital-marketing-dashboard",
        "page_title"          => "Digital Marketing Dashboard",
        "teams"               => $teams,
        "stats"               => $stats,
        "leads"               => [],   // AJAX loads leads now
        "selected_team_id"    => $teamId,
        "sources"             => $sources,
        "platformBudgets"     => $platformBudgets,
        "budgetSummary"       => $budgetSummary,
        "cplData"             => $cplData,
        "budget_month"        => substr($currentMonthFilter, 0, 7),
        "phoneFreq"           => [],
        "duplicateLeadsCount" => $duplicateLeadsCount,
        "totalLeads"          => $totalLeads,
        "date_age"            => $dateAge,
        "sort_order"          => $sortOrder,
        "duplicates_only"     => $duplicatesOnly,
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
        $teamId = $_GET['team_id'] ?? null;

        // Filters (passed to view for filter form state only)
        $dateAge             = $_GET['date_age']      ?? 'all';
        $sortOrder           = $_GET['sort']           ?? 'newest';
        $duplicatesOnly      = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';
        $contactStatusFilter = $_GET['contact_status'] ?? '';

        // Only load summary counts (not full lead list) — AJAX will fetch leads per page
        $totalLeads    = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge);
        $contactedCount    = 0;
        $notContactedCount = 0;

        // Quick counts for the summary cards via a lightweight query
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE
                    WHEN EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                      OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                      OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                    THEN 1 ELSE 0
                END) AS contacted,
                SUM(CASE
                    WHEN NOT (
                        EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                        OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                        OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                    ) THEN 1 ELSE 0
                END) AS not_contacted
            FROM leads l
            WHERE l.deleted_at IS NULL
            " . ($teamId ? 'AND l.team_id = ?' : '')
        );
        if ($teamId) {
            $stmt->bind_param('i', $teamId);
        }
        $stmt->execute();
        $counts = $stmt->get_result()->fetch_assoc();
        $contactedCount    = (int)($counts['contacted']     ?? 0);
        $notContactedCount = (int)($counts['not_contacted'] ?? 0);

        // Duplicate count (lightweight)
        $dupStmt = $this->db->query("
            SELECT COUNT(*) AS total FROM (
                SELECT phone_number FROM leads
                WHERE deleted_at IS NULL AND phone_number IS NOT NULL AND phone_number != ''
                GROUP BY phone_number HAVING COUNT(*) > 1
            ) dupes
        ");
        $duplicateLeadsCount = $dupStmt ? (int)($dupStmt->fetch_assoc()['total'] ?? 0) : 0;

        return $this->view("digital-marketing-team/digital-marketing-executive", [
            "current_route"        => "digital-marketing-executive-dashboard",
            "page_title"           => "Digital Marketing Executive Dashboard",
            "teams"                => $teams,
            "stats"                => $stats,
            "leads"                => [], // AJAX loads leads now
            "selected_team_id"     => $teamId,
            "phoneFreq"            => [],
            "duplicateLeadsCount"  => $duplicateLeadsCount,
            "date_age"             => $dateAge,
            "sort_order"           => $sortOrder,
            "duplicates_only"      => $duplicatesOnly,
            "contact_status_filter"=> $contactStatusFilter,
            "totalContacted"       => $contactedCount,
            "totalNotContacted"    => $notContactedCount,
            "totalLeads"           => $totalLeads,
        ]);
    }
    // =========================================================================
    // AJAX — Server-side paginated leads
    // Supports both:
    //   role 10 (DM Head)  → /dm-head-leads-ajax
    //   role 11 (DM Exec)  → /dm-exec-leads-ajax
    // =========================================================================
    public function getLeadsAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        // Role guard: allow DM Head (10) and DM Executive (11)
        $userRoleId = $_SESSION['user']['role_id'] ?? null;
        if (!in_array($userRoleId, [10, 11])) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $page           = max(1, (int)($_GET['page']      ?? 1));
        $perPage        = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
        $offset         = ($page - 1) * $perPage;
        $teamId         = !empty($_GET['team_id'])        ? (int)$_GET['team_id'] : null;
        $dateAge        = $_GET['date_age']               ?? 'all';
        $sortOrder      = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';
        $contactStatus  = $_GET['contact_status']         ?? '';
        $duplicatesOnly = !empty($_GET['duplicates_only']);
        $search         = trim($_GET['search'] ?? '');
        $search         = $search === '' ? null : $search;

        // Total count (pre-filter: no contact_status/duplicates which are post-DB)
        $total = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge, $search);

        // Fetch paginated leads
        $leads = $this->leadModel->getMarketingLeadsPaginated(
            $teamId, $dateAge, $sortOrder, $perPage, $offset, $search
        );

        // Phone freq map for duplicate marking
        $phoneFreq = [];
        foreach ($leads as $l) {
            $p = $l['phone_number'] ?? '';
            if (!empty($p)) $phoneFreq[$p] = ($phoneFreq[$p] ?? 0) + 1;
        }

        // Post-filter: contact_status
        if ($contactStatus === 'contacted') {
            $leads = array_values(array_filter($leads, fn($l) => ($l['contact_status'] ?? '') === 'Contacted'));
        } elseif ($contactStatus === 'not_contacted') {
            $leads = array_values(array_filter($leads, fn($l) => ($l['contact_status'] ?? '') !== 'Contacted'));
        }

        // Post-filter: duplicates only
        if ($duplicatesOnly) {
            $leads = array_values(array_filter($leads, function ($l) use ($phoneFreq) {
                $p = $l['phone_number'] ?? '';
                return !empty($p) && ($phoneFreq[$p] ?? 0) > 1;
            }));
        }

        // Build HTML rows
        $rows = [];
        foreach ($leads as $lead) {
            $p           = $lead['phone_number'] ?? '';
            $isDuplicate = !empty($p) && isset($phoneFreq[$p]) && $phoneFreq[$p] > 1;
            $cs          = $lead['contact_status'] ?? 'Not Contacted';
            $leadType    = $lead['lead_type'] ?? '';
            $fuCount     = (int)($lead['follow_up_count'] ?? 0);

            $csBadge = $cs === 'Contacted'
                ? '<span class="badge-contacted"><i class="bi bi-check-circle-fill"></i> Contacted' .
                  (!empty($lead['last_follow_up_type']) ? '<div class="mt-1"><small class="text-muted" style="font-size:.65rem;"><i class="bi bi-arrow-right-short"></i>' . htmlspecialchars($lead['last_follow_up_type']) . '</small></div>' : '') .
                  '</span>'
                : '<span class="badge-not-contacted"><i class="bi bi-x-circle-fill"></i> Not Contacted</span>';

            $ltClass = match (strtolower($leadType)) {
                'hot'         => 'lt-hot',
                'warm'        => 'lt-warm',
                'cold'        => 'lt-cold',
                'prospect', 'high prospect' => 'lt-prospect',
                'visited', 'will visit'     => 'lt-visited',
                'sold'        => 'lt-sold',
                'not interested', "don't call again", 'refund' => 'lt-not-interested',
                'pending'     => 'lt-pending',
                'future'      => 'lt-future',
                'no response' => 'lt-no-response',
                default       => 'lt-default',
            };
            $ltBadge = !empty($leadType)
                ? '<span class="lead-type-badge ' . $ltClass . '">' . htmlspecialchars($leadType) . '</span>'
                : '<span class="text-muted" style="font-size:.75rem;">—</span>';

            $fuBadge = $fuCount > 0
                ? '<span class="badge rounded-pill" style="background:linear-gradient(135deg,#d4edda,#a8d8b8);color:#155724;border:1px solid #c3e6cb;font-size:.7rem;font-weight:700;padding:.3rem .65rem;display:inline-flex;align-items:center;gap:.3rem;"><i class="bi bi-telephone-fill" style="font-size:.6rem;"></i>' . $fuCount . '</span>'
                : '<span class="badge rounded-pill" style="background:#f0f4f8;color:#adb5bd;border:1px solid #dee2e6;font-size:.7rem;font-weight:700;padding:.3rem .65rem;">0</span>';

            $phoneHtml = '<span class="' . ($isDuplicate ? 'text-danger fw-bold' : 'text-muted') . '">' . htmlspecialchars($p ?: 'N/A') . '</span>';

            $rows[] = [
                'id'            => $lead['id'],
                'checkbox'      => '<input type="checkbox" class="rowCheckbox" value="' . $lead['id'] . '">',
                'name'          => '<div class="fw-bold text-dark">' . htmlspecialchars($lead['full_name'] ?? 'N/A') . '</div><small class="text-muted">' . htmlspecialchars($lead['job_title'] ?? 'N/A') . '</small>',
                'contact'       => '<div class="d-flex flex-column"><span><i class="bi bi-telephone text-muted me-2"></i>' . $phoneHtml . '</span><small class="text-muted"><i class="bi bi-envelope me-2"></i>' . htmlspecialchars($lead['email'] ?? 'N/A') . '</small></div>',
                'follow_up'     => $fuBadge,
                'contact_status'=> $csBadge,
                'lead_type'     => $ltBadge,
                'platform'      => '<span class="badge bg-info-soft text-info mb-1 d-inline-block">' . htmlspecialchars($lead['platform'] ?? 'N/A') . '</span><div class="small text-muted">' . htmlspecialchars($lead['campaign_name'] ?? 'N/A') . '</div>',
                'team'          => htmlspecialchars($lead['team_name'] ?? 'N/A'),
                'sales_lead'    => '<div class="fw-bold text-primary">' . htmlspecialchars($lead['sales_team_lead_name'] ?? 'Not Assigned') . '</div>',
                'upload_date'   => '<div class="fw-semibold">' . date('M d, Y', strtotime($lead['created_at'])) . '</div><small class="text-muted">' . date('h:i A', strtotime($lead['created_at'])) . '</small>',
                'action'        => '<button type="button" class="btn btn-sm btn-outline-danger removeBtnCust cursor" data-name="' . htmlspecialchars($lead['full_name'] ?? 'N/A') . '" data-action="delete-lead-permanent" data-id="' . $lead['id'] . '"><i class="bi bi-trash me-1"></i>Delete</button>',
            ];
        }

        echo json_encode([
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'count'       => count($rows),
            'rows'        => $rows,
        ]);
        exit;
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