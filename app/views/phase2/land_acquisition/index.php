<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = in_array($roleId, [1, 3, 14]);
?>

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
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLandModal">
        <i class="fa-light fa-plus me-1"></i> Record New Land Purchase
    </button>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
            <div class="card-body">
                <h6 class="text-white-50">Total Lands Purchased</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['total_lands'] ?? 0)) ?></h3>
                <div class="mt-2 text-white-50 small">
                    Area: <strong><?= number_format((float)($stats['total_area'] ?? 0), 2) ?> Dec/Katha</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <div class="card-body">
                <h6 class="text-white-50">Registered & Cleared</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['registered_count'] ?? 0)) ?></h3>
                <div class="mt-2 text-white-50 small">
                    Cost: <strong>BDT <?= number_format((float)($stats['total_reg_cost'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); color: white;">
            <div class="card-body">
                <h6 class="text-white-50">Bayana Underway</h6>
                <h3 class="mb-0"><?= number_format((int)($stats['bayana_count'] ?? 0)) ?></h3>
                <div class="mt-2 text-white-50 small">
                    Paid: <strong>BDT <?= number_format((float)($stats['total_bayana_paid'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #3a7bd5 0%, #3a6073 100%); color: white;">
            <div class="card-body">
                <h6 class="text-white-50">Total Estimated Portfolio Value</h6>
                <h3 class="mb-0">BDT <?= number_format((float)($stats['total_value'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-white-50 small">
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
<div class="modal fade" id="addLandModal" tabindex="-1" aria-labelledby="addLandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addLandModalLabel">Record Land Acquisition</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $base_url ?>/con-land-acquisitions/store" method="POST">
                <div class="modal-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assign to Project <span class="text-danger">*</span></label>
                            <select class="form-select" name="project_id" required>
                                <option value="" disabled selected>Select Project...</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location / Address</label>
                            <input type="text" class="form-control" name="location" placeholder="Mouza, Dag, Khatian numbers etc.">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Landowner Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="land_owner_name" required placeholder="Owner's full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Landowner Phone</label>
                            <input type="text" class="form-control" name="land_owner_phone" placeholder="Phone number">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Area <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" class="form-control" name="total_area" required placeholder="e.g. 15.5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Area Unit <span class="text-danger">*</span></label>
                            <select class="form-select" name="area_unit" required>
                                <option value="decimal" selected>Decimal</option>
                                <option value="katha">Katha</option>
                                <option value="bigha">Bigha</option>
                                <option value="sft">Square Feet</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price per Unit (BDT) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="price_per_unit" required placeholder="Price per decimal/katha">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Status</label>
                            <select class="form-select" name="status">
                                <option value="selected" selected>Selected (Under negotiation)</option>
                                <option value="bayana">Bayana (Advance paid)</option>
                                <option value="registered">Registered (Purchased)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Negotiation details, agreements etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Land Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
