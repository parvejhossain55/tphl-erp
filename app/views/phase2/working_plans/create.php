<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-md-8 col-lg-6 mx-auto">
            <div class="card__wrapper shadow-sm border-0">
                <div class="card__header d-flex justify-content-between">
                    <h5 class="card__title mb-0">Project Working Plan Entry</h5>
                    <a href="<?= $base_url ?>/con-working-plans" class="btn btn-secondary btn-sm">List</a>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-working-plan-store" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select select2" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-select" required>
                                    <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                    <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $i, 10)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Material <span class="text-danger">*</span></label>
                            <select name="material_id" class="form-select select2-material" required>
                                <option value="">-- Select Material --</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>" data-unit="<?= $material['unit'] ?>">
                                        <?= htmlspecialchars($material['name']) ?> (<?= $material['unit'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Planned Qty <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.001" name="planned_quantity" class="form-control" required>
                                    <span class="input-group-text unit-label">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Cost</label>
                                <input type="number" step="0.01" name="estimated_cost" class="form-control">
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded mb-4">
                            <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i> Note: One material can have only one plan entry per project per month.</small>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-save me-1"></i> Save Plan Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2-material').on('change', function() {
        const unit = $(this).find(':selected').data('unit') || '-';
        $('.unit-label').text(unit);
    });
});
</script>
