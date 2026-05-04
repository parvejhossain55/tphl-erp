<?php
$base_url = $GLOBALS['base_url'];
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><?= htmlspecialchars($page_title ?? 'Create Material Test') ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/qc-dashboard" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<section class="section">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <div class="card__wrapper shadow-sm">
        <div class="card-header bg-white border-bottom pb-3 mb-3">
            <h5 class="card-title m-2">Material Lab Test Form</h5>
        </div>
        <form action="<?= $base_url ?>/qc-material-test-store" method="POST" enctype="multipart/form-data">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Material Receiving (Challan) <span class="text-danger">*</span></label>
                        <select name="material_receiving_id" class="form-select" required>
                            <option value="">-- Select Received Material --</option>
                            <?php foreach ($receivings as $rec): ?>
                                <option value="<?= $rec['id'] ?>">GRN-<?= htmlspecialchars($rec['id']) ?> (PO: <?= htmlspecialchars($rec['purchase_order_id'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Test Name <span class="text-danger">*</span></label>
                        <input type="text" name="test_name" class="form-control" placeholder="e.g. Concrete Cylinder Test, Rod Grade 60" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Result Value</label>
                        <input type="text" name="result_value" class="form-control" placeholder="e.g. 3200 PSI">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Test Status <span class="text-danger">*</span></label>
                        <select name="test_status" class="form-select" required>
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                            <option value="partially_passed">Partially Passed</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Attachment (Optional)</label>
                        <input type="file" name="test_attachment" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Lab Report Link</label>
                        <input type="url" name="lab_report_link" class="form-control" placeholder="https://...">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Test Result
                </button>
            </div>
        </form>
    </div>

    <!-- Container to show materials under the selected receiving/challan -->
    <div id="receiving-items-container" class="mt-4" style="display:none;">
        <div class="card__wrapper shadow-sm">
            <div class="card-header bg-white border-bottom pb-2 mb-2">
                <h6 class="card-title m-0"><i class="bi bi-box-seam text-primary me-2"></i>Materials in this Challan</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0" id="receiving-items-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Material Name</th>
                                <th>Vendor</th>
                                <th>Received Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const receivingSelect = document.querySelector('select[name="material_receiving_id"]');
    const container = document.getElementById('receiving-items-container');
    const tbody = document.querySelector('#receiving-items-table tbody');

    receivingSelect.addEventListener('change', function() {
        const id = this.value;
        if (!id) {
            container.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }

        fetch(`<?= $base_url ?>/qc-ajax-receiving-items?receiving_id=${id}`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data && data.length > 0) {
                    data.forEach((item, index) => {
                        const tr = document.createElement('tr');
                        const statusBadgeClass = item.status === 'Accepted' ? 'bg-success' : (item.status === 'Rejected' ? 'bg-danger' : 'bg-secondary');
                        tr.innerHTML = `
                            <td>${index + 1}</td>
                            <td class="fw-bold">${item.material_name}</td>
                            <td>${item.vendor_name || 'N/A'}</td>
                            <td>${item.received_qty} ${item.unit}</td>
                            <td><span class="badge ${statusBadgeClass}">${item.status || 'Pending'}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });
                    container.style.display = 'block';
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No materials found for this challan.</td></tr>';
                    container.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error fetching receiving items:', err);
                container.style.display = 'none';
            });
    });
});
</script>
