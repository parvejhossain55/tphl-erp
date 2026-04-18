<?php $page_title = "Add Project Drawing"; $page = 'Add Project Drawing'; $show_breadcrumb = true; ?>

<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-project-drawings/store" method="POST">
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
                <label>Design Type</label>
                <select name="design_type_id" class="form-control">
                    <option value="">Select Design Type</option>
                    <?php foreach ($design_types as $dt): ?>
                        <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6">
                <label>Designer Type</label>
                <select name="is_in_house" class="form-control" id="designer_type">
                    <option value="1">In-House</option>
                    <option value="0">External</option>
                </select>
            </div>

            <div class="col-xl-6" id="internal_designer_wrap">
                <label>Internal Designer (User)</label>
                <select name="user_id" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6 d-none" id="ext_name_wrap">
                <label>External Designer Name</label>
                <input type="text" name="designer_name" class="form-control" placeholder="Full Name">
            </div>
            <div class="col-xl-6 d-none" id="ext_desig_wrap">
                <label>Designer Designation</label>
                <input type="text" name="designer_designation" class="form-control" placeholder="e.g. Architect">
            </div>
            <div class="col-xl-6 d-none" id="ext_company_wrap">
                <label>Designer Company</label>
                <input type="text" name="designer_company" class="form-control" placeholder="Company Name">
            </div>

            <div class="col-xl-6">
                <label>Design Link</label>
                <input type="url" name="design_link" class="form-control" placeholder="https://...">
            </div>

            <div class="col-xl-3">
                <label>Version</label>
                <input type="text" name="version" class="form-control" value="1.0" placeholder="1.0">
            </div>

            <div class="col-xl-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Draft">Draft</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Save Drawing</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings" class="btn btn-secondary ms-2">Cancel</a>
            </div>

        </div>
    </form>
</div></div>

<script>
document.getElementById('designer_type').addEventListener('change', function () {
    const inHouse = this.value === '1';
    document.getElementById('internal_designer_wrap').classList.toggle('d-none', !inHouse);
    document.getElementById('ext_name_wrap').classList.toggle('d-none', inHouse);
    document.getElementById('ext_desig_wrap').classList.toggle('d-none', inHouse);
    document.getElementById('ext_company_wrap').classList.toggle('d-none', inHouse);
});
</script>
