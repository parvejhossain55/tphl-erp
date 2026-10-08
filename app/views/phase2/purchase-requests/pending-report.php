
<style>
    .summary-card {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
        text-align: center;
        background: #fff;
    }
    .summary-card h3 { margin: 0; font-size: 24px; font-weight: bold; }
    .summary-card p { margin: 5px 0 0; color: #666; font-size: 14px; }
    .bg-primary-light { border-left: 5px solid #0d6efd; }
    .bg-warning-light { border-left: 5px solid #ffc107; }
    .bg-info-light { border-left: 5px solid #0dcaf0; }
    .bg-danger-light { border-left: 5px solid #dc3545; }
</style>

<div class="container-fluid px-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h2 class="mb-0">PR Pending Report (Last 1 Month)</h2>
        <a href="<?= $GLOBALS['base_url'] ?>/con-purchase-pending-report?export=excel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </a>
    </div>

    <!-- Cumulative Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="summary-card bg-primary-light">
                <h3 class="text-primary"><?= $cumulative['total_raised'] ?></h3>
                <p>Total PR Raised (Last 30 Days)</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card bg-warning-light">
                <h3 class="text-warning"><?= $cumulative['pending_approval'] ?></h3>
                <p>Pending at Approval</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card bg-info-light">
                <h3 class="text-info"><?= $cumulative['pending_purchase'] ?></h3>
                <p>Pending at Purchase Dept.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card bg-danger-light">
                <h3 class="text-danger"><?= $cumulative['pending_delivery'] ?></h3>
                <p>Pending at Delivery (PO Issued)</p>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="pendingPrTable">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Project</th>
                            <th>PR No.</th>
                            <th>PR Date</th>
                            <th>Required Date</th>
                            <th>Category</th>
                            <th>Requested By</th>
                            <th>Lead Time</th>
                            <th>Items Overview</th>
                            <th>Current Status</th>
                            <th>Pending With</th>
                            <th>Aging (Days)</th>
                            <th>Delay Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingPRs)): ?>
                            <tr><td colspan="12" class="text-center">No pending PR found for the last 1 month.</td></tr>
                        <?php else: ?>
                            <?php $sl = 1; foreach ($pendingPRs as $pr): 
                                $delayClass = $pr['delay_status'] === 'Delayed' ? 'text-danger fw-bold' : 'text-success';
                                $reqDate = $pr['required_date'] ? date('d-M-Y', strtotime($pr['required_date'])) : 'N/A';
                                $itemNames = $pr['item_names'] ?? '';
                                $items = htmlspecialchars(strlen($itemNames) > 50 ? substr($itemNames, 0, 50) . '...' : $itemNames);
                            ?>
                            <tr>
                                <td><?= $sl++ ?></td>
                                <td><strong><?= htmlspecialchars($pr['project_name']) ?></strong></td>
                                <td>
                                    <a href="<?= $GLOBALS['base_url'] ?>/con-purchase-request/<?= $pr['pr_id'] ?>" target="_blank">
                                        PR-<?= $pr['pr_id'] ?>
                                    </a>
                                </td>
                                <td><?= date('d-M-Y', strtotime($pr['created_at'])) ?></td>
                                <td><?= $reqDate ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($pr['category_name'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($pr['requested_by'] ?? 'N/A') ?></td>
                                <td class="text-center"><?= $pr['total_lead_days'] ?> Days</td>
                                <td>
                                    <small class="text-muted d-block text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($itemNames) ?>">
                                        <?= $items ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-info text-dark"><?= $pr['detailed_status'] ?></span></td>
                                <td><?= $pr['pending_with'] ?></td>
                                <td class="text-center"><strong><?= $pr['aging_days'] ?></strong></td>
                                <td class="<?= $delayClass ?>"><?= $pr['delay_status'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    $('#pendingPrTable').DataTable({
        pageLength: 20,
        order: [[1, 'asc']], // Order by Project Name by default
        drawCallback: function (settings) {
            var api = this.api();
            var rows = api.rows({page:'current'}).nodes();
            var last = null;
 
            api.column(1, {page:'current'}).data().each(function (group, i) {
                if (last !== group) {
                    $(rows).eq(i).before(
                        '<tr class="group"><td colspan="13" style="background-color: #f8f9fa; font-weight: bold; color: #0d6efd;">' + group + '</td></tr>'
                    );
                    last = group;
                }
            });
        }
    });
});
</script>
<?php $page_script = ob_get_clean(); ?>
