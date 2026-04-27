<?php $page_title = "Add Project Structure Design"; $page = 'Add Structure Design'; $show_breadcrumb = true; ?>

<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/structure-design/store" method="POST">
        <div class="row gy-20">

            <div class="col-xl-6">
                <label>Project <span class="text-danger">*</span></label>
                <select name="project_id" class="form-control select2" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6">
                <label>Designer Type</label>
                <select name="designer_type" class="form-control" id="designer_type">
                    <option value="internal">Internal</option>
                    <option value="external">External</option>
                </select>
            </div>

            <!-- Internal: show session user as readonly -->
            <div class="col-xl-6" id="internal_designer_wrap">
                <label>Internal Designer</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Current User') ?>"
                       readonly style="background:#f8f9fa;cursor:not-allowed">
                <small class="text-muted">Auto-filled from your login session</small>
            </div>

            <!-- External fields — hidden by default -->
            <div class="col-xl-6 d-none" id="ext_name_wrap">
                <label>External Designer Name <span class="text-danger">*</span></label>
                <input type="text" name="designer_name" id="designer_name" class="form-control" placeholder="Full Name">
            </div>

            <div class="col-xl-6 d-none" id="ext_phone_wrap">
                <label>Designer Phone</label>
                <input type="text" name="designer_phone" class="form-control" placeholder="Phone Number">
            </div>

            <div class="col-xl-6 d-none" id="ext_company_wrap">
                <label>Company Name</label>
                <input type="text" name="company_name" class="form-control" placeholder="Designer Company Name">
            </div>

            <div class="col-xl-6 d-none" id="ext_address_wrap">
                <label>Designer Address</label>
                <textarea name="designer_address" class="form-control" rows="2" placeholder="Address"></textarea>
            </div>

            <div class="col-xl-6">
                <label>Design Link <span class="text-danger">*</span></label>
                <input type="url" name="design_link" class="form-control" placeholder="https://..." required>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Save Design</button>
                <a href="<?= $GLOBALS['base_url'] ?>/structure-design" class="btn btn-secondary ms-2">Cancel</a>
            </div>

        </div>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const designerType  = document.getElementById('designer_type');
    const internalWrap  = document.getElementById('internal_designer_wrap');
    const extFields     = ['ext_name_wrap','ext_phone_wrap','ext_company_wrap','ext_address_wrap'];
    const designerName  = document.getElementById('designer_name');

    function toggle() {
        const isInternal = designerType.value === 'internal';
        internalWrap.classList.toggle('d-none', !isInternal);
        extFields.forEach(id => document.getElementById(id).classList.toggle('d-none', isInternal));

        if (isInternal) {
            designerName && designerName.removeAttribute('required');
        } else {
            designerName && designerName.setAttribute('required', 'required');
        }
    }

    designerType.addEventListener('change', toggle);
    toggle(); // run on page load
});
</script>
