<?php
$page_title = "Expense Adjustments";
$page = 'Accounts';
$show_breadcrumb = true;
?>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">
                    <i class="fa-light fa-money-check-pen me-2"></i>Expense Adjustments
                </h5>
                <div class="d-flex gap-2">
                    <?php if ($isAccounts): ?>
                        <div class="btn-group">
                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=my" class="btn <?= $viewType === 'my' ? 'btn-primary' : 'btn-outline-primary' ?>">My Adjustments</a>
                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=all" class="btn <?= $viewType === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All Adjustments</a>
                        </div>
                    <?php endif; ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create" class="btn btn-success">
                        <i class="fa-light fa-plus-circle me-1"></i> New Request
                    </a>
                </div>
            </div>
            
            <div class="card__body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="adjustmentTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Requester</th>
                                <th>Parent Request Info</th>
                                <th>Type</th>
                                <th>Requested Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adjustments as $row): ?>
                                <tr>
                                    <td>REQ-<?= $row['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['requester_name']) ?></strong>
                                    </td>
                                    <td>
                                        <div><strong>Title:</strong> <?= htmlspecialchars($row['original_purpose']) ?></div>
                                        <div><strong>Original Amt:</strong> TK.<?= number_format($row['original_amount'], 2) ?></div>
                                        <?php if (!empty($row['project_name'])): ?>
                                            <div><strong>Project:</strong> <span class="text-info"><?= htmlspecialchars($row['project_name']) ?></span></div>
                                        <?php elseif (!empty($row['office'])): ?>
                                            <div><strong>Office:</strong> <span class="text-primary"><?= htmlspecialchars($row['office']) ?></span></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['adjustment_type'] == 'return'): ?>
                                            <span class="badge bg-info">Return Money (Cash-In)</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Extra Money (Cash-Out)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="<?= $row['adjustment_type'] == 'return' ? 'text-success' : 'text-danger' ?>">
                                            TK.<?= number_format($row['amount'], 2) ?>
                                        </strong>
                                    </td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            $badge = 'bg-secondary';
                                            if ($row['status'] == 'pending') $badge = 'bg-warning text-dark';
                                            if ($row['status'] == 'approved') $badge = 'bg-primary';
                                            if ($row['status'] == 'rejected') $badge = 'bg-danger';
                                            if ($row['status'] == 'partially_paid') $badge = 'bg-info';
                                            if ($row['status'] == 'fully_paid') $badge = 'bg-success';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= strtoupper(str_replace('_', ' ', $row['status'])) ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/show?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-light fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        $('#adjustmentTable').DataTable();
    }
});
</script>
