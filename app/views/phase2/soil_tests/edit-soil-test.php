<?php $page_title = "Edit Soil Test"; $page = 'Edit Soil Test'; $show_breadcrumb = true; ?>
<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-soil-tests/update" method="POST">
        <input type="hidden" name="id" value="<?= $record['id'] ?>">
        <div class="row gy-20">
            <div class="col-xl-6">
                <label>Project <span class="text-danger">*</span></label>
                <select name="project_id" class="form-control" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($p['id'] == $record['project_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-xl-6">
                <label>Site Engineer</label>
                <select name="site_engineer_id" class="form-control">
                    <option value="">Select Engineer (Optional)</option>
                    <?php foreach ($engineers as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= ($e['id'] == $record['site_engineer_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-xl-6">
                <label>Test Date</label>
                <input type="date" name="test_date" class="form-control" value="<?= $record['test_date'] ?? '' ?>">
            </div>
            <div class="col-xl-6">
                <label>Lab Test Document Link</label>
                <input type="url" name="lab_test_document_link" class="form-control"
                    value="<?= htmlspecialchars($record['lab_test_document_link'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="col-xl-6">
                <label>Field Test Document Link</label>
                <input type="url" name="field_test_doc_link" class="form-control"
                    value="<?= htmlspecialchars($record['field_test_doc_link'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Update Soil Test</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div></div>
