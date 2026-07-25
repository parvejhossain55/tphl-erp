<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$conditionStatuses = ['new','ok','need_repair','repairing','repaired','damaged','disposed'];
$assetStatuses = ['in_use','in_store','transferred','disposed','maintenance'];
$categories = ['Electronics','Furniture','Vehicle','IT Equipment','Office Supplies','Machinery','Safety Equipment','Other'];

$conditionBadges = [
    'new' => 'primary', 'ok' => 'success', 'need_repair' => 'warning',
    'repairing' => 'info', 'repaired' => 'teal', 'damaged' => 'danger', 'disposed' => 'dark'
];
$statusBadges = [
    'in_use' => 'success', 'in_store' => 'info', 'transferred' => 'warning',
    'disposed' => 'dark', 'maintenance' => 'orange'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <h1 class="page-title fw-semibold fs-18 mb-0">Asset Register</h1>
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

    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center p-2 p-md-3">
                    <h3 class="fw-bold text-primary mb-0"><?= $stats['total_assets'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Total Assets</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center p-2 p-md-3">
                    <h3 class="fw-bold text-success mb-0"><?= $stats['total_products'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center p-2 p-md-3">
                    <h3 class="fw-bold text-danger mb-0"><?= $stats['needs_attention'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Need Attention</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center p-2 p-md-3">
                    <h3 class="fw-bold text-warning mb-0"><?= $stats['pending_purchases'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Pending Purchases</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-8 col-12">
            <div class="card custom-card">
                <div class="card-body text-center p-2 p-md-3">
                    <h3 class="fw-bold text-info mb-0">৳ <?= number_format($stats['total_value'] ?? 0, 2) ?></h3>
                    <p class="text-muted mb-0 fs-11">Total Asset Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
            <div class="card-title">All Assets</div>
            <div class="d-flex gap-2">
                <?php if (!empty($canEdit)): ?>
                    <button type="button" class="btn btn-warning btn-sm d-none align-items-center gap-1" id="bulkAssignBtn" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                        <i class="fa-solid fa-people-arrows"></i> Bulk Assign (<span id="bulkAssignCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fa-solid fa-plus"></i> Register Asset
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body border-bottom pb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Asset code, name..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects ?? [] as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] ?? '') == $proj['id'] ? 'selected' : '' ?>><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Condition</label>
                    <select name="condition_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($conditionStatuses as $cs): ?>
                            <option value="<?= $cs ?>" <?= ($filters['condition_status'] ?? '') === $cs ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cs)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Status</label>
                    <select name="asset_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($assetStatuses as $as): ?>
                            <option value="<?= $as ?>" <?= ($filters['asset_status'] ?? '') === $as ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $as)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/office-assets" class="btn btn-light btn-sm flex-fill text-center"><i class="fa-solid fa-arrows-rotate"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Assets Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="selectAllAssets">
                            </th>
                            <th>#</th>
                            <th>Asset Code</th>
                            <th>Product Code</th>
                            <th>Item Name</th>
                            <th class="text-center">Qty</th>
                            <th>Project</th>
                            <th>Office/Location</th>
                            <th>Assigned To</th>
                            <th class="text-end">Price (BDT)</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No assets found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $i => $a): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input asset-checkbox" value="<?= $a['id'] ?>">
                                    </td>
                                    <td class="fw-semibold text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $GLOBALS['base_url'] ?>/office-assets/show/<?= $a['id'] ?>" class="fw-semibold text-primary text-decoration-underline">
                                            <?= htmlspecialchars($a['asset_code']) ?>
                                        </a>
                                    </td>
                                    <td class="fs-12"><?= htmlspecialchars($a['serial_no'] ?? '—') ?></td>
                                    <td>
                                        <span class="d-block fs-13 fw-semibold"><?= htmlspecialchars($a['item_name']) ?></span>
                                    </td>
                                    <td class="text-center fw-semibold text-dark">
                                        1
                                    </td>
                                    <td>
                                        <span class="d-block fs-12 fw-semibold text-dark"><?= htmlspecialchars($a['project_name'] ?? 'Head Office') ?></span>
                                    </td>
                                    <td>
                                        <span class="fs-12 text-muted"><?= htmlspecialchars($a['location'] ?? 'Head Office') ?></span>
                                    </td>
                                    <td class="fs-12"><?= htmlspecialchars($a['assigned_to_name'] ?? '—') ?></td>
                                    <td class="text-end fw-semibold"><?= number_format($a['purchase_price'], 2) ?></td>
                                    <td>
                                        <span class="fw-semibold fs-12 text-dark">
                                            <?= ucfirst(str_replace('_', ' ', $a['condition_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold fs-12 text-dark">
                                            <?= ucfirst(str_replace('_', ' ', $a['asset_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $GLOBALS['base_url'] ?>/office-assets/show/<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-icon ms-1 position-relative" title="View Transfer Logs" onclick="openTransferLogModal(<?= $a['id'] ?>)">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                            <?php if (($a['transfer_count'] ?? 0) > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                                    <?= $a['transfer_count'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                        <?php if (in_array($a['condition_status'], ['need_repair', 'repairing', 'repaired'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon ms-1" title="Request Repair Fund" onclick="openRepairFundModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['asset_code']) ?>', '<?= htmlspecialchars($a['item_name']) ?>')">
                                                <i class="fa-solid fa-money-bill-wave"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($roleId === 20): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info btn-icon ms-1" title="Edit Asset" data-asset-b64="<?= base64_encode(json_encode($a)) ?>" onclick="openEditModal(this)"><i class="fa-solid fa-pencil"></i></button>
                                        <?php endif; ?>
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

<?php if (!empty($canEdit)): ?>
<!-- ========================================================================= -->
<!-- MODAL: REGISTER NEW ASSET -->
<!-- ========================================================================= -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= $GLOBALS['base_url'] ?>/office-assets/store" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register New Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products ?? [] as $prod): ?>
                                    <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name'] . ' (' . $prod['product_code'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name (Optional)</label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Dell Monitor 24''">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_no" class="form-control" placeholder="S/N if available">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">Head Office (No Project)</option>
                                <?php foreach ($projects ?? [] as $proj): ?>
                                    <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                                <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" id="edit_assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                <?php foreach ($users ?? [] as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <select name="location" class="form-select">
                                <option value="Head Office">Head Office</option>
                                <?php foreach ($locations ?? [] as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($users ?? [] as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Price (BDT)</label>
                            <input type="number" name="purchase_price" class="form-control" step="0.01" value="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condition</label>
                            <select name="condition_status" class="form-select">
                                <option value="new" selected>New</option>
                                <option value="ok">OK</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Register Asset</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: EDIT ASSET -->
<!-- ========================================================================= -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editAssetForm" action="" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <select name="product_id" id="edit_product_id" class="form-select">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products ?? [] as $prod): ?>
                                    <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name'] . ' (' . $prod['product_code'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_no" id="edit_serial_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <select name="project_id" id="edit_project_id" class="form-select">
                                <option value="">Head Office (No Project)</option>
                                <?php foreach ($projects ?? [] as $proj): ?>
                                    <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                                <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" id="edit_assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                <?php foreach ($users ?? [] as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <select name="location" id="edit_location" class="form-select">
                                <option value="Head Office">Head Office</option>
                                <?php foreach ($locations ?? [] as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Price (BDT)</label>
                            <input type="text" id="edit_purchase_price_display" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Condition</label>
                            <select name="condition_status" id="edit_condition_status" class="form-select">
                                <option value="new">New</option>
                                <option value="ok">OK</option>
                                <option value="used">Used</option>
                                <option value="need_repair">Need Repair</option>
                                <option value="repairing">Repairing</option>
                                <option value="repaired">Repaired</option>
                                <option value="damaged">Damaged</option>
                                <option value="disposed">Disposed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="asset_status" id="edit_asset_status" class="form-select">
                                <option value="in_store">In Store</option>
                                <option value="in_use">In Use</option>
                                <option value="transferred">Transferred</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="sold">Sold</option>
                                <option value="disposed">Disposed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/office-assets/bulk-transfer" method="POST" id="bulkAssignForm">
            <input type="hidden" name="asset_ids" id="bulk_asset_ids" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Assign Assets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        You have selected <strong id="bulkSelectedCountText">0</strong> asset(s) to transfer.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transfer to Project</label>
                        <select name="project_id" class="form-select">
                            <option value="">Head Office (No Project)</option>
                            <?php foreach ($projects ?? [] as $proj): ?>
                                <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($users ?? [] as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Location</label>
                        <select name="location" class="form-select">
                            <option value="">-- Same as current or Head Office --</option>
                            <?php foreach ($locations ?? [] as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for transfer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Transfer Assets</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- MODAL: VIEW TRANSFER LOGS -->
<div class="modal fade" id="transferLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-muted">Date</th>
                                <th class="fs-11 text-muted">Action</th>
                                <th class="fs-11 text-muted">Condition</th>
                                <th class="fs-11 text-muted">From &rarr; To</th>
                                <th class="fs-11 text-muted">Performed By</th>
                                <th class="fs-11 text-muted">Notes</th>
                            </tr>
                        </thead>
                        <tbody id="transferLogTableBody">
                            <tr><td colspan="6" class="text-center py-3">Loading logs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- MODAL: REQUEST REPAIR FUND -->
<div class="modal fade" id="repairFundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="repairFundForm" method="POST" action="" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header bg-primary-transparent">
                    <h5 class="modal-title text-primary"><i class="fa-solid fa-money-bill-wave me-2"></i>Request Repair Fund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-13 text-muted mb-3">Requesting repair fund for: <strong id="repair_asset_code_display"></strong> - <span id="repair_asset_name_display"></span></p>
                    <div class="mb-3">
                        <label class="form-label fs-12">Amount (BDT)</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12">Purpose / Vendor</label>
                        <textarea name="purpose" class="form-control form-control-sm" rows="3" placeholder="Repair details and vendor name" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12">Receipt / Quotation (Optional)</label>
                        <input type="file" name="receipt" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit Requisition</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
@media (max-width: 480px) {
    #stats-cards .card.custom-card {
        padding: 10px !important;
    }
    #stats-cards .card-body {
        padding: 0px !important;
    }
    #stats-cards .fs-11 {
        font-size: 10px !important;
    }
    #stats-cards h3 {
        font-size: 18px !important;
    }
    #stats-cards p {
    font-size: 13px !important;
}
}
</style>

<script>
function openEditModal(button) {
    var b64 = button.getAttribute('data-asset-b64');
    var asset = JSON.parse(atob(b64));
    document.getElementById('editAssetForm').action = '<?= $GLOBALS['base_url'] ?>/office-assets/update/' + asset.id;
    if (document.getElementById('edit_product_id')) {
        document.getElementById('edit_product_id').value = asset.product_id || '';
    }
    document.getElementById('edit_item_name').value = asset.item_name || '';
    document.getElementById('edit_serial_no').value = asset.serial_no || '';
    document.getElementById('edit_project_id').value = asset.project_id || '';
    document.getElementById('edit_location').value = asset.location || '';
    document.getElementById('edit_purchase_price_display').value = asset.purchase_price ? parseFloat(asset.purchase_price).toLocaleString('en-BD', {minimumFractionDigits: 2}) : '0.00';
    document.getElementById('edit_condition_status').value = asset.condition_status || 'new';
    document.getElementById('edit_asset_status').value = asset.asset_status || 'in_store';
    if(document.getElementById('edit_assigned_to')) {
        document.getElementById('edit_assigned_to').value = asset.assigned_to || '';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllAssets');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    const bulkAssignBtn = document.getElementById('bulkAssignBtn');
    const bulkAssignCount = document.getElementById('bulkAssignCount');
    const bulkSelectedCountText = document.getElementById('bulkSelectedCountText');
    const bulkAssetIdsInput = document.getElementById('bulk_asset_ids');

    function updateBulkButton() {
        let selectedCount = 0;
        let selectedIds = [];
        checkboxes.forEach(cb => {
            if (cb.checked) {
                selectedCount++;
                selectedIds.push(cb.value);
            }
        });

        if (selectedCount > 0) {
            bulkAssignBtn.classList.remove('d-none');
            bulkAssignBtn.classList.add('d-flex');
            bulkAssignCount.textContent = selectedCount;
            bulkSelectedCountText.textContent = selectedCount;
            bulkAssetIdsInput.value = selectedIds.join(',');
        } else {
            bulkAssignBtn.classList.add('d-none');
            bulkAssignBtn.classList.remove('d-flex');
        }
    }

    if(selectAllBtn) {
        selectAllBtn.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkButton();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked && selectAllBtn) selectAllBtn.checked = false;
            updateBulkButton();
        });
    });
});

function openTransferLogModal(assetId) {
    const tbody = document.getElementById('transferLogTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...</td></tr>';
    
    var myModal = new bootstrap.Modal(document.getElementById('transferLogModal'));
    myModal.show();

    fetch('<?= $GLOBALS['base_url'] ?>/office-assets/logs/' + assetId)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No transfer history found.</td></tr>';
                return;
            }
            
            let html = '';
            data.forEach(log => {
                let from = log.from_location || 'N/A';
                if (log.from_project_name) from += ` (${log.from_project_name})`;
                
                let to = log.to_location || 'N/A';
                if (log.to_project_name) to += ` (${log.to_project_name})`;
                
                let condition = log.new_condition ? log.new_condition.replace('_', ' ') : '-';
                
                html += `
                    <tr>
                        <td class="fs-12">${log.created_at}</td>
                        <td><span class="badge bg-secondary">${log.action}</span></td>
                        <td class="fs-12 text-capitalize">${condition}</td>
                        <td class="fs-12">
                            <span class="text-muted">From:</span> ${from}<br>
                            <span class="text-muted">To:</span> <span class="fw-medium">${to}</span>
                        </td>
                        <td class="fs-12">${log.performed_by_name || 'System'}</td>
                        <td class="fs-12 text-wrap" style="max-width: 200px;">${log.notes || '-'}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('Error fetching logs:', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Failed to load logs.</td></tr>';
        });
}

function openRepairFundModal(assetId, assetCode, assetName) {
    document.getElementById('repair_asset_code_display').textContent = assetCode;
    document.getElementById('repair_asset_name_display').textContent = assetName;
    document.getElementById('repairFundForm').action = '<?= $GLOBALS['base_url'] ?>/office-assets/repair-requisition/' + assetId;
    
    var modal = new bootstrap.Modal(document.getElementById('repairFundModal'));
    modal.show();
}
</script>