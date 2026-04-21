<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-operators">Operators</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card__wrapper shadow-sm">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-operator-store" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Operator Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="Contractor">Contractor</option>
                                    <option value="Subcontractor">Subcontractor</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Worker">Worker</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trade / Expertise</label>
                                <input type="text" name="trade" class="form-control" placeholder="e.g. Masonry, Electrical, Plumbing">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="01XXX-XXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NID Number</label>
                                <input type="text" name="nid_number" class="form-control" placeholder="NID Card Number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Daily Rate (if applicable)</label>
                                <input type="number" step="0.01" name="daily_rate" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Primary address details"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-check-circle me-1"></i> Save Operator
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/con-operators" class="btn btn-light px-4 border ms-2">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'app/views/layouts/footer.php'; ?>
