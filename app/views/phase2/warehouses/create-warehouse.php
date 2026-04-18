<?php
$page_title = "Add Warehouse";
$page = 'Add Warehouse';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-warehouse-store" method="POST">
            <div class="row gy-20">

                <div class="col-xl-6">
                    <label>Warehouse Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Main Site Store" required>
                </div>

                <div class="col-xl-6">
                    <label>Project (Optional)</label>
                    <select name="project_id" class="form-control">
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-xl-12">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Physical address or location description">
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Save Warehouse</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-warehouses" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
