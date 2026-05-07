<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Bulk Material Receiving Import (CSV)';
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

<div class="row g-3">

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
                      method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Select CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">UTF-8 encoded CSV. Header row required.</div>
                    </div>

                    <!-- Info box -->
                    <div class="alert alert-primary py-2 mb-3" style="font-size:13px">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>One CSV group</strong> (same project + email) creates all 8 records automatically:
                        PR → PO → Receiving → Stock update. <strong>CST বাদ।</strong>
                        <br>
                        <span class="text-muted">Multiple rows with same project+email = 1 PR / 1 PO / 1 Receiving batch.</span>
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
                <table class="table table-sm table-bordered mb-0" style="font-size:12.5px">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:35%">Column Name</th>
                            <th style="width:30%">Example Value</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>project_name</code></td>
                            <td>Mirpur Housing</td>
                            <td>Exact project name in DB</td>
                        </tr>
                        <tr>
                            <td><code>created_by_email</code></td>
                            <td>john@tphl.com</td>
                            <td>User's email (requester)</td>
                        </tr>
                        <tr>
                            <td><code>approved_by_email</code></td>
                            <td>pm@tphl.com</td>
                            <td>PM / Manager email</td>
                        </tr>
                        <tr>
                            <td><code>receiving_date</code></td>
                            <td>2026-05-07</td>
                            <td>Format: YYYY-MM-DD</td>
                        </tr>
                        <tr class="table-warning">
                            <td><code>warehouse_name</code></td>
                            <td>Ashulia Warehouse</td>
                            <td><strong>Project-scoped</strong> — name + project must match</td>
                        </tr>
                        <tr class="table-info">
                            <td><code>material_name</code></td>
                            <td>Rod 10mm</td>
                            <td><span class="badge bg-success" style="font-size:10px">Auto-create</span> না থাকলে DB তে save হবে</td>
                        </tr>
                        <tr class="table-info">
                            <td><code>unit</code></td>
                            <td>kg / bag / pcs</td>
                            <td>Material unit — material এর সাথে save হবে</td>
                        </tr>
                        <tr>
                            <td><code>qty</code></td>
                            <td>500</td>
                            <td>Decimal allowed (e.g. 12.5)</td>
                        </tr>
                        <tr>
                            <td><code>unit_price</code></td>
                            <td>85.50</td>
                            <td>Per unit price in ৳</td>
                        </tr>
                        <tr>
                            <td><code>vendor_name</code></td>
                            <td>ABC Suppliers Ltd</td>
                            <td>Exact vendor name in DB</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT: Step Flow ═════════════════════════════════════════════════ -->
    <div class="col-lg-5">
        <div class="card custom-card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2 text-warning"></i>
                    Import Sequence (8 Steps)
                </h6>
            </div>
            <div class="card-body py-3 px-3">

                <?php
                $steps = [
                    ['num'=>0, 'color'=>'dark',      'table'=>'con_materials (Step 0)',    'note'=>'Auto-save: material_name + unit — না থাকলে DB তে insert'],
                    ['num'=>1, 'color'=>'primary',   'table'=>'purchase_requests',         'note'=>'status = PO Issued, approved_by_id set'],
                    ['num'=>2, 'color'=>'primary',   'table'=>'purchase_request_items',    'note'=>'one row per material'],
                    ['num'=>3, 'color'=>'success',   'table'=>'purchase_orders',           'note'=>'status = Fully Received, PM + SE approved'],
                    ['num'=>4, 'color'=>'success',   'table'=>'purchase_order_items',      'note'=>'material + vendor + unit_price (total_price auto)'],
                    ['num'=>5, 'color'=>'warning',   'table'=>'material_receiving',        'note'=>'received_status = Full, overall_status = Accepted'],
                    ['num'=>6, 'color'=>'warning',   'table'=>'material_receiving_items',  'note'=>'site_engineer_checked = 1, qc_checked = 1'],
                    ['num'=>7, 'color'=>'danger',    'table'=>'con_stocks',                'note'=>'UPSERT — current_quantity += qty'],
                    ['num'=>8, 'color'=>'secondary', 'table'=>'con_stock_movements',       'note'=>'type = IN, ref_type = material_receiving'],
                ];
                ?>

                <div style="position:relative">
                    <!-- vertical line -->
                    <div style="position:absolute;left:19px;top:28px;bottom:28px;width:2px;
                                background:linear-gradient(180deg,#0d6efd,#198754,#ffc107,#dc3545,#6c757d);
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

                <!-- Grouping note -->
                <div class="alert alert-secondary py-2 mt-3 mb-0" style="font-size:12px">
                    <i class="bi bi-layers me-1"></i>
                    <strong>Grouping rule:</strong> Rows with same
                    <code>project_name + created_by_email</code>
                    → 1 PR + 1 PO + 1 Receiving batch.
                    Multiple materials = multiple item rows.
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
                <pre class="mb-0 p-3" style="font-size:10px;overflow-x:auto;background:#f8f9fa;border-radius:0 0 8px 8px"><code>project_name,created_by_email,approved_by_email,receiving_date,warehouse_name,material_name,unit,qty,unit_price,vendor_name
Mirpur Housing,john@tphl.com,pm@tphl.com,2026-05-07,Ashulia Warehouse,Rod 10mm,kg,500,85.50,ABC Suppliers Ltd
Mirpur Housing,john@tphl.com,pm@tphl.com,2026-05-07,Ashulia Warehouse,Cement (50kg),bag,200,420.00,XYZ Suppliers
Gulshan Tower,jane@tphl.com,pm@tphl.com,2026-05-07,Gulshan Store,Brick,pcs,1000,12.00,DEF Traders</code></pre>
                <div class="px-3 pb-2 pt-1" style="font-size:11px;color:#6b7280">
                    ↑ Row 1–2: 1 PR + 1 PO + 1 Receiving (2 materials) for Mirpur Housing<br>
                    ↑ Row 3: Separate PR + PO + Receiving for Gulshan Tower
                </div>
            </div>
        </div>
    </div>

</div>
