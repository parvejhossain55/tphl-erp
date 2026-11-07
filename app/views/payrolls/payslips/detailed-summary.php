<?php
$page = "Detailed Summary";
$show_breadcrumb = true;

// Default Month and Year limits for filter
$defaultYear = date('Y');
$defaultMonth = date('m');
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-detailed-summary" id="filterForm"
                class="row gx-3 gy-2 align-items-end">
                <!-- Payroll Project Filter -->
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="payrollProject" class="form-label fw-semibold text-muted mb-2">Select Payroll Project
                        <span class="text-danger">*</span></label>
                    <select class="form-select custom-select2" name="payroll_project_id" id="payrollProject" required>
                        <option value="">-- Choose a Project --</option>
                        <?php if (!empty($payroll_projects)): ?>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>" <?= (isset($payroll_project_id) && $payroll_project_id == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?><?= !empty($proj['date']) ? ' (' . htmlspecialchars($proj['date']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detailed Summary Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Detailed Summary
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#detailedSummaryTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm"
                        onclick="$('#detailedSummaryTable').DataTable().button('.buttons-pdf').trigger();">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <table id="detailedSummaryTable" class="table table-bordered mb-20">
                    <thead class="text-center text-nowrap align-middle bg-light">
                        <tr>
                            <th style="width: 14%">Department</th>
                            <th style="width: 5%">SL</th>
                            <th class="text-start" style="width: 21%">Employee Name</th>
                            <th style="width: 9%">ID</th>
                            <th class="text-start" style="width: 13%">Designation</th>
                            <th class="text-end" style="width: 12%">Special Allowance</th>
                            <th class="text-end" style="width: 13%">Net Payable</th>
                            <th class="text-end" style="width: 13%">Total Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departmentsData)): ?>
                            <?php foreach ($departmentsData as $deptName => $deptInfo): ?>
                                <?php
                                $empCount = count($deptInfo['employees']);
                                $totalPay = number_format($deptInfo['total_payable'], 2);
                                $sl = 1;
                                ?>
                                <?php foreach ($deptInfo['employees'] as $index => $emp): ?>
                                    <?php
                                    $isFirst = ($index === 0);
                                    $isLast = ($index === ($empCount - 1));
                                    $bStyle = '';
                                    if ($empCount > 1) {
                                        if ($isFirst) {
                                            $bStyle = 'border-bottom: 1px solid transparent !important;';
                                        } elseif ($isLast) {
                                            $bStyle = 'border-top: 1px solid transparent !important;';
                                        } else {
                                            $bStyle = 'border-top: 1px solid transparent !important; border-bottom: 1px solid transparent !important;';
                                        }
                                    }
                                    ?>
                                    <tr class="text-center align-middle">

                                        <td class="<?= $isFirst ? 'fw-bold text-dark' : 'text-transparent' ?> bg-white align-top"
                                            style="<?= $bStyle ?>">
                                            <?= $isFirst ? htmlspecialchars($deptName) : '' ?>
                                        </td>

                                        <td><?= $sl++ ?></td>
                                        <td class="text-start"><?= htmlspecialchars($emp['full_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($emp['emp_id'] ?? '') ?></td>
                                        <td class="text-start"><?= htmlspecialchars($emp['designation_name'] ?? '-') ?></td>
                                        <td class="text-end text-nowrap">
                                            <?= number_format($emp['special_allowance'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-end fw-semibold text-nowrap">
                                            <?= number_format($emp['net_salary'] ?? 0, 2) ?>
                                        </td>

                                        <td class="<?= $isFirst ? 'fw-bold text-dark' : 'text-transparent' ?> text-end h5 bg-white align-top text-nowrap"
                                            style="<?= $bStyle ?>">
                                            <?= $isFirst ? $totalPay : '' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($departmentsData)): 
                        $grandTotalEmployees = 0;
                        $grandTotalSpecial = 0;
                        $grandTotalPayable = 0;
                        foreach ($departmentsData as $deptInfo) {
                            $grandTotalEmployees += count($deptInfo['employees']);
                            $grandTotalSpecial += (float)($deptInfo['total_special_allowance'] ?? 0);
                            $grandTotalPayable += (float)$deptInfo['total_payable'];
                        }
                    ?>
                    <tfoot class="table-light fw-bold">
                        <tr class="align-middle">
                            <td colspan="5" class="text-end text-dark fs-14">Grand Total (<?= $grandTotalEmployees ?> Employees):</td>
                            <td class="text-end text-dark fs-14">৳ <?= number_format($grandTotalSpecial, 2) ?></td>
                            <td class="text-end text-success fs-14">৳ <?= number_format($grandTotalPayable, 2) ?></td>
                            <td class="text-end text-success fs-14">৳ <?= number_format($grandTotalPayable, 2) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF Export to Backend -->
<form id="exportPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-detailed-summary-pdf" method="GET" target="_blank"
    style="display:none;">
    <input type="hidden" name="payroll_project_id" id="pdfPayrollProject">
</form>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
$('#payrollProject').on('change', function() {
$('#filterForm').submit();
});

if ($.fn.DataTable.isDataTable('#detailedSummaryTable')) {
$('#detailedSummaryTable').DataTable().destroy();
}

$('#detailedSummaryTable').DataTable({
dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
            '<"row"<"col-sm-12"tr>>' +
                '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                        buttons: [
                        {
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Detailed_Summary_Report'
                        },
                        {
                        text: 'PDF', className: 'buttons-pdf',
                        action: function (e, dt, node, config) {
                        var projId = $('#payrollProject').val();
                        if (!projId) {
                        alert("Please select a project first.");
                        return;
                        }
                        document.getElementById('pdfPayrollProject').value = projId;
                        document.getElementById('exportPdfForm').submit();
                        }
                        }
                        ],
                        language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search list..."
                        },
                        pagingType: "simple_numbers",
                        pageLength: 50,
                        ordering: false
                        });
                        });
                        <?php
                        $page_script = ob_get_clean();
                        ?>