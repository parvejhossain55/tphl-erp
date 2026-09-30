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
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Total Lands Acquired</div>
                <h3 class="mb-0 mt-1"><?= number_format((int)($stats['total_lands'] ?? 0)) ?></h3>
                <div class="mt-2 text-muted small">
                    Total Area: <strong><?= number_format((float)($stats['total_area'] ?? 0), 2) ?> Dec/Katha</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Total Land Valuation (Agreed)</div>
                <h3 class="mb-0 mt-1 text-primary">BDT <?= number_format((float)($stats['total_value'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Selected/Pending: <strong><?= number_format((int)($stats['selected_count'] ?? 0)) ?></strong> | Bayana: <strong><?= number_format((int)($stats['bayana_count'] ?? 0)) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Paid by Accounts</div>
                <h3 class="mb-0 mt-1 text-success">BDT <?= number_format((float)($stats['total_paid_land'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Extra Costs Paid: <strong>BDT <?= number_format((float)($stats['total_paid_extra'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Remaining Land Price Due</div>
                <h3 class="mb-0 mt-1 text-danger">BDT <?= number_format((float)($stats['total_land_due'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Pending In Pipeline: <strong class="text-warning">BDT <?= number_format(((float)($stats['pending_mgmt_amount'] ?? 0) + (float)($stats['pending_accounts_amount'] ?? 0)), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">All Land Acquisitions</h5>
                <form method="GET" action="<?= $base_url ?>/con-land-acquisitions" class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small text-muted text-nowrap">Filter by Project:</label>
                    <select name="project_id" class="form-select form-select-sm select2-filter-project" style="min-width: 240px;">
                        <option value="">All Projects (<?= count($projects) ?>)</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($selectedProjectId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> <?= !empty($p['project_status']) ? '('.$p['project_status'].')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($selectedProjectId)): ?>
                        <a href="<?= $base_url ?>/con-land-acquisitions" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project / Deal Name</th>
                                <th>Landowner & Receiver</th>
                                <th>Broker & POA</th>
                                <th>Area & Rate</th>
                                <th>Valuation & Paid Status</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($acquisitions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No land acquisitions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($acquisitions as $index => $row): ?>
                                    <?php 
                                        $val = (float)($row['total_amount'] ?? 0);
                                        $paidLand = (float)($row['paid_land_amount'] ?? 0);
                                        $dueLand = max(0, $val - $paidLand);
                                        $pctPaid = ($val > 0) ? min(100, round(($paidLand / $val) * 100, 1)) : 0;
                                        $paidExtra = (float)($row['paid_extra_cost'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-primary fs-6">
                                                <?= htmlspecialchars($row['effective_project_name']) ?>
                                            </div>
                                            <?php if (!empty($row['project_id']) && !empty($row['project_name']) && $row['project_name'] !== $row['land_project_name']): ?>
                                                <small class="badge bg-light text-secondary border">Linked: <?= htmlspecialchars($row['project_name']) ?></small>
                                            <?php elseif (empty($row['project_id'])): ?>
                                                <small class="badge bg-warning-subtle text-warning border border-warning-subtle">Tentative Deal</small>
                                            <?php endif; ?>
                                            <?php if (!empty($row['location'])): ?>
                                                <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($row['location']) ?>">
                                                    <i class="fa-light fa-location-dot me-1"></i><?= htmlspecialchars($row['location']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><i class="fa-light fa-user me-1 text-muted"></i><?= htmlspecialchars($row['land_owner_name']) ?></div>
                                            <?php if (!empty($row['land_owner_phone'])): ?>
                                                <small class="text-muted d-block"><i class="fa-light fa-phone me-1"></i><?= htmlspecialchars($row['land_owner_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($row['land_receiver_name'])): ?>
                                                <small class="text-success d-block mt-1">
                                                    <i class="fa-light fa-hand-holding-hand me-1"></i>Receiver: <strong><?= htmlspecialchars($row['land_receiver_name']) ?></strong>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 200px;">
                                            <?php if (!empty($row['media_broker_info'])): ?>
                                                <div class="small text-truncate" title="<?= htmlspecialchars($row['media_broker_info']) ?>">
                                                    <i class="fa-light fa-user-tie me-1 text-primary"></i><strong>Broker:</strong> <?= htmlspecialchars($row['media_broker_info']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['power_of_attorney'])): ?>
                                                <div class="small text-muted text-truncate mt-1" title="<?= htmlspecialchars($row['power_of_attorney']) ?>">
                                                    <i class="fa-light fa-file-contract me-1 text-info"></i><strong>POA:</strong> <?= htmlspecialchars($row['power_of_attorney']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= number_format((float)($row['total_area'] ?? 0), 2) ?> <?= htmlspecialchars($row['area_unit']) ?></div>
                                            <small class="text-muted">@ BDT <?= number_format((float)($row['price_per_unit'] ?? 0), 2) ?></small>
                                        </td>
                                        <td style="min-width: 220px;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted">Valuation:</span>
                                                <strong class="text-dark">BDT <?= number_format($val, 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted">Paid by Acc:</span>
                                                <span class="badge bg-success-subtle text-success fw-bold">BDT <?= number_format($paidLand, 2) ?> (<?= $pctPaid ?>%)</span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pctPaid ?>%;" aria-valuenow="<?= $pctPaid ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="small text-muted">Due Balance:</span>
                                                <span class="fw-bold <?= $dueLand > 0 ? 'text-danger' : 'text-success' ?>">BDT <?= number_format($dueLand, 2) ?></span>
                                            </div>
                                            <?php if ($paidExtra > 0): ?>
                                                <div class="small text-info mt-1">
                                                    <i class="fa-light fa-plus-circle me-1"></i>Extra Cost Paid: <strong>BDT <?= number_format($paidExtra, 2) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['pending_mgmt_amount']) && $row['pending_mgmt_amount'] > 0): ?>
                                                <div class="small text-warning mt-1" style="font-size:11px;">
                                                    <i class="fa-light fa-hourglass-half me-1"></i>Pending Mgmt: <strong>BDT <?= number_format((float)$row['pending_mgmt_amount'], 2) ?></strong> (<?= (int)$row['pending_mgmt_count'] ?>)
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['pending_accounts_amount']) && $row['pending_accounts_amount'] > 0): ?>
                                                <div class="small text-info mt-1" style="font-size:11px;">
                                                    <i class="fa-light fa-wallet me-1"></i>Ready to Pay: <strong>BDT <?= number_format((float)$row['pending_accounts_amount'], 2) ?></strong> (<?= (int)$row['pending_accounts_count'] ?>)
                                                </div>
                                            <?php endif; ?>
                                        </td>
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
                                            <div class="mt-1">
                                                <?php if (!empty($row['is_bulk_import'])): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10px;" title="Uploaded via Bulk CSV Import">
                                                        <i class="fa-light fa-file-csv me-1 text-primary"></i>Bulk CSV Uploaded
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;" title="Created manually in ERP">
                                                        <i class="fa-light fa-keyboard me-1"></i>ERP Manual
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= $base_url ?>/con-land-requisitions?land_acquisition_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success" title="View Requisitions for this Land">
                                                    <i class="fa-light fa-receipt me-1"></i>Req (<?= (int)$row['req_count'] ?>)
                                                </a>
                                                <a href="<?= $base_url ?>/con-land-acquisitions/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Manage / Edit Deal">
                                                    <i class="fa-light fa-pen-to-square"></i>
                                                </a>
                                                <?php if ($roleId === 1 || $roleId === 3): ?>
                                                <form action="<?= $base_url ?>/con-land-acquisitions/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this land acquisition portfolio? This will delete all related bayana payments and registrations.');" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
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
                <h5 class="modal-title"><i class="fa-light fa-landmark me-2"></i>Record Land Acquisition</h5>
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
                                    <!-- Tentative Land / Project Name -->
                                    <div class="col-md-7">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Land / Project Name (Tentative / Final) <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="land_project_name" id="modal_land_project_name" required placeholder="e.g. Project No 60 (New)">
                                            </div>
                                            <small class="text-muted" style="font-size:11px;">If the official project name isn't final, enter tentative name here.</small>
                                        </div>
                                    </div>

                                    <!-- Link to Project (Optional) -->
                                    <div class="col-md-5">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Link to Existing Project <span class="text-muted">(Optional)</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control select2-project-modal" name="project_id" id="modal_project_id" data-placeholder="Select project...">
                                                    <option value="">-- Not Finalized / Tentative Deal --</option>
                                                    <?php foreach ($projects as $p): ?>
                                                        <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">
                                                            <?= htmlspecialchars($p['name']) ?> <?= !empty($p['project_status']) ? '('.$p['project_status'].')' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <small class="text-muted" style="font-size:11px;">You can link or update this anytime later.</small>
                                        </div>
                                    </div>

                                    <!-- Landowner Name & Phone -->
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
                                                <input type="text" class="form-control" name="land_owner_phone" placeholder="Landowner phone number">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Land Receiver Name & Location -->
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Land Receiver Name (কোম্পানির পক্ষে গ্রহণকারী)</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="land_receiver_name" placeholder="Receiver full name (Company / Officer)">
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

                                    <!-- Media / Broker Info & POA -->
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Media / Broker Info (মিডিয়া) </label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="media_broker_info" placeholder="Broker/Media name, phone & commission notes">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Power of Attorney (POA) Details (পাওয়ার অব অ্যাটর্নি তথ্য)</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="power_of_attorney" rows="1" placeholder="POA holder name, deed number, registration date etc."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Area, Unit, Price per Unit -->
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Total Area <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="number" step="0.0001" class="form-control" name="total_area" id="modal_total_area" required placeholder="e.g. 10.0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Area Unit <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="area_unit" id="modal_area_unit" required>
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
                                                <input type="number" step="0.01" class="form-control" name="price_per_unit" id="modal_price_per_unit" required placeholder="Price per unit">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Calculated Land Valuation Card -->
                                    <div class="col-12">
                                        <div class="alert alert-success-subtle border border-success-subtle p-3 rounded-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-bold text-success">Total Land Agreed Valuation:</span>
                                                <div class="small text-muted">This becomes the maximum budget for Bayana and Registration fund requisitions.</div>
                                            </div>
                                            <div class="fs-5 fw-bold text-success" id="modal_live_total_valuation">
                                                BDT 0.00
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="col-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Remarks</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="remarks" rows="2" placeholder="Agreements, payment installment conditions, terms etc."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary px-4" type="submit"><i class="fa-light fa-check me-1"></i> Save Land Acquisition</button>
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
                        <li><strong>Required Columns:</strong> Either <code>project_name</code> or <code>land_project_name</code> (for tentative/unfinalized projects), <code>land_owner_name</code>, <code>total_area</code>, <code>price_per_unit</code>, and <code>land_dept_email</code> are required. All other columns are optional.</li>
                        <li><strong>Project Name & Tentative Projects:</strong> If <code>project_name</code> exists in TPHL-ERP, it will be automatically linked. If the project name is tentative or not yet created in the ERP projects table, provide it in <code>land_project_name</code>.</li>
                        <li><strong>New Supported Columns:</strong> You can include <code>land_project_name</code>, <code>land_receiver_name</code>, <code>media_broker_info</code>, and <code>power_of_attorney</code> directly in the CSV.</li>
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

<?php
$page_script = "
$(document).ready(function() {
    if (typeof $.fn.select2 !== 'undefined') {
        // Searchable filter
        $('.select2-filter-project').select2({
            width: '260px',
            placeholder: 'Search Project...'
        }).on('select2:select', function() {
            $(this).closest('form').submit();
        });

        // Searchable dropdown inside #addLandModal
        $('#addLandModal').on('shown.bs.modal', function() {
            $(this).find('.select2-project-modal').select2({
                dropdownParent: $('#addLandModal'),
                width: '100%',
                placeholder: 'Choose existing project or leave tentative...'
            }).on('select2:select', function(e) {
                var selectedText = $(this).find('option:selected').data('name');
                if (selectedText && !$('#modal_land_project_name').val()) {
                    $('#modal_land_project_name').val(selectedText);
                }
            });
        });
    }

    // Live valuation calculator in #addLandModal
    function updateLiveValuation() {
        var area = parseFloat($('#modal_total_area').val()) || 0;
        var price = parseFloat($('#modal_price_per_unit').val()) || 0;
        var total = area * price;
        $('#modal_live_total_valuation').text('BDT ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    $('#modal_total_area, #modal_price_per_unit').on('input change', updateLiveValuation);
});

";
?>
