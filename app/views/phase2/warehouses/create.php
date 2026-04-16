<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-warehouses">Warehouses</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card__wrapper shadow-sm">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-warehouse-store" method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Warehouse Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Project Site A Store, Central Godown" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Project (Optional)</label>
                                <select name="project_id" class="form-select">
                                    <option value="">-- General / No Specific Project --</option>
                                    <?php if (!empty($projects)): ?>
                                        <?php foreach ($projects as $proj): ?>
                                            <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['project_name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Location</label>
                                <textarea name="location" class="form-control" rows="2" placeholder="Detail address or site location"></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-check-circle me-1"></i> Save Warehouse
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/con-warehouses" class="btn btn-light px-4 border ms-2">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'app/views/layouts/footer.php'; ?>
