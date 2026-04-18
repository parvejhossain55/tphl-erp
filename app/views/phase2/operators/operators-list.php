<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Operators & Contractors</h4>

<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/con-operator-create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add Operator
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Trade</th>
                            <th>Phone</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($operators)): ?>
                            <?php foreach ($operators as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($row['name']) ?>
                                        <?php if (!empty($row['nid_number'])): ?>
                                            <br><small class="text-muted">NID: <?= htmlspecialchars($row['nid_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['role']) ?></td>
                                    <td><?= htmlspecialchars($row['trade'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
                                    <td><?= $row['daily_rate'] ? number_format($row['daily_rate'], 2) : '—' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-operator-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-action="con-operator-delete">
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
