<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Soil Tests</h4>

<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add Soil Test
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover ">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Site Engineer</th>
                            <th>Test Date</th>
                            <th>Lab Document</th>
                            <th>Field Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['engineer_name'] ?? 'N/A') ?></td>
                                    <td><?= $row['test_date'] ? date('d M Y', strtotime($row['test_date'])) : '—' ?></td>
                                    <td>
                                        <?php if (!empty($row['lab_test_document_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['lab_test_document_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['field_data'])): ?>
                                            <span title="<?= htmlspecialchars($row['field_data']) ?>">
                                                <?= mb_strimwidth($row['field_data'], 0, 40, '...') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-soil-test">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No soil test records found.</td>
                            </tr>
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
