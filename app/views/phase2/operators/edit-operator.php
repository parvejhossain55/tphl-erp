<?php
$page_title = "Edit Operator";
$page = 'Edit Operator';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-operator-update/<?= $operator['id'] ?>" method="POST">
            <div class="row gy-20">

                <div class="col-xl-6">
                    <label>Operator Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($operator['name']) ?>" required>
                </div>

                <div class="col-xl-6">
                    <label>Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-control" required>
                        <?php foreach (['Contractor','Subcontractor','Supervisor','Worker'] as $r): ?>
                            <option value="<?= $r ?>" <?= $operator['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-xl-6">
                    <label>Trade / Expertise</label>
                    <input type="text" name="trade" class="form-control" value="<?= htmlspecialchars($operator['trade'] ?? '') ?>">
                </div>

                <div class="col-xl-6">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($operator['phone'] ?? '') ?>">
                </div>

                <div class="col-xl-6">
                    <label>NID Number</label>
                    <input type="text" name="nid_number" class="form-control" value="<?= htmlspecialchars($operator['nid_number'] ?? '') ?>">
                </div>

                <div class="col-xl-6">
                    <label>Daily Rate (BDT)</label>
                    <input type="number" step="0.01" name="daily_rate" class="form-control" value="<?= $operator['daily_rate'] ?? '' ?>">
                </div>

                <div class="col-xl-12">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($operator['address'] ?? '') ?></textarea>
                </div>

                <div class="col-xl-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= $operator['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $operator['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Update Operator</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-operators" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
