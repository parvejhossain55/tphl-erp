
<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>

    <section class="section">
        <form action="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/store" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Project</label>
                    <select name="project_id" class="form-select" required>
                        <option value="">Select Project</option>
                        <?php foreach($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Legal Doc Status</label>
                    <select name="land_legal_doc_status" class="form-select">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </section>
</main>
