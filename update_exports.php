<?php

$files = [
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/attendance-report.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/detailed-summary.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/joining-list.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/resign-list.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/salary-hold.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/summary-report.php',
    'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/workspace.php'
];

foreach ($files as $file) {
    if (!file_exists($file))
        continue;

    $content = file_get_contents($file);

    // Skip if already processed or already has the div format
    if (strpos($content, '<i class="fa-solid fa-file-invoice text-primary') !== false || strpos($content, 'justify-content-between mb-4 border-bottom') !== false && basename($file) !== 'salary-report.php') {
        // Maybe already done, wait, we are generating it now.
    }

    $pageName = "Report";
    if (basename($file) == 'attendance-report.php')
        $pageName = "Attendance Report";
    if (basename($file) == 'detailed-summary.php')
        $pageName = "Detailed Summary";
    if (basename($file) == 'joining-list.php')
        $pageName = "Joining List";
    if (basename($file) == 'resign-list.php')
        $pageName = "Resign List";
    if (basename($file) == 'salary-hold.php')
        $pageName = "Salary Hold";
    if (basename($file) == 'summary-report.php')
        $pageName = "Summary Report";
    if (basename($file) == 'workspace.php')
        $pageName = "Payroll Workplace";

    // Figure out table ID
    preg_match("/<table[^>]*id=['\"]([^'\"]+)['\"]/i", $content, $matches);
    $tableId = $matches[1] ?? 'tableId';

    $hasPdf = strpos($content, 'exportPdfForm') !== false;

    // Build the dynamic UI replacement String
    $uiString = '
        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>' . $pageName . '
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2" onclick="$(\'#' . $tableId . '\').DataTable().button(\'.buttons-csv\').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>';
    if ($hasPdf) {
        // Depending on file, PDF form submit might need JS population hooks. Let's just blindly call click on the datatable PDF button instead of reimplementing form!
        // Wait! The datatable PDF button does complex form prep.
        // We can just trigger the datatable PDF button from JS!
        $uiString .= '
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="$(\'#' . $tableId . '\').DataTable().button(\'.buttons-pdf\').trigger();">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>';
    }
    $uiString .= '
                </div>
            </div>
            <div class="table__wrapper table-responsive">';

    // Replace the opening wrapping tags
    $content = preg_replace('/<div class="card__wrapper">\s*<div class="table__wrapper table-responsive">/s', $uiString, $content);
    // Some files might be slightly different
    $content = preg_replace('/<div class="card__wrapper p-3 bg-white">\s*<div class="table__wrapper table-responsive">/s', $uiString, $content);

    // Modify dom
    $content = str_replace('<"col-sm-6 text-end"Bf>', '<"col-sm-6 text-end"f><"d-none"B>', $content);
    $content = str_replace('<"col-sm-6 text-end"fB>', '<"col-sm-6 text-end"f><"d-none"B>', $content);
    $content = str_replace('Bfrtip', 'f<"d-none"B>rtip', $content);
    $content = str_replace('<"d-flex justify-content-between"lfB>', '<"d-flex justify-content-between"lf><"d-none"B>', $content);

    // Modify buttons classname
    // We want the extend: 'csvHtml5' to have className: 'buttons-csv' so we can trigger it.
    // And for PDF, we need className: 'buttons-pdf'
    $content = preg_replace("/extend:\s*['\"]csvHtml5['\"],\s*text:[^,]+,\s*className:\s*['\"][^'\"]+['\"]/i", "extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv'", $content);

    // For manual button PDF (without extend), add buttons-pdf class
    $content = preg_replace("/text:\s*['\"]<i class=\"fa-solid fa-file-pdf me-1\"><\/i> Export PDF['\"],\s*className:\s*['\"][^'\"]+['\"]/i", "text: 'PDF', className: 'buttons-pdf'", $content);


    file_put_contents($file, $content);
    echo "Updated $file\n";
}
