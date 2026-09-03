<?php
$page_title = "CSV Bulk Payment Import";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;

$preview_data = $preview_data ?? null;
$errors = $errors ?? null;
$summary = $summary ?? null;
$filename = $filename ?? '';
$base_url = $GLOBALS['base_url'];
?>

<style>
    .csv-import-card {
        border: 2px dashed #dee2e6;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        transition: border-color 0.3s, background 0.3s;
        cursor: pointer;
    }
    .csv-import-card:hover,
    .csv-import-card.drag-over {
        border-color: #6c5ffc;
        background: linear-gradient(135deg, #f0eeff 0%, #fff 100%);
    }
    .csv-import-card i.upload-icon {
        font-size: 4rem;
        color: #6c5ffc;
        margin-bottom: 16px;
    }
    .summary-card {
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e9ecef;
    }
    .summary-card .value {
        font-size: 2rem;
        font-weight: 700;
    }
    .summary-card .label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .preview-table th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .preview-table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .row-error {
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
    .row-valid {
        background-color: rgba(25, 135, 84, 0.03) !important;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    .template-instructions {
        background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 100%);
        border: 1px solid #b6d4fe;
        border-radius: 12px;
        padding: 20px;
    }
    .template-instructions code {
        background: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.82rem;
    }
    .preview-container {
        max-height: 500px;
        overflow-y: auto;
        border-radius: 12px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30 d-flex justify-content-between align-items-center">
                <h3 class="card__title">
                    <i class="fa-light fa-file-csv me-2" style="color: #6c5ffc;"></i>
                    CSV Bulk Payment Import
                </h3>
                <div class="d-flex gap-2">
                    <a href="<?= $base_url ?>/csv-import-template" class="btn btn-outline-primary btn-sm">
                        <i class="fa-light fa-download me-1"></i> Download Template
                    </a>
                    <a href="<?= $base_url ?>/clients-cash-in" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-light fa-arrow-left me-1"></i> Back to Clients
                    </a>
                </div>
            </div>

            <div class="card__body">

                <?php if (!$preview_data && !$errors): ?>
                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STATE 1: UPLOAD FORM                               -->
                <!-- ═══════════════════════════════════════════════════ -->

                <!-- Instructions Panel -->
                <div class="template-instructions mb-4">
                    <h6 class="mb-3"><i class="fa-light fa-circle-info me-2"></i>CSV Format Instructions</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Required Columns (8):</strong></p>
                            <ul class="mb-0" style="font-size:0.85rem;">
                                <li><code>client_name</code> — Client full name (for validation)</li>
                                <li><code>client_phone</code> — Used to resolve <strong>lead_id</strong></li>
                                <li><code>project_name</code> — Must match project name in system</li>
                                <li><code>flat_name</code> — Must match flat name under the project</li>
                                <li><code>amount</code> — Payment amount (positive number)</li>
                                <li><code>payment_date</code> — Date (YYYY-MM-DD or DD/MM/YYYY)</li>
                                <li><code>payment_type</code> — <code>development_charge</code>, <code>emi</code>, or <code>emi_due_settlement</code></li>
                                <li><code>payment_method</code> — <code>cash</code>, <code>bank</code>, <code>online</code>, <code>cheque</code></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Optional Columns (13):</strong></p>
                            <ul class="mb-0" style="font-size:0.85rem;">
                                <li><code>verification_status</code> — <code>pending</code> (default), <code>verified</code>, <code>rejected</code></li>
                                <li><code>company_bank_name</code> — Company bank name (auto-inserts if new)</li>
                                <li><code>verify_by</code> — Email of the verifier</li>
                                <li><code>transaction_ref</code> — Transaction reference ID</li>
                                <li><code>bank_name</code> — Bank name (auto-resolved)</li>
                                <li><code>bank_account_no</code> — Bank account number</li>
                                <li><code>cheque_no</code> — Cheque number</li>
                                <li><code>online_type</code> — bkash, nagad, rocket, etc.</li>
                                <li><code>payment_details</code> — Notes/description</li>
                                <li><code>bulk_emi_discount</code> — Bulk discount for multi-EMI</li>
                                <li><code>emi_due_date</code> — EMI schedule due date</li>
                                <li><code>emi_no</code> — EMI serial number (auto if empty)</li>
                                <li><code>service_fee</code> — Service fee amount (auto-calc if empty)</li>
                            </ul>
                            <div class="mt-3 p-2 rounded" style="background:rgba(25,135,84,0.08);">
                                <p class="mb-1" style="font-size:0.82rem;"><strong><i class="fa-light fa-robot me-1"></i>Auto-handled by system:</strong></p>
                                <ul class="mb-0" style="font-size:0.80rem;">
                                    <li><code>payment_status</code> — Auto: <code>paid</code> / <code>partially_paid</code></li>
                                    <li><code>created_by</code> / <code>verified_by</code> — Auto from logged-in user (if empty)</li>
                                    <li><code>service_fee</code> — Auto-calculated if not provided</li>
                                    <li><code>due_emi_amount</code> — Auto-calculated from EMI schedule</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background:rgba(108,95,252,0.08);">
                        <small class="text-muted">
                            <i class="fa-light fa-lightbulb me-1" style="color:#6c5ffc;"></i>
                            <strong>Tip:</strong> Download the template above for a pre-formatted CSV with sample data. 
                            Rows must be in <strong>chronological order</strong> — development charges before EMI payments for each booking.
                        </small>
                    </div>
                </div>

                <!-- Upload Form -->
                <form action="<?= $base_url ?>/csv-import-preview" method="POST" enctype="multipart/form-data" id="csvUploadForm">
                    <div class="csv-import-card" id="dropZone" onclick="document.getElementById('csvFileInput').click();">
                        <i class="fa-light fa-cloud-arrow-up upload-icon"></i>
                        <h5 class="mb-2">Drop your CSV file here or click to browse</h5>
                        <p class="text-muted mb-3">Supports .csv files only</p>
                        <input type="file" name="csv_file" id="csvFileInput" accept=".csv" 
                               class="d-none" required onchange="handleFileSelect(this)">
                        <div id="selectedFileName" class="mt-2" style="display:none;">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <i class="fa-light fa-file-csv me-2"></i>
                                <span id="fileNameDisplay"></span>
                            </span>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="uploadBtn" disabled>
                            <i class="fa-light fa-magnifying-glass me-2"></i> Upload & Preview
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STATE 2: PREVIEW TABLE                             -->
                <!-- ═══════════════════════════════════════════════════ -->

                <!-- File Name Badge -->
                <div class="mb-3">
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <i class="fa-light fa-file-csv me-2"></i><?= htmlspecialchars($filename) ?>
                    </span>
                </div>

                <!-- Summary Cards -->
                <?php if ($summary): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#f0f0ff 0%,#fff 100%);">
                            <div class="value" style="color:#6c5ffc;"><?= $summary['total_rows'] ?></div>
                            <div class="label">Total Rows</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#e8fbe8 0%,#fff 100%);">
                            <div class="value" style="color:#198754;"><?= $summary['valid_count'] ?></div>
                            <div class="label">Valid</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#fde8e8 0%,#fff 100%);">
                            <div class="value" style="color:#dc3545;"><?= $summary['error_count'] ?></div>
                            <div class="label">Errors</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#fff3e0 0%,#fff 100%);">
                            <div class="value" style="color:#fd7e14;"><?= $summary['dev_charge_count'] ?></div>
                            <div class="label">Dev Charges</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#e0f4ff 0%,#fff 100%);">
                            <div class="value" style="color:#0d6efd;"><?= $summary['emi_count'] ?></div>
                            <div class="label">EMI Payments</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background:linear-gradient(135deg,#f5f0ff 0%,#fff 100%);">
                            <div class="value" style="color:#6c5ffc; font-size:1.4rem;">৳<?= number_format($summary['total_amount']) ?></div>
                            <div class="label">Total Amount</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Error List (if any) -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-warning mb-3">
                    Found <?= count($errors) ?> rows with errors (missing data, not found, or validation failed). These will be skipped.
                </div>
                <?php if (empty($preview_data)): ?>
                    <div class="alert alert-danger mb-3">
                        No valid rows to import. Please fix the errors and try again.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-4">
                        <?= count($preview_data) ?> rows are valid and ready to import.
                    </div>
                <?php endif; ?>

                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">Skipped Rows (Errors)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                            <table class="table table-bordered table-hover mb-0" style="font-size: 0.85rem;">
                                <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                                    <tr>
                                        <th style="width: 80px;" class="text-muted fw-normal">CSV Row</th>
                                        <th class="text-muted fw-normal">Lead Phone</th>
                                        <th class="text-muted fw-normal">Project Name</th>
                                        <th class="text-muted fw-normal">Flat Name</th>
                                        <th class="text-muted fw-normal">Reason for Failure</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($errors as $err): 
                                        $phone = $err['data']['client_phone'] ?? '-';
                                        $project = $err['data']['project_name'] ?? '-';
                                        $flat = $err['data']['flat_name'] ?? '-';
                                    ?>
                                    <tr>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-success px-2 py-1">#<?= $err['row'] ?></span>
                                        </td>
                                        <td class="align-middle"><?= htmlspecialchars($phone) ?></td>
                                        <td class="align-middle fw-bold"><?= htmlspecialchars($project) ?></td>
                                        <td class="align-middle fw-bold"><?= htmlspecialchars($flat) ?></td>
                                        <td class="align-middle text-danger">
                                            <?php if (isset($err['messages']) && is_array($err['messages'])): ?>
                                                <?php if (count($err['messages']) > 1): ?>
                                                    <ul class="mb-0 ps-3">
                                                        <?php foreach($err['messages'] as $msg): ?>
                                                            <li class="py-1"><?= htmlspecialchars($msg) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="py-1"><?= htmlspecialchars($err['messages'][0]) ?></div>
                                                <?php endif; ?>
                                            <?php elseif (isset($err['message'])): ?>
                                                <div class="py-1"><?= htmlspecialchars($err['message']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Valid Rows Preview Table -->
                <?php if (!empty($preview_data)): ?>
                <h6 class="mb-3">
                    <i class="fa-light fa-table me-2"></i>
                    Valid Rows Preview (<?= count($preview_data) ?> rows ready to import)
                </h6>
                <div class="preview-container mb-4">
                    <table class="table table-hover preview-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Project</th>
                                <th>Flat</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Verification</th>
                                <th>EMI#</th>
                                <th>Resolved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $row): ?>
                            <tr class="row-valid">
                                <td><?= $row['row_num'] ?></td>
                                <td><?= htmlspecialchars($row['client_name']) ?></td>
                                <td><small><?= htmlspecialchars($row['client_phone']) ?></small></td>
                                <td><?= htmlspecialchars($row['project_name']) ?></td>
                                <td><?= htmlspecialchars($row['flat_name']) ?></td>
                                <td><strong>৳<?= number_format($row['amount']) ?></strong></td>
                                <td><small><?= date('d M Y', strtotime($row['payment_date'])) ?></small></td>
                                <td>
                                    <?php if ($row['payment_type'] === 'emi'): ?>
                                        <span class="status-badge bg-info text-white">EMI</span>
                                    <?php elseif ($row['payment_type'] === 'emi_due_settlement'): ?>
                                        <span class="status-badge bg-danger text-white">Settlement</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-warning text-dark">Dev Charge</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= ucfirst($row['payment_method']) ?></small></td>
                                <td>
                                    <?php
                                    $vClass = match($row['verification_status']) {
                                        'verified' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="status-badge <?= $vClass ?>"><?= ucfirst($row['verification_status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['emi_no']): ?>
                                        <span class="badge bg-primary">#<?= $row['emi_no'] ?></span>
                                    <?php elseif ($row['payment_type'] === 'emi'): ?>
                                        <span class="badge bg-light text-muted">Auto</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge bg-success text-white" 
                                          title="Resolved: <?= htmlspecialchars($row['resolved_client']) ?> (Booking #<?= $row['booking_id'] ?>)">
                                        <i class="fa-light fa-check me-1"></i>✓
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $base_url ?>/csv-import-form" class="btn btn-outline-secondary">
                        <i class="fa-light fa-arrow-left me-2"></i> Re-Upload CSV
                    </a>
                    <form action="<?= $base_url ?>/csv-import-execute" method="POST">
                        <button type="submit" class="btn btn-success btn-lg px-5" 
                                onclick="return confirm('Are you sure you want to import <?= count($preview_data) ?> payment records? This action cannot be undone.');">
                            <i class="fa-light fa-check-double me-2"></i> 
                            Confirm Import (<?= count($preview_data) ?> rows)
                        </button>
                    </form>
                </div>
                <?php elseif (!empty($errors)): ?>
                <!-- Only errors, no valid rows -->
                <div class="text-center mt-4">
                    <a href="<?= $base_url ?>/csv-import-form" class="btn btn-primary btn-lg px-5">
                        <i class="fa-light fa-arrow-left me-2"></i> Fix & Re-Upload CSV
                    </a>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Drag & drop support
const dropZone = document.getElementById('dropZone');
const csvFileInput = document.getElementById('csvFileInput');

if (dropZone) {
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });
    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].name.endsWith('.csv')) {
            csvFileInput.files = files;
            handleFileSelect(csvFileInput);
        }
    });
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        document.getElementById('selectedFileName').style.display = 'block';
        document.getElementById('fileNameDisplay').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('uploadBtn').disabled = false;
    }
}
</script>
