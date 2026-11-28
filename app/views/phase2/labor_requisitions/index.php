<?php
$base      = $GLOBALS['base_url'];
$isSE      = in_array($userRole, [12, 1]);
$isPM      = in_array($userRole, [15, 22, 1, 3]);
$isQC      = in_array($userRole, [13, 24, 25, 1, 3]);
$isMgmt    = in_array($userRole, [3, 1]);
$isAccts   = in_array($userRole, [6, 30, 1, 3]);
$filterStatus = $_GET['status'] ?? '';
$isPayView = $isAccts && $filterStatus === 'management_approved';

$statusBadge = [
    'pending'              => 'bg-warning text-dark',
    'pm_approved'          => 'bg-info text-dark',
    'work_completed'       => 'bg-primary',
    'payment_requested'    => 'bg-purple text-white',
    'qc_approved'          => 'bg-teal text-white',
    'pm_payment_approved'  => 'bg-indigo text-white',
    'management_approved'  => 'bg-success',
    'partially_paid'       => 'bg-warning text-dark',
    'paid'                 => 'bg-success',
    'labor_disbursed'      => 'bg-success',
    'rejected'             => 'bg-danger',
];
$statusLabel = [
    'pending'              => '⏳ Pending PM',
    'pm_approved'          => '✅ Dept. Head Approved',
    'work_completed'       => '🔨 Work Done',
    'payment_requested'    => '💸 Payment Req.',
    'qc_approved'          => '🔍 QC Approved',
    'pm_payment_approved'  => '✔️ PM Verified',
    'management_approved'  => '👔 Mgmt Approved',
    'partially_paid'       => '⏳ Partially Paid',
    'paid'                 => '💰 Paid',
    'labor_disbursed'      => '✅ Labour Disbursed',
    'rejected'             => '❌ Rejected',
];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Labor Requisitions</h1>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/con-dashboard">Construction</a></li>
                <li class="breadcrumb-item active">Labor Requisitions</li>
            </ol></nav>
        </div>
        <?php if ($isSE || $isPM): ?>
        <a href="<?php echo $base; ?>/con-labor-requisition-create" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Requisition
        </a>
        <?php endif; ?>
    </div>

    <!-- Executive Management Approval Schedule Notice -->
    <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card custom-card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?php echo $base; ?>/con-labor-requisitions" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-12 mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (($_GET['project_id'] ?? '') == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-12 mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <?php foreach ($statusLabel as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo (($_GET['status'] ?? '') === $k) ? 'selected' : ''; ?>>
                                <?php echo strip_tags($v); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo $base; ?>/con-labor-requisitions" class="btn btn-light btn-sm border w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title fs-14">
                <i class="fa-solid fa-hard-hat me-2 text-warning"></i>
                <?php if ($isPayView): ?>
                    <span class="text-success">Labor Payment Approvals</span>
                <?php elseif ($filterStatus === 'paid'): ?>
                    <span class="text-primary">Paid Labor Requisitions</span>
                <?php else: ?>
                    All Labor Requisitions
                <?php endif; ?>
                <span class="badge bg-primary ms-2"><?php echo count($requisitions); ?></span>
            </div>
            <?php if ($isPayView && !empty($requisitions)): ?>
            <span class="badge bg-success-transparent text-success fs-12">
                <i class="fa-solid fa-circle-check me-1"></i>
                Management Approved — Ready to Pay
            </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap mb-0 fs-13">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Requisition No</th>
                            <th>Project</th>
                            <th>Labor</th>
                            <th>Rate/Day</th>
                            <th>Days</th>
                            <th class="text-end">Total Amount</th>
                            <th>Purpose</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($requisitions)): ?>
                        <tr><td colspan="12" class="text-center text-muted py-5">
                            <i class="fa-solid fa-inbox fs-30 d-block mb-2 text-muted opacity-50"></i>
                            No labor requisitions found.
                        </td></tr>
                    <?php else: ?>
                        <?php
                        $lrEditMap = [];
                        if (!empty($requisitions) && function_exists('db')) {
                            $lrDb = db();
                            if ($lrDb) {
                                require_once __DIR__ . '/../../../services/RequisitionRevisionService.php';
                                $lrEditMap = (new RequisitionRevisionService($lrDb))->getLastEditMap('con_labor_requisitions');
                            }
                        }
                        ?>
                        <?php foreach ($requisitions as $i => $lr): ?>
                        <tr>
                            <td class="text-muted fs-12"><?php echo $i + 1; ?></td>
                            <td>
                                <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="fw-semibold text-primary">
                                    <?php echo htmlspecialchars($lr['requisition_no']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($lr['project_name'] ?? '---'); ?></td>
                            <td>
                                <span class="fw-semibold"><?php echo $lr['labor_count']; ?></span>
                                <small class="text-muted ms-1"><?php echo htmlspecialchars($lr['labor_type']); ?></small>
                            </td>
                            <td>&#2547; <?php echo number_format($lr['per_labor_rate'], 0); ?></td>
                            <td><?php echo $lr['working_days']; ?> days</td>
                            <td class="text-end fw-bold text-success">&#2547; <?php echo number_format($lr['total_amount'], 2); ?></td>
                            <td style="max-width:180px;white-space:normal;font-size:12px"><?php echo htmlspecialchars(mb_substr($lr['purpose'], 0, 60)); ?><?php echo mb_strlen($lr['purpose']) > 60 ? '…' : ''; ?></td>
                            <td>
                                <?php echo htmlspecialchars($lr['requested_by_name'] ?? '---'); ?>
                                <?php $lrEdit = $lrEditMap[(int)($lr['id'] ?? 0)] ?? null; ?>
                                <?php if (!empty($lrEdit)): ?>
                                    <div class="mt-1" style="font-size:10px; color:#b45309; line-height:1.25;" title="<?= htmlspecialchars($lrEdit['change_summary'] ?? 'Edited') ?>">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>Edited: <strong><?= htmlspecialchars($lrEdit['edited_by_name']) ?></strong>
                                        <?php if (!empty($lrEdit['created_at'])): ?>
                                            <div class="text-muted" style="font-size:9.5px;"><?= date('d M Y, h:i A', strtotime($lrEdit['created_at'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($lr['requisition_date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $statusBadge[$lr['status']] ?? 'bg-secondary'; ?> fs-11">
                                    <?php echo $statusLabel[$lr['status']] ?? $lr['status']; ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1 flex-nowrap">
                                <?php if ($isAccts && $lr['status'] === 'management_approved'): ?>
                                    <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i> Pay Now
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="btn btn-icon btn-sm btn-light border" title="View Requisition">
                                        <i class="fa-solid fa-eye fs-11"></i>
                                    </a>
                                    <?php if ($isPM && ($lr['status'] === 'pending' || $lr['status'] === 'qc_approved')): ?>
                                        <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="btn btn-sm btn-outline-success py-1 px-2 fs-11" title="Review & Approve">
                                            <i class="fa-solid fa-check me-1"></i> <?= ($lr['status'] === 'pending') ? 'Approve' : 'Verify' ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php 
                                    $isOwner = ((int)($lr['requested_by'] ?? 0) === (int)($_SESSION['user']['id'] ?? 0));
                                    $isAdmin = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3], true);
                                    $canEditLr = (($lr['status'] ?? '') === 'pending') && empty($lr['pm_approved_by']) && empty($lr['qc_approved_by']) && empty($lr['management_approved_by']) && ($isOwner || $isAdmin);
                                    if ($canEditLr):
                                        $lrPayload = [
                                            'id'             => (int)$lr['id'],
                                            'requisition_no' => $lr['requisition_no'] ?? ('#' . $lr['id']),
                                            'labor_type'     => $lr['labor_type'] ?? 'General',
                                            'labor_count'    => (int)($lr['labor_count'] ?? 1),
                                            'per_labor_rate' => $lr['per_labor_rate'] ?? 0,
                                            'working_days'   => $lr['working_days'] ?? 1,
                                            'work_date_from' => $lr['work_date_from'] ?? '',
                                            'work_date_to'   => $lr['work_date_to'] ?? '',
                                            'purpose'        => $lr['purpose'] ?? '',
                                            'remarks'        => $lr['remarks'] ?? '',
                                        ];
                                    ?>
                                        <button type="button"
                                                class="btn btn-icon btn-sm btn-warning text-dark border btn-edit-labor-req"
                                                title="Edit Requisition"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editLaborReqIndexModal"
                                                data-item="<?= htmlspecialchars(json_encode($lrPayload), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-pen-to-square fs-11"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php
                                    if (($lr['status'] === 'pending' || $lr['status'] === 'rejected') && ($isOwner || $isAdmin)): 
                                    ?>
                                        <form action="<?php echo $base; ?>/con-labor-requisition-delete/<?php echo $lr['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this requisition?')">
                                            <button type="submit" class="btn btn-icon btn-sm btn-danger-light border" title="Delete">
                                                <i class="fa-solid fa-trash fs-11"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                </div>
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

<!-- Edit Labor Requisition Modal (Before 1st Approval — List View) -->
<div class="modal custom-modal fade" id="editLaborReqIndexModal" tabindex="-1" aria-labelledby="editLaborReqIndexModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editLaborReqIndexModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Labor Requisition <span id="edit_lr_title_no"></span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="" id="editLaborReqIndexForm">
                    <input type="hidden" name="redirect_to" value="index">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Labor Type</label>
                            <input type="text" name="labor_type" id="edit_lr_type" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Labor Count <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="labor_count" id="edit_lr_count" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Per Labor Rate (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="per_labor_rate" id="edit_lr_rate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Working Days <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" min="0.5" name="working_days" id="edit_lr_days" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Work Date From</label>
                            <input type="date" name="work_date_from" id="edit_lr_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Work Date To</label>
                            <input type="date" name="work_date_to" id="edit_lr_to" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Purpose / Work Description <span class="text-danger">*</span></label>
                        <textarea name="purpose" id="edit_lr_purpose" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Remarks</label>
                        <textarea name="remarks" id="edit_lr_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initLrIndexEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initLrIndexEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editLaborReqIndexModal').length && $('#editLaborReqIndexModal').parent().prop('tagName') !== 'BODY') {
                $('#editLaborReqIndexModal').appendTo('body');
            }
            function populateLr(btn) {
                var raw = btn.attr('data-item');
                if (!raw) return;
                try {
                    var d = (typeof raw === 'object') ? raw : JSON.parse(raw);
                    $('#editLaborReqIndexForm').attr('action', '<?= $base ?>/con-labor-requisition-update/' + (d.id || ''));
                    $('#edit_lr_title_no').text('(' + (d.requisition_no || ('#' + d.id)) + ')');
                    $('#edit_lr_type').val(d.labor_type || 'General');
                    $('#edit_lr_count').val(d.labor_count || 1);
                    $('#edit_lr_rate').val(d.per_labor_rate || 0);
                    $('#edit_lr_days').val(d.working_days || 1);
                    $('#edit_lr_from').val(d.work_date_from || '');
                    $('#edit_lr_to').val(d.work_date_to || '');
                    $('#edit_lr_purpose').val(d.purpose || '');
                    $('#edit_lr_remarks').val(d.remarks || '');
                } catch (e) {}
            }
            $('#editLaborReqIndexModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateLr($(e.relatedTarget));
            });
            $(document).on('click', '.btn-edit-labor-req', function() {
                populateLr($(this));
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLrIndexEditModal);
    } else {
        initLrIndexEditModal();
    }
})();
</script>
