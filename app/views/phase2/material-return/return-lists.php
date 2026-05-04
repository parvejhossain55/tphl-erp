<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Construction</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($page_title) ?></li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card__wrapper shadow-sm">
        <div class="card-header justify-content-between">
            <div class="card-title">
                Pending & Processed Returns
            </div>
        </div>
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table" id="returnsTable">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>GRN No.</th>
                                        <th>Project</th>
                                        <th>Vendor</th>
                                        <th>Date</th>
                                        <th>Reason (from QC)</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($returns as $r): ?>
                                        <tr>
                                            <td>RET-<?= $r['id'] ?></td>
                                            <td>GRN-<?= $r['receiving_id'] ?></td>
                                            <td><?= htmlspecialchars($r['project_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($r['vendor_name'] ?? '') ?></td>
                                            <td><?= date('d M Y', strtotime($r['return_date'])) ?></td>
                                            <td>
                                                <span class="text-danger small"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($r['return_reason'] ?? '') ?></span>
                                            </td>
                                            <td>
                                                <?php if ($r['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($r['status'] == 'sent'): ?>
                                                    <span class="badge bg-info">Sent</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Acknowledged</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($r['status'] == 'pending'): ?>
                                                    <form method="POST" action="<?= $base_url ?>/con-material-returns/update/<?= $r['id'] ?>" class="d-inline">
                                                        <input type="hidden" name="status" value="sent">
                                                        <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Mark as Sent back to vendor?')"><i class="bi bi-truck me-1"></i> Mark as Sent</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#returnsTable')) {
            $('#returnsTable').DataTable();
        }
    });
</script>
