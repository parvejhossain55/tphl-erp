<?php
/**
 * Executive Management Approval Schedule Notice Partial
 * Displays official approval time windows:
 * Morning: 11:00 AM - 01:30 PM
 * Evening: 05:00 PM - 07:00 PM
 *
 * Applicable for:
 * - All Approval Requests
 * - All Payment / Expense / Requisition request lists & creation pages
 *
 * NOTE: Management (Role 1, 3, 38) retains full 24/7 capability to review and approve anytime.
 */

$currRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$isMgmtUserNotice = in_array($currRoleId, [1, 3, 38], true) 
    || str_contains($currRoleName, 'management') 
    || str_contains($currRoleName, 'ceo') 
    || str_contains($currRoleName, 'director');

// Bangladesh Time (Asia/Dhaka) calculation
$tzDhaka = new DateTimeZone('Asia/Dhaka');
$nowDhaka = new DateTime('now', $tzDhaka);
$curHour = (int)$nowDhaka->format('G');
$curMin = (int)$nowDhaka->format('i');
$totalCurMins = $curHour * 60 + $curMin;

// Window 1: 11:00 AM (660) to 1:30 PM (810)
// Window 2: 5:00 PM (1020) to 7:00 PM (1140)
$isSlotMorning = ($totalCurMins >= 660 && $totalCurMins <= 810);
$isSlotEvening = ($totalCurMins >= 1020 && $totalCurMins <= 1140);
$isWindowActive = ($isSlotMorning || $isSlotEvening);

if ($isSlotMorning) {
    $sessionStatusLabel = 'Morning Session Active';
    $sessionStatusBadge = 'bg-success text-white';
    $sessionPulse = true;
    $remainingMinutes = 810 - $totalCurMins;
    $sessionSubtext = 'Active morning window ends in: ' . $remainingMinutes . ' min';
} elseif ($isSlotEvening) {
    $sessionStatusLabel = 'Evening Session Active';
    $sessionStatusBadge = 'bg-success text-white';
    $sessionPulse = true;
    $remainingMinutes = 1140 - $totalCurMins;
    $sessionSubtext = 'Active evening window ends in: ' . $remainingMinutes . ' min';
} elseif ($totalCurMins < 660) {
    $sessionStatusLabel = 'Next Session: Today 11:00 AM';
    $sessionStatusBadge = 'bg-warning text-dark';
    $sessionPulse = false;
    $diffMins = 660 - $totalCurMins;
    $h = floor($diffMins / 60);
    $m = $diffMins % 60;
    $sessionSubtext = 'First session starts in: ' . ($h > 0 ? "{$h} hr " : '') . "{$m} min";
} elseif ($totalCurMins > 810 && $totalCurMins < 1020) {
    $sessionStatusLabel = 'Next Session: Today 05:00 PM';
    $sessionStatusBadge = 'bg-info text-white';
    $sessionPulse = false;
    $diffMins = 1020 - $totalCurMins;
    $h = floor($diffMins / 60);
    $m = $diffMins % 60;
    $sessionSubtext = 'Second session starts in: ' . ($h > 0 ? "{$h} hr " : '') . "{$m} min";
} else {
    $sessionStatusLabel = 'Next Session: Tomorrow 11:00 AM';
    $sessionStatusBadge = 'bg-secondary text-white';
    $sessionPulse = false;
    $sessionSubtext = "Today's scheduled windows concluded • Resumes tomorrow at 11:00 AM";
}

$noticeId = 'mgmtApprovalNotice_' . substr(md5(__FILE__ . ($noticeContext ?? 'gen')), 0, 8);
?>

<div id="<?= $noticeId ?>" class="card border-0 shadow-sm mb-3 rounded-3 mgmt-schedule-notice-card">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 mgmt-notice-header-border">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mgmt-notice-avatar">
                    <i class="fa-solid fa-clock-rotate-left fs-14 text-white"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold fs-13 d-flex align-items-center gap-2 mgmt-notice-title">
                        <span>Management / DCEO / CEO Approval Schedule</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 fw-semibold px-2 py-0.5 rounded-pill">
                            Official Schedule
                        </span>
                    </h6>
                    <small class="fs-11 mgmt-notice-subtitle">Executive Approval Hours & Guidance (Routine Review Windows)</small>
                </div>
            </div>

            <!-- Live Status & Pulse Pill -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge <?= $sessionStatusBadge ?> rounded-pill px-3 py-1.5 fs-11 d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <?php if ($sessionPulse): ?>
                        <span class="spinner-grow spinner-grow-sm text-light" style="width: 7px; height: 7px; animation-duration: 1.2s;" role="status"></span>
                    <?php else: ?>
                        <i class="fa-regular fa-clock fs-11"></i>
                    <?php endif; ?>
                    <strong><?= $sessionStatusLabel ?></strong>
                </span>
                <button type="button" class="btn btn-xs btn-link text-muted p-0 text-decoration-none" onclick="toggleMgmtNoticeDetails('<?= $noticeId ?>')" title="Toggle details">
                    <i class="fa-solid fa-chevron-up fs-12 toggle-icon"></i>
                </button>
            </div>
        </div>

        <div class="mgmt-notice-content">
            <div class="row g-2 align-items-center">
                <!-- Two Time Slots Cards -->
                <div class="col-xl-6 col-lg-7 col-md-12">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Morning Slot -->
                        <div class="flex-fill p-2 rounded-2 d-flex align-items-center gap-2 mgmt-slot-box <?= $isSlotMorning ? 'is-active' : '' ?>">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background: rgba(255, 153, 0, 0.15); color: #e67e22;">
                                <i class="fa-solid fa-sun fs-13"></i>
                            </div>
                            <div class="line-height-tight">
                                <div class="fs-10 text-uppercase fw-bold mgmt-slot-label">Morning Session</div>
                                <div class="fs-12 fw-bold mgmt-slot-time">11:00 AM – 01:30 PM</div>
                            </div>
                            <?php if ($isSlotMorning): ?>
                                <span class="badge bg-success ms-auto fs-9 rounded-pill">Active</span>
                            <?php endif; ?>
                        </div>

                        <!-- Evening Slot -->
                        <div class="flex-fill p-2 rounded-2 d-flex align-items-center gap-2 mgmt-slot-box <?= $isSlotEvening ? 'is-active' : '' ?>">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fa-solid fa-moon fs-13"></i>
                            </div>
                            <div class="line-height-tight">
                                <div class="fs-10 text-uppercase fw-bold mgmt-slot-label">Evening Session</div>
                                <div class="fs-12 fw-bold mgmt-slot-time">05:00 PM – 07:00 PM</div>
                            </div>
                            <?php if ($isSlotEvening): ?>
                                <span class="badge bg-success ms-auto fs-9 rounded-pill">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info message depending on user role -->
                <div class="col-xl-6 col-lg-5 col-md-12">
                    <div class="p-2 rounded-2 fs-11 mgmt-desc-box">
                        <?php if ($isMgmtUserNotice): ?>
                            <div class="d-flex align-items-start gap-1.5 mgmt-desc-text">
                                <i class="fa-solid fa-user-tie text-primary mt-0.5 fs-12"></i>
                                <div>
                                    <strong class="mgmt-desc-title">Management Desk (Management / DCEO / CEO):</strong> 
                                    Your designated routine approval hours are shown above. 
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-0.5"></i>However, you are fully authorized to review and approve urgent pending requests at any time (24/7).</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-start gap-1.5 mgmt-desc-text">
                                <i class="fa-solid fa-circle-info text-primary mt-0.5 fs-12"></i>
                                <div>
                                    <strong class="mgmt-desc-title">Attention Requesters & Staff:</strong> 
                                    Payment requests and requisitions requiring Management / DCEO / CEO approval are officially reviewed during these daily executive hours. 
                                    <span class="mgmt-desc-muted">(Emergency requests may be approved by Management at any time).</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between mt-2 pt-1.5 border-top fs-10 mgmt-notice-footer">
                <div>
                    <i class="fa-solid fa-hourglass-half me-1 text-primary"></i><?= htmlspecialchars($sessionSubtext) ?>
                </div>
                <div class="d-none d-sm-block">
                    <i class="fa-solid fa-shield-halved me-1 text-success"></i>Approval Authority: Management / DCEO / CEO
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================================================= */
/* 1. LIGHT MODE (DEFAULT) STYLING                                          */
/* ========================================================================= */
#<?= $noticeId ?>.mgmt-schedule-notice-card {
    background: linear-gradient(135deg, #f0f7ff 0%, #f8fbff 100%) !important;
    border: 1px solid #cfe2ff !important;
    border-left: 5px solid #0d6efd !important;
    box-shadow: 0 4px 14px rgba(13, 110, 253, 0.06) !important;
    transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

#<?= $noticeId ?> .mgmt-notice-avatar {
    background-color: #0d6efd !important;
    width: 34px;
    height: 34px;
}

#<?= $noticeId ?> .mgmt-notice-header-border {
    border-bottom: 1px solid rgba(13, 110, 253, 0.12) !important;
}

#<?= $noticeId ?> .mgmt-notice-title {
    color: #1e293b !important;
}

#<?= $noticeId ?> .mgmt-notice-subtitle {
    color: #64748b !important;
}

#<?= $noticeId ?> .mgmt-slot-box {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    transition: all 0.2s ease;
}

#<?= $noticeId ?> .mgmt-slot-box.is-active {
    background-color: rgba(25, 135, 84, 0.08) !important;
    border-color: #198754 !important;
}

#<?= $noticeId ?> .mgmt-slot-label {
    color: #64748b !important;
}

#<?= $noticeId ?> .mgmt-slot-time {
    color: #0f172a !important;
}

#<?= $noticeId ?> .mgmt-desc-box {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
}

#<?= $noticeId ?> .mgmt-desc-title {
    color: #1e293b !important;
}

#<?= $noticeId ?> .mgmt-desc-muted {
    color: #64748b !important;
}

#<?= $noticeId ?> .mgmt-notice-footer {
    border-color: rgba(0, 0, 0, 0.08) !important;
    color: #64748b !important;
}

/* ========================================================================= */
/* 2. DARK MODE STYLING (Comprehensive Selectors)                           */
/* ========================================================================= */
:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
body.dark-mode #<?= $noticeId ?>.mgmt-schedule-notice-card,
body.dark-only #<?= $noticeId ?>.mgmt-schedule-notice-card,
body.dark #<?= $noticeId ?>.mgmt-schedule-notice-card,
html.dark #<?= $noticeId ?>.mgmt-schedule-notice-card,
body.dark-theme #<?= $noticeId ?>.mgmt-schedule-notice-card,
[data-bs-theme="dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
[data-theme="dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
[data-theme-mode="dark"] #<?= $noticeId ?>.mgmt-schedule-notice-card,
#<?= $noticeId ?>.is-dark-theme {
    background: linear-gradient(135deg, #182234 0%, #1e2a3f 100%) !important;
    border: 1px solid #28374f !important;
    border-left: 5px solid #3b82f6 !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45) !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
body.dark-mode #<?= $noticeId ?> .mgmt-notice-avatar,
body.dark-only #<?= $noticeId ?> .mgmt-notice-avatar,
body.dark #<?= $noticeId ?> .mgmt-notice-avatar,
html.dark #<?= $noticeId ?> .mgmt-notice-avatar,
body.dark-theme #<?= $noticeId ?> .mgmt-notice-avatar,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-notice-avatar,
#<?= $noticeId ?>.is-dark-theme .mgmt-notice-avatar {
    background-color: #2563eb !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
body.dark-mode #<?= $noticeId ?> .mgmt-notice-header-border,
body.dark-only #<?= $noticeId ?> .mgmt-notice-header-border,
body.dark #<?= $noticeId ?> .mgmt-notice-header-border,
html.dark #<?= $noticeId ?> .mgmt-notice-header-border,
body.dark-theme #<?= $noticeId ?> .mgmt-notice-header-border,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-notice-header-border,
#<?= $noticeId ?>.is-dark-theme .mgmt-notice-header-border {
    border-bottom: 1px solid rgba(59, 130, 246, 0.25) !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-title,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-title,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-title,
body.dark-mode #<?= $noticeId ?> .mgmt-notice-title,
body.dark-only #<?= $noticeId ?> .mgmt-notice-title,
body.dark #<?= $noticeId ?> .mgmt-notice-title,
html.dark #<?= $noticeId ?> .mgmt-notice-title,
body.dark-theme #<?= $noticeId ?> .mgmt-notice-title,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-notice-title,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-notice-title,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-notice-title,
#<?= $noticeId ?>.is-dark-theme .mgmt-notice-title {
    color: #f8fafc !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
body.dark-mode #<?= $noticeId ?> .mgmt-notice-subtitle,
body.dark-only #<?= $noticeId ?> .mgmt-notice-subtitle,
body.dark #<?= $noticeId ?> .mgmt-notice-subtitle,
html.dark #<?= $noticeId ?> .mgmt-notice-subtitle,
body.dark-theme #<?= $noticeId ?> .mgmt-notice-subtitle,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-notice-subtitle,
#<?= $noticeId ?>.is-dark-theme .mgmt-notice-subtitle {
    color: #94a3b8 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box,
body.dark-mode #<?= $noticeId ?> .mgmt-slot-box,
body.dark-only #<?= $noticeId ?> .mgmt-slot-box,
body.dark #<?= $noticeId ?> .mgmt-slot-box,
html.dark #<?= $noticeId ?> .mgmt-slot-box,
body.dark-theme #<?= $noticeId ?> .mgmt-slot-box,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-slot-box,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-slot-box,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-slot-box,
#<?= $noticeId ?>.is-dark-theme .mgmt-slot-box {
    background-color: #121927 !important;
    border: 1px solid #28374f !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
body.dark-mode #<?= $noticeId ?> .mgmt-slot-box.is-active,
body.dark-only #<?= $noticeId ?> .mgmt-slot-box.is-active,
body.dark #<?= $noticeId ?> .mgmt-slot-box.is-active,
html.dark #<?= $noticeId ?> .mgmt-slot-box.is-active,
body.dark-theme #<?= $noticeId ?> .mgmt-slot-box.is-active,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-slot-box.is-active,
#<?= $noticeId ?>.is-dark-theme .mgmt-slot-box.is-active {
    background-color: rgba(34, 197, 94, 0.15) !important;
    border-color: #22c55e !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-label,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-label,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-label,
body.dark-mode #<?= $noticeId ?> .mgmt-slot-label,
body.dark-only #<?= $noticeId ?> .mgmt-slot-label,
body.dark #<?= $noticeId ?> .mgmt-slot-label,
html.dark #<?= $noticeId ?> .mgmt-slot-label,
body.dark-theme #<?= $noticeId ?> .mgmt-slot-label,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-slot-label,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-slot-label,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-slot-label,
#<?= $noticeId ?>.is-dark-theme .mgmt-slot-label {
    color: #94a3b8 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-time,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-time,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-slot-time,
body.dark-mode #<?= $noticeId ?> .mgmt-slot-time,
body.dark-only #<?= $noticeId ?> .mgmt-slot-time,
body.dark #<?= $noticeId ?> .mgmt-slot-time,
html.dark #<?= $noticeId ?> .mgmt-slot-time,
body.dark-theme #<?= $noticeId ?> .mgmt-slot-time,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-slot-time,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-slot-time,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-slot-time,
#<?= $noticeId ?>.is-dark-theme .mgmt-slot-time {
    color: #f1f5f9 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-box,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-box,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-box,
body.dark-mode #<?= $noticeId ?> .mgmt-desc-box,
body.dark-only #<?= $noticeId ?> .mgmt-desc-box,
body.dark #<?= $noticeId ?> .mgmt-desc-box,
html.dark #<?= $noticeId ?> .mgmt-desc-box,
body.dark-theme #<?= $noticeId ?> .mgmt-desc-box,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-desc-box,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-desc-box,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-desc-box,
#<?= $noticeId ?>.is-dark-theme .mgmt-desc-box {
    background-color: #121927 !important;
    border: 1px solid #28374f !important;
    color: #cbd5e1 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-title,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-title,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-title,
body.dark-mode #<?= $noticeId ?> .mgmt-desc-title,
body.dark-only #<?= $noticeId ?> .mgmt-desc-title,
body.dark #<?= $noticeId ?> .mgmt-desc-title,
html.dark #<?= $noticeId ?> .mgmt-desc-title,
body.dark-theme #<?= $noticeId ?> .mgmt-desc-title,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-desc-title,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-desc-title,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-desc-title,
#<?= $noticeId ?>.is-dark-theme .mgmt-desc-title {
    color: #f8fafc !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-muted,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-muted,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-desc-muted,
body.dark-mode #<?= $noticeId ?> .mgmt-desc-muted,
body.dark-only #<?= $noticeId ?> .mgmt-desc-muted,
body.dark #<?= $noticeId ?> .mgmt-desc-muted,
html.dark #<?= $noticeId ?> .mgmt-desc-muted,
body.dark-theme #<?= $noticeId ?> .mgmt-desc-muted,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-desc-muted,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-desc-muted,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-desc-muted,
#<?= $noticeId ?>.is-dark-theme .mgmt-desc-muted {
    color: #94a3b8 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-footer,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-footer,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .mgmt-notice-footer,
body.dark-mode #<?= $noticeId ?> .mgmt-notice-footer,
body.dark-only #<?= $noticeId ?> .mgmt-notice-footer,
body.dark #<?= $noticeId ?> .mgmt-notice-footer,
html.dark #<?= $noticeId ?> .mgmt-notice-footer,
body.dark-theme #<?= $noticeId ?> .mgmt-notice-footer,
[data-bs-theme="dark"] #<?= $noticeId ?> .mgmt-notice-footer,
[data-theme="dark"] #<?= $noticeId ?> .mgmt-notice-footer,
[data-theme-mode="dark"] #<?= $noticeId ?> .mgmt-notice-footer,
#<?= $noticeId ?>.is-dark-theme .mgmt-notice-footer {
    border-color: #28374f !important;
    color: #94a3b8 !important;
}

:root[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
html[bd-theme="bd-theme-dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
body.dark-mode #<?= $noticeId ?> .badge.bg-primary-subtle,
body.dark-only #<?= $noticeId ?> .badge.bg-primary-subtle,
body.dark #<?= $noticeId ?> .badge.bg-primary-subtle,
html.dark #<?= $noticeId ?> .badge.bg-primary-subtle,
body.dark-theme #<?= $noticeId ?> .badge.bg-primary-subtle,
[data-bs-theme="dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
[data-theme="dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
[data-theme-mode="dark"] #<?= $noticeId ?> .badge.bg-primary-subtle,
#<?= $noticeId ?>.is-dark-theme .badge.bg-primary-subtle {
    background-color: rgba(59, 130, 246, 0.18) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.3) !important;
}

.line-height-tight {
    line-height: 1.25;
}
</style>

<script>
(function() {
    var noticeId = '<?= $noticeId ?>';
    
    function syncMgmtNoticeTheme() {
        var card = document.getElementById(noticeId);
        if (!card) return;
        var isDark = (document.documentElement.getAttribute('bd-theme') === 'bd-theme-dark')
            || (document.documentElement.getAttribute('data-bs-theme') === 'dark')
            || (document.documentElement.getAttribute('data-theme') === 'dark')
            || (document.documentElement.getAttribute('data-theme-mode') === 'dark')
            || document.body.classList.contains('dark-mode')
            || document.body.classList.contains('dark-only')
            || document.body.classList.contains('dark-theme')
            || document.body.classList.contains('dark')
            || document.documentElement.classList.contains('dark')
            || (localStorage.getItem('bd_theme_scheme') === 'bd-theme-dark');
            
        if (isDark) {
            card.classList.add('is-dark-theme');
        } else {
            card.classList.remove('is-dark-theme');
        }
    }

    // Run on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncMgmtNoticeTheme);
    } else {
        syncMgmtNoticeTheme();
    }
    setTimeout(syncMgmtNoticeTheme, 50);
    setTimeout(syncMgmtNoticeTheme, 300);

    // Watch for theme toggle changes
    try {
        var observer = new MutationObserver(function(mutations) {
            syncMgmtNoticeTheme();
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['bd-theme', 'data-bs-theme', 'data-theme', 'data-theme-mode', 'class']
        });
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class', 'data-theme']
        });
    } catch(e) {}
})();

if (typeof toggleMgmtNoticeDetails === 'undefined') {
    function toggleMgmtNoticeDetails(noticeId) {
        var card = document.getElementById(noticeId);
        if (!card) return;
        var content = card.querySelector('.mgmt-notice-content');
        var icon = card.querySelector('.toggle-icon');
        if (!content) return;
        if (content.style.display === 'none') {
            content.style.display = '';
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        } else {
            content.style.display = 'none';
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    }
}
</script>
