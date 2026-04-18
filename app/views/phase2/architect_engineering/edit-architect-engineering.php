<?php
$page_title = "Edit Architect & Engineering";
$page = 'Edit Architect & Engineering';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/update" method="POST">
            <input type="hidden" name="id" value="<?= $record['id'] ?>">
            <div class="row gy-20">

                <div class="col-xl-6">
                    <label>Project <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($p['id'] == $record['project_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-xl-6">
                    <label>Internal Designer (User)</label>
                    <select name="user_id" class="form-control">
                        <option value="">Select User (Optional)</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($u['id'] == $record['user_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-xl-12">
                    <label>Full Architecture Design Link</label>
                    <input type="url" name="full_architecture_design_link" class="form-control"
                        value="<?= htmlspecialchars($record['full_architecture_design_link'] ?? '') ?>"
                        placeholder="https://...">
                </div>

                <div class="col-xl-12">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($record['notes'] ?? '') ?></textarea>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Update Record</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
