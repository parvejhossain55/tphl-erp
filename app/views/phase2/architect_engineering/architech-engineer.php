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
            <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/create" class="btn btn-primary shadow-sm"><i class="bi bi-plus-circle me-1"></i> Add New</a>
        </div>
    </div>

    <!-- MAIN BODY -->
            <div class="col-12">
                <div class="card__wrapper shadow-sm">
                    <div class="table__wrapper">
                        <table class="table mb-20 multiple_tables">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project Name</th>
                                    <th>Architect</th>
                                    <th>Design Link</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($records)): ?>
                                    <?php foreach ($records as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><?= $row['full_architecture_design_link'] ? '<a href="' . htmlspecialchars($row['full_architecture_design_link']) . '" target="_blank">View</a>' : 'N/A' ?></td>
                                            <td>
                                                <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
          
    </section>
</main>