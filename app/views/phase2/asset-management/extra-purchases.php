<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);

$overallBadges = [
    'pending' => 'warning', 'in_review' => 'info', 'approved' => 'success',
    'rejected' => 'danger', 'purchased' => 'primary'
];
$stepBadges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <h1 class="page-title fw-semibold fs-18 mb-0">Extra Asset Purchase Requests</h1>
        <div class="ms-md-1 ms-0">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/home">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-products">Asset Management</a></li>
                    <li class="breadcrumb-item active">Extra Purchases</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Alert Messages -->
    <div class="row">
        <div class="col-xl-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Flow Info -->
    <div class="alert alert-info-transparent border fs-12 mb-4">
        <i class="fa-solid fa-circle-info me-1"></i>
        <strong>Approval Flow:</strong> 
        Operations (Request) → Purchase Head (Step 1) → QC & Internal Audit (Step 2) → Management (Step 3) → Accounts (Payment)
    </div>

    <!-- Main Card -->
    <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
            <div class="card-title">Purchase Requests</div>
            <div class="d-flex gap-2">
                <?php if (!empty($canCreate)): ?>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="fa-solid fa-plus"></i> New Request
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body border-bottom pb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects ?? [] as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($_GET['project_id'] ?? '') == $proj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_review" <?= ($_GET['status'] ?? '') === 'in_review' ? 'selected' : '' ?>>In Review</option>
                        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="purchased" <?= ($_GET['status'] ?? '') === 'purchased' ? 'selected' : '' ?>>Purchased</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases" class="btn btn-light btn-sm ms-1"><i class="fa-solid fa-arrows-rotate"></i></a>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total (BDT)</th>
                            <th>Requested By</th>
                            <th class="text-center">Purchase Head</th>
                            <th class="text-center">QC & Audit</th>
                            <th class="text-center">Management</th>
                            <th class="text-center">Payment</th>
                            <th>Overall</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">No purchase requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td class="fw-semibold">#<?= $r['id'] ?></td>
                                    <td class="fs-12"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                    <td class="fs-12 fw-semibold"><?= htmlspecialchars($r['project_name'] ?? '—') ?></td>
                                    <td>
                                        <span class="fs-12 fw-semibold"><?= htmlspecialchars($r['item_name']) ?></span>
                                    </td>
                                    <td class="text-center"><?= $r['quantity'] ?></td>
                                    <td class="text-end fw-semibold">
                                        <?= number_format($r['total_price'], 2) ?>
                                        <?php if (!empty($r['paid_amount']) && $r['paid_amount'] > 0): ?>
                                            <br><small class="text-success fs-10">Paid: <?= number_format($r['paid_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fs-12"><?= htmlspecialchars($r['requested_by_name'] ?? '—') ?></td>
                                    <!-- Step 1: Purchase Head -->
                                    <td class="text-center">
                                        <?php
                                        $phColor = $r['purchase_head_status'] === 'approved' ? '#198754' : ($r['purchase_head_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $phBg = $r['purchase_head_status'] === 'approved' ? '#d1e7dd' : ($r['purchase_head_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $phBg ?>;color:<?= $phColor ?>;font-weight:600;">
                                            <?= ucfirst($r['purchase_head_status']) ?>
                                        </span>
                                    </td>
                                    <!-- Step 2: QC & Audit -->
                                    <td class="text-center">
                                        <?php
                                        $qcColor = $r['qc_audit_status'] === 'approved' ? '#198754' : ($r['qc_audit_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $qcBg = $r['qc_audit_status'] === 'approved' ? '#d1e7dd' : ($r['qc_audit_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $qcBg ?>;color:<?= $qcColor ?>;font-weight:600;">
                                            <?= ucfirst($r['qc_audit_status']) ?>
                                        </span>
                                    </td>
                                    <!-- Step 3: Management -->
                                    <td class="text-center">
                                        <?php
                                        $mgColor = $r['mgmt_status'] === 'approved' ? '#198754' : ($r['mgmt_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $mgBg = $r['mgmt_status'] === 'approved' ? '#d1e7dd' : ($r['mgmt_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $mgBg ?>;color:<?= $mgColor ?>;font-weight:600;">
                                            <?= ucfirst($r['mgmt_status']) ?>
                                        </span>
                                    </td>
                                    <!-- Step 4: Payment -->
                                    <td class="text-center">
                                        <?php
                                        $pyColor = $r['payment_status'] === 'paid' ? '#198754' : '#e6a817';
                                        $pyBg = $r['payment_status'] === 'paid' ? '#d1e7dd' : '#fff3cd';
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $pyBg ?>;color:<?= $pyColor ?>;font-weight:600;">
                                            <?= ucfirst($r['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $ovColors = [
                                            'pending' => ['#e6a817','#fff3cd'], 'in_review' => ['#0d6efd','#cfe2ff'],
                                            'approved' => ['#198754','#d1e7dd'], 'rejected' => ['#dc3545','#f8d7da'],
                                            'paid' => ['#0d6efd','#cfe2ff'],
                                            'purchased' => ['#6f42c1','#e2d9f3']
                                        ];
                                        $oc = $ovColors[$r['overall_status']] ?? ['#6c757d','#e9ecef'];
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $oc[1] ?>;color:<?= $oc[0] ?>;font-weight:700;">
                                            <?= ucfirst(str_replace('_', ' ', $r['overall_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        // Determine available action based on role and status
                                        $canApprove = false;
                                        $step = '';
                                        
                                        if (!empty($isPurchaseHead) && $r['purchase_head_status'] === 'pending') {
                                            $canApprove = true; $step = 'purchase_head';
                                        } elseif (!empty($isQcAudit) && $r['purchase_head_status'] === 'approved' && $r['qc_audit_status'] === 'pending') {
                                            $canApprove = true; $step = 'qc_audit';
                                        } elseif (!empty($isManagement) && $r['qc_audit_status'] === 'approved' && $r['mgmt_status'] === 'pending') {
                                            $canApprove = true; $step = 'mgmt';
                                        }
                                        
                                        $canPay = !empty($isAccounts) && $r['mgmt_status'] === 'approved' && $r['payment_status'] === 'unpaid';
                                        $canConfirmPurchase = !empty($canCreate) && $r['payment_status'] === 'paid' && $r['overall_status'] === 'paid';
                                        ?>

                                        <!-- Always show View button -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" title="View"
                                            data-info='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'
                                            onclick="openDetailModal(this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <?php if ($canApprove): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success btn-icon" title="Approve"
                                                onclick="openApprovalModal(<?= $r['id'] ?>, 'approved', '<?= $step ?>')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Reject"
                                                onclick="openApprovalModal(<?= $r['id'] ?>, 'rejected', '<?= $step ?>')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php elseif ($canPay): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" title="Process Payment"
                                                onclick="openPaymentModal(<?= $r['id'] ?>)">
                                                <i class="fa-solid fa-money-bill me-1"></i> Pay
                                            </button>
                                        <?php elseif ($canConfirmPurchase): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="Mark as Purchased"
                                                onclick="openConfirmPurchaseModal(<?= $r['id'] ?>)">
                                                <i class="fa-solid fa-box-check me-1"></i> Mark Purchased
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($canCreate)): ?>
<!-- ========================================================================= -->
<!-- MODAL: NEW PURCHASE REQUEST -->
<!-- ========================================================================= -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/store" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Extra Asset Purchase Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects ?? [] as $proj): ?>
                                    <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="asset_location" class="form-select" required>
                                <option value="">-- Select Location --</option>
                                <option value="Site Office">Site Office</option>
                                <option value="Site Office 2">Site Office 2</option>
                                <option value="Zonal Office">Zonal Office</option>
                                <option value="Corporate Office">Corporate Office</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="itemName" class="form-control" placeholder="e.g. Dell Laptop 15-inch" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="qty" class="form-control" value="1" min="1" required onchange="calcTotal()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price (BDT) <span class="text-danger">*</span></label>
                            <input type="number" name="unit_price" id="unitPrice" class="form-control" step="0.01" min="0" required onchange="calcTotal()">
                        </div>
                        <div class="col-12">
                            <div class="bg-light p-2 rounded text-end">
                                <strong>Total: ৳ <span id="totalDisplay">0.00</span></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Justification <span class="text-danger">*</span></label>
                            <textarea name="justification" class="form-control" rows="3" placeholder="Why is this purchase needed?" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: APPROVAL ACTION -->
<!-- ========================================================================= -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="approvalForm" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalTitle">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="approval_status" id="approvalStatus">
                    <div class="mb-3">
                        <label class="form-label">Note / Remarks</label>
                        <textarea name="approval_note" class="form-control" rows="3" placeholder="Add your comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm" id="approvalSubmitBtn">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PAYMENT -->
<!-- ========================================================================= -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentForm" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Paid Amount (BDT) <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" required placeholder="Enter amount paid">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Receipt (Optional)</label>
                        <input type="file" name="payment_attachment" class="form-control" accept="image/*,application/pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Process Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL VIEW (Professional) -->
<!-- ========================================================================= -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="detailTitle">Purchase Request #1</h5>
                    <small class="text-muted" id="detailDate"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2" id="detailModalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.approval-pipeline { display: flex; align-items: flex-start; justify-content: space-between; position: relative; padding: 0 10px; margin: 20px 0 10px; }
.approval-pipeline::before { content: ''; position: absolute; top: 20px; left: 40px; right: 40px; height: 3px; background: #e9ecef; z-index: 0; }
.approval-step { text-align: center; position: relative; z-index: 1; flex: 1; }
.step-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 700; font-size: 14px; border: 3px solid #e9ecef; background: #fff; transition: all 0.3s; }
.step-circle.pending { border-color: #ffc107; color: #ffc107; background: #fff8e1; }
.step-circle.approved { border-color: #198754; color: #fff; background: #198754; }
.step-circle.rejected { border-color: #dc3545; color: #fff; background: #dc3545; }
.step-circle.waiting { border-color: #0d6efd; color: #0d6efd; background: #e7f1ff; animation: pulse-ring 1.5s infinite; }
@keyframes pulse-ring { 0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.4); } 70% { box-shadow: 0 0 0 8px rgba(13,110,253,0); } 100% { box-shadow: 0 0 0 0 rgba(13,110,253,0); } }
.step-label { font-size: 11px; font-weight: 600; color: #495057; }
.step-person { font-size: 10px; color: #6c757d; margin-top: 2px; }
.step-time { font-size: 9px; color: #adb5bd; margin-top: 1px; }
.detail-info-card { background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border: 1px solid #e9ecef; border-radius: 10px; padding: 16px; }
.detail-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 2px; }
.detail-value { font-size: 13px; font-weight: 600; color: #212529; }
.next-step-banner { border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; gap: 10px; }
.next-step-banner i { font-size: 18px; }
</style>

<script>

function calcTotal() {
    var qty = parseFloat(document.getElementById('qty').value) || 0;
    var price = parseFloat(document.getElementById('unitPrice').value) || 0;
    document.getElementById('totalDisplay').textContent = (qty * price).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function openApprovalModal(id, status, step) {
    var form = document.getElementById('approvalForm');
    form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/' + id;
    document.getElementById('approvalStatus').value = status;
    
    var title = status === 'approved' ? 'Approve Request #' + id : 'Reject Request #' + id;
    document.getElementById('approvalModalTitle').textContent = title;
    
    var btn = document.getElementById('approvalSubmitBtn');
    btn.className = 'btn btn-sm btn-' + (status === 'approved' ? 'success' : 'danger');
    btn.textContent = status === 'approved' ? 'Approve' : 'Reject';
    
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function openPaymentModal(id) {
    var form = document.getElementById('paymentForm');
    form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/purchased/' + id;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function getStepClass(status) {
    if (status === 'approved') return 'approved';
    if (status === 'rejected') return 'rejected';
    return 'pending';
}

function getStepIcon(status) {
    if (status === 'approved') return '<i class="fa-solid fa-check"></i>';
    if (status === 'rejected') return '<i class="fa-solid fa-xmark"></i>';
    return '<i class="fa-solid fa-clock"></i>';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ', ' + d.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
}

function getNextStep(data) {
    if (data.overall_status === 'rejected') return { text: 'Request was rejected', color: 'danger', icon: 'fa-circle-xmark' };
    if (data.overall_status === 'purchased') return { text: 'Completed — Payment processed & assets registered', color: 'success', icon: 'fa-circle-check' };
    if (data.purchase_head_status === 'pending') return { text: 'Waiting for Purchase Head approval', color: 'primary', icon: 'fa-hourglass-half' };
    if (data.qc_audit_status === 'pending') return { text: 'Waiting for QC & Internal Audit review', color: 'info', icon: 'fa-hourglass-half' };
    if (data.mgmt_status === 'pending') return { text: 'Waiting for Management approval', color: 'warning', icon: 'fa-hourglass-half' };
    if (data.payment_status === 'unpaid') return { text: 'Approved — Waiting for Accounts to process payment', color: 'success', icon: 'fa-money-bill-wave' };
    return { text: 'Processing...', color: 'secondary', icon: 'fa-spinner' };
}

function openDetailModal(btn) {
    var data = JSON.parse(btn.getAttribute('data-info'));
    document.getElementById('detailTitle').textContent = 'Purchase Request #' + data.id;
    document.getElementById('detailDate').textContent = 'Submitted on ' + formatDate(data.created_at);

    var nextStep = getNextStep(data);
    
    // Determine waiting step for animation
    var phClass = getStepClass(data.purchase_head_status);
    var qcClass = getStepClass(data.qc_audit_status);
    var mgClass = getStepClass(data.mgmt_status);
    var pyClass = data.payment_status === 'paid' ? 'approved' : 'pending';
    
    // Add "waiting" animation to the current active step
    if (data.overall_status !== 'rejected' && data.overall_status !== 'purchased') {
        if (data.purchase_head_status === 'pending') phClass = 'waiting';
        else if (data.qc_audit_status === 'pending') qcClass = 'waiting';
        else if (data.mgmt_status === 'pending') mgClass = 'waiting';
        else if (data.payment_status === 'unpaid') pyClass = 'waiting';
    }

    var body = document.getElementById('detailModalBody');
    body.innerHTML = `
        <!-- Next Step Banner -->
        <div class="next-step-banner bg-${nextStep.color}-transparent mb-3">
            <i class="fa-solid ${nextStep.icon} text-${nextStep.color}"></i>
            <div>
                <div class="fw-bold fs-12 text-${nextStep.color}">${nextStep.text}</div>
            </div>
        </div>

        <!-- Request Info Card -->
        <div class="detail-info-card mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="detail-label">Project</div>
                    <div class="detail-value"><i class="fa-solid fa-building text-primary me-1 fs-11"></i>${data.project_name || '—'}</div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Requested By</div>
                    <div class="detail-value"><i class="fa-solid fa-user text-secondary me-1 fs-11"></i>${data.requested_by_name || '—'}</div>
                </div>
                <div class="col-md-6 mt-2">
                    <div class="detail-label">Item</div>
                    <div class="detail-value">
                        ${data.item_name || '—'}

                    </div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value">${data.quantity} pcs</div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value text-success">৳ ${parseFloat(data.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-12 mt-2">
                    <div class="detail-label">Justification</div>
                    <div class="bg-white border rounded p-2 fs-12 mt-1">${data.justification || 'No justification provided.'}</div>
                </div>
            </div>
        </div>

        <!-- Approval Pipeline -->
        <div class="detail-label mb-1 px-1">APPROVAL PIPELINE</div>
        <div class="approval-pipeline">
            <div class="approval-step">
                <div class="step-circle approved"><i class="fa-solid fa-paper-plane" style="font-size:14px"></i></div>
                <div class="step-label">Requested</div>
                <div class="step-person">${data.requested_by_name || '—'}</div>
                <div class="step-time">${data.created_at ? formatDate(data.created_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${phClass}">${getStepIcon(data.purchase_head_status)}</div>
                <div class="step-label">Purchase Head</div>
                <div class="step-person">${data.purchase_head_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.purchase_head_at ? formatDate(data.purchase_head_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${qcClass}">${getStepIcon(data.qc_audit_status)}</div>
                <div class="step-label">QC & Audit</div>
                <div class="step-person">${data.qc_audit_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.qc_audit_at ? formatDate(data.qc_audit_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${mgClass}">${getStepIcon(data.mgmt_status)}</div>
                <div class="step-label">Management</div>
                <div class="step-person">${data.mgmt_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.mgmt_at ? formatDate(data.mgmt_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${pyClass}">${data.payment_status === 'paid' ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-bangladeshi-taka-sign" style="font-size:14px"></i>'}</div>
                <div class="step-label">Payment</div>
                <div class="step-person">${data.paid_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.paid_at ? formatDate(data.paid_at) : ''}</div>
            </div>
        </div>

        <!-- Approval Notes (if any) -->
        ${(data.purchase_head_note || data.qc_audit_note || data.mgmt_note) ? `
        <div class="mt-3">
            <div class="detail-label mb-1 px-1">APPROVAL NOTES</div>
            <div class="bg-light rounded p-2">
                ${data.purchase_head_note ? '<div class="fs-11 mb-1"><strong class="text-primary">Purchase Head:</strong> ' + data.purchase_head_note + '</div>' : ''}
                ${data.qc_audit_note ? '<div class="fs-11 mb-1"><strong class="text-info">QC & Audit:</strong> ' + data.qc_audit_note + '</div>' : ''}
                ${data.mgmt_note ? '<div class="fs-11"><strong class="text-warning">Management:</strong> ' + data.mgmt_note + '</div>' : ''}
            </div>
        </div>
        ` : ''}

        <!-- Documents -->
        ${(data.attachment || data.payment_attachment || data.purchase_document) ? `
        <div class="mt-3">
            <div class="detail-label mb-1 px-1">SUBMITTED DOCUMENTS</div>
            <div class="d-flex flex-wrap gap-2">
                ${data.attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.attachment + '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Request Doc</a>' : ''}
                ${data.payment_attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.payment_attachment + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-receipt me-1"></i> Payment Receipt</a>' : ''}
                ${data.purchase_document ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.purchase_document + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-invoice me-1"></i> Purchase Document</a>' : ''}
            </div>
        </div>
        ` : ''}
    `;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function openConfirmPurchaseModal(id) {
    document.getElementById('confirmPurchaseForm').action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/confirm/' + id;
    document.getElementById('confirm_purchase_note').value = '';
    document.getElementById('confirm_purchase_document').value = '';
    new bootstrap.Modal(document.getElementById('confirmPurchaseModal')).show();
}
</script>

<!-- ========================================================================= -->
<!-- MODAL: CONFIRM PURCHASE (Operations) -->
<!-- ========================================================================= -->
<div class="modal fade" id="confirmPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="confirmPurchaseForm" action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header bg-success-transparent">
                    <h6 class="modal-title fw-semibold"><i class="fa-solid fa-box-check me-2"></i>Confirm Purchase Received</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-12 mb-3">Confirm that the purchased items have been received. This will register the assets in the system.</p>
                    <div class="mb-3">
                        <label class="form-label">Upload Document <span class="text-muted fs-11">(Invoice, Receipt, Challan etc.)</span></label>
                        <input type="file" name="purchase_document" id="confirm_purchase_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes <span class="text-muted fs-11">(optional)</span></label>
                        <textarea name="purchase_note" id="confirm_purchase_note" class="form-control" rows="2" placeholder="Any remarks about the received items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm & Register Assets</button>
                </div>
            </div>
        </form>
    </div>
</div>
