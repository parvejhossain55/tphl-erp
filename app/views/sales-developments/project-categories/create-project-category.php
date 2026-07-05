<?php $show_breadcrumb = true; ?>

<style>
.cat-form-card { max-width: 560px; margin: 0 auto; }
.cat-form-card .form-label { font-size: .85rem; font-weight: 600; color: #374151; }
.cat-form-card .form-control, .cat-form-card .form-select {
    border-radius: 8px; font-size: .88rem; border-color: #d1d5db;
}
.cat-form-card .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
.incentive-hint { font-size: .76rem; color: #6b7280; }
</style>

<!-- Page Header -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $GLOBALS['base_url'] ?>/project-category-lists" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e293b;">Add Project Category</h4>
        <small class="text-muted">Create a new category with incentive percentage</small>
    </div>
</div>

<div class="cat-form-card">
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body p-4">
            <form action="<?= $GLOBALS['base_url'] ?>/store-project-category" method="POST">

                <!-- Category Name -->
                <div class="mb-4">
                    <label class="form-label" for="cat_name">
                        Category Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="cat_name" name="name"
                           class="form-control" placeholder="e.g. Residential, Commercial..."
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <!-- Incentive Percentage -->
                <div class="mb-4">
                    <label class="form-label" for="cat_incentive">
                        Incentive Percentage <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" id="cat_incentive" name="incentive_percentage"
                               class="form-control" placeholder="0.00"
                               min="0" max="100" step="0.01"
                               value="<?= htmlspecialchars($_POST['incentive_percentage'] ?? '0') ?>" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="incentive-hint mt-1">
                        <i class="bi bi-info-circle me-1"></i>
                        This percentage will be auto-applied when this category is selected for a project.
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                    <a href="<?= $GLOBALS['base_url'] ?>/project-category-lists"
                       class="btn btn-outline-secondary btn-sm px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check-circle me-1"></i> Save Category
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
