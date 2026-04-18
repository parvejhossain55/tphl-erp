<?php $page_title = "Edit Design Type"; $page = 'Edit Design Type'; $show_breadcrumb = true; ?>
<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/update" method="POST">
        <input type="hidden" name="id" value="<?= $record['id'] ?>">
        <div class="row gy-20">
            <div class="col-xl-8">
                <label>Design Type Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($record['name']) ?>">
            </div>
            <div class="col-xl-4">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active"   <?= $record['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $record['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Update</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-design-types" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div></div>
