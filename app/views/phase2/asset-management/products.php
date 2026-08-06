<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <h1 class="page-title fw-semibold fs-18 mb-0">Office Asset Products</h1>
    </div>

    <!-- Alert Messages -->
    <div class="row">
        <div class="col-xl-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card custom-card">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 fs-11">Total Products</p>
                            <h5 class="fw-bold mb-0"><?= count($products ?? []) ?></h5>
                        </div>
                        <div class="avatar avatar-sm bg-primary-transparent rounded-circle d-none d-sm-flex">
                            <i class="fa-solid fa-boxes-stacked fs-14 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card custom-card">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 fs-11">Active Products</p>
                            <h5 class="fw-bold mb-0 text-success"><?= count(array_filter($products ?? [], fn($p) => $p['status'] === 'active')) ?></h5>
                        </div>
                        <div class="avatar avatar-sm bg-success-transparent rounded-circle d-none d-sm-flex">
                            <i class="fa-solid fa-check-circle fs-14 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card custom-card">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 fs-11">Total Stock</p>
                            <h5 class="fw-bold mb-0 text-info"><?= array_sum(array_column($stockMap ?? [], 'total_assets')) ?></h5>
                        </div>
                        <div class="avatar avatar-sm bg-info-transparent rounded-circle d-none d-sm-flex">
                            <i class="fa-solid fa-warehouse fs-14 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card custom-card">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 fs-11">Attention</p>
                            <h5 class="fw-bold mb-0 text-danger"><?= array_sum(array_column($stockMap ?? [], 'need_repair')) + array_sum(array_column($stockMap ?? [], 'damaged')) ?></h5>
                        </div>
                        <div class="avatar avatar-sm bg-danger-transparent rounded-circle d-none d-sm-flex">
                            <i class="fa-solid fa-triangle-exclamation fs-14 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
            <div class="card-title">Product Master List</div>
            <div class="d-flex gap-2">
                <?php if (!empty($canEdit)): ?>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fa-solid fa-plus"></i> Add Product
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body border-bottom pb-3">
            <form method="GET" class="row  align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fs-12 fw-semibold mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Code, name, brand..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label fs-12 fw-semibold mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label fs-12 fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/asset-products" class="btn btn-light btn-sm flex-fill text-center"><i class="fa-solid fa-arrows-rotate me-1"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand / Model</th>
                            <th class="text-end">Unit Price (BDT)</th>
                            <th class="text-center">In Stock</th>
                            <th class="text-center">In Use</th>
                            <th>Status</th>
                            <?php if (!empty($canEdit)): ?>
                                <th class="text-center">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="<?= !empty($canEdit) ? 10 : 9 ?>" class="text-center py-4 text-muted">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $i => $p): 
                                $stock = $stockMap[$p['id']] ?? ['total_assets' => 0, 'in_use' => 0, 'in_store' => 0];
                            ?>
                                <tr>
                                    <td class="fw-semibold text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <span class="badge bg-primary fw-semibold text-white"><?= htmlspecialchars($p['product_code']) ?></span>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></span>
                                    </td>
                                    <td class="text-muted fs-12">
                                        <?= htmlspecialchars($p['brand'] ?? '—') ?>
                                        <?= $p['model'] ? ' / ' . htmlspecialchars($p['model']) : '' ?>
                                    </td>
                                    <td class="text-end fw-semibold"><?= number_format($p['unit_price'], 2) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-white"><?= $stock['in_store'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success text-white"><?= $stock['in_use'] ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?> text-white">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <?php if (!empty($canEdit)): ?>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-icon" 
                                                    onclick='openEditModal(<?= json_encode($p) ?>)' title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/asset-products/delete" 
                                                    onsubmit="return confirm('Delete this product?');" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    <?php endif; ?>
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
<!-- MODAL: ADD PRODUCT -->
<!-- ========================================================================= -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-products/store" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Asset Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row ">
                        <div class="col-md-6">
                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" name="product_code" class="form-control" placeholder="e.g. ELEC-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Dell Laptop" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" value="Piece" placeholder="Piece, Set, Unit...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" placeholder="Brand name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" placeholder="Model number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price (BDT)</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Product description..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Product</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT -->
<!-- ========================================================================= -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-products/update" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_product_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Asset Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row ">
                        <div class="col-md-6">
                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" name="product_code" id="edit_product_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" id="edit_brand" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="edit_model" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price (BDT)</label>
                            <input type="number" name="unit_price" id="edit_unit_price" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Update Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update Product</button>
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
    #stats-cards h5 {
        font-size: 15px !important;
    }
}
</style>

<script>
function openEditModal(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_product_code').value = product.product_code;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_category_id').value = product.category_id;
    document.getElementById('edit_unit').value = product.unit || 'Piece';
    document.getElementById('edit_brand').value = product.brand || '';
    document.getElementById('edit_model').value = product.model || '';
    document.getElementById('edit_unit_price').value = product.unit_price || 0;
    document.getElementById('edit_status').value = product.status;
    document.getElementById('edit_description').value = product.description || '';
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>
<?php endif; ?>
