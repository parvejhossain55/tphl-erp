<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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

    <!-- Pending Verification Card -->
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper" style="border-left: 4px solid #f59e0b;">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span style="color:#f59e0b; background:rgba(245,158,11,0.1);"><i class="fa-light fa-clock"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Pending Verification</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title" id="pendingVerificationCount">–</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center mb-20">
                <h4>Clients Information</h4>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-warning text-dark me-1"><i class="fa-light fa-clock me-1"></i> Pending Verification</span>
                    <span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i> Verified</span>
                </div>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables" id="clientsTable">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Booking Agent(s)</th>
                            <th>Total Paid</th>
                            <th>Last Payment</th>
                            <th>Verification</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <?php
                                    // Determine verification status based on latest payment
                                    $verStatus    = $client['latest_verification_status'] ?? 'pending';
                                    $rowClass     = ($verStatus === 'pending')  ? 'table-warning'  : '';
                                    $rowClass     = ($verStatus === 'rejected') ? 'table-danger'   : $rowClass;
                                    $badgeClass   = ($verStatus === 'verified') ? 'bg-success'     : 'bg-warning text-dark';
                                    $badgeIcon    = ($verStatus === 'verified') ? 'fa-check-circle' : 'fa-clock';
                                    if ($verStatus === 'rejected') { $badgeClass = 'bg-danger'; $badgeIcon = 'fa-times-circle'; }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="fa-light fa-building me-1"></i>
                                            <?= htmlspecialchars($client['project_name'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="fa-light fa-home me-1"></i>
                                            <?= htmlspecialchars($client['flat_no'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td>TK.<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <i class="fa-light <?= $badgeIcon ?> me-1"></i>
                                            <?= ucfirst($verStatus) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <button class="table__icon download"
                                                onclick="openProjectsModal(<?= $client['lead_id'] ?>, '<?= htmlspecialchars($client['client_name'], ENT_QUOTES) ?>')"
                                                title="View Projects">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center text-muted py-4">No clients found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Projects Modal -->
<div class="modal fade" id="projectsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Land Shares - <span id="modal_client_name"></span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <div id="projects_loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading land shares...</p>
                </div>

                <div id="projects_content" style="display: none;">
                    <div class="table__wrapper table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="table__title">
                                    <th>Project Name</th>
                                    <th>Unit - Floor</th>
                                    <th>Added By</th>
                                    <th>Flat Size</th>
                                    <th>Total Price</th>
                                    <th>Agreement Value</th>
                                    <th>Total Paid</th>
                                    <th>Service Fee</th>
                                    <th>Booking Status</th>
                                    <th>Land Share Status</th>
                                    <th>Last Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="projects_table_body">
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="projects_error" class="alert alert-danger" style="display: none;">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <span id="error_message"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let projectsModal;

    document.addEventListener('DOMContentLoaded', function () {
        projectsModal = new bootstrap.Modal(document.getElementById('projectsModal'));

        // Count pending verification rows
        const pending = document.querySelectorAll('#clientsTable tbody tr.table-warning').length;
        const el = document.getElementById('pendingVerificationCount');
        if (el) el.textContent = pending;
    });

    function openProjectsModal(leadId, clientName) {
        document.getElementById('modal_client_name').textContent = clientName;
        document.getElementById('projects_loading').style.display = 'block';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'none';
        projectsModal.show();

        const url = '<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.lands && data.lands.length > 0) {
                    displayProjects(data.lands);
                } else {
                    showError(data.message || 'No land shares found for this client');
                }
            })
            .catch(err => showError('Failed to load: ' + err.message));
    }

    function displayProjects(lands) {
        const tbody = document.getElementById('projects_table_body');
        tbody.innerHTML = '';

        lands.forEach(land => {
            const row = document.createElement('tr');
            const bookingStatusClass = getStatusClass(land.booking_status);
            const landshareStatusClass = getStatusClass(land.landshare_status);

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${escapeHtml(land.booking_agent_name || 'System')}</td>
            <td>${Number(land.flat_size).toLocaleString('en-BD')} sqft</td>
            <td>TK.${Number(land.total_price).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.agreement_value).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.total_paid || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>
                <div><strong>Fee:</strong> TK.${Number(land.total_service_fee || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</div>
                <div class="text-muted small"><strong>Balance:</strong> TK.${Number(land.service_fee_balance || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</div>
            </td>
            <td><span class="bd-badge ${bookingStatusClass}">${escapeHtml(land.booking_status || 'N/A')}</span></td>
            <td><span class="bd-badge ${landshareStatusClass}">${escapeHtml(land.landshare_status || 'N/A')}</span></td>
            <td>${land.last_payment_date ? formatDate(land.last_payment_date) : 'No Payment'}</td>
            <td>
                <a class="table__icon download"
                    href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history/${land.booking_id}"
                    title="View Transaction History">
                    <i class="fa-regular fa-eye"></i>
                </a>
            </td>`;
            tbody.appendChild(row);
        });

        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_content').style.display = 'block';
    }

    function showError(message) {
        document.getElementById('error_message').textContent = message;
        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_error').style.display = 'block';
    }

    function getStatusClass(status) {
        if (!status) return 'bg-secondary';
        status = status.toLowerCase();
        if (['booked','active','approved','paid'].includes(status)) return 'bg-success';
        if (status === 'pending') return 'bg-warning';
        if (['cancelled','rejected'].includes(status)) return 'bg-danger';
        return 'bg-info';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>

<style>
    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 16px;
    }
    .card__title { font-size: 1.25rem; }
    /* Amber highlight for provisional / pending rows */
    tr.table-warning td { background-color: rgba(245, 158, 11, 0.08) !important; }
    tr.table-danger  td { background-color: rgba(220, 53, 69, 0.06) !important; }
    @media (max-width: 575px) {
        .dataTables_wrapper .dataTables_paginate .paginate_button { font-size: 12px; padding: 6px 8px; }
    }
</style>