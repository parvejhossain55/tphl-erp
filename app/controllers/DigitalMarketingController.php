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
        $projectCategoryId = !empty($_GET['project_category_id']) ? (int)$_GET['project_category_id'] : null;

        // Filters (for form state only — leads load via AJAX)
        $dateAge        = $_GET['date_age']      ?? 'all';
        $sortOrder      = $_GET['sort']           ?? 'newest';
        $duplicatesOnly = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';
        $fromDate       = $_GET['from_date']     ?? '';
        $toDate         = $_GET['to_date']       ?? '';

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
                  AND uploaded_from = 'From Campaign'
                GROUP BY phone_number HAVING COUNT(*) > 1
            ) dupes
        ");
        $duplicateLeadsCount = $dupStmt ? (int)($dupStmt->fetch_assoc()['total'] ?? 0) : 0;

        // Total lead count
        $totalLeads = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge, null, $fromDate, $toDate, false, $projectCategoryId);

        // Contacted / Not Contacted summary counts (From Campaign only)
        $cSql = "
            SELECT
                SUM(CASE
                    WHEN EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                      OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                      OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                      OR (l.call_status IN ('called', 'received'))
                    THEN 1 ELSE 0
                END) AS contacted,
                SUM(CASE
                    WHEN NOT (
                        EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                        OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                        OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                        OR (l.call_status IN ('called', 'received'))
                    ) THEN 1 ELSE 0
                END) AS not_contacted
            FROM leads l
            WHERE l.deleted_at IS NULL
              AND l.uploaded_from = 'From Campaign'
        ";

        $cParams = [];
        $cTypes  = "";

        if ($teamId) {
            $cSql .= " AND l.team_id = ?";
            $cParams[] = $teamId;
            $cTypes   .= "i";
        }

        if ($projectCategoryId) {
            $cSql .= " AND l.project_category_id = ?";
            $cParams[] = $projectCategoryId;
            $cTypes   .= "i";
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $cSql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $cParams[] = $fromDate;
            $cParams[] = $toDate;
            $cTypes   .= "ss";
        } elseif (!empty($fromDate)) {
            $cSql .= " AND DATE(l.created_at) >= ?";
            $cParams[] = $fromDate;
            $cTypes   .= "s";
        } elseif (!empty($toDate)) {
            $cSql .= " AND DATE(l.created_at) <= ?";
            $cParams[] = $toDate;
            $cTypes   .= "s";
        } elseif ($dateAge === 'today') {
            $cSql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateAge === 'old') {
            $cSql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateAge === '7days') {
            $cSql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($dateAge === '15days') {
            $cSql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
        }

        $cStmt = $this->db->prepare($cSql);
        if (!empty($cParams)) {
            $cStmt->bind_param($cTypes, ...$cParams);
        }
        $cStmt->execute();
        $cCounts           = $cStmt->get_result()->fetch_assoc();
        $totalContacted    = (int)($cCounts['contacted']    ?? 0);
        $totalNotContacted = (int)($cCounts['not_contacted'] ?? 0);
        $categories        = (new ProjectCategory($this->db))->getAll();

        return $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
            "current_route"       => "digital-marketing-dashboard",
            "page_title"          => "Digital Marketing Dashboard",
            "teams"               => $teams,
            "categories"          => $categories,
            "stats"               => $stats,
            "leads"               => [],   // AJAX loads leads now
            "selected_team_id"    => $teamId,
            "selected_project_category_id" => $projectCategoryId,
            "sources"             => $sources,
            "platformBudgets"     => $platformBudgets,
            "budgetSummary"       => $budgetSummary,
            "cplData"             => $cplData,
            "budget_month"        => substr($currentMonthFilter, 0, 7),
            "phoneFreq"           => [],
            "duplicateLeadsCount" => $duplicateLeadsCount,
            "totalLeads"          => $totalLeads,
            "totalContacted"      => $totalContacted,
            "totalNotContacted"   => $totalNotContacted,
            "date_age"            => $dateAge,
            "sort_order"          => $sortOrder,
            "duplicates_only"     => $duplicatesOnly,
            "from_date"           => $fromDate,
            "to_date"             => $toDate,
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
        $projectCategoryId = !empty($_GET['project_category_id']) ? (int)$_GET['project_category_id'] : null;
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        // Filters (passed to view for filter form state only)
        $dateAge             = $_GET['date_age']      ?? 'all';
        $sortOrder           = $_GET['sort']           ?? 'newest';
        $duplicatesOnly      = isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1';
        $contactStatusFilter = $_GET['contact_status'] ?? '';
        $fromDate            = $_GET['from_date']     ?? '';
        $toDate              = $_GET['to_date']       ?? '';

        // Only load summary counts (not full lead list) — AJAX will fetch leads per page
        $totalLeads    = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge, null, $fromDate, $toDate, false, $projectCategoryId, $projectId);
        $contactedCount    = 0;
        $notContactedCount = 0;

        // Quick counts for the summary cards via a lightweight query
        $cSql = "
            SELECT
                SUM(CASE
                    WHEN EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                      OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                      OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                      OR (l.call_status IN ('called', 'received'))
                    THEN 1 ELSE 0
                END) AS contacted,
                SUM(CASE
                    WHEN NOT (
                        EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                        OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                        OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                        OR (l.call_status IN ('called', 'received'))
                    ) THEN 1 ELSE 0
                END) AS not_contacted
            FROM leads l
            WHERE l.deleted_at IS NULL
              AND l.uploaded_from = 'From Campaign'
        ";

        $cParams = [];
        $cTypes  = "";

        if ($teamId) {
            $cSql .= " AND l.team_id = ?";
            $cParams[] = $teamId;
            $cTypes   .= "i";
        }

        if ($projectCategoryId) {
            $cSql .= " AND l.project_category_id = ?";
            $cParams[] = $projectCategoryId;
            $cTypes   .= "i";
        }

        if ($projectId) {
            $cSql .= " AND l.project_id = ?";
            $cParams[] = $projectId;
            $cTypes   .= "i";
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $cSql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $cParams[] = $fromDate;
            $cParams[] = $toDate;
            $cTypes   .= "ss";
        } elseif (!empty($fromDate)) {
            $cSql .= " AND DATE(l.created_at) >= ?";
            $cParams[] = $fromDate;
            $cTypes   .= "s";
        } elseif (!empty($toDate)) {
            $cSql .= " AND DATE(l.created_at) <= ?";
            $cParams[] = $toDate;
            $cTypes   .= "s";
        } elseif ($dateAge === 'today') {
            $cSql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateAge === 'old') {
            $cSql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateAge === '7days') {
            $cSql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($dateAge === '15days') {
            $cSql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
        }

        $stmt = $this->db->prepare($cSql);
        if (!empty($cParams)) {
            $stmt->bind_param($cTypes, ...$cParams);
        }
        $stmt->execute();
        $counts = $stmt->get_result()->fetch_assoc();
        $contactedCount    = (int)($counts['contacted']     ?? 0);
        $notContactedCount = (int)($counts['not_contacted'] ?? 0);

        // Duplicate count — same phone_number OR same email = duplicate
        $dupStmt = $this->db->query("
            SELECT COUNT(DISTINCT id) AS total FROM leads
            WHERE deleted_at IS NULL
              AND uploaded_from = 'From Campaign'
              AND (
                  (phone_number IS NOT NULL AND phone_number != '' AND phone_number IN (
                      SELECT phone_number FROM leads
                      WHERE deleted_at IS NULL AND phone_number IS NOT NULL AND phone_number != ''
                        AND uploaded_from = 'From Campaign'
                      GROUP BY phone_number HAVING COUNT(*) > 1
                  ))
                  OR
                  (email IS NOT NULL AND email != '' AND email IN (
                      SELECT email FROM leads
                      WHERE deleted_at IS NULL AND email IS NOT NULL AND email != ''
                        AND uploaded_from = 'From Campaign'
                      GROUP BY email HAVING COUNT(*) > 1
                  ))
              )
        ");
        $duplicateLeadsCount = $dupStmt ? (int)($dupStmt->fetch_assoc()['total'] ?? 0) : 0;
        $categories = (new ProjectCategory($this->db))->getAll();
        $projects   = (new Project($this->db))->getAll();

        return $this->view("digital-marketing-team/digital-marketing-executive", [
            "current_route"        => "digital-marketing-executive-dashboard",
            "page_title"           => "Digital Marketing Executive Dashboard",
            "teams"                => $teams,
            "categories"           => $categories,
            "projects"             => $projects,
            "stats"                => $stats,
            "leads"                => [], // AJAX loads leads now
            "selected_team_id"     => $teamId,
            "selected_project_category_id" => $projectCategoryId,
            "selected_project_id"  => $projectId,
            "phoneFreq"            => [],
            "duplicateLeadsCount"  => $duplicateLeadsCount,
            "date_age"             => $dateAge,
            "sort_order"           => $sortOrder,
            "duplicates_only"      => $duplicatesOnly,
            "contact_status_filter"=> $contactStatusFilter,
            "from_date"            => $fromDate,
            "to_date"              => $toDate,
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
        $projectCategoryId = !empty($_GET['project_category_id']) ? (int)$_GET['project_category_id'] : null;
        $projectId      = !empty($_GET['project_id'])     ? (int)$_GET['project_id'] : null;
        $dateAge        = $_GET['date_age']               ?? 'all';
        $sortOrder      = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';
        $contactStatus  = $_GET['contact_status']         ?? '';
        $duplicatesOnly = !empty($_GET['duplicates_only']);
        $search         = trim($_GET['search'] ?? '');
        $search         = $search === '' ? null : $search;
        $fromDate       = $_GET['from_date']              ?? '';
        $toDate         = $_GET['to_date']                ?? '';

        // Total count — include duplicatesOnly for correct pagination total
        $total = $this->leadModel->countMarketingLeadsFlexible($teamId, $dateAge, $search, $fromDate, $toDate, $duplicatesOnly, $projectCategoryId, $projectId);

        // Fetch paginated leads — duplicate filter applied at DB level
        $leads = $this->leadModel->getMarketingLeadsPaginated(
            $teamId, $dateAge, $sortOrder, $perPage, $offset, $search, $fromDate, $toDate, $duplicatesOnly, $projectCategoryId, $projectId
        );

        // ── Duplicate detection: phone_number OR email appears > 1 time in leads table ──
        $duplicateLeadIds = []; // lead IDs that are duplicates
        if (!empty($leads)) {
            $phones = array_filter(array_unique(array_column($leads, 'phone_number')));
            $emails = array_filter(array_unique(array_column($leads, 'email')));

            // Phones that appear > 1 in DB
            $dupPhones = [];
            if (!empty($phones)) {
                $in   = implode(',', array_fill(0, count($phones), '?'));
                $typs = str_repeat('s', count($phones));
                $st   = $this->db->prepare(
                    "SELECT phone_number FROM leads
                     WHERE phone_number IN ($in) AND deleted_at IS NULL
                     GROUP BY phone_number HAVING COUNT(*) > 1"
                );
                if ($st) {
                    $st->bind_param($typs, ...array_values($phones));
                    $st->execute();
                    $r = $st->get_result();
                    while ($row = $r->fetch_assoc()) $dupPhones[$row['phone_number']] = true;
                }
            }

            // Emails that appear > 1 in DB
            $dupEmails = [];
            if (!empty($emails)) {
                $in   = implode(',', array_fill(0, count($emails), '?'));
                $typs = str_repeat('s', count($emails));
                $st   = $this->db->prepare(
                    "SELECT email FROM leads
                     WHERE email IN ($in) AND deleted_at IS NULL
                     GROUP BY email HAVING COUNT(*) > 1"
                );
                if ($st) {
                    $st->bind_param($typs, ...array_values($emails));
                    $st->execute();
                    $r = $st->get_result();
                    while ($row = $r->fetch_assoc()) $dupEmails[$row['email']] = true;
                }
            }

            // Mark which leads on current page are duplicates
            foreach ($leads as $l) {
                $lp = trim($l['phone_number'] ?? '');
                $le = trim($l['email'] ?? '');
                if ((!empty($lp) && isset($dupPhones[$lp])) || (!empty($le) && isset($dupEmails[$le]))) {
                    $duplicateLeadIds[$l['id']] = true;
                }
            }
        }

        // Post-filter: contact_status only
        if ($contactStatus === 'contacted') {
            $leads = array_values(array_filter($leads, fn($l) => ($l['contact_status'] ?? '') === 'Contacted'));
        } elseif ($contactStatus === 'not_contacted') {
            $leads = array_values(array_filter($leads, fn($l) => ($l['contact_status'] ?? '') !== 'Contacted'));
        }

        // Note: duplicates_only is now handled at DB level (above), no post-filter needed

        // Build HTML rows
        $rows = [];
        foreach ($leads as $lead) {
            $p           = trim($lead['phone_number'] ?? '');
            $isDuplicate = isset($duplicateLeadIds[$lead['id']]);

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

            // Assigned To cell — show name + duplicate badge if applicable
            $assignedName = htmlspecialchars($lead['assigned_user_name'] ?? '');
            if (!empty($assignedName)) {
                $dupBadge = $isDuplicate
                    ? ' <span class="badge bg-danger ms-1" style="font-size:.65rem;">Duplicate</span>'
                    : '';
                $assignedToHtml = '<div class="d-flex align-items-center gap-1 flex-wrap">'
                    . '<span class="fw-semibold text-dark" style="font-size:.82rem;">' . $assignedName . '</span>'
                    . $dupBadge
                    . '</div>';
            } else {
                $notAssignedDup = $isDuplicate
                    ? '<span class="badge bg-danger ms-1" style="font-size:.65rem;">Duplicate</span>'
                    : '';
                $assignedToHtml = '<span class="text-muted" style="font-size:.78rem;">Not Assigned</span>' . $notAssignedDup;
            }

            $campProj = '';
            if (!empty($lead['project_category_name'])) {
                $campProj .= $lead['project_category_name'];
            }
            if (!empty($lead['campaign_name'])) {
                $campProj .= ($campProj !== '' ? ' - ' : '') . $lead['campaign_name'];
            }
            if ($campProj === '') {
                $campProj = 'N/A';
            }

            $rawJt = !empty($lead['job_title']) ? $lead['job_title'] : (!empty($lead['designation']) ? $lead['designation'] : '');
            $rawCo = !empty($lead['company_name']) ? $lead['company_name'] : (!empty($lead['company']) ? $lead['company'] : '');

            // Clean Mojibake / garbled symbols
            $cleanText = function($text) {
                if (empty($text)) return '';
                if (strpos($text, 'â') !== false || strpos($text, 'Ã') !== false) {
                    $conv = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
                    if (!empty($conv) && mb_check_encoding($conv, 'UTF-8')) {
                        $text = $conv;
                    }
                }
                $text = preg_replace('/[\x{2190}-\x{21FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F300}-\x{1F9FF}â‡¨â™¡â™¥â–’â–“â˜☆â†→â™ªâ™©â™¬♡♥▒▓█]+/u', ' ', $text);
                return trim(preg_replace('/\s+/', ' ', $text));
            };

            $jt = $cleanText($rawJt);
            $co = $cleanText($rawCo);

            $jobCompanyStr = '';
            if ($jt !== '' || $co !== '') {
                $jobCompanyStr = '<div class="text-muted" style="font-size: .75rem;"><i class="bi bi-briefcase me-1"></i>' . htmlspecialchars($jt);
                if ($co !== '') {
                    $jobCompanyStr .= ($jt !== '' ? ' at ' : '') . '<strong>' . htmlspecialchars($co) . '</strong>';
                }
                $jobCompanyStr .= '</div>';
            }
            $parts = [];
            
            if (!empty($lead['address'])) {
                $parts[] = htmlspecialchars($lead['address']);
            }
            if (!empty($lead['city'])) {
                $parts[] = htmlspecialchars($lead['city']);
            }
            if (!empty($lead['country'])) {
                $parts[] = htmlspecialchars($lead['country']);
            }
            $countryAddressStr = !empty($parts) ? '<i class="bi bi-geo-alt me-1 text-danger"></i>' . implode(', ', $parts) : '—';

            $rawBestTime = $lead['when_is_the_best_time_to_contact_you'] ?? '';
            $bestTimeFormatted = !empty($rawBestTime) ? htmlspecialchars(str_replace('_', ' ', $rawBestTime)) : '—';

            $rows[] = [
                'id'            => $lead['id'],
                'checkbox'      => '<input type="checkbox" class="rowCheckbox" value="' . $lead['id'] . '">',
                'name'          => '<div class="fw-bold text-dark">' . htmlspecialchars($lead['full_name'] ?? 'N/A') . '</div>' . $jobCompanyStr,
                'best_time_to_contact' => '<span class="text-muted small">' . $bestTimeFormatted . '</span>',
                'contact'       => '<div class="d-flex flex-column"><span><i class="bi bi-telephone text-muted me-2"></i>' . $phoneHtml . '</span><small class="text-muted"><i class="bi bi-envelope me-2"></i>' . htmlspecialchars($lead['email'] ?? 'N/A') . '</small></div>',
                'country_address' => '<div class="text-muted" style="font-size: .8rem;">' . $countryAddressStr . '</div>',
                'follow_up'     => $fuBadge,
                'contact_status'=> $csBadge,
                'lead_type'     => $ltBadge,
                'platform'      => '<span class="badge bg-info-soft text-info mb-1 d-inline-block">' . htmlspecialchars($lead['platform'] ?? 'N/A') . '</span><div class="small text-muted">' . htmlspecialchars($campProj) . '</div>',
                'team'          => htmlspecialchars($lead['team_name'] ?? 'N/A'),
                'sales_lead'    => '<div class="fw-bold text-primary">' . htmlspecialchars($lead['sales_team_lead_name'] ?? 'Not Assigned') . '</div>',
                'assigned_to'   => $assignedToHtml,
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
            $this->sendDeleteResponse(false, "Invalid Request", null);
        }

        $id           = (int)($_POST['id'] ?? 0);
        $redirectPage = max(1, (int)($_POST['redirect_page'] ?? 1));
        $isAjax       = !empty($_POST['ajax']);

        if ($id <= 0) {
            $this->sendDeleteResponse($isAjax, "Invalid Lead ID", null, $redirectPage);
        }

        try {
            $ok = $this->leadModel->permanentDelete($id);
            if ($ok) {
                $_SESSION['success'] = "Lead permanently deleted successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong while deleting.";
            }
        } catch (\Throwable $th) {
            $_SESSION['error'] = "Error: " . $th->getMessage();
        }

        $this->sendDeleteResponse($isAjax, null, "Lead deleted successfully!", $redirectPage);
    }

    public function deletePermanentMultiple()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Invalid Request";
            header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard");
            exit;
        }

        $ids          = $_POST['ids'] ?? [];
        $redirectPage = max(1, (int)($_POST['redirect_page'] ?? 1));
        $isAjax       = !empty($_POST['ajax']);

        if (empty($ids)) {
            $_SESSION['error'] = "No leads selected";
            $this->sendDeleteResponse($isAjax, "No leads selected", null, $redirectPage);
        }

        try {
            foreach ($ids as $id) {
                $this->leadModel->permanentDelete((int)$id);
            }
            $_SESSION['success'] = "Selected leads deleted successfully!";
        } catch (\Throwable $th) {
            $_SESSION['error'] = "Error: " . $th->getMessage();
        }

        $this->sendDeleteResponse($isAjax, null, "Leads deleted!", $redirectPage);
    }

    private function sendDeleteResponse(bool $isAjax, ?string $error, ?string $success, int $page = 1): void
    {
        if ($isAjax) {
            header('Content-Type: application/json');
            if ($error) {
                echo json_encode(['ok' => false, 'message' => $error]);
            } else {
                echo json_encode(['ok' => true, 'message' => $success]);
            }
            exit;
        }
        // Regular redirect — preserve page
        $query = $page > 1 ? '?page=' . $page : '';
        header("Location: {$GLOBALS['base_url']}/digital-marketing-executive-dashboard{$query}");
        exit;
    }


    // =========================================================================
    // External API Integration (Brochure Leads & Forms)
    // =========================================================================

    public function brochureLeads()
    {
        global $config;

        $projects = [];
        // Removed 'WHERE deleted_at IS NULL' so historical/deleted properties can still be resolved by name.
        $stmt = $this->db->query("SELECT id, name FROM projects");
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $projects[$row['id']] = $row['name'];
            }
        }

        return $this->view("digital-marketing-team/brochure-leads", [
            "current_route" => "brochure-leads",
            "page_title"    => "Brochure Leads",
            "api_url"       => $config['premium_homes_api_url'] ?? '',
            "api_key"       => $config['premium_homes_api_key'] ?? '',
            "projects"      => $projects,
        ]);
    }

    public function forms()
    {
        global $config;
        return $this->view("digital-marketing-team/forms", [
            "current_route" => "forms",
            "page_title"    => "TPHL Form Leads",
            "api_url"       => $config['premium_homes_api_url'] ?? '',
            "api_key"       => $config['premium_homes_api_key'] ?? '',
        ]);
    }

    public function formResponses($id)
    {
        global $config;
        return $this->view("digital-marketing-team/form-responses", [
            "current_route" => "form-responses",
            "page_title"    => "Form Responses",
            "api_url"       => $config['premium_homes_api_url'] ?? '',
            "api_key"       => $config['premium_homes_api_key'] ?? '',
            "form_id"       => (int)$id,
        ]);
    }

    public function freshsalesLeadsPage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }

        $platform = $_GET['platform'] ?? '';
        $campaign = $_GET['campaign'] ?? '';
        $status = $_GET['status'] ?? '0'; // default Pending (is_exported = 0)
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';

        $sql = "SELECT * FROM freshsales_leads_cache WHERE 1=1";
        $params = [];
        $types = "";

        if ($platform !== '') {
            $sql .= " AND platform = ?";
            $params[] = $platform;
            $types .= "s";
        }
        if ($campaign !== '') {
            $sql .= " AND campaign_name = ?";
            $params[] = $campaign;
            $types .= "s";
        }
        if ($status !== 'all') {
            $sql .= " AND is_exported = ?";
            $params[] = (int)$status;
            $types .= "i";
        }
        if (!empty($fromDate) && !empty($toDate)) {
            $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $types .= "ss";
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        
        $leads = [];
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $platformsResult = $this->db->query("SELECT DISTINCT platform FROM freshsales_leads_cache WHERE platform IS NOT NULL AND platform != ''");
        $platforms = [];
        if ($platformsResult) {
            while ($row = $platformsResult->fetch_assoc()) {
                $platforms[] = $row['platform'];
            }
        }

        $campaignsResult = $this->db->query("SELECT DISTINCT campaign_name FROM freshsales_leads_cache WHERE campaign_name IS NOT NULL AND campaign_name != '' ORDER BY campaign_name ASC");
        $campaigns = [];
        if ($campaignsResult) {
            while ($row = $campaignsResult->fetch_assoc()) {
                $campaigns[] = $row['campaign_name'];
            }
        }

        return $this->view("digital-marketing-team/freshsales-leads", [
            "current_route" => "freshsales-leads",
            "page_title"    => "Freshsales API Leads",
            "leads"         => $leads,
            "platforms"     => $platforms,
            "campaigns"     => $campaigns,
            "filter"        => [
                "platform"  => $platform,
                "campaign"  => $campaign,
                "status"    => $status,
                "from_date" => $fromDate,
                "to_date"   => $toDate
            ]
        ]);
    }
}