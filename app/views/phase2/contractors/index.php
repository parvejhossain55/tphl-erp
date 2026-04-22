

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item">Construction</li>
                    <li class="breadcrumb-item active">Operators</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/con-operator-create" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Operator
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
                                    <th>Operator Name</th>
                                    <th>Role</th>
                                    <th>Trade</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($operators)): ?>
                                    <?php foreach ($operators as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                                <small class="text-muted">NID: <?= htmlspecialchars($row['nid_number'] ?? 'N/A') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['role']) ?></td>
                                            <td><?= htmlspecialchars($row['trade'] ?? 'General') ?></td>
                                            <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'danger' ?>">
                                                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/con-operator-edit/<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger removeBtnCust" 
                                                            data-id="<?= $row['id'] ?>" 
                                                            data-route="con-operator-delete" 
                                                            title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No operators found.</td>
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
