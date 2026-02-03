<?php
$page = 'Sales report'
?>



<div class="row">
    <div class="card__title-wrap mb-20">
        <h5 class="card__heading-title">Sales Reports</h5>
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
                                                <th>Booking Amount</th>
                                                <th>Sales By User</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dailySalesReport as $row): ?>
                                                <tr>
                                                    <td><?= date('d-M-Y', strtotime($row['booking_date'])) ?></td>
                                                    <td><?= $row['client_name'] ?></td>
                                                    <td><?= htmlspecialchars($row['project_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['flat_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['agreement_value']) ?></td>
                                                    <td><?= $row['user_name'] ?? $row['assigned_user_name'] ?> - ID:<span><?= $row['user_id'] ?></span></td>
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