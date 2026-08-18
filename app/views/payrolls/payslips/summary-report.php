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
                    <label for="fromMonth" class="form-label fw-semibold text-muted mb-2">From Month</label>
                    <input type="month" class="form-control" name="from_month" id="fromMonth" value="<?= $from_month ?>"
                        required>
                </div>

                <div class="col-lg-4 col-md-5 col-sm-6">
                    <label for="toMonth" class="form-label fw-semibold text-muted mb-2">To Month</label>
                    <input type="month" class="form-control" name="to_month" id="toMonth" value="<?= $to_month ?>"
                        required>
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
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table id="summaryReportTable" class="table table-bordered mb-20">
                    <thead class="text-center text-nowrap align-middle bg-light">
                        <tr>
                            <th class="text-start bg-white" style="position: sticky; left: 0; z-index: 2;">Particulars
                            </th>
                            <?php foreach ($months as $m): ?>
                                <th class="text-end">
                                    <?= date('F y', strtotime($m . '-01')) ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-end text-success bg-light">Growth in (%)<br>
                                <?= date('F Y', strtotime($secondLastM . '-01')) ?>
                            </th>
                            <th class="text-end text-success bg-light">Growth in (%)<br>
                                <?= date('F Y', strtotime($lastM . '-01')) ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $key => $monthlyValues): ?>
                            <tr class="align-middle text-nowrap">
                                <td class="fw-bold bg-light text-start" style="position: sticky; left: 0; z-index: 1;">
                                    <?= $key ?>
                                </td>
                                <?php foreach ($months as $m): ?>
                                    <td class="text-end">
                                        <?= ($key === 'No of employees') ? (int) ($monthlyValues[$m] ?? 0) : number_format($monthlyValues[$m] ?? 0, 2) ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php
                                $valLast = $monthlyValues[$lastM] ?? 0;
                                $valSecondLast = $monthlyValues[$secondLastM] ?? 0;
                                $valThirdLast = $monthlyValues[$thirdLastM] ?? 0;

                                $growthLast = 0;
                                if ($valSecondLast > 0)
                                    $growthLast = (($valLast - $valSecondLast) / $valSecondLast) * 100;
                                elseif ($valLast > 0)
                                    $growthLast = 100.0;

                                $growthSecondLast = 0;
                                if ($valThirdLast > 0)
                                    $growthSecondLast = (($valSecondLast - $valThirdLast) / $valThirdLast) * 100;
                                elseif ($valSecondLast > 0)
                                    $growthSecondLast = 100.0;
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
    <input type="hidden" name="from_month" id="pdfFromMonth">
    <input type="hidden" name="to_month" id="pdfToMonth">
</form>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
if ($.fn.DataTable.isDataTable('#summaryReportTable')) {
$('#summaryReportTable').DataTable().destroy();
}

$('#summaryReportTable').DataTable({
dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"Bf>>' +
        '<"row"<"col-sm-12"tr>>' +
            '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                    buttons: [
                    {
                    extend: 'csvHtml5',
                    text: '<i class="fa-solid fa-file-csv me-1"></i> Export CSV',
                    className: 'btn btn-success btn-sm me-2',
                    title: 'Payroll_Summary_Report_
                    <?= $from_month ?>_to_
                    <?= $to_month ?>'
                    },
                    {
                    text: '<i class="fa-solid fa-file-pdf me-1"></i> Export PDF',
                    className: 'btn btn-danger btn-sm me-4',
                    action: function (e, dt, node, config) {
                    document.getElementById('pdfFromMonth').value = $('#fromMonth').val();
                    document.getElementById('pdfToMonth').value = $('#toMonth').val();
                    document.getElementById('exportPdfForm').submit();
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
                    });
                    <?php
                    $page_script = ob_get_clean();
                    ?>