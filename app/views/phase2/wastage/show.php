<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-wastage">Wastage Management</a></li>
                <li class="breadcrumb-item active">WST-<?= $wastage['id'] ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Main Details Column -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle text-primary me-2"></i> Wastage Details</h5>
                <?php
                $badge = match($wastage['status']) {
                    'Pending QC'       => 'bg-warning text-dark',
                    'Pending Purchase' => 'bg-info',
                    'Pending Payment'  => 'bg-primary',
                    'Completed'        => 'bg-success',
                    'Rejected'         => 'bg-danger',
                    default            => 'bg-secondary',
                };
                ?>
                <span class="badge <?= $badge ?> fs-6"><?= $wastage['status'] ?></span>
            </div>
            <div class="card-body mt-3">
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Project</small>
                        <span class="fw-bold"><?= htmlspecialchars($wastage['project_name']) ?></span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Warehouse</small>
                        <span class="fw-bold"><?= htmlspecialchars($wastage['warehouse_name']) ?></span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Type</small>
                        <?php if (($wastage['wastage_type'] ?? '') === 'storage_damage'): ?>
                            <span class="badge bg-secondary">Storage Damage</span>
                        <?php else: ?>
                            <span class="badge bg-dark">Selling Scrap</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">Recorded By</small>
                        <span><?= htmlspecialchars($wastage['recorded_by_name']) ?></span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">Record Date</small>
                        <span><?= date('d M Y, h:i A', strtotime($wastage['created_at'])) ?></span>
                    </div>
                    <div class="col-md-12 mb-2">
                        <small class="text-muted d-block">Total Proposed Selling Amount</small>
                        <span class="fw-bold fs-5 text-success">৳ <?= number_format($wastage['total_selling_amount'], 2) ?></span>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mt-4 text-primary">Materials</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mt-2">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Material</th>
                                <th class="text-center">Qty</th>
                                <th>Condition</th>
                                <th class="text-end">Selling Price</th>
                                <th class="text-center">Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-primary"><?= htmlspecialchars($item['material_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold"><?= number_format($item['quantity'], 2) ?></span> <small><?= $item['unit'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($item['condition_note']) ?></td>
                                <td class="text-end fw-bold">৳ <?= number_format($item['selling_amount'], 2) ?></td>
                                <td class="text-center">
                                    <?php if ($item['document']): ?>
                                        <a href="<?= $base_url ?>/<?= $item['document'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View Document">
                                            <i class="bi bi-image"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No Doc</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Action Cards (Approval Forms) -->
        <?php if ($canApproveQC): ?>
        <div class="card shadow-sm border-warning mb-4">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="bi bi-shield-check"></i> QC & Internal Audit Approval
            </div>
            <div class="card-body mt-3">
                <form action="<?= $base_url ?>/con-wastage-approve-qc/<?= $wastage['id'] ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">QC Note / Observation <span class="text-danger">*</span></label>
                        <textarea name="qc_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach Document (Optional)</label>
                        <input type="file" name="qc_doc" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4">Approve as QC</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canApprovePurchase): ?>
        <div class="card shadow-sm border-info mb-4">
            <div class="card-header bg-info text-white fw-bold">
                <i class="bi bi-cart-check"></i> Purchase Approval
            </div>
            <div class="card-body mt-3">
                <form action="<?= $base_url ?>/con-wastage-approve-purchase/<?= $wastage['id'] ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Purchase Note <span class="text-danger">*</span></label>
                        <textarea name="purchase_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach Selling Document (Optional)</label>
                        <input type="file" name="purchase_doc" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-info text-white fw-bold px-4">Approve Selling</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canReceivePayment): ?>
        <div class="card shadow-sm border-primary mb-4">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-cash-coin"></i> Receive Payment & Deduct Stock
            </div>
            <div class="card-body mt-3">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Receiving payment will automatically log the amount to <strong>Management Cash-in</strong> and deduct the material quantities from the selected project's stock.
                </div>
                <form action="<?= $base_url ?>/con-wastage-receive-payment/<?= $wastage['id'] ?>" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank">Bank</option>
                                <option value="MobileWallet">Mobile Wallet</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Document/Receipt <span class="text-danger">*</span></label>
                            <input type="file" name="payment_doc" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Confirm Payment Receipt</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Timeline Sidebar Column -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-secondary me-2"></i> Approval Timeline</h6>
            </div>
            <div class="card-body">
                <div class="timeline mt-3">
                    
                    <!-- Recorded -->
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:30px;height:30px;"><i class="bi bi-check"></i></div>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold">Recorded</h6>
                            <small class="text-muted d-block"><?= htmlspecialchars($wastage['recorded_by_name']) ?></small>
                            <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($wastage['created_at'])) ?></small>
                        </div>
                    </div>

                    <!-- QC -->
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <?php if ($wastage['qc_approved_by']): ?>
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:30px;height:30px;"><i class="bi bi-check"></i></div>
                            <?php else: ?>
                                <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center text-muted" style="width:30px;height:30px;">2</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold <?= $wastage['qc_approved_by'] ? 'text-success' : 'text-muted' ?>">QC Approval</h6>
                            <?php if ($wastage['qc_approved_by']): ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($wastage['qc_approver_name']) ?></small>
                                <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($wastage['qc_approved_at'])) ?></small>
                                <div class="mt-2 p-2 bg-light border rounded small">
                                    <strong>Note:</strong> <?= htmlspecialchars($wastage['qc_note']) ?>
                                    <?php if ($wastage['qc_doc']): ?>
                                        <br><a href="<?= $base_url ?>/<?= $wastage['qc_doc'] ?>" target="_blank" class="mt-1 d-inline-block"><i class="bi bi-paperclip"></i> Attached Doc</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Purchase -->
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <?php if ($wastage['purchase_approved_by']): ?>
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:30px;height:30px;"><i class="bi bi-check"></i></div>
                            <?php else: ?>
                                <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center text-muted" style="width:30px;height:30px;">3</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold <?= $wastage['purchase_approved_by'] ? 'text-success' : 'text-muted' ?>">Purchase Approval</h6>
                            <?php if ($wastage['purchase_approved_by']): ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($wastage['purchase_approver_name']) ?></small>
                                <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($wastage['purchase_approved_at'])) ?></small>
                                <div class="mt-2 p-2 bg-light border rounded small">
                                    <strong>Note:</strong> <?= htmlspecialchars($wastage['purchase_note']) ?>
                                    <?php if ($wastage['purchase_doc']): ?>
                                        <br><a href="<?= $base_url ?>/<?= $wastage['purchase_doc'] ?>" target="_blank" class="mt-1 d-inline-block"><i class="bi bi-paperclip"></i> Attached Doc</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="d-flex">
                        <div class="me-3">
                            <?php if ($wastage['payment_received_by']): ?>
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:30px;height:30px;"><i class="bi bi-check"></i></div>
                            <?php else: ?>
                                <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center text-muted" style="width:30px;height:30px;">4</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold <?= $wastage['payment_received_by'] ? 'text-success' : 'text-muted' ?>">Payment Received</h6>
                            <?php if ($wastage['payment_received_by']): ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($wastage['accounts_receiver_name']) ?></small>
                                <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($wastage['payment_received_at'])) ?></small>
                                <div class="mt-2 p-2 bg-light border rounded small">
                                    <strong>Method:</strong> <?= htmlspecialchars($wastage['payment_method']) ?>
                                    <?php if ($wastage['payment_doc']): ?>
                                        <br><a href="<?= $base_url ?>/<?= $wastage['payment_doc'] ?>" target="_blank" class="mt-1 d-inline-block"><i class="bi bi-paperclip"></i> Attached Doc</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
