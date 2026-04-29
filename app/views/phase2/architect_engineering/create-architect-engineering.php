<?php $page_title = "Add Architect & Engineering"; $page = 'Add Architect & Engineering'; $show_breadcrumb = true; ?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/store" method="POST">
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

                <!-- user_id auto from session — no dropdown needed -->

                <div class="col-xl-6">
                    <label>Full Architecture Design Link</label>
                    <input type="url" name="full_architecture_design_link" class="form-control" placeholder="https://...">
                </div>

                <div class="col-xl-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft">Draft</option>
                        <option value="partially">Partially Completed</option>
                        <option value="fully">Fully Completed</option>
                    </select>
                </div>

                <div class="col-xl-12">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add notes..."></textarea>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Save Record</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>