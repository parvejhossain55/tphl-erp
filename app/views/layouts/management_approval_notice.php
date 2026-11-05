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
    $sessionStatusLabel = 'সকালের সেশন চলমান (Morning Session Active)';
    $sessionStatusBadge = 'bg-success text-white';
    $sessionPulse = true;
    $remainingMinutes = 810 - $totalCurMins;
    $sessionSubtext = 'চলমান সেশন শেষ হতে বাকি: ' . $remainingMinutes . ' মিনিট';
} elseif ($isSlotEvening) {
    $sessionStatusLabel = 'বিকালের সেশন চলমান (Evening Session Active)';
    $sessionStatusBadge = 'bg-success text-white';
    $sessionPulse = true;
    $remainingMinutes = 1140 - $totalCurMins;
    $sessionSubtext = 'চলমান সেশন শেষ হতে বাকি: ' . $remainingMinutes . ' মিনিট';
} elseif ($totalCurMins < 660) {
    $sessionStatusLabel = 'পরবর্তী সেশন: আজ সকাল ১১:০০ AM';
    $sessionStatusBadge = 'bg-warning text-dark';
    $sessionPulse = false;
    $diffMins = 660 - $totalCurMins;
    $h = floor($diffMins / 60);
    $m = $diffMins % 60;
    $sessionSubtext = 'আজকের ১ম সেশন শুরু হতে বাকি: ' . ($h > 0 ? "{$h} ঘণ্টা " : '') . "{$m} মিনিট";
} elseif ($totalCurMins > 810 && $totalCurMins < 1020) {
    $sessionStatusLabel = 'পরবর্তী সেশন: আজ বিকাল ০৫:০০ PM';
    $sessionStatusBadge = 'bg-info text-white';
    $sessionPulse = false;
    $diffMins = 1020 - $totalCurMins;
    $h = floor($diffMins / 60);
    $m = $diffMins % 60;
    $sessionSubtext = 'আজকের ২য় সেশন শুরু হতে বাকি: ' . ($h > 0 ? "{$h} ঘণ্টা " : '') . "{$m} মিনিট";
} else {
    $sessionStatusLabel = 'পরবর্তী সেশন: আগামীকাল সকাল ১১:০০ AM';
    $sessionStatusBadge = 'bg-secondary text-white';
    $sessionPulse = false;
    $sessionSubtext = 'আজকের নির্ধারিত সেশন সমাপ্ত • আগামীকাল সকাল ১১:০০ AM পুনরায় শুরু হবে';
}

$noticeId = 'mgmtApprovalNotice_' . substr(md5(__FILE__ . ($noticeContext ?? 'gen')), 0, 8);
?>

<div id="<?= $noticeId ?>" class="card border-0 shadow-sm mb-3 rounded-3 mgmt-schedule-notice-card" style="background: linear-gradient(135deg, rgba(238, 246, 255, 0.95) 0%, rgba(245, 249, 255, 0.95) 100%); border-left: 5px solid #0d6efd !important;">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom border-primary border-opacity-10">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="background: #0d6efd; color: #fff; width: 34px; height: 34px;">
                    <i class="fa-solid fa-clock-rotate-left fs-14"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark fs-13 d-flex align-items-center gap-2">
                        <span>Management / DCEO / CEO Approval Schedule</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 fw-semibold px-2 py-0.5 rounded-pill">
                            অফিসিয়াল সময়সূচী
                        </span>
                    </h6>
                    <small class="text-muted fs-11">কার্যনির্বাহী অনুমোদন সময়সূচী ও দিকনির্দেশনা (Executive Routine Review Windows)</small>
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
                <button type="button" class="btn btn-xs btn-link text-muted p-0 text-decoration-none" onclick="toggleMgmtNoticeDetails('<?= $noticeId ?>')" title="বিস্তারিত হাইড/শো করুন">
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
                        <div class="flex-fill p-2 rounded-2 border d-flex align-items-center gap-2 <?= $isSlotMorning ? 'bg-success bg-opacity-10 border-success' : 'bg-white border-light-subtle' ?>">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background: rgba(255, 153, 0, 0.15); color: #e67e22;">
                                <i class="fa-solid fa-sun fs-13"></i>
                            </div>
                            <div class="line-height-tight">
                                <div class="fs-10 text-uppercase fw-bold text-muted">সকালের সেশন (Morning)</div>
                                <div class="fs-12 fw-bold text-dark">১১:০০ AM – ০১:৩০ PM</div>
                            </div>
                            <?php if ($isSlotMorning): ?>
                                <span class="badge bg-success ms-auto fs-9 rounded-pill">Active</span>
                            <?php endif; ?>
                        </div>

                        <!-- Evening Slot -->
                        <div class="flex-fill p-2 rounded-2 border d-flex align-items-center gap-2 <?= $isSlotEvening ? 'bg-success bg-opacity-10 border-success' : 'bg-white border-light-subtle' ?>">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fa-solid fa-moon fs-13"></i>
                            </div>
                            <div class="line-height-tight">
                                <div class="fs-10 text-uppercase fw-bold text-muted">বিকালের সেশন (Evening)</div>
                                <div class="fs-12 fw-bold text-dark">০৫:০০ PM – ০৭:০০ PM</div>
                            </div>
                            <?php if ($isSlotEvening): ?>
                                <span class="badge bg-success ms-auto fs-9 rounded-pill">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info message depending on user role -->
                <div class="col-xl-6 col-lg-5 col-md-12">
                    <div class="p-2 rounded-2 bg-white border border-light-subtle fs-11 text-secondary">
                        <?php if ($isMgmtUserNotice): ?>
                            <div class="d-flex align-items-start gap-1.5 text-dark">
                                <i class="fa-solid fa-user-tie text-primary mt-0.5 fs-12"></i>
                                <div>
                                    <strong>ম্যানেজমেন্ট ডেস্ক (Management / DCEO / CEO):</strong> 
                                    আপনার নির্ধারিত অফিসিয়াল অনুমোদন সময়সূচী উপরে উল্লেখিত রয়েছে। 
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-0.5"></i>তবে যেকোনো জরুরি রিকোয়েস্ট আপনি দিন-রাত যেকোনো সময় (২৪/৭) সরাসরি অনুমোদন করতে পারবেন।</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-start gap-1.5">
                                <i class="fa-solid fa-circle-info text-primary mt-0.5 fs-12"></i>
                                <div>
                                    <strong>সকল আবেদনকারী ও সংশ্লিষ্ট ডিপার্টমেন্টের জ্ঞাতার্থে:</strong> 
                                    ম্যানেজমেন্ট অনুমোদনের জন্য অপেক্ষমাণ পেমেন্ট ও ফান্ড রিকুইজিশনসমূহ সাধারণত প্রতিদিন উল্লেখিত সময়সূচী অনুযায়ী যাচাই ও অনুমোদন করা হয়। 
                                    <span class="text-muted">(জরুরি প্রয়োজনে ম্যানেজমেন্ট যেকোনো সময় বিশেষ অনুমোদন দিতে পারেন)।</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between mt-2 pt-1.5 border-top border-light-subtle fs-10 text-muted">
                <div>
                    <i class="fa-solid fa-hourglass-half me-1 text-primary"></i><?= htmlspecialchars($sessionSubtext) ?>
                </div>
                <div class="d-none d-sm-block">
                    <i class="fa-solid fa-shield-halved me-1 text-success"></i>অনুমোদন কর্তৃপক্ষ: Management / DCEO / CEO
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Mode adaptivity for Management Schedule Notice */
html[data-theme-mode="dark"] #<?= $noticeId ?>,
[data-theme-mode="dark"] #<?= $noticeId ?>,
[data-bs-theme="dark"] #<?= $noticeId ?>,
body.dark-theme #<?= $noticeId ?>,
body.dark #<?= $noticeId ?> {
    background: linear-gradient(135deg, rgba(17, 28, 46, 0.95) 0%, rgba(21, 35, 58, 0.95) 100%) !important;
    border-color: rgba(13, 110, 253, 0.4) !important;
}

html[data-theme-mode="dark"] #<?= $noticeId ?> .bg-white,
[data-theme-mode="dark"] #<?= $noticeId ?> .bg-white,
[data-bs-theme="dark"] #<?= $noticeId ?> .bg-white,
body.dark-theme #<?= $noticeId ?> .bg-white,
body.dark #<?= $noticeId ?> .bg-white {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}

html[data-theme-mode="dark"] #<?= $noticeId ?> .text-dark,
[data-theme-mode="dark"] #<?= $noticeId ?> .text-dark,
[data-bs-theme="dark"] #<?= $noticeId ?> .text-dark,
body.dark-theme #<?= $noticeId ?> .text-dark,
body.dark #<?= $noticeId ?> .text-dark {
    color: #f1f5f9 !important;
}

html[data-theme-mode="dark"] #<?= $noticeId ?> .border-light-subtle,
[data-theme-mode="dark"] #<?= $noticeId ?> .border-light-subtle,
[data-bs-theme="dark"] #<?= $noticeId ?> .border-light-subtle,
body.dark-theme #<?= $noticeId ?> .border-light-subtle,
body.dark #<?= $noticeId ?> .border-light-subtle {
    border-color: rgba(255, 255, 255, 0.08) !important;
}

.line-height-tight {
    line-height: 1.25;
}
</style>

<script>
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
