<?php $show_breadcrumb = true; ?>

<style>
.cat-table th { white-space: nowrap; font-size: .84rem; font-weight: 700; background: #f8f9fa; }
.cat-table td { vertical-align: middle; font-size: .84rem; }
.incentive-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .28rem .65rem; border-radius: 999px;
    background: linear-gradient(135deg,#d1fae5,#a7f3d0);
    color: #065f46; border: 1px solid #6ee7b7; font-size: .76rem; font-weight: 700;
}
</style>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e293b;">
            <i class="bi bi-tags-fill me-2 text-primary"></i>Project Categories
        </h4>
        <small class="text-muted">Manage project categories and incentive percentages</small>
    </div>
    <a href="<?= $GLOBALS['base_url'] ?>/create-project-category" class="btn btn-primary btn-sm px-4">
        <i class="bi bi-plus-circle me-1"></i> Add Category
    </a>
</div>


<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table cat-table mb-0" id="categoryTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:50px;">#</th>
                        <th>Category Name</th>
                        <th>Main Category</th>
                        <th>Location</th>
                        <th>Incentive %</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="text-center" style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-2 d-block mb-2"></i>
                            No categories yet. <a href="<?= $GLOBALS['base_url'] ?>/create-project-category">Add one</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td class="ps-4 text-muted"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($cat['main_category'] ?? '—') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($cat['location_name'] ?? '—') ?></td>
                        <td>
                            <span class="incentive-pill">
                                <i class="bi bi-percent" style="font-size:.7rem;"></i>
                                <?= number_format((float)$cat['incentive_percentage'], 2) ?>%
                            </span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($cat['created_by_name'] ?? '—') ?></td>
                        <td class="text-muted" style="font-size:.8rem;">
                            <?= date('M d, Y', strtotime($cat['created_at'])) ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= $GLOBALS['base_url'] ?>/edit-project-category/<?= $cat['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger"
                                    title="Delete"
                                    onclick="confirmDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash-fill"></i>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-1"></i> Delete Category</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="text-muted mb-0" style="font-size:.88rem;">
            Are you sure you want to delete <strong id="catDeleteName"></strong>?<br>
            <small class="text-warning">Projects using this category will have their category cleared.</small>
        </p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <form id="deleteCatForm" method="POST" action="<?= $GLOBALS['base_url'] ?>/delete-project-category">
            <input type="hidden" name="id" id="deleteCatId">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-danger ms-1">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCatId').value   = id;
    document.getElementById('catDeleteName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteCatModal')).show();
}
</script>
