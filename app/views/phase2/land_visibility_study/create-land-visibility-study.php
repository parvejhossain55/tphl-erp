<?php
$page_title = "Add Land Visibility Study";
$page = 'Add Land Visibility Study';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/store" method="POST">
            <div class="row gy-20">

                <!-- Project -->
                <div class="col-xl-6">
                    <label>Project <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Legal Doc Status -->
                <div class="col-xl-6">
                    <label>Land Legal Doc Status</label>
                    <select name="land_legal_doc_status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Verified">Verified</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Digital Survey Report Link -->
                <div class="col-xl-6">
                    <label>Digital Survey Report Link</label>
                    <input type="url" name="digital_survey_report_link" class="form-control" placeholder="https://...">
                </div>

                <!-- Govt Approval Doc Link -->
                <div class="col-xl-6">
                    <label>Govt Approval Document Link</label>
                    <input type="url" name="govt_approval_doc_link" class="form-control" placeholder="https://...">
                </div>

                <!-- Others Doc Link -->
                <div class="col-xl-6">
                    <label>Others Document Link <span class="text-muted">(Optional)</span></label>
                    <input type="url" name="others_doc_link" class="form-control" placeholder="https://...">
                </div>

                <!-- Buttons -->
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Save Study</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
