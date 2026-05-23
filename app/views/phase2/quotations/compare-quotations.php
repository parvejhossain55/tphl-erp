<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>">PR-<?= $pr['id'] ?></a></li>
                <li class="breadcrumb-item active">CST Comparison</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to PR
    </a>
</div>

<?php
$materials = $comparison['materials'] ?? [];
$vendors   = $comparison['vendors']   ?? [];
$matrix    = $comparison['matrix']    ?? [];
$totals    = $comparison['totals']    ?? [];
$vendorIds = array_keys($vendors);
?>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-layout-three-columns me-2"></i>
                        Comparative Statement — <?= htmlspecialchars($pr['project_name']) ?>
                    </h6>
                </div>
                <div class="card__body p-4">

                    <?php if (empty($vendors) || count($vendors) < 2): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            At least 2 vendor quotations are needed for comparison.
                            <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>" class="ms-2">Add Quotation →</a>
                        </div>
                    <?php else: ?>

                    <form action="<?= $base_url ?>/con-quotation-select" method="POST" id="cstForm">
                        <input type="hidden" name="request_id" value="<?= $pr['id'] ?>">

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center">
                                <thead>
                                    <tr class="table-dark">
                                        <th class="text-start" style="width:5%;">#</th>
                                        <th class="text-start" style="width:25%;">Material</th>
                                        <th style="width:10%;">PR Qty</th>
                                        <?php foreach ($vendors as $vid => $vname): ?>
                                            <th style="min-width:150px;">
                                                <?= htmlspecialchars($vname) ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <th style="width:15%;">Select Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $idx => $mat): ?>
                                    <?php
                                    $mid = $mat['material_id'];
                                    // Find the lowest price vendor
                                    $lowestPrice  = PHP_FLOAT_MAX;
                                    $lowestVendor = 0;
                                    foreach ($vendorIds as $vid) {
                                        if (isset($matrix[$mid][$vid])) {
                                            if ($matrix[$mid][$vid]['unit_price'] < $lowestPrice) {
                                                $lowestPrice  = $matrix[$mid][$vid]['unit_price'];
                                                $lowestVendor = $vid;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td class="text-start"><?= $idx + 1 ?></td>
                                        <td class="text-start fw-bold">
                                            <?= htmlspecialchars($mat['material_name']) ?>
                                            <span class="text-muted small">(<?= $mat['unit'] ?>)</span>
                                            <input type="hidden" name="sel_material_id[]" value="<?= $mid ?>">
                                        </td>
                                        <td><?= number_format($mat['quantity'], 3) ?></td>
                                        <?php foreach ($vendorIds as $vid): ?>
                                            <?php
                                            $cell = $matrix[$mid][$vid] ?? null;
                                            $isLowest = ($vid === $lowestVendor && $cell);
                                            ?>
                                            <td class="<?= $isLowest ? 'table-success fw-bold' : '' ?>">
                                                <?php if ($cell): ?>
                                                    ৳ <?= number_format($cell['unit_price'], 2) ?>
                                                    <div class="small text-muted">
                                                        Total: ৳ <?= number_format($cell['total_price'], 2) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td>
                                            <select name="selected_vendor[]" class="form-select form-select-sm cst-vendor-select" required
                                                    data-material-index="<?= $idx ?>">
                                                <option value="">Select</option>
                                                <?php foreach ($vendorIds as $vid): ?>
                                                    <?php if (isset($matrix[$mid][$vid])): ?>
                                                        <option value="<?= $vid ?>"
                                                                data-price="<?= $matrix[$mid][$vid]['unit_price'] ?>"
                                                                <?= ($vid === $lowestVendor) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($vendors[$vid]) ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="sel_unit_price[]"
                                                   class="cst-selected-price"
                                                   value="<?= ($lowestVendor && isset($matrix[$mid][$lowestVendor])) ? $matrix[$mid][$lowestVendor]['unit_price'] : 0 ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">Vendor Totals:</td>
                                        <?php foreach ($vendorIds as $vid): ?>
                                            <td>৳ <?= number_format($totals[$vid] ?? 0, 2) ?></td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="button" id="btnConfirmCST" class="btn btn-success px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Confirm Selections
                            </button>
                        </div>
                    </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // When vendor selection changes, update the hidden price field
    document.querySelectorAll('.cst-vendor-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var opt   = this.options[this.selectedIndex];
            var price = opt.getAttribute('data-price') || 0;
            this.closest('td').querySelector('.cst-selected-price').value = price;
        });
    });

    // SweetAlert confirmation for CST form
    var confirmBtn = document.getElementById('btnConfirmCST');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            var form = document.getElementById('cstForm');
            // Check form validity first
            var selects = form.querySelectorAll('select[required]');
            var allSelected = true;
            selects.forEach(function(s) { if (!s.value) allSelected = false; });
            if (!allSelected) {
                Swal.fire({ icon: 'error', title: 'Missing Selection', text: 'Please select a vendor for every material row.' });
                return;
            }
            Swal.fire({
                title: 'Confirm Vendor Selections?',
                text: 'This will finalize vendor choices and enable Purchase Order generation.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Confirm!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    }
});
</script>
