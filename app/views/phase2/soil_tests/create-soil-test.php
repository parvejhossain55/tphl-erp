<?php $page_title = "Add Soil Test"; $page = 'Add Soil Test'; $show_breadcrumb = true; ?>
<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-soil-tests/store" method="POST">
        <div class="row gy-20">
            <div class="col-xl-6">
                <label>Project <span class="text-danger">*</span></label>
                <select name="project_id" class="form-control" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-xl-6">
                <label>Site Engineer</label>
                <select name="site_engineer_id" class="form-control">
                    <option value="">Select Engineer (Optional)</option>
                    <?php foreach ($engineers as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-xl-6">
                <label>Test Date</label>
                <input type="date" name="test_date" class="form-control">
            </div>
            <div class="col-xl-6">
                <label>Lab Test Document Link</label>
                <input type="url" name="lab_test_document_link" class="form-control" placeholder="https://...">
            </div>
            <div class="col-12">
                <label>Field Data / Notes</label>
                <textarea name="field_data" class="form-control" rows="4" placeholder="Enter field observations..."></textarea>
            </div>
            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Save Soil Test</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div></div>
