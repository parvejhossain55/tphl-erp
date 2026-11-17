<div class="content__wrapper">
    <div class="page-title-box">
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="page-title"><?= $page_title ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">HRM</a></li>
                    <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Quick Stats KPI Cards -->
    <div class="row  mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card custom-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-envelope fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-12 text-uppercase fw-semibold">Total Dispatched</span>
                            <h4 class="fw-bold text-dark mb-0" id="statTotalEmails">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card custom-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-user-check fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-12 text-uppercase fw-semibold">Approver Reminders</span>
                            <h4 class="fw-bold text-dark mb-0" id="statApproverEmails">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card custom-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-user fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-12 text-uppercase fw-semibold">Requester Updates</span>
                            <h4 class="fw-bold text-dark mb-0" id="statRequesterEmails">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card custom-card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-bolt fs-20"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-12 text-uppercase fw-semibold">Login Triggers</span>
                            <h4 class="fw-bold text-dark mb-0" id="statLoginTriggers">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card__wrapper shadow-sm rounded-3 bg-white p-3">
                <div class="card__header d-flex flex-wrap justify-content-between align-items-center gap-2 pb-3 border-bottom">
                    <div>
                        <h4 class="card__title mb-1 fw-bold text-dark">Tipsoi Email Dispatch History</h4>
                        <p class="text-muted fs-12 mb-0">Live audit log of all automated reminder emails sent to approvers and requesters.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all">All Logs</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="approver">Approvers </button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="requester">Requesters</button>
                        </div>
                        <button id="previewPendingBtn" class="btn btn-sm btn-outline-info"><i class="fa fa-eye me-1"></i> Live Pending Preview</button>
                        <button id="dispatchNowBtn" class="btn btn-sm btn-warning text-dark fw-semibold"><i class="fa fa-paper-plane me-1"></i> Dispatch Due Reminders</button>
                        <button id="refreshLogsBtn" class="btn btn-sm btn-light border"><i class="fa fa-sync me-1"></i> Refresh</button>
                    </div>
                </div>
                
                <div class="card-body p-0 pt-3">
                    <div class="table-responsive">
                        <table id="emailLogsTable" class="table table-hover table-bordered align-middle w-100 fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 130px;">Sent Time</th>
                                    <th style="width: 130px;">Recipient Type</th>
                                    <th>Recipient Details</th>
                                    <th style="width: 100px;">Trigger Source</th>
                                    <th style="width: 100px;">Pending Items</th>
                                    <th>Request Details</th>
                                    <th style="width: 90px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Live Pending Preview -->
<div class="modal fade" id="pendingPreviewModal" tabindex="-1" aria-labelledby="pendingPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="pendingPreviewModalLabel"><i class="fa-solid fa-list-check text-primary me-2"></i>Tipsoi Live Pending Requests & Cooldown Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Fetching live status across Tipsoi API...</p>
                </div>
                <div id="previewContent" class="d-none">
                    <div class="alert alert-info py-2 fs-12 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <i class="fa-solid fa-calendar-check text-primary me-1"></i>
                            <strong>Active Attendance Cycle:</strong> <span id="modalCycleLabel" class="badge bg-primary fs-12 px-2 py-1">26th to 25th</span>
                            <span class="text-muted ms-2">(Only pending requests within this cycle are included)</span>
                        </div>
                        <span class="badge bg-light text-dark border"><i class="fa-solid fa-clock text-success me-1"></i>Daily (11:00 AM) — Once Per Day</span>
                    </div>
                    
                    <ul class="nav nav-pills mb-3" id="previewTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-3 fs-13" id="tab-appr" data-bs-toggle="pill" data-bs-target="#content-appr" type="button">
                                👔 Approvers Pending (<span id="modalApprCount">0</span>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 fs-13" id="tab-req" data-bs-toggle="pill" data-bs-target="#content-req" type="button">
                                🧑‍💼 Requesters Pending (<span id="modalReqCount">0</span>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="previewTabsContent">
                        <!-- Approvers Tab -->
                        <div class="tab-pane fade show active" id="content-appr">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle fs-12">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Approver Name</th>
                                            <th>Email</th>
                                            <th class="text-center">Pending Approvals</th>
                                            <th>Daily Status (Once/Day)</th>
                                            <th>Last Reminder Sent</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalApprTbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Requesters Tab -->
                        <div class="tab-pane fade" id="content-req">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle fs-12">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee Name</th>
                                            <th>Email</th>
                                            <th class="text-center">Pending Applications</th>
                                            <th>Daily Status (Once/Day)</th>
                                            <th>Last Reminder Sent</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalReqTbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
$(document).ready(function() {
    console.log('[Tipsoi Logs] Initializing view...');
    let currentFilter = 'all';

    var baseUrl = (window.base_url || '<?= $GLOBALS['base_url'] ?? '' ?>').replace(/\/+$/, '');
    if (!baseUrl && window.location.pathname.indexOf('/TPHL-ERP') !== -1) {
        baseUrl = '/TPHL-ERP';
    }

    let table = $('#emailLogsTable').DataTable({
        ajax: {
            url: baseUrl + '/api/hrm/tipsoi-email-logs',
            dataSrc: function(json) {
                console.log('[Tipsoi Logs] Received data:', json);
                let logs = json.logs || [];
                
                // Update KPI Cards
                let total = logs.length;
                let approvers = logs.filter(l => (l.recipient_type || 'approver') === 'approver').length;
                let requesters = logs.filter(l => l.recipient_type === 'requester').length;
                let logins = logs.filter(l => (l.trigger_source || '').indexOf('login') !== -1).length;

                $('#statTotalEmails').text(total);
                $('#statApproverEmails').text(approvers);
                $('#statRequesterEmails').text(requesters);
                $('#statLoginTriggers').text(logins);

                // Apply custom tab filter
                if (currentFilter === 'approver') {
                    return logs.filter(l => (l.recipient_type || 'approver') === 'approver');
                } else if (currentFilter === 'requester') {
                    return logs.filter(l => l.recipient_type === 'requester');
                }
                return logs;
            },
            error: function(xhr, error, thrown) {
                console.error('[Tipsoi Logs] Ajax load error:', error, thrown, xhr.responseText);
            }
        },
        columns: [
            { 
                data: 'id',
                render: function(data) {
                    return '<span class="fw-bold text-muted">#' + data + '</span>';
                }
            },
            { 
                data: 'sent_at',
                defaultContent: '-',
                render: function(data) {
                    if (!data || data === '-') return '-';
                    let d = new Date(data);
                    return '<div class="fw-semibold text-dark">' + d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + '</div>' + 
                           '<small class="text-muted">' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) + '</small>';
                }
            },
            { 
                data: 'recipient_type',
                defaultContent: 'approver',
                render: function(data) {
                    if (data === 'requester') {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-user me-1"></i>Requester</span>';
                    }
                    return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="fa fa-user-check me-1"></i>Approver</span>';
                }
            },
            { 
                data: null,
                render: function(row) {
                    let name = row.recipient_name || row.approver_name || 'Unknown';
                    let email = row.recipient_email || row.approver_email || '-';
                    return '<div class="fw-bold text-dark">' + name + '</div>' + 
                           '<small class="text-muted"><i class="fa-regular fa-envelope me-1"></i>' + email + '</small>';
                }
            },
            { 
                data: 'trigger_source',
                defaultContent: 'login',
                render: function(data) {
                    if (!data || data === 'login') {
                        return '<span class="badge bg-light text-dark border"><i class="fa fa-sign-in-alt text-primary me-1"></i>Portal Login</span>';
                    } else if (data === 'cron') {
                        return '<span class="badge bg-dark-subtle text-dark border"><i class="fa fa-clock text-secondary me-1"></i>Cron Schedule</span>';
                    } else if (data === 'admin_manual') {
                        return '<span class="badge bg-warning-subtle text-dark border"><i class="fa fa-hand-pointer text-warning me-1"></i>Manual Admin</span>';
                    }
                    return '<span class="badge bg-light text-muted border">' + data + '</span>';
                }
            },
            { 
                data: null,
                render: function(row) {
                    let count = row.pending_count || 1;
                    let level = row.pending_level || 'Pending';
                    return '<div class="text-center">' +
                           '<span class="badge bg-warning text-dark px-2 py-1 fw-bold fs-11">' + count + ' Items</span><br>' +
                           '<small class="text-muted fs-11">' + level + '</small>' +
                           '</div>';
                }
            },
            { 
                data: 'request_details',
                defaultContent: '-',
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">—</span>';
                    let lines = data.split('\n');
                    if (lines.length === 1) return '<span class="text-dark">' + lines[0] + '</span>';
                    let html = '<ul class="mb-0 ps-3 fs-12">';
                    lines.slice(0, 3).forEach(function(l) {
                        html += '<li>' + l + '</li>';
                    });
                    if (lines.length > 3) {
                        html += '<li class="text-primary fw-semibold">+ ' + (lines.length - 3) + ' more request(s)...</li>';
                    }
                    html += '</ul>';
                    return html;
                }
            },
            { 
                data: 'status',
                defaultContent: '-',
                render: function(data, type, row) {
                    if (data === 'Success') {
                        return '<span class="badge bg-success px-2 py-1"><i class="fa fa-check me-1"></i>Success</span>';
                    }
                    let err = row.error_message ? '<br><small class="text-danger" title="' + row.error_message + '">Error Details</small>' : '';
                    return '<span class="badge bg-danger px-2 py-1"><i class="fa fa-times me-1"></i>Failed</span>' + err;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Filter Buttons
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        table.ajax.reload();
    });

    $('#refreshLogsBtn').on('click', function() {
        table.ajax.reload();
    });

    // Dispatch Due Reminders Button
    $('#dispatchNowBtn').on('click', function() {
        if (!confirm('Are you sure you want to run the reminder check and dispatch emails to all eligible approvers and requesters whose 7-day cooldown is complete or today is 23rd cutoff?')) {
            return;
        }
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Dispatching...');
        
        fetch(baseUrl + '/api/hrm/send-tipsoi-reminders')
            .then(r => r.json())
            .then(res => {
                btn.prop('disabled', false).html('<i class="fa fa-paper-plane me-1"></i> Dispatch Due Reminders');
                if (res.success) {
                    alert(res.message);
                    table.ajax.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            })
            .catch(err => {
                btn.prop('disabled', false).html('<i class="fa fa-paper-plane me-1"></i> Dispatch Due Reminders');
                alert('Network Error: ' + err.message);
            });
    });

    // Live Pending Preview Modal
    $('#previewPendingBtn').on('click', function() {
        console.log('[Tipsoi Logs] Opening preview modal...');
        var modalEl = document.getElementById('pendingPreviewModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bsModal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#pendingPreviewModal').modal('show');
        }

        $('#previewLoading').removeClass('d-none');
        $('#previewContent').addClass('d-none');

        fetch(baseUrl + '/api/tipsoi/send-reminders-test?mode=preview')
            .then(r => r.json())
            .then(res => {
                console.log('[Tipsoi Logs] Preview response:', res);
                $('#previewLoading').addClass('d-none');
                $('#previewContent').removeClass('d-none');

                if (!res.success) {
                    alert('Failed to fetch preview: ' + res.message);
                    return;
                }

                if (res.cycle_range && res.cycle_range.label) {
                    $('#modalCycleLabel').text(res.cycle_range.label);
                }

                // Render Approvers
                let apprs = res.approvers || [];
                $('#modalApprCount').text(apprs.length);
                let apprHtml = '';
                if (apprs.length === 0) {
                    apprHtml = '<tr><td colspan="5" class="text-center text-muted py-3">No pending approver requests! All clear.</td></tr>';
                } else {
                    apprs.forEach(a => {
                        let statusBadge = a.should_send 
                            ? '<span class="badge bg-success"><i class="fa fa-check me-1"></i>Eligible (Due Now)</span>' 
                            : '<span class="badge bg-secondary"><i class="fa fa-clock me-1"></i>Cooldown Active</span>';
                        let reason = '<small class="text-muted d-block">' + a.reason + '</small>';
                        let lastSent = a.last_sent ? '<small class="text-dark fw-semibold">' + a.last_sent + '</small>' : '<small class="text-muted">Never sent</small>';

                        let apprDetails = '';
                        if (a.requests && a.requests.length > 0) {
                            apprDetails = '<div class="text-start mt-2" style="font-size: 11px;">';
                            a.requests.slice(0, 5).forEach(function(item) {
                                apprDetails += '<div class="mb-1 pb-1 border-bottom"><span class="badge bg-light text-dark border me-1">' + item.type + '</span> <strong>' + (item.applicant_name || 'Staff') + '</strong> (' + (item.date || '') + ') <span class="badge bg-light-primary text-primary">' + (item.pending_level || '') + '</span></div>';
                            });
                            if (a.requests.length > 5) {
                                apprDetails += '<div class="text-primary fw-semibold">+ ' + (a.requests.length - 5) + ' more pending item(s)...</div>';
                            }
                            apprDetails += '</div>';
                        }

                        apprHtml += `<tr>
                            <td class="fw-bold">${a.name}</td>
                            <td>${a.email}</td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark fs-12 px-2 py-1">${a.pending_count}</span>
                                ${apprDetails}
                            </td>
                            <td>${statusBadge}${reason}</td>
                            <td>${lastSent}</td>
                        </tr>`;
                    });
                }
                $('#modalApprTbody').html(apprHtml);

                // Render Requesters
                let reqs = res.requesters || [];
                $('#modalReqCount').text(reqs.length);
                let reqHtml = '';
                if (reqs.length === 0) {
                    reqHtml = '<tr><td colspan="5" class="text-center text-muted py-3">No pending applications found! All clear.</td></tr>';
                } else {
                    reqs.forEach(r => {
                        let statusBadge = r.should_send 
                            ? '<span class="badge bg-success"><i class="fa fa-check me-1"></i>Eligible (Due Now)</span>' 
                            : '<span class="badge bg-secondary"><i class="fa fa-clock me-1"></i>Cooldown Active</span>';
                        let reason = '<small class="text-muted d-block">' + r.reason + '</small>';
                        let lastSent = r.last_sent ? '<small class="text-dark fw-semibold">' + r.last_sent + '</small>' : '<small class="text-muted">Never sent</small>';

                        let reqDetails = '';
                        if (r.requests && r.requests.length > 0) {
                            reqDetails = '<div class="text-start mt-2" style="font-size: 11px;">';
                            r.requests.forEach(function(item) {
                                reqDetails += '<div class="mb-1 pb-1 border-bottom"><span class="badge bg-light text-dark border me-1">' + item.type + '</span> <strong>' + (item.date || '') + '</strong><br><span class="text-muted">Stage:</span> <span class="text-primary fw-semibold">' + (item.pending_level || '') + '</span> (' + (item.target_approver_name || 'Approver') + ')</div>';
                            });
                            reqDetails += '</div>';
                        }

                        reqHtml += `<tr>
                            <td class="fw-bold">${r.name}</td>
                            <td>${r.email}</td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark fs-12 px-2 py-1">${r.pending_count}</span>
                                ${reqDetails}
                            </td>
                            <td>${statusBadge}${reason}</td>
                            <td>${lastSent}</td>
                        </tr>`;
                    });
                }
                $('#modalReqTbody').html(reqHtml);
            })
            .catch(err => {
                $('#previewLoading').addClass('d-none');
                alert('Preview Error: ' + err.message);
            });
    });
});
<?php $page_script = ob_get_clean(); ?>
