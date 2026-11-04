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

$isMealEnrolled = ((int)($eRow['is_meal_deduction'] ?? 0) === 1);

$empFullName = !empty($eRow['full_name']) ? $eRow['full_name'] : ($_SESSION['user']['full_name'] ?? 'Colleague');

date_default_timezone_set('Asia/Dhaka');

// Strict 11:00 AM Cutoff: The floating meal widget and modal must NOT be shown after 11:00 AM
$isMealTestMode = isset($_GET['test_meal']) || isset($_GET['preview_meal']) || isset($_GET['test_modal']);
if (!$isMealTestMode && date('H:i') >= '11:00') {
    return;
}
?>

<!-- ========================================================================= -->
<!-- Floating All-Time Meal Status Widget (No annoying auto-popups)             -->
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
    padding: 6px 14px !important;
    border-radius: 30px !important;
    font-family: inherit !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    line-height: 1.3 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    opacity: 1 !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.3s ease !important;
}
#floatingMealWidget button#btnFloatingMealWidget:hover {
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28) !important;
}
#dailyMealNoticeModal {
    z-index: 10050 !important;
}
#dailyMealNoticeModal .modal-dialog {
    max-width: 480px;
    width: 92%;
    margin: 1.75rem auto;
}
#dailyMealNoticeModal .modal-content {
    border-radius: 20px !important;
    border: none !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2) !important;
    overflow: hidden !important;
    background: #ffffff !important;
}
</style>

<div id="floatingMealWidget">
    <button type="button" id="btnFloatingMealWidget" 
            style="all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 6px 14px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; <?= $isMealEnrolled ? 'background: #0f766e !important; color: #ffffff !important; border: 1.5px solid #0d5f58 !important;' : 'background: #475569 !important; color: #ffffff !important; border: 1.5px solid #334155 !important;' ?> box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22) !important;"
            data-bs-toggle="modal" data-bs-target="#dailyMealNoticeModal"
            title="Click to manage Meal Service status">
        <i class="fa-solid fa-utensils" style="font-size: 12px !important; color: #ffffff !important;"></i>
        <span id="floatingMealStatusText" style="color: #ffffff !important; font-weight: 700 !important; white-space: nowrap !important;">Meal: <?= $isMealEnrolled ? 'ON' : 'OFF' ?></span>
        <span id="dailyMealBadge" style="background: #ffffff !important; color: <?= $isMealEnrolled ? '#0f766e' : '#dc2626' ?> !important; font-size: 10.5px !important; font-weight: 700 !important; padding: 2px 8px !important; border-radius: 15px !important; box-shadow: 0 1px 4px rgba(0,0,0,0.12) !important; white-space: nowrap !important;"><?= $isMealEnrolled ? 'All-Time Active' : 'Disabled' ?></span>
    </button>
</div>

<!-- ========================================================================= -->
<!-- Modal: All-Time Meal Service Management                                    -->
<!-- ========================================================================= -->
<div class="modal custom-modal fade" id="dailyMealNoticeModal" tabindex="-1" role="dialog" aria-labelledby="dailyMealNoticeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-4 text-start">
            
            <!-- Row 1: Header Icon, Title & Close Button -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #e6f4ea; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-utensils" style="color: #0f766e; font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" style="color: #0f172a; font-size: 20px; letter-spacing: -0.3px;">All-Time Meal Service</h4>
                        <div class="text-muted" style="font-size: 13px; font-weight: 500;">Company Subsidized Lunch</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="box-shadow: none; font-size: 12px;"></button>
            </div>

            <!-- Greeting -->
            <div class="mb-3">
                <h5 class="fw-bold mb-1" style="color: #0f172a; font-size: 16px;">
                    Welcome, <?= htmlspecialchars($empFullName) ?>
                </h5>
                <p class="text-muted mb-0" style="font-size: 13px;">
                    Manage your subsidized office lunch service.
                </p>
            </div>

            <!-- Status Card -->
            <div class="p-3 mb-3" id="modalStatusCard" style="background: <?= $isMealEnrolled ? '#f0fdf4' : '#fef2f2' ?>; border: 1px solid <?= $isMealEnrolled ? '#bbf7d0' : '#fecaca' ?>; border-radius: 14px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div id="modalMealIconBox" style="width: 40px; height: 40px; border-radius: 50%; background: <?= $isMealEnrolled ? '#dcfce7' : '#fee2e2' ?>; display: flex; align-items: center; justify-content: center; color: <?= $isMealEnrolled ? '#16a34a' : '#dc2626' ?>; font-size: 16px; flex-shrink: 0;">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 500;">Current Meal Status</div>
                            <div class="fw-bold" id="modalMealStatusText" style="font-size: 15px; color: <?= $isMealEnrolled ? '#16a34a' : '#dc2626' ?>;">
                                <?= $isMealEnrolled ? '🟢 Meal Service is ON' : '🔴 Meal Service is OFF' ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="badge" id="modalMealPillBadge" style="background: <?= $isMealEnrolled ? '#16a34a' : '#dc2626' ?>; color: #fff; font-size: 11px; padding: 5px 10px; border-radius: 12px;">
                            <?= $isMealEnrolled ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Policy & Automatic Sync Highlights (Clear single lines) -->
            <div class="p-3 mb-3" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 12.5px; color: #475569;">
                <div class="fw-semibold text-dark mb-2"><i class="fa-solid fa-circle-info text-primary me-1"></i> How It Works:</div>
                <div class="mb-1 d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-check text-success mt-1" style="font-size: 11px;"></i>
                    <span><strong>Attendance Based:</strong> Lunch is auto-counted when you are marked Present or Late.</span>
                </div>
                <div class="mb-1 d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-xmark text-danger mt-1" style="font-size: 11px;"></i>
                    <span><strong>No Charge on Days Off:</strong> No meals on Weekends (Fri/Sat), Holidays, or Absent days.</span>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-user-gear text-secondary mt-1" style="font-size: 11px;"></i>
                    <span><strong>Admin Overrides:</strong> Admin or HR can adjust daily records anytime from Meal Tracker.</span>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mb-2">
                <button type="button" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" id="btnToggleAllTimeMeal" data-target-action="<?= $isMealEnrolled ? 'disable' : 'enable' ?>" style="<?= $isMealEnrolled ? 'background: #ffffff; color: #dc2626; border: 1.5px solid #f87171;' : 'background: #0f766e; color: #ffffff; border: none;' ?> border-radius: 10px; padding: 11px; font-size: 15px; transition: all 0.2s ease;">
                    <?php if ($isMealEnrolled): ?>
                        <i class="fa-solid fa-power-off"></i> Turn OFF Meal Service
                    <?php else: ?>
                        <i class="fa-solid fa-check"></i> Turn ON Meal Service
                    <?php endif; ?>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Frontend JavaScript (No annoying auto-popups, manual toggle only)          -->
<!-- ========================================================================= -->
<script>
(function() {
    function initAllTimeMealScript() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initAllTimeMealScript, 50);
            return;
        }

        jQuery(function($) {
            const baseUrl = "<?= $GLOBALS['base_url'] ?? '' ?>";

            // Automatically hide widget if time crosses 11:00 AM while user is on page
            function checkCutoff() {
                var now = new Date();
                if (now.getHours() >= 11) {
                    $('#floatingMealWidget').fadeOut(300, function() { $(this).remove(); });
                    var modalEl = document.getElementById('dailyMealNoticeModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalInst = bootstrap.Modal.getInstance(modalEl);
                        if (modalInst) modalInst.hide();
                    }
                }
            }
            setInterval(checkCutoff, 30000);

            // Append modal & floating widget directly to body to avoid clipping
            if ($('#dailyMealNoticeModal').parent().prop('tagName') !== 'BODY') {
                $('#dailyMealNoticeModal').appendTo('body');
            }
            if ($('#floatingMealWidget').parent().prop('tagName') !== 'BODY') {
                $('#floatingMealWidget').appendTo('body');
            }

            // Click on floating widget opens modal cleanly
            $(document).on('click', '#btnFloatingMealWidget, #floatingMealWidget', function(e) {
                const modalEl = document.getElementById('dailyMealNoticeModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalObj.show();
                }
            });

            // Handle All-Time Meal Toggle (Turn ON / Turn OFF)
            $('#btnToggleAllTimeMeal').on('click', function() {
                const btn = $(this);
                const targetAction = btn.attr('data-target-action'); // 'enable' or 'disable'
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

                fetch(`${baseUrl}/api/employee/toggle-all-time-meal`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ action: targetAction })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false);
                    if (res && res.success) {
                        const isEnrolled = (res.is_meal_deduction === 1);
                        
                        // Update floating pill
                        const floatBtn = $('#btnFloatingMealWidget');
                        if (isEnrolled) {
                            floatBtn.attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 6px 14px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; background: #0f766e !important; color: #ffffff !important; border: 1.5px solid #0d5f58 !important; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22) !important;');
                            $('#floatingMealStatusText').text('Meal: ON');
                            $('#dailyMealBadge').css({ color: '#0f766e' }).text('All-Time Active');
                        } else {
                            // Turned OFF: keep floating widget visible with OFF status
                            floatBtn.attr('style', 'all: unset !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 6px 14px !important; border-radius: 30px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.3 !important; white-space: nowrap !important; cursor: pointer !important; background: #475569 !important; color: #ffffff !important; border: 1.5px solid #334155 !important; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22) !important;');
                            $('#floatingMealStatusText').text('Meal: OFF');
                            $('#dailyMealBadge').css({ color: '#dc2626' }).text('Disabled');
                        }

                        // Update modal contents
                        if (isEnrolled) {
                            $('#modalStatusCard').css({ background: '#f0fdf4', borderColor: '#bbf7d0' });
                            $('#modalMealIconBox').css({ background: '#dcfce7', color: '#16a34a' });
                            $('#modalMealStatusText').css({ color: '#16a34a' }).html('🟢 Meal Service is ON');
                            $('#modalMealPillBadge').css({ background: '#16a34a' }).text('Active');
                            btn.attr('data-target-action', 'disable')
                               .attr('style', 'background: #ffffff; color: #dc2626; border: 1.5px solid #f87171; border-radius: 10px; padding: 11px; font-size: 15px;')
                               .html('<i class="fa-solid fa-power-off"></i> Turn OFF Meal Service');
                        } else {
                            $('#modalStatusCard').css({ background: '#fef2f2', borderColor: '#fecaca' });
                            $('#modalMealIconBox').css({ background: '#fee2e2', color: '#dc2626' });
                            $('#modalMealStatusText').css({ color: '#dc2626' }).html('🔴 Meal Service is OFF');
                            $('#modalMealPillBadge').css({ background: '#dc2626' }).text('Disabled');
                            btn.attr('data-target-action', 'enable')
                               .attr('style', 'background: #0f766e; color: #ffffff; border: none; border-radius: 10px; padding: 11px; font-size: 15px;')
                               .html('<i class="fa-solid fa-check"></i> Turn ON Meal Service');
                        }

                        // SweetAlert Toast Notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: isEnrolled ? 'success' : 'info',
                                title: res.message || 'Status updated successfully',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }

                        // Close modal after brief moment
                        setTimeout(function() {
                            const modalEl = document.getElementById('dailyMealNoticeModal');
                            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const modalObj = bootstrap.Modal.getInstance(modalEl);
                                if (modalObj) modalObj.hide();
                            }
                        }, 700);

                    } else {
                        btn.html(origHtml);
                        alert(res.message || 'Failed to update status');
                    }
                })
                .catch(err => {
                    btn.prop('disabled', false).html(origHtml);
                    alert('Error updating status: ' + err.message);
                });
            });
        });
    }

    initAllTimeMealScript();
})();
</script>
