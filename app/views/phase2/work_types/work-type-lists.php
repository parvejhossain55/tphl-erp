<?php
$page_title = "Work Types";
$page = 'Work Types';
$show_breadcrumb = true;
?>

<div class="breadcrumb__btn my-3">
    <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-create" class="btn btn-primary">
        <i class="fa-light fa-plus me-1"></i> Add Work Type
    </a>
</div>

<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper shadow-sm">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Work Type Name</th>
                            <th>Design Type</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['work_type_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($row['design_type_name'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this work type?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="table__icon delete removeBtnCust" style="border:none;background:none">
                                                    <i class="fa-regular fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No work types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
