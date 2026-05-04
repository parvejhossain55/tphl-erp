<?php
$addPage = true;
$show_breadcrumb = false;
?>




<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4 class="mb-2">Land Visibility Study</h4>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/create" class="btn btn-primary text-nowrap">
                        <i class="fa-light fa-plus me-1"></i> Add New Study
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importLandVisibilityCsvModal">
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
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Legal Doc Status</th>
                            <th>Survey Report</th>
                            <th>Govt Approval</th>
                            <th class="d-none">QC Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($studies)): ?>
                            <?php foreach ($studies as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php
                                        $docBadge = match ($row['land_legal_doc_status']) {
                                            'Verified' => 'success',
                                            'Rejected' => 'danger',
                                            default    => 'warning'
                                        };
                                        ?>
                                        <span class="bd-badge bg-<?= $docBadge ?>"><?= $row['land_legal_doc_status'] ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['digital_survey_report_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['digital_survey_report_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['govt_approval_doc_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['govt_approval_doc_link']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none">
                                        <?php
                                        $qcBadge = match ($row['qc_approval_status'] ?? 'Pending') {
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            default    => 'secondary'
                                        };
                                        ?>
                                        <span class="bd-badge bg-<?= $qcBadge ?>"><?= $row['qc_approval_status'] ?? 'Pending' ?></span>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-land-visibility-study">
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



<!-- Import CSV Modal: Import Land Visibility Study -->
<div class="modal fade" id="importLandVisibilityCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Land Visibility Study</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-land-visibility-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Project_Name (required), Land_Legal_Doc_Status (Pending/Verified/Rejected), Digital_Survey_Report_Link, Govt_Approval_Doc_Link, Others_Doc_Link</code>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2">
                            <i class="bi bi-upload me-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>