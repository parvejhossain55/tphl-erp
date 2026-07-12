<?php
$page_title = "Tipsoi Day Off Lists";
$page = 'Tipsoi Day Off Lists';
$show_breadcrumb = true;
?>

<style>
.bg-light-success {
    background-color: rgba(25, 135, 84, 0.08) !important;
}
.text-success-custom {
    color: #198754 !important;
}
.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08) !important;
}
.day-off-badge {
    background-color: rgba(25, 135, 84, 0.1) !important;
    color: #198754 !important;
    border: 1px solid rgba(25, 135, 84, 0.2);
    font-weight: 600;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
}
.day-off-badge.weekend {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
    border: 1px solid rgba(220, 53, 69, 0.2);
}
</style>

<div class="day-off-lists-container container-fluid p-0">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mb-25">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Tipsoi Day Off Lists</h4>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">View weekly scheduled rest days for all employees</p>
        </div>
        <div class="d-flex align-items-center gap-10">
            <a href="<?= $base_url ?>/day-off" class="btn btn-success btn-sm fw-semibold d-flex align-items-center gap-1" style="background-color: #198754; border-color: #198754;">
                <i class="fa-solid fa-user-gear"></i> Manage Day Offs
            </a>
            <button id="btnExportCSV" class="btn btn-outline-success btn-sm fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </button>
            <button id="btnExportPDF" class="btn btn-outline-danger btn-sm fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Info Indicator Card -->
    <?php if (isset($from_mock)): ?>
        <div class="card__wrapper mb-20 border-start border-4 <?= $from_mock ? 'border-warning' : 'border-success' ?>">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-15">
                <div class="d-flex align-items-center gap-10">
                    <i class="bi <?= $from_mock ? 'bi-exclamation-circle-fill text-warning' : 'bi-check-circle-fill text-success' ?> fs-4"></i>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">Tipsoi API Status: <?= $from_mock ? 'Offline / Fallback Mode' : 'Connected' ?></h6>
                        <small class="text-muted">
                            <?= $from_mock 
                                ? (!empty($api_error) ? htmlspecialchars($api_error) : 'Using local ERP fallback database because the Tipsoi API is offline or unreachable.') 
                                : 'Authenticated successfully with Bearer Token and fetched latest live employees.' ?>
                        </small>
                    </div>
                </div>
                <span class="badge <?= $from_mock ? 'bg-warning text-dark' : 'bg-success text-white' ?> px-3 py-2">
                    <?= $from_mock ? 'Simulation Mode' : 'Live Sync Mode' ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Card Row -->
    <div class="row g-20 mb-25">
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success-custom border-4 shadow-sm stats-card">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Assigned Employees</h6>
                    <h2 class="fw-bold text-success-custom mb-0"><?= count($employee_days_off) ?></h2>
                    <small class="text-muted">Employees with scheduled rest days</small>
                </div>
                <div class="bg-light-success p-3 rounded-circle text-success-custom d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-users fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="card custom-card shadow-xs border-top border-4 border-success-custom">
        <div class="card-header py-3 border-bottom bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-6 col-12">
                    <h5 class="card-title fw-bold mb-0 text-success-custom fs-14">Day Off Schedules</h5>
                </div>
                <div class="col-md-6 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="dayOffSearchInput" class="form-control" placeholder="Search by ID, Name, Designation, Department...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-striped align-middle mb-0 fs-12" id="dayOffTable" style="border-color: #cbd5e1;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">S/L</th>
                            <th class="text-center" style="width: 12%;">Employee ID</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Scheduled Day Offs</th>
                        </tr>
                    </thead>
                    <tbody id="dayOffTbody">
                        <?php if (!empty($employee_days_off)): ?>
                            <?php foreach ($employee_days_off as $index => $emp): ?>
                                <tr>
                                    <td class="text-center text-muted fw-semibold"><?= $index + 1 ?></td>
                                    <td class="text-center fw-bold text-dark"><?= htmlspecialchars($emp['employee_id']) ?></td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($emp['full_name']) ?></span>
                                    </td>
                                    <td class="fw-semibold text-muted"><?= htmlspecialchars($emp['designation_name'] ?? 'N/A') ?></td>
                                    <td class="fw-semibold text-muted"><?= htmlspecialchars($emp['department_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($emp['days_off']) && strtolower($emp['days_off']) !== 'none') {
                                            $daysArray = explode(', ', $emp['days_off']);
                                            foreach ($daysArray as $d) {
                                                $dLower = strtolower(trim($d));
                                                if (in_array($dLower, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])) {
                                                    $class = (in_array($dLower, ['friday', 'saturday'])) ? 'weekend' : '';
                                                    echo '<span class="day-off-badge me-2 ' . $class . '">' . htmlspecialchars($d) . '</span>';
                                                } else {
                                                    echo '<span class="text-muted">' . htmlspecialchars($d) . '</span>';
                                                }
                                            }
                                        } else {
                                            echo '<span class="text-muted">None</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No days off schedules found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("dayOffSearchInput");
    const tableBody = document.getElementById("dayOffTbody");
    const originalRows = Array.from(tableBody.querySelectorAll("tr"));

    // Realtime Client-side Search
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            originalRows.forEach(row => {
                if (row.cells.length < 2) return; // Skip empty row
                
                const id = row.cells[1].textContent.toLowerCase();
                const name = row.cells[2].textContent.toLowerCase();
                const designation = row.cells[3].textContent.toLowerCase();
                const department = row.cells[4].textContent.toLowerCase();
                const daysOff = row.cells[5].textContent.toLowerCase();

                const matches = id.includes(query) || 
                                name.includes(query) || 
                                designation.includes(query) || 
                                department.includes(query) ||
                                daysOff.includes(query);

                if (matches) {
                    row.style.display = "";
                    visibleCount++;
                    // Update index text
                    row.cells[0].textContent = visibleCount;
                } else {
                    row.style.display = "none";
                }
            });

            // Handle empty search results state
            const existingNoResults = document.getElementById("noResultsRow");
            if (visibleCount === 0) {
                if (!existingNoResults) {
                    const noResults = document.createElement("tr");
                    noResults.id = "noResultsRow";
                    noResults.innerHTML = `<td colspan="6" class="text-center py-4 text-muted">No matching records found.</td>`;
                    tableBody.appendChild(noResults);
                }
            } else {
                if (existingNoResults) {
                    existingNoResults.remove();
                }
            }
        });
    }

    // Export CSV Functionality
    document.getElementById("btnExportCSV").addEventListener("click", function() {
        const rows = Array.from(tableBody.querySelectorAll("tr"));
        let csv = [];
        // Header
        csv.push("S/L,Employee ID,Employee Name,Designation,Department,Scheduled Day Offs");

        rows.forEach(row => {
            if (row.id === "noResultsRow" || row.style.display === "none") return;
            const sl = row.cells[0].textContent.trim();
            const id = row.cells[1].textContent.trim();
            const name = row.cells[2].textContent.trim().replace(/"/g, '""');
            const desg = row.cells[3].textContent.trim().replace(/"/g, '""');
            const dept = row.cells[4].textContent.trim().replace(/"/g, '""');
            
            // Get all day off badges
            const badges = Array.from(row.cells[5].querySelectorAll(".day-off-badge")).map(b => b.textContent.trim());
            const daysOff = badges.join("; ");

            csv.push(`"${sl}","${id}","${name}","${desg}","${dept}","${daysOff}"`);
        });

        const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "tipsoi_day_off_list.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Export PDF Functionality
    document.getElementById("btnExportPDF").addEventListener("click", function() {
        const printWindow = window.open("", "_blank");
        const title = "Tipsoi Day Off Lists";
        const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        let tableRowsHtml = "";
        let count = 0;
        originalRows.forEach(row => {
            if (row.id === "noResultsRow" || row.style.display === "none") return;
            count++;
            const id = row.cells[1].textContent.trim();
            const name = row.cells[2].textContent.trim();
            const desg = row.cells[3].textContent.trim();
            const dept = row.cells[4].textContent.trim();
            
            const badges = Array.from(row.cells[5].querySelectorAll(".day-off-badge")).map(b => b.textContent.trim());
            const daysOff = badges.join(", ");

            tableRowsHtml += `
                <tr>
                    <td style="text-align: center;">${count}</td>
                    <td style="text-align: center; font-weight: bold;">${id}</td>
                    <td><strong>${name}</strong></td>
                    <td>${desg}</td>
                    <td>${dept}</td>
                    <td>${daysOff}</td>
                </tr>
            `;
        });

        printWindow.document.write(`
            <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; margin: 30px; }
                    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #198754; padding-bottom: 10px; margin-bottom: 20px; }
                    .title { font-size: 22px; font-weight: bold; color: #198754; }
                    .meta { font-size: 11px; color: #666; text-align: right; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
                    th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
                    th { background-color: #f4f6f8; font-weight: bold; color: #555; }
                    tr:nth-child(even) { background-color: #fafbfc; }
                    .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div>
                        <div class="title">${title}</div>
                        <div style="font-size: 12px; color: #666; margin-top: 3px;">Weekly Day Off Rest Schedules</div>
                    </div>
                    <div class="meta">
                        Generated Date: ${dateStr}<br>
                        Total Employees: ${count}
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">S/L</th>
                            <th style="width: 12%; text-align: center;">Employee ID</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Scheduled Day Offs</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>
                <div class="footer">
                    TPHL-ERP System - Confidential & Internal Use Only
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    });
});
<?php $page_script = ob_get_clean(); ?>
