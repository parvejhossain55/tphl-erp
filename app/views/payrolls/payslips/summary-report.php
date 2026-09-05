<?php
$page = "Payroll Summary Report";
$show_breadcrumb = true;

if (!function_exists('formatGrowthNode')) {
    function formatGrowthNode($val)
    {
        $str = number_format($val, 2) . '%';
        if ($val > 0)
            return '<span class="text-success fw-bold">+' . $str . '</span>';
        if ($val < 0)
            return '<span class="text-danger fw-bold">' . $str . '</span>';
        return '<span class="text-muted">' . $str . '</span>';
    }
}
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-summary-report" id="filterForm"
                class="row gx-3 gy-2 align-items-end">

                <div class="col-lg-4 col-md-5 col-sm-6">
                    <label for="fromProject" class="form-label fw-semibold text-muted mb-2">From Project <span
                            class="text-danger">*</span></label>
                    <select class="form-select" name="from_project_id" id="fromProject" required>
                        <option value="">Select Project</option>
                        <?php foreach ($payroll_projects as $proj): ?>
                            <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($from_project_id == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-4 col-md-5 col-sm-6">
                    <label for="toProject" class="form-label fw-semibold text-muted mb-2">To Project <span
                            class="text-danger">*</span></label>
                    <select class="form-select" name="to_project_id" id="toProject" required>
                        <option value="">Select Project</option>
                        <?php foreach ($payroll_projects as $proj): ?>
                            <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($to_project_id == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center">
                        <i class="bi bi-search me-1"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Summary Report Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Summary Report
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#summaryReportTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="exportSummaryPdf()">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <table id="summaryReportTable" class="table table-bordered mb-20">
                    <thead class="text-center text-nowrap align-middle bg-light">
                        <tr>
                            <th class="text-start bg-white" style="position: sticky; left: 0; z-index: 2;">Particulars
                            </th>
                            <?php foreach ($targetProjects as $proj): ?>
                                <th class="text-end">
                                    <?= htmlspecialchars($proj['name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($proj['date'] ?? '') ?></small>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-end text-success bg-light">Growth in (%)<br>Before Last</th>
                            <th class="text-end text-success bg-light">Growth in (%)<br>Last</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $key => $monthlyValues): ?>
                            <tr class="align-middle text-nowrap">
                                <td class="fw-bold bg-light text-start" style="position: sticky; left: 0; z-index: 1;">
                                    <?= htmlspecialchars($key) ?>
                                </td>
                                <?php foreach ($targetProjects as $proj): ?>
                                    <td class="text-end">
                                        <?php
                                        $pid = $proj['id'];
                                        $val = $monthlyValues[$pid] ?? 0;
                                        echo ($key === 'No of employees') ? (int) $val : number_format($val, 2);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php
                                $count = count($targetProjects);
                                $valLast = ($count >= 1) ? ($monthlyValues[$targetProjects[$count - 1]['id']] ?? 0) : 0;
                                $valSecondLast = ($count >= 2) ? ($monthlyValues[$targetProjects[$count - 2]['id']] ?? 0) : 0;
                                $valThirdLast = ($count >= 3) ? ($monthlyValues[$targetProjects[$count - 3]['id']] ?? 0) : 0;

                                $growthLast = 0;
                                if ($valSecondLast != 0) {
                                    $growthLast = ($valLast - $valSecondLast) / $valSecondLast * 100;
                                }

                                $growthSecondLast = 0;
                                if ($valThirdLast != 0) {
                                    $growthSecondLast = ($valSecondLast - $valThirdLast) / $valThirdLast * 100;
                                }
                                ?>
                                <td class="text-end" style="background-color: #f8fcf8;">
                                    <?= formatGrowthNode($growthSecondLast) ?>
                                </td>
                                <td class="text-end" style="background-color: #f8fcf8;">
                                    <?= formatGrowthNode($growthLast) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF Export -->
<form id="exportPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-summary-report-pdf" method="GET" target="_blank"
    style="display:none;">
    <input type="hidden" name="from_project_id" id="pdfFromProjectId">
    <input type="hidden" name="to_project_id" id="pdfToProjectId">
</form>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
if ($.fn.DataTable.isDataTable('#summaryReportTable')) {
$('#summaryReportTable').DataTable().destroy();
}

$('#summaryReportTable').DataTable({
dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
            '<"row"<"col-sm-12"tr>>' +
                '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                        buttons: [
                        {
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Payroll_Summary_Report_<?= time() ?>'
                        },
                        {
                            text: 'PDF', className: 'buttons-pdf',
                            action: function (e, dt, node, config) {
                                exportSummaryPdf();
                            }
                        }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search report..."
                    },
                    paging: false,
                    ordering: false,
                    info: false
                });

                window.exportSummaryPdf = function () {
                    var fpid = $('#fromProject').val();
                    var tpid = $('#toProject').val();
                    if (!fpid || !tpid) {
                        Swal.fire('Error', 'Please select both From and To projects first.', 'error');
                        return;
                    }
                    document.getElementById('pdfFromProjectId').value = fpid;
                    document.getElementById('pdfToProjectId').value = tpid;
                    document.getElementById('exportPdfForm').submit();
                };
            });
                        <?php
                        $page_script = ob_get_clean();
                        ?>