<?php
$hasCompany = !empty(trim($vendor['company_name'] ?? ''));
$displayVendorName = $hasCompany ? trim($vendor['company_name']) : $vendor['name'];
?>
<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendors">Vendors</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($displayVendorName) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-vendor-edit/<?= $vendor['id'] ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?= $base_url ?>/con-vendors" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- Vendor Info + Stats -->
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">
                    <div class="row g-4">
                        <!-- Vendor Info -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                                     style="width:56px;height:56px;font-size:1.5rem;font-weight:700;">
                                    <?= strtoupper(substr($displayVendorName, 0, 1)) ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($displayVendorName) ?></h5>
                                    <span class="badge <?= ($vendor['status'] === 'active') ? 'bg-success' : (($vendor['status'] === 'blacklisted') ? 'bg-danger' : 'bg-secondary') ?> mt-1">
                                        <?= ucfirst($vendor['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <ul class="list-unstyled mt-3">
                                <li class="mb-2"><i class="bi bi-person me-2 text-muted"></i><strong>Contact Person:</strong> <?= htmlspecialchars($vendor['name'] ?? '—') ?></li>
                                <li class="mb-2"><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($vendor['phone'] ?? '—') ?></li>
                                <li class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i><?= htmlspecialchars($vendor['email'] ?? '—') ?></li>
                                <li class="mb-2"><i class="bi bi-geo-alt me-2 text-muted"></i><?= htmlspecialchars($vendor['address'] ?? '—') ?></li>
                                <li class="mb-2">
                                    <i class="bi bi-person-vcard me-2 text-muted"></i><strong>NID:</strong>
                                    <?= htmlspecialchars($vendor['nid_number'] ?: '—') ?>
                                    <?php if (!empty($vendor['nid_file'])): ?>
                                        <a href="<?= $base_url ?>/<?= htmlspecialchars($vendor['nid_file']) ?>" target="_blank" class="btn btn-xs btn-outline-primary ms-2 py-0 px-2" style="font-size: 11px;">
                                            <i class="bi bi-file-earmark-text me-1"></i>View Doc
                                        </a>
                                    <?php endif; ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-file-earmark-check me-2 text-muted"></i><strong>Trade License:</strong>
                                    <?= htmlspecialchars($vendor['trade_license_number'] ?: '—') ?>
                                    <?php if (!empty($vendor['trade_license_file'])): ?>
                                        <a href="<?= $base_url ?>/<?= htmlspecialchars($vendor['trade_license_file']) ?>" target="_blank" class="btn btn-xs btn-outline-primary ms-2 py-0 px-2" style="font-size: 11px;">
                                            <i class="bi bi-file-earmark-text me-1"></i>View Doc
                                        </a>
                                    <?php endif; ?>
                                </li>
                            </ul>
                            <div class="mt-2">
                                <?php
                                $rating = (float)($vendor['rating'] ?? 0);
                                for ($s = 1; $s <= 5; $s++) {
                                    echo ($s <= round($rating))
                                        ? '<i class="bi bi-star-fill text-warning fs-5"></i>'
                                        : '<i class="bi bi-star text-muted fs-5"></i>';
                                }
                                ?>
                                <span class="ms-1 fw-bold"><?= number_format($rating, 1) ?> / 5.0</span>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="col-md-7">
                            <div class="row ">
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="fs-3 fw-bold text-primary"><?= $stats['po_count'] ?? 0 ?></div>
                                        <div class="small text-muted">Purchase Orders</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <div class="fs-3 fw-bold text-info">৳ <?= number_format($stats['total_value'] ?? 0, 0) ?></div>
                                        <div class="small text-muted">Total PO Value</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3 text-center">
                                        <div class="fs-4 fw-bold text-success"><?= $stats['accepted_items'] ?? 0 ?></div>
                                        <div class="small text-muted">QC Accepted</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3 text-center">
                                        <div class="fs-4 fw-bold text-danger"><?= $stats['rejected_items'] ?? 0 ?></div>
                                        <div class="small text-muted">QC Rejected</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3 text-center">
                                        <div class="fs-4 fw-bold text-warning"><?= $stats['penalty_count'] ?? 0 ?></div>
                                        <div class="small text-muted">Penalties</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Order History -->
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-receipt me-2"></i>Purchase Order History</h6>
                </div>
                <div class="card__body p-0">
                    <?php if (!empty($poHistory)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>PO #</th>
                                    <th>Project</th>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Payment Type</th>
                                    <th class="text-end">Due Amount</th>
                                    <th>PO Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($poHistory as $ph): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $base_url ?>/con-purchase-order/<?= $ph['po_id'] ?>" class="fw-bold text-primary">
                                            PO-<?= $ph['po_id'] ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($ph['project_name'] ?? '—') ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($ph['material_name']) ?></td>
                                    <td><?= $ph['unit'] ?></td>
                                    <td class="text-end"><?= number_format($ph['quantity'], 3) ?></td>
                                    <td class="text-end">৳ <?= number_format($ph['unit_price'], 2) ?></td>
                                    <td class="text-end fw-bold">৳ <?= number_format($ph['quantity'] * $ph['unit_price'], 2) ?></td>
                                    <td>
                                        <?php if (!empty($ph['payment_type'])): ?>
                                            <span class="badge bg-secondary"><?= $ph['payment_type'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ph['due_amount'] > 0): ?>
                                            <span class="fw-bold text-danger">৳ <?= number_format($ph['due_amount'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $poBadge = match($ph['po_status']) {
                                            'Issued'             => 'bg-info',
                                            'Partially Received' => 'bg-warning text-dark',
                                            'Fully Received'     => 'bg-success',
                                            default              => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $poBadge ?>"><?= $ph['po_status'] ?></span>
                                    </td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($ph['order_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox me-2"></i>No purchase order history.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Penalty History -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Penalty History</h6>
                </div>
                <div class="card__body p-0">
                    <?php if (!empty($penalties)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($penalties as $i => $pen): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($pen['penalty_category']) ?></span></td>
                                    <td class="text-end fw-bold text-danger">
                                        <?= ($pen['penalty_amount'] > 0) ? '৳ ' . number_format($pen['penalty_amount'], 2) : '—' ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($pen['reason'] ?? '—') ?></td>
                                    <td><span class="badge bg-secondary"><?= $pen['penalty_status'] ?></span></td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($pen['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle text-success me-2"></i>No penalties. Clean record!
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>
