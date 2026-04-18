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
                    <li class="breadcrumb-item active">Materials</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/con-material-create" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Material
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
                                    <th>Material Name</th>
                                    <th>Unit</th>
                                    <th>Min Stock Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($materials)): ?>
                                    <?php foreach ($materials as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div></td>
                                            <td><?= htmlspecialchars($row['unit']) ?></td>
                                            <td><?= htmlspecialchars($row['min_stock_level']) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/con-material-edit/<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <!-- Material delete logic could be added here if needed -->
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No materials found.</td>
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
