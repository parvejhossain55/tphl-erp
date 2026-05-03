<?php
$page_title = "CSV Import Results";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;

$base_url = $GLOBALS['base_url'];
$filename = $filename ?? '';
$success_count = $success_count ?? 0;
$error_count = $error_count ?? 0;
$total_rows = $total_rows ?? 0;
$import_results = $import_results ?? [];
$import_errors = $import_errors ?? [];
$parse_errors = $parse_errors ?? [];
?>

<style>
    .result-hero {
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        margin-bottom: 24px;
    }
    .result-hero.success {
        background: linear-gradient(135deg, #d4edda 0%, #f0fff0 100%);
        border: 1px solid #c3e6cb;
    }
    .result-hero.partial {
        background: linear-gradient(135deg, #fff3cd 0%, #fffef5 100%);
        border: 1px solid #ffc107;
    }
    .result-hero.failure {
        background: linear-gradient(135deg, #f8d7da 0%, #fff5f5 100%);
        border: 1px solid #f5c6cb;
    }
    .result-hero .big-icon {
        font-size: 4rem;
        margin-bottom: 16px;
    }
    .result-hero h3 {
        margin-bottom: 8px;
    }
    .result-stat {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.95rem;
        margin: 4px;
    }
    .result-table th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8f9fa;
    }
    .result-table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
</style>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30 d-flex justify-content-between align-items-center">
                <h3 class="card__title">
                    <i class="fa-light fa-circle-check me-2" style="color:#198754;"></i>
                    Import Results
                </h3>
                <div class="d-flex gap-2">
                    <a href="<?= $base_url ?>/csv-import-form" class="btn btn-outline-primary btn-sm">
                        <i class="fa-light fa-upload me-1"></i> Import Another CSV
                    </a>
                    <a href="<?= $base_url ?>/clients-cash-in" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-light fa-arrow-left me-1"></i> Back to Clients
                    </a>
                </div>
            </div>

            <div class="card__body">
                <!-- Result Hero Section -->
                <?php
                $heroClass = $error_count === 0 ? 'success' : ($success_count > 0 ? 'partial' : 'failure');
                $heroIcon = $error_count === 0 ? 'fa-circle-check' : ($success_count > 0 ? 'fa-triangle-exclamation' : 'fa-circle-xmark');
                $heroColor = $error_count === 0 ? '#198754' : ($success_count > 0 ? '#fd7e14' : '#dc3545');
                ?>
                <div class="result-hero <?= $heroClass ?>">
                    <div class="big-icon" style="color:<?= $heroColor ?>;">
                        <i class="fa-light <?= $heroIcon ?>"></i>
                    </div>
                    <h3>
                        <?php if ($error_count === 0): ?>
                            All Payments Imported Successfully!
                        <?php elseif ($success_count > 0): ?>
                            Import Completed With Errors
                        <?php else: ?>
                            Import Failed
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted mb-3">File: <strong><?= htmlspecialchars($filename) ?></strong></p>

                    <div>
                        <span class="result-stat" style="background:rgba(25,135,84,0.1);color:#198754;">
                            <i class="fa-light fa-check"></i> <?= $success_count ?> Imported
                        </span>
                        <?php if ($error_count > 0): ?>
                        <span class="result-stat" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                            <i class="fa-light fa-xmark"></i> <?= $error_count ?> Failed
                        </span>
                        <?php endif; ?>
                        <span class="result-stat" style="background:rgba(108,95,252,0.1);color:#6c5ffc;">
                            <i class="fa-light fa-list"></i> <?= $total_rows ?> Total
                        </span>
                    </div>
                </div>

                <!-- Successfully Imported Rows -->
                <?php if (!empty($import_results)): ?>
                <div class="mb-4">
                    <h6 class="mb-3">
                        <i class="fa-light fa-check-double me-2 text-success"></i>
                        Successfully Imported (<?= count($import_results) ?>)
                    </h6>
                    <div style="max-height:400px;overflow-y:auto;border-radius:12px;border:1px solid #dee2e6;">
                        <table class="table table-hover result-table mb-0">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Flat</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Payment ID</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results as $r): ?>
                                <tr>
                                    <td><?= $r['row_num'] ?></td>
                                    <td><?= htmlspecialchars($r['client']) ?></td>
                                    <td><?= htmlspecialchars($r['project']) ?></td>
                                    <td><?= htmlspecialchars($r['flat']) ?></td>
                                    <td><strong>৳<?= number_format($r['amount']) ?></strong></td>
                                    <td>
                                        <?php if ($r['type'] === 'emi'): ?>
                                            <span class="badge bg-info">EMI</span>
                                        <?php elseif ($r['type'] === 'emi_due_settlement'): ?>
                                            <span class="badge bg-danger">Settlement</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Dev Charge</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success">#<?= $r['payment_id'] ?></span></td>
                                    <td>
                                        <a href="<?= $base_url ?>/clients-cash-in-history?booking_id=<?= $r['booking_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fa-light fa-external-link me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Parse Errors -->
                <?php if (!empty($parse_errors)): ?>
                <div class="mb-4">
                    <h6 class="mb-3">
                        <i class="fa-light fa-file-exclamation me-2 text-warning"></i>
                        Parse/Validation Errors (<?= count($parse_errors) ?>)
                    </h6>
                    <div class="alert alert-warning">
                        <?php foreach ($parse_errors as $err): ?>
                        <div class="mb-2 p-2 rounded" style="background:rgba(255,193,7,0.1);">
                            <strong>Row <?= $err['row'] ?>:</strong>
                            <?php if (isset($err['messages'])): ?>
                                <?= implode(', ', $err['messages']) ?>
                            <?php elseif (isset($err['message'])): ?>
                                <?= htmlspecialchars($err['message']) ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Import Errors -->
                <?php if (!empty($import_errors)): ?>
                <div class="mb-4">
                    <h6 class="mb-3">
                        <i class="fa-light fa-circle-xmark me-2 text-danger"></i>
                        Import Errors (<?= count($import_errors) ?>)
                    </h6>
                    <div style="max-height:300px;overflow-y:auto;border-radius:12px;border:1px solid #f5c6cb;">
                        <table class="table table-hover result-table mb-0">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Flat</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_errors as $e): ?>
                                <tr class="table-danger">
                                    <td><?= $e['row_num'] ?></td>
                                    <td><?= htmlspecialchars($e['client']) ?></td>
                                    <td><?= htmlspecialchars($e['project']) ?></td>
                                    <td><?= htmlspecialchars($e['flat']) ?></td>
                                    <td>৳<?= number_format($e['amount']) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $e['type'])) ?></td>
                                    <td><small class="text-danger"><?= htmlspecialchars($e['message']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
