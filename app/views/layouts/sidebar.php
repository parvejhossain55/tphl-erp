<!-- app-sidebar-start -->
<!--<pre>
<php print_r($_SESSION['user']['permissions']); ?>
</pre> -->

<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header">
        <a href="?home" class="header-logo">
            <img class="main-logo" src="<?= getImage('settings', getSetting('white_logo')) ?>" alt="logo">
            <img class="dark-logo" src="<?= getImage('settings', getSetting('dark_logo')) ?>" alt="logo">
        </a>
    </div>

    <?php
    // Only hide create/edit/delete UI for truly view-only construction roles
    // Roles 3 (Director) and 22 (PM Head) are APPROVERS — they need to see Action columns
    $conReadOnlyRoles = []; // Currently no role is fully restricted — footer.php handles role 20
    
    // Check if the current user belongs to Management (Admin or Super Admin or Chief Officer)
    $isManagement = in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33]);

    // Check if the current user belongs to Management (Admin or Super Admin)
    $isManagement = in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33]);

    // Calculate pending verifications
    $pendingVerificationsCount = 0;
    global $db;
    if (isset($db)) {
        $res = $db->query("
            SELECT COUNT(DISTINCT l.id) AS total 
            FROM lead_payments lp 
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            WHERE lp.verification_status = 'pending'
        ");
        if ($res && $row = $res->fetch_assoc()) {
            $pendingVerificationsCount = (int) $row['total'];
        }
    }

    // Calculate pending system approval requests count for Management & Accounts badge
    $pendingApprovalCount = 0;
    if (isset($db)) {
        $resApp = $db->query("SELECT COUNT(*) AS total FROM approval_requests WHERE status = 'pending'");
        if ($resApp) {
            $pendingApprovalCount = (int) ($resApp->fetch_assoc()['total'] ?? 0);
        }
    }

    // Calculate pending / incomplete sales incentive claims count for Accounts badge
    $pendingIncentiveClaimsCount = 0;
    if (isset($db)) {
        $resInc = $db->query("SELECT COUNT(*) AS total FROM project_incentive_claims WHERE claim_status IN ('claimed', 'approved')");
        if ($resInc) {
            $pendingIncentiveClaimsCount = (int) ($resInc->fetch_assoc()['total'] ?? 0);
        }
    }

    // Calculate pending contractor bills for the current user's role
    $conPendingBillsCount = 0;
    if (isset($db)) {
        $ur = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleNameLower = strtolower($_SESSION['user']['role_name'] ?? '');
        $qcRoles = [1, 24, 25];
        $mgmtRoles = [1, 3, 33, 22, 20, 32];
        $isQc = in_array($ur, $qcRoles) || str_contains($roleNameLower, 'qc') || str_contains($roleNameLower, 'audit') || hasPermission('QC & Internal Audit');
        $isMgmt = in_array($ur, $mgmtRoles) || str_contains($roleNameLower, 'operation') || str_contains($roleNameLower, 'operator') || str_contains($roleNameLower, 'management') || str_contains($roleNameLower, 'admin') || str_contains($roleNameLower, 'pm head');
        
        if ($isQc || $isMgmt) {
            $statusConditions = [];
            if ($isQc) {
                $statusConditions[] = "status IN ('submitted', 'verified')";
            }
            if ($isMgmt) {
                $statusConditions[] = "status IN ('submitted', 'verified', 'qc_approved')";
            }
            
            $condStr = implode(" OR ", $statusConditions);
            $cbbStmt = $db->query("SELECT COUNT(id) as cnt FROM contractor_bill_requests WHERE ($condStr) AND status != 'rejected'");
            if ($cbbStmt) {
                $conPendingBillsCount = (int)$cbbStmt->fetch_assoc()['cnt'];
            }
        }
    }

    // Calculate pending tasks count for logged in user
    $pendingTasksCount = 0;
    if (isset($db) && isset($_SESSION['user']['id'])) {
        $loggedUserId = (int) $_SESSION['user']['id'];
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM tasks WHERE user_id = ? AND progress < 100 AND status != 'completed'");
        if ($stmt) {
            $stmt->bind_param("i", $loggedUserId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                $pendingTasksCount = (int) $res->fetch_assoc()['total'];
            }
            $stmt->close();
        }

        // Calculate pending Fund Requisition action counts for logged-in user
        $pendingFundReqDeptCount = 0;
        $pendingFundReqMgmtCount = 0;
        $pendingFundReqAccountsCount = 0;
        $pendingFundReqTotalCount = 0;

        $loggedUserId = (int) $_SESSION['user']['id'];
        $loggedRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);

        // 1. Dept Head Pending Count (waiting for this user's approval or Admin)
        $resDept = $db->query("
            SELECT COUNT(DISTINCT fr.id) AS total 
            FROM fund_requisitions fr
            LEFT JOIN users u ON fr.requested_by = u.id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            WHERE fr.status = 'pending_dept' 
              AND (e.reporting_user_id = $loggedUserId OR $loggedRoleId IN (1, 3))
        ");
        if ($resDept) {
            $pendingFundReqDeptCount = (int) ($resDept->fetch_assoc()['total'] ?? 0);
        }

        // 2. Management Pending Count (Management roles 1, 3, 20)
        if (in_array($loggedRoleId, [1, 3, 33, 20, 32])) {
            $resMgmt = $db->query("SELECT COUNT(*) AS total FROM fund_requisitions WHERE status = 'pending'");
            if ($resMgmt) {
                $pendingFundReqMgmtCount = (int) ($resMgmt->fetch_assoc()['total'] ?? 0);
            }
        }

        // 3. Accounts Pending Pay Count (Accounts roles 1, 6)
        if (in_array($loggedRoleId, [1, 6])) {
            $resAccts = $db->query("SELECT COUNT(*) AS total FROM fund_requisitions WHERE status = 'approved'");
            if ($resAccts) {
                $pendingFundReqAccountsCount = (int) ($resAccts->fetch_assoc()['total'] ?? 0);
            }
        }

        $pendingFundReqTotalCount = $pendingFundReqDeptCount + $pendingFundReqMgmtCount + $pendingFundReqAccountsCount;
    }

    // Calculate pending Purchase Requests & Purchase Orders count for sidebar red badges
    $sidebarPendingPRCount = 0;
    $sidebarPendingPOCount = 0;
    $sidebarPendingPOCostsCount = 0;
    if (isset($db) && isset($_SESSION['user']['id'])) {
        $sidebarUserId = (int) $_SESSION['user']['id'];
        $sidebarRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);

        try {
            // Pending Project Other Costs awaiting action
            $pocStatus = null;
            if (in_array($sidebarRoleId, [13, 24, 25, 1])) $pocStatus = 'pending';
            elseif (in_array($sidebarRoleId, [3, 15, 20, 22, 32, 33, 1])) $pocStatus = 'qc_approved';
            elseif (in_array($sidebarRoleId, [6, 30, 1])) $pocStatus = 'management_approved';
            
            if ($pocStatus) {
                $pocRes = $db->prepare("SELECT COUNT(*) AS c FROM project_other_costs WHERE status = ?");
                $pocRes->bind_param("s", $pocStatus);
                $pocRes->execute();
                $sidebarPendingPOCostsCount += $pocRes->get_result()->fetch_assoc()['c'] ?? 0;
            }
            // 1. Pending Purchase Requests awaiting role-specific action
            if (in_array($sidebarRoleId, [1, 3])) {
                // Admin / Super Admin: Submitted PRs awaiting approval OR Approved PRs awaiting executive assignment
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND (
                        pr.status = 'Submitted' 
                        OR (pr.status = 'Approved' AND (pr.assigned_executive_id IS NULL OR pr.assigned_executive_id = 0))
                    )
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } elseif (in_array($sidebarRoleId, [17, 31])) {
                // Purchase Head & Senior Purchase Executive: Approved PRs awaiting executive assignment OR assigned to them
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND (
                        (pr.status = 'Approved' AND (pr.assigned_executive_id IS NULL OR pr.assigned_executive_id = 0))
                        OR (pr.status = 'Approved' AND pr.assigned_executive_id = {$sidebarUserId})
                    )
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } elseif ($sidebarRoleId === 23) {
                // Purchase Executive: Approved PRs assigned to them for quotation / CST
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND pr.status = 'Approved' AND pr.assigned_executive_id = {$sidebarUserId}
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } elseif (in_array($sidebarRoleId, [20, 32])) {
                // Operation Head: Stage 2 approval (Submitted & approved by PM)
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND pr.status = 'Submitted' AND pr.approved_by_id IS NOT NULL AND pr.qc_approved_by_id IS NULL
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } elseif ($sidebarRoleId === 22) {
                // Project Manager Head: Stage 1 approval for all projects
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND pr.status = 'Submitted' AND pr.approved_by_id IS NULL
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } elseif ($sidebarRoleId === 15) {
                // Project Manager (assigned projects): Stage 1 approval
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND pr.status = 'Submitted' AND pr.approved_by_id IS NULL AND p.pm_id = {$sidebarUserId}
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            } else {
                // Creator / Other roles: PRs created by them in Submitted/Draft status
                $prRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_requests pr 
                    JOIN projects p ON p.id = pr.project_id 
                    WHERE p.deleted_at IS NULL AND pr.created_by = {$sidebarUserId} AND pr.status IN ('Draft', 'Submitted')
                ");
                $sidebarPendingPRCount = $prRes ? (int) $prRes->fetch_assoc()['c'] : 0;
            }

            // 2. Pending Purchase Orders awaiting approval / receiving action
            if ($sidebarRoleId === 17) { // Purchase Head
                $poRes = $db->query("
                    SELECT COUNT(po.id) AS c 
                    FROM purchase_orders po
                    LEFT JOIN users u ON u.id = po.created_by
                    WHERE po.purchase_head_approved_by_id IS NULL 
                      AND (po.status != 'Cancelled' OR po.status IS NULL)
                      AND u.role_id IN (23, 31)
                ");
                $sidebarPendingPOCount = $poRes ? (int) $poRes->fetch_assoc()['c'] : 0;
            } elseif ($sidebarRoleId === 24) { // QC User
                $poRes = $db->query("
                    SELECT COUNT(po.id) AS c 
                    FROM purchase_orders po
                    LEFT JOIN users u ON u.id = po.created_by
                    WHERE po.qc_approved_by_id IS NULL 
                      AND (po.status != 'Cancelled' OR po.status IS NULL)
                      AND (u.role_id NOT IN (23, 31) OR po.purchase_head_approved_by_id IS NOT NULL)
                ");
                $sidebarPendingPOCount = $poRes ? (int) $poRes->fetch_assoc()['c'] : 0;
            } elseif ($sidebarRoleId === 20) { // Operation Head
                $poRes = $db->query("
                    SELECT COUNT(po.id) AS c 
                    FROM purchase_orders po
                    WHERE po.op_head_approved_by_id IS NULL 
                      AND po.qc_approved_by_id IS NOT NULL
                      AND (po.status != 'Cancelled' OR po.status IS NULL)
                ");
                $sidebarPendingPOCount = $poRes ? (int) $poRes->fetch_assoc()['c'] : 0;
            } else { // Admin, Super Admin, PMs, Site Engineers, Executives
                $poRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM purchase_orders 
                    WHERE status IN ('Issued', 'Partially Received', 'Pending')
                ");
                $sidebarPendingPOCount = $poRes ? (int) $poRes->fetch_assoc()['c'] : 0;
            }

            // 3. Pending Vendor Payment Requests awaiting action based on user's role
            $sidebarPendingVprCount = 0;
            $sbRoleName = strtolower($_SESSION['user']['role_name'] ?? '');
            $isQcRole = in_array($sidebarRoleId, [24, 25]) || strpos($sbRoleName, 'qc') !== false;
            $isMgmtRole = in_array($sidebarRoleId, [1, 3, 33, 20, 32]) || strpos($sbRoleName, 'admin') !== false || strpos($sbRoleName, 'management') !== false || strpos($sbRoleName, 'operation') !== false;
            $isAccountsRole = in_array($sidebarRoleId, [6, 30]) || strpos($sbRoleName, 'accounts') !== false;

            if ($isQcRole && !$isMgmtRole) {
                // QC: Awaiting QC Verification (Submitted)
                $vprRes = $db->query("SELECT COUNT(*) AS c FROM vendor_payment_requests WHERE status = 'Submitted'");
                $sidebarPendingVprCount = $vprRes ? (int)$vprRes->fetch_assoc()['c'] : 0;
            } elseif ($isAccountsRole && !$isMgmtRole) {
                // Accounts: Awaiting Disbursal (Approved / Partially Paid with due amount > 0)
                $vprRes = $db->query("
                    SELECT COUNT(*) AS c 
                    FROM vendor_payment_requests vpr
                    WHERE vpr.status IN ('Approved', 'Partially_Paid')
                      AND (
                          vpr.due_amount > 0 
                          OR (vpr.due_amount IS NULL AND vpr.net_payable > COALESCE((SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = vpr.id AND cft.ref_type = 'vendor_payment'), 0))
                      )
                ");
                $sidebarPendingVprCount = $vprRes ? (int)$vprRes->fetch_assoc()['c'] : 0;
            } elseif ($isMgmtRole) {
                // Management / Operation Head / Admin: Awaiting Management Decision (QC Approved or Submitted)
                $vprRes = $db->query("SELECT COUNT(*) AS c FROM vendor_payment_requests WHERE status IN ('QC Approved', 'Submitted')");
                $sidebarPendingVprCount = $vprRes ? (int)$vprRes->fetch_assoc()['c'] : 0;
            }
        } catch (\Throwable $e) {
        }
    }
    ?>


    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu">
                <?php if (hasPermission("role & permission") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <!-- Home -->
                    <li class="slide <?= isActive('home', $current_route) ?>">
                        <a href="<?= $base_url ?>/home" class="sidebar__menu-item <?= isActive('home', $current_route) ?>">
                            <div class="side-menu__icon"><i class="icon-house"></i></div>
                            <span class="sidebar__menu-label">Home</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isManagement || in_array($_SESSION['user']['role_id'] ?? 0, [6, 20])): ?>
                    <!-- All Approval Requests for Management & Accounts -->
                    <li class="slide <?= isActive('all-approval-requests', $current_route) ?>">
                        <a href="<?= $base_url ?>/all-approval-requests"
                            class="sidebar__menu-item <?= isActive('all-approval-requests', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-list-check text-primary"></i></div>
                            <span class="sidebar__menu-label">
                                All Approval Requests
                                <?php if ($pendingApprovalCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-2"><?= $pendingApprovalCount ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Check if user has ERP Tracker access
                $sidebar_user_id = $_SESSION['user']['id'] ?? 0;
                $sidebar_role_id = $_SESSION['user']['role_id'] ?? 0;
                $hasErpAccess = in_array($sidebar_role_id, [1, 3]);

                if (!$hasErpAccess) {
                    global $db;
                    $champ_check = $db->query("SELECT id FROM erp_tracker_champions WHERE user_id = $sidebar_user_id LIMIT 1");
                    if ($champ_check && $champ_check->num_rows > 0) {
                        $hasErpAccess = true;
                    }
                }
                ?>

                <?php if ($hasErpAccess): ?>
                    <!-- ERP Tracker -->
                    <li class="slide d-none <?= isActive('erp-tracker', $current_route) ?>">
                        <a href="<?= $base_url ?>/erp-tracker"
                            class="sidebar__menu-item <?= isActive('erp-tracker', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fas fa-tasks"></i></div>
                            <span class="sidebar__menu-label">ERP Tracker</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Sales Manager CATEGORY -->
                <?php
                // Ensure $db is initialized
                // $db = db(); // call your db() function that returns mysqli connection
                
                // user id
                $userId = $_SESSION['user']['id'] ?? 0;

                // call the global helper function
                 $todayCount = getFollowUpCount($userId, 'today');
                $todayAssignedCount = getTodayAssignedCount($userId);

                $upcomingCount = getFollowUpCount($userId, 'upcoming');
                ?>
                <?php if (hasPermission("sales & business") || in_array($_SESSION['user']['role_id'] ?? 0, [15, 22, 9, 33])): ?>

                    <!--Sales CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">Sales & Business</span>
                    </li>

                    <?php if (hasPermission("sales & business") || in_array($_SESSION['user']['role_id'] ?? 0, [9, 33])): ?>
                        <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                            <!-- sales Dashboard -->
                            <li class="slide ">
                                <a href="<?= $base_url ?>/sales-dashboard"
                                    class="sidebar__menu-item <?= isActive('sales-dashboard', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                    <span class="sidebar__menu-label">Sales Dashboard</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (($_SESSION['user']['role_id'] ?? 0) == 9): ?>
                            <!-- sales Team Head Dashboard -->
                            <li class="slide ">
                                <a href="<?= $base_url ?>/sales-team-head-dashboard"
                                    class="sidebar__menu-item <?= isActive('sales-team-head-dashboard', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                    <span class="sidebar__menu-label">Overall Sales Dashboard</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!$isManagement && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                            <!-- Location -->
                            <li class="slide ">
                                <a href="<?= $base_url ?>/location-lists"
                                    class="sidebar__menu-item <?= isActive('location-lists', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-location-dot"></i></div>
                                    <span class="sidebar__menu-label">Location Manage</span>
                                </a>
                            </li>

                            <!-- Amenities -->
                            <li class="slide ">
                                <a href="<?= $base_url ?>/amenities"
                                    class="sidebar__menu-item <?= isActive(['amenities', 'create-amenity', 'edit-amenity'], $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-spa"></i></div>
                                    <span class="sidebar__menu-label">Amenities Manage</span>
                                </a>
                            </li>

                            <!-- Community -->
                            <li class="slide">
                                <a href="<?= $base_url ?>/community-lists"
                                    class="sidebar__menu-item  <?= isActive('community-lists', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-people-group"></i></div>
                                    <span class="sidebar__menu-label">Community Lists</span>
                                </a>
                            </li>

                        <?php endif; ?>
                        <!-- Lead & Booking -->
                        <li
                            class="slide has-sub <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients', 'client-refunds'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients', 'client-refunds'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                                <span class="sidebar__menu-label">Lead & Booking</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <?php if (!$isManagement && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/create-lead"
                                            class="sidebar__menu-item <?= isActive('create-lead', $current_route) ?>">Add Lead</a>
                                    </li>
                                <?php endif; ?>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/lead-lists"
                                        class="sidebar__menu-item <?= isActive('lead-lists', $current_route) ?>">Lead List</a>
                                </li>
                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/my-all-assigned-leads"
                                            class="sidebar__menu-item <?= isActive('my-all-assigned-leads', $current_route) ?>">My
                                            All Assigned Leads </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/today-leads"
                                            class="sidebar__menu-item <?= isActive('today-leads', $current_route) ?>">
                                            Today Leads Assigned <?php if ($todayAssignedCount > 0): ?><span class="badge bg-primary ms-2"><?= $todayAssignedCount ?></span><?php endif; ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/today-follow-up"
                                            class="sidebar__menu-item <?= isActive('today-follow-up', $current_route) ?>">Today
                                            Follow Up <span class="badge bg-primary ms-2"><?= $todayCount ?></span> </a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/my-all-assigned-leads?filter=missing_followup"
                                            class="sidebar__menu-item <?= ($current_route === 'my-all-assigned-leads' && isset($_GET['filter']) && $_GET['filter'] === 'missing_followup') ? 'active' : '' ?>">
                                            Missing Follow-Ups
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/upcoming-follow-up"
                                            class="sidebar__menu-item <?= isActive('overdue-next-followup', $current_route) ?>">
                                            Upcoming Follow Up<span class="ms-2 badge bg-warning"><?= $upcomingCount ?></span></a>
                                    </li>
                                <?php endif; ?>

                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/my-onboarding-projects"
                                            class="sidebar__menu-item <?= isActive('my-onboarding-projects', $current_route) ?>">My
                                            Onboarding Projects </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!$isManagement): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/onboarding-clients"
                                            class="sidebar__menu-item <?= isActive('onboarding-clients', $current_route) ?>">All
                                            Onboarding Clients</a>
                                    </li>
                                <?php endif; ?>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/client-refunds"
                                        class="sidebar__menu-item <?= isActive('client-refunds', $current_route) ?>">Client
                                        Refunds</a>
                                </li>
                            </ul>
                        </li>

                        <!-- Incentive Module Menu Links -->
                        <li class="slide d-none">
                            <a href="<?= $base_url ?>/my-incentives"
                                class="sidebar__menu-item <?= isActive('my-incentives', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                                <span class="sidebar__menu-label">My Incentives</span>
                            </a>
                        </li>
                        <li class="slide d-none">
                            <a href="<?= $base_url ?>/incentive-management"
                                class="sidebar__menu-item <?= isActive('incentive-management', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-coins"></i></div>
                                <span class="sidebar__menu-label">Sales Incentive Accounts</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Project Manage -->
                    <li
                        class="slide has-sub <?= isActive(['create-project', 'project-lists', 'project-category-lists', 'create-project-category'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['create-project', 'project-lists', 'project-category-lists', 'create-project-category'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-building"></i></div>
                            <span class="sidebar__menu-label">Project Manage</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <?php if (!$isManagement): ?>
                                <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/project-category-lists"
                                            class="sidebar__menu-item <?= isActive(['project-category-lists', 'create-project-category'], $current_route) ?>">
                                            Project Categories
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/create-project"
                                        class="sidebar__menu-item <?= isActive('create-project', $current_route) ?>">Add
                                        Project</a>
                                </li>
                            <?php endif; ?>
                            <li class="slide">
                                <a href="<?= $base_url ?>/project-lists"
                                    class="sidebar__menu-item <?= isActive('project-lists', $current_route) ?>">Project
                                    List</a>
                            </li>
                        </ul>
                    </li>

                    <?php if ((hasPermission("sales & business") || in_array($_SESSION['user']['role_id'] ?? 0, [1, 2, 3, 5, 9, 15, 33]))): ?>

                        <!-- Sales Activity Report -->
                        <li class="slide">
                            <a href="<?= $base_url ?>/sales-activity-report"
                                class="sidebar__menu-item <?= isActive('sales-activity-report', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-pie"></i></div>
                                <span class="sidebar__menu-label">Sales Activity Report</span>
                            </a>
                        </li>

                        <!-- Incentives Module -->
                        <li
                            class="slide has-sub <?= isActive(['my-incentives', 'incentive-management', 'incentive-config', 'my-team-incentives'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['my-incentives', 'incentive-management', 'incentive-config', 'my-team-incentives'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                                <span class="sidebar__menu-label">Incentives</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide child2">
                                    <a href="<?= $base_url ?>/my-incentives"
                                        class="sidebar__menu-item <?= isActive('my-incentives', $current_route) ?>">My
                                        Incentives</a>
                                </li>
                                <!-- Visible to Management, Accounts, HR, Chairman, and Sales Heads/Managers -->
                                <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 2, 3, 4, 5, 6, 9, 33])): ?>
                                    <li class="slide child2">
                                        <a href="<?= $base_url ?>/incentive-management"
                                            class="sidebar__menu-item <?= isActive('incentive-management', $current_route) ?>">Company
                                            Incentives & Accounts</a>
                                    </li>
                                <?php endif; ?>

                                <!-- Visible to Sales Managers, Heads, Co-ordinators (5,6,9,15) and Management -->
                                <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 2, 3, 5, 6, 9, 15, 33])): ?>
                                    <li class="slide child2">
                                        <a href="<?= $base_url ?>/my-team-incentives"
                                            class="sidebar__menu-item <?= isActive('my-team-incentives', $current_route) ?>">My
                                            Team Incentives</a>
                                    </li>
                                    <li class="slide child2">
                                        <a href="<?= $base_url ?>/sales-executive-teams"
                                            class="sidebar__menu-item <?= isActive('sales-executive-teams', $current_route) ?>">My Team
                                            Targets</a>
                                    </li>
                                <?php endif; ?>

                                <!-- Settings for Management, Sales Manager and Sales Head -->
                                <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 2, 3, 5, 6, 9, 33])): ?>
                                    <li class="slide child2">
                                        <a href="<?= $base_url ?>/incentive-config"
                                            class="sidebar__menu-item <?= isActive('incentive-config', $current_route) ?>">Settings</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <!-- Flats Manage -->
                        <li
                            class="slide has-sub <?= isActive(['create-flat', 'flat-lists'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-flat', 'flat-lists'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-house"></i></div>
                                <span class="sidebar__menu-label">Flats Manage</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <?php if (!$isManagement): ?>
                                    <li class="slide child2">
                                        <a href="<?= $base_url ?>/create-flat"
                                            class="sidebar__menu-item <?= isActive('create-flat', $current_route) ?>">Add Flat</a>
                                    </li>
                                <?php endif; ?>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/flat-lists"
                                        class="sidebar__menu-item <?= isActive('flat-lists', $current_route) ?>">Flat List</a>
                                </li>
                            </ul>
                        </li>
                        <!-- Inventroy -->
                        <li class="slide ">
                            <a href="<?= $base_url ?>/inventory-lists"
                                class="sidebar__menu-item <?= isActive('inventory-lists', $current_route) ?>">
                                <div class="side-menu__icon"><i class="bi bi-stack"></i> </div>
                                <span class="sidebar__menu-label">Project Wise Flat Stock</span>
                            </a>
                        </li>

                        <?php if (!$isManagement): ?>
                            <!-- Executive Team  -->
                            <!--target set-sales-target show here for sales executiive team  -->
                            <li class="slide ">
                                <a href="<?= $base_url ?>/sales-executive-teams"
                                    class="sidebar__menu-item <?= isActive('sales-executive-teams', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                                    <span class="sidebar__menu-label">Sales Incentive Target</span>
                                </a>
                            </li>

                            <li class="slide ">
                                <a href="<?= $base_url ?>/contact-lists"
                                    class="sidebar__menu-item <?= isActive('contact-lists', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                                    <span class="sidebar__menu-label">Contact Lists</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php endif; ?>
                <!--End Sales Manager CATEGORY -->
                <?php if (hasPermission("whatsapp panel") && !$isManagement && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <!--Sales CATEGORY -->

                    <div class="card-title ms-4 fs-6 fw-semibold mb-2">Whatsapp System</div>
                    <!-- sales Dashboard -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/whatsapp-dashboard"
                            class="sidebar__menu-item <?= isActive('whatsapp-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Dashboard</span>
                        </a>
                    </li>
                    <li class="slide">
                        <a href="<?= $base_url ?>/contacts-whatsapp"
                            class="sidebar__menu-item  <?= isActive('contacts-whatsapp', $current_route) ?>">
                            <div class="side-menu__icon"><i class="bi bi-people-fill"></i></div>
                            <span class="sidebar__menu-label">Contact</span>
                        </a>
                    </li>
                    <!-- Location -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/campaign-contacts"
                            class="sidebar__menu-item <?= isActive('campaign-contacts', $current_route) ?>">
                            <div class="side-menu__icon"><i class="bi bi-megaphone-fill"></i></div>
                            <span class="sidebar__menu-label">Campaign</span>
                        </a>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/report-campaign"
                            class="sidebar__menu-item <?= isActive('report-campaign', $current_route) ?>">
                            <div class="side-menu__icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                            <span class="sidebar__menu-label">Reports</span>
                        </a>
                    </li>
                    <!-- Community -->
                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/community-lists"
                            class="sidebar__menu-item  <?= isActive('community-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-people-group"></i></div>
                            <span class="sidebar__menu-label">Device</span>
                        </a>
                    </li>


                    <!-- Executive Team  -->
                    <!--target set-sales-target show here for sales executiive team  -->
                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/sales-executive-teams"
                            class="sidebar__menu-item <?= isActive('sales-executive-teams', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label"> Team</span>
                        </a>
                    </li>


                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/contact-lists"
                            class="sidebar__menu-item <?= isActive('contact-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label">Safety Controll</span>
                        </a>
                    </li>
                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/contact-lists"
                            class="sidebar__menu-item <?= isActive('contact-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label">Notification</span>
                        </a>
                    </li>

                <?php endif; ?>

                <!-- Camera Manage -->
                <?php if (hasPermission("Camera Manage") && !$isManagement && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li
                        class="slide has-sub <?= isActive(['create-camera', 'camera-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['create-camera', 'camera-lists'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-camera"></i></div>
                            <span class="sidebar__menu-label">Camera Manage</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/create-camera"
                                    class="sidebar__menu-item <?= isActive('create-camera', $current_route) ?>">Add
                                    Camera</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/camera-lists"
                                    class="sidebar__menu-item <?= isActive('camera-lists', $current_route) ?>">Camera
                                    List</a>
                            </li>
                        </ul>
                    </li>
                <?php endif ?>





                <!-- For Sales head -->
                <?php if (hasPermission("Add User")): ?>
                    <ul class="d-none sidebar-menu child1" style="display:block;">
                        <li class="slide <?= isActive('create-user', $current_route) ?>">
                            <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>"
                                href="<?= $base_url ?>/create-user">
                                Add User
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <!--END For Sales Head -->

                <?php
                // Ensure $db is initialized
                // $db = db(); // call your db() function that returns mysqli connection
                
                // user id
                $userId = $_SESSION['user']['id'] ?? 0;

                // call the global helper function
                 $todayCount = getFollowUpCount($userId, 'today');
                $todayAssignedCount = getTodayAssignedCount($userId);

                $upcomingCount = getFollowUpCount($userId, 'upcoming');
                ?>
                <!-- for sales executive  -->
                <?php if (hasPermission("add Lead") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <ul class="sidebar-menu child2" style="display:block;">
                        <li class="slide">
                            <a href="<?= $base_url ?>/my-dashboard"
                                class="sidebar__menu-item <?= isActive('my-dashboard', $current_route) ?>">My Dashboard</a>
                        </li>
                        <?php if (isSalesCoLeader()): ?>
                            <!-- Co-Leader: Lead List Access (team_id wise) -->
                            <li class="slide">
                                <a href="<?= $base_url ?>/lead-lists"
                                    class="sidebar__menu-item <?= isActive('lead-lists', $current_route) ?>">
                                    <i class="fa-solid fa-list-check me-1"></i> Lead List
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="slide">
                            <a href="<?= $base_url ?>/my-all-assigned-leads"
                                class="sidebar__menu-item <?= isActive('my-all-assigned-leads', $current_route) ?>">My All
                                Assigned Leads </a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/today-leads"
                                class="sidebar__menu-item <?= isActive('today-leads', $current_route) ?>">
                                Today Leads Assigned <?php if ($todayAssignedCount > 0): ?><span class="badge bg-primary ms-2"><?= $todayAssignedCount ?></span><?php endif; ?></a>
                        </li>

                        <li class="slide d-none">
                            <a href="<?= $base_url ?>/task-dashboard"
                                class="sidebar__menu-item  <?= isActive('task-dashboard', $current_route) ?>">
                                <span class="sidebar__menu-label">Task Dashbaord</span>
                                <?php if ($pendingTasksCount > 0): ?>
                                    <span class="badge bg-danger ms-2 rounded-pill"
                                        style="font-size: 10px; padding: 2px 6px; line-height: 1;"><?= $pendingTasksCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>


                        <li class="slide">
                            <a href="<?= $base_url ?>/create-lead"
                                class="sidebar__menu-item <?= isActive('create-lead', $current_route) ?>">Add Lead</a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/today-follow-up"
                                class="sidebar__menu-item <?= isActive('today-follow-up', $current_route) ?>">Today Follow
                                Up <span class="badge bg-primary ms-2"><?= $todayCount ?></span> </a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/my-all-assigned-leads?filter=missing_followup"
                                class="sidebar__menu-item <?= ($current_route === 'my-all-assigned-leads' && isset($_GET['filter']) && $_GET['filter'] === 'missing_followup') ? 'active' : '' ?>">
                                Missing Follow-Ups
                            </a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/upcoming-follow-up"
                                class="sidebar__menu-item <?= isActive('overdue-next-followup', $current_route) ?>">
                                Upcoming Follow Up<span class="ms-2 badge bg-warning"><?= $upcomingCount ?></span></a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/my-onboarding-projects"
                                class="sidebar__menu-item <?= isActive('my-onboarding-projects', $current_route) ?>">My
                                Onboarding Projects </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <!-- for sales executive  -->

                <!--Start Marketing Head Dashboard  -->
                <?php if (hasPermission("Digital Marketing Panel")): ?>
                    <!--CED CATEGORY -->
                    <li class="sidebar__menu-category">

                        <span class="category-name">My Dashboard</span>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/digital-marketing-dashboard"
                            class="sidebar__menu-item <?= isActive('digital-marketing-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Marketing Head Dashboard</span>
                        </a>
                    </li>

                    <li class="slide ">
                        <a href="<?= $base_url ?>/digital-marketing-upload-report"
                            class="sidebar__menu-item <?= isActive('digital-marketing-upload-report', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-file-contract"></i></div>
                            <span class="sidebar__menu-label">Lead Upload Report</span>
                        </a>
                    </li>
                <?php endif; ?>
                <!--End Marketing Head Dashboard  -->
                <!--Start Marketing Executive Dashboard  -->
                <?php if (hasPermission("Upload CSV")): ?>
                    <!--CED CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">My Dashboard</span>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/digital-marketing-executive-dashboard"
                            class="sidebar__menu-item <?= isActive('digital-marketing-executive-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Marketing Executive Dashboard</span>
                        </a>
                    </li>

                    <li class="slide ">
                        <a href="<?= $base_url ?>/digital-marketing-upload-report"
                            class="sidebar__menu-item <?= isActive('digital-marketing-upload-report', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-file-contract"></i></div>
                            <span class="sidebar__menu-label">Lead Upload Report</span>
                        </a>
                    </li>
                    <li class="slide">
                        <a href="<?= $base_url ?>/create-lead"
                            class="sidebar__menu-item <?= isActive('create-lead', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                            <span class="sidebar__menu-label">Create Lead</span>
                        </a>
                    </li>
                    <ul class="sidebar-menu child2" style="display:block;">
                        <li class="slide">
                            <a href="<?= $base_url ?>/lead-lists"
                                class="sidebar__menu-item <?= isActive('lead-lists', $current_route) ?>">Lead List</a>
                        </li>
                        <li class="slide ">
                            <a href="<?= $base_url ?>/onboarding-clients"
                                class="sidebar__menu-item <?= isActive('onboarding-clients', $current_route) ?>">Team
                                Onboarding Clients</a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/brochure-leads"
                                class="sidebar__menu-item <?= isActive('brochure-leads', $current_route) ?>">Web Leads</a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/forms"
                                class="sidebar__menu-item <?= isActive(['forms', 'form-responses'], $current_route) ?>">TPHL
                                Form Leads</a>
                        </li>
                    </ul>
                <?php endif; ?>


                <!--End Marketing Executive Dashboard  -->
                <!-- For Sales Team Head  sales-head -->
                <?php if (hasPermission("view lead")): ?>
                    <!-- Sales Team Dashboard  -->

                    <!-- sales Dashboard -->
                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/task-dashboard"
                            class="sidebar__menu-item  <?= isActive('task-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-list-check"></i></div>
                            <span class="sidebar__menu-label">Task Dashbaord</span>
                            <?php if ($pendingTasksCount > 0): ?>
                                <span class="badge bg-danger ms-2 rounded-pill"
                                    style="font-size: 10px; padding: 2px 6px; line-height: 1;"><?= $pendingTasksCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="slide ">
                        <a href="<?= $base_url ?>/sales-team-view"
                            class="sidebar__menu-item <?= isActive('sales-team-view', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label">Sales Team View</span>
                        </a>

                    </li>


                    <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                        <li class="slide child2">
                            <a href="<?= $base_url ?>/project-lists"
                                class="sidebar__menu-item <?= isActive('project-lists', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="sidebar__menu-label">Project List</span>
                            </a>

                        </li>
                    <?php endif; ?>
                    <li class="slide child2">
                        <a href="<?= $base_url ?>/flat-lists"
                            class="sidebar__menu-item <?= isActive('flat-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Flat List</span>
                        </a>

                    </li>
                    <li class="slide child2">
                        <a href="<?= $base_url ?>/sales-report"
                            class="sidebar__menu-item <?= isActive('sales-report', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Sales Report</span>
                        </a>

                    </li>
                    <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                        <li class="slide child2">
                            <a href="<?= $base_url ?>/client-refunds"
                                class="sidebar__menu-item <?= isActive('client-refunds', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
                                <span class="sidebar__menu-label">Client Refunds</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                        <!-- Lead & Booking -->
                        <li
                            class="slide has-sub <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                                <span class="sidebar__menu-label">Lead & Booking</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/lead-lists"
                                        class="sidebar__menu-item <?= isActive('lead-lists', $current_route) ?>">Lead List</a>
                                </li>
                                <li class="slide d-none">
                                    <a href="<?= $base_url ?>/onboarding-clients"
                                        class="sidebar__menu-item <?= isActive('onboarding-clients', $current_route) ?>">Team
                                        Onboarding Clients</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- Inventroy -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/inventory-lists"
                            class="sidebar__menu-item <?= isActive('inventory-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="bi bi-stack"></i> </div>
                            <span class="sidebar__menu-label">Project Wise Inventory</span>
                        </a>
                    </li>

                <?php endif; ?>
                <!--End For Sales Team Head  -->

                <!--HRM CATEGORY -->
                <?php if (hasPermission("Hr Manage") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">HR & Payroll </span>
                    </li>

                    <!-- Sales Dashboard -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/hrm-dashboard"
                            class="sidebar__menu-item <?= isActive('hrm-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">HR Dashboard</span>
                        </a>
                    </li>
                    <li class="slide d-none">
                        <a href="<?= $base_url ?>/task-dashboard"
                            class="sidebar__menu-item  <?= isActive('task-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-list-check"></i></div>
                            <span class="sidebar__menu-label">Task Dashbaord</span>
                            <?php if ($pendingTasksCount > 0): ?>
                                <span class="badge bg-danger ms-2 rounded-pill"
                                    style="font-size: 10px; padding: 2px 6px; line-height: 1;"><?= $pendingTasksCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Location -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/employee-lists"
                            class="sidebar__menu-item <?= isActive('employee-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-users"></i>
                            </div>
                            <span class="sidebar__menu-label">Employee Manage</span>
                        </a>
                    </li>

                    <!-- Tipsoi Employee Data Group -->
                    <li class="slide has-sub active open">
                        <a href="javascript:void(0);" class="sidebar__menu-item menu-open active">
                            <div class="side-menu__icon"><i class="fa-solid fa-users-line"></i></div>
                            <span class="sidebar__menu-label">Tipsoi Employee Data</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-external-employees"
                                    class="sidebar__menu-item <?= isActive('hrm-external-employees', $current_route) ?>">
                                    Tipsoi Employees
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-create-tipsoi-employee"
                                    class="sidebar__menu-item <?= isActive('hrm-create-tipsoi-employee', $current_route) ?>">
                                    Create Employee
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-attendance-dashboard"
                                    class="sidebar__menu-item <?= isActive('hrm-attendance-dashboard', $current_route) ?>">
                                    Tipsoi Attendance
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-leave-history"
                                    class="sidebar__menu-item <?= isActive('hrm-leave-history', $current_route) ?>">
                                    Tipsoi Leave History
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-leave-balances"
                                    class="sidebar__menu-item <?= isActive('hrm-leave-balances', $current_route) ?>">
                                    Tipsoi Leave Balances
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-monthly-lwp"
                                    class="sidebar__menu-item <?= isActive('hrm-monthly-lwp', $current_route) ?>">
                                    Monthly LWP
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-tipsoi-requests"
                                    class="sidebar__menu-item <?= isActive('hrm-tipsoi-requests', $current_route) ?>">
                                    All Pending Requests
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/tipsoi-email-logs"
                                    class="sidebar__menu-item <?= isActive('tipsoi-email-logs', $current_route) ?>">
                                    Email Log for Pending Req
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/holiday-lists?tab=tipsoi"
                                    class="sidebar__menu-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'tipsoi' && isActive('holiday-lists', $current_route)) ? 'active' : '' ?>">
                                    Tipsoi Holidays
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/hrm-day-off-lists"
                                    class="sidebar__menu-item <?= isActive('hrm-day-off-lists', $current_route) ?>">
                                    Day Off Lists
                                </a>
                            </li>
                        </ul>
                    </li>

                    <?php if (hasPermission("HRM Management") || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 4])): ?>
                        <!-- Tipsoi Reports -->
                        <li
                            class="slide has-sub <?= isActive(['tipsoi-monthly-report', 'tipsoi-daily-report', 'tipsoi-department-report'], $current_route) ? 'active open' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['tipsoi-monthly-report', 'tipsoi-daily-report', 'tipsoi-department-report'], $current_route) ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-column"></i></div>
                                <span class="sidebar__menu-label">Tipsoi Reports</span>
                            </a>
                            <ul class="sidebar-menu child2"
                                style="display: <?= isActive(['tipsoi-monthly-report', 'tipsoi-daily-report', 'tipsoi-department-report'], $current_route) ? 'block' : 'none' ?>;">
                                <li class="slide <?= isActive('tipsoi-monthly-report', $current_route) ?>">
                                    <a href="<?= $base_url ?>/tipsoi-monthly-report"
                                        class="sidebar__menu-item <?= isActive('tipsoi-monthly-report', $current_route) ?>">
                                        Monthly Report
                                    </a>
                                </li>
                                <li class="slide <?= isActive('tipsoi-daily-report', $current_route) ?>">
                                    <a href="<?= $base_url ?>/tipsoi-daily-report"
                                        class="sidebar__menu-item <?= isActive('tipsoi-daily-report', $current_route) ?>">
                                        Daily Report
                                    </a>
                                </li>
                                <li class="slide <?= isActive('tipsoi-department-report', $current_route) ?>">
                                    <a href="<?= $base_url ?>/tipsoi-department-report"
                                        class="sidebar__menu-item <?= isActive('tipsoi-department-report', $current_route) ?>">
                                        Department Report
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (!$isManagement): ?>
                        <!-- department -->
                        <li class="slide ">
                            <a href="<?= $base_url ?>/departments"
                                class="sidebar__menu-item <?= isActive('departments', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-building"></i></div>
                                <span class="sidebar__menu-label">Department Lists</span>
                            </a>
                        </li>
                        <!-- designation -->
                        <li class="slide ">
                            <a href="<?= $base_url ?>/designations"
                                class="sidebar__menu-item <?= isActive('designations', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="sidebar__menu-label">Designation Lists</span>
                            </a>
                        </li>
                        <!-- designation 
                        <li class="slide ">
                            <a href="<?= $base_url ?>/holiday-lists"
                                class="sidebar__menu-item <?= isActive('holiday-lists', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="sidebar__menu-label">Holiday Manage</span>
                            </a>
                        </li>
                        -->
                        <!-- Day Off Manage -->
                        <li class="slide d-none">
                            <a href="<?= $base_url ?>/day-off"
                                class="sidebar__menu-item <?= isActive('day-off', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="sidebar__menu-label">Day Off Manage</span>
                            </a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/generate-letter"
                                class="sidebar__menu-item <?= isActive('generate-letter', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="sidebar__menu-label">Generate Employee Letter</span>
                            </a>
                            <a href="<?= $base_url ?>/letter-templates"
                                class="d-none sidebar__menu-item <?= isActive('letter-templates', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="sidebar__menu-label">Generate Template</span>
                            </a>
                        </li>
                        <!-- Teams Manage -->
                        <li class="slide ">
                            <a href="<?= $base_url ?>/teams"
                                class="sidebar__menu-item <?= isActive('teams', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-users-gear"></i></div>
                                <span class="sidebar__menu-label">Sales Teams Add</span>
                            </a>
                        </li>
                        <li
                            class="slide has-sub d-none <?= isActive(['create-attendance', 'attendance-list'], $current_route) ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-attendance', 'attendance-list'], $current_route) ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                                <span class="sidebar__menu-label">Attendance Manage</span>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide <?= isActive('create-attendance', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('create-attendance', $current_route) ?>"
                                        href="<?= $base_url ?>/create-attendance">Add Attendance</a>
                                </li>
                                <li class="slide <?= isActive('attendance-list', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('attendance-list', $current_route) ?>"
                                        href="<?= $base_url ?>/attendance-list">Attendance List</a>
                                </li>
                            </ul>
                        </li>

                        <li class="slide has-sub d-none <?= isActive(['leave-types', 'leave-lists'], $current_route) ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['leave-types', 'leave-lists'], $current_route) ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                                <span class="sidebar__menu-label">Leave Manage</span>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide <?= isActive('leave-types', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('leave-types', $current_route) ?>"
                                        href="<?= $base_url ?>/leave-types">Add Leave Type</a>
                                </li>
                                <li class="slide <?= isActive('leave-lists', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('leave-lists', $current_route) ?>"
                                        href="<?= $base_url ?>/leave-lists">Employee Leave Request</a>
                                </li>

                            </ul>
                        </li>


                        <li class="sidebar__menu-category d-none">
                            <span class="category-name">Report</span>
                        </li>

                        <ul class="sidebar-menu child2 d-none" style="display:block;">
                            <li class="slide <?= isActive('daily-attendance-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('daily-attendance-report', $current_route) ?>"
                                    href="<?= $base_url ?>/daily-attendance-report">Dept. Wise Attendance Report</a>
                            </li>
                            <li class="slide <?= isActive('leave-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('leave-report', $current_route) ?>"
                                    href="<?= $base_url ?>/leave-report">Leave Report</a>
                            </li>
                            <li class="slide <?= isActive('absent-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('absent-report', $current_route) ?>"
                                    href="<?= $base_url ?>/absent-report">Absent Report</a>
                            </li>
                            <li class="slide <?= isActive('daily-late-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('daily-late-report', $current_route) ?>"
                                    href="<?= $base_url ?>/daily-late-report"> Daily Late Report</a>
                            </li>
                            <li class="slide <?= isActive('mobile-punch-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('mobile-punch-report', $current_route) ?>"
                                    href="<?= $base_url ?>/mobile-punch-report"> Mobile Punch Report</a>
                            </li>
                        </ul>
                        <li class="sidebar__menu-category d-none">
                            <span class="category-name">All Report </span>
                        </li>

                        <ul class="sidebar-menu child2 d-none" style="display:block;">
                            <li class="slide <?= isActive('department-kpi', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('department-kpi', $current_route) ?>"
                                    href="<?= $base_url ?>/department-kpi">Performance Report</a>
                            </li>
                            <li class="slide <?= isActive('job-card-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('job-card-report', $current_route) ?>"
                                    href="<?= $base_url ?>/job-card-report">Attendance (Job Card)</a>
                            </li>
                            <li class="slide <?= isActive('hrm-daily-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('hrm-daily-report', $current_route) ?>"
                                    href="<?= $base_url ?>/hrm-daily-report">Daily Punch Report</a>
                            </li>
                            <li class="slide <?= isActive('hrm-monthly-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('hrm-monthly-report', $current_route) ?>"
                                    href="<?= $base_url ?>/hrm-monthly-report">Monthly Report</a>
                            </li>
                            <li class="slide <?= isActive('dept-wise-report', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('dept-wise-report', $current_route) ?>"
                                    href="<?= $base_url ?>/dept-wise-report"> Department Wise</a>
                            </li>

                        </ul>

                    <?php endif; ?>
                    <!-- User Activity Log — Admin / Management only -->
                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33])): ?>
                        <li class="slide <?= isActive('user-activity-log', $current_route) ?>">
                            <a href="<?= $base_url ?>/user-activity-log"
                                class="sidebar__menu-item <?= isActive('user-activity-log', $current_route) ?>">
                                <div class="side-menu__icon">
                                    <i class="fa-solid fa-user-clock"></i>
                                </div>
                                <span class="sidebar__menu-label">User Activity Log</span>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>

                <?php if (hasPermission("Sales team Performance")): ?>
                    <!-- Sales Team Performance-->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/sales-team-performance"
                            class="sidebar__menu-item <?= isActive('sales-team-performance', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                            <span class="sidebar__menu-label">Sales Teams Performance</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!--Payroll CATEGORY -->
                <?php if (hasPermission("Payroll Manage") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li class="sidebar__menu-category ">
                        <span class="category-name">Payroll Manage</span>
                    </li>
                    <!-- payslip items -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/payslip-items"
                            class="sidebar__menu-item <?= isActive('payslip-items', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Payslip Items</span>
                        </a>
                    </li>

                    <!-- Payslip Lists -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/payslip-lists"
                            class="sidebar__menu-item <?= isActive('payslip-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill"></i></div>
                            <span class="sidebar__menu-label">Payslip Lists</span>
                        </a>
                    </li>

                    <!-- Advance Salary -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/advance-salary"
                            class="sidebar__menu-item <?= isActive('advance-salary', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill"></i></div>
                            <span class="sidebar__menu-label">Advance Salary</span>
                        </a>
                    </li>

                    <!-- Auto Generate Payslip -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/auto-generate-payslip"
                            class="sidebar__menu-item <?= isActive('auto-generate-payslip', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                            <span class="sidebar__menu-label">Auto Generate Payslip</span>
                        </a>
                    </li>

                    <!-- Payroll & Report -->
                    <li
                        class="slide has-sub <?= isActive(['payroll-workspace', 'payroll-salary-hold', 'payroll-attendance-report', 'payroll-joining-list', 'payroll-resign-list', 'payroll-detailed-summary', 'payroll-summary-report', 'payroll-salary-report'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['payroll-workspace', 'payroll-salary-hold', 'payroll-attendance-report', 'payroll-joining-list', 'payroll-resign-list', 'payroll-detailed-summary', 'payroll-summary-report', 'payroll-salary-report'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-regular fa-file-excel"></i></div>
                            <span class="sidebar__menu-label">Payroll & Report</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-workspace"
                                    class="sidebar__menu-item <?= isActive('payroll-workspace', $current_route) ?>">Payroll
                                    Workplace</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-salary-hold"
                                    class="sidebar__menu-item <?= isActive('payroll-salary-hold', $current_route) ?>">Salary
                                    Hold</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-attendance-report"
                                    class="sidebar__menu-item <?= isActive('payroll-attendance-report', $current_route) ?>">Attendance
                                    Report</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-joining-list"
                                    class="sidebar__menu-item <?= isActive('payroll-joining-list', $current_route) ?>">Joining
                                    List</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-resign-list"
                                    class="sidebar__menu-item <?= isActive('payroll-resign-list', $current_route) ?>">Resign
                                    List</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-detailed-summary"
                                    class="sidebar__menu-item <?= isActive('payroll-detailed-summary', $current_route) ?>">Detailed
                                    Summary</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-summary-report"
                                    class="sidebar__menu-item <?= isActive('payroll-summary-report', $current_route) ?>">Payroll
                                    Summary Report</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/payroll-salary-report"
                                    class="sidebar__menu-item <?= isActive('payroll-salary-report', $current_route) ?>">Salary
                                    Report</a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (hasPermission("User Manage") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li
                        class="slide has-sub <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                            <span class="sidebar__menu-label">User</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('create-user', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>"
                                    href="<?= $base_url ?>/create-user">
                                    Add User
                                </a>
                            </li>
                            <li class="slide <?= isActive('user-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('user-lists', $current_route) ?>"
                                    href="<?= $base_url ?>/user-lists">
                                    User List
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php endif; ?>

                <!--End HRM CATEGORY -->



                <!-- My Profile (Accessible by all logged-in users) -->
                <li class="slide d-none<?= isActive('my-profile', $current_route) ?>">
                    <a href="<?= $base_url ?>/my-profile"
                        class="sidebar__menu-item <?= isActive('my-profile', $current_route) ?>">
                        <div class="side-menu__icon"><i class="fas fa-user"></i></div>
                        <span class="sidebar__menu-label">My Profile</span>
                    </a>
                </li>

                <?php
                $showTipsoiMyData = checkTipsoiEmployeeMatch(db())
                    || !empty($_SESSION['user']['employee_id'])
                    || (($_SESSION['user']['role_id'] ?? 0) == 9)
                    || hasPermission("Employee Dashboard");
                if ($showTipsoiMyData):
                    ?>
                    <!-- Tipsoi My Data -->
                    <li class="slide has-sub <?= isActive(['my-tipsoi-info', 'tipsoi-pending-requests'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['my-tipsoi-info', 'tipsoi-pending-requests'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-users-line"></i></div>
                            <span class="sidebar__menu-label">Tipsoi My Data</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#profile" class="sidebar__menu-item">
                                    My Profile
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#attendance" class="sidebar__menu-item">
                                    Attendance Data
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#leave" class="sidebar__menu-item">
                                    My Leave Balance
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#requests" class="sidebar__menu-item">
                                    Pending Request
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#approvals" class="sidebar__menu-item">
                                    Approval Pending
                                </a>
                            </li>

                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#apply-leave" class="sidebar__menu-item">
                                    Apply Leave
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#appliedLeaveListTabContent" class="sidebar__menu-item">
                                    Applied Leave List
                                </a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/my-tipsoi-info#late-request" class="sidebar__menu-item">
                                    Late Request
                                </a>
                            </li>

                        </ul>
                    </li>
                <?php endif; ?>


                <?php if (hasPermission("Employee Dashboard") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <!-- Employee Dashboard -->
                    <li class="slide <?= isActive(['employee-dashboard', 'my-attendance', 'my-leaves'], $current_route) ?>">
                        <a href="<?= $base_url ?>/employee-dashboard"
                            class="sidebar__menu-item <?= isActive('employee-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">My Dashboard</span>
                        </a>
                        <a href="<?= $base_url ?>/my-attendance"
                            class="d-none sidebar__menu-item <?= isActive('my-attendance', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">My Attendance</span>
                        </a>
                        <a href="<?= $base_url ?>/my-leaves"
                            class="d-none sidebar__menu-item <?= isActive('my-leaves', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">My Leaves </span>
                        </a>
                    </li>
                <?php endif; ?>



                <!--Start CED  -->
                <?php if ((hasPermission("CED Manage") || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33])) && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <!--CED CATEGORY -->
                    <li class="sidebar__menu-category">

                        <span class="category-name">CED</span>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/ced-dashboard"
                            class="sidebar__menu-item <?= isActive('ced-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">CED Dashboard</span>
                        </a>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/ced-report"
                            class="sidebar__menu-item <?= isActive('ced-report', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-file-earmark-bar-graph"></i></div>
                            <span class="sidebar__menu-label">CED Report</span>
                        </a>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/company-bank-accounts"
                            class="sidebar__menu-item <?= isActive('company-bank-accounts', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-landmark"></i></div>
                            <span class="sidebar__menu-label">Company Bank Manage</span>
                        </a>
                    </li>


                    <!-- Cash In Manage -->
                    <li
                        class="slide has-sub <?= isActive(['clients-cashin-view', 'clients-cash-in-history-view', 'client-refunds'], $current_route) ? 'open active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['clients-cashin-view', 'clients-cash-in-history-view', 'client-refunds'], $current_route) ? 'active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                            <span class="sidebar__menu-label">Cash In View</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2"
                            style="<?= isMenuOpen(['clients-cashin-view', 'clients-cash-in-history-view', 'client-refunds'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('clients-cashin-view', $current_route) ?>">
                                <a href="<?= $base_url ?>/clients-cashin-view"
                                    class="sidebar__menu-item <?= isActive('clients-cashin-view', $current_route) ?>">Clients</a>
                            </li>
                            <li class="slide <?= isActive('client-refunds', $current_route) ?>">
                                <a href="<?= $base_url ?>/client-refunds"
                                    class="sidebar__menu-item <?= isActive('client-refunds', $current_route) ?>">Refunds</a>
                            </li>
                        </ul>
                    </li>
                    <!-- Clients For Login  -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/onboard-access-client-lists"
                            class="sidebar__menu-item <?= isActive('onboard-access-client-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label">Access Onboard Clients</span>
                        </a>
                    </li>



                <?php endif; ?>
                <!--End CED CATEGORY -->


                <!--Accounts CATEGORY -->
                <?php if (hasPermission("accounts manage") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">Accounts</span>
                    </li>

                    <!-- Accounts Dashboard -->
                    <?php
                    $accountsNotifCount = 0;
                    if (hasPermission("accounts manage") && ($_SESSION['user']['role_id'] ?? 0) != 9) {
                        if (!class_exists('DashboardController')) {
                            require_once __DIR__ . '/../../controllers/DashboardController.php';
                        }
                        global $db;
                        $dashCtrl = new DashboardController($db);
                        $notifs = $dashCtrl->getAccountsNotifications(false);
                        $accountsNotifCount = count($notifs);
                    }
                    ?>
                    <li class="slide <?= isActive('accounts-dashboard', $current_route) ?>">
                        <a href="<?= $base_url ?>/accounts-dashboard"
                            class="sidebar__menu-item <?= isActive('accounts-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Accounts Dashboard</span>
                            <?php if ($accountsNotifCount > 0): ?>
                                <span class="badge bg-danger ms-2"
                                    style="border-radius: 50%; padding: 0.35em 0.5em; font-size: 0.75em;"><?= $accountsNotifCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/company-bank-accounts"
                            class="sidebar__menu-item <?= isActive('company-bank-accounts', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-landmark"></i></div>
                            <span class="sidebar__menu-label">Company Bank Manage</span>
                        </a>
                    </li>
                    <?php
                    $financialHubRoutes = [
                        'financial-dashboard',
                        'accounts/coa',
                        'general-journal',
                        'clients-cash-in-history',
                        'expense-adjustments',
                        'payment-invoice',
                        'reports/trial-balance',
                        'reports/profit-loss',
                        'reports/balance-sheet'
                    ];
                    ?>
                    <li class="slide <?= isActive($financialHubRoutes, $current_route) ?>">
                        <a href="<?= $base_url ?>/financial-dashboard"
                            class="sidebar__menu-item <?= isActive($financialHubRoutes, $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-file-invoice-dollar text-success"></i></div>
                            <span class="sidebar__menu-label">Accounts & Financial Hub</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('accounts/coa', $current_route) ?>">
                        <a href="<?= $base_url ?>/accounts/coa"
                            class="sidebar__menu-item <?= isActive('accounts/coa', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-sitemap text-info"></i></div>
                            <span class="sidebar__menu-label">Chart of Accounts (COA)</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('reports/trial-balance', $current_route) ?>">
                        <a href="<?= $base_url ?>/reports/trial-balance"
                            class="sidebar__menu-item <?= isActive('reports/trial-balance', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-scale-balanced text-warning"></i></div>
                            <span class="sidebar__menu-label">Trial Balance (রেওয়ামিল)</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('reports/profit-loss', $current_route) ?>">
                        <a href="<?= $base_url ?>/reports/profit-loss"
                            class="sidebar__menu-item <?= isActive('reports/profit-loss', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-pie text-success"></i></div>
                            <span class="sidebar__menu-label">Profit & Loss Statement</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('reports/balance-sheet', $current_route) ?>">
                        <a href="<?= $base_url ?>/reports/balance-sheet"
                            class="sidebar__menu-item <?= isActive('reports/balance-sheet', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-building-columns text-primary"></i></div>
                            <span class="sidebar__menu-label">Balance Sheet</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('reports/ap-ageing', $current_route) ?>">
                        <a href="<?= $base_url ?>/reports/ap-ageing"
                            class="sidebar__menu-item <?= isActive('reports/ap-ageing', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-hourglass-clock text-warning"></i></div>
                            <span class="sidebar__menu-label">AP Ageing Analysis</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('reports/cash-commitment-forecast', $current_route) ?>">
                        <a href="<?= $base_url ?>/reports/cash-commitment-forecast"
                            class="sidebar__menu-item <?= isActive('reports/cash-commitment-forecast', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line-up text-purple"
                                    style="color: #6f42c1;"></i></div>
                            <span class="sidebar__menu-label">Cash Flow Forecast</span>
                        </a>
                    </li>
                    <?php if (!$isManagement): ?>
                        <li class="slide <?= isActive('all-approval-requests', $current_route) ?>">
                            <a href="<?= $base_url ?>/all-approval-requests"
                                class="sidebar__menu-item <?= isActive('all-approval-requests', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-list-check text-primary"></i></div>
                                <span class="sidebar__menu-label">
                                    All Approval Requests
                                    <?php if ($pendingApprovalCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill ms-2"><?= $pendingApprovalCount ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="slide   <?= isActive('central-statement', $current_route) ?>">
                        <a href="<?= $base_url ?>/central-statement"
                            class="sidebar__menu-item <?= isActive('central-statement', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-book"></i></div>
                            <span class="sidebar__menu-label">Central Statement</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('incentive-management', $current_route) ?>">
                        <a href="<?= $base_url ?>/incentive-management"
                            class="sidebar__menu-item <?= isActive('incentive-management', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-coins text-warning"></i></div>
                            <span class="sidebar__menu-label">
                                Sales Incentive Accounts
                                <?php if ($pendingIncentiveClaimsCount > 0): ?>
                                    <span
                                        class="badge bg-warning text-dark rounded-pill ms-2"><?= $pendingIncentiveClaimsCount ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                    <li class="slide d-none <?= isActive('projects-cashout', $current_route) ?>">
                        <a href="<?= $base_url ?>/projects-cashout"
                            class="sidebar__menu-item <?= isActive('projects-cashout', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-book"></i></div>
                            <span class="sidebar__menu-label">Projects Cashout</span>
                        </a>
                    </li>
                    <?php if (!$isManagement): ?>
                        <li class="slide <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                            <a href="<?= $GLOBALS['base_url'] ?>/con-wastage"
                                class="sidebar__menu-item <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                <div class="side-menu__icon"><i class="fal fa-recycle"></i></div>
                                <span class="sidebar__menu-label">Wastage Management</span>
                            </a>
                        </li>


                    <?php endif; ?>
                    <!-- ===================== -->
                    <!-- Manage Cash In -->
                    <!-- ===================== -->
                    <li
                        class="slide has-sub <?= isActive(['clients-cash-in', 'management-cash-in'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['clients-cash-in', 'management-cash-in'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                            <span class="sidebar__menu-label">Manage Cash In</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>

                        <!-- ALWAYS visible like Flats -->
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('clients-cash-in', $current_route) ?>">
                                <a href="<?= $base_url ?>/clients-cash-in"
                                    class="sidebar__menu-item <?= isActive('clients-cash-in', $current_route) ?>"
                                    style="display:flex; justify-content:space-between; align-items:center; padding-right:12px;">
                                    <span class="text-truncate">Clients</span>
                                    <?php if (!empty($pendingVerificationsCount) && $pendingVerificationsCount > 0): ?>
                                        <span class="badge text-dark rounded-pill ms-2 flex-shrink-0"
                                            style="font-size:10px; padding: 2px 6px; background-color: #ffb300;"
                                            title="<?= $pendingVerificationsCount ?> Pending Verifications">
                                            <?= $pendingVerificationsCount ?> Pending
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="slide <?= isActive('trash-client-payment-list', $current_route) ?>">
                                <a href="<?= $base_url ?>/trash-client-payment-list"
                                    class="sidebar__menu-item <?= isActive('trash-client-payment-list', $current_route) ?>"
                                    style="display:flex; justify-content:space-between; align-items:center; padding-right:12px;">
                                    <span class="text-truncate" style="color: #ff9800;"><i
                                            class="fa-regular fa-trash-can-arrow-up me-2"></i> Trash List</span>
                                </a>
                            </li>
                            <li class="slide <?= isActive('management-cash-in', $current_route) ?>">
                                <a href="<?= $base_url ?>/management-cash-in"
                                    class="sidebar__menu-item <?= isActive('management-cash-in', $current_route) ?>">Management</a>
                            </li>
                        </ul>
                    </li>

                    <?php if (!$isManagement || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33])): ?>
                        <li
                            class="slide has-sub <?= isActive(['create-cashout-request', 'admin-cashout-requests', 'expense-adjustments'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-cashout-request', 'admin-cashout-requests', 'expense-adjustments'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"> <i class="fa-solid fa-money-bill-trend-up"></i></div>
                                <span class="sidebar__menu-label">Manage Cash Out</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>

                            <ul class="sidebar-menu child2" style="display:block;">


                                <?php if (($_SESSION['user']['role_id'] ?? 0) != 3): ?>
                                    <li class="slide <?= isActive('create-cashout-request', $current_route) ?>">
                                        <a href="<?= $base_url ?>/create-cashout-request"
                                            class="sidebar__menu-item <?= isActive('create-cashout-request', $current_route) ?>">Create
                                            Cashout Request </a>
                                    </li>
                                <?php endif; ?>

                                <li class="slide <?= isActive('admin-cashout-requests', $current_route) ?>">
                                    <a href="<?= $base_url ?>/admin-cashout-requests"
                                        class="sidebar__menu-item <?= isActive('admin-cashout-requests', $current_route) ?>">
                                        Cashout Reports And Approve</a>
                                </li>

                                <li class="slide <?= isActive('expense-adjustments', $current_route) ?>">
                                    <a href="<?= $base_url ?>/expense-adjustments"
                                        class="sidebar__menu-item <?= isActive('expense-adjustments', $current_route) ?>">Expense
                                        Adjustments</a>
                                </li>
                            </ul>
                        </li>

                    <?php endif; ?>
                    <!-- ===================== -->
                    <!-- Construction Finance -->
                    <!-- ===================== -->
                    <li
                        class="slide has-sub <?= isActive(['con-vendor-payment-requests', 'con-contractor-bills', 'con-transactions', 'con-pending-payments', 'con-land-requisitions', 'fund-requisitions'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['con-vendor-payment-requests', 'con-contractor-bills', 'con-transactions', 'con-pending-payments', 'con-land-requisitions', 'fund-requisitions'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-helmet-safety"></i></div>
                            <span class="sidebar__menu-label">Construction Finance</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('con-vendor-payment-requests', $current_route) ?>">
                                <a href="<?= $base_url ?>/con-vendor-payment-requests"
                                    class="sidebar__menu-item <?= isActive('con-vendor-payment-requests', $current_route) ?> d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-wallet2 me-1"></i> Vendor Payments</span>
                                    <?php if (!empty($sidebarPendingVprCount) && $sidebarPendingVprCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill me-1"
                                            style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingVprCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="slide <?= isActive('con-contractor-bills', $current_route) ?>">
                                <a href="<?= $base_url ?>/con-contractor-bills"
                                    class="sidebar__menu-item <?= isActive('con-contractor-bills', $current_route) ?>">
                                    <i class="bi bi-receipt me-1"></i>Contractor Bills
                                    <?php if (isset($conPendingBillsCount) && $conPendingBillsCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill ms-2"><?= $conPendingBillsCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="slide <?= isActive('con-transactions', $current_route) ?>">
                                <a href="<?= $base_url ?>/con-transactions"
                                    class="sidebar__menu-item <?= isActive('con-transactions', $current_route) ?>">
                                    <i class="bi bi-journal-check me-1"></i>Transactions Ledger
                                </a>
                            </li>
                            <li class="slide <?= isActive('con-pending-payments', $current_route) ?>">
                                <a href="<?= $base_url ?>/con-pending-payments"
                                    class="sidebar__menu-item <?= isActive('con-pending-payments', $current_route) ?>">
                                    <i class="bi bi-clock me-1"></i> Pending Payments
                                </a>
                            </li>
                            <li class="slide <?= isActive('con-land-requisitions', $current_route) ?>">
                                <a href="<?= $base_url ?>/con-land-requisitions"
                                    class="sidebar__menu-item <?= isActive('con-land-requisitions', $current_route) ?>">
                                    <i class="bi bi-cash-coin me-1"></i> Land Requisitions
                                </a>
                            </li>
                            <li class="slide <?= isActive('fund-requisitions', $current_route) ?>">
                                <a href="<?= $base_url ?>/fund-requisitions"
                                    class="sidebar__menu-item <?= isActive('fund-requisitions', $current_route) ?>">
                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Fund Requisitions
                                    <?php if (!empty($pendingFundReqTotalCount)): ?>
                                        <span class="badge bg-warning text-dark rounded-pill ms-2"
                                            style="font-size: 10px; padding: 2px 6px;"
                                            title="Pending Actions: <?= $pendingFundReqDeptCount ?> Dept Head, <?= $pendingFundReqMgmtCount ?> Mgmt, <?= $pendingFundReqAccountsCount ?> Accounts">
                                            <?= $pendingFundReqTotalCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>



                    <!-- Client Refunds -->
                    <li class="slide <?= isActive('client-refunds', $current_route) ?>">
                        <a href="<?= $base_url ?>/client-refunds"
                            class="sidebar__menu-item <?= isActive('client-refunds', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
                            <span class="sidebar__menu-label">Client Refunds</span>
                        </a>
                    </li>

                    <!-- ====== Labor Payment Approvals ====== -->
                    <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 6])): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-labor-requisitions', 'con-labor-requisition'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-labor-requisitions', 'con-labor-requisition'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-hard-hat"></i
                                ></div>
                                <span class="sidebar__menu-label">Labor Pay Approval</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-labor-requisitions?status=management_approved"
                                        class="sidebar__menu-item <?= (isActive('con-labor-requisitions', $current_route) && ($_GET['status'] ?? '') === 'management_approved') ? 'active' : '' ?>">
                                        <i class="bi bi-clock-history me-1"></i> Pending Payments
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-labor-requisitions?status=paid" class="sidebar__menu-item">
                                        <i class="bi bi-check2-circle me-1"></i> Paid History
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-labor-requisitions"
                                        class="sidebar__menu-item <?= isActive('con-labor-requisitions', $current_route) ? 'active' : '' ?>">
                                        <i class="bi bi-list-ul me-1"></i> All Requisitions
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                <?php endif ?>
                <!--END Accounts CATEGORY -->


                <!--ROle & Permission -->
                <?php if (hasPermission("role & permission") && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">Role & Permission</span>
                    </li>

                    <!-- User Submenu -->
                    <li
                        class="slide has-sub <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                            <span class="sidebar__menu-label">User</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('create-user', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>"
                                    href="<?= $base_url ?>/create-user">
                                    Add User
                                </a>
                            </li>
                            <li class="slide <?= isActive('user-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('user-lists', $current_route) ?>"
                                    href="<?= $base_url ?>/user-lists">
                                    User List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Permission -->
                    <li
                        class="slide has-sub <?= isActive(['role-permissions', 'permissions'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);"
                            class="sidebar__menu-item <?= isActive(['role-permissions', 'permissions'], $current_route) ? 'active' : '' ?>">

                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">Permission</span>
                        </a>
                        <ul class="sidebar-menu child1 " style="display:block;">
                            <li class="slide <?= isActive('permissions', $current_route) ?>">
                                <a href="<?= $base_url ?>/permissions"
                                    class="sidebar__menu-item <?= isActive('permissions', $current_route) ?>"> Permission
                                    Lists</a>
                            </li>
                            <li class="slide <?= isActive('role-permissions', $current_route) ?>">
                                <a href="<?= $base_url ?>/role-permissions"
                                    class="sidebar__menu-item <?= isActive('role-permissions', $current_route) ?>">Role-Permission</a>
                            </li>
                        </ul>
                    </li>

                <?php endif ?>
                <!--ROle & Permission -->

                <?php if (hasPermission("My Leave Request") && !$isManagement && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <ul class="d-none sidebar-menu child2" style="display:block;">
                        <li class="slide ">
                            <a href="<?= $base_url ?>/create-self-leave-request"
                                class="sidebar__menu-item <?= isActive('create-self-leave-request', $current_route) ?>">

                                <span class="sidebar__menu-label">My Leave Request</span>
                            </a>
                        </li>
                    </ul>
                <?php endif ?>


                <li class="slide d-none <?= isActive('mobile-punch', $current_route) ?>">
                    <a href="<?= $base_url ?>/mobile-punch"
                        class="sidebar__menu-item <?= isActive('mobile-punch', $current_route) ?>">
                        <div class="side-menu__icon"><i class="fa-solid fa-book"></i></div>
                        <span class="sidebar__menu-label">Mobile Punch</span>
                    </a>
                </li>



                <!-- Others-->
                <?php if ((hasPermission("others") || hasPermission("Hr Manage")) && ($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">Others</span>
                    </li>





                    <!-- Location -->
                    <?php if (hasPermission("others")): ?>
                        <li class="slide ">
                            <a href="<?= $base_url ?>/company-bank-accounts"
                                class="sidebar__menu-item <?= isActive('company-bank-accounts', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-landmark"></i></div>
                                <span class="sidebar__menu-label">Company Bank Accounts</span>
                            </a>
                        </li>
                    <?php endif; ?>


                    <!-- Site Manage -->
                    <?php if (hasPermission("others") || hasPermission("Hr Manage")): ?>
                        <li
                            class="slide has-sub <?= isActive(['site-settings', 'home-footer-setting', 'flat-purchase-rules'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['site-settings', 'home-footer-setting', 'flat-purchase-rules'], $current_route) ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon">
                                    <i class="fa-solid fa-gear"></i>
                                </div>
                                <span class="sidebar__menu-label">Site Manage</span>
                            </a>

                            <!-- always open -->
                            <ul class="sidebar-menu child1" style="display:block;">
                                <li class="slide <?= isActive('site-settings', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('site-settings', $current_route) ?>"
                                        href="<?= $GLOBALS['base_url'] ?>/site-settings">
                                        Setting Manage
                                    </a>
                                </li>

                                <li class="slide <?= isActive('flat-purchase-rules', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('flat-purchase-rules', $current_route) ?>"
                                        href="<?= $GLOBALS['base_url'] ?>/flat-purchase-rules">
                                        Flat Purchase Rules
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>






                <?php endif; ?>


                <!-- ==================== PHASE 2: CONSTRUCTION MANAGEMENT ==================== -->
                <?php if (($_SESSION['user']['role_id'] ?? 0) != 9): ?>
                    <?php
                    $hasConPerms = hasPermission('Construction Management') || hasPermission('site engineer dashboard') || in_array($_SESSION['user']['role_id'] ?? 0, [20, 32]) || hasPermission('Purchase order') || ($_SESSION['user']['role_id'] ?? 0) == 23 || hasPermission('Project Draft Design') || hasPermission('Land Study') || hasPermission('Architect Design') || hasPermission('Estimator dashboard') || hasPermission('Project structure design') || hasPermission('QC Dashboard') || hasPermission('Project Manage') || hasPermission('Estimate Budget') || hasPermission('Work Order') || hasPermission('Working Plan') || hasPermission('Project Target Set') || hasPermission('Budget Extend') || hasPermission('Material Manage') || hasPermission('Operators & Bills') || hasPermission('Project Report') || hasPermission('Bill & Payments') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3]);
                    if ($hasConPerms):
                        ?>
                        <li class="sidebar__menu-category">
                            <span class="category-name">Construction </span>
                        </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Construction Management') || ($_SESSION['user']['role_id'] ?? 0) == 15): ?>
                        <li class="slide ">
                            <a href="<?= $base_url ?>/con-dashboard"
                                class="sidebar__menu-item <?= isActive('con-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="sidebar__menu-label">Constrution Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [3, 15, 22, 12, 20, 32])): ?>
                        <li class="slide">
                            <a href="<?= $base_url ?>/all-projects-overview"
                                class="sidebar__menu-item <?= isActive('all-projects-overview', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-bar"></i></div>
                                <span class="sidebar__menu-label">All Project Overview</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 12, 15, 22])): ?>
                        <li class="slide">
                            <a href="<?= $base_url ?>/con-progress-report"
                                class="sidebar__menu-item <?= isActive('con-progress-report', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-file-invoice"></i></div>
                                <span class="sidebar__menu-label">Site Progress Report</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])): ?>
                        <li class="slide">
                            <a href="<?= $base_url ?>/con-projects"
                                class="sidebar__menu-item <?= isActive('con-projects', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-building-circle-check"></i></div>
                                <span class="sidebar__menu-label"> Projects & Phase Plan</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $showAllPhases = in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22]);
                    if (!empty($_SESSION['active_project_id']) && $showAllPhases):
                        $projectSubRoutes = ['con-project', 'con-project-timeline', 'con-phase-plan', 'con-floor-setup', 'con-monthly-targets', 'con-monthly-target', 'con-monthly-review', 'con-risk-dashboard'];
                        $isProjectActive = isActive($projectSubRoutes, $current_route);
                        ?>
                        <li class="slide has-sub <?= $isProjectActive ? 'active' : '' ?>">
                            <a href="javascript:void(0);" class="sidebar__menu-item <?= $isProjectActive ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon"><i class="fa-solid fa-folder-open"></i></div>
                                <span class="sidebar__menu-label"
                                    style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 150px;"
                                    title="<?= htmlspecialchars($_SESSION['active_project_name'] ?? 'Active Project') ?>">
                                    <?= htmlspecialchars($_SESSION['active_project_name'] ?? 'Active Project') ?>
                                </span>
                            </a>
                            <ul class="sidebar-menu child1" style="display:block;">
                                <li class="slide <?= isActive('con-project', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-project', $current_route) ?>"
                                        href="<?= $base_url ?>/con-project/<?= $_SESSION['active_project_id'] ?>">
                                        Project Overview
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-project-timeline', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-project-timeline', $current_route) ?>"
                                        href="<?= $base_url ?>/con-project-timeline/<?= $_SESSION['active_project_id'] ?>">
                                        Timeline Setup
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-phase-plan', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-phase-plan', $current_route) ?>"
                                        href="<?= $base_url ?>/con-phase-plan/<?= $_SESSION['active_project_id'] ?>">
                                        Phase Plan
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-floor-setup', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-floor-setup', $current_route) ?>"
                                        href="<?= $base_url ?>/con-floor-setup/<?= $_SESSION['active_project_id'] ?>">
                                        Floor Setup
                                    </a>
                                </li>
                                <li
                                    class="slide <?= isActive(['con-monthly-targets', 'con-monthly-target'], $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive(['con-monthly-targets', 'con-monthly-target'], $current_route) ?>"
                                        href="<?= $base_url ?>/con-monthly-targets/<?= $_SESSION['active_project_id'] ?? 0 ?>">
                                        Monthly Targets
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-monthly-review', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-monthly-review', $current_route) ?>"
                                        href="<?= $base_url ?>/con-monthly-review/<?= $_SESSION['active_project_id'] ?>">
                                        Monthly Review
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-risk-dashboard', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('con-risk-dashboard', $current_route) ?>"
                                        href="<?= $base_url ?>/con-risk-dashboard/<?= $_SESSION['active_project_id'] ?>">
                                        Risk Dashboard
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (hasPermission('site engineer dashboard')): ?>
                        <li class="slide ">
                            <a href="<?= $base_url ?>/con-site-engineer-dashboard"
                                class="sidebar__menu-item <?= isActive('con-site-engineer-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="sidebar__menu-label">site engineer Dashboard</span>
                            </a>
                        </li>

                    <?php endif; ?>


                    <!-- ══ Operations Dashboard ════════════════════════════════════════
                     Site Operator -->
                    <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [20, 32])): ?>
                        <li class="slide <?= isActive('operations-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/operations-dashboard"
                                class="sidebar__menu-item <?= isActive('operations-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-hard-hat"></i></div>
                                <span class="sidebar__menu-label">Operations Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ══ Purchase Team Leader Dashboard ════════════════════════════
                     PTL has: Purchase order + Vendor Manage + Material Manage
                     Unique: Material Manage (Site Eng lacks it, QC lacks it)
                     Exclude: Construction Management -->
                    <?php if (hasPermission('Purchase order') && hasPermission('Vendor Manage') && hasPermission('Material Manage') && !hasPermission('Construction Management') && ($_SESSION['user']['role_id'] ?? 0) != 31): ?>
                        <li class="slide <?= isActive('purchase-team-lead-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/purchase-team-lead-dashboard"
                                class="sidebar__menu-item <?= isActive('purchase-team-lead-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-users-gear"></i></div>
                                <span class="sidebar__menu-label">Procurement Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (($_SESSION['user']['role_id'] ?? 0) == 31): ?>
                        <li class="slide <?= isActive('sr-executive-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/sr-executive-dashboard"
                                class="sidebar__menu-item <?= isActive('sr-executive-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-users-gear"></i></div>
                                <span class="sidebar__menu-label">Procurement Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ══ Purchase Executive Dashboard ════════════════════════════
                     Role ID 23: Purchase Executive -->
                    <?php if ($_SESSION['user']['role_id'] == 23): ?>
                        <li class="slide <?= isActive('purchase-executive-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/purchase-executive-dashboard"
                                class="sidebar__menu-item <?= isActive('purchase-executive-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-cart-flatbed"></i></div>
                                <span class="sidebar__menu-label">Executive Dashboard</span>
                            </a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/con-purchase-requests"
                                class="sidebar__menu-item <?= isActive('con-purchase-requests', $current_route) ?> d-flex align-items-center justify-content-between">
                                <span>Purchase Requests</span>
                                <?php if (!empty($sidebarPendingPRCount) && $sidebarPendingPRCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill me-1"
                                        style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPRCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ══ Draftsman Dashboard ════════════════════════════════════════
                     Draftsman has ONLY: Project Draft Design
                     Unique: Project Draft Design + NO Architect Design + NO Material Issue
                     Exclude: Construction Management, Site Engineer (has Material Issue) -->
                    <?php if ((hasPermission('Project Draft Design') && !hasPermission('Architect Design') && !hasPermission('Material Issue') && !hasPermission('Construction Management')) || ($_SESSION['user']['role_id'] ?? 0) == 16): ?>
                        <li class="slide <?= isActive('draft-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/draft-dashboard"
                                class="sidebar__menu-item <?= isActive('draft-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-drafting-compass"></i></div>
                                <span class="sidebar__menu-label">Draft Dashboard</span>
                            </a>
                        </li>

                    <?php endif; ?>

                    <!-- ══ Land Manager Dashboard ═════════════════════════════════════
                     Land Manager has ONLY: Land Study
                     Unique: Land Study + NO Soil tests (Site Eng has both)
                     Exclude: Construction Management -->
                    <?php if (hasPermission('Land Study') && !hasPermission('Soil tests') && !hasPermission('Construction Management')): ?>
                        <li class="slide <?= isActive('land-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/land-dashboard"
                                class="sidebar__menu-item <?= isActive('land-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-map-location-dot"></i></div>
                                <span class="sidebar__menu-label">Land Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- ══ Project Architecture Dashboard ════════════════════════════
                     Project Architecture has: Project Draft Design + Architect Design
                     Unique: Architect Design + Project Draft Design + NO Material Issue
                     Exclude: Construction Management, Site Engineer (has Material Issue) -->
                    <?php if (hasPermission('Architect Design') || ($_SESSION['user']['role_id'] ?? 0) == 18): ?>
                        <li class="slide <?= isActive('project-architecture-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/project-architecture-dashboard"
                                class="sidebar__menu-item <?= isActive('project-architecture-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-building-columns"></i></div>
                                <span class="sidebar__menu-label">Architect Dashboard</span>
                            </a>
                        </li>

                    <?php endif; ?>

                    <!-- ══ Estimator Dashboard ════════════════════════════════════════
                     Estimator has: Project Drawing + Estimate Budget
                     Unique: Project Drawing (QC & PTL don't have it)
                     Exclude: Construction Management -->
                    <?php if (hasPermission('Estimator dashboard')): ?>
                        <li class="slide <?= isActive('estimator-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/estimator-dashboard"
                                class="sidebar__menu-item <?= isActive('estimator-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-calculator"></i></div>
                                <span class="sidebar__menu-label">Estimator Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ══ Project Structure Design Dashboard ════════════════════════════
                     and NOT 'Construction Management' (PM/Admin see main dashboard) -->
                    <?php if ((hasPermission('Project structure design') && !hasPermission('Construction Management')) || ($_SESSION['user']['role_id'] ?? 0) == 21): ?>
                        <li class="slide <?= isActive('project-structural-design-dashboard', $current_route) ?>">
                            <a href="<?= $base_url ?>/project-structural-design-dashboard"
                                class="sidebar__menu-item <?= isActive('project-structural-design-dashboard', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-drafting-compass"></i></div>
                                <span class="sidebar__menu-label">Structure Dashboard</span>
                            </a>
                        </li>

                    <?php endif; ?>
                    <!-- ====== QC MODULE ====== -->
                    <?php if (hasPermission('QC Dashboard')): ?>
                        <li
                            class="slide has-sub <?= isActive(['qc-dashboard', 'con-weekly-reports', 'con-weekly-report', 'qc-inspection-create', 'qc-material-test-create', 'qc-checklists', 'qc-test-types', 'con-wastage', 'con-wastage-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['qc-dashboard', 'con-weekly-reports', 'con-weekly-report', 'qc-inspection-create', 'qc-material-test-create', 'qc-checklists', 'qc-test-types', 'con-wastage', 'con-wastage-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="bi bi-shield-check"></i></div>
                                <span class="sidebar__menu-label">QC Module</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $GLOBALS['base_url'] ?>/qc-dashboard"
                                        class="sidebar__menu-item <?= isActive('qc-dashboard', $current_route) ?>">QC
                                        Dashboard</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $GLOBALS['base_url'] ?>/con-weekly-reports"
                                        class="sidebar__menu-item <?= isActive(['con-weekly-reports', 'con-weekly-report'], $current_route) ?>">New
                                        Inspection</a>
                                </li>

                                <li class="slide">
                                    <a href="<?= $GLOBALS['base_url'] ?>/qc-test-types"
                                        class="sidebar__menu-item <?= isActive('qc-test-types', $current_route) ?>">QC Test
                                        Types</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $GLOBALS['base_url'] ?>/qc-material-test-create"
                                        class="sidebar__menu-item <?= isActive('qc-material-test-create', $current_route) ?>">Quality
                                        Control Tests</a>
                                </li>
                                <li class="slide <?= isActive('qc-checklists', $current_route) ?>">
                                    <a href="<?= $GLOBALS['base_url'] ?>/qc-checklists"
                                        class="sidebar__menu-item <?= isActive('qc-checklists', $current_route) ?>">
                                        <i class="fal fa-clipboard-list me-1"></i> Checklists
                                    </a>
                                </li>
                                <li class="slide <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                    <a href="<?= $GLOBALS['base_url'] ?>/con-wastage"
                                        class="sidebar__menu-item <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                        <i class="fal fa-recycle me-1"></i> Wastage Management
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>


                    <!-- Pre-Construction — only show if user has at least one permission -->
                    <?php if (hasPermission('Land Study') || hasPermission('Architect Design') || hasPermission('Project structure design') || hasPermission('Project Draft Design') || hasPermission('Project Drawing') || hasPermission('Soil tests') || hasPermission('Construction Management') || in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 12, 15, 19, 22])): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-land-visibility-studies', 'con-land-acquisitions', 'con-land-requisitions', 'con-land-ledger', 'con-architect-engineering', 'con-design-types', 'con-project-drawings', 'con-soil-tests', 'con-work-types', 'con-work-type-create', 'con-work-type-edit', 'legal-sheet-lists', 'create-legal-sheet'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-land-visibility-studies', 'con-land-acquisitions', 'con-land-requisitions', 'con-land-ledger', 'con-land-monthly-reports', 'con-architect-engineering', 'con-design-types', 'con-project-drawings', 'con-soil-tests', 'con-work-types', 'con-work-type-create', 'con-work-type-edit', 'legal-sheet-lists', 'create-legal-sheet'], $current_route) ? 'active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <span class="sidebar__menu-label">Engineering Department</span>
                            </a>

                            <ul class="sidebar-menu child1" style="display:block;">
                                <?php if (hasPermission('Land Study') || hasPermission('Construction Management')): ?>
                                    <li class="slide <?= isActive('con-land-visibility-studies', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-land-visibility-studies', $current_route) ?>"
                                            href="<?= $base_url ?>/con-land-visibility-studies">Land Visibility Study</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], [1, 3, 6, 14])): ?>
                                    <li
                                        class="slide <?= isActive(['con-land-acquisitions', 'con-land-acquisitions/create', 'con-land-acquisitions/edit'], $current_route) ? 'active' : '' ?>">
                                        <a class="sidebar__menu-item <?= isActive(['con-land-acquisitions', 'con-land-acquisitions/create', 'con-land-acquisitions/edit'], $current_route) ? 'active' : '' ?>"
                                            href="<?= $base_url ?>/con-land-acquisitions">Land Acquisitions</a>
                                    </li>
                                    <li
                                        class="slide <?= isActive(['con-land-requisitions', 'con-land-requisitions/create'], $current_route) ? 'active' : '' ?>">
                                        <a class="sidebar__menu-item <?= isActive(['con-land-requisitions', 'con-land-requisitions/create'], $current_route) ? 'active' : '' ?>"
                                            href="<?= $base_url ?>/con-land-requisitions">Land Requisitions</a>
                                    </li>
                                    <li class="slide <?= isActive('con-land-ledger', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-land-ledger', $current_route) ?>"
                                            href="<?= $base_url ?>/con-land-ledger">Land Ledger</a>
                                    </li>
                                    <li class="d-none slide <?= isActive('con-land-monthly-reports', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-land-monthly-reports', $current_route) ?>"
                                            href="<?= $base_url ?>/con-land-monthly-reports">Land Monthly Report</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (hasPermission('Architect Design') || hasPermission('Construction Management')): ?>
                                    <li class="slide <?= isActive('con-architect-engineering', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-architect-engineering', $current_route) ?>"
                                            href="<?= $base_url ?>/con-architect-engineering">Architecture Design</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (($_SESSION['user']['role_id'] ?? 0) == 16): ?>
                                    <li class="slide <?= isActive('con-design-types', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-design-types', $current_route) ?>"
                                            href="<?= $base_url ?>/con-design-types">Drawing Types</a>
                                    </li>
                                <?php endif; ?>
                                <?php if ((hasPermission('Project Draft Design') || hasPermission('Project Drawing') || hasPermission('Construction Management') || ($_SESSION['user']['role_id'] ?? 0) == 19) && ($_SESSION['user']['role_id'] ?? 0) != 18): ?>
                                    <li class="slide <?= isActive('con-project-drawings', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-project-drawings', $current_route) ?>"
                                            href="<?= $base_url ?>/con-project-drawings">Project Drawings</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (hasPermission('Project Draft Design') || hasPermission('Project Drawing') || hasPermission('Construction Management')): ?>
                                    <li
                                        class="slide <?= isActive(['legal-sheet-lists', 'create-legal-sheet'], $current_route) ? 'active' : '' ?>">
                                        <a class="sidebar__menu-item <?= isActive(['legal-sheet-lists', 'create-legal-sheet'], $current_route) ? 'active' : '' ?>"
                                            href="<?= $base_url ?>/legal-sheet-lists">Project Approval Sheets</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (hasPermission('Project structure design') || ($_SESSION['user']['role_id'] ?? 0) == 12): ?>
                                    <li class="slide <?= isActive('structure-design', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('structure-design', $current_route) ?>"
                                            href="<?= $base_url ?>/structure-design">Project Structure Design</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission('Soil tests') || hasPermission('Construction Management')): ?>
                                    <li class="slide <?= isActive('con-soil-tests', $current_route) ?>">
                                        <a class="sidebar__menu-item <?= isActive('con-soil-tests', $current_route) ?>"
                                            href="<?= $base_url ?>/con-soil-tests">Soil Tests</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission('Construction Management')): ?>
                                    <li
                                        class="slide <?= isActive(['con-work-types', 'con-work-type-create', 'con-work-type-edit'], $current_route) ? 'active' : '' ?>">
                                        <a class="sidebar__menu-item <?= isActive(['con-work-types', 'con-work-type-create', 'con-work-type-edit'], $current_route) ? 'active' : '' ?>"
                                            href="<?= $base_url ?>/con-work-types">Work Types</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('Project Manage')): ?>
                        <li class="slide child2">
                            <a href="<?= $base_url ?>/project-lists"
                                class="sidebar__menu-item <?= isActive('project-lists', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="sidebar__menu-label">Project List</span>
                            </a>

                        </li>
                    <?php endif; ?>

                    <!-- Module 2.0: Initial Estimate Budgets -->
                    <?php if (hasPermission('Estimate Budget')): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-estimates', 'con-estimate-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-estimates', 'con-estimate-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-calculator"></i></div>
                                <span class="sidebar__menu-label">Initial Estimate</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-estimates"
                                        class="sidebar__menu-item <?= isActive('con-estimates', $current_route) ?>">Estimate
                                        List</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-estimate-create"
                                        class="sidebar__menu-item <?= isActive('con-estimate-create', $current_route) ?>">New
                                        Estimate</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- ====== Work Orders ====== -->
                    <?php if (hasPermission('Work Order')): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-work-orders', 'con-work-order-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-work-orders', 'con-work-order-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="bi bi-file-earmark-ruled"></i></div>
                                <span class="sidebar__menu-label">Work Orders</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide <?= isActive('con-work-orders', $current_route) ?>">
                                    <a href="<?= $base_url ?>/con-work-orders"
                                        class="sidebar__menu-item <?= isActive('con-work-orders', $current_route) ?>">
                                        <i class="bi bi-list-ul me-1"></i> All Work Orders
                                    </a>
                                </li>
                                <?php if (hasPermission('Construction Management') || hasPermission('Work Order')): ?>
                                    <li class="slide <?= isActive('con-work-order-create', $current_route) ?>">
                                        <a href="<?= $base_url ?>/con-work-order-create"
                                            class="sidebar__menu-item <?= isActive('con-work-order-create', $current_route) ?>">
                                            + New Work Order
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- Module 2.1: Working Plans — REMOVED (legacy system) -->
                    <!-- Module 2.2: Target Fixed / Site Operations — REMOVED (legacy system) -->

                    <!-- Module 2.2: Budget Extension Requests -->
                    <?php if (hasPermission('Budget Extend')): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-budget-extends', 'con-budget-extend-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-budget-extends', 'con-budget-extend-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-coins"></i></div>
                                <span class="sidebar__menu-label">Budget Adjustments</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-budget-extends"
                                        class="sidebar__menu-item <?= isActive('con-budget-extends', $current_route) ?>">Adjustment
                                        Requests</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-budget-extend-create"
                                        class="sidebar__menu-item <?= isActive('con-budget-extend-create', $current_route) ?>">New
                                        Request</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- ====== END Site Operations ====== -->

                    <?php if (hasPermission('Material Manage') || hasPermission('Estimator dashboard')): ?>
                        <!-- Module 3: Materials & Inventory -->
                        <li
                            class="slide has-sub <?= isActive(['con-materials', 'con-material-create', 'con-warehouses', 'con-warehouse-create', 'con-stocks'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-materials', 'con-material-create', 'con-warehouses', 'con-warehouse-create', 'con-stocks'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                <span class="sidebar__menu-label">Materials & Inventory</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-materials"
                                        class="sidebar__menu-item <?= isActive('con-materials', $current_route) ?>">Material
                                        Master</a>
                                </li>
                                <?php if (hasPermission('Material Manage')): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/con-material-create"
                                            class="sidebar__menu-item <?= isActive('con-material-create', $current_route) ?>">Add
                                            Material</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/con-warehouses"
                                            class="sidebar__menu-item <?= isActive('con-warehouses', $current_route) ?>">Warehouses</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/con-warehouse-create"
                                            class="sidebar__menu-item <?= isActive('con-warehouse-create', $current_route) ?>">Add
                                            Warehouse</a>
                                    </li>
                                <?php endif; ?>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-stocks"
                                        class="sidebar__menu-item <?= isActive('con-stocks', $current_route) ?>">Stock
                                        Levels</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('Purchase order') || in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 12, 15, 17, 22, 23, 31])): ?>
                        <!-- Module 5: Procurement -->
                        <li
                            class="slide has-sub <?= isActive(['con-purchase-requests', 'con-purchase-request-create', 'con-purchase-orders', 'con-wastage', 'con-wastage-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-purchase-requests', 'con-purchase-request-create', 'con-purchase-orders', 'con-wastage', 'con-wastage-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-cart-shopping"></i></div>
                                <span class="sidebar__menu-label">Procurement</span>
                                <?php if (($sidebarPendingPRCount + $sidebarPendingPOCount) > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-2 me-1"
                                        style="font-size:9.5px; padding: 2px 6px;"><?= ($sidebarPendingPRCount + $sidebarPendingPOCount) ?></span>
                                <?php endif; ?>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-purchase-requests"
                                        class="sidebar__menu-item <?= isActive('con-purchase-requests', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span>Purchase Requests</span>
                                        <?php if (!empty($sidebarPendingPRCount) && $sidebarPendingPRCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPRCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-purchase-request-create"
                                        class="sidebar__menu-item <?= isActive('con-purchase-request-create', $current_route) ?>">New
                                        PR</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-purchase-orders"
                                        class="sidebar__menu-item <?= isActive('con-purchase-orders', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span>Purchase Orders</span>
                                        <?php if (!empty($sidebarPendingPOCount) && $sidebarPendingPOCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPOCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                    <a href="<?= $GLOBALS['base_url'] ?>/con-wastage"
                                        class="sidebar__menu-item <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                        <i class="fal fa-recycle me-1"></i> Wastage Management
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('Vendor Manage') || in_array($_SESSION['user']['role_id'] ?? 0, [22])): ?>
                        <!-- Module 6: Vendors -->

                        <li
                            class="slide has-sub <?= isActive(['con-vendors', 'con-vendor-create', 'con-vendor-penalties', 'con-vendor-penalty-create', 'con-vendor-payment-requests', 'con-vendor-payment-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-vendors', 'con-vendor-create', 'con-vendor-penalties', 'con-vendor-penalty-create', 'con-vendor-payment-requests', 'con-vendor-payment-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-user-tie"></i></div>
                                <span class="sidebar__menu-label">Vendors</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>

                            <ul class="sidebar-menu child2" style="display:block;">

                                <li class="slide <?= isActive('con-vendors', $current_route) ? 'active' : '' ?>">
                                    <a href="<?= $base_url ?>/con-vendors"
                                        class="sidebar__menu-item <?= isActive('con-vendors', $current_route) ?>">Vendor
                                        List</a>
                                </li>
                                <li class="slide <?= isActive('con-vendor-create', $current_route) ? 'active' : '' ?>">
                                    <a href="<?= $base_url ?>/con-vendor-create"
                                        class="sidebar__menu-item <?= isActive('con-vendor-create', $current_route) ?>">Add
                                        Vendor</a>
                                </li>
                                <li
                                    class="slide <?= isActive(['con-vendor-penalties', 'con-vendor-penalty-create'], $current_route) ? 'active' : '' ?>">
                                    <a href="<?= $base_url ?>/con-vendor-penalties"
                                        class="sidebar__menu-item <?= isActive(['con-vendor-penalties', 'con-vendor-penalty-create'], $current_route) ?>">Vendor
                                        Penalties</a>
                                </li>
                                <li
                                    class="slide <?= isActive('con-vendor-payment-requests', $current_route) ? 'active' : '' ?>">
                                    <a href="<?= $base_url ?>/con-vendor-payment-requests"
                                        class="sidebar__menu-item <?= isActive('con-vendor-payment-requests', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-wallet2 me-1"></i> Payment Requests</span>
                                        <?php if (!empty($sidebarPendingVprCount) && $sidebarPendingVprCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingVprCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide <?= isActive('con-vendor-payment-create', $current_route) ? 'active' : '' ?>">
                                    <a href="<?= $base_url ?>/con-vendor-payment-create"
                                        class="sidebar__menu-item <?= isActive('con-vendor-payment-create', $current_route) ?>">
                                        + New Payment Request
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Module 7:Operation And  Receiving & QC -->
                    <?php if (hasPermission('Material Issue')): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-material-receivings', 'con-stocks', 'con-material-returns', 'con-wastage', 'con-wastage-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-material-receivings', 'con-stocks', 'con-material-returns', 'con-wastage', 'con-wastage-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-box-open"></i></div>
                                <span class="sidebar__menu-label">Receiving & QC</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-material-receivings"
                                        class="sidebar__menu-item <?= isActive('con-material-receivings', $current_route) ?>">Material
                                        Receiving</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-material-returns"
                                        class="sidebar__menu-item <?= isActive('con-material-returns', $current_route) ?>">Material
                                        Returns</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-stocks"
                                        class="sidebar__menu-item <?= isActive('con-stocks', $current_route) ?>">Stock
                                        Levels</a>
                                </li>
                            </ul>
                        </li>

                        <!-- Module 8: Material Issues (Stock OUT) -->
                        <li
                            class="slide has-sub <?= isActive(['con-material-issues', 'con-material-issue-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-material-issues', 'con-material-issue-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
                                <span class="sidebar__menu-label">Material Issues</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-material-issues"
                                        class="sidebar__menu-item <?= isActive('con-material-issues', $current_route) ?>">Issue
                                        List</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-material-issue-create"
                                        class="sidebar__menu-item <?= isActive('con-material-issue-create', $current_route) ?>">New
                                        Issue</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-wastage"
                                        class="sidebar__menu-item <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">Wastage
                                        Management</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (hasPermission('site engineer dashboard')): ?>
                        <li class="slide <?= isActive('con-project-plan-targets', $current_route) ?>">
                            <a href="<?= $base_url ?>/con-project-plan-targets"
                                class="sidebar__menu-item <?= isActive('con-project-plan-targets', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-bullseye-arrow"></i></div>
                                <span class="sidebar__menu-label">Project Plan & BOQ</span>
                            </a>
                        </li>
                        <li class="slide <?= isActive(['con-weekly-reports', 'con-weekly-report'], $current_route) ?>">
                            <a href="<?= $base_url ?>/con-weekly-reports"
                                class="sidebar__menu-item <?= isActive(['con-weekly-reports', 'con-weekly-report'], $current_route) ?>">
                                <div class="side-menu__icon"><i class="bi bi-calendar-week"></i></div>
                                <span class="sidebar__menu-label">Weekly Reports</span>
                            </a>
                        </li>
                        <li class="slide <?= isActive('con-contractor-bills', $current_route) ?>">
                            <a href="<?= $base_url ?>/con-contractor-bills"
                                class="sidebar__menu-item <?= isActive('con-contractor-bills', $current_route) ?>">
                                <i class="bi bi-receipt me-1"></i>Contractor Bills
                                <?php if (isset($conPendingBillsCount) && $conPendingBillsCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-2"><?= $conPendingBillsCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ====== Operators & Bills (Batch 2 NEW) ====== -->
                    <?php if (hasPermission('Operation manage')): ?>
                        <!-- Module 2: Operators / Contractors -->
                        <li
                            class="slide has-sub <?= isActive(['con-contractors', 'con-contractor-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-contractors', 'con-contractor-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-person-digging"></i></div>
                                <span class="sidebar__menu-label">Contractors</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-contractors"
                                        class="sidebar__menu-item <?= isActive('con-contractors', $current_route) ?>">Constractor
                                        List</a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-contractor-create"
                                        class="sidebar__menu-item <?= isActive('con-contractor-create', $current_route) ?>">Add
                                        contractor</a>
                                </li>
                            </ul>
                        </li>


                    <?php endif; ?>



                    <!-- ====== Operators & Bills (Batch 2 NEW) ====== -->
                    <?php if (hasPermission('Contractor Bill') || hasPermission('QC & Internal Audit') || hasPermission('Operation manage') || in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 12, 15, 17, 20, 22, 23, 24, 25, 30, 31, 32, 33])): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-contractor-bills', 'con-contractor-bill-create', 'con-fund-requests', 'con-fund-request-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-contractor-bills', 'con-contractor-bill-create', 'con-fund-requests', 'con-fund-request-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                                <span class="sidebar__menu-label">Bills & Funds</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-contractor-bills"
                                        class="sidebar__menu-item <?= isActive('con-contractor-bills', $current_route) ?>">
                                        <i class="bi bi-receipt me-1"></i> Contractor Bills
                                        <?php if (isset($conPendingBillsCount) && $conPendingBillsCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-2"><?= $conPendingBillsCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-contractor-bill-create"
                                        class="sidebar__menu-item <?= isActive('con-contractor-bill-create', $current_route) ?>">
                                        + New Bill
                                    </a>
                                </li>

                                <!-- Labor Requisition shortcut inside Bills menu -->
                                <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 12, 15, 22])): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/con-labor-requisitions"
                                            class="sidebar__menu-item <?= isActive('con-labor-requisitions', $current_route) ?>">
                                            <i class="fa-solid fa-hard-hat me-1"></i> Labor Requisitions
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- <li class="slide">
                            <a href="<= $base_url ?>/con-fund-requests" class="sidebar__menu-item <?= isActive('con-fund-requests', $current_route) ?>">
                                <i class="bi bi-cash-coin me-1"></i> Fund Requests
                            </a>
                        </li>
                        <li class="slide">
                            <a href="<= $base_url ?>/con-fund-request-create" class="sidebar__menu-item <?= isActive('con-fund-request-create', $current_route) ?>">
                                + New Fund Request
                            </a>
                        </li> -->
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- ====== Labor Requisitions ====== -->
                    <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 12, 15, 22])): ?>
                        <li
                            class="slide has-sub <?= isActive(['con-labor-requisitions', 'con-labor-requisition', 'con-labor-requisition-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-labor-requisitions', 'con-labor-requisition', 'con-labor-requisition-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-hard-hat"></i></div>
                                <span class="sidebar__menu-label">Labor Requisition</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-labor-requisitions"
                                        class="sidebar__menu-item <?= isActive('con-labor-requisitions', $current_route) ?>">
                                        <i class="bi bi-list-ul me-1"></i> All Requisitions
                                    </a>
                                </li>
                                <?php if (in_array((int) ($_SESSION['user']['role_id'] ?? 0), [1, 3, 12, 15, 22])): ?>
                                    <li class="slide">
                                        <a href="<?= $base_url ?>/con-labor-requisition-create"
                                            class="sidebar__menu-item <?= isActive('con-labor-requisition-create', $current_route) ?>">
                                            <i class="bi bi-plus-circle me-1"></i> + New Requisition
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- ══ Weekly Progress Reports (Phase-Based) ════════════════════ -->
                    <?php if (hasPermission('Construction Management') || hasPermission('site engineer dashboard') || hasPermission('QC Dashboard') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])): ?>
                        <li class="d-none slide <?= isActive(['con-weekly-reports', 'con-weekly-report'], $current_route) ?>">
                            <a href="<?= $base_url ?>/con-weekly-reports"
                                class="sidebar__menu-item <?= isActive(['con-weekly-reports', 'con-weekly-report'], $current_route) ?>">
                                <div class="side-menu__icon"><i class="bi bi-calendar-week"></i></div>
                                <span class="sidebar__menu-label">Weekly Reports</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- ====== END Operators & Bills ====== -->

                    <?php if (hasPermission('Bill & Payments')): ?>
                        <!-- ====== Finance & Accounts (Batch 3 NEW) ====== -->

                        <li
                            class="slide has-sub <?= isActive(['con-transactions', 'con-pending-payments', 'create-cashout-request', 'con-wastage', 'con-wastage-create'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-transactions', 'con-pending-payments', 'create-cashout-request', 'con-wastage', 'con-wastage-create'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-wallet"></i></div>
                                <span class="sidebar__menu-label">Bill & Payments</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-transactions"
                                        class="sidebar__menu-item <?= isActive('con-transactions', $current_route) ?>">
                                        <i class="bi bi-journal-check me-1"></i> Transactions Ledger
                                    </a>
                                </li>

                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-pending-payments"
                                        class="sidebar__menu-item <?= isActive('con-pending-payments', $current_route) ?>">
                                        <i class="bi bi-clock me-1"></i> Pending Payments
                                    </a>
                                </li>


                                <li class="slide <?= isActive('create-cashout-request', $current_route) ?>">
                                    <a href="<?= $base_url ?>/create-cashout-request"
                                        class="sidebar__menu-item <?= isActive('create-cashout-request', $current_route) ?>">Create
                                        Cashout Request </a>
                                </li>

                                <li class="slide <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                    <a href="<?= $GLOBALS['base_url'] ?>/con-wastage"
                                        class="sidebar__menu-item <?= isActive(['con-wastage', 'con-wastage-create'], $current_route) ?>">
                                        <i class="fal fa-recycle me-1"></i> Wastage Management
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- ====== END Finance & Accounts ====== -->

                    <!-- ====== Land Department (NEW) ====== -->
                    <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 14): ?>
                        <li class="sidebar__menu-category d-none">
                            <span class="category-name">Land Department</span>
                        </li>
                        <li
                            class="slide has-sub d-none <?= isActive(['con-land-acquisitions', 'con-land-requisitions', 'con-land-reports'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-land-acquisitions', 'con-land-requisitions', 'con-land-reports'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-earth-americas"></i></div>
                                <span class="sidebar__menu-label">Land Portfolio</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-land-acquisitions"
                                        class="sidebar__menu-item <?= isActive('con-land-acquisitions', $current_route) ?>">
                                        <i class="bi bi-geo-alt me-1"></i> Land Acquisitions
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-land-requisitions"
                                        class="sidebar__menu-item <?= isActive('con-land-requisitions', $current_route) ?>">
                                        <i class="bi bi-cash-coin me-1"></i> Fund Requisitions
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-land-reports"
                                        class="sidebar__menu-item <?= isActive('con-land-reports', $current_route) ?>">
                                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Monthly Reports
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3])): ?>
                        <li class="slide <?= isActive('approval-requests', $current_route) ?>">
                            <a href="<?= $base_url ?>/approval-requests"
                                class="sidebar__menu-item <?= isActive('approval-requests', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-check-double"></i></div>
                                <span class="sidebar__menu-label">Approval Requests</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('QC & Internal Audit') || strtolower($_SESSION['user']['role_name'] ?? '') === 'qc & internal audit' || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 24, 25])): ?>
                        <!-- ====== Internal Audit Section ====== -->
                        <li class="sidebar__menu-category">
                            <span class="category-name">Internal Audit</span>
                        </li>
                        <li
                            class="slide has-sub <?= isActive(['qc-audit-dashboard', 'qc-requests', 'qc-advanced-audit'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['qc-audit-dashboard', 'qc-requests', 'qc-advanced-audit'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="bi bi-shield-check"></i></div>
                                <span class="sidebar__menu-label">Internal Audit</span>
                                <?php if (!empty($sidebarPendingPOCount) && $sidebarPendingPOCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-2 me-1"
                                        style="font-size:9.5px; padding: 2px 6px;"><?= $sidebarPendingPOCount ?></span>
                                <?php endif; ?>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/qc-audit-dashboard"
                                        class="sidebar__menu-item <?= isActive('qc-audit-dashboard', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-speedometer2 me-1"></i> Dashboard</span>
                                        <?php if (!empty($sidebarPendingPOCount) && $sidebarPendingPOCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPOCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/qc-advanced-audit"
                                        class="sidebar__menu-item <?= isActive('qc-advanced-audit', $current_route) ?>">
                                        <i class="bi bi-filter-square me-1"></i> Advanced Audit
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/qc-requests"
                                        class="sidebar__menu-item <?= isActive('qc-requests', $current_route) ?>">
                                        <i class="bi bi-check2-square me-1"></i> QC Approvals
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-purchase-orders"
                                        class="sidebar__menu-item <?= isActive('con-purchase-orders', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-cart me-1"></i> Purchase Orders</span>
                                        <?php if (!empty($sidebarPendingPOCount) && $sidebarPendingPOCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPOCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-vendor-payment-requests"
                                        class="sidebar__menu-item <?= isActive('con-vendor-payment-requests', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-cash-stack me-1"></i> Vendor Payments</span>
                                        <?php if (!empty($sidebarPendingVprCount) && $sidebarPendingVprCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingVprCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-contractor-bills"
                                        class="sidebar__menu-item <?= isActive('con-contractor-bills', $current_route) ?> d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-receipt me-1"></i> Contractor Bills</span>
                                        <?php if (isset($conPendingBillsCount) && $conPendingBillsCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill me-1"
                                                style="font-size:10px; padding: 2px 6px;"><?= $conPendingBillsCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission("Security Operations")): ?>
                        <!-- ====== Site IT & Security (NEW) ====== -->
                        <li
                            class="slide has-sub <?= isActive(['con-security-systems', 'con-security-equipments', 'con-security-logs', 'con-security-incidents'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['con-security-systems', 'con-security-equipments', 'con-security-logs', 'con-security-incidents'], $current_route) ? 'menu-open active' : '' ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-shield-halved"></i></div>
                                <span class="sidebar__menu-label">Site IT & Security</span>
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-security-systems"
                                        class="sidebar__menu-item <?= isActive('con-security-systems', $current_route) ?>">
                                        <i class="bi bi-diagram-3 me-1"></i> Security Systems
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-security-equipments"
                                        class="sidebar__menu-item <?= isActive('con-security-equipments', $current_route) ?>">
                                        <i class="bi bi-webcam me-1"></i> Equipment List
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-security-equipments/create"
                                        class="sidebar__menu-item <?= isActive('con-security-equipments/create', $current_route) ?>">
                                        <i class="bi bi-plus-circle me-1"></i> Add Equipment
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-security-logs"
                                        class="sidebar__menu-item <?= isActive('con-security-logs', $current_route) ?>">
                                        <i class="bi bi-journal-text me-1"></i> Daily Logs
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="<?= $base_url ?>/con-security-incidents"
                                        class="sidebar__menu-item <?= isActive('con-security-incidents', $current_route) ?>">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Incidents
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ====== END Site IT & Security ====== -->
                    <?php endif; ?>


                    <?php if (hasPermission("Manpower Requisition") || hasPermission('Architect Design') || ($_SESSION['user']['role_id'] ?? 0) == 18): ?>
                        <!-- Dashboard -->
                        <li class="slide <?= isActive('requisition-lists', $current_route) ?>">
                            <a href="<?= $base_url ?>/requisition-lists"
                                class="sidebar__menu-item <?= isActive('requisition-lists', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <span class="sidebar__menu-label">Manpower Requisition</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- FOR Task Manage  -->
                    <li class="slide d-none <?= isActive('email-station', $current_route) ?>">
                        <a href="<?= $base_url ?>/email-station"
                            class="sidebar__menu-item <?= isActive('email-station', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-envelope"></i></div>
                            <span class="sidebar__menu-label">Email Station</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('asset-extra-purchases', $current_route) ?>">
                        <a href="<?= $base_url ?>/asset-extra-purchases"
                            class="sidebar__menu-item <?= isActive('asset-extra-purchases', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-cart-shopping"></i></div>
                            <span class="sidebar__menu-label">Office Procurement Request</span>
                        </a>
                    </li>
                    <li class="slide ">
                        <a href="<?= $base_url ?>/task-sheets"
                            class="sidebar__menu-item <?= isActive('task-sheets', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-user-group"></i></div>
                            <span class="sidebar__menu-label">My Task Sheets</span>
                        </a>
                    </li>
                    <?php if (hasPermission("task manage")): ?>

                        <li
                            class="slide has-sub <?= isActive(['create-task', 'task-lists'], $current_route) ? 'active' : '' ?>">
                            <a href="javascript:void(0);"
                                class="sidebar__menu-item <?= isActive(['create-task', 'task-lists'], $current_route) ? 'menu-open active' : '' ?>">
                                <i class="fa-regular fa-angle-down side-menu__angle"></i>
                                <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                                <span class="sidebar__menu-label">Task Manage</span>
                                <?php if ($pendingTasksCount > 0): ?>
                                    <span class="badge bg-danger ms-2 rounded-pill"
                                        style="font-size: 10px; padding: 2px 6px; line-height: 1;"><?= $pendingTasksCount ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="sidebar-menu child2" style="display:block;">
                                <li class="slide">
                                    <a href="<?= $base_url ?>/task-dashboard"
                                        class="sidebar__menu-item  <?= isActive('task-dashboard', $current_route) ?>">
                                        <span class="sidebar__menu-label">Task Dashbaord</span>
                                        <?php if ($pendingTasksCount > 0): ?>
                                            <span class="badge bg-danger ms-2 rounded-pill"
                                                style="font-size: 10px; padding: 2px 6px; line-height: 1;"><?= $pendingTasksCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="slide <?= isActive('create-task', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('create-task', $current_route) ?>"
                                        href="<?= $base_url ?>/create-task">Add Task</a>
                                </li>
                                <li class="slide <?= isActive('task-lists', $current_route) ?>">
                                    <a class="sidebar__menu-item <?= isActive('task-lists', $current_route) ?>"
                                        href="<?= $base_url ?>/task-lists">Task List</a>
                                </li>
                            </ul>
                        </li>

                    <?php endif; ?>
                    <!-- FOR Task Manage  -->
                    <!-- ====== BATCH IMPORTS ====== -->
                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3])): // Only Admins can import ?>
                        <li class="slide d-none">
                            <a href="<?= $GLOBALS['base_url'] ?>/con-batch2-import"
                                class="sidebar__menu-item <?= isActive('con-batch2-import', $current_route) ?>">
                                <div class="side-menu__icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                                <span class="sidebar__menu-label">Batch CSV Import</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- ====== ASSET MANAGEMENT ====== -->
                    <?php if (hasPermission('Asset Management') || hasPermission('QC & Internal Audit') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 32])): ?>
                        <li class="slide <?= isActive(['office-assets', 'asset-products'], $current_route) ?>">
                            <a href="<?= $base_url ?>/office-assets"
                                class="sidebar__menu-item <?= isActive(['office-assets', 'asset-products'], $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                <span class="sidebar__menu-label">Asset Register</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [18, 16, 21])): ?>
                        <li class="slide <?= isActive('con-monthly-work-report', $current_route) ?>">
                            <a href="<?= $base_url ?>/con-monthly-work-report"
                                class="sidebar__menu-item <?= isActive('con-monthly-work-report', $current_route) ?>">
                                <div class="side-menu__icon"><i class="fa-solid fa-file-contract"></i></div>
                                <span class="sidebar__menu-label">Monthly Report</span>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
                <!-- ==================== END PHASE 2 ==================== -->

                <!-- Project Other Costs -->
                <li class="slide <?= isActive('project-other-costs', $current_route) ?>">
                    <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs"
                        class="sidebar__menu-item <?= isActive('project-other-costs', $current_route) ?>">
                        <div class="side-menu__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <span class="sidebar__menu-label">Project Other Costs</span>
                        <?php if ($sidebarPendingPOCostsCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-2 me-1" style="font-size:10px; padding: 2px 6px;"><?= $sidebarPendingPOCostsCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Fund Requisitions -->
                <li class="slide <?= isActive('fund-requisitions', $current_route) ?>">
                    <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions"
                        class="sidebar__menu-item <?= isActive('fund-requisitions', $current_route) ?>">
                        <div class="side-menu__icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <span class="sidebar__menu-label">Fund Requisitions</span>
                        <?php if (!empty($pendingFundReqTotalCount)): ?>
                            <span class="badge bg-warning text-dark rounded-pill ms-auto"
                                style="font-size: 10px; padding: 2px 6px;"
                                title="Pending Actions: <?= $pendingFundReqDeptCount ?> Dept Head, <?= $pendingFundReqMgmtCount ?> Mgmt, <?= $pendingFundReqAccountsCount ?> Accounts">
                                <?= $pendingFundReqTotalCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if (($_SESSION['user']['role_id'] ?? 0) == 33): ?>
                    <li class="slide <?= isActive('tutorial-video', $current_route) ?>">
                        <a href="<?= $GLOBALS['base_url'] ?>/tutorial-video" target="_blank" rel="noopener noreferrer"
                            class="sidebar__menu-item <?= isActive('tutorial-video', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-circle-play"></i></div>
                            <span class="sidebar__menu-label">Tutorial Video (Management)</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('tutorial-video-sales', $current_route) ?>">
                        <a href="<?= $GLOBALS['base_url'] ?>/tutorial-video-sales" target="_blank" rel="noopener noreferrer"
                            class="sidebar__menu-item <?= isActive('tutorial-video-sales', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-circle-play"></i></div>
                            <span class="sidebar__menu-label">Tutorial Video (Sales Head)</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="slide <?= isActive('tutorial-video', $current_route) ?>">
                        <a href="<?= $GLOBALS['base_url'] ?>/tutorial-video" target="_blank" rel="noopener noreferrer"
                            class="sidebar__menu-item <?= isActive('tutorial-video', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-circle-play"></i></div>
                            <span class="sidebar__menu-label">Tutorial Video</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="slide ">
                    <a class="sidebar__menu-item " href="<?= $GLOBALS['base_url'] ?>/logout"><svg width="18" height="18"
                            viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_643_343)">
                                <path
                                    d="M17.4368 8.43771H10.312C10.0015 8.43771 9.74951 8.18572 9.74951 7.87523C9.74951 7.56474 10.0015 7.31274 10.312 7.31274H17.4368C17.7473 7.31274 17.9993 7.56474 17.9993 7.87523C17.9993 8.18572 17.7473 8.43771 17.4368 8.43771Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M14.6244 11.2502C14.4803 11.2502 14.3364 11.1954 14.2268 11.0852C14.0071 10.8654 14.0071 10.5091 14.2268 10.2894L16.6418 7.87457L14.2268 5.45958C14.0071 5.23986 14.0071 4.88364 14.2268 4.66392C14.4467 4.44406 14.8029 4.44406 15.0226 4.66392L17.835 7.47633C18.0547 7.69605 18.0547 8.05227 17.835 8.27199L15.0226 11.0844C14.9123 11.1954 14.7684 11.2502 14.6244 11.2502Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M5.99986 18.0002C5.83932 18.0002 5.68703 17.9776 5.53488 17.9304L1.02142 16.4267C0.407305 16.2122 0 15.64 0 15.0003V1.50073C0 0.673487 0.672754 0.000732422 1.5 0.000732422C1.66039 0.000732422 1.81269 0.0232537 1.96498 0.0704934L6.4783 1.5742C7.09255 1.7887 7.49972 2.36093 7.49972 3.00059V16.5002C7.49972 17.3274 6.8271 18.0002 5.99986 18.0002ZM1.5 1.1257C1.29374 1.1257 1.12496 1.29447 1.12496 1.50073V15.0003C1.12496 15.16 1.23222 15.3085 1.3852 15.3617L5.8775 16.8587C5.90977 16.8692 5.95179 16.8752 5.99986 16.8752C6.20612 16.8752 6.37475 16.7064 6.37475 16.5002V3.00059C6.37475 2.84088 6.2675 2.69244 6.11452 2.63915L1.62222 1.14218C1.58995 1.13174 1.54793 1.1257 1.5 1.1257Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M11.4371 6.00035C11.1266 6.00035 10.8746 5.74836 10.8746 5.43786V2.06297C10.8746 1.54622 10.454 1.12545 9.93722 1.12545H1.49998C1.18949 1.12545 0.9375 0.873462 0.9375 0.562971C0.9375 0.252479 1.18949 0.000488281 1.49998 0.000488281H9.93722C11.075 0.000488281 11.9996 0.925234 11.9996 2.06297V5.43786C11.9996 5.74836 11.7476 6.00035 11.4371 6.00035Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M9.93699 15.7501H6.93699C6.6265 15.7501 6.37451 15.4981 6.37451 15.1876C6.37451 14.8771 6.6265 14.6251 6.93699 14.6251H9.93699C10.4537 14.6251 10.8744 14.2044 10.8744 13.6876V10.3127C10.8744 10.0022 11.1264 9.75024 11.4369 9.75024C11.7473 9.75024 11.9993 10.0022 11.9993 10.3127V13.6876C11.9993 14.8254 11.0747 15.7501 9.93699 15.7501Z"
                                    fill="#7A7A7A" />
                            </g>
                            <defs>
                                <clipPath id="clip0_643_343">
                                    <rect width="18" height="18" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                        <span class="sidebar__menu-label ms-2">Log Out</span> </a>
                </li>

            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>

        </nav>

    </div>
</div>
<div class="app__offcanvas-overlay"></div>
<!--app-sidebar-end -->