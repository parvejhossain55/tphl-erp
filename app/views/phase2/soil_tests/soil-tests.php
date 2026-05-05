<?php
$addPage = true;
$show_breadcrumb = false;
?>
<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4 class="">Soil Tests</h4>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/create" class="btn btn-primary text-nowrap">
                        <i class="fa-light fa-plus me-1"></i> Add Soil Test
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importSoilTestCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover ">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Site Engineer</th>
                            <th>Test Date</th>
                            <th>Lab Document</th>
                            <th>Field Test Doc</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['engineer_name'] ?? 'N/A') ?></td>
                                    <td><?= $row['test_date'] ? date('d M Y', strtotime($row['test_date'])) : '—' ?></td>
                                    <td>
                                        <?php if (!empty($row['lab_test_document_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['lab_test_document_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['field_test_doc_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['field_test_doc_link']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-soil-tests/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-soil-test">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No soil test records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal: Import Soil Tests -->
<div class="modal fade" id="importSoilTestCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Soil Tests</h5>
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
                                    <td><code>Project_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Exact Project Name</td>
                                    <td><em>The Prime Heights</em></td>
                                </tr>
                                <tr>
                                    <td><code>Test_Date</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>YYYY-MM-DD</td>
                                    <td><em>2026-05-15</em></td>
                                </tr>
                                <tr>
                                    <td><code>Field_Data</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Text details</td>
                                    <td><em>Soil layers checked...</em></td>
                                </tr>
                                <tr>
                                    <td><code>Lab_Test_Document_Link</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Valid URL</td>
                                    <td><em>https://example.com/lab</em></td>
                                </tr>
                                <tr>
                                    <td><code>Field_Test_Doc_Link</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Valid URL</td>
                                    <td><em>https://example.com/field</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadSoilTestSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-soil-test-import" method="post" enctype="multipart/form-data">
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
function downloadSoilTestSampleCsv() {
    const headers = [
        'Project_Name', 'Test_Date', 'Field_Data', 'Lab_Test_Document_Link', 'Field_Test_Doc_Link'
    ];
    const sampleRow = [
        'The Prime Heights', '2026-05-15', 'N-value found 15', 'https://example.com/lab', 'https://example.com/field'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleRow.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'soil_test_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
