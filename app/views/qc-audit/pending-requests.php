<!-- app-content-start -->
<style>
@media (max-width: 767.98px) {
    /* DataTables controls */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        text-align: left !important;
        margin-bottom: 8px;
    }
    .dataTables_wrapper .dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
    }
    .dataTables_wrapper .dataTables_info {
        text-align: center;
        font-size: 11px;
        padding-top: 8px;
    }
    .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        margin-top: 8px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
    /* Stack action buttons on mobile */
    #pendingTable td .btn {
        display: block;
        width: 100%;
        margin-bottom: 4px;
        margin-right: 0 !important;
    }
    /* Page title */
    .page-header-breadcrumb h1 {
        font-size: 16px !important;
    }
    /* Modal */
    .modal-dialog {
        margin: 10px;
    }
}
</style>
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/qc-audit-dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">QC Approvals</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header border-bottom">
                        <div class="card-title">Actionable Pending Requests</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="pendingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Submission Date</th>
                                        <th>Requested By</th>
                                        <th>Project</th>
                                        <th>Request Type</th>
                                        <th>Ref ID</th>
                                        <th>Amount</th>
                                        <th>Current Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($requests)): ?>
                                        <?php foreach ($requests as $req): ?>
                                            <?php
                                            $details_url = '#';
                                            if ($req['type'] === 'Material Requisition') {
                                                $details_url = $GLOBALS['base_url'] . "/con-purchase-request/" . $req['id'];
                                            } elseif ($req['type'] === 'Cashout Request') {
                                                $details_url = $GLOBALS['base_url'] . "/view-cashout-request?id=" . $req['id'];
                                            } elseif ($req['type'] === 'Vendor Payment') {
                                                $details_url = $GLOBALS['base_url'] . "/con-vendor-payment-show/" . $req['id'];
                                            } elseif ($req['type'] === 'Contractor Bill') {
                                                $details_url = $GLOBALS['base_url'] . "/con-contractor-bill/" . $req['id'];
                                            } elseif ($req['type'] === 'Purchase Order') {
                                                $details_url = $GLOBALS['base_url'] . "/con-purchase-order/" . $req['id'];
                                            } elseif ($req['type'] === 'Material Receiving (GRN)') {
                                                $details_url = $GLOBALS['base_url'] . "/con-material-receiving-qc/" . $req['id'];
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($req['created_at']) ?></td>
                                                <td><?= htmlspecialchars($req['requester_name'] ?? 'System / N/A') ?></td>
                                                <td><?= htmlspecialchars($req['project_name'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-warning text-dark border"><?= htmlspecialchars($req['type']) ?></span></td>
                                                <td class="fw-bold">#<?= htmlspecialchars($req['id']) ?></td>
                                                <td class="fw-semibold"><?= ($req['type'] === 'Material Receiving (GRN)') ? '<span class="text-muted small">GRN Items</span>' : '৳' . number_format((float)($req['amount'] ?? 0), 2) ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($req['status']) ?></span>
                                                    <?php if (!empty($req['pending_stage_label'])): ?>
                                                        <div class="mt-1"><span class="badge <?= !empty($req['can_qc_approve']) ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning' ?> fs-10"><?= htmlspecialchars($req['pending_stage_label']) ?></span></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($req['type'] === 'Material Receiving (GRN)'): ?>
                                                        <a href="<?= $details_url ?>" class="btn btn-sm btn-success">
                                                            <i class="bi bi-shield-check me-1"></i> QC Inspection &amp; Approve
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= $details_url ?>" target="_blank" class="btn btn-sm btn-outline-info me-1">
                                                            <i class="bi bi-eye me-1"></i> Details
                                                        </a>
                                                        <?php $canQcAct = !isset($req['can_qc_approve']) || (int)$req['can_qc_approve'] === 1 || in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3], true); ?>
                                                        <?php if ($canQcAct): ?>
                                                            <button class="btn btn-sm btn-success approve-btn" 
                                                                    data-id="<?= $req['id'] ?>" 
                                                                    data-type="<?= $req['type'] ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#approveModal">
                                                                <i class="bi bi-check-circle me-1"></i> Review & Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-secondary" disabled title="Waiting for <?= htmlspecialchars($req['pending_stage_label'] ?? 'prior approval') ?>">
                                                                <i class="bi bi-lock me-1"></i> <?= htmlspecialchars($req['pending_stage_label'] ?? 'Awaiting Prior Step') ?>
                                                            </button>
                                                        <?php endif; ?>
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
        </div>

<!-- Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= $GLOBALS['base_url'] ?>/qc-approve-request" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="approveModalLabel"><i class="bi bi-shield-check text-success me-2"></i> Confirm QC Approval</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="modal_req_id">
            <input type="hidden" name="type" id="modal_req_type">
            <div class="alert alert-primary mb-3">
                <i class="bi bi-info-circle me-2"></i> You are about to approve <strong id="modal_type_text"></strong>. 
                This action will forward it to final Management approval.
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Internal Audit Note / Observation (Optional)</label>
                <textarea class="form-control" name="qc_note" rows="3" placeholder="Add any discrepancies or clearance notes..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success"><i class="bi bi-check2-all me-1"></i> Confirm Approval</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php ob_start(); ?>
$(document).ready(function() {
    $('#pendingTable').DataTable({
        responsive: true,
        order: [[0, 'desc']]
    });
    
    $('.approve-btn').on('click', function() {
        let id = $(this).data('id');
        let type = $(this).data('type');
        $('#modal_req_id').val(id);
        $('#modal_req_type').val(type);
        $('#modal_type_text').text(type + ' #' + id);
    });
});
<?php $page_script = ob_get_clean(); ?>
