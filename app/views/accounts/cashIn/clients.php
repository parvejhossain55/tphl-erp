<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$addPage = 'add transaction';
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


    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between mb-20">
                <h4>Clients Information</h4>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Assigned Agent</th>
                            <th>Designation</th>
                            <th>Total Paid</th>
                            <th>Last Payment Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['designation'] ?? 'N/A') ?></td>

                                  
                                    <td>TK.<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
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
                            <!-- <tr>
                                <td colspan="11" class="text-center">No clients found</td>
                            </tr> -->
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
    });

    function openProjectsModal(leadId, clientName) {
        console.log('Opening modal for lead ID:', leadId);
        console.log('Client name:', clientName);

        // Set client name
        document.getElementById('modal_client_name').textContent = clientName;

        // Reset modal state
        document.getElementById('projects_loading').style.display = 'block';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'none';

        // Show modal
        projectsModal.show();

        // Construct URL
        const url = '<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId;
        console.log('Fetching from URL:', url);

        // Fetch projects
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }

                return response.text(); // First get as text to see what we're getting
            })
            .then(text => {
                console.log('Response text:', text);

                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);

                    if (data.success) {
                        if (data.lands && data.lands.length > 0) {
                            displayProjects(data.lands);
                        } else {
                            showError('No land shares found for this client');
                        }
                    } else {
                        showError(data.message || 'Failed to load land shares');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    showError('Invalid response from server. Please check console for details.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showError('Failed to load land shares: ' + error.message);
            });
    }

    function displayProjects(lands) {
        console.log('Displaying projects:', lands);

        const tbody = document.getElementById('projects_table_body');
        tbody.innerHTML = '';

        lands.forEach(land => {
            const row = document.createElement('tr');

            // Calculate due amount
            const dueAmount = (parseFloat(land.total_price) || 0) - (parseFloat(land.total_paid) || 0);

            // Determine status classes
            const bookingStatusClass = getStatusClass(land.booking_status);
            const landshareStatusClass = getStatusClass(land.landshare_status);

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${Number(land.flat_size).toLocaleString('en-BD')} sqft</td>
            <td>TK.${Number(land.total_price).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.agreement_value).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.total_paid || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>
                <div>
                    <strong>Fee:</strong> TK.${Number(land.total_service_fee || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}
                </div>
                <div class="text-muted small">
                    <strong>Balance:</strong> TK.${Number(land.service_fee_balance || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}
                </div>
            </td>
            <td>
                <span class="bd-badge ${bookingStatusClass}">
                    ${escapeHtml(land.booking_status || 'N/A')}
                </span>
            </td>
            <td>
                <span class="bd-badge ${landshareStatusClass}">
                    ${escapeHtml(land.landshare_status || 'N/A')}
                </span>
            </td>
            <td>
                ${land.last_payment_date ? formatDate(land.last_payment_date) : 'No Payment'}
            </td>
            <td>
                <a class="table__icon download"
                    href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history/${land.booking_id}"
                    title="View Transaction History">
                    <i class="fa-regular fa-eye"></i>
                </a>
            </td>
        `;

            tbody.appendChild(row);
        });

        // Show content, hide loading
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

        if (status === 'booked' || status === 'active' || status === 'approved' || status === 'paid') {
            return 'bg-success';
        } else if (status === 'pending') {
            return 'bg-warning';
        } else if (status === 'cancelled' || status === 'rejected') {
            return 'bg-danger';
        } else {
            return 'bg-info';
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-GB', options);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>


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

    /* Modal styles */
    .modal-lg {
        max-width: 900px;
    }

    .land-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        transition: all 0.3s;
        background-color: #fff;
    }

    .land-item:hover {
        background-color: #f8f9fa;
        border-color: var(--clr-theme-primary);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .land-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .status-active {
        background-color: #d4edda;
        color: #155724;
    }

    .status-completed {
        background-color: #cce5ff;
        color: #004085;
    }

    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }

    .land-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .land-detail-item {
        font-size: 13px;
        color: #666;
    }

    .land-detail-item strong {
        color: #333;
        display: block;
        margin-bottom: 2px;
        font-size: 12px;
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .loading-spinner i {
        font-size: 36px;
        margin-bottom: 10px;
    }
</style>