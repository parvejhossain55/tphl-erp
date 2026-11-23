<?php
$displayVendorName = !empty(trim($vendor['company_name'] ?? '')) ? $vendor['company_name'] : $vendor['name'];
?>
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendors">Vendors</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendors" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Vendor — <?= htmlspecialchars($displayVendorName) ?>
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-vendor-update/<?= $vendor['id'] ?>" method="POST" enctype="multipart/form-data">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Business / Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" required
                                       value="<?= htmlspecialchars($vendor['company_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Person / Designated Person Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($vendor['name']) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required
                                       value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Address</label>
                                <input type="text" name="address" class="form-control"
                                       value="<?= htmlspecialchars($vendor['address'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($vendor['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="pending_approval" <?= ($vendor['status'] === 'pending_approval') ? 'selected' : '' ?>>Pending Approval</option>
                                    <option value="inactive" <?= ($vendor['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blacklisted" <?= ($vendor['status'] === 'blacklisted') ? 'selected' : '' ?>>Blacklisted</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-file-earmark-check me-2"></i>Verification Documents</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NID Document (Image / PDF)</label>
                                <input type="file" name="nid_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <?php if (!empty($vendor['nid_file'])): ?>
                                    <div class="mt-1">
                                        <a href="<?= $base_url ?>/<?= htmlspecialchars($vendor['nid_file']) ?>" target="_blank" class="small text-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i>View Current NID Document
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NID Number</label>
                                <input type="text" name="nid_number" class="form-control"
                                       value="<?= htmlspecialchars($vendor['nid_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trade License Document (Image / PDF)</label>
                                <input type="file" name="trade_license_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <?php if (!empty($vendor['trade_license_file'])): ?>
                                    <div class="mt-1">
                                        <a href="<?= $base_url ?>/<?= htmlspecialchars($vendor['trade_license_file']) ?>" target="_blank" class="small text-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i>View Current Trade License
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trade License Number</label>
                                <input type="text" name="trade_license_number" class="form-control"
                                       value="<?= htmlspecialchars($vendor['trade_license_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-vendors" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Update Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
