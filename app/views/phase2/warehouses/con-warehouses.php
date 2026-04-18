<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Warehouse Management</h4>

<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/con-warehouse-create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add Warehouse
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
                            <th>Warehouse Name</th>
                            <th>Project</th>
                            <th>Location</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($warehouses)): ?>
                            <?php foreach ($warehouses as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['project_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['location'] ?? '—') ?></td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-warehouse-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
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
