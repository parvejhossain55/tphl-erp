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
$todayDate  = date('Y-m-d');
$isBefore12 = (date('H:i') < '12:00');

// Strictly active ONLY up to 12:00 PM (noon)! After 12:00 PM, do not show bottom line or modal.
if (!$isBefore12) {
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
?>

<!-- ========================================================================= -->
<!-- Floating Mini Indicator Styles & Element (Positioned Above Back-to-Top)    -->
<!-- ========================================================================= -->
<style>
#floatingMealWidget {
    position: fixed !important;
    bottom: 110px !important;
    right: 30px !important;
    z-index: 99999 !important;
    opacity: 1 !important;
    filter: none !important;
    pointer-events: auto !important;
    max-width: calc(100vw - 60px) !important;
    box-sizing: border-box !important;
}
#floatingMealWidget button#btnFloatingMealWidget {
    all: unset !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 8px 16px !important;
    border-radius: 50px !important;
    font-family: inherit !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    line-height: 1.4 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    opacity: 1 !important;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}
#floatingMealWidget button#btnFloatingMealWidget:hover {
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.45) !important;
}
#dailyMealNoticeModal {
    z-index: 10050 !important;
}
</style>

<div id="floatingMealWidget">
    <button type="button" id="btnFloatingMealWidget" 
            style="all: unset !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 8px 16px !important; border-radius: 50px !important; font-family: inherit !important; font-size: 13px !important; font-weight: 600 !important; line-height: 1.4 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; <?= $isOptedOut ? 'background: #dc2626 !important; color: #ffffff !important; border: 2px solid #b91c1c !important;' : 'background: #0f766e !important; color: #ffffff !important; border: 2px solid #0d5f58 !important;' ?> box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35) !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important;"
            data-bs-toggle="modal" data-bs-target="#dailyMealNoticeModal"
            title="আজকের মিল স্ট্যাটাস দেখুন ও পরিবর্তন করুন">
        <i class="fa-solid fa-utensils" style="font-size: 14px !important; color: #ffffff !important;"></i>
        <span id="floatingMealStatusText" style="color: #ffffff !important; font-weight: 700 !important; white-space: nowrap !important;">মিল: <?= $isOptedOut ? 'বন্ধ (Off)' : 'চালু (On)' ?></span>
        <?php if ($isBefore12): ?>
            <span id="dailyMealBadge" style="background: #ffffff !important; color: <?= $isOptedOut ? '#dc2626' : '#0f766e' ?> !important; font-size: 11px !important; font-weight: 700 !important; padding: 3px 9px !important; border-radius: 20px !important; box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important; white-space: nowrap !important;">১২টার আগে পরিবর্তন</span>
        <?php endif; ?>
    </button>
</div>

<!-- ========================================================================= -->
<!-- Modal: Daily Meal Opt-Out Notice (Compliant with conventions)             -->
<!-- ========================================================================= -->
<div class="modal custom-modal fade" id="dailyMealNoticeModal" tabindex="-1" role="dialog" aria-labelledby="dailyMealNoticeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold" id="dailyMealNoticeLabel">
                    <i class="fa-solid fa-utensils me-2" style="color: #0f766e;"></i> আজকের মিল সার্ভিস (Lunch Tracker)
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small"><i class="fa-solid fa-calendar-day me-1"></i> তারিখ: <strong><?= date('d M Y (l)') ?></strong></span>
                        <span class="badge bg-warning" style="color: #1e293b !important; font-weight: 700;">
                            <i class="fa-solid fa-clock me-1"></i> <?= $isBefore12 ? 'কাটঅফ: দুপুর ১২:০০ টা' : 'কাটঅফ শেষ' ?>
                        </span>
                    </div>
                    <h6 class="fw-bold mb-1">
                        শুভ <?= (date('H') < 12 ? 'সকাল' : (date('H') < 17 ? 'দুপুর' : 'সন্ধ্যা')) ?>, <?= htmlspecialchars($empFullName) ?>!
                    </h6>
                    <p class="text-muted small mb-0">কোম্পানি সাবসিডাইজড লাঞ্চ পলিসি অনুযায়ী আপনার মিলের স্ট্যাটাস নিচে দেখুন।</p>
                </div>

                <!-- Status Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded text-center <?= $isPresentToday ? 'bg-success bg-opacity-10 border-success' : 'bg-warning bg-opacity-10 border-warning' ?>">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-id-badge me-1"></i> আজকের অ্যাটেনডেন্স</div>
                            <span class="fw-bold <?= $isPresentToday ? 'text-success' : 'text-warning' ?>">
                                <?= $isPresentToday ? '<i class="fa-solid fa-check-circle me-1"></i> ' . htmlspecialchars($attStatus) : '<i class="fa-solid fa-circle-question me-1"></i> পাঞ্চ নেই' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded text-center <?= $isOptedOut ? 'bg-danger bg-opacity-10 border-danger' : 'bg-teal bg-opacity-10 border-teal' ?>" id="mealStatusBox">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-bowl-food me-1"></i> মিল কাউন্ট স্ট্যাটাস</div>
                            <span class="fw-bold <?= $isOptedOut ? 'text-danger' : 'text-success' ?>" id="mealStatusText">
                                <?= $isOptedOut ? '<i class="fa-solid fa-circle-xmark me-1"></i> অফ (Off)' : '<i class="fa-solid fa-circle-check me-1"></i> চালু (On)' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Guidance Info Box -->
                <div class="alert <?= $isOptedOut ? 'alert-danger' : 'alert-info' ?> py-2 px-3 small mb-3 d-flex align-items-center gap-2" id="mealStatusAlert">
                    <i class="fa-solid fa-circle-info fs-5"></i>
                    <div id="mealStatusAlertMsg">
                        <?php if ($isOptedOut): ?>
                            আপনি আজকের মিল <strong>বন্ধ (OFF)</strong> করেছেন। মিল ট্র্যাকারে আপনার মিল কাউন্ট হবে না।
                        <?php elseif ($isPresentToday): ?>
                            আপনি অ্যাটেনডেন্সে <strong>উপস্থিত</strong> আছেন। আজকের মিল ট্র্যাকারে স্বয়ংক্রিয়ভাবে টিক পড়বে।
                        <?php else: ?>
                            অ্যাটেনডেন্সে উপস্থিতি পেলে আপনার মিল স্বয়ংক্রিয়ভাবে কাউন্ট হবে। প্রয়োজন না হলে ১২টার আগে অফ করতে পারেন।
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 12 PM Action Box -->
                <?php if ($isBefore12): ?>
                    <div class="border rounded p-3 text-center mb-3" style="background: rgba(15, 118, 110, 0.05); border-color: rgba(15, 118, 110, 0.2) !important;">
                        <p class="mb-2 fw-semibold fs-14">
                            আপনি কি আজকের মিল <?= $isOptedOut ? 'পুনরায় চালু করতে চান?' : 'অফ করতে চান?' ?>
                        </p>
                        <button type="button" class="btn fw-bold px-4 py-2 <?= $isOptedOut ? 'btn-success' : 'btn-danger' ?>" id="btnToggleMealStatus" data-action="<?= $isOptedOut ? 'opt_in' : 'opt_out' ?>">
                            <i class="fa-solid <?= $isOptedOut ? 'fa-check me-1' : 'fa-ban me-1' ?>"></i> 
                            <span id="btnToggleMealText"><?= $isOptedOut ? 'আজকের মিল চালু করুন' : 'আজকের মিল অফ করুন' ?></span>
                        </button>
                        <div class="text-muted mt-2" style="font-size: 11px;">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> দুপুর ১২:০০ টার আগ পর্যন্ত যেকোনো সময় পরিবর্তন করা যাবে।
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary py-2 px-3 small mb-3 text-center">
                        <i class="fa-solid fa-lock me-1"></i> দুপুর ১২:০০ টার কাটঅফ সময় অতিক্রম হয়েছে। আজকের মিল স্ট্যাটাস লকড।
                    </div>
                <?php endif; ?>

                <div class="modal-footer px-0 pb-0 pt-2 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small d-flex align-items-center gap-1" style="font-size: 11px;">
                        <i class="fa-solid fa-clock-rotate-left" style="color: #0f766e;"></i>
                        <span>কাটঅফের আগ পর্যন্ত প্রতি ৩০ মিনিট পর রিমাইন্ডার আসবে</span>
                    </div>
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" id="btnDismissMealModal">
                        <?= $isOptedOut ? 'বন্ধ রাখুন' : 'মিল চালু থাকবে' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Frontend JavaScript (Safe Ready Checker Pattern as per rules)              -->
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
            const isBefore12 = <?= $isBefore12 ? 'true' : 'false' ?>;
            const intervalMs = 30 * 60 * 1000; // 30 minutes in milliseconds
            const lastShownKey = 'tphl_meal_last_shown_' + todayDate;

            // Rule 4 (DOM Placement & Stacking Context Fix): Always append modal & widget directly to body
            if ($('#dailyMealNoticeModal').parent().prop('tagName') !== 'BODY') {
                $('#dailyMealNoticeModal').appendTo('body');
            }
            if ($('#floatingMealWidget').parent().prop('tagName') !== 'BODY') {
                $('#floatingMealWidget').appendTo('body');
            }

            function isNowBefore12() {
                const now = new Date();
                return (now.getHours() < 12);
            }

            function shouldShowMealModal() {
                if (!isNowBefore12()) {
                    $('#floatingMealWidget').remove();
                    return false;
                }
                const lastShown = localStorage.getItem(lastShownKey);
                if (!lastShown) {
                    return true; // First time viewing today
                }
                const timeDiff = Date.now() - parseInt(lastShown, 10);
                return timeDiff >= intervalMs; // 30 minutes elapsed
            }

            function triggerMealModalIfDue() {
                if (!isNowBefore12()) {
                    $('#floatingMealWidget').remove();
                    const modalEl = document.getElementById('dailyMealNoticeModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getInstance(modalEl);
                        if (modalObj) modalObj.hide();
                    }
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

            // 1. Initial check on page load
            setTimeout(triggerMealModalIfDue, 600);

            // 2. Continuous check every 1 minute if 30 minutes have elapsed
            setInterval(triggerMealModalIfDue, 60000);

            // Delegated click handler to guarantee modal opens whenever the floating widget is clicked
            $(document).on('click', '#btnFloatingMealWidget, #floatingMealWidget', function(e) {
                const modalEl = document.getElementById('dailyMealNoticeModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalObj.show();
                    localStorage.setItem(lastShownKey, Date.now().toString());
                }
            });

            $('#btnDismissMealModal, #dailyMealNoticeModal .bd-btn-close').on('click', function() {
                localStorage.setItem(lastShownKey, Date.now().toString());
            });

            // Toggle Meal Action via AJAX
            $('#btnToggleMealStatus').on('click', function() {
                const btn = $(this);
                const action = btn.data('action');
                const origHtml = btn.html();

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

                fetch(`${baseUrl}/api/employee/toggle-today-meal`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ action: action })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(origHtml);

                    if (res.success) {
                        // Reset 30-minute interval timer on action
                        localStorage.setItem(lastShownKey, Date.now().toString());

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: res.status === 'opt_out' ? 'মিল অফ করা হয়েছে' : 'মিল চালু করা হয়েছে',
                                text: res.message,
                                timer: 2500,
                                showConfirmButton: false
                            });
                        }

                        // Update Modal UI live
                        if (res.status === 'opt_out') {
                            btn.removeClass('btn-danger').addClass('btn-success');
                            btn.data('action', 'opt_in');
                            $('#btnToggleMealText').text('আজকের মিল চালু করুন');
                            btn.find('i').removeClass('fa-ban').addClass('fa-check');

                            $('#mealStatusText').removeClass('text-success').addClass('text-danger').html('<i class="fa-solid fa-circle-xmark me-1"></i> অফ (Off)');
                            $('#mealStatusBox').removeClass('bg-teal border-teal').addClass('bg-danger bg-opacity-10 border-danger');
                            $('#mealStatusAlert').removeClass('alert-info').addClass('alert-danger');
                            $('#mealStatusAlertMsg').html('আপনি আজকের মিল <strong>বন্ধ (OFF)</strong> করেছেন। মিল ট্র্যাকারে আপনার মিল কাউন্ট হবে না।');
                            
                            // Dynamic update on floating pill widget
                            $('#btnFloatingMealWidget').attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 8px 16px !important; border-radius: 50px !important; font-family: inherit !important; font-size: 13px !important; font-weight: 600 !important; line-height: 1.4 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; background: #dc2626 !important; color: #ffffff !important; border: 2px solid #b91c1c !important; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35) !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important;');
                            $('#floatingMealStatusText').text('মিল: বন্ধ (Off)');
                            $('#dailyMealBadge').attr('style', 'background: #ffffff !important; color: #dc2626 !important; font-size: 11px !important; font-weight: 700 !important; padding: 3px 9px !important; border-radius: 20px !important; box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important; white-space: nowrap !important;');
                            $('#btnDismissMealModal').text('বন্ধ রাখুন');
                        } else {
                            btn.removeClass('btn-success').addClass('btn-danger');
                            btn.data('action', 'opt_out');
                            $('#btnToggleMealText').text('আজকের মিল অফ করুন');
                            btn.find('i').removeClass('fa-check').addClass('fa-ban');

                            $('#mealStatusText').removeClass('text-danger').addClass('text-success').html('<i class="fa-solid fa-circle-check me-1"></i> চালু (On)');
                            $('#mealStatusBox').removeClass('bg-danger border-danger').addClass('bg-teal bg-opacity-10 border-teal');
                            $('#mealStatusAlert').removeClass('alert-danger').addClass('alert-info');
                            $('#mealStatusAlertMsg').html('আপনার আজকের মিল <strong>চালু (ON)</strong> করা হয়েছে।');
                            
                            // Dynamic update on floating pill widget
                            $('#btnFloatingMealWidget').attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 8px 16px !important; border-radius: 50px !important; font-family: inherit !important; font-size: 13px !important; font-weight: 600 !important; line-height: 1.4 !important; white-space: nowrap !important; cursor: pointer !important; opacity: 1 !important; background: #0f766e !important; color: #ffffff !important; border: 2px solid #0d5f58 !important; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35) !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important;');
                            $('#floatingMealStatusText').text('মিল: চালু (On)');
                            $('#dailyMealBadge').attr('style', 'background: #ffffff !important; color: #0f766e !important; font-size: 11px !important; font-weight: 700 !important; padding: 3px 9px !important; border-radius: 20px !important; box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important; white-space: nowrap !important;');
                            $('#btnDismissMealModal').text('মিল চালু থাকবে');
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'ব্যর্থ হয়েছে',
                                text: res.message || 'Error occurred.'
                            });
                        } else {
                            alert(res.message);
                        }
                    }
                })
                .catch(err => {
                    btn.prop('disabled', false).html(origHtml);
                    console.error("Meal toggle error:", err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Failed to communicate with server.'
                        });
                    }
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
