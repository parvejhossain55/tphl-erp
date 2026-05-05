<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Operators & Contractors</h4>

<div class="row mb-3">
    <div class="col-xxl-12">
        <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <a href="<?= $GLOBALS['base_url'] ?>/con-contractor-create" class="btn btn-primary text-nowrap">
                    <i class="fa-light fa-plus me-1"></i> Add Contractor
                </a>
                <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importContractorCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Trade</th>
                            <th>Phone</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($operators)): ?>
                            <?php foreach ($operators as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($row['name']) ?>
                                        <?php if (!empty($row['nid_number'])): ?>
                                            <br><small class="text-muted">NID: <?= htmlspecialchars($row['nid_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['role']) ?></td>
                                    <td><?= htmlspecialchars($row['trade'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
                                    <td><?= $row['daily_rate'] ? number_format($row['daily_rate'], 2) : '—' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-contractor-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-action="con-contractor-delete">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
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

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>

<!-- Import CSV Modal: Import Contractors -->
<div class="modal fade" id="importContractorCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Contractors</h5>
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
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Unique Contractor Name</td>
                                </tr>
                                <tr>
                                    <td><code>Role</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Contractor, Subcontractor, Supervisor</td>
                                </tr>
                                <tr>
                                    <td><code>Trade</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Masonry, Plumbing, etc.</td>
                                </tr>
                                <tr>
                                    <td><code>Phone</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>01700000000</td>
                                </tr>
                                <tr>
                                    <td><code>NID_Number</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Numeric ID</td>
                                </tr>
                                <tr>
                                    <td><code>Address</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Text details</td>
                                </tr>
                                <tr>
                                    <td><code>Daily_Rate</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Numeric (e.g., 500.00)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadContractorSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-contractors-import" method="post" enctype="multipart/form-data">
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
function downloadContractorSampleCsv() {
    const headers = [
        'Name', 'Role', 'Trade', 'Phone', 'NID_Number', 'Address', 'Daily_Rate'
    ];
    const sampleRow = [
        'John Doe', 'Contractor', 'Masonry', '01711223344', '1990123456789', 'Dhaka, Bangladesh', '1200.00'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleRow.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'contractors_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
