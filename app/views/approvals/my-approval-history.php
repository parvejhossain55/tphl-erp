<?php
$baseFilterQs = "&module=" . urlencode($searchModule ?? '') . "&request_no=" . urlencode($searchReqNo ?? '') . "&department_id=" . urlencode($searchDept ?? 0);
$fullQs = $baseFilterQs . "&per_page=" . urlencode($perPage ?? '20');
?>

<div class="container-fluid py-4">
    <!-- Screen Header -->
    <div class="row mb-4 align-items-center d-print-none">
        <div class="col-md-6 col-12 mb-2 mb-md-0">
            <h4 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                <span><?php echo $isAccounts ? 'My Payment & Approval History' : 'My Approval History'; ?></span>
                <span class="badge bg-primary px-2 py-1" style="font-size:13px;"><?php echo $totalItems; ?> Total</span>
                <?php if($totalItems > 0): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:12px;">
                        My Total: ৳<?php echo number_format($totalMyAmount, 2); ?>
                    </span>
                <?php endif; ?>
            </h4>
            <small class="text-muted">
                Track all approval requests you have actioned, your approved amounts, and final approved amounts.
            </small>
        </div>
        <div class="col-md-6 col-12 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
            <!-- View All Toggle -->
            <?php if (!empty($isViewAll)): ?>
                <a href="<?php echo $GLOBALS['base_url']; ?>/my-approval-history?page=1&per_page=20<?php echo $baseFilterQs; ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="fas fa-pager me-1"></i> Paginated View (20)
                </a>
            <?php else: ?>
                <a href="<?php echo $GLOBALS['base_url']; ?>/my-approval-history?page=1&per_page=all<?php echo $baseFilterQs; ?>" class="btn btn-outline-primary btn-sm shadow-sm" title="View all rows together on a single page">
                    <i class="fas fa-list me-1"></i> View All Rows (<?php echo $totalItems; ?>)
                </a>
            <?php endif; ?>

            <!-- Print Button -->
            <button type="button" class="btn btn-outline-dark btn-sm shadow-sm" onclick="window.print()" title="Print approval history report">
                <i class="fas fa-print me-1"></i> Print Report
            </button>

            <!-- Back to Approvals -->
            <a href="<?php echo $GLOBALS['base_url']; ?>/all-approval-requests" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Approvals
            </a>
        </div>
    </div>

    <!-- Print Only Header -->
    <div class="d-none d-print-block print-header mb-4">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-2">
            <div>
                <h3 class="fw-bold mb-0" style="color: #006837;">THE PREMIUM HOMES LIMITED</h3>
                <h5 class="text-dark mb-1">My Approval & Payment History Report</h5>
                <div class="text-muted small">
                    Actioned By: <strong><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></strong> 
                    (<?php echo htmlspecialchars($_SESSION['user']['role_name'] ?? 'Approver'); ?>) 
                    &bull; Printed on: <strong><?php echo date('d M Y, h:i A'); ?></strong>
                </div>
            </div>
            <div class="text-end">
                <span class="badge border border-dark text-dark px-3 py-2 fw-bold" style="font-size: 13px;">
                    Total Requests: <?php echo $totalItems; ?>
                </span>
                <?php if(!empty($searchModule) || !empty($searchReqNo) || !empty($searchDept)): ?>
                    <div class="small text-muted mt-1">
                        Filter: <?php echo htmlspecialchars($searchModule ?: 'All Modules'); ?>
                        <?php echo $searchReqNo ? ' | Req #' . htmlspecialchars($searchReqNo) : ''; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4 shadow-sm border-0 d-print-none">
        <div class="card-body py-3">
            <form method="GET" action="<?php echo $GLOBALS['base_url']; ?>/my-approval-history" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">REQUEST #</label>
                    <input type="text" name="request_no" class="form-control form-control-sm" placeholder="e.g. 03337" value="<?php echo htmlspecialchars($searchReqNo ?? ''); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">MODULE / SOURCE TYPE</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="all">All Approval Source Types</option>
                        <option value="Fund Requisition" <?php echo ($searchModule == 'Fund Requisition') ? 'selected' : ''; ?>>Fund Requisitions / Petty Cash</option>
                        <option value="Vendor Payment" <?php echo ($searchModule == 'Vendor Payment') ? 'selected' : ''; ?>>Vendor Payment Requests (Construction)</option>
                        <option value="Contractor Bill" <?php echo ($searchModule == 'Contractor Bill') ? 'selected' : ''; ?>>Contractor Bills (Construction)</option>
                        <option value="Labor Requisition" <?php echo ($searchModule == 'Labor Requisition') ? 'selected' : ''; ?>>Labor Bill by SE (Construction)</option>
                        <option value="Cashout Request" <?php echo ($searchModule == 'Cashout Request') ? 'selected' : ''; ?>>Cashout Requests (Accounts)</option>
                        <option value="Expense Adjustment" <?php echo ($searchModule == 'Expense Adjustment') ? 'selected' : ''; ?>>Expense Adjustments</option>
                        <option value="Project Other Cost" <?php echo ($searchModule == 'Project Other Cost') ? 'selected' : ''; ?>>Project Other Costs (Construction)</option>
                        <option value="Project Extra Asset" <?php echo ($searchModule == 'Project Extra Asset') ? 'selected' : ''; ?>>Office Procurement Requests</option>
                        <option value="Land Fund Requisition" <?php echo ($searchModule == 'Land Fund Requisition') ? 'selected' : ''; ?>>Land Fund Requisitions</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">DEPARTMENT FILTER</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="0">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo (isset($searchDept) && $searchDept == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">SHOW ROWS</label>
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="20" <?php echo ($perPage == '20') ? 'selected' : ''; ?>>20 per page</option>
                        <option value="50" <?php echo ($perPage == '50') ? 'selected' : ''; ?>>50 per page</option>
                        <option value="100" <?php echo ($perPage == '100') ? 'selected' : ''; ?>>100 per page</option>
                        <option value="all" <?php echo (!empty($isViewAll)) ? 'selected' : ''; ?>>All Rows (View All)</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search me-1"></i> Search</button>
                    <?php if(!empty($searchModule) || !empty($searchReqNo) || !empty($searchDept) || !empty($isViewAll)): ?>
                        <a href="<?php echo $GLOBALS['base_url']; ?>/my-approval-history" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- History Table Card -->
    <div class="card shadow-sm border-0 print-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 print-table">
                    <thead class="table-light">
                        <tr>
                            <th width="18%">Module & Request</th>
                            <th width="22%">Requested By & When</th>
                            <th width="18%">Previous Approver (Before Me)</th>
                            <th width="21%">My Action & Amount</th>
                            <th width="21%">Last Approved Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    No approval history found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($history as $item): ?>
                                <tr>
                                    <!-- 1. Module & Request -->
                                    <td>
                                        <span class="badge bg-primary mb-1"><?php echo htmlspecialchars($item['module_name']); ?></span><br>
                                        <?php
                                        $historyReqCode = '#' . $item['request_id'];
                                        $modName = strtolower(trim($item['module_name'] ?? ''));
                                        if (strpos($modName, 'fund req') !== false) {
                                            $historyReqCode = 'REQ-' . $item['request_id'];
                                        } elseif (strpos($modName, 'contractor') !== false) {
                                            $historyReqCode = 'CB-' . $item['request_id'];
                                        } elseif (strpos($modName, 'vendor') !== false) {
                                            $historyReqCode = 'VPR-' . $item['request_id'];
                                        } elseif (strpos($modName, 'labor') !== false) {
                                            $historyReqCode = 'LR-' . $item['request_id'];
                                        } elseif (strpos($modName, 'other cost') !== false) {
                                            $historyReqCode = 'POC-' . $item['request_id'];
                                        } elseif (strpos($modName, 'asset') !== false || strpos($modName, 'procurement') !== false) {
                                            $historyReqCode = 'PR-' . $item['request_id'];
                                        } elseif (strpos($modName, 'land') !== false) {
                                            $historyReqCode = 'LFR-' . $item['request_id'];
                                        } elseif (strpos($modName, 'cashout') !== false) {
                                            $historyReqCode = 'CSH-' . $item['request_id'];
                                        } elseif (strpos($modName, 'expense') !== false || strpos($modName, 'adjustment') !== false) {
                                            $historyReqCode = 'ADJ-' . $item['request_id'];
                                        } elseif (strpos($modName, 'refund') !== false) {
                                            $historyReqCode = 'REF-' . $item['request_id'];
                                        } elseif (strpos($modName, 'salary') !== false || strpos($modName, 'payslip') !== false) {
                                            $historyReqCode = 'PAY-' . $item['request_id'];
                                        }
                                        ?>
                                        <small class="text-dark fw-bold"><?= $historyReqCode ?></small><br>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width:220px;" title="<?php echo htmlspecialchars($item['request_title']); ?>">
                                            <?php echo htmlspecialchars($item['request_title']); ?>
                                        </small>
                                        <?php if (!empty($item['item_status'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-secondary border extra-small" style="font-size:9.5px;">
                                                    Status: <?php echo ucfirst(str_replace('_', ' ', $item['item_status'])); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 2. Requested By & When -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle text-center me-2 d-print-none" style="width:30px;height:30px;line-height:30px;">
                                                <i class="fas fa-user text-secondary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold d-block text-sm text-dark"><?php echo htmlspecialchars($item['requested_by_name']); ?></span>
                                                <small class="text-muted d-block"><?php echo date('d M Y, h:i A', strtotime($item['created_at'])); ?></small>
                                                <?php if(!empty($item['req_amount']) && (float)$item['req_amount'] > 0): ?>
                                                    <span class="badge bg-light text-muted border mt-1" style="font-size:10px;">
                                                        Req: ৳<?php echo number_format($item['req_amount'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 3. Previous Approver (Before Me) -->
                                    <td>
                                        <?php if($item['prev_approver_name'] === 'None'): ?>
                                            <span class="badge bg-light text-dark border">First Approver</span>
                                        <?php else: ?>
                                            <span class="fw-bold d-block text-sm text-dark"><?php echo htmlspecialchars($item['prev_approver_name']); ?></span>
                                            <small class="text-muted d-block">
                                                <i class="far fa-clock"></i> 
                                                <?php echo $item['prev_approved_at'] ? date('d M Y, h:i A', strtotime($item['prev_approved_at'])) : 'Unknown'; ?>
                                            </small>
                                            <?php if(!empty($item['prev_role'])): ?>
                                                <small class="text-info" style="font-size:10px;">Role: <?php echo htmlspecialchars(str_replace('_', ' ', $item['prev_role'])); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 4. My Action & Amount ("ami koto taka korechi") -->
                                    <td>
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge bg-success">
                                                <?php echo $item['my_role'] === 'Accounts' ? 'Paid / Disbursed' : 'Approved'; ?>
                                            </span>
                                            <span class="badge bg-light text-dark border" style="font-size:10px;">
                                                Role: <?php echo htmlspecialchars(str_replace('_', ' ', $item['my_role'])); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- My Approved Amount -->
                                        <div class="fw-bold text-success" style="font-size:14px;">
                                            ৳<?php echo number_format($item['my_approved_amount'] ?? 0, 2); ?>
                                        </div>

                                        <small class="text-muted d-block">
                                            <i class="far fa-calendar-check me-1"></i><?php echo date('d M Y, h:i A', strtotime($item['my_action_time'])); ?>
                                        </small>

                                        <?php 
                                            // Calculate delay from previous to me
                                            if(!empty($item['prev_approved_at'])) {
                                                $prevTime = strtotime($item['prev_approved_at']);
                                                $myTime = strtotime($item['my_action_time']);
                                                if($myTime > $prevTime) {
                                                    $diff = $myTime - $prevTime;
                                                    $hours = floor($diff / 3600);
                                                    $mins = floor(($diff % 3600) / 60);
                                                    $delayStr = "";
                                                    if($hours > 0) $delayStr .= $hours . "h ";
                                                    if($mins > 0) $delayStr .= $mins . "m";
                                                    if($delayStr) {
                                                        echo "<small class='text-danger d-block' style='font-size:10px;'><i class='fas fa-stopwatch'></i> Delay: $delayStr</small>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </td>

                                    <!-- 5. Last Approved Amount ("last approved er time e tini last approved amount") -->
                                    <td>
                                        <!-- Last Approved Amount -->
                                        <div class="fw-bold text-primary" style="font-size:14px;">
                                            ৳<?php echo number_format($item['last_approved_amount'] ?? 0, 2); ?>
                                        </div>

                                        <?php if($item['last_approver_name'] !== 'None'): ?>
                                            <small class="text-dark d-block fw-semibold mt-1">
                                                <i class="fas fa-user-check text-success me-1"></i>
                                                <?php echo htmlspecialchars($item['last_approver_name']); ?>
                                                <?php if(!empty($item['last_role'])): ?>
                                                    <span class="badge bg-light text-dark border extra-small" style="font-size:9.5px;"><?php echo htmlspecialchars(str_replace('_', ' ', $item['last_role'])); ?></span>
                                                <?php endif; ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo $item['last_approved_at'] ? date('d M Y, h:i A', strtotime($item['last_approved_at'])) : 'Unknown'; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-minus-circle me-1"></i>No subsequent approval
                                            </small>
                                        <?php endif; ?>

                                        <?php if(abs((float)($item['my_approved_amount'] ?? 0) - (float)($item['last_approved_amount'] ?? 0)) > 0.01): ?>
                                            <small class="text-danger d-block mt-0.5" style="font-size:10px;">
                                                <i class="fas fa-info-circle me-1"></i>Diff: ৳<?php echo number_format(abs((float)$item['my_approved_amount'] - (float)$item['last_approved_amount']), 2); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <!-- Summary Footer -->
                    <?php if(!empty($history)): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end text-dark">
                                    Summary Total (<?php echo !empty($isViewAll) ? "All {$totalItems} Records" : "Page {$page} of {$totalPages}"; ?>):
                                </td>
                                <td class="text-success fs-6">
                                    ৳<?php echo number_format($totalMyAmount, 2); ?>
                                </td>
                                <td class="text-primary fs-6">
                                    ৳<?php echo number_format($totalLastAmount, 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Footer / Pagination -->
            <div class="card-footer bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center d-print-none">
                <div class="text-muted small mb-2 mb-md-0">
                    <?php if($totalItems > 0): ?>
                        <?php if(!empty($isViewAll)): ?>
                            Showing <strong>all <?php echo $totalItems; ?></strong> total requests
                        <?php else: ?>
                            Showing <strong><?php echo (($page - 1) * $limit) + 1; ?></strong> to <strong><?php echo min($page * $limit, $totalItems); ?></strong> of <strong><?php echo $totalItems; ?></strong> total requests
                        <?php endif; ?>
                    <?php else: ?>
                        No requests found
                    <?php endif; ?>
                </div>

                <?php if(empty($isViewAll) && $totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-end mb-0">
                        <!-- Prev Button -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $fullQs; ?>">Previous</a>
                        </li>
                        
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $fullQs; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $fullQs; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    /* Hide layout chrome and non-print controls */
    .sidebar, 
    #sidebar, 
    .app-sidebar,
    .main-sidebar,
    .main-header,
    .navbar, 
    .topbar, 
    header, 
    footer, 
    .footer, 
    .card-footer, 
    .pagination, 
    .btn, 
    form, 
    .d-print-none {
        display: none !important;
    }

    body, .main-content, .container-fluid, .content-wrapper, .page-wrapper {
        margin: 0 !important;
        padding: 5px !important;
        background: #fff !important;
        color: #000 !important;
        font-family: Arial, sans-serif !important;
    }

    .print-card {
        border: none !important;
        box-shadow: none !important;
    }

    .print-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    .print-table th, 
    .print-table td {
        border: 1px solid #ccc !important;
        padding: 6px 8px !important;
        font-size: 10pt !important;
    }

    .print-table th {
        background-color: #f2f2f2 !important;
        color: #000 !important;
        font-weight: bold !important;
    }

    .print-table tr {
        page-break-inside: avoid !important;
    }

    .badge {
        border: 1px solid #999 !important;
        color: #000 !important;
        background: transparent !important;
        font-size: 8.5pt !important;
        padding: 2px 4px !important;
    }

    .text-success, .text-primary, .text-dark, .text-secondary {
        color: #000 !important;
    }

    .print-header {
        display: block !important;
    }
}
</style>
