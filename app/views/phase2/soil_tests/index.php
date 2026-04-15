<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard">Home</a></li>
                    <li class="breadcrumb-item">Pre-Construction</li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($title) ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/create" class="btn btn-primary shadow-sm"><i class="bi bi-plus-circle me-1"></i> Add New</a>
        </div>
    </div>

    <!-- MAIN BODY -->
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card__wrapper shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project Name</th>
                                    <th>Site Engineer</th>
                                    <th>Test Date</th>
                                    <th>Lab Document</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tests)): ?>
                                    <?php foreach ($tests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars($row['engineer_name']) ?></td>
                                            <td><?= htmlspecialchars($row['test_date']) ?></td>
                                            <td><?= $row['lab_test_document_link'] ? '<a href="'.htmlspecialchars($row['lab_test_document_link']).'" target="_blank">View</a>' : 'N/A' ?></td>
                                            <td>
                                                <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
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
