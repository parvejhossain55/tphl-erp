<?php
$page_title = "Add Material";
$page = 'Add Material';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-material-store" method="POST">
            <div class="row gy-20">

                <div class="col-xl-6">
                    <label>Material Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Cement, Rod, Brick" required>
                </div>

                <div class="col-xl-6">
                    <label>Unit <span class="text-danger">*</span></label>
                    <input type="text" name="unit" class="form-control" placeholder="e.g. Bag, KG, CFT, PCS" required>
                </div>

                <div class="col-xl-6">
                    <label>Minimum Stock Level</label>
                    <input type="number" step="0.001" name="min_stock_level" class="form-control" value="0" placeholder="0.000">
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Save Material</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-materials" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
