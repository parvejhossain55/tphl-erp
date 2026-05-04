<?php
$file = 'C:/xampp/htdocs/TPHL-ERP/app/views/phase2/vendor-penalties/penalty-lists.php';
$content = file_get_contents($file);

$tableRowAction = <<<EOD
                                <td class="text-center">
                                    <?php if (\$pen['penalty_status'] === 'Pending'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                onclick="reviewPenalty(<?= \$pen['id'] ?>, '<?= htmlspecialchars(addslashes(\$pen['vendor_name'])) ?>', <?= \$pen['penalty_amount'] ?>)">
                                            <i class="bi bi-check2-circle"></i> Review
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDeletePenalty(<?= \$pen['id'] ?>, '<?= htmlspecialchars(addslashes(\$pen['vendor_name'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
EOD;

// Update the action column in table
$content = preg_replace('/<td class="text-center">\s*<button type="button" class="btn btn-sm btn-outline-danger"[\s\S]*?<\/td>/i', $tableRowAction, $content);

// Add the review modal at the end
$modalCode = <<<EOD

<!-- Review Penalty Modal -->
<div class="modal fade" id="reviewPenaltyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Review Penalty</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= \$base_url ?>/con-vendor-penalty-update-status" method="POST">
                <div class="modal-body">
                    <p>Reviewing penalty for <strong id="reviewPenaltyVendor" class="text-primary"></strong>.</p>
                    <input type="hidden" name="id" id="reviewPenaltyId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Penalty Amount (৳)</label>
                        <input type="number" step="0.01" name="penalty_amount" id="reviewPenaltyAmount" class="form-control" required>
                        <small class="text-muted">Enter 0 to waive without fine, or set an amount to deduct.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Decision</label>
                        <select name="penalty_status" class="form-select" required>
                            <option value="Approved">Enforce Penalty (Deduct from Bill)</option>
                            <option value="Rejected">Waive / Cancel Penalty</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function reviewPenalty(id, vendorName, currentAmount) {
    document.getElementById('reviewPenaltyId').value = id;
    document.getElementById('reviewPenaltyVendor').textContent = vendorName;
    document.getElementById('reviewPenaltyAmount').value = currentAmount;
    new bootstrap.Modal(document.getElementById('reviewPenaltyModal')).show();
}
EOD;

$content = str_replace("<script>\nfunction confirmDeletePenalty", $modalCode . "\nfunction confirmDeletePenalty", $content);
$content = str_replace("<script>\r\nfunction confirmDeletePenalty", $modalCode . "\r\nfunction confirmDeletePenalty", $content);

file_put_contents($file, $content);
echo "Done";
