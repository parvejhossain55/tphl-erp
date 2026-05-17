<?php
$page_title = "Work Types";
$page = 'Work Types';
$show_breadcrumb = true;
?>

<div class="row justify-content-end mb-3 mt-3">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-create" class="btn btn-primary text-nowrap">
                    <i class="fa-light fa-plus me-1"></i> Add Work Type
                </a>
            
                <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importWorkTypeCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper shadow-sm">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Work Type Name</th>
                            <th>Design Type</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['work_type_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($row['design_type_name'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= ($row['description'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this work type?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="table__icon delete removeBtnCust" style="border:none;background:none">
                                                    <i class="fa-regular fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No work types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Import CSV Modal: Import Work Types -->
<div class="modal fade" id="importWorkTypeCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Work Types</h5>
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
                                    <td><code>Design_Type_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Existing Design Type Name</td>
                                    <td><em>Architectural Design</em></td>
                                </tr>
                                <tr>
                                    <td><code>Work_Type_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Unique Work Type Name</td>
                                    <td><em>Foundation Work</em></td>
                                </tr>
                                <tr>
                                    <td><code>Description</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Text details</td>
                                    <td><em>Details about foundation...</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadWorkTypeSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-import" method="post" enctype="multipart/form-data">
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
function downloadWorkTypeSampleCsv() {
    const headers = [
        'Design_Type_Name', 'Work_Type_Name', 'Description'
    ];
    const sampleRow = [
        'Architectural Design', 'Foundation Work', 'Excavation and foundation laying'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleRow.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'work_types_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>