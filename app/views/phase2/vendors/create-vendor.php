<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendors">Vendors</a></li>
                <li class="breadcrumb-item active">Add Vendor</li>
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
                        <i class="bi bi-person-plus me-2"></i>Vendor / Supplier Information
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-vendor-store" method="POST">

                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       placeholder="e.g., ABC Suppliers Ltd.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       placeholder="e.g., +880 1711-000000">
                            </div>
                        </div>

                        <div class="row  mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="vendor@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Address</label>
                                <input type="text" name="address" class="form-control"
                                       placeholder="Full address...">
                            </div>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-vendors" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Save Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
