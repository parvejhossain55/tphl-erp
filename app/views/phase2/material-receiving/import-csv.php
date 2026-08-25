<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Bulk Material Receiving & Payment Import (CSV)';
?>
<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-orders">Purchase Orders</a></li>
                <li class="breadcrumb-item active">CSV Import</li>
            </ol>
        </nav>
    </div>
    <div class="mb-4">
        <a href="<?= $base_url ?>/con-purchase-orders" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to PO List
        </a>
    </div>
</div>

<div class="row">

    <!-- ══ LEFT: Upload Form ════════════════════════════════════════════════ -->
    <div class="col-lg-7">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-cloud-upload me-2 text-primary"></i>Upload CSV File
                </h6>
                <a href="<?= $base_url ?>/con-material-receiving-import-template"
                   class="btn btn-outline-success btn-sm fw-bold">
                    <i class="bi bi-download me-1"></i> Download Template
                </a>
            </div>
            <div class="card-body">
                <form action="<?= $base_url ?>/con-material-receiving-import-process"
                      method="POST" enctype="multipart/form-data" id="importPoCsvForm">

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Select CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="csv_file" id="importPoCsvFile" class="form-control" accept=".csv" required>
                        <div class="form-text">UTF-8 encoded CSV. Header row required.</div>
                    </div>

                    <!-- Info box -->
                    <div class="alert alert-primary py-2 mb-3" style="font-size:13px">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>Complete Workflow:</strong> One CSV upload creates and links the entire lifecycle:
                        PR → PO → Material Receiving → Stocks → Vendor Payment Request → Financial Transaction.
                        <br>
                        <span class="text-muted">Same project, creator, vendor, payment amount, and reference = 1 PO & Payment Request batch.</span>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-upload me-1"></i> Import Now
                    </button>
                </form>
            </div>
        </div>

        <!-- CSV Column Guide -->
        <div class="card custom-card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-table me-2 text-success"></i>Required CSV Columns
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size:12px">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:30%">Column Name</th>
                                <th style="width:30%">Example Value</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>project_name</code></td>
                                <td>Mirpur Housing</td>
                                <td>Exact project name in DB (Case-insensitive)</td>
                            </tr>
                            <tr class="table-light text-muted">
                                <td><code>purchase_request_email</code></td>
                                <td>site.engineer@tphl.com</td>
                                <td><strong>Optional.</strong> Requester user email. If empty, defaults to superadmin.</td>
                            </tr>
                            <tr class="table-light text-muted">
                                <td><code>pm_approved_email</code></td>
                                <td>pm@tphl.com</td>
                                <td><strong>Optional.</strong> PM approver email. If empty, defaults to superadmin.</td>
                            </tr>
                            <tr class="table-light text-muted">
                                <td><code>management_approved_email</code></td>
                                <td>management@tphl.com</td>
                                <td><strong>Optional.</strong> Management approver email. If empty, defaults to superadmin.</td>
                            </tr>
                            <tr class="table-light text-muted">
                                <td><code>accounts_paid_email</code></td>
                                <td>accounts@tphl.com</td>
                                <td><strong>Optional.</strong> Accounts payer email. Required only if payment_amount > 0. Defaults to superadmin if empty.</td>
                            </tr>
                            <tr>
                                <td><code>receiving_date</code></td>
                                <td>2026-05-07</td>
                                <td>Format: YYYY-MM-DD</td>
                            </tr>
                            <tr class="table-warning">
                                <td><code>warehouse_name</code></td>
                                <td>Main Warehouse</td>
                                <td>Warehouse name (Project-scoped)</td>
                            </tr>
                            <tr class="table-info">
                                <td><code>material_name</code></td>
                                <td>Rod 10mm</td>
                                <td><span class="badge bg-success" style="font-size:9px">Auto-create</span> Checks case-insensitive and space-insensitive matching. Creates material if missing.</td>
                            </tr>
                            <tr class="table-info">
                                <td><code>material_category</code></td>
                                <td>Civil Materials</td>
                                <td><strong>Optional.</strong> Matches case-insensitive and space-insensitive. Auto-creates category if missing and links to material.</td>
                            </tr>
                            <tr class="table-info">
                                <td><code>unit</code></td>
                                <td>kg / bag / pcs</td>
                                <td>Defaults to 'pcs' if empty</td>
                            </tr>
                            <tr>
                                <td><code>qty</code></td>
                                <td>500</td>
                                <td>Decimal numbers allowed</td>
                            </tr>
                            <tr>
                                <td><code>unit_price</code></td>
                                <td>85.50</td>
                                <td>Per unit price in ৳</td>
                            </tr>
                            <tr>
                                <td><code>vendor_name</code></td>
                                <td>ABC Suppliers Ltd</td>
                                <td>Vendor name in DB</td>
                            </tr>
                            <tr>
                                <td><code>vendor_phone</code></td>
                                <td>01712345678</td>
                                <td>Vendor's phone/mobile number (Validated)</td>
                            </tr>
                            <tr>
                                <td><code>remarks</code></td>
                                <td>Remarks for this purchase order</td>
                                <td><strong>Optional.</strong> General remark/note for this purchase order (e.g. detailed material list for category others).</td>
                            </tr>
                            <tr class="table-success">
                                <td><code>payment_amount</code></td>
                                <td>600.00</td>
                                <td>Amount paid to vendor. Can be 0 or empty for due.</td>
                            </tr>
                            <tr class="table-success">
                                <td><code>payment_method</code></td>
                                <td>BankTransfer</td>
                                <td>Cash / Cheque / BankTransfer / MobileWallet / Other</td>
                            </tr>
                            <tr class="table-success">
                                <td><code>payment_date</code></td>
                                <td>2026-05-07</td>
                                <td>Defaults to today if empty</td>
                            </tr>
                            <tr class="table-success">
                                <td><code>payment_reference</code></td>
                                <td>TXN987654321</td>
                                <td>Transaction ref / check number</td>
                            </tr>
                            <tr class="table-success">
                                <td><code>payment_attachment</code></td>
                                <td>receipt.pdf</td>
                                <td>Filename of the payment voucher/invoice receipt</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT: Step Flow ═════════════════════════════════════════════════ -->
    <div class="col-lg-5">
        <div class="card custom-card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2 text-warning"></i>
                    Import Sequence (10 Steps)
                </h6>
            </div>
            <div class="card-body py-3 px-3">

                <?php
                $steps = [
                    ['num'=>0, 'color'=>'dark',      'table'=>'con_materials (Step 0)',         'note'=>'Auto-save: material_name + unit if not in DB'],
                    ['num'=>1, 'color'=>'primary',   'table'=>'purchase_requests',              'note'=>'status = PO Issued, pm_approved set'],
                    ['num'=>2, 'color'=>'primary',   'table'=>'purchase_request_items',         'note'=>'creates requested items'],
                    ['num'=>3, 'color'=>'success',   'table'=>'purchase_orders',                'note'=>'status = Fully Received, PM + SE approved'],
                    ['num'=>4, 'color'=>'success',   'table'=>'purchase_order_items',           'note'=>'tracks vendor and unit_price'],
                    ['num'=>5, 'color'=>'warning',   'table'=>'material_receiving',             'note'=>'overall_status = Accepted'],
                    ['num'=>6, 'color'=>'warning',   'table'=>'material_receiving_items',       'note'=>'site_engineer_checked = 1, qc_checked = 1'],
                    ['num'=>7, 'color'=>'danger',    'table'=>'con_stocks',                     'note'=>'UPSERT — current_quantity += qty'],
                    ['num'=>8, 'color'=>'secondary', 'table'=>'con_stock_movements',            'note'=>'type = IN, ref_type = material_receiving'],
                    ['num'=>9, 'color'=>'info',      'table'=>'vendor_payment_requests (NEW)',  'note'=>'Creates approved payment request (invoice amount)'],
                    ['num'=>10,'color'=>'purple',    'table'=>'con_financial_transactions (NEW)','note'=>'Completed vendor payment transaction (if paid)'],
                ];
                ?>

                <div style="position:relative">
                    <!-- vertical line -->
                    <div style="position:absolute;left:19px;top:28px;bottom:28px;width:2px;
                                background:linear-gradient(180deg,#0d6efd,#198754,#ffc107,#dc3545,#0dcaf0,#6f42c1);
                                opacity:.3;z-index:0"></div>

                    <?php foreach ($steps as $s): ?>
                    <div class="d-flex align-items-start gap-3 mb-3" style="position:relative;z-index:1">
                        <div class="flex-shrink-0">
                            <span class="badge bg-<?= $s['color'] ?> rounded-circle"
                                  style="width:38px;height:38px;display:flex;align-items:center;
                                         justify-content:center;font-size:14px;font-weight:800">
                                  <?= $s['num'] ?>
                            </span>
                        </div>
                        <div class="flex-grow-1 pt-1">
                            <div style="font-size:13px;font-weight:700;color:var(--text-heading,#1e293b)">
                                <?= $s['table'] ?>
                            </div>
                            <div style="font-size:11px;color:#6b7280"><?= $s['note'] ?></div>
                        </div>
                        <div class="flex-shrink-0 pt-1">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:16px"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-secondary py-2 mt-3 mb-0" style="font-size:12px">
                    <i class="bi bi-layers me-1"></i>
                    <strong>Grouping rule:</strong> Rows with same
                    <code>project + purchase_request_email + vendor + phone + payment amount + reference</code>
                    → 1 PO, 1 Material Receiving, and 1 Vendor Payment batch.
                </div>
            </div>
        </div>

        <!-- Sample CSV preview -->
        <div class="card custom-card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-file-earmark-text me-2 text-info"></i>Sample CSV
                </h6>
            </div>
            <div class="card-body p-0">
                <pre class="mb-0 p-3" style="font-size:10px;overflow-x:auto;background:#f8f9fa;border-radius:0 0 8px 8px"><code>project_name,purchase_request_email,pm_approved_email,management_approved_email,accounts_paid_email,receiving_date,warehouse_name,material_name,material_category,unit,qty,unit_price,vendor_name,vendor_phone,remarks,payment_amount,payment_method,payment_date,payment_reference,payment_attachment
Mirpur Housing,site.engineer@tphl.com,pm@tphl.com,management@tphl.com,accounts@tphl.com,2026-05-07,Main Warehouse,Rod 10mm,Civil Materials,kg,500,85.50,ABC Suppliers Ltd,01712345678,Remarks for PO,600.00,BankTransfer,2026-05-07,TXN987654321,receipt.pdf
Mirpur Housing,site.engineer@tphl.com,pm@tphl.com,management@tphl.com,accounts@tphl.com,2026-05-07,Main Warehouse,Cement (50kg),Civil Materials,bag,200,420.00,ABC Suppliers Ltd,01712345678,Remarks for PO,600.00,BankTransfer,2026-05-07,TXN987654321,receipt.pdf</code></pre>
            </div>
        </div>
    </div>

</div>

<!-- ══ CONFIRMATION MODAL ════════════════════════════════════════════════ -->
<div class="modal fade" id="importPoConfirmModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-check fs-3 me-2 text-success"></i>
                    <div>
                        <h5 class="modal-title fw-bold m-0 text-white">Import Verification & Confirmation</h5>
                        <small class="text-white-50">Validating CSV rows against the active database records</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div id="confirmPoAlertContainer">
                <div class="alert alert-info d-flex align-items-center m-0 border-0 rounded-0" id="confirmPoAlert" role="alert">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div>Checking database records. Please wait...</div>
                </div>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0" style="font-size:12.5px">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th class="ps-3" style="width: 50px">Row</th>
                                <th>Project Name</th>
                                <th>Requester (PR Email)</th>
                                <th>Vendor (Name & Phone)</th>
                                <th>Material Details</th>
                                <th>Payment Info</th>
                                <th>Warehouse</th>
                                <th>Remarks</th>
                                <th>Verification Status</th>
                            </tr>
                        </thead>
                        <tbody id="confirmPoTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancel & Fix CSV
                </button>
                <button type="button" class="btn btn-success px-5 py-2 fw-bold" id="submitPoImportConfirmed" disabled>
                    <i class="bi bi-check-circle me-1"></i> Confirm & Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const importForm = document.getElementById('importPoCsvForm');
    const fileInput = document.getElementById('importPoCsvFile');
    const confirmModalEl = document.getElementById('importPoConfirmModal');
    let confirmModal = null;
    let isConfirmed = false;

    if (importForm && fileInput) {
        importForm.addEventListener('submit', function (e) {
            if (isConfirmed) {
                return; // Let form submit
            }

            e.preventDefault();

            const file = fileInput.files[0];
            if (!file) {
                alert("Please select a CSV file first.");
                return;
            }

            // Create FormData to send file to server
            const formData = new FormData();
            formData.append('csv_file', file);

            // Show confirmation modal with loading spinner
            const tbody = document.getElementById('confirmPoTableBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Checking database records...</div></td></tr>';

            const alertEl = document.getElementById('confirmPoAlert');
            alertEl.className = 'alert alert-info d-block';
            alertEl.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>Verifying CSV data against database...';

            const submitBtn = document.getElementById('submitPoImportConfirmed');
            submitBtn.disabled = true;

            if (!confirmModal) {
                confirmModal = new bootstrap.Modal(confirmModalEl);
            }
            confirmModal.show();

            fetch('<?= $GLOBALS['base_url'] ?>/con-material-receiving-import-validate', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger fw-semibold">${escapeHTML(res.message || 'Validation failed')}</td></tr>`;
                    alertEl.className = 'alert alert-danger d-block';
                    alertEl.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><strong>Error:</strong> ${escapeHTML(res.message || 'Failed to validate CSV')}`;
                    submitBtn.disabled = true;
                    return;
                }

                tbody.innerHTML = '';
                let hasErrors = false;
                let rowCount = 0;

                res.rows.forEach((rowResult, idx) => {
                    rowCount++;
                    const rowData = rowResult.data;
                    const error = rowResult.error;
                    const valid = rowResult.valid;

                    if (!valid) {
                        hasErrors = true;
                    }

                    const statusHtml = !valid 
                        ? `<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Error: ${escapeHTML(error)}</span>`
                        : '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Valid</span>';

                    const tr = document.createElement('tr');
                    if (!valid) {
                        tr.style.backgroundColor = '#fff5f5';
                    }

                    // Format payment info display
                    const payAmt = parseFloat(rowData.payment_amount || 0);
                    let paymentInfoHtml = '<span class="text-muted">Due (Unpaid)</span>';
                    if (payAmt > 0) {
                        paymentInfoHtml = `<div><strong>Tk. ${escapeHTML(rowData.payment_amount)}</strong></div>
                                           <div class="text-muted" style="font-size:10px">${escapeHTML(rowData.payment_method)} (${escapeHTML(rowData.payment_reference || 'No Ref')})</div>`;
                    }

                    // Format material details
                    const catDisplay = rowData.material_category ? ` <span class="badge bg-secondary-subtle text-secondary" style="font-size:9px">${escapeHTML(rowData.material_category)}</span>` : '';
                    const matDetails = `<div><strong>${escapeHTML(rowData.material_name)}</strong>${catDisplay}</div>
                                        <div class="text-muted" style="font-size:10px">${escapeHTML(rowData.qty)} ${escapeHTML(rowData.unit)} @ Tk. ${escapeHTML(rowData.unit_price)}</div>`;

                    // Format vendor details
                    const vendorDetails = `<div><strong>${escapeHTML(rowData.vendor_name)}</strong></div>
                                           <div class="text-muted" style="font-size:10px">${escapeHTML(rowData.vendor_phone || 'No phone')}</div>`;

                    tr.innerHTML = `
                        <td class="ps-3 text-muted fw-bold">${idx + 1}</td>
                        <td><strong>${escapeHTML(rowData.project_name)}</strong></td>
                        <td>${escapeHTML(rowData.purchase_request_email || 'System Default')}</td>
                        <td>${vendorDetails}</td>
                        <td>${matDetails}</td>
                        <td>${paymentInfoHtml}</td>
                        <td>${escapeHTML(rowData.warehouse_name)}</td>
                        <td>${escapeHTML(rowData.remarks || '—')}</td>
                        <td>${statusHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });

                if (hasErrors) {
                    alertEl.className = 'alert alert-danger d-block';
                    alertEl.innerHTML = `<i class="bi bi-exclamation-octagon-fill me-2"></i><strong>Validation Failed:</strong> The CSV contains invalid rows or mismatching database records. Please correct the CSV and try again.`;
                    submitBtn.disabled = true;
                } else {
                    alertEl.className = 'alert alert-success d-block';
                    let alertMsg = `<i class="bi bi-check-circle-fill me-2"></i><strong>Ready to Import:</strong> All ${rowCount} rows are valid and database relations matched. Click Confirm to run the import.`;
                    if (res.has_today_upload) {
                        alertMsg += `<br><br><span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Warning: Material receiving was already uploaded today for one or more projects in this file!</span>`;
                    }
                    alertEl.innerHTML = alertMsg;
                    submitBtn.disabled = false;
                    window.hasTodayUpload = res.has_today_upload;
                }
            })
            .catch(err => {
                console.error("AJAX validation error:", err);
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger fw-semibold">Error verifying CSV file with the server.</td></tr>`;
                alertEl.className = 'alert alert-danger d-block';
                alertEl.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><strong>Error:</strong> Failed to connect to verification server.`;
                submitBtn.disabled = true;
            });
        });

        document.getElementById('submitPoImportConfirmed').addEventListener('click', function () {
            if (window.hasTodayUpload) {
                if (!confirm("You have already uploaded a CSV for this project today.\n\nAre you sure you want to upload again?")) {
                    return;
                }
            }
            isConfirmed = true;
            importForm.submit();
        });
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;')
                          .replace(/</g, '&lt;')
                          .replace(/>/g, '&gt;')
                          .replace(/"/g, '&quot;')
                          .replace(/'/g, '&#039;');
    }
});
</script>
