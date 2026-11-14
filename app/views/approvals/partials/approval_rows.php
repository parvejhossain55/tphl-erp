<?php if (empty($approvalHistory)): ?>
    <tr>
        <td colspan="10" class="text-center py-4 text-muted">
            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
            No approval requests found matching your filter criteria.
        </td>
    </tr>
<?php else: ?>
                            <?php foreach ($approvalHistory as $req): ?>
                                <?php
                                // Direct module target URL resolution so clicking review/view opens the exact module page!
                                $targetUrl = 'javascript:void(0)';
                                $sourceTable = strtolower($req['source_table'] ?? '');
                                $requestType = strtolower($req['request_type'] ?? '');

                                if ($sourceTable === 'lead_payments') {
                                    if (!empty($req['booking_id'])) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $req['booking_id'];
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'lead_bookings') {
                                    $bookingId = $req['source_id'] ?? $req['booking_id'] ?? null;
                                    if (!empty($bookingId)) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $bookingId;
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'project_incentive_claims') {
                                    $targetUrl = $base_url . '/incentive-management';
                                } elseif ($sourceTable === 'vendor_payment_requests') {
                                    $targetUrl = $base_url . '/con-vendor-payment-show/' . $req['source_id'];
                                } elseif ($sourceTable === 'contractor_bill_requests') {
                                    $targetUrl = $base_url . '/con-contractor-bill/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_land_requisitions' || $sourceTable === 'land_fund_requisitions') {
                                    $targetUrl = $base_url . '/con-land-requisitions?pay_id=' . $req['source_id'];
                                } elseif ($sourceTable === 'con_labor_requisitions') {
                                    $targetUrl = $base_url . '/con-labor-requisition/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_work_orders') {
                                    $targetUrl = $base_url . '/con-work-order/' . $req['source_id'];
                                } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                    $targetUrl = $base_url . '/fund-requisitions/view/' . $req['source_id'];
                                } elseif ($sourceTable === 'cashout_requests') {
                                    $targetUrl = $base_url . '/view-cashout-request?id=' . $req['source_id'];
                                } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests' || $requestType === 'salary') {
                                    $targetUrl = $base_url . '/show-payslip/' . $req['source_id'];
                                } elseif ($sourceTable === 'client_refund_requests') {
                                    $targetUrl = $base_url . '/client-refund/' . $req['source_id'];
                                } elseif ($sourceTable === 'employee_leaves' || $sourceTable === 'leave') {
                                    $targetUrl = $base_url . '/hrm-leave-history';
                                } elseif ($sourceTable === 'manpower_requisitions') {
                                    $targetUrl = $base_url . '/manpower-requisitions';
                                } elseif ($sourceTable === 'advance_salaries') {
                                    $targetUrl = $base_url . '/advance-salary';
                                } elseif ($sourceTable === 'project_other_costs') {
                                    $targetUrl = $base_url . '/project-other-costs/review/' . $req['source_id'];
                                } elseif ($sourceTable === 'project_extra_assets' || $sourceTable === 'purchase_requests') {
                                    $targetUrl = $base_url . '/asset-extra-purchases?view=' . $req['source_id'];
                                } elseif ($sourceTable === 'expense_adjustments') {
                                    $targetUrl = $base_url . '/expense-adjustments/show?id=' . $req['source_id'];
                                }
                                
                                if ($targetUrl !== 'javascript:void(0)') {
                                    $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . 'ref=all_approvals';
                                }

                                $reqApproveRole = strtolower($req['approve_role'] ?? 'management');
                                if (empty($reqApproveRole)) {
                                    $reqApproveRole = 'management';
                                }
                                $isMgmtRequest = ($reqApproveRole === 'management');
                                $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
                                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

                                $isManagementUser = (in_array($userRoleId, [1, 3], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management'], true)) && !$isChiefOfficer;
                                $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
                                $isAccountsHeadUser = ($userRoleId === 30) || strpos($sessionRoleName, 'accounts head') !== false;
                                $isAccountsStaffUser = ($userRoleId === 6) || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHeadUser);
                                $isAccountsUser = in_array($userRoleId, [6, 30], true) || strpos($sessionRoleName, 'account') !== false;
                                $isQcUser = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;
                                $isHrUser = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;
                                $isPurchaseUser = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
                                $isDeptHeadUser = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

                                $userSecondaryRoleId = (int) ($_SESSION['user']['secondary_role_id'] ?? 0);
                                if ($userSecondaryRoleId === 0 && !empty($_SESSION['user']['id'])) {
                                    static $secondaryRoleCache = [];
                                    $uid = (int)$_SESSION['user']['id'];
                                    if (!isset($secondaryRoleCache[$uid])) {
                                        $conn = $GLOBALS['db'] ?? ($this->db ?? null);
                                        $secId = 0;
                                        if ($conn) {
                                            $res = $conn->query("SELECT secondary_role_id FROM users WHERE id = {$uid} LIMIT 1");
                                            if ($res && $r = $res->fetch_assoc()) {
                                                $secId = (int)($r['secondary_role_id'] ?? 0);
                                            }
                                        }
                                        $secondaryRoleCache[$uid] = $secId;
                                    }
                                    $userSecondaryRoleId = $secondaryRoleCache[$uid];
                                }
                                $isSalesHeadUser = ($userRoleId === 9 || $userSecondaryRoleId === 9 || strpos($sessionRoleName, 'sales head') !== false || strpos($sessionRoleName, 'sales team head') !== false || $isManagementUser || $isSiteOperator || in_array($userRoleId, [20, 32], true));

                                $statusLower = strtolower($req['status'] ?? '');
                                $isPendingStatus = in_array($statusLower, ['pending', 'partially_approved', '']);
                                $reqAmount = (float)($req['request_amount'] ?? 0);
                                $payoutStatus = $req['payout_status'] ?? '';
                                $ahStatus = strtolower($req['accounts_head_status'] ?? 'pending');
                                $isFrPartialMgmt = ($sourceTable === 'fund_requisitions' && $statusLower === 'partially_approved');
                                $opHeadDone = (strtolower($req['op_head_status'] ?? '') === 'approved');
                                $isPendingAccountsHead = ($sourceTable !== 'project_incentive_claims') ? (($ahStatus !== 'approved') && (
                                    $reqApproveRole === 'accounts_head'
                                    || ($reqAmount > 10000.01 && $opHeadDone)
                                    || ($reqAmount <= 10000.01 && ($opHeadDone || in_array($statusLower, ['approved', 'partially_approved']) || $reqApproveRole === 'accounts_head'))
                                    || in_array($statusLower, ['approved', 'partially_approved'])
                                    || $isFrPartialMgmt
                                )) : ($reqApproveRole === 'accounts_head' && $isPendingStatus);
                                $isAccountsHeadApproved = ($ahStatus === 'approved');

                                // Determine whether this request is actionable by the logged-in user right now
                                $isActionableByMe = false;
                                $canUserApprove = false;
                                if ($isChiefOfficer) {
                                    // Chief Officer has view-only access across all requests; strictly cannot approve or reject
                                    $isActionableByMe = false;
                                    $canUserApprove = false;
                                } elseif ($sourceTable === 'project_incentive_claims') {
                                    if ($reqApproveRole === 'sales_head' && $isPendingStatus) {
                                        if ($isSalesHeadUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($reqApproveRole === 'accounts_head' && $isPendingStatus) {
                                        if ($isAccountsHeadUser || $userRoleId === 1 || $isManagementUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($reqApproveRole === 'management' && $isPendingStatus) {
                                        if ($isManagementUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($statusLower === 'approved') {
                                        if ($isAccountsUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    }
                                } elseif ($isPendingAccountsHead) {
                                    // Pending Accounts Head approval: Actionable by Accounts Head (Role 30) and Super Admin (Role 1)
                                    if ($isAccountsHeadUser || (int)($_SESSION['user']['role_id'] ?? 0) === 1) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    } else {
                                        $isActionableByMe = false;
                                        $canUserApprove = false;
                                    }
                                } elseif ($isAccountsHeadApproved && in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid'])) {
                                    // Approved by Accounts Head & awaiting payout: Actionable by BOTH Accounts (Role 6) and Accounts Head (Role 30)
                                    if ($isAccountsUser) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isAccountsUser) {
                                    if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid']) || ($reqApproveRole === 'accounts' && in_array($statusLower, ['pending', 'approved', 'partially_approved', 'partially_paid']) && $isAccountsHeadApproved)) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isSiteOperator) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', 'operations', '']) && (strtolower($req['op_head_status'] ?? '') !== 'approved')) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isManagementUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', 'operations', ''])) {
                                        $reqOpStatus = strtolower($req['op_head_status'] ?? 'pending');
                                        $isOver10k = ((float)($req['request_amount'] ?? 0) > 10000.01);
                                        if ($isOver10k && ($reqOpStatus !== 'approved' || !$isAccountsHeadApproved)) {
                                            $isActionableByMe = false;
                                            $canUserApprove = false;
                                        } else {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    }
                                } elseif ($isQcUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['qc', 'qc_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isPurchaseUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['purchase', 'purchase team leader', 'purchase_lead'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isHrUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['hr', 'hr_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isDeptHeadUser) {
                                    if ($isPendingStatus && ($reqApproveRole === 'dept_head' || $reqApproveRole === 'dept_head_' . ($_SESSION['user']['id'] ?? 0))) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } else {
                                    if ($isPendingStatus && !empty($reqApproveRole) && strpos($sessionRoleName, $reqApproveRole) !== false) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                }

                                $rowBgStyle = $isActionableByMe ? 'background-color: #f8fafc;' : '';
                                ?>
                                <tr data-source-table="<?= htmlspecialchars($req['source_table']) ?>" <?= !empty($targetUrl) ? 'data-href="' . htmlspecialchars($targetUrl) . '" class="clickable-approval-row' . ($isActionableByMe ? ' border-start border-primary border-3' : '') . '" style="cursor: pointer; ' . $rowBgStyle . '"' : '' ?>>
                                    <td class="ps-3">
                                        <input type="checkbox" class="approval-req-checkbox form-check-input"
                                            value="<?= $req['id'] ?>" <?= ((!in_array($req['status'], ['pending', 'partially_approved', 'partially_paid']) && !($isPendingAccountsHead && ($isAccountsHeadUser || $isManagementUser))) || !$canUserApprove || $isChiefOfficer) ? 'disabled' : '' ?> onchange="updateBulkApprovalCount()">
                                    </td>
                                    <!-- Req # & Date -->
                                    <td>
                                        <?php
                                        // Standardized Requisition / Bill ID for all modules
                                        $displayReqCode = '';
                                        if ($sourceTable === 'vendor_payment_requests' || $sourceTable === 'vendor_payments') {
                                            $displayReqCode = 'VPR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'contractor_bill_requests' || $sourceTable === 'contractor_bills') {
                                            $displayReqCode = 'CB-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                            $displayReqCode = 'REQ-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'con_labor_requisitions' || $sourceTable === 'labor_requisitions') {
                                            $displayReqCode = !empty($req['requisition_no']) ? htmlspecialchars($req['requisition_no']) : ('LR-' . (int)$req['source_id']);
                                        } elseif ($sourceTable === 'project_other_costs') {
                                            $displayReqCode = 'POC-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'project_extra_assets' || $sourceTable === 'purchase_requests') {
                                            $displayReqCode = 'PR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'land_fund_requisitions' || $sourceTable === 'con_land_requisitions') {
                                            $displayReqCode = 'LFR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'cashout_requests') {
                                            $displayReqCode = 'CSH-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'expense_adjustments') {
                                            $displayReqCode = 'ADJ-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'client_refund_requests') {
                                            $displayReqCode = 'REF-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests') {
                                            $displayReqCode = 'PAY-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'project_incentive_claims') {
                                            $displayReqCode = 'INC-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'advance_salaries') {
                                            $displayReqCode = 'ADV-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'lead_bookings') {
                                            $displayReqCode = 'BK-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'con_work_orders') {
                                            $displayReqCode = 'WO-' . (int)$req['source_id'];
                                        } else {
                                            $displayReqCode = !empty($req['source_id']) ? ('#' . (int)$req['source_id']) : ('#REQ-' . str_pad($req['id'], 5, '0', STR_PAD_LEFT));
                                        }
                                        ?>
                                        <?php if (!empty($targetUrl)): ?>
                                            <a href="<?= $targetUrl ?>" class="text-decoration-none d-block">
                                                <span class="fw-bold text-dark"><?= $displayReqCode ?></span>
                                                <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-bold text-dark d-block"><?= $displayReqCode ?></span>
                                            <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="text-muted extra-small">
                                            <?= date('d M, Y', strtotime($req['requested_at'])) ?>
                                        </span>
                                    </td>

                                    <!-- Department & Module -->
                                    <td>
                                        <span
                                            class="badge bg-success text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                            <?= htmlspecialchars($req['department'] ?? 'General') ?>
                                        </span>
                                        <span class="badge bg-light text-muted border extra-small">
                                            <?= htmlspecialchars($req['request_type'] ?? $req['source_table']) ?>
                                        </span>
                                    </td>

                                    <!-- Title -->
                                    <td style="max-width: 250px;">
                                        <div class="fw-semibold text-dark text-truncate"
                                            title="<?= htmlspecialchars($req['title']) ?>">
                                            <?php if (!empty($targetUrl)): ?>
                                                <a href="<?= $targetUrl ?>" class="text-dark text-decoration-none hover-primary">
                                                    <?= htmlspecialchars($req['title']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($req['title']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($sourceTable === 'expense_adjustments'): ?>
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                <?php if (($req['request_type'] ?? '') === 'Extra Expense' || strpos(strtolower($req['title'] ?? ''), 'extra') !== false): ?>
                                                    <span class="badge bg-warning text-dark border border-warning-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-plus-circle me-1"></i>Extra Amount Request
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Return Unused Money
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['project_name'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small">
                                                    <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($req['project_name']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['attachment'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= $base_url ?>/<?= htmlspecialchars(ltrim($req['attachment'], '/')) ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small text-decoration-none" onclick="event.stopPropagation();" title="View Attachment / Supporting Document">
                                                    <i class="fa-solid fa-paperclip me-1"></i>Attachment Document
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-muted extra-small">Source Table:
                                            <code><?= htmlspecialchars($req['source_table']) ?></code></span>
                                    </td>

                                    <!-- Requested By -->
                                    <td>
                                        <div class="fw-bold text-dark extra-small">
                                            <?= htmlspecialchars($req['requester_name'] ?? 'N/A') ?>
                                        </div>
                                        <span class="badge bg-light text-secondary border extra-small">
                                            <?= !empty($req['requester_code']) ? htmlspecialchars($req['requester_code']) : 'null' ?>
                                        </span>
                                    </td>

                                    <!-- Requested Amount -->
                                    <td>
                                        <?php
                                        $displayAmount = $req['request_amount'];
                                        if ($sourceTable === 'advance_salaries' && $displayAmount <= 0) {
                                            $db = $GLOBALS['db'] ?? null;
                                            if ($db) {
                                                $res = $db->query("SELECT amount FROM advance_salaries WHERE id = " . (int) $req['source_id']);
                                                if ($res && $row = $res->fetch_assoc()) {
                                                    $displayAmount = $row['amount'];
                                                    // Self-heal the record in the DB
                                                    $db->query("UPDATE approval_requests SET request_amount = {$displayAmount} WHERE id = " . (int) $req['id']);
                                                }
                                            }
                                        }
                                        ?>
                                        <?php if ($displayAmount > 0): ?>
                                            <span class="fw-bold text-dark">à§³
                                                <?= number_format($displayAmount, 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">N/A</span>
                                        <?php endif; ?>
                                        <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['vpr_payment_type'])): ?>
                                            <?php
                                            $vprType = strtolower($req['vpr_payment_type']);
                                            $typeLabel = match ($vprType) {
                                                'advance' => 'Advance',
                                                'partial' => 'Partially',
                                                'final', 'full', 'fully' => 'Fully',
                                                default => ucfirst($vprType)
                                            };
                                            $typeClass = match ($vprType) {
                                                'advance' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'partial' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'final', 'full', 'fully' => 'bg-success-subtle text-success border-success-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                            ?>
                                            <div class="mt-1"><span class="badge <?= $typeClass ?> border extra-small">
                                                    <?= $typeLabel ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approved Amount -->
                                    <td>
                                        <?php 
                                        $appAmt = (float)($req['approved_amount'] ?? 0);
                                        $qcAmt = (float)($req['qc_approved_amount'] ?? 0);
                                        $isPendingMgmt = in_array(strtolower($req['approve_role'] ?? ''), ['management', 'operation_head']);
                                        $isQcOnlyApproved = ($qcAmt > 0 && ($appAmt == $qcAmt || $appAmt <= 0) && $isPendingMgmt);
                                        $isFullyApproved = ($appAmt > 0 && !$isQcOnlyApproved);
                                        $reqAmt = (float)($req['request_amount'] ?? 0);
                                        ?>
                                        <?php if ($isQcOnlyApproved): ?>
                                            <span class="fw-bold text-primary">৳ <?= number_format($qcAmt, 2) ?></span>
                                            <div class="mt-1">
                                                <span class="badge bg-info-subtle text-info border border-info-subtle extra-small" style="font-size: 9px; padding: 2px 6px;">
                                                    <i class="bi bi-shield-check me-1"></i>QC Approved
                                                </span>
                                            </div>
                                            <?php if ($reqAmt > $qcAmt): ?>
                                                <div class="extra-small text-danger fw-semibold mt-0.5" style="font-size: 10px;" title="Original Requested: ৳ <?= number_format($reqAmt, 2) ?>">
                                                    <i class="fa-solid fa-arrow-down me-1"></i>Reduced by ৳ <?= number_format($reqAmt - $qcAmt, 2) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($req['qc_approver_name'])): ?>
                                                <div class="extra-small text-muted mt-1 lh-sm" style="font-size: 10px;">
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($req['qc_approver_name']) ?>">
                                                        <i class="fa-solid fa-user-check text-primary me-1"></i><?= htmlspecialchars($req['qc_approver_name']) ?>
                                                    </div>
                                                    <?php if (!empty($req['qc_approved_at'])): ?>
                                                        <div class="text-muted" style="font-size: 9.5px;">
                                                            <i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($req['qc_approved_at'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($req['qc_note'])): ?>
                                                        <div class="fst-italic text-secondary text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($req['qc_note']) ?>">
                                                            <i class="fa-solid fa-comment-dots text-muted me-1"></i>"<?= htmlspecialchars($req['qc_note']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($isFullyApproved): ?>
                                            <span class="fw-bold text-success">৳ <?= number_format($appAmt, 2) ?></span>
                                            <?php if ($reqAmt > $appAmt): ?>
                                                <div class="extra-small text-danger fw-semibold mt-0.5" style="font-size: 10px;" title="Original Requested: ৳ <?= number_format($reqAmt, 2) ?>">
                                                    <i class="fa-solid fa-arrow-down me-1"></i>Reduced by ৳ <?= number_format($reqAmt - $appAmt, 2) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                            $apprDisplayName = (!empty($req['approved_by']) && (int)$req['approved_by'] !== (int)($req['accounts_head_by'] ?? 0) && stripos($req['approver_name'] ?? '', 'accounts') === false)
                                                ? ($req['approver_name'] ?? 'Management')
                                                : (!empty($req['op_head_name']) ? $req['op_head_name'] : ($req['approver_name'] ?? 'Management'));
                                            $apprDisplayAt = (!empty($req['approved_by']) && (int)$req['approved_by'] !== (int)($req['accounts_head_by'] ?? 0) && stripos($req['approver_name'] ?? '', 'accounts') === false)
                                                ? ($req['approved_at'] ?? null)
                                                : (!empty($req['op_head_at']) ? $req['op_head_at'] : ($req['approved_at'] ?? null));
                                            ?>
                                            <?php if (!empty($apprDisplayName)): ?>
                                                <div class="extra-small text-muted mt-1 lh-sm" style="font-size: 10px;">
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($apprDisplayName) ?>">
                                                        <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($apprDisplayName) ?>
                                                    </div>
                                                    <?php if (!empty($apprDisplayAt)): ?>
                                                        <div class="text-muted" style="font-size: 9.5px;">
                                                            <i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($apprDisplayAt)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approval Status & Pending At -->
                                    <td class="text-center">
                                        <?php if ($sourceTable === 'project_incentive_claims' && $statusLower === 'rejected'): ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;">
                                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 extra-small fw-semibold">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                                </span>
                                                <span class="extra-small text-muted fw-semibold">
                                                    By: <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                </span>
                                                <?php if (!empty($req['approved_at'])): ?>
                                                    <span class="extra-small text-muted" style="font-size: 8.5px;"><?= date('d M, Y h:i A', strtotime($req['approved_at'])) ?></span>
                                                <?php endif; ?>
                                                <?php 
                                                $rejReason = !empty($req['rejection_reason']) ? $req['rejection_reason'] : (!empty($req['approval_note']) ? $req['approval_note'] : (!empty($req['sales_head_note']) ? $req['sales_head_note'] : (!empty($req['accounts_head_note']) ? $req['accounts_head_note'] : '')));
                                                ?>
                                                <?php if (!empty($rejReason)): ?>
                                                    <span class="badge bg-light text-danger border border-danger-subtle text-wrap extra-small mt-1 px-1.5 py-0.5 text-start" style="font-size: 9px;" title="Rejection Reason">
                                                        <i class="fa-solid fa-comment-dots me-1"></i><?= htmlspecialchars($rejReason) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($sourceTable === 'project_incentive_claims'): ?>
                                            <?php
                                            $shDone = (strtolower($req['sales_head_status'] ?? '') === 'approved');
                                            $shName = !empty($req['sales_head_name']) ? $req['sales_head_name'] : 'Sales Head';
                                            $shDate = !empty($req['sales_head_at']) ? $req['sales_head_at'] : null;

                                            $ahDone = (strtolower($req['accounts_head_status'] ?? '') === 'approved');
                                            $ahName = !empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head';
                                            $ahDate = !empty($req['accounts_head_at']) ? $req['accounts_head_at'] : null;

                                            $mgmtDone = in_array(strtolower($req['status'] ?? ''), ['approved', 'paid']);
                                            $mgmtName = !empty($req['approver_name']) ? $req['approver_name'] : 'Management';
                                            $mgmtDate = !empty($req['approved_at']) ? $req['approved_at'] : null;
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-start" style="min-width: 180px; max-width: 220px; margin: 0 auto;">
                                                <!-- Step 1: Sales Head -->
                                                <?php if ($shDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Sales Head Approved • <?= htmlspecialchars($shName) ?><?= !empty($shDate) ? ' • ' . date('d M, Y h:i A', strtotime($shDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($shName) ?>"><?= htmlspecialchars($shName) ?></span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Sales Head</span>
                                                        </div>
                                                        <?php if (!empty($shDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($shDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Sales Head or Op Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 1</span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Step 2: Accounts Head -->
                                                <?php if ($ahDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Accounts Head Approved • <?= htmlspecialchars($ahName) ?><?= !empty($ahDate) ? ' • ' . date('d M, Y h:i A', strtotime($ahDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($ahName) ?>"><?= htmlspecialchars($ahName) ?></span>
                                                            <span class="badge bg-primary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">AH</span>
                                                        </div>
                                                        <?php if (!empty($ahDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($ahDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($shDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Accts Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 2</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-light border text-muted d-flex align-items-center justify-content-between gap-1" style="font-size: 9.5px; line-height: 1.2;">
                                                        <span class="text-muted"><i class="fa-solid fa-lock text-muted me-1"></i>Accts Head (Locked)</span>
                                                        <span class="badge bg-secondary-subtle text-muted p-0 px-1" style="font-size: 7.5px;">Step 2</span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Step 3: Management -->
                                                <?php if ($mgmtDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Management Approved • <?= htmlspecialchars($mgmtName) ?><?= !empty($mgmtDate) ? ' • ' . date('d M, Y h:i A', strtotime($mgmtDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-circle-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($mgmtName) ?>"><?= htmlspecialchars($mgmtName) ?></span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Mgmt</span>
                                                        </div>
                                                        <?php if (!empty($mgmtDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($mgmtDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($ahDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-info-subtle border border-info-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-info flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Mgmt</span>
                                                        </div>
                                                        <span class="badge bg-info text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 3</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-light border text-muted d-flex align-items-center justify-content-between gap-1" style="font-size: 9.5px; line-height: 1.2;">
                                                        <span class="text-muted"><i class="fa-solid fa-lock text-muted me-1"></i>Management (Locked)</span>
                                                        <span class="badge bg-secondary-subtle text-muted p-0 px-1" style="font-size: 7.5px;">Step 3</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($req['status'] === 'pending'): ?>
                                            <?php
                                            $stageLabel = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_stage_label'] ?? 'Pending Review');
                                            $pendingFor = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_for_text'] ?? 'Management');
                                            $pendingFor = trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', $pendingFor));

                                            if ($isPendingAccountsHead) {
                                                $stageLabel = ($reqAmount > 10000.01) ? 'Pending Accounts Head (>10K)' : 'Pending Accounts Head';
                                                $pendingFor = 'Accounts Head';
                                            }

                                            $badgeClass = 'bg-warning text-dark';
                                            if (stripos($stageLabel, 'QC') !== false || stripos($stageLabel, 'Audit') !== false) {
                                                $badgeClass = 'bg-info text-dark';
                                            } elseif (stripos($stageLabel, 'Dept') !== false) {
                                                $badgeClass = 'bg-secondary text-white';
                                            } elseif (stripos($stageLabel, 'HR') !== false) {
                                                $badgeClass = 'bg-primary text-white';
                                            } elseif (stripos($stageLabel, 'Purchase') !== false) {
                                                $badgeClass = 'bg-secondary text-white';
                                            } elseif (stripos($stageLabel, 'PM') !== false) {
                                                $badgeClass = 'bg-info text-white';
                                            } elseif (stripos($stageLabel, 'Accounts Head') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif (stripos($stageLabel, 'Management') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif (stripos($stageLabel, 'Accounts') !== false) {
                                                $badgeClass = 'bg-success text-white';
                                            }
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 210px; margin: 0 auto;">
                                                <div>
                                                    <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1 extra-small fw-semibold">
                                                        <i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($stageLabel) ?>
                                                    </span>
                                                </div>
                                                <div class="extra-small text-dark" style="font-size: 11px;">
                                                    <i class="fa-solid fa-hourglass-half text-warning me-1"></i><span class="text-muted">Pending:</span>
                                                    <span class="text-primary fw-semibold"><?= htmlspecialchars($pendingFor) ?></span>
                                                </div>
                                                <?php if (strtolower($req['op_head_status'] ?? '') === 'approved' || !empty($req['op_head_by'])): ?>
                                                    <div class="p-1 px-2 rounded-2 bg-secondary-subtle border border-secondary-subtle text-start d-flex align-items-center justify-content-between"
                                                         title="<?= htmlspecialchars(($req['op_head_name'] ?? 'Admin & Operation Head') . (!empty($req['op_head_at']) ? ' • ' . date('d M, Y h:i A', strtotime($req['op_head_at'])) : '')) ?>">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-secondary flex-shrink-0" style="font-size: 10px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="font-size: 10.5px; max-width: 110px;">
                                                                <?= htmlspecialchars(!empty($req['op_head_name']) ? $req['op_head_name'] : 'Op Head') ?>
                                                            </span>
                                                            <span class="badge bg-secondary text-white p-0 px-1" style="font-size: 8px;">Op Head</span>
                                                        </div>
                                                        <span class="text-secondary fw-semibold flex-shrink-0" style="font-size: 9.5px;">
                                                            <?= !empty($req['op_head_at']) ? date('d M, h:i A', strtotime($req['op_head_at'])) : 'Approved' ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($isAccountsHeadApproved): ?>
                                                    <div class="p-1 px-2 rounded-2 bg-primary-subtle border border-primary-subtle text-start d-flex align-items-center justify-content-between"
                                                         title="<?= htmlspecialchars(($req['accounts_head_name'] ?? 'Accounts Head') . (!empty($req['accounts_head_at']) ? ' • ' . date('d M, Y h:i A', strtotime($req['accounts_head_at'])) : '')) ?>">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 10px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="font-size: 10.5px; max-width: 110px;">
                                                                <?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>
                                                            </span>
                                                            <span class="badge bg-primary text-white p-0 px-1" style="font-size: 8px;">Accts</span>
                                                        </div>
                                                        <span class="text-primary fw-semibold flex-shrink-0" style="font-size: 9.5px;">
                                                            <?= !empty($req['accounts_head_at']) ? date('d M, h:i A', strtotime($req['accounts_head_at'])) : 'Approved' ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php include __DIR__ . '/fr_mgmt_approvals_block.php'; ?>
                                            </div>

                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid'])): ?>
                                            <div class="d-flex flex-column gap-1 text-start" style="min-width: 180px; max-width: 220px; margin: 0 auto;">
                                                <?php
                                                $hasOpHead = (strtolower($req['op_head_status'] ?? '') === 'approved' || !empty($req['op_head_by']));
                                                $opName = !empty($req['op_head_name']) ? $req['op_head_name'] : 'Admin & Operation Head';
                                                $opDate = !empty($req['op_head_at']) ? $req['op_head_at'] : null;

                                                $ahId = (int)($req['accounts_head_by'] ?? 0);
                                                $opId = (int)($req['op_head_by'] ?? 0);
                                                $appId = (int)($req['approved_by'] ?? 0);
                                                $appName = strtolower(trim($req['approver_name'] ?? ''));
                                                $ahName = strtolower(trim($req['accounts_head_name'] ?? ''));
                                                $opNameLower = strtolower(trim($req['op_head_name'] ?? ''));
                                                $appDes = strtolower(trim($req['approver_designation'] ?? ''));

                                                // Determine if there is a distinct Management approval (not Accounts Head and not Op Head)
                                                $isMgmtDistinct = false;
                                                if (!empty($req['approved_by'])) {
                                                    $isSameAsAh = ($appId > 0 && $appId === $ahId) || ($appName !== '' && $appName === $ahName) || (stripos($appName, 'accounts head') !== false) || (stripos($appDes, 'accounts') !== false);
                                                    $isSameAsOp = ($appId > 0 && $appId === $opId) || ($appName !== '' && $appName === $opNameLower);
                                                    if (!$isSameAsAh && !$isSameAsOp) {
                                                        $isMgmtDistinct = true;
                                                    }
                                                } elseif (!$hasOpHead && !$isAccountsHeadApproved && !empty($req['approver_name'])) {
                                                    $isMgmtDistinct = true;
                                                }
                                                ?>

                                                <!-- Layer 1: Admin & Operation Head Approval -->
                                                <?php if ($hasOpHead): ?>
                                                    <?php
                                                    $opTooltip = trim(
                                                        (!empty($req['op_head_designation']) ? $req['op_head_designation'] : 'Admin & Operation Head') .
                                                        (!empty($opDate) ? ' • ' . date('d M, Y h:i A', strtotime($opDate)) : '')
                                                    );
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded bg-secondary-subtle border border-secondary-subtle d-flex align-items-center justify-content-between gap-1" 
                                                         title="<?= htmlspecialchars($opTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-secondary flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($opName) ?>">
                                                                <?= htmlspecialchars($opName) ?>
                                                            </span>
                                                            <span class="badge bg-secondary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Op Head</span>
                                                        </div>
                                                        <?php if (!empty($opDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($opDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Layer 2: Accounts Head Row (Pending or Approved) -->
                                                <?php if ($isPendingAccountsHead): ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" title="Payment locked until Accounts Head approves" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Accts Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8.5px;">AH</span>
                                                    </div>
                                                <?php elseif ($isAccountsHeadApproved): ?>
                                                    <?php
                                                    $ahTooltip = trim(
                                                        (!empty($req['accounts_head_designation']) ? $req['accounts_head_designation'] : 'Accounts Head') .
                                                        (!empty($req['accounts_head_at']) ? ' • Approved: ' . date('d M, Y h:i A', strtotime($req['accounts_head_at'])) : '')
                                                    );
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded bg-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between gap-1" title="<?= htmlspecialchars($ahTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>">
                                                                <?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>
                                                            </span>
                                                            <span class="badge bg-primary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Accts</span>
                                                        </div>
                                                        <?php if (!empty($req['accounts_head_at'])): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($req['accounts_head_at'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Layer 3: Management Final Approval (Only if distinct Management approved, or multiple partial approvals) -->
                                                <?php if (count($req['mgmt_approvals'] ?? []) > 1 || (($req['fr_status'] ?? '') === 'partially_approved')): ?>
                                                    <?php include __DIR__ . '/fr_mgmt_approvals_block.php'; ?>
                                                <?php elseif ($isMgmtDistinct): ?>
                                                    <?php
                                                    $approverTooltip = trim(
                                                        (!empty($req['approver_designation']) ? $req['approver_designation'] : 'Management Executive') .
                                                        (!empty($req['approver_email']) ? ' • ' . $req['approver_email'] : '') .
                                                        ($req['approved_at'] ? ' • ' . date('d M, Y h:i A', strtotime($req['approved_at'])) : '')
                                                    );
                                                    $mgmtBadgeBg = $req['status'] === 'approved' ? 'bg-success-subtle border-success-subtle' : 'bg-info-subtle border-info-subtle';
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded <?= $mgmtBadgeBg ?> border d-flex align-items-center justify-content-between gap-1" title="<?= htmlspecialchars($approverTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-circle-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>">
                                                                <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                            </span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Mgmt</span>
                                                        </div>
                                                        <?php if ($req['approved_at']): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($req['approved_at'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 180px; margin: 0 auto;">
                                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 extra-small fw-semibold">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                                </span>
                                                <span class="extra-small text-muted" title="<?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>">
                                                    By: <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Accounts Payout Status -->
                                    <td class="text-center">
                                        <?php if ($req['payout_status'] === 'Paid & Settled'): ?>
                                            <?php
                                            $payoutTooltip = trim(
                                                'Paid & Settled: à§³ ' . number_format($req['payout_amount'], 2) .
                                                (!empty($req['disbursed_by_name']) ? ' â€¢ Disbursed by: ' . $req['disbursed_by_name'] : '') .
                                                (!empty($req['disbursed_branch_name']) ? ' (' . $req['disbursed_branch_name'] . ')' : '') .
                                                (!empty($req['payout_date']) ? ' â€¢ ' . date('d M, Y', strtotime($req['payout_date'])) : '')
                                            );
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;" title="<?= htmlspecialchars($payoutTooltip) ?>">
                                                <div>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small fw-bold">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Paid: à§³ <?= number_format($req['payout_amount'], 2) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($req['disbursed_by_name'])): ?>
                                                    <div class="extra-small text-dark fw-semibold text-truncate" style="font-size: 11px;">
                                                        <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['disbursed_by_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex align-items-center justify-content-center gap-1 extra-small text-muted" style="font-size: 10px;">
                                                    <?php if (!empty($req['disbursed_branch_name'])): ?>
                                                        <span class="badge bg-light text-secondary border p-0 px-1" style="font-size: 9px;">
                                                            <i class="fa-solid fa-building me-1 text-muted"></i><?= htmlspecialchars($req['disbursed_branch_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($req['payout_date']): ?>
                                                        <span><?= date('d M, Y', strtotime($req['payout_date'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php elseif ($req['payout_status'] === 'Partially Paid'): ?>
                                            <?php
                                            $payoutTooltip = trim(
                                                'Partially Paid: à§³ ' . number_format($req['payout_amount'], 2) .
                                                (!empty($req['disbursed_by_name']) ? ' â€¢ By: ' . $req['disbursed_by_name'] : '') .
                                                (!empty($req['disbursed_branch_name']) ? ' (' . $req['disbursed_branch_name'] . ')' : '') .
                                                (!empty($req['payout_date']) ? ' â€¢ ' . date('d M, Y', strtotime($req['payout_date'])) : '')
                                            );
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;" title="<?= htmlspecialchars($payoutTooltip) ?>">
                                                <div>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 extra-small fw-bold">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Partially Paid
                                                    </span>
                                                </div>
                                                <span class="extra-small fw-bold text-success">à§³ <?= number_format($req['payout_amount'], 2) ?></span>
                                                <div class="d-flex align-items-center justify-content-center gap-1 extra-small text-muted" style="font-size: 10px;">
                                                    <?php if (!empty($req['disbursed_by_name'])): ?>
                                                        <span class="text-dark fw-semibold text-truncate"><?= htmlspecialchars($req['disbursed_by_name']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($req['payout_date']): ?>
                                                        <span><?= date('d M, Y', strtotime($req['payout_date'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-user-clock me-1"></i>Pending Accts Head
                                            </span>
                                        <?php elseif ($req['payout_status'] === 'Pending Accounts Payout' || $req['status'] === 'partially_approved'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-wallet me-1"></i>Pending Payout
                                            </span>
                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-check me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Button (Directly routes to specific module page or Debit Voucher!) -->
                                    <td class="pe-4 text-end text-nowrap">
                                        <?php
                                        $isVoucherSupported = true;
                                        $voucherUrl = $base_url . '/print-debit-voucher?module=' . urlencode($sourceTable) . '&id=' . (int)$req['source_id'];
                                        $isPaidOrSettled = in_array($payoutStatus, ['Paid & Settled', 'Settled']) || in_array(strtolower($req['status']), ['paid', 'fully_paid', 'labor_disbursed', 'deducted']);
                                        $isPartiallyPaid = ($payoutStatus === 'Partially Paid') || strtolower($req['status']) === 'partially_paid';
                                        ?>

                                        <?php if ($sourceTable === 'project_incentive_claims'): ?>
                                            <?php
                                            $shDone = (strtolower($req['sales_head_status'] ?? '') === 'approved');
                                            $ahDone = (strtolower($req['accounts_head_status'] ?? '') === 'approved');
                                            $mgmtDone = in_array(strtolower($req['status'] ?? ''), ['approved', 'paid']);
                                            $isPaid = in_array(strtolower($req['status'] ?? ''), ['paid']) || ($req['payout_status'] === 'Paid & Settled');
                                            ?>
                                            <?php if ($statusLower === 'rejected'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                    <i class="fa-solid fa-ban me-1"></i>Rejected
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isPaid): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                    <i class="fa-solid fa-check-circle me-1"></i>Settled
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($mgmtDone): ?>
                                                <?php if ($isAccountsUser): ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold me-1" title="Disburse Incentive Payout">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Disburse Payout
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                        <i class="fa-solid fa-clock me-1"></i>Ready for Disbursement
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'sales_head'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="approveSalesHead(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? 'Sales Incentive Claim')) ?>', '<?= number_format($req['request_amount'], 2) ?>')"
                                                        title="Approve as Sales Head or Operation Head">
                                                        <i class="fa-solid fa-check me-1"></i>Approve (Sales Head / Op Head)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 shadow-sm fw-semibold me-1"
                                                        onclick="rejectSalesHead(<?= (int)$req['id'] ?>)"
                                                        title="Reject Claim">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Sales Team Head or Operation Head review">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Pending Sales Head or Operation Head
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'accounts_head'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="openAccountsHeadApprovalModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? '')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>', 'project_incentive_claims')"
                                                        title="Approve as Accounts Head">
                                                        <i class="fa-solid fa-stamp me-1"></i>Approve (Accounts Head)
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Accounts Head verification">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'management'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-success rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="approveManagement(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? 'Sales Incentive Claim')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>')"
                                                        title="Approve as Management">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Approve (Management)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 shadow-sm fw-semibold me-1"
                                                        onclick="rejectManagement(<?= (int)$req['id'] ?>)"
                                                        title="Reject Claim">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Management approval">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Management
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye me-1"></i>View Module Page
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isVoucherSupported && !$isPaidOrSettled): ?>
                                            <a href="<?= $voucherUrl ?>&is_approval=1" target="_blank" class="btn btn-sm btn-outline-info rounded-2 px-2 shadow-sm fw-semibold mb-1" title="View & Print Approval Voucher">
                                                <i class="fa-solid fa-file-signature me-1"></i>Approval Voucher
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isPartiallyPaid): ?>
                                            <?php if ($isAccountsUser && $isAccountsHeadApproved): ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-2 shadow-sm fw-semibold me-1" title="Disburse remaining amount">
                                                    <i class="fa-solid fa-money-check-dollar me-1"></i>Pay
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($isVoucherSupported): ?>
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold" title="View, Print & Download Payment Voucher">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isPaidOrSettled): ?>
                                            <?php if ($isVoucherSupported): ?>
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <?php if ($isAccountsHeadUser || (int)($_SESSION['user']['role_id'] ?? 0) === 1): ?>
                                                <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" 
                                                    onclick="openAccountsHeadApprovalModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? '')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>')"
                                                    title="Approve as Accounts Head">
                                                    <i class="fa-solid fa-stamp me-1"></i>Approve (Accounts Head)
                                                </button>
                                                <?php if ($isFrPartialMgmt && $isManagementUser): ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="Approve / Reject the remaining (unapproved) balance">
                                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Remaining
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isManagementUser): ?>
                                                <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Amount exceeds ৳10,000 - Accounts Head payment verification required before Management">
                                                    <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isAccountsStaffUser): ?>
                                                <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Payment locked until Accounts Head (Md. Mamunoor Rashid [1309]) approves">
                                                    <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye me-1"></i>View Module Page
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isAccountsUser && in_array($payoutStatus, ['Pending Accounts Payout']) && $isAccountsHeadApproved): ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Pay / Disburse
                                            </a>

                                        <?php elseif (in_array($req['status'], ['pending', 'partially_approved'])): ?>
                                            <?php if ($isChiefOfficer): ?>
                                                <a href="<?= !empty($targetUrl) && $targetUrl !== 'javascript:void(0)' ? $targetUrl : 'javascript:void(0)' ?>"
                                                    class="btn btn-sm btn-light text-dark rounded-2 px-2 border"
                                                    title="Pending for <?= htmlspecialchars(trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', $req['pending_for_text'] ?? 'Management')) ?: 'Management') ?>">
                                                    <i class="fa-solid fa-eye me-1"></i>View Details
                                                </a>
                                            <?php elseif ($canUserApprove): ?>
                                                <?php if ($sourceTable === 'lead_bookings'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold"
                                                        onclick="openDiscountApprovalModal(<?= htmlspecialchars(json_encode([
                                                            'id' => (int)$req['id'],
                                                            'source_id' => (int)$req['source_id'],
                                                            'title' => $req['title'] ?? 'Lead Booking Discount Approval',
                                                            'amount' => (float)$req['request_amount'],
                                                            'client_name' => $req['booking_client_name'] ?? '',
                                                            'project_name' => $req['booking_project_name'] ?? '',
                                                            'flat_name' => $req['booking_flat_name'] ?? '',
                                                            'floor_no' => $req['booking_floor_no'] ?? '',
                                                            'sales_agent' => $req['booking_sales_agent'] ?? '',
                                                            'sales_code' => $req['booking_sales_code'] ?? '',
                                                            'agreement_value' => $req['booking_agreement_value'] ?? 0,
                                                            'attachment' => $req['attachment'] ?? '',
                                                            'op_head_status' => $req['op_head_status'] ?? '',
                                                            'op_head_name' => $req['op_head_name'] ?? '',
                                                            'op_head_at' => $req['op_head_at'] ?? '',
                                                            'status' => $req['status'] ?? '',
                                                            'booking_url' => $targetUrl
                                                        ])) ?>)"
                                                        title="Review & Process Booking Discount">
                                                        <i class="fa-solid fa-stamp me-1"></i>Review / Process
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold">
                                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php
                                                $pfClean = trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_for_text'] ?? '')));
                                                $opHeadDone = (strtolower($req['op_head_status'] ?? '') === 'approved');
                                                if ($isFrPartialMgmt) {
                                                    $pfShort = $isAccountsHeadApproved ? 'Accounts / Mgmt (Remaining)' : 'Accounts Head';
                                                } else {
                                                    $pfShort = $pfClean !== '' ? $pfClean : 'Other Role';
                                                }
                                                if (mb_strlen($pfShort) > 30) {
                                                    $pfShort = mb_substr($pfShort, 0, 28) . 'â€¦';
                                                }
                                                $ahDone = ($ahStatus === 'approved');
                                                $awaitOpHead = ($reqAmount > 10000.01 && !$opHeadDone && $isManagementUser);
                                                $awaitAh = ($reqAmount > 10000.01 && $opHeadDone && !$ahDone && $isManagementUser);
                                                if ($awaitOpHead) {
                                                    $lockLabel = 'Awaiting Op Head';
                                                    $lockTitle = 'Amount exceeds ৳10,000 - Admin & Operation Head approval required first';
                                                } elseif ($awaitAh) {
                                                    $lockLabel = 'Awaiting Accounts Head';
                                                    $lockTitle = 'Amount exceeds ৳10,000 - Accounts Head payment verification required before Management';
                                                } else {
                                                    $lockLabel = 'Pending: ' . $pfShort;
                                                    $lockTitle = (($isSiteOperator && $opHeadDone) ? 'You (Admin & Operation Head) already approved. ' : '') . 'Pending for ' . ($pfClean !== '' ? $pfClean : 'Other Role');
                                                }
                                                ?>
                                                <a href="javascript:void(0)"
                                                    class="btn btn-sm btn-light text-muted rounded-2 px-2 border"
                                                    title="<?= htmlspecialchars($lockTitle) ?>">
                                                    <i class="fa-solid <?= ($isSiteOperator && $opHeadDone) ? 'fa-circle-check text-success' : 'fa-lock text-warning' ?> me-1"></i><?= htmlspecialchars($lockLabel) ?>
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <a href="<?= $targetUrl ?>"
                                                class="btn btn-sm btn-light text-dark rounded-2 px-2 border">
                                                <i class="fa-solid fa-eye me-1"></i>View Module Page
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
<?php endif; ?>
