<?php
$page_title = "Monthly LWP Report";
$page = 'Monthly LWP';
$show_breadcrumb = true;
?>

<style>
.bg-light-primary {
    background-color: rgba(13, 110, 253, 0.08) !important;
}
.text-primary-custom {
    color: #15a15b !important;
}
.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08) !important;
}
.date-badge {
    background-color: #f3f4f6;
    color: #374151;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<div class="monthly-lwp-container container-fluid p-0">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mb-25">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Monthly LWP (Leave Without Pay) Report</h4>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Track employees who have taken Leave Without Pay (LWP) within the selected month</p>
        </div>
        <div class="d-flex align-items-center gap-10">
            <button id="btnExportPDF" class="btn btn-outline-danger btn-sm fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-20 mb-25">
        <div class="col-md-4 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-primary border-4 shadow-sm stats-card">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Employees on LWP</h6>
                    <h2 class="fw-bold text-primary mb-0" id="statEmpCount">0</h2>
                    <small class="text-muted">In selected month</small>
                </div>
                <div class="bg-light-primary p-3 rounded-circle text-primary-custom d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total LWP Days Taken</h6>
                    <h2 class="fw-bold text-danger mb-0" id="statTotalDays">0</h2>
                    <small class="text-muted">Sum of all leaves in month</small>
                </div>
                <div class="bg-light-danger p-3 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar-x-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Selected Month</h6>
                    <h2 class="fw-bold text-success mb-0" style="font-size: 1.5rem;" id="statSelectedMonth">...</h2>
                    <small class="text-muted" id="statDateRange">Range: ...</small>
                </div>
                <div class="bg-light-success p-3 rounded-circle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card__wrapper p-20 shadow-sm mb-20">
        <form id="filterForm">
            <div class="row g-15 align-items-end">
                <div class="col-md-4 col-12">
                    <label class="form-label fw-bold text-secondary mb-2" style="font-size: 0.75rem;">Select Month & Year</label>
                    <input type="month" id="selectedMonth" class="form-control form-control-sm" value="<?= date('Y-m') ?>">
                </div>
                <div class="col-md-5 col-12">
                    <label class="form-label fw-bold text-secondary mb-2" style="font-size: 0.75rem;">Search Employee</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchQuery" class="form-control border-start-0 ps-0" placeholder="Search by name, office ID, department...">
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <button type="button" id="btnResetFilters" class="btn btn-sm btn-light w-100 fw-semibold border">
                        <i class="bi bi-eraser me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Grid -->
    <div class="card__wrapper p-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 60px;">SN</th>
                        <th style="width: 120px;">Office ID</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>LWP Dates Taken (In Month)</th>
                        <th class="text-center" style="width: 180px;">LWP Days Taken</th>
                        <th class="text-center" style="width: 180px;">Remaining LWP Balance</th>
                    </tr>
                </thead>
                <tbody id="lwpTableBody">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function() {
    const baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';
    
    // UI Elements
    const selectedMonthInput = document.getElementById("selectedMonth");
    const searchQueryInput = document.getElementById("searchQuery");
    const btnResetFilters = document.getElementById("btnResetFilters");
    const lwpTableBody = document.getElementById("lwpTableBody");
    const btnExportPDF = document.getElementById("btnExportPDF");

    const statEmpCount = document.getElementById("statEmpCount");
    const statTotalDays = document.getElementById("statTotalDays");
    const statSelectedMonth = document.getElementById("statSelectedMonth");
    const statDateRange = document.getElementById("statDateRange");

    // Local State
    let aggregatedLwpData = [];
    let filteredLwpData = [];

    // Helper functions
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    function getDatesInRange(startDateStr, endDateStr) {
        const dates = [];
        let current = new Date(startDateStr);
        const end = new Date(endDateStr);
        while (current <= end) {
            dates.push(current.toISOString().split('T')[0]);
            current.setDate(current.getDate() + 1);
        }
        return dates;
    }

    function getMonthDateRange(monthString) {
        const parts = monthString.split('-');
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10); // 1-12
        
        const firstDay = `${year}-${String(month).padStart(2, '0')}-01`;
        const lastDayVal = new Date(year, month, 0).getDate();
        const lastDay = `${year}-${String(month).padStart(2, '0')}-${String(lastDayVal).padStart(2, '0')}`;

        return { firstDay, lastDay };
    }

    function fetchAndProcessLwpData() {
        const monthVal = selectedMonthInput.value;
        if (!monthVal) return;

        lwpTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2">Loading Monthly LWP report data...</div>
                </td>
            </tr>
        `;

        const { firstDay, lastDay } = getMonthDateRange(monthVal);
        const selectedYear = monthVal.split('-')[0];

        // Format dates for stats card
        const monthName = new Date(firstDay).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        statSelectedMonth.textContent = monthName;
        statDateRange.textContent = `Range: ${firstDay} to ${lastDay}`;

        // Fetch Leave Balances for remaining LWP calculations, and Leave History for dates
        const urlBalances = `${baseUrl}/api/hrm/leave-balances?from_date=${selectedYear}-01-01&to_date=${selectedYear}-12-31`;
        const urlHistory = `${baseUrl}/api/hrm/leave-history?from_date=${firstDay}&to_date=${lastDay}`;

        Promise.all([
            fetch(urlBalances).then(res => res.json()),
            fetch(urlHistory).then(res => res.json())
        ])
        .then(([balRes, histRes]) => {
            if (!balRes.success || !histRes.success) {
                showErrorRow("Failed to load data from Tipsoi APIs.");
                return;
            }

            const balances = balRes.data || [];
            const history = histRes.data || [];
            const localEmployees = balRes.local_employees || [];

            // 1. Flatten history into individual records
            const flatHistoryRecords = [];
            history.forEach(emp => {
                const dataList = emp.leaveLeaveInfo?.leaveDataList || [];
                dataList.forEach(item => {
                    if (item.from !== null) {
                        flatHistoryRecords.push({
                            employeeOfficeId: emp.employeeOfficeId || emp.employeeId,
                            employeeName: emp.employeeName,
                            departmentName: emp.departmentName || 'No Department',
                            leaveCategoryName: item.leaveCategoryName,
                            from: item.from,
                            to: item.to,
                            days: item.days || 0,
                            approvalStatus: item.approvalStatus
                        });
                    }
                });
            });

            // Filter history for LWP only
            const lwpHistory = flatHistoryRecords.filter(rec => {
                const isLwp = String(rec.leaveCategoryName || '').toLowerCase().includes('without pay') ||
                              String(rec.leaveCategoryName || '').toLowerCase().includes('lwp');
                // Status 1 means approved leave request
                return isLwp && rec.approvalStatus === 1;
            });

            // 2. Group by employee
            const empGroups = {};
            lwpHistory.forEach(rec => {
                const officeId = normalizeId(rec.employeeOfficeId);
                if (!empGroups[officeId]) {
                    empGroups[officeId] = {
                        officeId: rec.employeeOfficeId || '—',
                        employeeName: rec.employeeName || '—',
                        departmentName: rec.departmentName || 'No Department',
                        datesList: new Set(),
                        daysCount: 0
                    };
                }

                // Add specific dates in range falling in this month
                const dates = getDatesInRange(rec.from, rec.to);
                dates.forEach(d => {
                    if (d.startsWith(monthVal)) {
                        empGroups[officeId].datesList.add(d);
                    }
                });
            });

            // 3. Map with balances and format
            aggregatedLwpData = Object.keys(empGroups).map(officeId => {
                const group = empGroups[officeId];
                
                // Get list of specific dates sorted
                const sortedDates = Array.from(group.datesList).sort();
                
                // Fetch remaining LWP balance from balance API response
                const empBalRecord = balances.find(b => normalizeId(b.employeeOfficeId) === officeId);
                let remainingLwp = '—';
                if (empBalRecord) {
                    const leaveList = empBalRecord.leaveLeaveInfo?.leaveDataList || [];
                    const lwpBalItem = leaveList.find(l => 
                        String(l.leaveCategoryName).toLowerCase().includes('without pay') ||
                        String(l.leaveCategoryName).toLowerCase().includes('lwp')
                    );
                    if (lwpBalItem) {
                        remainingLwp = lwpBalItem.balance !== null ? lwpBalItem.balance : '—';
                    }
                }

                // Match ERP registration indicator
                const matchedLocal = localEmployees.find(le => normalizeId(le.employee_id) === officeId);
                const isRegistered = !!matchedLocal;

                return {
                    officeId: group.officeId,
                    employeeName: group.employeeName,
                    departmentName: group.departmentName,
                    dates: sortedDates,
                    daysCount: sortedDates.length,
                    remainingBalance: remainingLwp,
                    isRegistered: isRegistered
                };
            }).filter(item => item.daysCount > 0); // Only keep employees who actually have LWP days in this month

            applyFiltersAndRender();
        })
        .catch(err => {
            console.error(err);
            showErrorRow("An unexpected network error occurred.");
        });
    }

    function applyFiltersAndRender() {
        const searchVal = searchQueryInput.value.toLowerCase().trim();

        filteredLwpData = aggregatedLwpData.filter(item => {
            const matchesSearch = !searchVal ||
                item.employeeName.toLowerCase().includes(searchVal) ||
                item.officeId.toLowerCase().includes(searchVal) ||
                item.departmentName.toLowerCase().includes(searchVal);
            return matchesSearch;
        });

        // Calculate and update stats
        statEmpCount.textContent = filteredLwpData.length;
        let totalDays = 0;
        filteredLwpData.forEach(item => {
            totalDays += item.daysCount;
        });
        statTotalDays.textContent = totalDays;

        renderTable();
    }

    function renderTable() {
        if (filteredLwpData.length === 0) {
            lwpTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No employees took Leave Without Pay (LWP) in this month.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = 1;
        lwpTableBody.innerHTML = filteredLwpData.map(item => {
            const localIndicator = item.isRegistered
                ? '<i class="bi bi-patch-check-fill text-success ms-1" title="Registered in ERP DB"></i>'
                : '<i class="bi bi-patch-question text-muted ms-1" title="Not in ERP DB"></i>';

            // Format dates list nicely
            const datesDisplay = item.dates.map(d => {
                const dateObj = new Date(d);
                return dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            }).join(', ');

            return `
                <tr>
                    <td class="text-muted fw-semibold">${sn++}</td>
                    <td class="fw-semibold text-muted">${item.officeId}</td>
                    <td class="fw-bold">${item.employeeName} ${localIndicator}</td>
                    <td>${item.departmentName}</td>
                    <td class="text-secondary fw-semibold">${datesDisplay}</td>
                    <td class="text-center text-danger fw-bold fs-6">${item.daysCount}</td>
                    <td class="text-center text-success fw-bold fs-6">${item.remainingBalance}</td>
                </tr>
            `;
        }).join('');
    }

    function showErrorRow(msg) {
        lwpTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                    ${msg}
                </td>
            </tr>
        `;
    }

    function exportToPDF() {
        if (filteredLwpData.length === 0) {
            alert("No data available to print.");
            return;
        }

        const monthVal = selectedMonthInput.value;
        const firstDay = `${monthVal}-01`;
        const monthName = new Date(firstDay).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });

        let sn = 1;
        const rowsHtml = filteredLwpData.map(item => {
            const datesDisplay = item.dates.map(d => {
                const dateObj = new Date(d);
                return dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            }).join(', ');

            return `
                <tr>
                    <td>${sn++}</td>
                    <td>${item.officeId}</td>
                    <td style="font-weight: 600;">${item.employeeName}</td>
                    <td>${item.departmentName}</td>
                    <td>${datesDisplay}</td>
                    <td class="text-center text-danger" style="font-weight: 700;">${item.daysCount}</td>
                    <td class="text-center text-success" style="font-weight: 700;">${item.remainingBalance}</td>
                </tr>
            `;
        }).join('');

        const printWindow = window.open("", "_blank");
        
        let htmlStr = '';
        htmlStr += '<!DOCTYPE html>';
        htmlStr += '<html>';
        htmlStr += '<head>';
        htmlStr += '    <title>Monthly_LWP_Report_' + monthVal + '</title>';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
        htmlStr += '    <style>';
        htmlStr += '        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap");';
        htmlStr += '        body { font-family: "Inter", sans-serif; color: #333; margin: 30px; }';
        htmlStr += '        .report-header { border-bottom: 2px solid #15a15b; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }';
        htmlStr += '        .company-name { font-size: 26px; font-weight: 800; color: #15a15bff; margin: 0; }';
        htmlStr += '        .report-title { font-size: 18px; font-weight: 600; color: #495057; margin: 5px 0 0 0; }';
        htmlStr += '        .meta-info { font-size: 12px; color: #6c757d; margin-top: 10px; }';
        htmlStr += '        .table-custom th { background-color: #f8f9fa !important; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }';
        htmlStr += '        .table-custom td { font-size: 11px; }';
        htmlStr += '        @media print {';
        htmlStr += '            body { margin: 15px; }';
        htmlStr += '            .no-print { display: none; }';
        htmlStr += '        }';
        htmlStr += '    </style>';
        htmlStr += '</head>';
        htmlStr += '<body>';
        htmlStr += '    <div class="report-header position-relative">';
        htmlStr += '        <div class="no-print position-absolute end-0 top-0">';
        htmlStr += '            <button onclick="window.print();" class="btn btn-sm btn-primary me-2"><i class="bi bi-printer"></i> Print</button>';
        htmlStr += '            <button onclick="window.close();" class="btn btn-sm btn-outline-secondary">Close</button>';
        htmlStr += '        </div>';
        htmlStr += '        <h1 class="company-name">The Premium Homes Ltd</h1>';
        htmlStr += '        <h2 class="report-title">Monthly LWP (Leave Without Pay) Report</h2>';
        htmlStr += '        <div class="meta-info">';
        htmlStr += '            <strong>Report Month:</strong> ' + monthName + ' &nbsp;|&nbsp; <strong>Generated On:</strong> ' + new Date().toLocaleString();
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <table class="table table-bordered table-striped table-custom align-middle">';
        htmlStr += '        <thead>';
        htmlStr += '            <tr>';
        htmlStr += '                <th style="width: 50px;">SN</th>';
        htmlStr += '                <th style="width: 100px;">Office ID</th>';
        htmlStr += '                <th>Employee Name</th>';
        htmlStr += '                <th>Department</th>';
        htmlStr += '                <th>LWP Dates Taken</th>';
        htmlStr += '                <th class="text-center" style="width: 140px;">Days Taken</th>';
        htmlStr += '                <th class="text-center" style="width: 140px;">Remaining Balance</th>';
        htmlStr += '            </tr>';
        htmlStr += '        </thead>';
        htmlStr += '        <tbody>';
        htmlStr += rowsHtml;
        htmlStr += '        </tbody>';
        htmlStr += '    </table>';
        htmlStr += '    ';
        htmlStr += '    <script>';
        htmlStr += '        window.onload = function() {';
        htmlStr += '            setTimeout(function() {';
        htmlStr += '                window.print();';
        htmlStr += '            }, 500);';
        htmlStr += '        };';
        htmlStr += '    <\/script>';
        htmlStr += '</body>';
        htmlStr += '</html>';

        printWindow.document.write(htmlStr);
        printWindow.document.close();
    }

    // Reset Filters
    btnResetFilters.addEventListener("click", () => {
        searchQueryInput.value = "";
        selectedMonthInput.value = "<?= date('Y-m') ?>";
        fetchAndProcessLwpData();
    });

    // Event listeners
    selectedMonthInput.addEventListener("change", fetchAndProcessLwpData);
    searchQueryInput.addEventListener("input", applyFiltersAndRender);
    btnExportPDF.addEventListener("click", exportToPDF);

    // Initial load
    fetchAndProcessLwpData();
});
<?php $page_script = ob_get_clean(); ?>
