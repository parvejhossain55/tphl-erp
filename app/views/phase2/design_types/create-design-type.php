<?php $page_title = "Add Design Type"; $page = 'Add Design Type'; $show_breadcrumb = true; ?>
<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/store" method="POST">
        <div class="row gy-20">
            <div class="col-xl-8">
                <label>Design Type Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Architectural, Structural">
            </div>
            <div class="col-xl-4">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Save</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-design-types" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div></div>
