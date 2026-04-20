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
                        <i class="bi bi-pencil-square me-2"></i>Edit Vendor — <?= htmlspecialchars($vendor['name']) ?>
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-vendor-update/<?= $vendor['id'] ?>" method="POST">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($vendor['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Address</label>
                                <input type="text" name="address" class="form-control"
                                       value="<?= htmlspecialchars($vendor['address'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($vendor['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($vendor['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blacklisted" <?= ($vendor['status'] === 'blacklisted') ? 'selected' : '' ?>>Blacklisted</option>
                                </select>
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
