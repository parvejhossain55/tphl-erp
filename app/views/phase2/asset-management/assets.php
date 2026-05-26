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
        <div class="ms-md-1 ms-0">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/home">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-products">Asset Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">All Assets</li>
                </ol>
            </nav>
        </div>
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
    <div class="row mb-4">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold text-primary mb-0"><?= $stats['total_assets'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Total Assets</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold text-success mb-0"><?= $stats['total_products'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold text-danger mb-0"><?= $stats['needs_attention'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Need Attention</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card custom-card">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold text-warning mb-0"><?= $stats['pending_purchases'] ?? 0 ?></h3>
                    <p class="text-muted mb-0 fs-11">Pending Purchases</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-8 col-12">
            <div class="card custom-card">
                <div class="card-body text-center py-3">
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
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/office-assets" class="btn btn-light btn-sm ms-1"><i class="fa-solid fa-arrows-rotate"></i></a>
                </div>
            </form>
        </div>

        <!-- Assets Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Asset Code</th>
                            <th>Product Code</th>
                            <th>Item Name</th>
                            <th>Project / Location</th>
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
                                <td colspan="10" class="text-center py-4 text-muted">No assets found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $i => $a): ?>
                                <tr>
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
                                    <td>
                                        <span class="d-block fs-12"><?= htmlspecialchars($a['project_name'] ?? 'Head Office') ?></span>
                                        <span class="text-muted fs-11"><?= htmlspecialchars($a['location'] ?? '') ?></span>
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
                            <input type="text" name="location" class="form-control" value="Head Office" placeholder="Where is this asset?">
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
                            <input type="text" name="location" id="edit_location" class="form-control">
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
</script>