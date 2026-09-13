<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user']['id'])) {
    return;
}

$currentUserId = (int)$_SESSION['user']['id'];
$currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currentUserRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
?>

<!-- Approval & Disbursement Reminder Modal -->
<div class="modal fade" id="approvalReminderModal" tabindex="-1" aria-labelledby="approvalReminderModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden position-relative" style="background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 104, 55, 0.25) !important;">
            
            <!-- Subtle Top Glow Gradient -->
            <div style="height: 6px; background: linear-gradient(90deg, #059669 0%, #10b981 50%, #34d399 100%);"></div>

            <div class="modal-body p-4 p-md-4 pt-3 position-relative">
                
                <!-- Close Button (X) -->
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" onclick="dismissApprovalReminder('dismiss')" aria-label="Close" style="z-index: 10;"></button>

                <!-- Top Role Pill Badge -->
                <div class="text-center mb-3">
                    <span class="badge rounded-pill px-3 py-2 fw-bold text-uppercase" id="armRoleBadge" style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 11px; letter-spacing: 0.5px;">
                        <span class="arm-pulse-dot me-1"></span>
                        <span id="armRoleBadgeText">APPROVAL DESK</span>
                    </span>
                </div>

                <!-- Headline & Subtitle -->
                <div class="text-center mb-3">
                    <h4 class="fw-bold mb-1 text-dark" id="armHeadline" style="font-size: 1.28rem; line-height: 1.35; font-family: 'Playfair Display', serif, system-ui;">
                        Accuracy today protects every decision tomorrow.
                    </h4>
                    <p class="text-muted small mb-0" id="armSubHeadline" style="font-size: 13px;">
                        Complete the final checks to keep every transaction clear and accountable.
                    </p>
                </div>

                <!-- Circular Stat & Alert Row -->
                <div class="row g-2 align-items-stretch mb-3">
                    <!-- Left: Radial Circular Counter -->
                    <div class="col-5">
                        <div class="card h-100 border-0 rounded-3 p-2 text-center d-flex flex-column align-items-center justify-content-center" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                            <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 82px; height: 82px;">
                                <svg class="arm-radial-svg" width="82" height="82" viewBox="0 0 82 82">
                                    <circle cx="41" cy="41" r="34" stroke="#e2e8f0" stroke-width="7" fill="none"></circle>
                                    <circle id="armProgressCircle" cx="41" cy="41" r="34" stroke="#059669" stroke-width="7" stroke-dasharray="213.6" stroke-dashoffset="50" stroke-linecap="round" fill="none" style="transition: stroke-dashoffset 0.8s ease; transform: rotate(-90deg); transform-origin: 50% 50%;"></circle>
                                </svg>
                                <div class="position-absolute text-center">
                                    <span class="fw-bold d-block text-dark lh-1" id="armTotalCountDisplay" style="font-size: 1.65rem;">0</span>
                                </div>
                            </div>
                            <span class="text-uppercase fw-bold text-muted mt-1" style="font-size: 9.5px; letter-spacing: 0.5px;">Checks Waiting</span>
                        </div>
                    </div>

                    <!-- Right: Info Warning Box -->
                    <div class="col-7">
                        <div class="card h-100 border-0 rounded-3 p-3 d-flex flex-row align-items-center" style="background: #fffbeb; border: 1px solid #fef3c7 !important;">
                            <div class="me-2 text-warning flex-shrink-0" style="font-size: 22px;">
                                <i class="fa-solid fa-file-circle-exclamation"></i>
                            </div>
                            <div class="text-start" style="font-size: 11.5px; line-height: 1.4; color: #92400e;">
                                <strong>Important Note:</strong><br>
                                <span id="armNoticeText">Unrecorded updates can delay approvals, site supplies, and financial reporting.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actionable Items Group List -->
                <div class="d-flex flex-column gap-2 mb-3" id="armActionItemsContainer">
                    <!-- Dynamic Items will be rendered here -->
                </div>

                <!-- Motivational Bottom Banner -->
                <div class="text-center py-2 px-3 rounded-pill mb-3" style="background: #f0fdf4; border: 1px dashed #bbf7d0;">
                    <span class="text-success fw-semibold" style="font-size: 11.5px;">
                        <i class="fa-solid fa-star me-1 text-warning"></i>
                        <span id="armQuoteText">Your accuracy keeps TPHL moving with confidence.</span>
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/all-approval-requests?status=my_pending" class="btn btn-success py-2 fw-bold text-white shadow-sm d-flex align-items-center justify-content-center gap-2" id="armPrimaryBtn" style="background: linear-gradient(135deg, #006837 0%, #004d29 100%); border: none; font-size: 14px; border-radius: 10px;">
                        <span id="armPrimaryBtnText">Complete Pending Updates</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <button type="button" class="btn btn-outline-secondary py-2 fw-semibold text-dark d-flex align-items-center justify-content-center gap-1" onclick="dismissApprovalReminder('dismiss')" style="font-size: 13.5px; border-radius: 10px; background: #ffffff; border-color: #cbd5e1;">
                        <span>Everything Is Updated</span>
                        <i class="fa-solid fa-check text-success"></i>
                    </button>
                </div>

                <!-- Remind Me in 30 mins link -->
                <div class="text-center mt-2">
                    <a href="javascript:void(0)" onclick="dismissApprovalReminder('snooze')" class="text-decoration-none text-muted extra-small fw-semibold hover-underline" style="font-size: 12px;">
                        <i class="fa-regular fa-clock me-1"></i>Remind me in 30 minutes
                    </a>
                </div>

                <!-- Footer Security Assurance -->
                <div class="text-center mt-3 pt-2 border-top">
                    <span class="text-muted" style="font-size: 11px;">
                        <i class="fa-solid fa-shield-halved text-secondary me-1"></i> Secure company activity &bull; Usually takes under 2 minutes
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.arm-pulse-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #10b981;
    box-shadow: 0 0 0 rgba(16, 185, 129, 0.5);
    animation: armPulse 1.8s infinite;
}
@keyframes armPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.arm-action-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease-in-out;
}
.arm-action-card:hover {
    transform: translateY(-1px);
    border-color: #cbd5e1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}
.hover-underline:hover {
    text-decoration: underline !important;
    color: #0f172a !important;
}
</style>

<script>
(function() {
    const currentUserId = <?= $currentUserId ?>;
    const currentUserRoleId = <?= $currentUserRoleId ?>;
    const baseUrl = '<?= $GLOBALS['base_url'] ?>';
    
    const STORAGE_KEY_NEXT = 'tphl_approval_modal_next_' + currentUserId;
    const STORAGE_KEY_INTERVAL = 'tphl_approval_modal_interval_' + currentUserId;

    let reminderModalInstance = null;
    let cachedAlertData = null;

    window.initApprovalReminderSystem = function() {
        checkAndFetchAlerts(false);
    };

    function getNextEligibleTimestamp() {
        const val = localStorage.getItem(STORAGE_KEY_NEXT);
        return val ? parseInt(val, 10) : 0;
    }

    function setNextEligibleTimestamp(msFromNow) {
        const target = Date.now() + msFromNow;
        localStorage.setItem(STORAGE_KEY_NEXT, target.toString());
    }

    function checkAndFetchAlerts(forceShow = false) {
        fetch(baseUrl + '/api/actionable-approval-alerts')
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) return;
                cachedAlertData = data;

                const totalPending = parseInt(data.total_pending, 10) || 0;
                const roleCategory = data.role_category || 'general';

                // 1. Synchronize Topbar Notification Badge & Bell Icon
                updateTopNotificationBadges(totalPending, roleCategory);

                // 2. Handle manual click on bell icon
                if (forceShow) {
                    if (totalPending <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'No Pending Approvals',
                                text: 'You currently have no pending approval or disbursement tasks.',
                                confirmButtonColor: '#006837',
                                timer: 3000,
                                timerProgressBar: true
                            });
                        } else {
                            alert('You currently have no pending approval or disbursement tasks.');
                        }
                        return;
                    }
                    renderReminderModal(data);
                    showReminderModal();
                    return;
                }

                // 3. For auto-popup: do NOT show if 0 pending or user is general employee
                if (totalPending <= 0 || roleCategory === 'employee') {
                    return;
                }

                // Store role interval
                const intervalMs = parseInt(data.interval_ms, 10) || (3600 * 1000);
                localStorage.setItem(STORAGE_KEY_INTERVAL, intervalMs.toString());

                const now = Date.now();
                const nextShow = getNextEligibleTimestamp();

                if (now >= nextShow) {
                    renderReminderModal(data);
                    showReminderModal();
                }
            })
            .catch(err => {
                console.error('Error checking actionable approval alerts:', err);
            });
    }

    function updateTopNotificationBadges(count, roleCategory) {
        // Notification bell badge in topbar header
        const topBadge = document.getElementById('topbarPendingApprovalBadge');
        const bellItem = document.getElementById('topbarApprovalTaskItem');

        if (roleCategory === 'employee') {
            if (bellItem) bellItem.style.setProperty('display', 'none', 'important');
            if (topBadge) topBadge.style.display = 'none';
            return;
        }

        // Approvers / Management / Heads:
        if (bellItem) {
            bellItem.style.removeProperty('display');
        }

        if (topBadge) {
            if (count > 0) {
                topBadge.textContent = count;
                topBadge.style.display = 'inline-block';
            } else {
                topBadge.style.display = 'none';
            }
        }
    }

    function renderReminderModal(data) {
        const roleBadgeText = document.getElementById('armRoleBadgeText');
        const headline = document.getElementById('armHeadline');
        const subHeadline = document.getElementById('armSubHeadline');
        const countDisplay = document.getElementById('armTotalCountDisplay');
        const quoteText = document.getElementById('armQuoteText');
        const primaryBtnText = document.getElementById('armPrimaryBtnText');
        const container = document.getElementById('armActionItemsContainer');
        const progressCircle = document.getElementById('armProgressCircle');

        if (roleBadgeText) roleBadgeText.textContent = data.role_badge || 'APPROVAL DESK';
        if (headline) headline.textContent = data.headline || 'Accuracy today protects every decision tomorrow.';
        if (subHeadline) subHeadline.textContent = data.sub_headline || 'Complete the final checks to keep transactions accountable.';
        if (countDisplay) countDisplay.textContent = data.total_pending;
        if (quoteText) quoteText.textContent = data.quote || 'Your accuracy keeps TPHL moving with confidence.';
        if (primaryBtnText) primaryBtnText.textContent = `Complete ${data.total_pending} Pending Update${data.total_pending > 1 ? 's' : ''}`;

        // Update radial progress circle offset
        if (progressCircle) {
            const circumference = 213.6;
            const percentage = Math.min(data.total_pending / 10, 1.0);
            const offset = circumference - (circumference * (0.3 + (percentage * 0.7)));
            progressCircle.style.strokeDashoffset = offset.toString();
        }

        // Render Action Item Cards
        if (container) {
            container.innerHTML = '';
            if (data.groups && data.groups.length > 0) {
                data.groups.forEach(group => {
                    const card = document.createElement('div');
                    card.className = 'arm-action-card d-flex align-items-center justify-content-between gap-2';

                    let btnClass = 'btn-warning text-dark';
                    let dotColor = '#f59e0b';
                    if (group.color === 'success') {
                        btnClass = 'btn-success text-white';
                        dotColor = '#10b981';
                    } else if (group.color === 'primary') {
                        btnClass = 'btn-primary text-white';
                        dotColor = '#3b82f6';
                    } else if (group.color === 'info') {
                        btnClass = 'btn-info text-dark';
                        dotColor = '#06b6d4';
                    }

                    card.innerHTML = `
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${dotColor}; flex-shrink: 0;"></span>
                            <div class="avatar avatar-sm rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="background: #f1f5f9; width: 34px; height: 34px;">
                                <i class="${group.icon || 'fa-solid fa-list-check'} text-secondary" style="font-size: 15px;"></i>
                            </div>
                            <div class="text-truncate">
                                <h6 class="mb-0 fw-bold text-dark" style="font-size: 13px;">${escapeHtml(group.title)}</h6>
                                <span class="text-muted extra-small d-block text-truncate" style="font-size: 11px;">${escapeHtml(group.subtitle)}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="${group.action_url || (baseUrl + '/all-approval-requests?status=my_pending')}" class="btn btn-sm ${btnClass} rounded-pill px-3 py-1 fw-bold extra-small text-nowrap" style="font-size: 11px; letter-spacing: 0.3px;">
                                ${escapeHtml(group.action_text || 'REVIEW')} <i class="fa-solid fa-chevron-right ms-1" style="font-size: 9px;"></i>
                            </a>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }
        }
    }

    function showReminderModal() {
        const modalEl = document.getElementById('approvalReminderModal');
        if (!modalEl) return;
        if (!reminderModalInstance) {
            reminderModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
        }
        reminderModalInstance.show();
    }

    window.dismissApprovalReminder = function(type) {
        const modalEl = document.getElementById('approvalReminderModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }

        if (type === 'snooze') {
            // Snooze for 30 minutes
            setNextEligibleTimestamp(30 * 60 * 1000);
        } else {
            // Dismiss: set interval to user role interval (6h, 2h, or 1h)
            let intervalMs = parseInt(localStorage.getItem(STORAGE_KEY_INTERVAL), 10);
            if (!intervalMs || isNaN(intervalMs)) {
                intervalMs = 3600 * 1000;
            }
            setNextEligibleTimestamp(intervalMs);
        }
    };

    window.openApprovalReminderManually = function() {
        checkAndFetchAlerts(true);
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Auto-run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApprovalReminderSystem);
    } else {
        initApprovalReminderSystem();
    }

    // Periodic check every 60 seconds (keeps interval accurate while user leaves browser tab open)
    setInterval(function() {
        const now = Date.now();
        const nextShow = getNextEligibleTimestamp();
        if (now >= nextShow) {
            checkAndFetchAlerts(false);
        }
    }, 60000);

})();
</script>
