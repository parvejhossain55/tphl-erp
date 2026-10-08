<?php
$countAll        = count($requests ?? []);
$countPoRejected = 0;
$countApproved   = 0;
$countPoIssued   = 0;
$countSubmitted  = 0;
$countDraft      = 0;
$countRejected   = 0;

foreach (($requests ?? []) as $reqItem) {
    match ($reqItem['status'] ?? '') {
        'PO Rejected' => $countPoRejected++,
        'Approved'    => $countApproved++,
        'PO Issued'   => $countPoIssued++,
        'Submitted'   => $countSubmitted++,
        'Draft'       => $countDraft++,
        'Rejected'    => $countRejected++,
        default       => null,
    };
}
?>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Requests</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-purchase-pending-report" class="btn btn-outline-warning btn-sm fw-bold shadow-sm text-nowrap">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> PR Pending Report
        </a>
        <button type="button" class="btn btn-outline-info btn-sm fw-bold shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#leadTimeReportModal">
            <i class="bi bi-table me-1"></i> Lead Time Report
        </button>
        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
            <a href="<?= $base_url ?>/con-purchase-request-create" class="btn btn-primary btn-sm shadow-sm text-nowrap">
                <i class="bi bi-plus-circle me-1"></i> New Purchase Request
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="section">
    <!-- Filter Toolbar: Tabs / Pills + Status Dropdown -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary pr-filter-pill active" data-status="All" onclick="filterPRTable('All', this)">
                <i class="bi bi-grid-fill me-1"></i> All <span class="badge bg-secondary ms-1 rounded-pill"><?= $countAll ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-danger pr-filter-pill <?= $countPoRejected > 0 ? 'border-2 fw-bold' : '' ?>" data-status="PO Rejected" onclick="filterPRTable('PO Rejected', this)">
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> PO Rejected 
                <span class="badge bg-danger text-white ms-1 rounded-pill"><?= $countPoRejected ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-success pr-filter-pill" data-status="Approved" onclick="filterPRTable('Approved', this)">
                <i class="bi bi-check-circle-fill text-success me-1"></i> Approved 
                <span class="badge bg-success text-white ms-1 rounded-pill"><?= $countApproved ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-primary pr-filter-pill" data-status="PO Issued" onclick="filterPRTable('PO Issued', this)">
                <i class="bi bi-file-earmark-check-fill  me-1"></i> PO Issued 
                <span class="badge bg-primary text-white ms-1 rounded-pill"><?= $countPoIssued ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-info pr-filter-pill" data-status="Submitted" onclick="filterPRTable('Submitted', this)">
                <i class="bi bi-hourglass-split text-info me-1"></i> Pending PM 
                <span class="badge bg-info text-dark ms-1 rounded-pill"><?= $countSubmitted ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-dark pr-filter-pill" data-status="Draft" onclick="filterPRTable('Draft', this)">
                <i class="bi bi-pencil-fill text-muted me-1"></i> Draft 
                <span class="badge bg-light text-dark border ms-1 rounded-pill"><?= $countDraft ?></span>
            </button>

            <?php if ($countRejected > 0): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary pr-filter-pill" data-status="Rejected" onclick="filterPRTable('Rejected', this)">
                <i class="bi bi-x-circle-fill text-danger me-1"></i> PR Rejected 
                <span class="badge bg-secondary ms-1 rounded-pill"><?= $countRejected ?></span>
            </button>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <label for="prStatusFilterSelect" class="small text-muted fw-bold mb-0 text-nowrap">
                <i class="bi bi-funnel-fill  me-1"></i>Status Filter:
            </label>
            <select id="prStatusFilterSelect" class="form-select form-select-sm shadow-xs" style="min-width: 175px; font-weight: 600;" onchange="onStatusSelectChange(this.value)">
                <option value="All">All Statuses (<?= $countAll ?>)</option>
                <option value="PO Rejected"> PO Rejected (<?= $countPoRejected ?>)</option>
                <option value="Approved">✓ Approved (<?= $countApproved ?>)</option>
                <option value="PO Issued">PO Issued (<?= $countPoIssued ?>)</option>
                <option value="Submitted">Pending PM (<?= $countSubmitted ?>)</option>
                <option value="Draft">Draft (<?= $countDraft ?>)</option>
                <?php if ($countRejected > 0): ?>
                    <option value="Rejected">PR Rejected (<?= $countRejected ?>)</option>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0" id="purchaseRequestsTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="text-center">Items</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created By</th>
                            <th>Required Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $i => $r): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-muted">PR-<?= $r['id'] ?></span>
                                        <?php if (!empty($r['is_urgent'])): ?>
                                            <br><span class="badge bg-danger text-white mt-1" style="font-size:9.5px;letter-spacing:0.5px" title="<?= htmlspecialchars($r['urgent_reason'] ?? 'Urgent Requisition') ?>">
                                                <i class="bi bi-lightning-fill"></i> URGENT
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['revision_count']) && (int)$r['revision_count'] > 0): ?>
                                            <br><span class="badge bg-light  border mt-1" style="font-size:9.5px;">Rev #<?= $r['revision_count'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($r['project_name']) ?>
                                        <?php if (!empty($r['latest_note_author'])): ?>
                                            <div class="small fw-normal mt-1 p-1 rounded" style="font-size:10px; background:rgba(139,92,246,.08); color:#6d28d9; border: 1px solid rgba(139,92,246,.2);" title="<?= htmlspecialchars($r['latest_note_text']) ?>">
                                                <i class="fal fa-comment-dots me-1"></i><strong><?= htmlspecialchars($r['latest_note_author']) ?>:</strong> <?= htmlspecialchars(mb_strimwidth($r['latest_note_text'], 0, 30, '...')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['category_names'])): ?>
                                            <div class="d-flex flex-wrap gap-1" style="max-width:200px;" title="Requested Categories">
                                                <?php 
                                                $cats = array_filter(array_map('trim', explode(',', $r['category_names'])));
                                                foreach ($cats as $catName): ?>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle py-1 px-2" style="font-size:10.5px; font-weight:600; white-space:normal; text-align:left;">
                                                        <i class="bi bi-tag-fill me-1 "></i><?= htmlspecialchars($catName) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $prType = $r['pr_type'] ?? 'boq_linked'; ?>
                                        <?php if ($prType === 'independent'): ?>
                                            <span class="badge" style="background:rgba(245,158,11,.15);color:#b45309;border:1px solid rgba(245,158,11,.3);font-size:10px;font-weight:700;">
                                                 Not link with monthly target
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(37,99,235,.1);color:#1d4ed8;border:1px solid rgba(37,99,235,.2);font-size:10px;font-weight:700;">
                                                 Link with monthly target
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $r['item_count'] ?> items</span>
                                    </td>
                                    <td data-filter="<?= htmlspecialchars($r['status']) ?>" data-search="<?= htmlspecialchars($r['status'] . ' ' . ($r['po_rejection_note'] ?? '') . ' ' . ($r['po_rejected_by_name'] ?? '')) ?>">
                                        <?php
                                        $badge = match ($r['status']) {
                                            'Approved'    => 'bg-success text-white',
                                            'PO Issued'   => 'bg-primary text-white',
                                            'PO Rejected' => 'bg-danger text-white',
                                            'Rejected'    => 'bg-danger text-white',
                                            'Submitted'   => 'bg-info text-dark',
                                            default       => 'bg-warning text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?> px-2 py-1">
                                            <?php if ($r['status'] === 'PO Rejected'): ?>
                                                <i class="bi bi-exclamation-octagon-fill me-1"></i> PO Rejected
                                            <?php else: ?>
                                                <?= $r['status'] ?>
                                            <?php endif; ?>
                                        </span>

                                        <?php if ($r['status'] === 'PO Rejected'): ?>
                                            <?php if (!empty($r['po_rejection_note'])): ?>
                                                <div class="mt-1 p-1 rounded" style="font-size:11px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; max-width:230px;" title="<?= htmlspecialchars($r['po_rejection_note']) ?>">
                                                    <i class="bi bi-chat-quote-fill me-1 text-danger"></i><strong>Note:</strong> <?= htmlspecialchars(mb_strimwidth($r['po_rejection_note'], 0, 42, '...')) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['po_rejected_by_name'])): ?>
                                                <div class="text-muted mt-1" style="font-size:10px;">
                                                    By: <?= htmlspecialchars($r['po_rejected_by_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($r['status'] === 'Submitted'): ?>
                                            <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                                <i class="bi bi-hourglass-split"></i> Pending: Project Manager
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'Approved' && in_array($userRole, [1, 3, 17, 22, 31])): ?>
                                            <form action="<?= $base_url ?>/con-purchase-request-assign/<?= $r['id'] ?>"
                                                method="POST" class="d-flex align-items-center gap-1">
                                                <select name="executive_id" class="form-select form-select-sm"
                                                    style="width: auto; min-width: 130px; font-size:12px;" required>
                                                    <option value="">- Assign Exec -</option>
                                                    <?php foreach ($executives as $exec): ?>
                                                        <option value="<?= $exec['id'] ?>" <?= ($r['assigned_executive_id'] == $exec['id']) ? 'selected' : '' ?>>
                                                             <?= htmlspecialchars($exec['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary py-0 px-2" style="height: 28px;"
                                                    title="Assign">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <?php if (!empty($r['assigned_executive_name'])): ?>
                                                <span class="badge bg-secondary"><i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($r['assigned_executive_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Unassigned</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['created_by_name'] ?? '—') ?></td>
                                    <td>
                                        <?php 
                                        $reqDate = !empty($r['effective_required_date']) ? $r['effective_required_date'] : (!empty($r['required_date']) ? $r['required_date'] : null);
                                        ?>
                                        <?php if (!empty($reqDate)): ?>
                                            <div class="fw-bold <?= !empty($r['is_urgent']) ? 'text-danger' : '' ?>" style="font-size:12px;">
                                                <i class="bi <?= !empty($r['is_urgent']) ? 'bi-lightning-charge-fill' : 'bi-calendar-event' ?> me-1"></i><?= date('d M Y', strtotime($reqDate)) ?>
                                            </div>
                                            <div class="text-muted" style="font-size:10.5px;">
                                                <span title="Created on">Req: <?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark" style="font-size:12px;">
                                                <?= date('d M Y', strtotime($r['created_at'])) ?>
                                            </div>
                                            <div class="text-muted small" style="font-size:10px;">(Created Date)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <a href="<?= $base_url ?>/con-purchase-request/<?= $r['id'] ?>"
                                            class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php
                                        $reqUserId = (int)($_SESSION['user']['id'] ?? 0);
                                        $isPRCreator = (int)($r['created_by'] ?? 0) === $reqUserId;
                                        $isPRAdmin = in_array($userRole, [1, 3]);
                                        $isPurchase = in_array($userRole, [17, 23, 31]);
                                        $isPM = in_array($userRole, [12, 13, 15]);
                                        $canEditPR = ($isPRCreator || $isPRAdmin || $isPurchase || $isPM);
                                        ?>

                                        <?php if ($r['status'] === 'PO Rejected' && $canEditPR): ?>
                                            <a href="<?= $base_url ?>/con-purchase-request-edit/<?= $r['id'] ?>"
                                                class="btn btn-sm btn-danger text-white ms-1 fw-bold" title="Revise & Update Rejected Request">
                                                <i class="bi bi-pencil-square me-1"></i> Revise
                                            </a>
                                        <?php elseif ($canEditPR && $r['status'] !== 'PO Issued'): ?>
                                            <a href="<?= $base_url ?>/con-purchase-request-edit/<?= $r['id'] ?>"
                                                class="btn btn-sm btn-outline-primary ms-1" title="Edit Request">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($r['status'] === 'Approved'): ?>
                                            <?php if ($userRole === 23 || $userRole === 31 || in_array($userRole, [1, 3])): ?>
                                                <a href="<?= $base_url ?>/con-quotation-create/<?= $r['id'] ?>"
                                                    class="btn btn-sm btn-outline-success ms-1" title="Add Quotation & CST">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php
                                        $canDeletePR = ($isPRCreator || $isPRAdmin || $isPurchase);
                                        ?>
                                        <?php if ($canDeletePR && $r['status'] !== 'PO Issued'): ?>
                                            <a href="<?= $base_url ?>/con-purchase-request-delete/<?= $r['id'] ?>"
                                                class="btn btn-sm btn-outline-danger ms-1" title="Delete Request"
                                                onclick="return confirm('Are you sure you want to delete this purchase request?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No purchase requests yet.
                                    <a href="<?= $base_url ?>/con-purchase-request-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================
     LEAD TIME REPORT MODAL (EXCEL-LIKE TABLE)
     ========================================================= -->
<div class="modal fade" id="leadTimeReportModal" tabindex="-1" aria-labelledby="leadTimeReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg card__wrapper" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header" style="background-color: #16a34a !important; border:none; padding:18px 24px;">
                <h5 class="modal-title fw-bold text-white mb-0" id="leadTimeReportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> Materials Procurement Lead Time
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="text-center py-3 border-bottom">
                    <h6 class="fw-bold mb-1" style="font-size:16px;">Materials Procurement Lead Time</h6>
                    <div class="text-muted fw-semibold" style="font-size:14px;">The Premium Homes Ltd</div>
                </div>
                
                <div class="table-responsive" style="max-height: 60vh;">
                    <form id="leadTimeUpdateForm" action="<?= $base_url ?>/con-materials-update-leadtimes-bulk" method="POST">
                        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 13px;" id="leadTimeReportTable">
                            <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr class="text-center align-middle text-white" style="background-color: #16a34a !important;">
                                    <th style="width:5%; font-weight:600; padding:12px;">SL No.</th>
                                    <th style="width:15%; font-weight:600; padding:12px;">Item Name</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">Item Type</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">PR to Qtn Collection</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">CS Analysis & Negotiation</th>
                                    <th style="width:12%; font-weight:600; padding:12px;">Audit/HO Operation/DCEO/Approval</th>
                                    <th style="width:8%; font-weight:600; padding:12px;">WO/PO</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">Advance Payment if Required</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">Transport to Site</th>
                                    <th style="width:10%; font-weight:600; padding:12px;">Total Delivery Lead Time</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php 
                                $canEditLeadTime = in_array($_SESSION['user']['role_id'] ?? 0, [3, 17, 20, 31]);
                                $sl = 1;
                                $prevType = null;
                                foreach ($categoriesWithLeadTime ?? [] as $cat): 
                                    $isNewType = ($cat['item_type'] !== $prevType);
                                    $prevType = $cat['item_type'];
                                ?>
                                    <tr <?= $isNewType ? 'style="border-top: 2px solid var(--primary-green, #94a3b8);"' : '' ?>>
                                        <td class="fw-semibold text-muted"><?= $sl++ ?></td>
                                        <td class="text-start fw-bold px-3"><?= htmlspecialchars($cat['name']) ?></td>
                                        <td><span class="badge bg-secondary bg-opacity-25 "><?= htmlspecialchars($cat['item_type']) ?></span></td>
                                        
                                        <?php if ($canEditLeadTime): ?>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][pr_to_qtn_days]" value="<?= $cat['pr_to_qtn_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][cs_analysis_days]" value="<?= $cat['cs_analysis_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][approval_days]" value="<?= $cat['approval_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][wo_po_days]" value="<?= $cat['wo_po_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][adv_payment_days]" value="<?= $cat['adv_payment_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                            <td class="p-1"><input type="number" name="leadtimes[<?= $cat['id'] ?>][transport_days]" value="<?= $cat['transport_days'] ?>" class="editable-input lead-time-input" min="0"></td>
                                        <?php else: ?>
                                            <td><?= $cat['pr_to_qtn_days'] ?></td>
                                            <td><?= $cat['cs_analysis_days'] ?></td>
                                            <td><?= $cat['approval_days'] ?></td>
                                            <td><?= $cat['wo_po_days'] ?></td>
                                            <td><?= $cat['adv_payment_days'] ?></td>
                                            <td><?= $cat['transport_days'] ?></td>
                                        <?php endif; ?>

                                        <td class="fw-bold fs-6 lead-time-total text-success"><?= $cat['total_lead_days'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>

                <div class="p-3 border-top">
                    <strong class="d-block mb-2" style="font-size:13px;">Note:</strong>
                    <ol class="text-muted mb-0 ps-3" style="font-size:12px; line-height: 1.6;">
                        <li>For Structural Items, PR must be submitted once a month.</li>
                        <li>PRs for the remaining items must be submitted every Wednesday and Thursday of each week.</li>
                        <li>For any emergency items, we may accept emergency PRs up to a maximum of 2% of the total monthly PR volume, provided that proper justification is submitted along with each emergency PR.</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer border-top-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                <?php if (isset($canEditLeadTime) && $canEditLeadTime): ?>
                    <button type="button" class="btn btn-success btn-sm px-4 fw-bold" style="background-color: #16a34a !important; border-color: #16a34a !important;" onclick="document.getElementById('leadTimeUpdateForm').submit();">Save Changes</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern styling for the table inside modal */
#leadTimeReportTable th {
    border-bottom: none;
    white-space: pre-wrap;
    line-height: 1.3;
}
#leadTimeReportTable td {
    vertical-align: middle;
    padding: 10px 8px;
    border-color: inherit;
}
.editable-input {
    border: 1px solid transparent;
    background: transparent;
    width: 100%;
    text-align: center;
    color: inherit;
    font-weight: inherit;
    font-size: 13px;
    padding: 4px;
}
.editable-input:focus {
    outline: none;
    background: #fff;
    border: 1px solid #0d6efd;
    border-radius: 4px;
}
/* Chrome, Safari, Edge, Opera hide input arrows */
.editable-input::-webkit-outer-spin-button,
.editable-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>

<script>
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('lead-time-input')) {
        let row = e.target.closest('tr');
        let inputs = row.querySelectorAll('.lead-time-input');
        let total = 0;
        inputs.forEach(function(input) {
            total += parseInt(input.value) || 0;
        });
        row.querySelector('.lead-time-total').innerText = total;
    }
});

window.filterPRTable = function (status, btnElement) {
    document.querySelectorAll('.pr-filter-pill').forEach(function (pill) {
        pill.classList.remove('active');
        if (pill.classList.contains('btn-secondary')) {
            pill.classList.replace('btn-secondary', 'btn-outline-secondary');
        } else if (pill.classList.contains('btn-danger')) {
            pill.classList.replace('btn-danger', 'btn-outline-danger');
        } else if (pill.classList.contains('btn-success')) {
            pill.classList.replace('btn-success', 'btn-outline-success');
        } else if (pill.classList.contains('btn-primary')) {
            pill.classList.replace('btn-primary', 'btn-outline-primary');
        } else if (pill.classList.contains('btn-info')) {
            pill.classList.replace('btn-info', 'btn-outline-info');
        } else if (pill.classList.contains('btn-dark')) {
            pill.classList.replace('btn-dark', 'btn-outline-dark');
        }
    });

    if (btnElement) {
        btnElement.classList.add('active');
        if (btnElement.classList.contains('btn-outline-secondary')) {
            btnElement.classList.replace('btn-outline-secondary', 'btn-secondary');
        } else if (btnElement.classList.contains('btn-outline-danger')) {
            btnElement.classList.replace('btn-outline-danger', 'btn-danger');
        } else if (btnElement.classList.contains('btn-outline-success')) {
            btnElement.classList.replace('btn-outline-success', 'btn-success');
        } else if (btnElement.classList.contains('btn-outline-primary')) {
            btnElement.classList.replace('btn-outline-primary', 'btn-primary');
        } else if (btnElement.classList.contains('btn-outline-info')) {
            btnElement.classList.replace('btn-outline-info', 'btn-info');
        } else if (btnElement.classList.contains('btn-outline-dark')) {
            btnElement.classList.replace('btn-outline-dark', 'btn-dark');
        }
    }

    // Keep dropdown in sync
    var sel = document.getElementById('prStatusFilterSelect');
    if (sel && sel.value !== status) {
        sel.value = status;
    }

    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        var dt = jQuery('#purchaseRequestsTable').DataTable();
        if (!status || status === 'All') {
            dt.column(5).search('').draw();
        } else {
            // Regex search for exact status in column 5
            var escaped = jQuery.fn.dataTable.util.escapeRegex(status);
            dt.column(5).search('^' + escaped + '$', true, false).draw();
        }
    }
};

window.onStatusSelectChange = function (val) {
    var matchingBtn = document.querySelector('.pr-filter-pill[data-status="' + val + '"]');
    filterPRTable(val, matchingBtn);
};

document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        var table = jQuery('#purchaseRequestsTable').DataTable({
            destroy: true,
            responsive: true,
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [], // Preserve controller's ranking & SQL order
            columnDefs: [
                { orderable: false, searchable: false, targets: 0 },  // # serial column
                { orderable: false, searchable: false, targets: -1 } // Actions column
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Quick filter in table...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                emptyTable: "No purchase requests found.",
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            }
        });

        // Check URL params for pre-selected filter (e.g. ?status=po_rejected or ?filter=Approved)
        var urlParams = new URLSearchParams(window.location.search);
        var filterParam = urlParams.get('filter') || urlParams.get('status');
        if (filterParam) {
            var targetStatus = filterParam.replace('_', ' ');
            var matchingBtn = document.querySelector('.pr-filter-pill[data-status="' + targetStatus + '"]');
            if (matchingBtn) {
                matchingBtn.click();
            }
        }
    }
});
</script>