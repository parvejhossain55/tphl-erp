<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>

    <section class="section">
        <form action="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/update" method="POST">
            <input type="hidden" name="id" value="<?= $study['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Project</label>
                    <select name="project_id" class="form-select" required>
                        <option value="">Select Project</option>
                        <?php foreach($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= $proj['id'] == $study['project_id'] ? 'selected' : '' ?>><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Legal Doc Status</label>
                    <select name="land_legal_doc_status" class="form-select">
                        <option value="Pending" <?= $study['land_legal_doc_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $study['land_legal_doc_status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $study['land_legal_doc_status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </section>
</main>
<?php require_once 'app/views/layouts/footer.php'; ?>
