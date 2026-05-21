<!-- app-content-start -->
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
                                        <th>Project</th>
                                        <th>Request Type</th>
                                        <th>Ref ID</th>
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
                                            } elseif ($req['type'] === 'Cashout') {
                                                $details_url = $GLOBALS['base_url'] . "/view-cashout-request?id=" . $req['id'];
                                            } elseif ($req['type'] === 'Vendor Payment') {
                                                $details_url = $GLOBALS['base_url'] . "/con-vendor-payment-show/" . $req['id'];
                                            } elseif ($req['type'] === 'Contractor Bill') {
                                                $details_url = $GLOBALS['base_url'] . "/con-contractor-bill/" . $req['id'];
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($req['created_at']) ?></td>
                                                <td><?= htmlspecialchars($req['project_name'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-warning text-dark border"><?= htmlspecialchars($req['type']) ?></span></td>
                                                <td class="fw-bold">#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($req['status']) ?></span></td>
                                                <td>
                                                    <a href="<?= $details_url ?>" target="_blank" class="btn btn-sm btn-outline-info me-1">
                                                        <i class="bi bi-eye me-1"></i> Details
                                                    </a>
                                                    <button class="btn btn-sm btn-success approve-btn" 
                                                            data-id="<?= $req['id'] ?>" 
                                                            data-type="<?= $req['type'] ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#approveModal">
                                                        <i class="bi bi-check-circle me-1"></i> Review & Approve
                                                    </button>
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
  <div class="modal-dialog">
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
