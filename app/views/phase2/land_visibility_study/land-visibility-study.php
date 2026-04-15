

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard">Home</a></li>
                    <li class="breadcrumb-item">Pre-Construction</li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($title) ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/create" class="btn btn-primary shadow-sm"><i class="bi bi-plus-circle me-1"></i> Add New</a>
        </div>
    </div>

    <!-- MAIN BODY -->
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card__wrapper shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project Name</th>
                                    <th>Doc Status</th>
                                    <th>Survey Report</th>
                                    <th>Govt Approval</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($studies)): ?>
                                    <?php foreach ($studies as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><span class="badge bg-<?= $row['land_legal_doc_status'] == 'Approved' ? 'success' : ($row['land_legal_doc_status'] == 'Rejected' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($row['land_legal_doc_status']) ?></span></td>
                                            <td><?= $row['digital_survey_report_link'] ? '<a href="'.htmlspecialchars($row['digital_survey_report_link']).'" target="_blank">View</a>' : 'N/A' ?></td>
                                            <td><?= $row['govt_approval_doc_link'] ? '<a href="'.htmlspecialchars($row['govt_approval_doc_link']).'" target="_blank">View</a>' : 'N/A' ?></td>
                                            <td>
                                                <a href="<?= $GLOBALS['base_url'] ?>/con-land-visibility-studies/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

