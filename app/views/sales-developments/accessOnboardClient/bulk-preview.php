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
            <a href="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-upload" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Upload
            </a>
        </div>

        <!-- Summary counts -->
        <?php
        $totalRows  = count($previewData);
        $errorRows  = count(array_filter($previewData, fn($r) => !empty($r['error'])));
        $validCount = $totalRows - $errorRows;
        ?>
        <div class="row g-3 mb-20">
            <div class="col-md-4">
                <div class="card__wrapper text-center py-3">
                    <div class="fs-4 fw-bold text-dark"><?= $totalRows ?></div>
                    <div class="text-muted small">Total Rows</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card__wrapper text-center py-3 border border-success">
                    <div class="fs-4 fw-bold text-success"><?= $validCount ?></div>
                    <div class="text-muted small">Ready to Upload</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card__wrapper text-center py-3 border border-danger">
                    <div class="fs-4 fw-bold text-danger"><?= $errorRows ?></div>
                    <div class="text-muted small">Errors / Skipped</div>
                </div>
            </div>
        </div>

        <div class="card__wrapper mb-20">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="bg-light text-center">
                        <tr>
                            <th style="min-width:50px;">Row</th>
                            <th style="min-width:180px;">Project Name</th>
                            <th style="min-width:100px;">Flat</th>
                            <th style="min-width:180px;">
                                <i class="fa-solid fa-user-check text-success me-1"></i>
                                Lead Found (Name)
                            </th>
                            <th style="min-width:130px;">Lead Phone</th>
                            <th style="min-width:60px;">Bookings</th>
                            <th style="min-width:180px;">Login Email</th>
                            <th style="min-width:130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewData as $row):
                            $isError = !empty($row['error']);
                            if (!$isError) $hasValidRows = true;
                        ?>
                        <tr class="<?= $isError ? 'table-danger' : 'table-success' ?>">
                            <td class="text-center fw-bold"><?= htmlspecialchars($row['row_num']) ?></td>

                            <!-- Project -->
                            <td><?= htmlspecialchars($row['project_name']) ?></td>

                            <!-- Flat -->
                            <td class="text-center"><?= htmlspecialchars($row['flat_name']) ?></td>

                            <!-- Lead Name found -->
                            <td>
                                <?php if (!$isError && $row['lead_name']): ?>
                                    <span class="text-success fw-semibold">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        <?= htmlspecialchars($row['lead_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Lead Phone -->
                            <td class="text-center">
                                <?= $row['lead_phone'] ? htmlspecialchars($row['lead_phone']) : '<span class="text-muted">—</span>' ?>
                            </td>

                            <!-- Booking count -->
                            <td class="text-center">
                                <?php if ($row['booking_count'] > 0): ?>
                                    <span class="badge bg-success"><?= $row['booking_count'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Login email -->
                            <td><?= htmlspecialchars($row['login_email']) ?></td>

                            <!-- Status / Error -->
                            <td>
                                <?php if ($isError): ?>
                                    <span class="text-danger fw-bold">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>
                                        <?= htmlspecialchars($row['error']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success fw-bold">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        Ready to Upload
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($previewData)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No data found in the CSV file.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($hasValidRows): ?>
            <!-- Confirmation box for valid rows -->
            <div class="card__wrapper mb-20 border border-success">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa-solid fa-circle-info text-success fs-4 mt-1"></i>
                    <div>
                        <div class="fw-bold text-dark mb-1">Confirm Upload</div>
                        <p class="mb-0 text-muted">
                            মোট <strong class="text-success"><?= $validCount ?></strong>টি valid row পাওয়া গেছে।
                            শুধুমাত্র <span class="text-success fw-semibold">✔ Ready to Upload</span> rows গুলো save হবে।
                            Error rows গুলো skip হবে।
                        </p>
                    </div>
                </div>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-store" method="POST">
                <input type="hidden" name="valid_data" value="<?= htmlspecialchars(json_encode($validData), ENT_QUOTES, 'UTF-8') ?>">
                <div class="submit__btn text-end d-flex justify-content-end gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-upload" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Re-upload CSV
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-upload me-1"></i>
                        Confirm &amp; Upload <?= $validCount ?> Valid Client<?= $validCount > 1 ? 's' : '' ?>
                    </button>
                </div>
            </form>

        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-xmark me-2"></i>
                No valid rows available to upload. Please fix the errors in your CSV and try again.
            </div>
            <div class="text-end">
                <a href="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-upload" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Upload
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>
