<?php
$page_title = "Add Operator / Contractor";
$page = 'Add Operator';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <small>
                <strong>Multi-Step Approval Process:</strong> Once submitted, this contractor request will progress through required approvals (Dept Head &rarr; Purchase Head &rarr; Role 20 &rarr; Role 38 Board of Director &rarr; Management) before becoming active.
            </small>
        </div>
        <form action="<?= $GLOBALS['base_url'] ?>/con-contractor-store" method="POST">
            <div class="row gy-20">

                <div class="col-xl-6">
                    <label>Operator Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Full name" required>
                </div>

                <div class="col-xl-6">
                    <label>Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="Contractor">Contractor</option>
                        <option value="Subcontractor">Subcontractor</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Worker">Worker</option>
                    </select>
                </div>

                <div class="col-xl-6">
                    <label>Trade / Expertise</label>
                    <input type="text" name="trade" class="form-control" placeholder="e.g. Masonry, Electrical, Plumbing">
                </div>

                <div class="col-xl-6">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="01XXX-XXXXXX">
                </div>

                <div class="col-xl-6">
                    <label>NID Number</label>
                    <input type="text" name="nid_number" class="form-control" placeholder="NID Card Number">
                </div>

                <div class="col-xl-6">
                    <label>Daily Rate (BDT)</label>
                    <input type="number" step="0.01" name="daily_rate" class="form-control" placeholder="0.00">
                </div>

                <div class="col-xl-12">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Primary address details"></textarea>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5"><i class="bi bi-send-check me-1"></i> Submit for Approval</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-contractors" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>
