<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-budget-extends">Budget Extensions</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-coin me-2"></i>Request Budget Extension
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-budget-extend-store"
                          method="POST"
                          enctype="multipart/form-data">

                        <!-- Project + Budget -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Initial Budget Reference <span class="text-danger">*</span>
                                </label>
                                <select name="initial_budget_id" class="form-select" required>
                                    <option value="">-- Select Budget --</option>
                                    <?php foreach ($budgets as $b): ?>
                                        <option value="<?= $b['id'] ?>">
                                            #<?= $b['id'] ?> — <?= htmlspecialchars($b['project_name']) ?>
                                            (৳<?= number_format($b['total_estimated_cost'], 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Subject / Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="e.g. Additional Piling Work, Extra Foundation Cost"
                                   required>
                        </div>

                        <!-- Amount -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Extension Amount (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" name="amount"
                                       class="form-control" placeholder="0.00" min="0.01" required>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Reason / Justification <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" class="form-control" rows="4" required
                                      placeholder="Explain why the additional budget is needed…"></textarea>
                        </div>

                        <!-- ── Supporting Docs: Attachment + Cloud Link ── -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-paperclip me-1 text-secondary"></i>
                                    Attachment
                                    <span class="text-muted fw-normal small">(optional)</span>
                                </label>
                                <input type="file"
                                       name="attachment_file"
                                       id="attachment_file"
                                       class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xlsx">
                                <div class="form-text text-muted">
                                    Accepted: PDF, JPG, PNG, WEBP, DOC, XLSX
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-link-45deg me-1 text-secondary"></i>
                                    Cloud / Docs Link
                                    <span class="text-muted fw-normal small">(optional)</span>
                                </label>
                                <input type="url"
                                       name="docs_link"
                                       class="form-control"
                                       placeholder="https://drive.google.com/…">
                                <div class="form-text text-muted">
                                    Google Drive, Dropbox, OneDrive, etc.
                                </div>
                            </div>
                        </div>
                        <!-- ── End Supporting Docs ── -->

                        <div class="alert alert-info py-2 small mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            Once submitted, this request will be reviewed by management. Approved extensions
                            automatically increase the project's Revised Total Budget.
                        </div>

                        <div class="text-end">
                            <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-send me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
