<?php $page_title = "Edit Project Structure Design"; $page = 'Edit Structure Design'; $show_breadcrumb = true; ?>

<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/structure-design/update" method="POST">
        <input type="hidden" name="id" value="<?= $record['id'] ?>">
        <div class="row gy-20">

            <div class="col-xl-6">
                <label>Project <span class="text-danger">*</span></label>
                <select name="project_id" class="form-control select2" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($p['id'] == $record['project_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6">
                <label>Designer Type</label>
                <select name="designer_type" class="form-control" id="designer_type">
                    <option value="internal" <?= ($record['designer_type'] === 'internal') ? 'selected' : '' ?>>Internal</option>
                    <option value="external" <?= ($record['designer_type'] === 'external') ? 'selected' : '' ?>>External</option>
                </select>
            </div>

            <div class="col-xl-6 <?= ($record['designer_type'] !== 'internal') ? 'd-none' : '' ?>" id="internal_designer_wrap">
                <label>Internal Designer (User) <span class="text-danger">*</span></label>
                <select name="internal_designer_user_id" class="form-control select2" id="internal_designer_user_id" <?= ($record['designer_type'] === 'internal') ? 'required' : '' ?>>
                    <option value="">Select User</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($u['full_name'] == $record['designer_name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Current: <?= htmlspecialchars($record['designer_name']) ?></small>
            </div>

            <div class="col-xl-6 <?= ($record['designer_type'] === 'internal') ? 'd-none' : '' ?>" id="ext_name_wrap">
                <label>External Designer Name <span class="text-danger">*</span></label>
                <input type="text" name="designer_name" id="designer_name" class="form-control" value="<?= ($record['designer_type'] === 'external') ? htmlspecialchars($record['designer_name']) : '' ?>" placeholder="Full Name" <?= ($record['designer_type'] === 'external') ? 'required' : '' ?>>
            </div>

            <div class="col-xl-6 <?= ($record['designer_type'] === 'internal') ? 'd-none' : '' ?>" id="ext_phone_wrap">
                <label>Designer Phone</label>
                <input type="text" name="designer_phone" class="form-control" value="<?= htmlspecialchars($record['designer_phone'] ?? '') ?>" placeholder="Phone Number">
            </div>

            <div class="col-xl-12 <?= ($record['designer_type'] === 'internal') ? 'd-none' : '' ?>" id="ext_address_wrap">
                <label>Designer Address</label>
                <textarea name="designer_address" class="form-control" rows="2" placeholder="Address"><?= htmlspecialchars($record['designer_address'] ?? '') ?></textarea>
            </div>

            <div class="col-xl-6">
                <label>Design Link <span class="text-danger">*</span></label>
                <input type="url" name="design_link" class="form-control" value="<?= htmlspecialchars($record['design_link'] ?? '') ?>" placeholder="https://..." required>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Update Design</button>
                <a href="<?= $GLOBALS['base_url'] ?>/structure-design" class="btn btn-secondary ms-2">Cancel</a>
            </div>

        </div>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const designerType = document.getElementById('designer_type');
    const internalWrap = document.getElementById('internal_designer_wrap');
    const extNameWrap = document.getElementById('ext_name_wrap');
    const extPhoneWrap = document.getElementById('ext_phone_wrap');
    const extAddressWrap = document.getElementById('ext_address_wrap');
    const internalUserId = document.getElementById('internal_designer_user_id');
    const designerName = document.getElementById('designer_name');

    designerType.addEventListener('change', function() {
        const isInternal = this.value === 'internal';
        
        if (isInternal) {
            internalWrap.classList.remove('d-none');
            extNameWrap.classList.add('d-none');
            extPhoneWrap.classList.add('d-none');
            extAddressWrap.classList.add('d-none');
            
            internalUserId.setAttribute('required', 'required');
            designerName.removeAttribute('required');
        } else {
            internalWrap.classList.add('d-none');
            extNameWrap.classList.remove('d-none');
            extPhoneWrap.classList.remove('d-none');
            extAddressWrap.classList.remove('d-none');
            
            internalUserId.removeAttribute('required');
            designerName.setAttribute('required', 'required');
        }
    });
});
</script>
