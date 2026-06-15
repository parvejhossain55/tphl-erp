<?php $page_title = "Add Soil Test";
$page = 'Add Soil Test';
$show_breadcrumb = true; ?>
<div class="row">
    <div class="card__wrapper">
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
                    <div class="form-control d-flex align-items-center gap-2" style="background:var(--input-bg,#f8f9fa);cursor:default;color:var(--text-muted)">
                        <i class="fal fa-user-hard-hat" style="color:var(--clr-theme-secondary)"></i>
                        <span style="font-weight:600;color:inherit"><?= htmlspecialchars($currentUserName ?? 'N/A') ?></span>
                        <span class="ms-auto" style="font-size:10px;background:rgba(var(--clr-theme-secondary-rgb),.1);color:var(--clr-theme-secondary);padding:2px 8px;border-radius:20px;font-weight:700">Auto</span>
                    </div>
                    <small class="text-muted">Automatically set to your account</small>
                </div>
                <div class="col-xl-6">
                    <label>Test Date</label>
                    <input type="date" name="test_date" class="form-control">
                </div>
                <div class="col-xl-6">
                    <label>Soil Test Document Link</label>
                    <input type="url" name="lab_test_document_link" class="form-control" placeholder="https://...">
                </div>
                <div class="col-xl-6">
                    <label>Field Test Document Link</label>
                    <input type="url" name="field_test_doc_link" class="form-control" placeholder="https://...">
                </div>
                <div class="col-12 text-center mt-4 d-flex ">
                    <button type="submit" class="btn btn-primary px-5">Save Soil Test</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>