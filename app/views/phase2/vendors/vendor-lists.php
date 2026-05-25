<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Vendors</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a href="<?= $base_url ?>/con-vendor-create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Vendor
        </a>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importVendorCsvModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
        </button>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center">PO Orders</th>
                            <th class="text-center">Penalties</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vendors)): ?>
                            <?php foreach ($vendors as $i => $v): ?>
                            <tr>
                                <td><?= ($offset ?? 0) + $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($v['name']) ?></td>
                                <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($v['email'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $v['total_orders'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (($v['penalty_count'] ?? 0) > 0): ?>
                                        <span class="badge bg-danger"><?= $v['penalty_count'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $rating = (float)($v['rating'] ?? 0);
                                    $stars  = '';
                                    for ($s = 1; $s <= 5; $s++) {
                                        $stars .= ($s <= round($rating))
                                            ? '<i class="bi bi-star-fill text-warning"></i>'
                                            : '<i class="bi bi-star text-muted"></i>';
                                    }
                                    echo $stars;
                                    ?>
                                    <span class="ms-1 small text-muted">(<?= number_format($rating, 1) ?>)</span>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = ($v['status'] === 'active') ? 'bg-success' : 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusBadge ?>"><?= ucfirst($v['status']) ?></span>
                                </td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <a href="<?= $base_url ?>/con-vendor/<?= $v['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= $base_url ?>/con-vendor-edit/<?= $v['id'] ?>"
                                       class="btn btn-sm btn-outline-warning ms-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                            title="Delete" onclick="confirmDeleteVendor(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['name'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-people me-2"></i>No vendors yet.
                                    <a href="<?= $base_url ?>/con-vendor-create" class="ms-2">Add one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="row my-3">
                <div class="col-12 d-flex justify-content-center">
                    <?= paginateLinksSecondary($base_url . "/con-vendors", $currentPage, $totalPages); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteVendorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete vendor <strong id="deleteVendorName"></strong>?</p>
                <p class="text-muted small">This will permanently remove the vendor record. PO history will remain.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteVendorForm" action="<?= $base_url ?>/con-vendor-delete" method="POST" class="d-inline">
                    <input type="hidden" name="id" id="deleteVendorId">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteVendor(id, name) {
    document.getElementById('deleteVendorId').value = id;
    document.getElementById('deleteVendorName').textContent = name;
    var modal = new bootstrap.Modal(document.getElementById('deleteVendorModal'));
    modal.show();
}
</script>

<!-- Import CSV Modal: Import Vendors -->
<div class="modal fade" id="importVendorCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Vendors</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">

                <!-- Column Guide -->
                <div class="mb-3">
                    <p class="fw-semibold mb-2" style="font-size:13px;">📋 CSV Column Reference</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:11.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Column Header</th>
                                    <th>Required?</th>
                                    <th>Allowed Values</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Vendor_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Unique Vendor Name</td>
                                    <td><em>ABC Suppliers</em></td>
                                </tr>
                                <tr>
                                    <td><code>Phone</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Phone Number</td>
                                    <td><em>01700000000</em></td>
                                </tr>
                                <tr>
                                    <td><code>Email</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Valid Email</td>
                                    <td><em>contact@abc.com</em></td>
                                </tr>
                                <tr>
                                    <td><code>Address</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Text</td>
                                    <td><em>Dhaka, BD</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadVendorSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-vendor-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">শুধুমাত্র <strong>.csv</strong> ফাইল। Excel থেকে "Save As → CSV UTF-8" করে export করুন।</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-upload me-1"></i> Import Now
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
function downloadVendorSampleCsv() {
    const headers = [
        'Vendor_Name', 'Phone', 'Email', 'Address'
    ];
    const sampleRow = [
        'ABC Suppliers Ltd', '01711223344', 'contact@abc.com', 'Dhaka, Bangladesh'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleRow.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'vendors_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
