<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Land Visibility Study</h4>

<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add New Study
                </a>
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
                            <th>QC Status</th>
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
                                        $docBadge = match($row['land_legal_doc_status']) {
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
                                    <td>
                                        <?php
                                        $qcBadge = match($row['qc_approval_status'] ?? 'Pending') {
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
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No records found.</td>
                            </tr>
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
