

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <?php echo $isAccounts ? 'My Payment & Approval History' : 'My Approval History'; ?>
                <span class="badge bg-primary ms-2" style="font-size:14px; vertical-align:middle;"><?php echo $totalItems; ?> Total</span>
            </h4>
            <a href="<?php echo $GLOBALS['base_url']; ?>/all-approval-requests" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Approvals
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body py-3">
            <form method="GET" action="<?php echo $GLOBALS['base_url']; ?>/my-approval-history" class="row  align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-0 fw-bold" style="font-size:12px;">REQUEST #</label>
                    <input type="text" name="request_no" class="form-control form-control-sm" placeholder="e.g. 03337" value="<?php echo htmlspecialchars($searchReqNo ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-0 fw-bold" style="font-size:12px;">MODULE / SOURCE TYPE</label>
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
                <div class="col-md-3">
                    <label class="form-label mb-0 fw-bold" style="font-size:12px;">DEPARTMENT FILTER</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="0">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo (isset($searchDept) && $searchDept == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mt-4 pt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($searchModule) || !empty($searchReqNo) || !empty($searchDept)): ?>
                        <a href="<?php echo $GLOBALS['base_url']; ?>/my-approval-history" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- History Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">Module & Request</th>
                            <th width="25%">Requested By & When</th>
                            <th width="25%">Previous Approver (Before Me)</th>
                            <th width="20%">My Action (<?php echo $isAccounts ? 'Disbursed/Paid' : 'Approved'; ?>)</th>
                            <?php if($isAccounts): ?>
                            <th width="10%" class="text-end">Amount</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr>
                                <td colspan="<?php echo $isAccounts ? '5' : '4'; ?>" class="text-center py-5 text-muted">
                                    No approval history found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($history as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary mb-1"><?php echo $item['module_name']; ?></span><br>
                                        <small class="text-dark fw-bold">#<?php echo $item['request_id']; ?></small><br>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width:200px;" title="<?php echo htmlspecialchars($item['request_title']); ?>">
                                            <?php echo htmlspecialchars($item['request_title']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle text-center me-2" style="width:30px;height:30px;line-height:30px;">
                                                <i class="fas fa-user text-secondary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold d-block text-sm"><?php echo $item['requested_by_name']; ?></span>
                                                <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($item['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($item['prev_approver_name'] === 'None'): ?>
                                            <span class="badge bg-light text-dark border">First Approver</span>
                                        <?php else: ?>
                                            <span class="fw-bold d-block text-sm"><?php echo $item['prev_approver_name']; ?></span>
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i> 
                                                <?php echo $item['prev_approved_at'] ? date('d M Y, h:i A', strtotime($item['prev_approved_at'])) : 'Unknown'; ?>
                                            </small>
                                            <br>
                                            <small class="text-info" style="font-size:10px;">Role: <?php echo str_replace('_', ' ', $item['prev_role']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success mb-1">
                                            <?php echo $item['my_role'] === 'Accounts' ? 'Paid / Disbursed' : 'Approved'; ?>
                                        </span><br>
                                        <small class="text-dark fw-bold">
                                            <?php echo date('d M Y, h:i A', strtotime($item['my_action_time'])); ?>
                                        </small>
                                        <?php 
                                            // Calculate delay from previous to me
                                            if($item['prev_approved_at']) {
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
                                                        echo "<br><small class='text-danger' style='font-size:10px;'><i class='fas fa-stopwatch'></i> Delay: $delayStr</small>";
                                                    }
                                                }
                                            }
                                        ?>
                                    </td>
                                    <?php if($isAccounts): ?>
                                    <td class="text-end fw-bold text-success">
                                        ৳<?php echo number_format($item['approved_amount'] ?? 0, 2); ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer / Pagination -->
            <div class="card-footer bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted small mb-2 mb-md-0">
                    <?php if($totalItems > 0): ?>
                        Showing <strong><?php echo (($page - 1) * 20) + 1; ?></strong> to <strong><?php echo min($page * 20, $totalItems); ?></strong> of <strong><?php echo $totalItems; ?></strong> total requests
                    <?php else: ?>
                        No requests found
                    <?php endif; ?>
                </div>

                <?php if($totalPages > 1): 
                    $qs = "&module=" . urlencode($searchModule ?? '') . "&request_no=" . urlencode($searchReqNo ?? '') . "&department_id=" . urlencode($searchDept ?? 0);
                ?>
                <nav>
                    <ul class="pagination justify-content-end mb-0">
                        <!-- Prev Button -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $qs; ?>">Previous</a>
                        </li>
                        
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $qs; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $qs; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>


