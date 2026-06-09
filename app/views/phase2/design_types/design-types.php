<?php
$page_title = "Design Types";
$page = 'Design Types';
$addPage = true;
$show_breadcrumb = true;
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = ($roleId === 16);
?>

<div class="row justify-content-end mb-3 mt-3">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <?php if ($canModify): ?>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addDesignTypeModal">
                    <i class="fa-light fa-plus me-1"></i> Add Design Type
                </button>
            
                <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importDesignTypeCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover ">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Drawing Type Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="table__icon-box">
                                        <?php if ($canModify): ?>
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <button type="button"
                                                class="table__icon edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editDesignTypeModal"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-status="<?= $row['status'] ?>">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </button>
                                             <button class="removeBtnCust table__icon delete"
                                                 data-id="<?= $row['id'] ?>"
                                                 data-name="<?= htmlspecialchars($row['name']) ?>"
                                                 data-action="delete-design-type">
                                                 <i class="fa-regular fa-trash"></i>
                                             </button>
                                        </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No design types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Design Type Modal -->
<div class="modal fade" id="addDesignTypeModal" tabindex="-1" aria-labelledby="addDesignTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDesignTypeLabel">Add Design Type</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="employee__info">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/store" method="POST">
                        <div class="card__wrapper mb-20">
                            <div class="row g-20 gy-20">
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label>Design Type Name <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input type="text" class="form-control" name="name" required placeholder="e.g. Architectural, Structural">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label>Status</label>
                                        </div>
                                        <div class="form__input">
                                            <select name="status" class="form-control">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="submit__btn text-center">
                            <button class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Design Type Modal -->
<div class="modal fade" id="editDesignTypeModal" tabindex="-1" aria-labelledby="editDesignTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDesignTypeLabel">Edit Design Type</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/update" method="POST">
                    <input type="hidden" name="id" id="edit_design_type_id">
                    <div class="card__wrapper mb-20">
                        <div class="row g-20 gy-20">
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label>Design Type Name <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input type="text" class="form-control" name="name" id="edit_design_type_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label>Status</label>
                                    </div>
                                    <div class="form__input">
                                        <select name="status" id="edit_design_type_status" class="form-control">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Import CSV Modal: Import Design Types -->
<div class="modal fade" id="importDesignTypeCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Design Types</h5>
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
                                    <td><code>Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Any text</td>
                                    <td><em>Architectural</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadDesignTypeSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-design-type-import" method="post" enctype="multipart/form-data">
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
function downloadDesignTypeSampleCsv() {
    const headers = ['Name'];
    const sample  = ['Architectural'];
    const csvContent = [headers.join(','), sample.map(v => '"' + v + '"').join(',')].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'design_types_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const editModal = document.getElementById('editDesignTypeModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('edit_design_type_id').value      = btn.getAttribute('data-id');
                document.getElementById('edit_design_type_name').value    = btn.getAttribute('data-name');
                document.getElementById('edit_design_type_status').value  = btn.getAttribute('data-status');
            });
        }

        // Delete functionality is handled by global .removeBtnCust handler in main.js
    });
</script>
