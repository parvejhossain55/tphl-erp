<div class="container-fluid pt-4">
    <div class="row">
        <!-- New Request Form -->
        <div class="col-xl-4 col-lg-5">
            <div class="card__wrapper shadow-sm border-0 mb-4 sticky-top" style="top: 80px; z-index: 10;">
                <div class="card__header bg-warning-light">
                    <h5 class="card__title mb-0 text-dark">Request Budget Extension</h5>
                </div>
                <div class="card__body p-3">
                    <form action="<?= $base_url ?>/con-budget-extend-store" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select select2" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Subject / Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Additional Piling Work" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Extension Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">TK</span>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reason / Justification <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="4" class="form-control" placeholder="Explain why the budget increase is needed..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="fa-solid fa-paper-plane me-2"></i> Submit Extension Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Request History -->
        <div class="col-xl-8 col-lg-7">
            <div class="card__wrapper shadow-sm border-0">
                <div class="card__header d-flex justify-content-between">
                    <h5 class="card__title mb-0">Budget Extension History</h5>
                    <a href="<?= $base_url ?>/con-working-plans" class="btn btn-secondary btn-sm">Working Plans</a>
                </div>
                <div class="card__body p-0">
                    <div class="table__wrapper">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Project / Subject</th>
                                    <th class="text-end">Amount</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($extends)): ?>
                                    <?php foreach ($extends as $index => $item): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><small class="text-muted"><?= date('d M, Y', strtotime($item['created_at'])) ?></small></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($item['project_name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($item['title']) ?></div>
                                            </td>
                                            <td class="text-end fw-bold text-danger"><?= number_format($item['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($item['requested_by_name']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?= $item['status'] == 'Approved' ? 'success' : ($item['status'] == 'Rejected' ? 'danger' : 'secondary') ?>">
                                                    <?= $item['status'] ?>
                                                </span>
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
</div>

<style>
.bg-warning-light {
    background-color: #fff8eb;
    border-bottom: 2px solid #ffecb5;
}
</style>
