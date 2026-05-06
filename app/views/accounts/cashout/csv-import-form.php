<?php
$page_title = "Cashout CSV Bulk Import";
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
                    Cashout CSV Bulk Import
                </h3>
                <div class="d-flex gap-2">
                    <a href="<?= $base_url ?>/cashout-csv-import-template" class="btn btn-outline-primary btn-sm">
                        <i class="fa-light fa-download me-1"></i> Download Template
                    </a>
                    <a href="<?= $base_url ?>/admin-cashout-requests" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-light fa-arrow-left me-1"></i> Back to Cashouts
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
                            <p class="mb-2"><strong>Required Columns:</strong></p>
                            <ul class="mb-0" style="font-size:0.85rem;">
                                <li><code>Payment Type</code> — General, Vendor, or Contractor</li>
                                <li><code>Project Name</code> — Must match exactly the project name</li>
                                <li><code>Amount</code> — Total amount requested</li>
                                <li><code>Date</code> — Date of request</li>
                                <li><code>Status</code> — Pending, Approved, Paid, Fully Paid, etc.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Optional/Contextual Columns:</strong></p>
                            <ul class="mb-0" style="font-size:0.85rem;">
                                <li><code>Purpose / Description</code> — Detail for General/Contractor</li>
                                <li><code>Vendor Name</code> — Required for Vendor type</li>
                                <li><code>Vendor Phone</code> — Optional for better vendor matching</li>
                                <li><code>PO Number</code> — Optional. Automatically detected if left blank.</li>
                                <li><code>Invoice Number</code> — Vendor invoice</li>
                                <li><code>Operator Name</code> — Required for Contractor type</li>
                                <li><code>File Attachment</code> — Name of uploaded file (e.g., receipt.jpg)</li>
                                <li><code>Payment Method</code> — Cash, Cheque, BankTransfer</li>
                                <li><code>Paid Amount</code> — Will generate transaction records if > 0</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <form action="<?= $base_url ?>/cashout-csv-import-preview" method="POST" enctype="multipart/form-data" id="csvUploadForm">
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
                    <div class="col-md-3">
                        <div class="summary-card" style="background:linear-gradient(135deg,#f0f0ff 0%,#fff 100%);">
                            <div class="value" style="color:#6c5ffc;"><?= $summary['total_rows'] ?></div>
                            <div class="label">Total Rows</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card" style="background:linear-gradient(135deg,#e8fbe8 0%,#fff 100%);">
                            <div class="value" style="color:#198754;"><?= $summary['valid_rows'] ?></div>
                            <div class="label">Valid</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card" style="background:linear-gradient(135deg,#fde8e8 0%,#fff 100%);">
                            <div class="value" style="color:#dc3545;"><?= $summary['error_rows'] ?></div>
                            <div class="label">Errors</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card" style="background:linear-gradient(135deg,#f5f0ff 0%,#fff 100%);">
                            <div class="value" style="color:#6c5ffc; font-size:1.4rem;">৳<?= number_format($summary['total_amount']) ?></div>
                            <div class="label">Total Request Amount</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Error List (if any) -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <h6 class="alert-heading"><i class="fa-light fa-triangle-exclamation me-2"></i>Validation Errors (<?= count($errors) ?> rows)</h6>
                    <div style="max-height:200px;overflow-y:auto;">
                        <?php foreach ($errors as $err): ?>
                            <div class="mb-2 p-2 rounded" style="background:rgba(220,53,69,0.05);">
                                <strong>Row <?= $err['row'] ?>:</strong>
                                <?php if (isset($err['messages'])): ?>
                                    <?= implode(', ', $err['messages']) ?>
                                <?php elseif (isset($err['message'])): ?>
                                    <?= htmlspecialchars($err['message']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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
                                <th>Type</th>
                                <th>Project</th>
                                <th>Entity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; foreach ($preview_data as $row): ?>
                            <tr class="row-valid">
                                <td><?= $counter++ ?></td>
                                <td>
                                    <?php if ($row['payment_type'] === 'general'): ?>
                                        <span class="status-badge bg-primary text-white">General</span>
                                    <?php elseif ($row['payment_type'] === 'vendor'): ?>
                                        <span class="status-badge bg-info text-white">Vendor</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-warning text-dark">Contractor</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['project_name']) ?></td>
                                <td>
                                    <?php if ($row['payment_type'] === 'vendor'): ?>
                                        <?= htmlspecialchars($row['vendor_name']) ?>
                                    <?php elseif ($row['payment_type'] === 'contractor'): ?>
                                        <?= htmlspecialchars($row['operator_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>৳<?= number_format($row['amount']) ?></strong></td>
                                <td>
                                    <span class="status-badge bg-secondary"><?= ucfirst($row['status_raw']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['paid_amount'] > 0): ?>
                                        <strong>৳<?= number_format($row['paid_amount']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['payment_method']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= $base_url ?>/cashout-csv-import-form" class="btn btn-outline-secondary">
                        <i class="fa-light fa-arrow-left me-2"></i> Re-Upload CSV
                    </a>
                    <form action="<?= $base_url ?>/cashout-csv-import-execute" method="POST">
                        <button type="submit" class="btn btn-success btn-lg px-5" 
                                onclick="return confirm('Are you sure you want to import <?= count($preview_data) ?> records? This action cannot be undone.');">
                            <i class="fa-light fa-check-double me-2"></i> 
                            Confirm Import (<?= count($preview_data) ?> rows)
                        </button>
                    </form>
                </div>
                <?php elseif (!empty($errors)): ?>
                <!-- Only errors, no valid rows -->
                <div class="text-center mt-4">
                    <a href="<?= $base_url ?>/cashout-csv-import-form" class="btn btn-primary btn-lg px-5">
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
