<?php
$page = 'Sales report'
?>
<style>
    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }

    .btn-secondary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    @media print {

        /* DataTables controls hide */
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .dataTables_wrapper .dt-buttons {
            display: none !important;
        }

        /* Only table visible */
        table.dataTable {
            border: 1px solid #000;
        }

        table.dataTable th,
        table.dataTable td {
            border: 1px solid #000;
            padding: 5px;
        }
    }
</style>


<div class="row">
    <div class="card__title-wrap mb-20">
        <h5 class="card__heading-title">Sales Reports</h5>
    </div>
</div>

<form action="" method="get">
    <div class="row flat-select">
        <!-- Project Filter -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
            <div class="card__wrapper">
                <div class="from__input-box">
                    <select class="form-control" name="project_id">
                        <option value="">Filter By Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($selectedProject == $project['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
            <div class="card__wrapper">
                <div class="from__input-box">
                    <select class="form-control" name="sales_by_id">
                        <option value="">Filter By Sales Person</option>
                        <?php foreach ($executiveUsers as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($selectedUser == $user['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Submit & Reset Buttons -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
            <div class="card__wrapper">
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        Filter
                    </button>

                    <a href="<?= $GLOBALS['base_url'] ?>/sales-report" class="btn btn-success w-100">
                        Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <!-- Filter Controls -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <!-- Action Buttons -->
            <div class="filter-item">
                <label>&nbsp;</label>
                <div class="action-buttons">
                    <button type="button" class="btn btn-success" onclick="downloadPDF()">
                        <i class="fa-light fa-download me-2"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="printStatement()">
                        <i class="fa-light fa-print me-2"></i> Print Statement
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xxl-12 col-xl-12 col-sm-12">
        <div class="card__wrapper">

            <div class="">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="salesTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="daily-tab" data-bs-toggle="tab" href="#daily" role="tab" aria-controls="daily" aria-selected="true">Daily</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="monthly-tab" data-bs-toggle="tab" href="#monthly" role="tab" aria-controls="monthly" aria-selected="false">Monthly</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="yearly-tab" data-bs-toggle="tab" href="#yearly" role="tab" aria-controls="yearly" aria-selected="false">Yearly</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="salesTabContent">
                    <!-- Daily Sales -->
                    <div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">

                        <div class="col-xxl-12">
                            <div class="card__wrapper">
                                <div class="table__wrapper table-responsive">
                                    <table class="table mb-20 multiple_tables">
                                        <thead class="table-light">
                                            <tr class="table__title ">
                                                <th>Date</th>
                                                <th>Client Name</th>
                                                <th>Project/Property</th>
                                                <th>Flats Details</th>
                                                <th>Flat share Value</th>
                                                <th>Booking Amount</th>
                                                <th>Sales By User</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dailySalesReport as $row): ?>
                                                <tr>
                                                    <td><?= date('d-M-Y', strtotime($row['booking_date'])) ?></td>
                                                    <td><?= $row['client_name'] ?><span class="ms-2"> (ID: <?= $row['lead_id'] ?>)</span> </td>
                                                    <td><?= htmlspecialchars($row['project_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['flat_name']) ?> </td>
                                                    <td><?= format_bdt($row['final_price']) ?> </td>
                                                    <td><?= htmlspecialchars($row['agreement_value']) ?></td>
                                                    <td><?= $row['user_name'] ?? $row['assigned_user_name'] ?> - ID:<span><?= $row['sales_user_id'] ?></span></td>
                                                    <td>
                                                        <?php if ($row['booking_status'] === 'completed'): ?>
                                                            <span class="bd-badge bg-success">Completed</span>
                                                        <?php else: ?>
                                                            <span class="bd-badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Sales -->
                    <div class="tab-pane fade" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                        <div class="col-xxl-12">
                            <div class="card__wrapper">
                                <div class="table__wrapper table-responsive">
                                    <table class="table mb-20 multiple_tables">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Month</th>
                                                <th>Total Flats</th>
                                                <th>Total Clients</th>
                                                <th>Total Booking Amount</th>
                                                <th>Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($monthylySalesReport)): ?>
                                                <?php foreach ($monthylySalesReport as $row): ?>
                                                    <tr>
                                                        <td><?= $row['month_name']; ?></td>
                                                        <td><?= $row['total_flats']; ?></td>
                                                        <td><?= $row['total_clients']; ?></td>
                                                        <td>৳<?= number_format($row['total_booking_value'], 2); ?></td>
                                                        <td>
                                                            <span class="bd-badge bg-success">
                                                                <?= $row['completed']; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        No monthly sales data found
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yearly Sales -->
                    <div class="tab-pane fade" id="yearly" role="tabpanel" aria-labelledby="yearly-tab">
                        <div class="col-xxl-12">
                            <div class="card__wrapper">
                                <div class="table__wrapper table-responsive">
                                    <table class="table mb-20 multiple_tables">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Year</th>
                                                <th>Total Clients</th>
                                                <th>Total Flats</th>
                                                <th>Total Amount</th>
                                                <th>Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($yearlySalesReport)): ?>
                                                <?php foreach ($yearlySalesReport as $row): ?>
                                                    <tr>
                                                        <td><?= $row['year']; ?></td>
                                                        <td><?= $row['total_clients']; ?></td>
                                                        <td><?= $row['total_flats']; ?></td>
                                                        <td>৳<?= number_format($row['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="bd-badge bg-success">
                                                                <?= $row['completed']; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        No yearly sales data found
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- /tab-content -->
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    function downloadPDF() {
        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4'); // landscape

        // Get active table
        const activeTab = document.querySelector('#salesTabContent .tab-pane.active');
        const table = activeTab.querySelector('table');

        if (!table) {
            alert('No table found!');
            return;
        }

        doc.autoTable({
            html: table,
            startY: 20
        });
        doc.save('SalesReport.pdf');
    }
</script>

<script>
    function printStatement() {
        // Get currently active tab
        const activeTab = document.querySelector('#salesTabContent .tab-pane.active');
        if (!activeTab) return;

        const printWindow = window.open('', '', 'height=800,width=1200');
        printWindow.document.write('<html><head><title>Sales Report</title>');
        printWindow.document.write('<link rel="stylesheet" href="<?= $GLOBALS['base_url'] ?>/assets/css/bootstrap.min.css">'); // Bootstrap CSS if needed
        printWindow.document.write('<style> table {width: 100%; border-collapse: collapse;} th, td {border:1px solid #ccc; padding:5px;} th {background:#f4f4f4;} </style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(activeTab.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
</script>