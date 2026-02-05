<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>
<style>
    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        text-align: center;
        justify-content: center;
        align-items: center;
        font-size: 16px;
    }

    .card__title {
        font-size: 1.25rem;
    }

    @media (max-width: 575px) {
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }
</style>
<div class="row">

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Clients</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['total_clients'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Full Payment Completion</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['full_payment_completed'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Approved Bookings</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['approved_bookings'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-12">

        <div class="card__wrapper">
            <div class="d-flex  justify-content-between mb-20">
                <h4>Clients Information</h4>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <!-- <th>Add Transaction</th> -->
                            <th>Booking Money (Paid)</th>
                            <th>Last Payment Date</th>
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Assigned Agent</th>
                            <th>Designation</th>
                            <th>Project Name</th>
                            <th>Project Type</th>
                            <!-- <th>Flat No.</th> -->
                            <th>Flat Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <!-- <td>
                                        <button 
                                            class="bd-badge bg-primary p-2 " style="font-size: 16px;"
                                            onclick="openTransactionModal(<?= $client['booking_id'] ?>, '<?= htmlspecialchars($client['client_name']) ?>', <?= $client['total_agreement_value'] ?>, <?= $client['booking_money_paid'] ?>)">
                                            <i class="fa-solid fa-plus px-1"></i> Add
                                        </button>
                                    </td> -->
                                    <td>
                                        <!-- Show paid amount and agreement value -->
                                        <div>
                                        </div>
                                        <div class="text-muted small">
                                            ৳<?= format_bdt($client['total_agreement_value']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
                                    </td>
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td><?= htmlspecialchars($client['email']) ?></td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['designation'] ?? 'N/A') ?></td>
                                    <td><?= $client['project_name'] ?? 'N/A' ?></td>
                                    <td>
                                        <span class="bd-badge bg-primary">
                                            <?= $client['project_type'] ?? 'N/A' ?>
                                        </span>
                                    </td>
                                    <!-- <td><= htmlspecialchars($client['unit_name']) ?> - Floor <= $client['flat_no'] ?></td> -->
                                    <td><?= $client['flat_size'] ?? 'N/A' ?> sqft</td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <a class="table__icon download"
                                                href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $client['booking_id'] ?>"
                                                title="View Transaction History">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14" class="text-center">No clients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function openTransactionModal(bookingId, clientName, agreementValue, paidAmount) {
        document.getElementById('modal_booking_id').value = bookingId;
        document.getElementById('modal_client_name').textContent = clientName;
        document.getElementById('modal_total_agreement_value').textContent = agreementValue.toLocaleString('en-BD');
        document.getElementById('modal_paid_amount').textContent = paidAmount.toLocaleString('en-BD');

        const remaining = agreementValue - paidAmount;
        document.getElementById('modal_remaining_amount').textContent = remaining.toLocaleString('en-BD');

        const modal = new bootstrap.Modal(document.getElementById('addTransactionModal'));
        modal.show();
    }
</script>