<style>
    .report-card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: none;
        transition: transform 0.2s ease;
    }
    .kpi-card {
        border-radius: 12px;
        border: none;
        padding: 16px 18px;
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        position: relative;
        overflow: hidden;
    }
    .kpi-card .kpi-icon {
        position: absolute;
        right: 15px;
        bottom: 12px;
        font-size: 2.5rem;
        opacity: 0.18;
    }
    .kpi-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 700;
        margin-bottom: 6px;
        opacity: 0.9;
    }
    .kpi-value {
        font-size: 1.45rem;
        font-weight: 700;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .bg-grad-clients { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
    .bg-grad-dev-total { background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%); }
    .bg-grad-dev-paid { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .bg-grad-dev-due { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #212529 !important; }
    .bg-grad-emi-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .bg-grad-emi-paid { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
    .bg-grad-emi-due { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }

    .table-container {
        max-height: 720px;
        overflow-x: auto;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }
    .table-sticky thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        border-bottom: 2px solid #cbd5e1;
        padding: 12px 10px;
    }
    .table-sticky tbody td {
        font-size: 0.85rem;
        padding: 10px;
        vertical-align: middle;
        white-space: nowrap;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-sticky tbody tr:hover {
        background-color: #f8fafc;
    }
    .currency-amt {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }
    .badge-due {
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
    }
    .badge-cleared {
        background-color: #f0fdf4;
        color: #15803d;
        border: 1px solid #86efac;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
    }
    .badge-status {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 500;
        background: #e2e8f0;
        color: #334155;
    }
    .note-tooltip {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        vertical-align: middle;
        cursor: pointer;
    }

    @media print {
        .app-sidebar, .app-header, .page-header, .no-print, .main-footer {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }
        .report-card, .table-container {
            box-shadow: none !important;
            border: none !important;
            max-height: none !important;
            overflow: visible !important;
        }
        .kpi-card {
            border: 1px solid #ccc !important;
            color: #000 !important;
            background: #fff !important;
        }
        .table-sticky thead th {
            position: static !important;
            background: #eee !important;
            color: #000 !important;
        }
        .print-header {
            display: block !important;
        }
    }
</style>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb no-print">
            <div>
                <h1 class="page-title fw-bold fs-20 mb-1 text-dark">
                    <i class="fa-solid fa-table-list text-primary me-2"></i>CED Central Report
                </h1>
                <p class="text-muted fs-13 mb-0">Consolidated overview of client units, development charge payments, EMI dues, and CED follow-up activities.</p>
            </div>
            <div class="d-flex gap-2 align-items-center mt-3 mt-md-0">
                <button type="button" class="btn btn-outline-success shadow-sm btn-sm px-3" onclick="exportReportToCSV()">
                    <i class="fa-solid fa-file-excel me-1"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-outline-secondary shadow-sm btn-sm px-3" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print View
                </button>
                <button type="button" class="btn btn-outline-danger shadow-sm btn-sm px-3" onclick="exportReportToPDF()">
                    <i class="fa-solid fa-file-pdf me-1"></i> Export to PDF
                </button>
            </div>
        </div>

        <!-- Print-only Title -->
        <div class="d-none print-header mb-4 text-center">
            <h2 class="fw-bold mb-1">TPHL - CED Central Report</h2>
            <p class="text-muted mb-0">Generated on: <?= date('d M Y, h:i A') ?></p>
        </div>

        <!-- Filters Section -->
        <div class="card report-card no-print mb-4">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/ced-central-report" id="centralReportFilterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">Project</label>
                            <select name="project_id" class="form-select form-select-sm shadow-sm">
                                <option value="">-- All Projects --</option>
                                <?php foreach ($projects as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">Payment Status</label>
                            <select name="status_filter" class="form-select form-select-sm shadow-sm">
                                <option value="all" <?= ($selectedStatus === 'all') ? 'selected' : '' ?>>All Clients</option>
                                <option value="has_due" <?= ($selectedStatus === 'has_due') ? 'selected' : '' ?>>Outstanding Due Only</option>
                                <option value="due_dev" <?= ($selectedStatus === 'due_dev') ? 'selected' : '' ?>>Dev Charge Due Only</option>
                                <option value="due_emi" <?= ($selectedStatus === 'due_emi') ? 'selected' : '' ?>>EMI Due Only</option>
                                <option value="paid" <?= ($selectedStatus === 'paid') ? 'selected' : '' ?>>Fully Cleared</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">CED Agent</label>
                            <select name="agent_id" class="form-select form-select-sm shadow-sm">
                                <option value="">-- All Agents --</option>
                                <?php if (!empty($cedEmployees)): ?>
                                    <?php foreach ($cedEmployees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>" <?= ($selectedAgentId == $emp['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['full_name'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-lg-5 col-md-6 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">Search Keyword</label>
                            <input type="text" name="search" class="form-control form-control-sm shadow-sm" 
                                   placeholder="Client, Phone, Unit..." value="<?= htmlspecialchars($search ?? '') ?>">
                        </div>

                        <!-- Date Range Inputs -->
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm shadow-sm" 
                                   value="<?= htmlspecialchars($startDate ?? '') ?>">
                        </div>

                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label text-muted fs-12 fw-bold text-uppercase mb-1">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm shadow-sm" 
                                   value="<?= htmlspecialchars($endDate ?? '') ?>">
                        </div>

                        <div class="col-lg-3 col-md-4 col-sm-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm w-100 fw-semibold">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/ced-central-report" class="btn btn-light btn-sm shadow-sm text-secondary border px-3" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dynamic KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Clients -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-clients">
                    <div class="kpi-title">Total Clients</div>
                    <div class="kpi-value" id="kpi-total-clients"><?= number_format($summaryCards['total_clients']) ?></div>
                    <i class="fa-solid fa-users kpi-icon"></i>
                </div>
            </div>

            <!-- 2. Total Development Charge -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-dev-total">
                    <div class="kpi-title">Total Dev Charge</div>
                    <div class="kpi-value">৳ <span id="kpi-total-dev"><?= number_format($summaryCards['total_dev_charge']) ?></span></div>
                    <i class="fa-solid fa-building-columns kpi-icon"></i>
                </div>
            </div>

            <!-- 3. Dev Charge Paid -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-dev-paid">
                    <div class="kpi-title">Dev Charge Paid</div>
                    <div class="kpi-value">৳ <span id="kpi-dev-paid"><?= number_format($summaryCards['total_dev_charge_paid']) ?></span></div>
                    <i class="fa-solid fa-circle-check kpi-icon"></i>
                </div>
            </div>

            <!-- 4. Dev Charge Due -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-dev-due">
                    <div class="kpi-title text-dark">Dev Charge Due</div>
                    <div class="kpi-value text-dark">৳ <span id="kpi-dev-due"><?= number_format($summaryCards['total_dev_charge_due']) ?></span></div>
                    <i class="fa-solid fa-clock-rotate-left kpi-icon" style="opacity: 0.15; color: #000;"></i>
                </div>
            </div>

            <!-- 5. Total EMI (New Card) -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-emi-total">
                    <div class="kpi-title">Total EMI</div>
                    <div class="kpi-value">৳ <span id="kpi-total-emi"><?= number_format($summaryCards['total_emi'] ?? 0) ?></span></div>
                    <i class="fa-solid fa-coins kpi-icon"></i>
                </div>
            </div>

            <!-- 6. Total EMI Paid -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-emi-paid">
                    <div class="kpi-title">Total EMI Paid</div>
                    <div class="kpi-value">৳ <span id="kpi-emi-paid"><?= number_format($summaryCards['total_emi_paid']) ?></span></div>
                    <i class="fa-solid fa-receipt kpi-icon"></i>
                </div>
            </div>

            <!-- 7. Total EMI Due -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="kpi-card bg-grad-emi-due">
                    <div class="kpi-title">Total EMI Due</div>
                    <div class="kpi-value">৳ <span id="kpi-emi-due"><?= number_format($summaryCards['total_emi_due']) ?></span></div>
                    <i class="fa-solid fa-triangle-exclamation kpi-icon"></i>
                </div>
            </div>
        </div>

        <!-- Details Table Card -->
        <div class="card report-card">
            <div class="card-header bg-transparent border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-15 fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-primary me-2"></i>Client Unit Statements
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12 fw-semibold" id="tableRowCount">
                        Showing <?= count($reportRows) ?> Units
                    </span>
                </div>

                <!-- Instant In-Table Filter Search -->
                <div class="no-print d-flex align-items-center gap-2" style="min-width: 280px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="liveTableSearch" class="form-control border-start-0" placeholder="Instant table filter..." onkeyup="filterTableLive()">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-sticky table-hover mb-0" id="centralReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>1. Project Name</th>
                                <th>2. Unit</th>
                                <th>3. Unit Size</th>
                                <th>4. Client Name</th>
                                <th>5. Mobile Number</th>
                                <th>6. Email</th>
                                <th class="text-end">7. Total Dev Charge</th>
                                <th class="text-end">8. Dev Charge Paid</th>
                                <th class="text-end">9. Dev Charge Due</th>
                                <th class="text-end">10. Disc. on Dev</th>
                                <th class="text-end">11. Single EMI Amt</th>
                                <th class="text-center">12. EMI Asked</th>
                                <th class="text-end">13. Total EMI Amt</th>
                                <th class="text-end">14. Total EMI Paid</th>
                                <th class="text-end">15. Total EMI Due</th>
                                <th class="text-end">16. Disc. on EMI</th>
                                <th class="text-end">17. Total Outstanding</th>
                                <th>18. Last Pay Date</th>
                                <th class="text-end">19. Last Pay Amt</th>
                                <th>20. Last Follow-up Note</th>
                                <th>21. Last Follow-up Date</th>
                                <th>22. Follow-up By</th>
                                <th>23. Follow-up Status</th>
                            </tr>
                        </thead>
                        <tbody id="centralReportTableBody">
                            <?php if (empty($reportRows)): ?>
                                <tr>
                                    <td colspan="24" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fs-1 opacity-25 d-block mb-3"></i>
                                        No client records found matching the specified filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $sl = 1; foreach ($reportRows as $row): ?>
                                    <tr class="report-data-row"
                                        data-dev-charge="<?= $row['total_dev_charge'] ?>"
                                        data-dev-paid="<?= $row['dev_charge_paid'] ?>"
                                        data-dev-due="<?= $row['dev_charge_due'] ?>"
                                        data-emi-total="<?= $row['total_emi_amount'] ?>"
                                        data-emi-paid="<?= $row['emi_paid'] ?>"
                                        data-emi-due="<?= $row['emi_due'] ?>">
                                        <td class="text-muted fw-bold"><?= $sl++ ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($row['project_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['unit_name']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['unit_size']) ?></td>
                                        <td>
                                            <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $row['booking_id'] ?>" 
                                               class="fw-bold text-primary text-decoration-none" title="View Cash-In History">
                                                <?= htmlspecialchars($row['client_name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="currency-amt"><?= htmlspecialchars($row['phone']) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-12"><?= htmlspecialchars($row['email']) ?></span>
                                        </td>
                                        <td class="text-end currency-amt">৳ <?= number_format($row['total_dev_charge'], 2) ?></td>
                                        <td class="text-end currency-amt text-success">৳ <?= number_format($row['dev_charge_paid'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if ($row['dev_charge_due'] > 0): ?>
                                                <span class="badge-due currency-amt">৳ <?= number_format($row['dev_charge_due'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="badge-cleared"><i class="fa-solid fa-check me-1"></i>0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-muted currency-amt">৳ <?= number_format($row['dev_charge_discount'], 2) ?></td>
                                        <td class="text-end currency-amt">৳ <?= number_format($row['single_emi_amount'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fw-bold"><?= $row['emi_asked_count'] ?></span>
                                        </td>
                                        <td class="text-end currency-amt">৳ <?= number_format($row['total_emi_amount'], 2) ?></td>
                                        <td class="text-end currency-amt text-success">৳ <?= number_format($row['emi_paid'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if ($row['emi_due'] > 0): ?>
                                                <span class="badge-due currency-amt">৳ <?= number_format($row['emi_due'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="badge-cleared"><i class="fa-solid fa-check me-1"></i>0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-muted currency-amt">৳ <?= number_format($row['bulk_emi_discount'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if ($row['total_outstanding'] > 0): ?>
                                                <span class="badge bg-danger fs-12 fw-bold">৳ <?= number_format($row['total_outstanding'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success fs-12 fw-bold">Cleared</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['last_payment_date']) ?></td>
                                        <td class="text-end currency-amt">
                                            <?= $row['last_payment_amount'] > 0 ? '৳ ' . number_format($row['last_payment_amount'], 2) : '-' ?>
                                        </td>
                                        <td>
                                            <span class="note-tooltip" title="<?= htmlspecialchars($row['last_followup_note']) ?>">
                                                <?= htmlspecialchars($row['last_followup_note']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['last_followup_date']) ?></td>
                                        <td>
                                            <span class="fw-semibold text-secondary"><?= htmlspecialchars($row['last_followup_by']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-status"><?= htmlspecialchars($row['followup_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    /**
     * Real-time Instant In-Table Filter & Dynamic KPI Cards Recalculation
     * Sums only the visible rows to ensure cards dynamically update on search!
     */
    function filterTableLive() {
        const input = document.getElementById('liveTableSearch');
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#centralReportTableBody tr.report-data-row');

        let visibleCount = 0;
        let sumDevCharge = 0.0;
        let sumDevPaid = 0.0;
        let sumDevDue = 0.0;
        let sumEmiTotal = 0.0;
        let sumEmiPaid = 0.0;
        let sumEmiDue = 0.0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.indexOf(filter) > -1) {
                row.style.display = '';
                visibleCount++;

                sumDevCharge += parseFloat(row.getAttribute('data-dev-charge')) || 0.0;
                sumDevPaid += parseFloat(row.getAttribute('data-dev-paid')) || 0.0;
                sumDevDue += parseFloat(row.getAttribute('data-dev-due')) || 0.0;
                sumEmiTotal += parseFloat(row.getAttribute('data-emi-total')) || 0.0;
                sumEmiPaid += parseFloat(row.getAttribute('data-emi-paid')) || 0.0;
                sumEmiDue += parseFloat(row.getAttribute('data-emi-due')) || 0.0;
            } else {
                row.style.display = 'none';
            }
        });

        // Update KPI Card numbers dynamically in real time
        document.getElementById('kpi-total-clients').textContent = new Intl.NumberFormat().format(visibleCount);
        document.getElementById('kpi-total-dev').textContent = new Intl.NumberFormat().format(Math.round(sumDevCharge));
        document.getElementById('kpi-dev-paid').textContent = new Intl.NumberFormat().format(Math.round(sumDevPaid));
        document.getElementById('kpi-dev-due').textContent = new Intl.NumberFormat().format(Math.round(sumDevDue));
        const kpiTotalEmi = document.getElementById('kpi-total-emi');
        if (kpiTotalEmi) {
            kpiTotalEmi.textContent = new Intl.NumberFormat().format(Math.round(sumEmiTotal));
        }
        document.getElementById('kpi-emi-paid').textContent = new Intl.NumberFormat().format(Math.round(sumEmiPaid));
        document.getElementById('kpi-emi-due').textContent = new Intl.NumberFormat().format(Math.round(sumEmiDue));

        // Update count badge
        document.getElementById('tableRowCount').textContent = 'Showing ' + visibleCount + ' Units';
    }

    /**
     * 1-Click Export to CSV / Excel
     * Exports all 23 columns of visible filtered records with UTF-8 BOM.
     */
    function exportReportToCSV() {
        const table = document.getElementById('centralReportTable');
        const csv = [];

        // Headers
        const headerCols = table.querySelectorAll('thead th');
        const headerRow = [];
        headerCols.forEach(th => {
            let text = th.innerText.replace(/"/g, '""').trim();
            headerRow.push('"' + text + '"');
        });
        csv.push(headerRow.join(','));

        // Visible Body Rows
        const bodyRows = table.querySelectorAll('tbody tr.report-data-row');
        bodyRows.forEach(row => {
            if (row.style.display !== 'none') {
                const cols = row.querySelectorAll('td');
                const rowData = [];
                cols.forEach(td => {
                    let val = td.innerText.replace(/৳/g, '').replace(/"/g, '""').trim();
                    val = val.replace(/\s+/g, ' '); // Clean newlines/extra spaces
                    rowData.push('"' + val + '"');
                });
                csv.push(rowData.join(','));
            }
        });

        const csvContent = '\uFEFF' + csv.join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const filename = 'ced_central_report_' + new Date().toISOString().slice(0, 10) + '.csv';

        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * 1-Click Export to PDF
     * Generates a landscape PDF with title, summary card figures, and all 23 table columns.
     */
    function exportReportToPDF() {
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF library is loading, please wait a moment and try again.');
            return;
        }

        const { jsPDF } = window.jspdf;
        // Landscape A3 format to comfortably fit 23 detailed columns
        const doc = new jsPDF('landscape', 'pt', 'a3');

        // Document Header
        doc.setFontSize(18);
        doc.setTextColor(30, 41, 59);
        doc.text('TPHL - CED Central Report', 40, 45);

        doc.setFontSize(10);
        doc.setTextColor(100, 116, 139);
        const generatedOn = 'Generated on: ' + new Date().toLocaleString();
        doc.text(generatedOn, 40, 62);

        // Summary Card Stats Header Box
        const totalClients = document.getElementById('kpi-total-clients') ? document.getElementById('kpi-total-clients').innerText : '0';
        const totalDev = document.getElementById('kpi-total-dev') ? document.getElementById('kpi-total-dev').innerText : '0';
        const devPaid = document.getElementById('kpi-dev-paid') ? document.getElementById('kpi-dev-paid').innerText : '0';
        const devDue = document.getElementById('kpi-dev-due') ? document.getElementById('kpi-dev-due').innerText : '0';
        const totalEmi = document.getElementById('kpi-total-emi') ? document.getElementById('kpi-total-emi').innerText : '0';
        const emiPaid = document.getElementById('kpi-emi-paid') ? document.getElementById('kpi-emi-paid').innerText : '0';
        const emiDue = document.getElementById('kpi-emi-due') ? document.getElementById('kpi-emi-due').innerText : '0';

        doc.setFillColor(241, 245, 249);
        doc.roundedRect(40, 75, 1110, 35, 4, 4, 'F');

        doc.setFontSize(8.5);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(15, 23, 42);

        const summaryText = `Clients: ${totalClients}  |  Dev Charge: BDT ${totalDev}  |  Dev Paid: BDT ${devPaid}  |  Dev Due: BDT ${devDue}  |  Total EMI: BDT ${totalEmi}  |  EMI Paid: BDT ${emiPaid}  |  EMI Due: BDT ${emiDue}`;
        doc.text(summaryText, 52, 97);

        // Table Headers
        const headerCols = document.querySelectorAll('#centralReportTable thead th');
        const headers = [];
        headerCols.forEach(th => {
            headers.push(th.innerText.replace(/\s+/g, ' ').trim());
        });

        // Visible Body Rows
        const bodyRows = document.querySelectorAll('#centralReportTableBody tr.report-data-row');
        const data = [];
        bodyRows.forEach(row => {
            if (row.style.display !== 'none') {
                const cols = row.querySelectorAll('td');
                const rowData = [];
                cols.forEach(td => {
                    let val = td.innerText.replace(/৳/g, '').trim();
                    val = val.replace(/\s+/g, ' ');
                    rowData.push(val);
                });
                data.push(rowData);
            }
        });

        if (data.length === 0) {
            alert('No records available to export.');
            return;
        }

        doc.autoTable({
            head: [headers],
            body: data,
            startY: 120,
            theme: 'grid',
            styles: {
                fontSize: 7,
                cellPadding: 4,
                overflow: 'linebreak',
                valign: 'middle'
            },
            headStyles: {
                fillColor: [30, 41, 59],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                halign: 'left'
            },
            alternateRowStyles: {
                fillColor: [248, 250, 252]
            },
            margin: { left: 40, right: 40, bottom: 40 }
        });

        const filename = 'ced_central_report_' + new Date().toISOString().slice(0, 10) + '.pdf';
        doc.save(filename);
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

