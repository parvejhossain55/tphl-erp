<?php
$page_title = "Bookings CSV Bulk Import Preview";
?>
<style>
    .main-content {
    margin-left: 90px;
    min-height: 100vh;
    padding: 0;
}
</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-10">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-5">Bookings CSV Bulk Import Preview</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb mb-5">
                                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/lead-lists">Leads & Bookings</a></li>
                                <li class="breadcrumb-item active">Import Preview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Summary -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Import Summary for <?= htmlspecialchars($filename) ?></h5>
                            <?php if (empty($skippedRows)): ?>
                                <div class="alert mb-0 border" style="background:rgba(10,179,156,.1); border-color:rgba(10,179,156,.3) !important; color:#10c469;">
                                    <i class="ri-checkbox-circle-line me-2"></i> All <?= $valid_count ?> rows are valid and ready to import.
                                </div>
                            <?php else: ?>
                                <div class="alert mb-3 border" style="background:rgba(245,158,11,.1); border-color:rgba(245,158,11,.3) !important; color:#f9b300;">
                                    <i class="ri-alert-line me-2"></i> Found <?= count($skippedRows) ?> rows with errors (missing data, not found, or already booked). These will be skipped.
                                </div>
                                <div class="alert mb-0 border" style="background:rgba(10,179,156,.1); border-color:rgba(10,179,156,.3) !important; color:#10c469;">
                                    <i class="ri-checkbox-circle-line me-2"></i> <?= $valid_count ?> rows are valid and ready to import.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($skippedRows)): ?>
                <!-- Errors Resolution Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-bottom-dashed">
                                <h5 class="card-title mb-0">Skipped Rows (Errors)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 80px;">CSV Row</th>
                                                <th scope="col">Lead Phone</th>
                                                <th scope="col">Project Name</th>
                                                <th scope="col">Flat Name</th>
                                                <th scope="col">Reason for Failure</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($skippedRows as $skipped): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary">#<?= htmlspecialchars($skipped['row']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted"><i class="ri-phone-line align-bottom me-1"></i> <?= htmlspecialchars($skipped['lead_phone']) ?></span>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($skipped['project']) ?></h6>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($skipped['flat']) ?></h6>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger"><i class="ri-error-warning-line me-1"></i><?= htmlspecialchars($skipped['reason']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12 text-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-light me-2">Cancel</a>
                    <form action="<?= $GLOBALS['base_url'] ?>/bulk-bookings-import-execute" method="POST" class="d-inline">
                        <button type="submit" class="btn btn-primary" id="btn-import-bookings" <?= $valid_count == 0 ? 'disabled' : '' ?>>
                            <i class="ri-file-upload-line align-middle me-1"></i> Import Valid Bookings (<?= $valid_count ?>)
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
