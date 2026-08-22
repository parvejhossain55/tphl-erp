<?php
$page_title = "email station ";
$page = 'Email Station';
$show_breadcrumb = true;
?>

<form action="" method="get" class="mb-20">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Email Station</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $GLOBALS['base_url'] ?>/create-email-template" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Add Template
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/create-email-alert" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Create Email Alert
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>S/L</th>
                                <th>Template Name</th>
                                <th>Target Audience</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php foreach ($emailAlerts as $index => $et): ?>
                                <tr>
                                    <!-- Serial number -->
                                    <td>#<?= $index + 1 ?></td>
                                    <td>
                                        <?= htmlspecialchars($et['template_name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($et['target_type']) ?></td>
                                    <td>
                                        <?php if ($et['status'] == 'sent'): ?>
                                            <span class="bd-badge bg-success">Sent</span>
                                        <?php elseif ($et['status'] == 'draft'): ?>
                                            <span class="bd-badge bg-warning">Draft</span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-danger"><?php $et['status'] ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action buttons -->
                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <button type="button" class="table__icon edit" data-bs-toggle="modal"
                                                data-bs-target="#editDepartment" data-id="<?= $dept['id'] ?>"
                                                data-name="<?= htmlspecialchars($dept['name']) ?>"
                                                data-status="<?= $dept['status'] ?>">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </button>

                                            <button type="button" class="removeBtnCust table__icon delete"
                                                data-id="<?= $dept['id'] ?>" data-name="<?= $dept['name'] ?>"
                                                data-action="delete-department">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>