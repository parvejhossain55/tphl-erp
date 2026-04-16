<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item">Construction</li>
                    <li class="breadcrumb-item active">Warehouses</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/con-warehouse-create" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Warehouse
            </a>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card__wrapper shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Warehouse Name</th>
                                    <th>Project</th>
                                    <th>Location</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($warehouses)): ?>
                                    <?php foreach ($warehouses as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div></td>
                                            <td><?= htmlspecialchars($row['project_name'] ?? 'General') ?></td>
                                            <td><?= htmlspecialchars($row['location'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($row['created_by_name']) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/con-warehouse-edit/<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No warehouses found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'app/views/layouts/footer.php'; ?>
