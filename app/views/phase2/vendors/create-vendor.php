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
                    <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <small>
                            <strong>Multi-Step Approval Process:</strong> Once submitted, this vendor request will progress through required approvals (Dept Head &rarr; Purchase Head &rarr; Admin & Operation Head &rarr; Board of Director &rarr; Management) before becoming active.
                        </small>
                    </div>

                    <form action="<?= $base_url ?>/con-vendor-store" method="POST">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Vendor / Contact Person Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       placeholder="e.g., Mohammad Rahim">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Company / Business Name</label>
                                <input type="text" name="company_name" class="form-control"
                                       placeholder="e.g., ABC Enterprise Ltd.">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control"
                                       placeholder="e.g., +880 1711-000000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="vendor@example.com">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Office / Business Address</label>
                                <textarea name="address" class="form-control" rows="2"
                                       placeholder="Full business address..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-vendors" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-send-check me-1"></i> Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
