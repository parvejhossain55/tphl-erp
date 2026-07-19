<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = in_array($roleId, [1, 3, 14]);

// ── Land CSV import skipped-row report ────────────────────────
$landCsvSkipped = $_SESSION['land_csv_import_skipped'] ?? [];
unset($_SESSION['land_csv_import_skipped']); // clear after reading
?>

<?php if (!empty($landCsvSkipped)): ?>
<div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #dc3545 !important;">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
            <h6 class="mb-0 fw-bold text-danger">
                <?= count($landCsvSkipped) ?> row(s) failed validation and were <u>not imported</u>
            </h6>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
            <table class="table align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px;">CSV Row</th>
                        <th>Landowner</th>
                        <th>Project</th>
                        <th>Location</th>
                        <th>Reason for Failure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($landCsvSkipped as $sk): ?>
                    <tr>
                        <td>
                            <span class="badge bg-danger">#<?= (int)$sk['row'] ?></span>
                        </td>
                        <td class="fw-semibold"><?= htmlspecialchars($sk['landowner'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($sk['project'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($sk['location'] ?? '—') ?></td>
                        <td class="text-danger fw-semibold"><i class="fa-light fa-circle-exclamation me-1"></i><?= htmlspecialchars($sk['reason'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2">Land Acquisitions Portfolio</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Land Acquisitions</li>
            </ol>
        </nav>
    </div>
    <?php if ($canModify): ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importLandModal">
            <i class="fa-light fa-file-import me-1"></i> Bulk Import CSV
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLandModal">
            <i class="fa-light fa-plus me-1"></i> Record New Land Purchase
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="row mb-4 align-items-stretch">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100" ">
            <div class="card-body">
                <h6 class="50">Total Lands Purchased</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['total_lands'] ?? 0)) ?></h3>
                <div class="mt-2 50 small">
                    Area: <strong><?= number_format((float)($stats['total_area'] ?? 0), 2) ?> Dec/Katha</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="50">Registered & Cleared</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['registered_count'] ?? 0)) ?></h3>
                <div class="mt-2 50 small">
                    Cost: <strong>BDT <?= number_format((float)($stats['total_reg_cost'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100" >
            <div class="card-body">
                <h6 class="50">Bayana Underway</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['bayana_count'] ?? 0)) ?></h3>
                <div class="mt-2 50 small">
                    Paid: <strong>BDT <?= number_format((float)($stats['total_bayana_paid'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100" >
            <div class="card-body">
                <h6 class="50">Total Estimated Portfolio Value</h6>
                <h3 class="mb-0">BDT <?= number_format((float)($stats['total_value'] ?? 0), 2) ?></h3>
                <div class="mt-2 50 small">
                    Selected/Pending: <strong><?= number_format((int)($stats['selected_count'] ?? 0)) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th>Landowner</th>
                                <th>Total Area</th>
                                <th>Price/Unit</th>
                                <th>Total Amount</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($acquisitions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No land acquisitions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($acquisitions as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <span class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['land_owner_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['land_owner_phone'] ?? 'No Phone') ?></small>
                                        </td>
                                        <td><?= number_format((float)($row['total_area'] ?? 0), 2) ?> <?= htmlspecialchars($row['area_unit']) ?></td>
                                        <td>BDT <?= number_format((float)($row['price_per_unit'] ?? 0), 2) ?></td>
                                        <td class="fw-semibold text-success">BDT <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($row['location'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                            $badge = match ($row['status']) {
                                                'selected' => 'secondary',
                                                'bayana' => 'warning',
                                                'registered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'info'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $badge ?> text-capitalize"><?= $row['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= $base_url ?>/con-land-acquisitions/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-light fa-pen-to-square me-1"></i> Manage
                                                </a>
                                                <?php if ($roleId === 1 || $roleId === 3): ?>
                                                <form action="<?= $base_url ?>/con-land-acquisitions/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this land acquisition portfolio? This will delete all related bayana payments and registrations.');" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-light fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Land Modal -->
<div id="addLandModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Land Acquisition</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-acquisitions/store" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Assign to Project <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="project_id" required>
                                                    <option value="" disabled selected>Select Project...</option>
                                                    <?php foreach ($projects as $p): ?>
                                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Location / Address</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="location" placeholder="Mouza, Dag, Khatian numbers etc.">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Landowner Name <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="land_owner_name" required placeholder="Owner's full name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Landowner Phone</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="land_owner_phone" placeholder="Phone number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Total Area <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="number" step="0.0001" class="form-control" name="total_area" required placeholder="e.g. 15.5">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Area Unit <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="area_unit" required>
                                                    <option value="decimal" selected>Decimal</option>
                                                    <option value="katha">Katha</option>
                                                    <option value="bigha">Bigha</option>
                                                    <option value="sft">Square Feet</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Price per Unit (BDT) <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="number" step="0.01" class="form-control" name="price_per_unit" required placeholder="Price per decimal/katha">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Initial Status</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="status">
                                                    <option value="selected" selected>Selected (Under negotiation)</option>
                                                    <option value="bayana">Bayana (Advance paid)</option>
                                                    <option value="registered">Registered (Purchased)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Remarks</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="remarks" rows="2" placeholder="Negotiation details, agreements etc."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Save Land Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Import CSV Modal -->
<div class="modal fade" id="importLandModal" tabindex="-1" aria-labelledby="importLandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importLandModalLabel"><i class="fa-light fa-file-import me-2"></i>Bulk Import Land Purchase Portfolio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <h6 class="fw-bold mb-1"><i class="fa-light fa-circle-info me-2"></i>Instructions & Validation Rules:</h6>
                    <ul class="mb-0 small ps-3">
                        <li><strong>Database Transaction:</strong> The entire import runs in a database transaction. If any row fails validation, no changes will be made to the database.</li>
                        <li><strong>Required Columns:</strong> <code>project_name</code>, <code>land_owner_name</code>, <code>total_area</code>, <code>price_per_unit</code>, and <code>land_dept_email</code> are required. All other columns are optional.</li>
                        <li><strong>Spelling & Project Names:</strong> Project names must match the project names in TPHL-ERP exactly.</li>
                        <li><strong>Emails Lookup:</strong> User emails for <code>land_dept_email</code>, <code>management_email</code>, and <code>account_verify_email</code> must match existing active users in TPHL-ERP to assign IDs correctly.</li>
                        <li><strong>Drive Document Links:</strong> You can paste Google Drive links directly into <code>bayana_document_link</code> and <code>deed_document_link</code>.</li>
                        <li><strong>File Uploads (Optional):</strong> If you want to upload local files (e.g. agreement PDF, deed scan), write the exact filename in the CSV column (e.g. <code>deed_copy.pdf</code>) and upload the file in the "Document Attachments" field below.</li>
                    </ul>
                </div>

                <div class="text-center mb-4">
                    <a href="<?= $base_url ?>/con-land-acquisitions/sample-csv" class="btn btn-success px-4">
                        <i class="fa-light fa-download me-2"></i>Download Sample CSV Template
                    </a>
                </div>

                <form action="<?= $base_url ?>/con-land-acquisitions/execute-import" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <!-- CSV File -->
                        <div class="col-12">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label class="fw-bold">Select CSV File <span class="text-danger">*</span></label>
                                </div>
                                <div class="form__input">
                                    <input type="file" name="csv_file" accept=".csv" required class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Attachments (Multiple) -->
                        <div class="col-12">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label class="fw-bold">Document Attachments <span class="text-muted">(Optional, Select multiple files)</span></label>
                                    <div class="text-muted small mt-1">Select all files that are referenced by name in your CSV (e.g. <code>bayana_receipt.pdf</code>, <code>deed_copy.pdf</code>).</div>
                                </div>
                                <div class="form__input mt-2">
                                    <input type="file" name="attachments[]" multiple class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submit__btn text-center mt-4">
                        <button class="btn btn-primary px-5" type="submit">Import Portfolio & Transactions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



