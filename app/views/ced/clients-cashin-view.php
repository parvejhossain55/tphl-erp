<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$show_breadcrumb = true;
?>

<div class="row">

    <!-- ══ STAT CARDS (existing) ══ -->
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

    <!-- ══ PROJECT FILTER DROPDOWN ══ -->
    <div class="col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-10">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-light fa-filter"></i></span>
                    </div>
                    <div>
                        <h6 class="card__sub-title mb-0">Filter by Project</h6>
                    </div>
                    <select id="projectFilter" class="form-select" style="max-width: 350px;" onchange="filterByProject(this.value)">
                        <option value="">All Projects</option>
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"
                                    <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if ($selectedProjectId && $selectedProjectName): ?>
                    <div class="d-flex align-items-center gap-10">
                        <span class="bd-badge bg-primary">
                            <i class="fa-light fa-building me-1"></i>
                            <?= htmlspecialchars($selectedProjectName) ?>
                        </span>
                        <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="bd-badge bg-danger" style="text-decoration:none;">
                            <i class="fa-light fa-xmark me-1"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ PROJECT FLAT STAT CARDS (shown only when project is selected) ══ -->
    <?php if ($selectedProjectId && $flatStats): ?>
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-light fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= (int)$flatStats['total_flats'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper" id="bookedFlatsCard" style="cursor: pointer;" onclick="openBookedFlatsModal()" title="Click to view booked flats list">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"><i class="fa-light fa-bookmark"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booked Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color: #f59e0b;"><?= (int)$flatStats['booked_flats'] ?></h4>
                            <small class="text-muted"><i class="fa-light fa-hand-pointer"></i> Click to view</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper" id="availableFlatsCard" style="cursor: pointer;" onclick="openAvailableFlatsModal()" title="Click to view available flats list">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span style="color: #10b981; background: rgba(16, 185, 129, 0.1);"><i class="fa-light fa-check-circle"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Available Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color: #10b981;"><?= (int)$flatStats['available_flats'] ?></h4>
                            <small class="text-muted"><i class="fa-light fa-hand-pointer"></i> Click to view</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <?php endif; ?>

    <!-- ══ CLIENTS TABLE ══ -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between mb-20">
                <h4>
                    Clients Information
                    <?php if ($selectedProjectName): ?>
                        <small class="text-muted" style="font-size: 14px; font-weight: 400;">
                            — <?= htmlspecialchars($selectedProjectName) ?> (<?= count($clients) ?> records)
                        </small>
                    <?php endif; ?>
                </h4>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Booking Agent(s)</th>
                            <th>Designation</th>
                            <th>Total Agreement Value</th>
                            <th>Total Paid</th>
                            <th>Last Payment Date</th>
                            <th>Current Month</th>
                            <th>Profile</th>
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

                                    <td>TK.<?= number_format($client['total_agreement_value'] ?? 0, 2) ?></td>
                                    <td>TK.<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $cms = $client['current_month_status'] ?? 'N/A';
                                            if ($cms === 'paid') echo '<span class="badge bg-success">Paid</span>';
                                            elseif ($cms === 'matured') echo '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
                                            elseif ($cms === 'pending') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            elseif ($cms === 'due') echo '<span class="badge bg-danger">Due</span>';
                                            elseif ($cms === 'future') echo '<span class="badge bg-secondary">Future</span>';
                                            else echo '<span class="text-muted">-</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                            onclick="openProfileModal(<?= $client['lead_id'] ?>)"
                                            title="View Client Profile" style="font-size:12px;">
                                            <i class="fa-light fa-id-card me-1"></i>Profile
                                        </button>
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
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="fa-light fa-folder-open fa-2x d-block mb-2 text-muted"></i>
                                    <span class="text-muted"><?= $selectedProjectId ? 'No clients found for this project' : 'No clients found' ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ══ PROJECTS MODAL (existing — per-lead land shares) ══ -->
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
                                    <th>Current Month</th>
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

<!-- ===== CLIENT PROFILE MODAL ===== -->
<div class="modal fade" id="clientProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color:#fff; border:0; padding:18px 24px;">
                <h5 class="modal-title" style="font-weight:600;">
                    <i class="fa-light fa-id-card me-2"></i>Client Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="profileModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading profile...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ BOOKED FLATS MODAL (shown when project selected) ══ -->
<?php if ($selectedProjectId && !empty($bookedFlats)): ?>
<div class="modal fade" id="bookedFlatsModal" tabindex="-1" aria-labelledby="bookedFlatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookedFlatsModalLabel">
                    <i class="fa-light fa-bookmark me-2"></i>
                    Booked Flats — <?= htmlspecialchars($selectedProjectName) ?>
                    <span class="bd-badge bg-primary ms-2"><?= count($bookedFlats) ?> flats</span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table__title">
                                <th>Unit</th>
                                <th>Floor</th>
                                <th>Size</th>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Agent</th>
                                <th>Agreement Value</th>
                                <th>Total Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookedFlats as $flat): ?>
                                <?php
                                    $due = max(0, (float)($flat['agreement_value'] ?? 0) - (float)($flat['total_paid'] ?? 0));
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['floor_no'] ?? 'N/A') ?></td>
                                    <td><?= number_format($flat['sqft'] ?? 0) ?> sqft</td>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['client_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['client_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['agent_name'] ?? 'N/A') ?></td>
                                    <td>TK.<?= number_format($flat['agreement_value'] ?? 0, 2) ?></td>
                                    <td class="text-success fw-semibold">TK.<?= number_format($flat['total_paid'] ?? 0, 2) ?></td>
                                    <td class="<?= $due > 0 ? 'text-danger' : 'text-success' ?> fw-semibold">
                                        TK.<?= number_format($due, 2) ?>
                                    </td>
                                    <td>
                                        <span class="bd-badge <?= $flat['booking_status'] === 'booked' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= htmlspecialchars(ucfirst($flat['booking_status'] ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="table__icon download"
                                            href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $flat['booking_id'] ?>"
                                            title="View Payment History">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══ AVAILABLE FLATS MODAL (shown when project selected) ══ -->
<?php if ($selectedProjectId && !empty($availableFlats)): ?>
<div class="modal fade" id="availableFlatsModal" tabindex="-1" aria-labelledby="availableFlatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="availableFlatsModalLabel">
                    <i class="fa-light fa-check-circle me-2 text-success"></i>
                    Available Flats — <?= htmlspecialchars($selectedProjectName) ?>
                    <span class="bd-badge bg-success ms-2"><?= count($availableFlats) ?> flats</span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table__title">
                                <th>Unit</th>
                                <th>Floor</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableFlats as $flat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['floor_no'] ?? 'N/A') ?></td>
                                    <td><?= number_format($flat['sqft'] ?? 0) ?> sqft</td>
                                    <td class="text-primary fw-semibold">TK.<?= number_format($flat['price_value'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="bd-badge bg-success">
                                            <?= htmlspecialchars(ucfirst($flat['flat_status'] ?? 'Available')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    let projectsModal;

    document.addEventListener('DOMContentLoaded', function () {
        projectsModal = new bootstrap.Modal(document.getElementById('projectsModal'));
    });

    // ── Project Filter ──────────────────────────────────────────────────────
    function filterByProject(projectId) {
        if (projectId) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/clients-cashin-view?project_id=' + projectId;
        } else {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/clients-cashin-view';
        }
    }

    // ── Booked Flats Modal ──────────────────────────────────────────────────
    function openBookedFlatsModal() {
        const modalEl = document.getElementById('bookedFlatsModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    // ── Available Flats Modal ───────────────────────────────────────────────
    function openAvailableFlatsModal() {
        const modalEl = document.getElementById('availableFlatsModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

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
            const transferBadge = parseInt(land.transfer_status) === 1 
                ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.7em;">Transferred</span>' 
                : '<span class="badge bg-success ms-1" style="font-size:0.7em;">Current</span>';

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)} ${transferBadge}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${escapeHtml(land.booking_agent_name || 'System')}</td>
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
                ${getCurrentMonthBadge(land.current_month_status)}
            </td>
            <td>
                <a class="table__icon download"
                    href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/${land.booking_id}"
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
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'block';
    }

    function getCurrentMonthBadge(status) {
        if (!status) return '<span class="text-muted">-</span>';
        
        switch(status.toLowerCase()) {
            case 'paid':
                return '<span class="badge bg-success">Paid</span>';
            case 'matured':
                return '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
            case 'pending':
                return '<span class="badge bg-warning text-dark">Pending</span>';
            case 'due':
                return '<span class="badge bg-danger">Due</span>';
            case 'future':
                return '<span class="badge bg-secondary">Future</span>';
            default:
                return '<span class="text-muted">-</span>';
        }
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

    // ─── Client Profile Modal ──────────────────────────────────────────
    function openProfileModal(leadId) {
        const body = document.getElementById('profileModalBody');
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading profile...</p></div>';
        new bootstrap.Modal(document.getElementById('clientProfileModal')).show();

        fetch('<?= $GLOBALS['base_url'] ?>/get-client-profile?lead_id=' + leadId)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.profile) {
                    body.innerHTML = renderProfile(data.profile);
                } else {
                    body.innerHTML = '<div class="alert alert-danger m-3"><i class="fa-light fa-exclamation-triangle me-1"></i>' + (data.message || 'Profile not found') + '</div>';
                }
            })
            .catch(err => {
                body.innerHTML = '<div class="alert alert-danger m-3"><i class="fa-light fa-exclamation-triangle me-1"></i>Failed to load: ' + err.message + '</div>';
            });
    }

    function renderProfile(p) {
        const v = (val) => escapeHtml(val || '—');
        const formatDate = (d) => {
            if (!d) return '—';
            const dt = new Date(d);
            return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        };
        const imgBase = '<?= $GLOBALS['base_url'] ?>';
        const clientImg = p.lead_image ? `<img src="${imgBase}/public/uploads/leads/${p.lead_image}" class="profile-img" alt="Client">` : '<div class="profile-img-placeholder"><i class="fa-light fa-user fa-3x"></i></div>';
        const nomineeImg = p.nomi_image ? `<img src="${imgBase}/public/uploads/leads/${p.nomi_image}" class="profile-img" alt="Nominee">` : '<div class="profile-img-placeholder"><i class="fa-light fa-user-shield fa-3x"></i></div>';

        return `
        <div class="profile-card">
            <!-- CLIENT SECTION -->
            <div class="profile-section">
                <div class="profile-section-header">
                    <i class="fa-light fa-user me-2"></i>Client Information
                </div>
                <div class="profile-content">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            ${clientImg}
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-6 mb-2"><div class="profile-label">Full Name</div><div class="profile-value">${v(p.full_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Name (Bangla)</div><div class="profile-value">${v(p.lead_name_bn)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Phone</div><div class="profile-value">${v(p.phone)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Alt. Phone</div><div class="profile-value">${v(p.alt_phone_number)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Email</div><div class="profile-value">${v(p.email)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Company</div><div class="profile-value">${v(p.company)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Designation</div><div class="profile-value">${v(p.designation)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Date of Birth</div><div class="profile-value">${formatDate(p.dob)}</div></div>
                            </div>
                        </div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Father's Name</div><div class="profile-value">${v(p.father_name)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Mother's Name</div><div class="profile-value">${v(p.mother_name)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Father (Bangla)</div><div class="profile-value">${v(p.father_name_bn)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Mother (Bangla)</div><div class="profile-value">${v(p.mother_name_bn)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">${v(p.identity_type) || 'NID'} No.</div><div class="profile-value">${v(p.nid_or_passport)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Nationality</div><div class="profile-value">${v(p.nationality)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Religion</div><div class="profile-value">${v(p.religion)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Residency</div><div class="profile-value">${v(p.residency_type)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Country</div><div class="profile-value">${v(p.country)}</div></div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Present Address</div><div class="profile-value">${v(p.address || p.present_add_bn)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Permanent Address</div><div class="profile-value">${v(p.permanent_address || p.permanent_add_bn)}</div></div>
                    </div>
                </div>
            </div>

            <!-- NOMINEE SECTION -->
            <div class="profile-section" style="margin-top:0;">
                <div class="profile-section-header" style="background: linear-gradient(135deg, #065f46, #059669);">
                    <i class="fa-light fa-user-shield me-2"></i>Nominee Information
                </div>
                <div class="profile-content">
                    ${p.nominee_name ? `
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            ${nomineeImg}
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-6 mb-2"><div class="profile-label">Nominee Name</div><div class="profile-value">${v(p.nominee_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Relation</div><div class="profile-value">${v(p.relation_with_lead)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Father's Name</div><div class="profile-value">${v(p.nomi_father_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Mother's Name</div><div class="profile-value">${v(p.nomi_mother_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Phone</div><div class="profile-value">${v(p.nomi_phone)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Email</div><div class="profile-value">${v(p.nomi_email)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Profession</div><div class="profile-value">${v(p.nomi_profession)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Date of Birth</div><div class="profile-value">${formatDate(p.nomi_dob)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">NID / Passport</div><div class="profile-value">${v(p.nomi_nid_passport)}</div></div>
                            </div>
                        </div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Present Address</div><div class="profile-value">${v(p.nomi_present_add)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Permanent Address</div><div class="profile-value">${v(p.nomi_permanent_address)}</div></div>
                    </div>
                    ` : '<p class="text-muted text-center py-3 mb-0"><i class="fa-light fa-info-circle me-1"></i>No nominee information on file.</p>'}
                </div>
            </div>
        </div>`;
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

    /* ─── Profile Modal ─── */
    .profile-card { font-family: inherit; }
    .profile-section { border-bottom: 1px solid #e5e7eb; }
    .profile-section-header {
        background: linear-gradient(135deg, #1e3a5f, #2563eb);
        color: #fff; padding: 10px 20px; font-size: 14px; font-weight: 600;
        letter-spacing: 0.3px;
    }
    .profile-content { padding: 20px 24px; }
    .profile-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
    .profile-value { font-size: 14px; color: #1f2937; font-weight: 500; }
    .profile-img {
        width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
        border: 3px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .profile-img-placeholder {
        width: 110px; height: 110px; border-radius: 50%;
        border: 3px dashed #d1d5db; display: inline-flex;
        align-items: center; justify-content: center; color: #9ca3af;
        background: #f9fafb;
    }
</style>