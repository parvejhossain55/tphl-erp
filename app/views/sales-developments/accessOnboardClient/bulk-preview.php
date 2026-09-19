<?php
$page_title = "Preview Bulk Upload";
$page = 'Preview Bulk Upload';
$show_breadcrumb = true;
$hasValidRows = false;
?>

<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12">
        <div class="card__title-wrap mb-20 d-flex justify-content-between align-items-center">
            <h5 class="card__heading-title">Preview Bulk Upload Data</h5>
            <a href="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-upload" class="btn btn-secondary btn-sm">Cancel</a>
        </div>

        <div class="card__wrapper mb-20">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>Row No</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Login Email</th>
                            <th>Bookings</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewData as $row): ?>
                            <?php 
                                $isError = !empty($row['error']); 
                                if (!$isError) {
                                    $hasValidRows = true;
                                }
                            ?>
                            <tr class="<?= $isError ? 'table-danger' : 'table-success' ?>">
                                <td><?= htmlspecialchars($row['row_num']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['login_email']) ?></td>
                                <td>
                                    <?php if ($row['booking_count'] > 0): ?>
                                        <span class="badge bg-success"><?= $row['booking_count'] ?> Bookings</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isError): ?>
                                        <span class="text-danger fw-bold"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($row['error']) ?></span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><i class="fa fa-check-circle"></i> Ready to Upload</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($previewData)): ?>
                <div class="alert alert-warning mt-3">No data found in the CSV file.</div>
            <?php endif; ?>
        </div>

        <?php if ($hasValidRows): ?>
            <form action="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-store" method="POST">
                <input type="hidden" name="valid_data" value='<?= htmlspecialchars(json_encode($validData), ENT_QUOTES, 'UTF-8') ?>'>
                <div class="submit__btn text-end">
                    <button type="submit" class="btn btn-primary">Confirm & Upload Valid Rows</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">No valid rows available to upload. Please fix the errors in your CSV and try again.</div>
        <?php endif; ?>

    </div>
</div>
