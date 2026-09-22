<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user']['id'])) {
    return;
}

$loggedInUserId = (int)$_SESSION['user']['id'];
$loggedInEmpId  = (int)($_SESSION['user']['employee_db_id'] ?? 0);
$dbConn         = db();

// Robust Employee Resolution if not already in session
if ($loggedInEmpId <= 0) {
    $uEmpId = (int)($_SESSION['user']['employee_id'] ?? 0);
    if ($uEmpId > 0) {
        $chkRes = $dbConn->query("SELECT id FROM employees WHERE id = {$uEmpId} AND deleted_at IS NULL LIMIT 1");
        if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
            $loggedInEmpId = (int)$chkRow['id'];
            $_SESSION['user']['employee_db_id'] = $loggedInEmpId;
        }
    }
    if ($loggedInEmpId <= 0) {
        $chkRes = $dbConn->query("SELECT id FROM employees WHERE user_id = {$loggedInUserId} AND deleted_at IS NULL LIMIT 1");
        if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
            $loggedInEmpId = (int)$chkRow['id'];
            $_SESSION['user']['employee_db_id'] = $loggedInEmpId;
        }
    }
}

// 1. If not linked to any valid employee record, do not show anything
if ($loggedInEmpId <= 0) {
    return;
}

// 2. Fetch fresh enrollment status directly from DB
$eRes = $dbConn->query("SELECT id, is_meal_deduction, full_name, office_branch_id FROM employees WHERE id = {$loggedInEmpId} AND deleted_at IS NULL LIMIT 1");
if (!$eRes || !($eRow = $eRes->fetch_assoc())) {
    return;
}

// 3. STRICT RULE: Must have is_meal_deduction = 1 (if 0 or NULL, exit immediately)
if ((int)($eRow['is_meal_deduction'] ?? 0) !== 1) {
    return;
}

$empFullName = !empty($eRow['full_name']) ? $eRow['full_name'] : ($_SESSION['user']['full_name'] ?? 'Colleague');

date_default_timezone_set('Asia/Dhaka');
$todayDate   = date('Y-m-d');
$currentTime = date('H:i');

// Test mode query parameter (?test_meal=1, ?preview_meal=1, ?test_modal=1) allows Mam to preview anytime
$isTestMode  = isset($_GET['test_meal']) || isset($_GET['preview_meal']) || isset($_GET['test_modal']);
$isBefore11  = ($currentTime < '11:00') || $isTestMode;

// Strictly active ONLY up to 11:00 AM! After 11:00 AM, neither modal nor bottom floating indicator will render
if (!$isBefore11) {
    return;
}

// Check opt-out status for today
$optRes = $dbConn->query("SELECT status, opt_out_time FROM employee_meal_opt_outs WHERE employee_id = {$loggedInEmpId} AND opt_out_date = '{$todayDate}' LIMIT 1");
$isOptedOut = false;
$optOutTime = null;
if ($optRes && $optRow = $optRes->fetch_assoc()) {
    $isOptedOut = ($optRow['status'] === 'opt_out');
    $optOutTime = $optRow['opt_out_time'];
}

// Check attendance status for today
$attRes = $dbConn->query("SELECT today_status, punch_in_time FROM emp_attendances WHERE employee_id = {$loggedInEmpId} AND attendance_date = '{$todayDate}' AND deleted_at IS NULL LIMIT 1");
$isPresentToday = false;
$attStatus = 'Not Marked Yet';
if ($attRes && $attRow = $attRes->fetch_assoc()) {
    $attStatus = $attRow['today_status'] ?? 'Not Marked Yet';
    $isPresentToday = in_array($attStatus, ['Present', 'Late', 'Half-Day']);
}

// Format Date in Bengali (e.g. রবিবার, ১৩ সেপ্টেম্বর ২০২৬)
$bnDays = [
    'Sunday' => 'রবিবার', 'Monday' => 'সোমবার', 'Tuesday' => 'মঙ্গলবার',
    'Wednesday' => 'বুধবার', 'Thursday' => 'বৃহস্পতিবার', 'Friday' => 'শুক্রবার', 'Saturday' => 'শনিবার'
];
$bnMonths = [
    'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
    'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
    'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
    'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
];
$bnNumbers = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

$dayNameEn = date('l');
$dayNum    = date('d');
$monthEn   = date('F');
$yearNum   = date('Y');

$bnDay     = $bnDays[$dayNameEn] ?? $dayNameEn;
$bnMonth   = $bnMonths[$monthEn] ?? $monthEn;
$bnDayNum  = strtr($dayNum, $bnNumbers);
$bnYear    = strtr($yearNum, $bnNumbers);

$formattedBnDate = "{$bnDay}, {$bnDayNum} {$bnMonth} {$bnYear}";
?>

<!-- ========================================================================= -->
<!-- Floating Mini Indicator Styles & Element (Active only before 11:00 AM)     -->
<!-- ========================================================================= -->
<style>
#floatingMealWidget {
    position: fixed !important;
    bottom: 16px !important;
    right: 20px !important;
    z-index: 99999 !important;
    opacity: 1 !important;
    filter: none !important;
    pointer-events: auto !important;
    max-width: calc(100vw - 40px) !important;
    box-sizing: border-box !important;
}
#floatingMealWidget button#btnFloatingMealWidget {
    all: unset !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 5px 12px !important;
    border-radius: 30px !important;
    font-family: inherit !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    line-height: 1.3 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    opacity: 1 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.25) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}
#floatingMealWidget button#btnFloatingMealWidget:hover {
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3) !important;
}
#dailyMealNoticeModal {
    z-index: 10050 !important;
}
#dailyMealNoticeModal .modal-dialog {
    max-width: 490px;
    width: 92%;
    margin: 1.75rem auto;
}
#dailyMealNoticeModal .modal-content {
    border-radius: 20px !important;
    border: none !important;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.16) !important;
    overflow: hidden !important;
    background: #ffffff !important;
}
#btnActionTurnOn:hover {
    background: #0d635c !important;
    transform: translateY(-1px);
}
#btnActionTurnOff:hover {
    background: #fef2f2 !important;
    border-color: #ef4444 !important;
    transform: translateY(-1px);
}

/* SweetAlert container and solid card styling to always render cleanly in front */
.swal2-container {
    z-index: 100099 !important;
}
.swal2-container.swal2-backdrop-show {
    background: rgba(15, 23, 42, 0.65) !important;
}
.swal2-container .swal2-popup {
    background: #ffffff !important;
    color: #1e293b !important;
    border-radius: 20px !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35) !important;
    padding: 24px 20px !important;
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
}
.swal2-container .swal2-title {
    color: #0f172a !important;
    font-size: 20px !important;
    font-weight: 700 !important;
}
.swal2-container .swal2-html-container {
    color: #475569 !important;
    font-size: 14px !important;
}
</style>

<div id="floatingMealWidget">
    <button type="button" id="btnFloatingMealWidget" 
            style="all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 5px 12px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; <?= $isOptedOut ? 'background: #dc2626 !important; color: #ffffff !important; border: 1.5px solid #b91c1c !important;' : 'background: #0f766e !important; color: #ffffff !important; border: 1.5px solid #0d5f58 !important;' ?> box-shadow: 0 3px 12px rgba(0, 0, 0, 0.25) !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important;"
            data-bs-toggle="modal" data-bs-target="#dailyMealNoticeModal"
            title="Click to view or update today's meal status">
        <i class="fa-solid fa-utensils" style="font-size: 11.5px !important; color: #ffffff !important;"></i>
        <span id="floatingMealStatusText" style="color: #ffffff !important; font-weight: 700 !important; white-space: nowrap !important;">Meal: <?= $isOptedOut ? 'OFF' : 'ON' ?></span>
        <span id="dailyMealBadge" style="background: #ffffff !important; color: <?= $isOptedOut ? '#dc2626' : '#0f766e' ?> !important; font-size: 10.5px !important; font-weight: 700 !important; padding: 2px 8px !important; border-radius: 15px !important; box-shadow: 0 1px 4px rgba(0,0,0,0.15) !important; white-space: nowrap !important;">Edit before 11 AM</span>
    </button>
</div>

<!-- ========================================================================= -->
<!-- Modal: Daily Meal Notice & Toggle (Matched 1:1 with Reference Design)      -->
<!-- ========================================================================= -->
<div class="modal custom-modal fade" id="dailyMealNoticeModal" tabindex="-1" role="dialog" aria-labelledby="dailyMealNoticeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-4">
            
            <!-- Row 1: Header Icon, Title & Close Button -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #e6f4ea; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-utensils" style="color: #0f766e; font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" style="color: #0f172a; font-size: 20px; letter-spacing: -0.3px;">আজকের মিল</h4>
                        <div class="text-muted" style="font-size: 13px; font-weight: 500;">Lunch Tracker</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="box-shadow: none; font-size: 12px;"></button>
            </div>

            <!-- Row 2: Date & Cutoff Info -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-1">
                <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 13px; font-weight: 600;">
                    <i class="fa-regular fa-calendar-days" style="color: #0f766e; font-size: 15px;"></i>
                    <span><?= $formattedBnDate ?></span>
                </div>
                <div style="background: #ffedd5; color: #9a3412; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fa-regular fa-clock" style="font-size: 12px;"></i> পরিবর্তনের শেষ সময় • সকাল ১১:০০
                </div>
            </div>

            <!-- Row 3: Greeting & Subtitle (User explicitly requested "শুভ সকাল") -->
            <div class="mb-3">
                <h5 class="fw-bold mb-1" style="color: #0f172a; font-size: 17px;">
                    শুভ সকাল, <?= htmlspecialchars($empFullName) ?>
                </h5>
                <p class="text-muted mb-0" style="font-size: 13px;">
                    আপনার আজকের দুপুরের মিল স্ট্যাটাস নিশ্চিত করুন।
                </p>
            </div>

            <!-- Row 4: Two Status Cards (Side by side in soft container) -->
            <div class="p-3 mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px;">
                <div class="row align-items-center g-2">
                    <!-- Left: Attendance -->
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; color: #16a34a; font-size: 15px; flex-shrink: 0;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <div style="font-size: 11.5px; color: #64748b; font-weight: 500;">আজকের উপস্থিতি</div>
                                <div class="fw-bold" style="font-size: 13.5px; color: <?= $isPresentToday ? '#16a34a' : '#d97706' ?>;">
                                    <?= $isPresentToday ? '<i class="fa-solid fa-check me-1"></i> উপস্থিত' : '<i class="fa-solid fa-clock me-1"></i> অপেক্ষমান' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Meal Status -->
                    <div class="col-6 border-start ps-3" style="border-color: #cbd5e1 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <div id="modalMealIconBox" style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isOptedOut ? '#fee2e2' : '#dcfce7' ?>; display: flex; align-items: center; justify-content: center; color: <?= $isOptedOut ? '#dc2626' : '#16a34a' ?>; font-size: 15px; flex-shrink: 0;">
                                <i class="fa-solid fa-utensils"></i>
                            </div>
                            <div>
                                <div style="font-size: 11.5px; color: #64748b; font-weight: 500;">বর্তমান মিল স্ট্যাটাস</div>
                                <div class="fw-bold" id="modalMealStatusText" style="font-size: 13.5px; color: <?= $isOptedOut ? '#dc2626' : '#16a34a' ?>;">
                                    <?= $isOptedOut ? '🔴 বন্ধ আছে' : '🟢 চালু আছে' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 5: Action Section -->
            <div class="mb-3">
                <h6 class="fw-bold mb-1" style="color: #0f172a; font-size: 15px;">আজকের মিল চালু রাখবেন?</h6>
                <p class="text-muted mb-3" style="font-size: 12.5px;">প্রয়োজনে নির্ধারিত সময়ের আগে স্ট্যাটাস পরিবর্তন করুন।</p>

                <!-- Button 1: Turn On / Keep On -->
                <button type="button" class="btn w-100 mb-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" id="btnActionTurnOn" style="background: #0f766e; color: #ffffff; border: none; border-radius: 10px; padding: 11px; font-size: 15px; transition: all 0.2s ease;">
                    <i class="fa-solid fa-utensils"></i> মিল চালু রাখুন
                </button>

                <!-- Button 2: Turn Off / Stop -->
                <button type="button" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" id="btnActionTurnOff" style="background: #ffffff; color: #dc2626; border: 1.5px solid #f87171; border-radius: 10px; padding: 11px; font-size: 15px; transition: all 0.2s ease;">
                    <i class="fa-solid fa-power-off"></i> আজকের মিল বন্ধ করুন
                </button>
            </div>

            <!-- Row 6: Lock Notice -->
            <div class="d-flex align-items-center gap-2 mb-3" style="background: #f1f5f9; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 12px; font-weight: 500;">
                <i class="fa-solid fa-lock text-secondary"></i>
                <span>সকাল ১১:০০টার পর আজকের মিল পরিবর্তন করা যাবে না।</span>
            </div>

            <!-- Row 7: Reminder Checkbox -->
            <div class="d-flex align-items-center gap-2 pt-1" style="font-size: 12.5px; color: #475569;">
                <i class="fa-solid fa-bell" style="color: #16a34a; font-size: 14px;"></i>
                <label class="form-check-label d-flex align-items-center gap-2 mb-0" style="cursor: pointer;">
                    <input class="form-check-input mt-0" type="checkbox" id="chkMealReminder30Min" checked style="cursor: pointer;">
                    <span>সময়সীমার ৩০ মিনিট আগে আবার মনে করিয়ে দিন</span>
                </label>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Frontend JavaScript (Cutoff at 11:00 AM & Stacking Context Safe)           -->
<!-- ========================================================================= -->
<script>
(function() {
    function initDailyMealNoticeScript() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initDailyMealNoticeScript, 50);
            return;
        }

        jQuery(function($) {
            const baseUrl = "<?= $GLOBALS['base_url'] ?>";
            const todayDate = "<?= $todayDate ?>";
            const isTestMode = <?= $isTestMode ? 'true' : 'false' ?>;
            const intervalMs = 30 * 60 * 1000; // 30 minutes in milliseconds
            const lastShownKey = 'tphl_meal_last_shown_' + todayDate;
            const reminderDisabledKey = 'tphl_meal_remind_disabled_' + todayDate;

            // Rule 4: Append modal & floating widget directly to body
            if ($('#dailyMealNoticeModal').parent().prop('tagName') !== 'BODY') {
                $('#dailyMealNoticeModal').appendTo('body');
            }
            if ($('#floatingMealWidget').parent().prop('tagName') !== 'BODY') {
                $('#floatingMealWidget').appendTo('body');
            }

            // Restore reminder checkbox state
            if (localStorage.getItem(reminderDisabledKey) === '1') {
                $('#chkMealReminder30Min').prop('checked', false);
            }

            $('#chkMealReminder30Min').on('change', function() {
                if (!this.checked) {
                    localStorage.setItem(reminderDisabledKey, '1');
                } else {
                    localStorage.removeItem(reminderDisabledKey);
                }
            });

            // 11:00 AM Strict Cutoff Checker
            function isNowBefore11() {
                if (isTestMode) return true;
                const now = new Date();
                return (now.getHours() < 11);
            }

            function shouldShowMealModal() {
                if (!isNowBefore11()) {
                    $('#floatingMealWidget').remove();
                    $('#dailyMealNoticeModal').remove();
                    return false;
                }
                if (localStorage.getItem(reminderDisabledKey) === '1') {
                    return false; // User opted out of 30-min reminders
                }
                const lastShown = localStorage.getItem(lastShownKey);
                if (!lastShown) {
                    return true; // First time viewing today
                }
                const timeDiff = Date.now() - parseInt(lastShown, 10);
                return timeDiff >= intervalMs; // 30 minutes elapsed
            }

            function triggerMealModalIfDue() {
                if (!isNowBefore11()) {
                    $('#floatingMealWidget').remove();
                    const modalEl = document.getElementById('dailyMealNoticeModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getInstance(modalEl);
                        if (modalObj) modalObj.hide();
                    }
                    $('#dailyMealNoticeModal').remove();
                    return;
                }
                if (shouldShowMealModal()) {
                    const modalEl = document.getElementById('dailyMealNoticeModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalObj.show();
                        localStorage.setItem(lastShownKey, Date.now().toString());
                    }
                }
            }

            // Initial check on page load
            setTimeout(triggerMealModalIfDue, 600);

            // Continuous check every 60 seconds
            setInterval(triggerMealModalIfDue, 60000);

            // Click on floating widget opens modal
            $(document).on('click', '#btnFloatingMealWidget, #floatingMealWidget', function(e) {
                const modalEl = document.getElementById('dailyMealNoticeModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalObj.show();
                    localStorage.setItem(lastShownKey, Date.now().toString());
                }
            });

            // Handle Turn ON (মিল চালু রাখুন)
            $('#btnActionTurnOn').on('click', function() {
                const btn = $(this);
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

                fetch(`${baseUrl}/api/employee/toggle-today-meal`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ action: 'opt_in' })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(origHtml);
                    if (res.success) {
                        localStorage.setItem(lastShownKey, Date.now().toString());

                        // Update Status Card to ON
                        $('#modalMealStatusText').css('color', '#16a34a').text('🟢 চালু আছে');
                        $('#modalMealIconBox').css({ 'background': '#dcfce7', 'color': '#16a34a' });

                        // Update Floating Widget
                        $('#btnFloatingMealWidget').attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 5px 12px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; background: #0f766e !important; color: #ffffff !important; border: 1.5px solid #0d5f58 !important; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.25) !important;');
                        $('#floatingMealStatusText').text('Meal: ON');
                        $('#dailyMealBadge').css({ 'color': '#0f766e' });

                        // Close modal immediately so the message displays cleanly in front
                        const modalEl = document.getElementById('dailyMealNoticeModal');
                        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const modalObj = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                            if (modalObj) modalObj.hide();
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'মিল চালু আছে!',
                                text: 'আপনার আজকের দুপুরের মিল সফলভাবে চালু রাখা হয়েছে।',
                                timer: 2200,
                                showConfirmButton: false,
                                backdrop: 'rgba(15, 23, 42, 0.65)'
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Action Failed', text: res.message || 'Error occurred.' });
                        } else {
                            alert(res.message);
                        }
                    }
                })
                .catch(err => {
                    btn.prop('disabled', false).html(origHtml);
                    console.error("Meal turn on error:", err);
                });
            });

            // Handle Turn OFF (আজকের মিল বন্ধ করুন)
            $('#btnActionTurnOff').on('click', function() {
                const btn = $(this);
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

                fetch(`${baseUrl}/api/employee/toggle-today-meal`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ action: 'opt_out' })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(origHtml);
                    if (res.success) {
                        localStorage.setItem(lastShownKey, Date.now().toString());

                        // Update Status Card to OFF
                        $('#modalMealStatusText').css('color', '#dc2626').text('🔴 বন্ধ আছে');
                        $('#modalMealIconBox').css({ 'background': '#fee2e2', 'color': '#dc2626' });

                        // Update Floating Widget
                        $('#btnFloatingMealWidget').attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 5px 12px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; background: #dc2626 !important; color: #ffffff !important; border: 1.5px solid #b91c1c !important; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.25) !important;');
                        $('#floatingMealStatusText').text('Meal: OFF');
                        $('#dailyMealBadge').css({ 'color': '#dc2626' });

                        // Close modal immediately so the message displays cleanly in front
                        const modalEl = document.getElementById('dailyMealNoticeModal');
                        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const modalObj = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                            if (modalObj) modalObj.hide();
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'মিল বন্ধ করা হয়েছে',
                                text: 'আপনার আজকের দুপুরের মিল বন্ধ করা হয়েছে।',
                                timer: 2200,
                                showConfirmButton: false,
                                backdrop: 'rgba(15, 23, 42, 0.65)'
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Action Failed', text: res.message || 'Error occurred.' });
                        } else {
                            alert(res.message);
                        }
                    }
                })
                .catch(err => {
                    btn.prop('disabled', false).html(origHtml);
                    console.error("Meal turn off error:", err);
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailyMealNoticeScript);
    } else {
        initDailyMealNoticeScript();
    }
})();
</script>
