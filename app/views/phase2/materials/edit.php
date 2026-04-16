<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-materials">Materials</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card__wrapper shadow-sm">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-material-update/<?= $material['id'] ?>" method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Material Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($material['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Unit <span class="text-danger">*</span></label>
                                <input type="text" name="unit" class="form-control" 
                                       value="<?= htmlspecialchars($material['unit']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Minimum Stock Level</label>
                                <input type="number" step="0.001" name="min_stock_level" class="form-control" 
                                       value="<?= htmlspecialchars($material['min_stock_level']) ?>">
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Update Material
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/con-materials" class="btn btn-light px-4 border ms-2">
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
