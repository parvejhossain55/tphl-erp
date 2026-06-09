

<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            
            <div class="d-flex align-items-center mb-4">
                <a href="<?= $GLOBALS['base_url'] ?>/legal-sheet-lists" class="btn btn-light border me-3" style="border-radius:8px">
                    <i class="fal fa-arrow-left"></i> Back
                </a>
                <h4 class="mb-0 text-dark font-weight-bold">Add New Legal Sheet</h4>
            </div>

            <div class="card shadow-sm border-0" style="border-radius:12px; max-width:800px">
                <div class="card-body p-4">
                    <form action="<?= $GLOBALS['base_url'] ?>/legal-sheet-store" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Select Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-control select2" required style="border-radius:8px">
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Sheet Type <span class="text-danger">*</span></label>
                                <input type="text" name="sheet_type" class="form-control" required placeholder="e.g. Rajuk, Amin Sheet" style="border-radius:8px">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label font-weight-bold">Sheet Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="Title of the sheet" style="border-radius:8px">
                            </div>
                            <div class="col-md-4 mb-3 d-none">
                                <label class="form-label font-weight-bold">Version</label>
                                <input type="text" name="version" class="form-control" value="V1" placeholder="e.g. V1, V2" style="border-radius:8px">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Sheet Link (Google Drive / Cloud) <span class="text-danger">*</span></label>
                            <input type="url" name="link" class="form-control" required placeholder="https://..." style="border-radius:8px">
                        </div>

                        <div class="mb-4">
                            <label class="form-label font-weight-bold">Remarks (Optional)</label>
                            <textarea name="remark" class="form-control" rows="3" placeholder="Any additional notes..." style="border-radius:8px"></textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary" style="border-radius:8px; padding:10px 24px; font-weight:600">
                                <i class="fal fa-save me-1"></i> Save Legal Sheet
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>


