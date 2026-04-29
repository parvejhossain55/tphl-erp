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
                            <th>Profile</th>
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