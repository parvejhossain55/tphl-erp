<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item">Inventory</li>
                <li class="breadcrumb-item active">Current Stock Levels</li>
            </ol>
        </nav>
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
                                    <th>Material</th>
                                    <th>Warehouse</th>
                                    <th>Current Quantity</th>
                                    <th>Unit</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stocks)): ?>
                                    <?php foreach ($stocks as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><div class="fw-bold"><?= htmlspecialchars($row['material_name']) ?></div></td>
                                            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                                            <td class="fw-bold text-primary"><?= number_format($row['current_quantity'], 3) ?></td>
                                            <td><?= htmlspecialchars($row['unit']) ?></td>
                                            <td><?= date('d M Y h:i A', strtotime($row['updated_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No stock data available. Stock records are created when materials are moved IN to a warehouse.</td>
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
