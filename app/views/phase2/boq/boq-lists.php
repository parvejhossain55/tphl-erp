<?php
$base_url = $GLOBALS['base_url'];

function fmtQ($n, $d = 2) {
    return number_format((float)$n, $d);
}
function consumeClass($pct) {
    if ($pct >= 90) return '#dc2626'; // red
    if ($pct >= 60) return '#f59e0b'; // amber
    return '#10b981'; // green
}
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-boq-create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New BOQ
        </a>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Target / Design</th>
                            <th>Version</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th style="min-width:180px">Material Consumption</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($boqs)): ?>
                            <?php foreach ($boqs as $i => $boq):
                                $badge = match ($boq['status']) {
                                    'Approved'  => 'bg-success',
                                    'Rejected'  => 'bg-danger',
                                    'Submitted' => 'bg-info',
                                    default     => 'bg-warning text-dark',
                                };

                                // Consumption stats
                                $boqQty      = (float)($boq['item_count'] > 0 ? 1 : 0); // just for existence check
                                $totOrdered  = (float)($boq['total_ordered']  ?? 0);
                                $totReceived = (float)($boq['total_received'] ?? 0);

                                // Use item_count as base for simple % (received units vs ordered units)
                                // Since we don't have a single "total boq units" scalar, show received/ordered ratio
                                $consumePct  = $totOrdered > 0
                                    ? min(100, round($totReceived / $totOrdered * 100, 1))
                                    : 0;
                                $clr = consumeClass($consumePct);
                            ?>
                            <tr>
                                <td style="font-size:11px;color:#6b7280"><?= $i + 1 ?></td>
                                <td class="fw-bold" style="font-size:13px"><?= htmlspecialchars($boq['project_name']) ?></td>
                                <td style="font-size:12px">
                                    <?php if (!empty($boq['target_title'])): ?>
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="bi bi-bullseye text-primary" style="font-size:11px"></i>
                                            <span class="fw-bold text-primary" style="font-size:11.5px"><?= htmlspecialchars($boq['target_title']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px">General BOQ</span>
                                    <?php endif; ?>
                                    <?php if (!empty($boq['design_type_name'])): ?>
                                        <span style="background:rgba(99,102,241,.1);color:#6366f1;padding:1px 7px;border-radius:6px;font-size:10px;font-weight:700">
                                            <?= htmlspecialchars($boq['design_type_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">v<?= htmlspecialchars($boq['version']) ?></span>
                                </td>
                                <td class="text-center fw-bold"><?= (int)($boq['item_count'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= $badge ?>"><?= $boq['status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($boq['status'] === 'Approved'): ?>
                                        <?php if ($totOrdered > 0 || $totReceived > 0): ?>
                                            <!-- Progress bar: received vs ordered -->
                                            <div style="font-size:10.5px;color:#6b7280;margin-bottom:3px">
                                                <span style="color:#2563eb;font-weight:600">Ordered: <?= fmtQ($totOrdered) ?></span>
                                                &nbsp;·&nbsp;
                                                <span style="color:#059669;font-weight:600">Received: <?= fmtQ($totReceived) ?></span>
                                            </div>
                                            <div style="height:7px;background:rgba(0,0,0,.07);border-radius:4px;overflow:hidden">
                                                <div style="height:100%;border-radius:4px;width:<?= $consumePct ?>%;background:<?= $clr ?>;transition:width .4s"></div>
                                            </div>
                                            <div style="font-size:10px;margin-top:2px;color:<?= $clr ?>;font-weight:700"><?= $consumePct ?>% received</div>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:11px">No orders yet</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px">Not approved</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px"><?= htmlspecialchars($boq['created_by_name'] ?? '—') ?></td>
                                <td class="text-muted" style="font-size:11px"><?= date('d M Y', strtotime($boq['created_at'])) ?></td>
                                <td class="text-center" style="white-space:nowrap">
                                    <a href="<?= $base_url ?>/con-boq-show/<?= $boq['id'] ?>"
                                        class="btn btn-sm btn-outline-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($boq['status'] !== 'Approved'): ?>
                                        <a href="<?= $base_url ?>/con-boq-edit/<?= $boq['id'] ?>"
                                            class="btn btn-sm btn-outline-warning ms-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger ms-1 btn-delete-boq"
                                            data-id="<?= $boq['id'] ?>"
                                            data-name="<?= htmlspecialchars($boq['project_name']) ?>"
                                            title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No BOQ records yet.
                                    <a href="<?= $base_url ?>/con-boq-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBoqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="deleteBoqForm" method="POST" action="<?= $base_url ?>/con-boq-delete">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the BOQ for <strong id="deleteBoqName"></strong>?
                        All associated items will be removed.</p>
                    <input type="hidden" name="id" id="deleteBoqId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-delete-boq').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('deleteBoqId').value = this.getAttribute('data-id');
                document.getElementById('deleteBoqName').textContent = this.getAttribute('data-name');
                new bootstrap.Modal(document.getElementById('deleteBoqModal')).show();
            });
        });
    });
</script>