<?php
$conditionBadges = [
    'new' => 'primary', 'ok' => 'success', 'need_repair' => 'warning',
    'repairing' => 'info', 'repaired' => 'teal', 'damaged' => 'danger', 'disposed' => 'dark'
];
$statusBadges = [
    'in_use' => 'success', 'in_store' => 'info', 'transferred' => 'warning',
    'disposed' => 'dark', 'maintenance' => 'orange'
];
$actionIcons = [
    'purchased' => 'fa-cart-shopping text-primary', 'assigned' => 'fa-user-check text-success',
    'transferred' => 'fa-arrows-left-right text-warning', 'returned' => 'fa-rotate-left text-info',
    'maintenance' => 'fa-wrench text-orange', 'repair_requested' => 'fa-screwdriver-wrench text-warning',
    'repaired' => 'fa-check-double text-success', 'disposed' => 'fa-trash-can text-danger',
    'status_change' => 'fa-shuffle text-secondary'
];
$conditionStatuses = ['new','ok','need_repair','repairing','repaired','damaged','disposed'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <h1 class="page-title fw-semibold fs-18 mb-0">
            <i class="fa-solid fa-box text-primary me-2"></i><?= htmlspecialchars($asset['asset_code']) ?>
        </h1>
    </div>

    <!-- Alert Messages -->
    <div class="row">
        <div class="col-xl-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Asset Info -->
        <div class="col-xl-8">
            <!-- Asset Details Card -->
            <div class="card custom-card">
                <div class="card-header justify-content-between align-items-center">
                    <div class="card-title">Asset Details</div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-<?= $conditionBadges[$asset['condition_status']] ?? 'secondary' ?>-transparent fs-12 px-3 py-2">
                            <i class="fa-solid fa-circle fs-8 me-1"></i> <?= ucfirst(str_replace('_', ' ', $asset['condition_status'])) ?>
                        </span>
                        <span class="badge bg-<?= $statusBadges[$asset['asset_status']] ?? 'secondary' ?>-transparent fs-12 px-3 py-2">
                            <?= ucfirst(str_replace('_', ' ', $asset['asset_status'])) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Asset Code</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-14 text-primary"><?= htmlspecialchars($asset['asset_code']) ?></span>
                                <?php if (!empty($canEdit)): ?>
                                    <button class="btn btn-sm btn-icon btn-light" data-bs-toggle="modal" data-bs-target="#editAssetCodeModal" title="Edit Asset Code">
                                        <i class="fa-solid fa-pen fs-10"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Item Name</label>
                            <span class="fw-semibold d-block mt-1"><?= htmlspecialchars($asset['item_name']) ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Quantity</label>
                            <span class="fw-semibold d-block mt-1">1</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Serial Number</label>
                            <span><?= htmlspecialchars($asset['serial_no'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Purchase Price</label>
                            <span class="fw-bold text-success fs-14">৳ <?= number_format($asset['purchase_price'], 2) ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Purchase Date</label>
                            <span><?= $asset['purchase_date'] ? date('d M Y', strtotime($asset['purchase_date'])) : '—' ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Warranty Expiry</label>
                            <?php if ($asset['warranty_expiry']): 
                                $isExpired = strtotime($asset['warranty_expiry']) < time();
                            ?>
                                <span class="<?= $isExpired ? 'text-danger' : 'text-success' ?>">
                                    <?= date('d M Y', strtotime($asset['warranty_expiry'])) ?>
                                    <?= $isExpired ? ' (Expired)' : '' ?>
                                </span>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-11 fw-semibold d-block">Registered By</label>
                            <span><?= htmlspecialchars($asset['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-11 fw-semibold d-block">Project</label>
                            <span class="fw-semibold"><?= htmlspecialchars($asset['project_name'] ?? 'Head Office') ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-11 fw-semibold d-block">Office Branch</label>
                            <span class="fw-semibold text-primary"><?= htmlspecialchars($asset['branch_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-11 fw-semibold d-block">Assigned To</label>
                            <span class="fw-semibold"><?= htmlspecialchars($asset['assigned_to_name'] ?? 'Unassigned') ?></span>
                        </div>
                        <?php if ($asset['remarks']): ?>
                            <div class="col-12">
                                <label class="text-muted fs-11 fw-semibold d-block">Remarks</label>
                                <p class="mb-0 bg-light p-2 rounded"><?= nl2br(htmlspecialchars($asset['remarks'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Movement History -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title"><i class="fa-solid fa-timeline me-2"></i>Movement History</div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($movements)): ?>
                        <div class="text-center py-4 text-muted">No movement history yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>From → To</th>
                                        <th>Condition Change</th>
                                        <th>Notes</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $m): ?>
                                        <tr>
                                            <td class="fs-12"><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fa-solid <?= $actionIcons[$m['action']] ?? 'fa-circle text-secondary' ?> me-1"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $m['action'])) ?>
                                                </span>
                                            </td>
                                            <td class="fs-12">
                                                <?php if ($m['from_location'] || $m['to_location']): ?>
                                                    <?= htmlspecialchars($m['from_location'] ?? '—') ?> → <?= htmlspecialchars($m['to_location'] ?? '—') ?>
                                                <?php elseif ($m['from_project_name'] || $m['to_project_name']): ?>
                                                    <?= htmlspecialchars($m['from_project_name'] ?? '—') ?> → <?= htmlspecialchars($m['to_project_name'] ?? '—') ?>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td class="fs-12">
                                                <?php if ($m['old_condition'] || $m['new_condition']): ?>
                                                    <?= ucfirst(str_replace('_', ' ', $m['old_condition'] ?? '—')) ?> → 
                                                    <span class="fw-semibold"><?= ucfirst(str_replace('_', ' ', $m['new_condition'] ?? '—')) ?></span>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td class="fs-12 text-wrap" style="max-width: 200px"><?= htmlspecialchars($m['notes'] ?? '—') ?></td>
                                            <td class="fs-12"><?= htmlspecialchars($m['performed_by_name'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Repair Requisitions -->
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <div class="card-title"><i class="fa-solid fa-money-bill-wave me-2"></i>Repair Requisitions</div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($repair_requisitions)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-solid fa-file-invoice-dollar fs-3 mb-2 opacity-50"></i>
                            <p class="mb-0 fs-13">No repair requisitions found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fs-11 text-muted">Date</th>
                                        <th class="fs-11 text-muted">Requested By</th>
                                        <th class="fs-11 text-muted">Amount</th>
                                        <th class="fs-11 text-muted">Purpose</th>
                                        <th class="fs-11 text-muted">Status</th>
                                        <th class="fs-11 text-muted">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($repair_requisitions as $req): ?>
                                        <tr>
                                            <td class="fs-12"><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></td>
                                            <td class="fs-12"><?= htmlspecialchars($req['requested_by_name'] ?? '—') ?></td>
                                            <td class="fs-12 fw-semibold">৳<?= number_format($req['amount'], 2) ?></td>
                                            <td class="fs-12 text-wrap" style="max-width: 250px"><?= htmlspecialchars($req['purpose'] ?? '—') ?></td>
                                            <td class="fs-12">
                                                <?php
                                                    $statusBadge = 'secondary';
                                                    $textColor = '';
                                                    if ($req['status'] === 'pending') {
                                                        $statusBadge = 'warning';
                                                        $textColor = 'text-dark';
                                                    }
                                                    elseif ($req['status'] === 'approved') $statusBadge = 'primary';
                                                    elseif ($req['status'] === 'paid') $statusBadge = 'success';
                                                    elseif ($req['status'] === 'rejected') $statusBadge = 'danger';
                                                ?>
                                                <span class="badge bg-<?= $statusBadge ?>-transparent <?= $textColor ?>">
                                                    <?= ucfirst($req['status']) ?>
                                                </span>
                                            </td>
                                             <td class="fs-12">
                                                <?php 
                                                if (!empty($req['receipt_path'])): 
                                                    $receiptList = [];
                                                    $decoded = json_decode($req['receipt_path'], true);
                                                    if (is_array($decoded)) {
                                                        $receiptList = $decoded;
                                                    } else {
                                                        $receiptList = [$req['receipt_path']];
                                                    }
                                                    $receiptList = array_values(array_filter($receiptList));
                                                ?>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <?php foreach ($receiptList as $idx => $rPath): 
                                                            $rUrl = (strpos($rPath, 'public/') === 0 ? $GLOBALS['base_url'] . '/' : $GLOBALS['base_url'] . '/public/') . ltrim($rPath, '/');
                                                            $ext = strtolower(pathinfo($rPath, PATHINFO_EXTENSION));
                                                            $isPdf = ($ext === 'pdf');
                                                        ?>
                                                            <a href="<?= htmlspecialchars($rUrl) ?>" target="_blank" class="btn btn-sm btn-light border" title="Attachment #<?= $idx + 1 ?>">
                                                                <i class="fa-solid <?= $isPdf ? 'fa-file-pdf text-danger' : 'fa-paperclip text-primary' ?>"></i>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                             </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="col-xl-4">
            <?php if (!empty($canEdit) && $asset['asset_status'] !== 'disposed'): ?>
                <!-- Transfer Card -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-arrows-left-right me-2 text-warning"></i>Transfer Asset</div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/office-assets/transfer/<?= $asset['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fs-12">Transfer to Project</label>
                                <select name="project_id" class="form-select form-select-sm">
                                    <option value="">Head Office</option>
                                    <?php foreach ($projects ?? [] as $proj): ?>
                                        <option value="<?= $proj['id'] ?>" <?= $asset['project_id'] == $proj['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($proj['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-12">Assign To</label>
                                <select name="assigned_to" class="form-select form-select-sm">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($users ?? [] as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $asset['assigned_to'] == $u['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-12">New Location</label>
                                <select name="location" class="form-select form-select-sm">
                                    <option value="">-- Select Location --</option>
                                    <?php foreach ($locations ?? [] as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc) ?>" <?= ($asset['location'] ?? '') == $loc ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-12">Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Reason for transfer..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm w-100" onclick="return confirm('Transfer this asset?')">
                                <i class="fa-solid fa-arrows-left-right me-1"></i> Transfer
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Condition Update Card -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-wrench me-2 text-info"></i>Update Condition</div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/office-assets/condition/<?= $asset['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fs-12">Current Condition: 
                                    <span class="badge bg-<?= $conditionBadges[$asset['condition_status']] ?? 'secondary' ?>-transparent">
                                        <?= ucfirst(str_replace('_', ' ', $asset['condition_status'])) ?>
                                    </span>
                                </label>
                                <select name="condition_status" class="form-select form-select-sm" required>
                                    <?php foreach ($conditionStatuses as $cs): ?>
                                        <option value="<?= $cs ?>" <?= $asset['condition_status'] === $cs ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $cs)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-12">Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Reason for condition change..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-info btn-sm w-100" onclick="return confirm('Update condition?')">
                                <i class="fa-solid fa-wrench me-1"></i> Update Condition
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Request Repair Fund Card -->
                <?php 
                $hasActiveRequisition = false;
                foreach ($repair_requisitions as $req) {
                    if (in_array($req['status'], ['pending', 'approved'])) {
                        $hasActiveRequisition = true;
                        break;
                    }
                }
                
                if (in_array($asset['condition_status'], ['need_repair', 'repairing', 'repaired'])): ?>
                    <div class="card custom-card border-primary">
                        <div class="card-header bg-primary-transparent">
                            <div class="card-title text-primary"><i class="fa-solid fa-money-bill-wave me-2"></i>Request Repair Fund</div>
                        </div>
                        <div class="card-body">
                            <?php if ($hasActiveRequisition): ?>
                                <div class="alert alert-info fs-12 mb-0 text-center">
                                    <i class="fa-solid fa-circle-info mb-2 fs-3 d-block"></i>
                                    <p class="mb-3">A repair fund requisition is currently <strong>pending</strong> or <strong>approved</strong> for this asset.</p>
                                    <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions" class="btn btn-sm btn-info w-100">
                                        Go to Fund Requisitions <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/office-assets/repair-requisition/<?= $asset['id'] ?>" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fs-12">Amount (BDT)</label>
                                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-12">Purpose / Vendor</label>
                                    <textarea name="purpose" class="form-control form-control-sm" rows="2" placeholder="Repair details and vendor name" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-12">Receipt / Quotation (Optional)</label>
                                    <input type="file" name="receipt" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100" onclick="return confirm('Submit repair fund requisition?')">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Submit Requisition
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Dispose Card -->
                <?php if ($asset['condition_status'] !== 'disposed'): ?>
                    <div class="card custom-card border-danger">
                        <div class="card-header bg-danger-transparent">
                            <div class="card-title text-danger"><i class="fa-solid fa-trash-can me-2"></i>Dispose Asset</div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-12 mb-3">This action is irreversible. The asset will be marked as disposed.</p>
                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/office-assets/dispose/<?= $asset['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fs-12">Disposal Reason</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Why is this being disposed?" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Are you sure you want to dispose this asset? This cannot be undone.')">
                                    <i class="fa-solid fa-trash-can me-1"></i> Dispose Asset
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Back button -->
            <a href="<?= $GLOBALS['base_url'] ?>/office-assets" class="btn btn-light btn-sm w-100 mt-2 mb-4">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to All Assets
            </a>
        </div>
    </div>
</div>

<?php if (!empty($canEdit)): ?>
<!-- ========================================================================= -->
<!-- MODAL: EDIT ASSET CODE -->
<!-- ========================================================================= -->
<div class="modal fade" id="editAssetCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/office-assets/update-code/<?= $asset['id'] ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-12 text-muted mb-3">You can manually overwrite the auto-generated asset code here.</p>
                    <div class="mb-3">
                        <label class="form-label">Asset Code <span class="text-danger">*</span></label>
                        <input type="text" name="asset_code" class="form-control fw-bold" value="<?= htmlspecialchars($asset['asset_code']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update Code</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

        </div>
    </div>
</div>
