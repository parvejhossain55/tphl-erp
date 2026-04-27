<?php 
$page_title = "Edit Work Type"; 
$page = 'Edit Work Type'; 
$show_breadcrumb = true; 
?>
<div class="row">
    <div class="card__wrapper shadow-sm">
        <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-update/<?= $record['id'] ?>" method="POST">
            <div class="row gy-20">
                <div class="col-xl-6">
                    <label class="form-label fw-bold">Design Type <span class="text-danger">*</span></label>
                    <select name="design_type_id" class="form-select" required>
                        <option value="">-- Select Design Type --</option>
                        <?php foreach ($designTypes as $dt): ?>
                            <option value="<?= $dt['id'] ?>" <?= $dt['id'] == $record['design_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-6">
                    <label class="form-label fw-bold">Work Type Name <span class="text-danger">*</span></label>
                    <input type="text" name="work_type_name" class="form-control" required value="<?= htmlspecialchars($record['work_type_name']) ?>">
                </div>
                <div class="col-xl-12 mt-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($record['description']) ?></textarea>
                </div>
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Update</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-work-types" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
