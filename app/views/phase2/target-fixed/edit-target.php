<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Edit Target';
$t = $target;
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-targets">Site Targets</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-8 col-xl-10">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Target</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-target-update/<?= $t['id'] ?>" method="POST">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $t['project_id'] == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Working Plan <span class="text-danger">*</span></label>
                                <select name="work_plan_id" id="work_plan_id" class="form-select" required>
                                    <option value="">-- Select Working Plan --</option>
                                    <?php foreach ($workPlans as $wp): ?>
                                        <option value="<?= $wp['id'] ?>"
                                            data-design="<?= htmlspecialchars($wp['design_type_name'] ?? '') ?>"
                                            data-desc="<?= htmlspecialchars($wp['work_description']) ?>"
                                            <?= $t['work_plan_id'] == $wp['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($wp['project_name'] ?? '') ?> —
                                            <?= $wp['year'] ?>/<?= str_pad($wp['month'], 2, '0', STR_PAD_LEFT) ?> —
                                            <?= htmlspecialchars($wp['design_type_name'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Title <span class="text-danger">*</span></label>
                            <input type="text" name="target_title" class="form-control" required value="<?= htmlspecialchars($t['target_title']) ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Year</label>
                                <input type="number" name="year" class="form-control" value="<?= $t['year'] ?>" min="2020" max="2035" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Month</label>
                                <select name="month" class="form-select" required>
                                    <?php foreach ($months as $i => $m): ?>
                                        <option value="<?= $i + 1 ?>" <?= $t['month'] == $i+1 ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Week Number</label>
                                <select name="week_number" class="form-select">
                                    <option value="">-- Monthly --</option>
                                    <?php for ($w = 1; $w <= 4; $w++): ?>
                                        <option value="<?= $w ?>" <?= $t['week_number'] == $w ? 'selected' : '' ?>>Week <?= $w ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Target Percentage (%)</label>
                            <input type="number" name="target_percentage" class="form-control" step="0.01" min="0" max="100"
                                   value="<?= $t['target_percentage'] ?>" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i> Update Target
                            </button>
                            <a href="<?= $base_url ?>/con-target/<?= $t['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
