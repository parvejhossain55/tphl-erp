<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-receivings">Material Receiving</a></li>
                <li class="breadcrumb-item active">New Receiving</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to PO
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-box-seam me-2"></i>Log Material Receiving — PO-<?= $po['id'] ?>
                        <span class="text-muted ms-2">(<?= htmlspecialchars($po['project_name']) ?>)</span>
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-material-receiving-store" method="POST" id="receivingForm" enctype="multipart/form-data">
                        <input type="hidden" name="purchase_order_id" value="<?= $po['id'] ?>">

                        <!-- Header -->
                        <div class="row  mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-select" required>
                                    <option value="">-- Select Warehouse --</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Receiving Date</label>
                                <input type="date" name="receiving_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Received Status</label>
                                <select name="received_status" class="form-select">
                                    <option value="Full">Full</option>
                                    <option value="Partial">Partial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Notes</label>
                                <input type="text" name="notes" class="form-control"
                                       placeholder="Challan/Invoice ref...">
                            </div>
                        </div>

                        </div>

                        <!-- Site Engineer Attachments -->
                        <div class="row  mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Site Engineer Note</label>
                                <textarea name="site_eng_note" class="form-control" rows="2" placeholder="Any remarks regarding the received materials..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Supporting Photo/Document</label>
                                <input type="file" name="site_eng_photo" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                <small class="text-muted">Optional: Upload challan photo or material condition.</small>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter the <strong>actual quantity received</strong> for each material. Items with 0 qty will be skipped.
                        </div>

                        <!-- Items from PO (pending quantities) -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;">#</th>
                                        <th style="width:28%;">Material</th>
                                        <th style="width:12%;">Vendor</th>
                                        <th style="width:12%;" class="text-end">Ordered</th>
                                        <th style="width:12%;" class="text-end">Already Received</th>
                                        <th style="width:12%;" class="text-end text-danger">Pending</th>
                                        <th style="width:19%;">Received Now</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $idx = 0; foreach ($pendingItems as $pi): ?>
                                    <?php if ($pi['pending_qty'] <= 0) continue; ?>
                                    <tr>
                                        <td><?= ++$idx ?></td>
                                        <td class="fw-bold">
                                            <?= htmlspecialchars($pi['material_name']) ?>
                                            <span class="text-muted small">(<?= $pi['unit'] ?>)</span>
                                            <input type="hidden" name="purchase_order_item_id[]" value="<?= $pi['id'] ?>">
                                            <input type="hidden" name="ordered_quantity[]" value="<?= $pi['quantity'] ?>">
                                        </td>
                                        <td class="small"><?= htmlspecialchars($pi['vendor_name'] ?? '—') ?></td>
                                        <td class="text-end"><?= number_format($pi['quantity'], 3) ?></td>
                                        <td class="text-end text-success"><?= number_format($pi['received_total'], 3) ?></td>
                                        <td class="text-end text-danger fw-bold"><?= number_format($pi['pending_qty'], 3) ?></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.001" name="received_quantity[]"
                                                       class="form-control" min="0"
                                                       max="<?= $pi['pending_qty'] ?>"
                                                       value="<?= $pi['pending_qty'] ?>"
                                                       placeholder="0.000">
                                                <span class="input-group-text"><?= $pi['unit'] ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if ($idx === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle text-success me-2"></i>All items already fully received.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($idx > 0): ?>
                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Log Receiving & Proceed to QC
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
