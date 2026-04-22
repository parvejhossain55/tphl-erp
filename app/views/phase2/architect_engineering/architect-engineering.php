<?php
$addPage = true;
$show_breadcrumb = false;
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
       
    </div>
    <div class="d-flex gap-2">
          <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add New Architecture
                </a>
    </div>
</div>

<!-- <h4 class="mb-2">Architect & Engineering</h4> -->



<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Designer</th>
                            <th>Design Link</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($row['full_architecture_design_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['full_architecture_design_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-architect-engineering">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
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

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
