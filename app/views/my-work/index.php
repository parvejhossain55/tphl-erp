<?php
/**
 * View: My Work - Personal Workspace (Single Page, Zero Reload AJAX)
 */

$tab = $tab ?? 'all';
$search = $search ?? '';
$priorityFilter = $priorityFilter ?? '';
$statusFilter = $statusFilter ?? '';
$metricFilter = $metricFilter ?? '';

$overdueCount = $overdueCount ?? 0;
$dueTodayCount = $dueTodayCount ?? 0;
$next7DaysCount = $next7DaysCount ?? 0;
$waitingReviewCount = $waitingReviewCount ?? 0;
$openTasksCount = $openTasksCount ?? 0;
$completedTasksCount = $completedTasksCount ?? 0;
$tasks = $tasks ?? [];
$reviewTasks = $reviewTasks ?? [];
$blockedTask = $blockedTask ?? null;

// Date range for "Next 7 days" card
$next7Start = date('j F', strtotime('+1 day'));
$next7End = date('j F', strtotime('+7 days'));
$todayDateFormatted = date('j F');
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
.my-work-workspace {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #142D26;
    background-color: #F8FAF9;
    min-height: calc(100vh - 80px);
    padding: 24px 28px 40px;
}

/* Page Header */
.mw-header-tag {
    font-weight: 700;
    font-size: 10.5px;
    line-height: 13px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #65776F;
    margin-bottom: 4px;
}

.mw-header-title {
    font-weight: 700;
    font-size: 29px;
    line-height: 35px;
    color: #142D26;
    margin-bottom: 4px;
}

.mw-header-subtitle {
    font-weight: 400;
    font-size: 13.5px;
    line-height: 16px;
    color: #65776F;
    margin-bottom: 0;
}

.mw-btn-create {
    background-color: #087F5B;
    color: #FFFFFF !important;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    padding: 10px 18px;
    border-radius: 6px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s ease;
    text-decoration: none;
    cursor: pointer;
}

.mw-btn-create:hover {
    background-color: #066649;
    color: #FFFFFF !important;
}

/* Scope Tabs */
.mw-tabs-wrap {
    border-bottom: 1px solid #E2EAE5;
    margin-top: 24px;
    margin-bottom: 20px;
    display: flex;
    gap: 28px;
}

.mw-tab-item {
    font-weight: 700;
    font-size: 13px;
    line-height: 16px;
    color: #65776F;
    padding-bottom: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    position: relative;
    cursor: pointer;
    transition: color 0.15s ease;
}

.mw-tab-badge {
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 3px 7px;
    border-radius: 999px;
    background-color: #EDF2EF;
    color: #4D625A;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.mw-tab-item.active .mw-tab-badge {
    background-color: #E3F6EE;
    color: #087F5B;
}

.mw-tab-item:hover {
    color: #142D26;
}

.mw-tab-item.active {
    color: #087F5B;
}

.mw-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #087F5B;
}

/* Task detail drawer (right side) */
.mw-td-backdrop { position: fixed; inset: 0; background: rgba(20,45,38,.35); z-index: 9996; opacity: 0; visibility: hidden; transition: opacity .25s ease, visibility .25s ease; }
.mw-td-backdrop.show { opacity: 1; visibility: visible; }
.mw-td { position: fixed; top: 0; right: 0; bottom: 0; left: 51.25%; background: #fff; z-index: 9997; border-left: 1px solid #E2EAE5; box-shadow: -12px 0 40px rgba(0,0,0,.12); display: flex; flex-direction: column; transform: translateX(100%); transition: transform .32s cubic-bezier(.16,1,.3,1); font-family: 'Inter', sans-serif; }
.mw-td.show { transform: translateX(0); }
@media (max-width: 1100px) { .mw-td { left: 35%; } }
@media (max-width: 768px) { .mw-td { left: 0; } }
.mw-td-body { flex: 1; overflow-y: auto; padding: 14px 1.75% 16px 1.75%; }
.mw-td-top { display: flex; justify-content: space-between; align-items: center; font-size: 12px; line-height: 15px; color: #65776F; }
.mw-td-top-right { display: inline-flex; align-items: center; gap: 8px; }
.mw-td-remind-btn { background: #F0FAF6; border: 1px solid #BBE3D0; color: #087F5B; border-radius: 6px; padding: 5px 11px; font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600; line-height: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; }
.mw-td-remind-btn:hover:not(:disabled) { background: #087F5B; border-color: #087F5B; color: #FFFFFF; }
.mw-td-remind-btn:disabled { opacity: 0.65; cursor: not-allowed; }
.mw-td-close { background: none; border: 0; color: #65776F; font-size: 16px; cursor: pointer; padding: 4px; }
.mw-td-title { font-size: 24px; line-height: 29px; font-weight: 700; color: #142D26; margin: 14px 0 12px; }
.mw-td-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.mw-td-pill { font-size: 11.5px; line-height: 14px; font-weight: 700; padding: 5px 10px; border-radius: 6px; background: #EDF2EF; color: #65776F; }
.mw-td-pill.status { background: #EEF3FC; color: #4364A5; }
.mw-td-pill.overdue { background: #FFF5E2; color: #9A6216; }
.mw-td-tabs { display: flex; gap: 22px; align-items: center; border-bottom: 1px solid #E2EAE5; margin-bottom: 16px; }
.mw-td-tabs .mw-td-remind-btn { margin-left: auto; margin-bottom: 6px; }
.mw-td-tab { background: none; border: 0; padding: 0 0 9px; font-size: 13px; line-height: 16px; font-weight: 700; color: #65776F; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.mw-td-tab.active { color: #087F5B; border-bottom-color: #087F5B; }
.mw-td-blocked { background: #FFF5E2; color: #9A6216; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; }
.mw-td-blocked strong { display: block; font-size: 14px; line-height: 17px; }
.mw-td-blocked span { font-size: 12px; line-height: 15px; }
.mw-td-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; margin-bottom: 18px; }
.mw-td-label { font-size: 11.5px; line-height: 14px; color: #65776F; margin-bottom: 6px; }
.mw-td-val { font-size: 13px; line-height: 16px; font-weight: 700; color: #142D26; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.mw-td-val.plain { font-weight: 400; }
.mw-td-val.late { color: #9A6216; }
.mw-td-avatar { width: 24px; height: 24px; border-radius: 50%; background: #E9F5EF; color: #087F5B; font-size: 10.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
.mw-td-sec { border-top: 1px solid #E2EAE5; padding-top: 16px; margin-top: 4px; margin-bottom: 20px; }
.mw-td-sec h4 { font-family: 'Inter', sans-serif; font-size: 16px; line-height: 19px; font-weight: 700; color: #142D26; margin: 0 0 14px 0; }
.mw-td-sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.mw-td-sec-head h4 { margin: 0; }
.mw-cl-counter-badge { font-family: 'Inter', sans-serif; font-style: normal; font-weight: 700; font-size: 12px; line-height: 15px; text-align: right; color: #087F5B; }
.mw-cl-progress-bar { width: 100%; height: 5px; background: #EDF2EF; border-radius: 99px; overflow: hidden; margin-bottom: 16px; }
.mw-cl-progress-fill { height: 100%; background: #087F5B; border-radius: 99px; transition: width 0.25s ease; }
.mw-cl-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; cursor: pointer; user-select: none; outline: none !important; }
.mw-cl-item:focus, .mw-cl-item:focus-visible, .mw-cl-item:active { outline: none !important; box-shadow: none !important; }
.mw-cl-box { width: 16px; height: 16px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.15s ease; outline: none !important; }
.mw-cl-box:focus, .mw-cl-box:focus-visible, .mw-cl-box:active { outline: none !important; box-shadow: none !important; }
.mw-cl-box.checked { background: #087F5B; border: 1px solid #087F5B; color: #FFFFFF; }
.mw-cl-box.unchecked { background: #FFFFFF; border: 1.5px solid #CCD7D0; }
.mw-cl-item-text { font-family: 'Inter', sans-serif; font-style: normal; font-size: 13.5px; line-height: 16px; font-weight: 400; transition: color 0.15s ease; border: none !important; outline: none !important; box-shadow: none !important; display: inline-block; }
.mw-cl-item-text:focus, .mw-cl-item-text:focus-visible, .mw-cl-item-text:active { outline: none !important; border: none !important; box-shadow: none !important; }
.mw-cl-item-text.checked { color: #65776F; border: none !important; outline: none !important; }
.mw-cl-item-text.unchecked { color: #142D26; border: none !important; outline: none !important; }
.mw-td-text { font-size: 13px; line-height: 16px; color: #65776F; }
.mw-td-text ul, .mw-td-text ol { padding-left: 18px; margin: 0; }
.mw-td-empty { font-size: 12.5px; color: #8A9992; }
.mw-td-file { display: flex; justify-content: space-between; align-items: center; background: #F5F7F6; border-radius: 6px; padding: 12px 14px; font-size: 12.5px; color: #142D26; text-decoration: none; }
.mw-td-file-left { display: flex; align-items: center; gap: 10px; font-family: 'Inter', sans-serif; font-size: 12.5px; line-height: 15px; color: #142D26; }
.mw-td-file-icon { color: #65776F; font-size: 15px; }
.mw-td-file-size { font-family: 'Inter', sans-serif; font-size: 11px; line-height: 13px; text-align: right; color: #65776F; }
.mw-activity-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 14px; }
.mw-act-avatar { width: 24px; height: 24px; border-radius: 50%; background: #E9F5EF; color: #087F5B; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 10.5px; line-height: 13px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mw-act-head { font-family: 'Inter', sans-serif; font-weight: 700; font-size: 11.5px; line-height: 14px; color: #142D26; margin-bottom: 3px; }
.mw-act-time { font-weight: 400; color: #65776F; }
.mw-act-comment { font-family: 'Inter', sans-serif; font-weight: 400; font-size: 12.5px; line-height: 16px; color: #65776F; word-break: break-word; }
.mw-act-mention { color: #087F5B; font-weight: 600; background: #E9F5EF; padding: 1px 5px; border-radius: 4px; display: inline-block; }
.mw-act-attach-chip { display: inline-flex; align-items: center; gap: 8px; background: #F5F7F6; border: 1px solid #E2EAE5; border-radius: 6px; padding: 6px 12px; margin-top: 6px; text-decoration: none; color: #142D26; font-size: 12px; font-weight: 500; transition: background .15s ease; }
.mw-act-attach-chip:hover { background: #EDF2EF; color: #087F5B; }
.mw-act-attach-chip i { color: #65776F; font-size: 13px; }
.mw-act-system { font-family: 'Inter', sans-serif; font-style: normal; font-weight: 400; font-size: 11.5px; line-height: 14px; color: #65776F; margin-bottom: 12px; }
.mw-figma-comment-box { position: relative; background: #FFFFFF; border: 1px solid #E2EAE5; border-radius: 8px; padding: 10px 42px 10px 14px; margin-top: 14px; }
.mw-figma-comment-input { width: 100%; border: none; outline: none; background: transparent; font-family: 'Inter', sans-serif; font-weight: 400; font-size: 12.5px; line-height: 15px; color: #142D26; resize: none; padding: 0; }
.mw-figma-comment-input::placeholder { color: #8A9992; }
.mw-figma-comment-actions { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; }
.mw-figma-icon-btn { border: none; background: transparent; color: #65776F; cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; border-radius: 4px; transition: color .15s ease, background .15s ease; }
.mw-figma-icon-btn:hover { color: #087F5B; background: #F5F7F6; }
.mw-comment-file-preview { display: none; align-items: center; justify-content: space-between; background: #F5F7F6; border: 1px solid #E2EAE5; border-radius: 6px; padding: 6px 10px; margin-top: 8px; font-size: 12px; color: #142D26; }
.mw-comment-file-preview.show { display: flex; }
.mw-comment-file-preview .file-remove { color: #E03131; cursor: pointer; padding: 2px 6px; font-size: 13px; }
/* Mentions dropdown popup */
.mw-mention-popup { position: absolute; bottom: 100%; left: 0; right: 0; margin-bottom: 6px; background: #FFFFFF; border: 1px solid #E2EAE5; border-radius: 8px; box-shadow: 0 10px 28px rgba(20,45,38,.12); max-height: 260px; overflow-y: auto; z-index: 1000; display: none; }
.mw-mention-popup.show { display: block; }
.mw-mention-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; cursor: pointer; transition: background .12s ease; }
.mw-mention-item:hover, .mw-mention-item.selected { background: #E9F5EF; }
.mw-mention-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #E9F5EF; color: #087F5B; font-size: 9.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
.mw-mention-name { font-size: 12.5px; font-weight: 600; color: #142D26; }
.mw-mention-role { font-size: 11px; color: #65776F; margin-left: auto; }
.mw-td-comment { border: 1px solid #E2EAE5; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; color: #8A9992; margin-top: 14px; }
.mw-td-foot { border-top: 1px solid #E2EAE5; padding: 14px 20px; }
.mw-td-foot-actions { display: flex; gap: 10px; align-items: center; justify-content: flex-start; }
.mw-td-btn { background: #FFFFFF; border: 1px solid #E2EAE5; border-radius: 8px; padding: 9px 16px; font-family: 'Inter', sans-serif; font-size: 12.5px; line-height: 15px; font-weight: 700; color: #142D26; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.mw-td-btn.primary { margin-left: 0; background: #EDF2EF; border-color: #EDF2EF; color: #8A9992; }
.mw-td-btn.green { background: #087F5B; border-color: #087F5B; color: #FFFFFF; }
.mw-td-btn.green:hover { background: #076b4d; }
.mw-td-btn:disabled,
.mw-td-btn[disabled] { background: #F5F7F6 !important; border-color: #E2EAE5 !important; color: #A3B3AA !important; cursor: not-allowed !important; opacity: 0.75; box-shadow: none !important; }
.mw-td-note { font-family: 'Inter', sans-serif; font-size: 11.5px; line-height: 15px; color: #65776F; margin-top: 8px; }
/* Reviewer specific styling */
.mw-rev-banner { background: #F4EFFA; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; }
.mw-rev-banner-title { font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700; line-height: 18px; color: #7757AB; margin-bottom: 4px; }
.mw-rev-banner-sub { font-family: 'Inter', sans-serif; font-size: 12px; line-height: 15px; color: #65776F; }
.mw-td-pill.in-review { background: #F4EFFA; color: #7757AB; }
.mw-rev-summary-title { font-family: 'Inter', sans-serif; font-weight: 700; font-size: 17px; line-height: 21px; color: #142D26; margin: 0 0 8px 0; }
.mw-rev-summary-text { font-family: 'Inter', sans-serif; font-weight: 400; font-size: 13px; line-height: 19px; color: #65776F; }
.mw-rev-note-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.mw-rev-note-label { font-family: 'Inter', sans-serif; font-weight: 700; font-size: 12.5px; line-height: 15px; color: #142D26; }
.mw-rev-note-req { font-family: 'Inter', sans-serif; font-weight: 400; font-size: 11px; line-height: 13px; color: #65776F; text-align: right; }
.mw-rev-textarea { width: 100%; background: #FFFFFF; border: 1px solid #E2EAE5; border-radius: 8px; padding: 12px 14px; font-family: 'Inter', sans-serif; font-size: 13px; line-height: 18px; color: #142D26; resize: vertical; min-height: 72px; outline: none; }
.mw-rev-textarea::placeholder { color: #8A9992; }
.mw-rev-textarea:focus { border-color: #087F5B; }
/* Delete Confirmation Modal */
.mw-confirm-backdrop { position: fixed; inset: 0; background: rgba(20,45,38,.45); z-index: 10040; opacity: 0; visibility: hidden; transition: opacity .2s ease, visibility .2s ease; backdrop-filter: blur(2px); }
.mw-confirm-backdrop.show { opacity: 1; visibility: visible; }
.mw-confirm-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(.95); background: #ffffff; border: 1px solid #E2EAE5; border-radius: 12px; box-shadow: 0 20px 48px rgba(20,45,38,.2); z-index: 10045; width: 92%; max-width: 440px; padding: 24px; opacity: 0; visibility: hidden; transition: transform .22s cubic-bezier(.16,1,.3,1), opacity .22s ease, visibility .22s ease; font-family: 'Inter', sans-serif; }
.mw-confirm-modal.show { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
.mw-confirm-icon { width: 44px; height: 44px; border-radius: 50%; background: #FFE3E3; color: #E03131; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 14px; }
.mw-confirm-title { font-size: 17px; font-weight: 700; color: #142D26; margin: 0 0 8px 0; }
.mw-confirm-desc { font-size: 13px; line-height: 19px; color: #65776F; margin: 0 0 20px 0; }
.mw-confirm-actions { display: flex; justify-content: flex-end; gap: 10px; }
.mw-confirm-btn { border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; font-family: 'Inter', sans-serif; }
.mw-confirm-btn.cancel { background: #FFFFFF; border-color: #E2EAE5; color: #142D26; }
.mw-confirm-btn.cancel:hover { background: #F5F7F6; }
.mw-confirm-btn.danger { background: #E03131; color: #FFFFFF; }
.mw-confirm-btn.danger:hover { background: #C92A2A; }
.mw-confirm-btn:disabled { opacity: 0.6; cursor: not-allowed; }
/* Owner Feedback & Satisfaction Rating Box */
.mw-feedback-card { background: #F8FAF9; border: 1px solid #D8E3DD; border-radius: 10px; padding: 16px 18px; margin-top: 18px; margin-bottom: 20px; }
.mw-feedback-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.mw-feedback-title { font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700; color: #142D26; display: flex; align-items: center; gap: 6px; margin: 0; }
.mw-rating-stars-wrap { display: inline-flex; align-items: center; gap: 4px; }
.mw-star-btn { background: none; border: none; font-size: 20px; color: #D1DDD6; cursor: pointer; padding: 2px 3px; line-height: 1; transition: color .15s ease, transform .1s ease; }
.mw-star-btn.active, .mw-star-btn.hovered { color: #F59F00; }
.mw-star-btn:hover { transform: scale(1.15); }
.mw-feedback-textarea { width: 100%; background: #FFFFFF; border: 1px solid #CCD7D0; border-radius: 8px; padding: 10px 12px; font-family: 'Inter', sans-serif; font-size: 12.5px; line-height: 16px; color: #142D26; resize: vertical; min-height: 64px; outline: none; margin-top: 10px; margin-bottom: 10px; }
.mw-feedback-textarea:focus { border-color: #087F5B; box-shadow: 0 0 0 2px rgba(8,127,91,.1); }
.mw-feedback-readonly { background: #FFFFFF; border: 1px solid #E2EAE5; border-radius: 8px; padding: 12px 14px; margin-top: 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.mw-feedback-readonly-stars { display: flex; align-items: center; gap: 3px; color: #F59F00; font-size: 15px; }
.mw-feedback-readonly-badge { background: #FFF9DB; color: #9A6216; border: 1px solid #FFE066; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
/* Toast notification */
.mw-toast-wrap { position: fixed; top: 20px; right: 24px; z-index: 10050; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.mw-toast { pointer-events: auto; min-width: 280px; max-width: 420px; padding: 12px 16px; border-radius: 8px; background: #fff; border: 1px solid #E2EAE5; border-left: 4px solid #087F5B; box-shadow: 0 10px 28px rgba(20,45,38,.14); display: flex; align-items: center; gap: 10px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #142D26; animation: mwToastIn .22s ease; }
.mw-toast.error { border-left-color: #C92A2A; }
.mw-toast .mw-toast-icon { font-size: 16px; color: #087F5B; flex-shrink: 0; }
.mw-toast.error .mw-toast-icon { color: #C92A2A; }
.mw-toast .mw-toast-close { margin-left: auto; background: none; border: 0; color: #65776F; cursor: pointer; padding: 2px 4px; font-size: 13px; }
@keyframes mwToastIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
/* Multi-select dropdown */
.mw-multi { position: relative; }
.mw-multi-btn { text-align: left; width: 100%; cursor: pointer; }
.mw-multi-panel { display: none; position: absolute; z-index: 50; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #E2EAE5; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.08); max-height: 240px; overflow-y: auto; padding: 8px; }
.mw-multi.open .mw-multi-panel { display: block; }
.mw-multi-search { margin-bottom: 6px; position: sticky; top: 0; z-index: 1; background: #fff; }
.mw-multi-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border-radius: 6px; cursor: pointer; font-size: 13px; margin: 0; }
.mw-multi-item:hover { background: #F4FAF7; }
/* Summary Metric Cards */
.mw-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 991px) {
    .mw-stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

.mw-stats-row.mw-stats-all {
    grid-template-columns: repeat(6, 1fr);
}

@media (max-width: 1199px) {
    .mw-stats-row.mw-stats-all {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 575px) {
    .mw-stats-row.mw-stats-all {
        grid-template-columns: repeat(2, 1fr);
    }
}

.mw-stats-row.mw-stats-all .mw-stat-card:not([data-metric]) {
    cursor: default;
}

@media (max-width: 575px) {
    .mw-stats-row {
        grid-template-columns: 1fr;
    }
}

.mw-stat-card {
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 8px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
    user-select: none;
}

.mw-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
    color: inherit;
    border-color: #CCD7D0;
}

.mw-stat-card.active-metric {
    border-color: #087F5B;
    background-color: #F4FAF7;
    box-shadow: 0 0 0 1px #087F5B;
}

.mw-stat-label {
    font-weight: 400;
    font-size: 12.5px;
    line-height: 15px;
    color: #65776F;
    margin-bottom: 8px;
}

.mw-stat-count {
    font-weight: 700;
    font-size: 30px;
    line-height: 36px;
    margin-bottom: 8px;
}

.mw-stat-count.overdue {
    color: #9A6216;
}

.mw-stat-count.today {
    color: #142D26;
}

.mw-stat-count.next7 {
    color: #4364A5;
}

.mw-stat-count.review {
    color: #7757AB;
}

.mw-stat-desc {
    font-weight: 400;
    font-size: 11.5px;
    line-height: 14px;
    color: #65776F;
    margin-bottom: 0;
}

/* Main Grid Layout */
.mw-grid-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1199px) {
    .mw-grid-layout {
        grid-template-columns: 1fr;
    }
}

/* Left: Tasks Worklist Card */
.mw-card {
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 8px;
    overflow: hidden;
}

.mw-tasks-header {
    padding: 18px 20px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mw-tasks-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mw-tasks-title {
    font-weight: 700;
    font-size: 17px;
    line-height: 21px;
    color: #142D26;
    margin: 0;
}

.mw-badge-open {
    background: #E9F5EF;
    color: #087F5B;
    font-weight: 700;
    font-size: 11.5px;
    line-height: 14px;
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
}

.mw-header-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    border-radius: 7px;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.mw-header-tab:hover {
    background: #F4FAF7;
}

.mw-header-tab.active {
    background: #F4FAF7;
    border-color: #C3E6CD;
}

.mw-tasks-completed-text {
    font-weight: 400;
    font-size: 12px;
    line-height: 15px;
    color: #65776F;
    text-align: right;
}

/* Toolbar */
.mw-toolbar {
    padding: 0 20px 16px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.mw-search-box {
    position: relative;
    width: 270px;
    flex: 0 0 270px;
}

.mw-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #65776F;
    font-size: 13px;
    pointer-events: none;
}

.mw-search-input {
    width: 100% !important;
    height: 38px !important;
    background: #F5F7F6 !important;
    border: 1px solid transparent !important;
    border-radius: 6px !important;
    padding: 8px 14px 8px 38px !important;
    font-family: 'Inter', sans-serif !important;
    font-weight: 400 !important;
    font-size: 12px !important;
    line-height: 16px !important;
    color: #142D26 !important;
    outline: none !important;
    box-shadow: none !important;
    transition: all 0.2s ease !important;
}

.mw-search-input::placeholder {
    color: #65776F !important;
}

.mw-search-input:focus {
    background: #FFFFFF !important;
    border-color: #087F5B !important;
    box-shadow: 0 0 0 2px rgba(8, 127, 91, 0.12) !important;
}

/* Custom Pill Buttons - 100% immune to theme's .dropdown button rule */
.mw-pill-btn {
    width: auto !important;
    height: 38px !important;
    background: #FFFFFF !important;
    border: 1px solid #E2EAE5 !important;
    border-radius: 6px !important;
    padding: 8px 16px !important;
    font-family: 'Inter', sans-serif !important;
    font-weight: 700 !important;
    font-size: 12.5px !important;
    line-height: 15px !important;
    color: #142D26 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    cursor: pointer !important;
    white-space: nowrap !important;
    text-decoration: none !important;
    box-shadow: none !important;
    outline: none !important;
    transition: all 0.15s ease !important;
}

.mw-pill-btn:hover {
    background: #F5F7F6 !important;
    border-color: #CCD7D0 !important;
    color: #142D26 !important;
}

.mw-pill-btn i {
    color: #142D26 !important;
    font-size: 12.5px !important;
}

.mw-pill-btn.active {
    border-color: #087F5B !important;
    background-color: #E9F5EF !important;
    color: #087F5B !important;
}

.mw-pill-btn.active i {
    color: #087F5B !important;
}

/* Custom Dropdown Menu for Pill Buttons */
.mw-popup-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    min-width: 190px;
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 8px;
    padding: 6px 0;
    z-index: 1050;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    display: none;
}

.mw-popup-menu.show {
    display: block;
}

.mw-popup-header {
    font-weight: 700;
    font-size: 10px;
    line-height: 12px;
    color: #65776F;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 16px 4px;
}

.mw-popup-item {
    display: block;
    padding: 8px 16px;
    font-size: 12.5px;
    line-height: 16px;
    font-weight: 500;
    color: #142D26;
    text-decoration: none;
    transition: background-color 0.15s ease;
    cursor: pointer;
}

.mw-popup-item:hover {
    background-color: #F5F7F6;
    color: #087F5B;
}

.mw-popup-divider {
    height: 1px;
    background-color: #E2EAE5;
    margin: 6px 0;
}

.mw-dots-icon {
    color: #65776F;
    font-size: 16px;
    letter-spacing: 2px;
    cursor: pointer;
    user-select: none;
    margin-left: 4px;
    line-height: 1;
}

/* Tasks Table */
.mw-table-wrap {
    width: 100%;
    overflow-x: auto;
    position: relative;
}

.mw-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.mw-table thead th {
    background: #F5F7F6;
    border-top: 1px solid #E2EAE5;
    border-bottom: 1px solid #E2EAE5;
    font-weight: 700;
    font-size: 10px;
    line-height: 12px;
    color: #65776F;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 10px 16px;
    text-align: left;
}

.mw-table tbody {
    transition: opacity 0.2s ease;
}

.mw-table tbody tr {
    border-bottom: 1px solid #E2EAE5;
    transition: background-color 0.15s ease;
}

.mw-table tbody tr:hover {
    background-color: #FAFCFA;
}

.mw-table tbody tr.row-selected {
    background-color: #E9F5EF;
    border-left: 3px solid #087F5B;
}

.mw-table td {
    padding: 14px 16px;
    vertical-align: middle;
}

.mw-task-check {
    width: 16px;
    height: 16px;
    accent-color: #087F5B;
    cursor: pointer;
    border: 1px solid #E2EAE5;
    border-radius: 3px;
    margin-right: 12px;
}

.mw-task-name {
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    color: #142D26;
    margin-bottom: 3px;
    text-decoration: none;
    display: inline-block;
}

.mw-task-name:hover {
    color: #087F5B;
}

.mw-task-sub {
    font-weight: 400;
    font-size: 10.5px;
    line-height: 13px;
    color: #65776F;
}

.mw-badge-blocked {
    background: #FFF5E2;
    color: #9A6216;
    font-weight: 700;
    font-size: 9px;
    line-height: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
    margin-left: 8px;
    vertical-align: middle;
}

.mw-priority-text {
    font-weight: 400;
    font-size: 11.5px;
    line-height: 14px;
    color: #65776F;
    text-transform: capitalize;
}

.mw-priority-text.urgent {
    color: #9A6216;
    font-weight: 600;
}

/* Status Pills */
.mw-status-pill {
    display: inline-block;
    font-weight: 700;
    font-size: 10.5px;
    line-height: 13px;
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: capitalize;
}

.mw-status-pill.in-review {
    background: #F4EFFA;
    color: #7757AB;
}

.mw-status-pill.in-progress {
    background: #EEF3FC;
    color: #4364A5;
}

.mw-status-pill.todo {
    background: #EDF2EF;
    color: #65776F;
}

.mw-status-pill.completed {
    background: #E9F5EF;
    color: #087F5B;
}

.mw-status-pill.expired {
    background: #FCE8E6;
    color: #C5221F;
}

/* Due Date */
.mw-due-date {
    font-weight: 700;
    font-size: 12px;
    line-height: 15px;
    color: #142D26;
}

.mw-due-date.overdue {
    color: #9A6216;
}

.mw-due-sub {
    font-weight: 400;
    font-size: 10px;
    line-height: 12px;
    color: #65776F;
    margin-top: 2px;
}

.mw-due-sub.overdue {
    color: #9A6216;
}

.mw-assignee {
    font-weight: 400;
    font-size: 11px;
    line-height: 13px;
    color: #142D26;
}

/* Row Actions */
.mw-actions-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
}

.mw-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 1px solid #E2EAE5;
    background: #FFFFFF;
    color: #65776F;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    padding: 0;
}

.mw-action-btn:hover {
    background: #F5F7F6;
    color: #142D26;
    border-color: #CBD5E1;
}

.mw-action-btn.view:hover {
    color: #087F5B;
    background: #E9F5EF;
    border-color: #BBE3D0;
}

.mw-action-btn.edit:hover {
    color: #2563EB;
    background: #EFF6FF;
    border-color: #BFDBFE;
}

.mw-action-btn.delete:hover {
    color: #DC2626;
    background: #FEF2F2;
    border-color: #FECACA;
}

/* Table Footer */
.mw-tasks-footer {
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mw-footer-info {
    font-weight: 400;
    font-size: 11px;
    line-height: 13px;
    color: #65776F;
}

.mw-btn-viewall {
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    color: #142D26;
    padding: 6px 14px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}

.mw-btn-viewall:hover {
    background: #F5F7F6;
    color: #142D26;
}

/* Right Focus Panel */
.mw-focus-card {
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 8px;
    padding: 18px 20px;
    margin-bottom: 20px;
}

.mw-focus-title {
    font-weight: 700;
    font-size: 17px;
    line-height: 21px;
    color: #142D26;
    margin-bottom: 4px;
}

.mw-focus-subtitle {
    font-weight: 400;
    font-size: 12px;
    line-height: 15px;
    color: #65776F;
    margin-bottom: 16px;
}

.mw-review-item {
    margin-bottom: 16px;
}

.mw-review-item:last-child {
    margin-bottom: 0;
}

.mw-review-item-title {
    font-weight: 700;
    font-size: 13px;
    line-height: 16px;
    color: #142D26;
    margin-bottom: 3px;
}

.mw-review-item-meta {
    font-weight: 400;
    font-size: 11.5px;
    line-height: 14px;
    color: #65776F;
}

.mw-btn-review {
    width: 100%;
    background: #087F5B;
    color: #FFFFFF !important;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    margin-top: 10px;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: block;
    transition: background-color 0.2s ease;
}

.mw-btn-review:hover {
    background: #066649;
    color: #FFFFFF !important;
}

.mw-review-divider {
    border-top: 1px solid #E2EAE5;
    margin: 14px 0;
}

.mw-review-link-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.mw-review-link-row:hover .mw-review-item-title {
    color: #087F5B;
}

.mw-arrow-icon {
    color: #087F5B;
    font-size: 13px;
}

/* Blocked Panel */
.mw-badge-waiting {
    background: #FFF5E2;
    color: #9A6216;
    font-weight: 700;
    font-size: 11.5px;
    line-height: 14px;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 12px;
}

.mw-blocked-desc {
    font-weight: 400;
    font-size: 13px;
    line-height: 18px;
    color: #65776F;
    margin-bottom: 14px;
}

.mw-blocked-action {
    font-weight: 700;
    font-size: 12px;
    line-height: 15px;
    color: #142D26;
    margin-bottom: 14px;
}

.mw-btn-opentask {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2EAE5;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    color: #142D26;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.15s ease;
}

.mw-btn-opentask:hover {
    background: #F5F7F6;
    color: #142D26;
}

.mw-empty-state {
    padding: 40px 20px;
    text-align: center;
    color: #65776F;
}

.mw-empty-state i {
    font-size: 32px;
    color: #CCD7D0;
    margin-bottom: 12px;
}

.mw-bottom-footnote {
    font-weight: 400;
    font-size: 11px;
    line-height: 14px;
    color: #65776F;
    margin-top: 24px;
}

/* =========================================================================
   Slide Drawer (Figma Right Slide Modal)
   Vector: left: 54.38%; right: 0%; top: 0%; bottom: 0%; background: #FFFFFF;
   ========================================================================= */
.mw-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(20, 45, 38, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
}

.mw-drawer-backdrop.show {
    opacity: 1;
    visibility: visible;
}

.mw-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 54.38%;
    background: #FFFFFF;
    z-index: 9999;
    box-shadow: -12px 0 40px rgba(0, 0, 0, 0.16);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 1px solid #E2EAE5;
}

.mw-drawer.show {
    transform: translateX(0);
}

@media (max-width: 1100px) {
    .mw-drawer {
        left: 35%;
    }
}

@media (max-width: 768px) {
    .mw-drawer {
        left: 0;
        width: 100%;
    }
}

.mw-drawer-header {
    padding: 22px 28px 16px;
    border-bottom: 1px solid #E2EAE5;
    position: relative;
    flex-shrink: 0;
}

.mw-drawer-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.mw-drawer-title {
    font-weight: 700;
    font-size: 20px;
    line-height: 24px;
    color: #142D26;
    margin: 0;
}

.mw-drawer-close {
    background: transparent;
    border: none;
    font-size: 18px;
    color: #65776F;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    line-height: 1;
    transition: all 0.15s ease;
}

.mw-drawer-close:hover {
    color: #142D26;
    background: #F5F7F6;
}

.mw-drawer-subtitle {
    font-weight: 400;
    font-size: 12px;
    line-height: 15px;
    color: #65776F;
    margin: 0;
}

.mw-drawer-meta-bar {
    padding: 10px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #F0F4F2;
    background: #FAFCFA;
    flex-shrink: 0;
}

.mw-status-badge-todo {
    background: #F5F7F6;
    border: 1px solid #E2EAE5;
    border-radius: 4px;
    font-weight: 600;
    font-size: 11px;
    line-height: 13px;
    color: #142D26;
    padding: 4px 10px;
    display: inline-block;
}

.mw-required-note {
    font-weight: 400;
    font-size: 11px;
    line-height: 13px;
    color: #65776F;
}

.mw-drawer-body {
    padding: 24px 28px;
    overflow-y: auto;
    flex-grow: 1;
}

.mw-form-group {
    margin-bottom: 18px;
}

.mw-form-label {
    display: block;
    font-weight: 600;
    font-size: 12px;
    line-height: 15px;
    color: #142D26;
    margin-bottom: 6px;
}

.mw-form-label span.req {
    color: #DC2626;
}

.mw-form-input,
.mw-form-select,
.mw-form-textarea {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #D1DDD6;
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 13px;
    line-height: 18px;
    color: #142D26;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}

.mw-form-input:focus,
.mw-form-select:focus,
.mw-form-textarea:focus {
    border-color: #087F5B;
    box-shadow: 0 0 0 3px rgba(8, 127, 91, 0.12);
}

.mw-form-row {
    display: flex;
    gap: 16px;
}

.mw-form-col {
    flex: 1;
    min-width: 0;
}

.mw-checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 14px 0 16px;
    cursor: pointer;
    user-select: none;
}

.mw-checkbox-wrap input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #087F5B;
    cursor: pointer;
}

.mw-checkbox-wrap span {
    font-weight: 500;
    font-size: 12.5px;
    color: #142D26;
}

/* Callout Notice Box */
.mw-callout-box {
    background: #EBF7EE;
    border: 1px solid #C3E6CD;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-top: 18px;
    margin-bottom: 12px;
}

.mw-callout-icon {
    color: #087F5B;
    font-size: 16px;
    margin-top: 1px;
}

.mw-callout-title {
    font-weight: 600;
    font-size: 12.5px;
    line-height: 16px;
    color: #087F5B;
    margin-bottom: 2px;
}

.mw-callout-sub {
    font-weight: 400;
    font-size: 11.5px;
    line-height: 15px;
    color: #4A6B5D;
}

/* Accordion */
.mw-accordion-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: #FAFCFA;
    border: 1px solid #E2EAE5;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 14px;
    transition: background 0.15s ease;
}

.mw-accordion-toggle:hover {
    background: #F5F7F6;
}

.mw-accordion-toggle strong {
    font-size: 12.5px;
    color: #142D26;
}

/* Drawer Footer */
.mw-drawer-footer {
    padding: 16px 28px 18px;
    border-top: 1px solid #E2EAE5;
    background: #FFFFFF;
    flex-shrink: 0;
}

.mw-drawer-footer-actions {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 12px;
}

.mw-drawer-btn {
    padding: 9px 18px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    line-height: 16px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mw-drawer-btn.secondary {
    background: #FFFFFF;
    border: 1px solid #D1DDD6;
    color: #142D26;
}

.mw-drawer-btn.secondary:hover {
    background: #F5F7F6;
    border-color: #B5C6BC;
}

.mw-drawer-btn.primary {
    background: #087F5B;
    border: 1px solid #087F5B;
    color: #FFFFFF;
}

.mw-drawer-btn.primary:hover {
    background: #066649;
    border-color: #066649;
}

.mw-drawer-foot-note {
    font-weight: 400;
    font-size: 11px;
    line-height: 14px;
    color: #65776F;
    margin-top: 10px;
}
</style>

<div class="my-work-workspace">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="mw-header-tag">PERSONAL WORKSPACE</div>
            <h1 class="mw-header-title">My Work</h1>
            <p class="mw-header-subtitle">Your next actions, deadlines and review requests in one place.</p>
        </div>
        <div>
            <button type="button" class="mw-btn-create" id="btnOpenCreateTask">
                <i class="fa-solid fa-plus"></i>
                <span>Create task</span>
            </button>
        </div>
    </div>

    <!-- Scope Tabs (Single-page Ajax switching) -->
    <?php
    $tc = $tabCounts ?? [];
    $renderTabBadge = function (string $key) use ($tc): string {
        $c = (int)($tc[$key] ?? 0);
        return '<span class="mw-tab-badge" data-tab-count="' . $key . '"' . ($c > 0 ? '' : ' style="display:none;"') . '>' . ($c > 0 ? $c : '') . '</span>';
    };
    ?>
    <div class="mw-tabs-wrap">
        <a class="mw-tab-item <?= $tab === 'all' ? 'active' : '' ?>" 
           data-tab="all" href="javascript:void(0);">All <?= $renderTabBadge('all') ?></a>
        <a class="mw-tab-item <?= $tab === 'assigned_to_me' ? 'active' : '' ?>" 
           data-tab="assigned_to_me" href="javascript:void(0);">Assigned to me <?= $renderTabBadge('assigned_to_me') ?></a>
        <a class="mw-tab-item <?= $tab === 'assigned_by_me' ? 'active' : '' ?>" 
           data-tab="assigned_by_me" href="javascript:void(0);">Assigned by me <?= $renderTabBadge('assigned_by_me') ?></a>
        <a class="mw-tab-item <?= $tab === 'following' ? 'active' : '' ?>" 
           data-tab="following" href="javascript:void(0);">Following <?= $renderTabBadge('following') ?></a>
        <a class="mw-tab-item <?= $tab === 'reviewer' ? 'active' : '' ?>" 
           data-tab="reviewer" href="javascript:void(0);">Reviewer <?= $renderTabBadge('reviewer') ?></a>
        <a class="mw-tab-item <?= $tab === 'realestate_project' ? 'active' : '' ?>" 
           data-tab="realestate_project" href="javascript:void(0);">Realestate Project <?= $renderTabBadge('realestate_project') ?></a>
        <a class="mw-tab-item <?= $tab === 'self_initiated' ? 'active' : '' ?>" 
           data-tab="self_initiated" href="javascript:void(0);">Self-Initiated Task <?= $renderTabBadge('self_initiated') ?></a>
        <a class="mw-tab-item <?= $tab === 'recurring' ? 'active' : '' ?>" 
           data-tab="recurring" href="javascript:void(0);">Recurring Task <?= $renderTabBadge('recurring') ?></a>
    </div>

    <!-- Summary cards (all tabs, scoped to the active tab) -->
    <?php $as = $allStats ?? []; ?>
    <div class="mw-stats-row mw-stats-all" id="mwStatsAll">
        <div class="mw-stat-card <?= $metricFilter === 'all_total' ? 'active-metric' : '' ?>" data-metric="all_total"><span class="mw-stat-label">TOTAL TASKS</span><span class="mw-stat-count" id="allTotal"><?= (int)($as['total'] ?? 0) ?></span></div>
        <div class="mw-stat-card <?= $metricFilter === 'all_in_progress' ? 'active-metric' : '' ?>" data-metric="all_in_progress"><span class="mw-stat-label">IN PROGRESS</span><span class="mw-stat-count next7" id="allInProgress"><?= (int)($as['in_progress'] ?? 0) ?></span></div>
        <div class="mw-stat-card <?= $metricFilter === 'all_completed' ? 'active-metric' : '' ?>" data-metric="all_completed"><span class="mw-stat-label">COMPLETED</span><span class="mw-stat-count review" id="allCompleted"><?= (int)($as['completed'] ?? 0) ?></span></div>
        <div class="mw-stat-card <?= $metricFilter === 'all_deadline_today' ? 'active-metric' : '' ?>" data-metric="all_deadline_today"><span class="mw-stat-label">DEADLINE TODAY</span><span class="mw-stat-count today" id="allDeadlineToday"><?= (int)($as['deadline_today'] ?? 0) ?></span></div>
        <div class="mw-stat-card <?= $metricFilter === 'all_deadline_missed' ? 'active-metric' : '' ?>" data-metric="all_deadline_missed"><span class="mw-stat-label">DEADLINE MISSED</span><span class="mw-stat-count overdue" id="allDeadlineMissed"><?= (int)($as['deadline_missed'] ?? 0) ?></span></div>
        <div class="mw-stat-card"><span class="mw-stat-label">COMPLETION RATE</span><span class="mw-stat-count" id="allCompletionRate"><?= (int)($as['completion_rate'] ?? 0) ?>%</span></div>
    </div>



    <!-- Main Grid Layout -->
    <div class="mw-grid-layout">
        <!-- Left: Task Worklist -->
        <div class="mw-card">
            <!-- Header -->
            <div class="mw-tasks-header">
                <div class="mw-tasks-title-group" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
                    <button type="button" class="mw-header-tab <?= $statusFilter === 'open' ? 'active' : '' ?>" id="tabMyActionable" data-header-status="open">
                        <h3 class="mw-tasks-title">My Actionable</h3>
                        <span class="mw-badge-open" id="badgeOpenCount"><?= $openTasksCount ?> open</span>
                    </button>
                    <button type="button" class="mw-header-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>" id="tabMyCompleted" data-header-status="completed">
                        <h3 class="mw-tasks-title" style="color:#2D453E;">Completed</h3>
                        <span class="mw-badge-open" id="badgeCompletedCount" style="background:#EDF2FF; color:#364FC7; font-weight:700;">
                            <?= $completedTasksCount ?> completed
                        </span>
                    </button>
                </div>
                <div class="mw-tasks-completed-text" id="textCompletedCount">
                    <?= $openTasksCount + $completedTasksCount ?> total
                </div>
            </div>

            <!-- Toolbar -->
            <div class="mw-toolbar">
                <div class="mw-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="mwSearchInput" class="mw-search-input" 
                           placeholder="Search title or task ID" 
                           value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                </div>

                <!-- Deadline Pill Button -->
                <div class="position-relative d-inline-block">
                    <button type="button" class="mw-pill-btn" id="btnDueDate">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Deadline</span>
                    </button>
                    <div class="mw-popup-menu" id="menuDueDate">
                        <div class="mw-popup-header">FILTER BY DATE</div>
                        <a class="mw-popup-item" href="javascript:void(0);" data-metric="">All Dates</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-metric="due_today">Due Today</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-metric="next7">Next 7 Days</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-metric="overdue">Overdue</a>
                    </div>
                </div>

                <!-- Filters Pill Button -->
                <div class="position-relative d-inline-block">
                    <button type="button" class="mw-pill-btn" id="btnFilters">
                        <i class="fa-solid fa-sliders"></i>
                        <span id="filterBtnText">Filters</span>
                    </button>
                    <div class="mw-popup-menu" id="menuFilters">
                        <div class="mw-popup-header">PRIORITY</div>
                        <a class="mw-popup-item" href="javascript:void(0);" data-priority="">All Priorities</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-priority="urgent">Urgent</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-priority="high">High</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-priority="medium">Medium</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-priority="low">Low</a>

                        <div class="mw-popup-divider"></div>

                        <div class="mw-popup-header">STATUS</div>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="all">All Statuses</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="open">Open only</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="pending">To do</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="in_progress">In progress</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="completed">Completed</a>
                    </div>
                </div>

                <!-- List view Pill Button (Static view indicator, no action) -->
                <span class="mw-pill-btn" style="cursor: default;" title="List view">
                    <i class="fa-solid fa-bars-staggered"></i>
                    <span>List view</span>
                </span>

                <!-- Dots icon -->
                <span class="mw-dots-icon" title="More options">···</span>
            </div>

            <!-- Table -->
            <div class="mw-table-wrap">
                <table class="mw-table">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">TASK / PROJECT</th>
                            <th style="width: 110px;">PRIORITY</th>
                            <th style="width: 120px;">STATUS</th>
                            <th style="width: 150px;">DEADLINE</th>
                            <th style="width: 150px;">NEXT ACTION BY</th>
                            <th style="width: 110px; text-align: right;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="mwTasksTbody">
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="mw-empty-state">
                                        <i class="fa-regular fa-clipboard d-block"></i>
                                        <div>No tasks found for this view.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $index => $t): ?>
                                <?php
                                $taskId = (int) $t['id'];
                                $isCompleted = ($t['status'] === 'completed');
                                $isBlocked = (!empty($t['remarks']) && stripos($t['remarks'], 'block') !== false) || stripos($t['title'], 'block') !== false;
                                
                                $isOverdue = false;
                                $duePrimary = 'No deadline';
                                $dueSub = '';
                                
                                if (!empty($t['deadline'])) {
                                    $deadlineTime = strtotime($t['deadline']);
                                    $now = time();
                                    
                                    if (!$isCompleted && $deadlineTime < $now) {
                                        $isOverdue = true;
                                        $diffDays = (int) ceil(($now - $deadlineTime) / 86400);
                                        $duePrimary = date('j M', $deadlineTime);
                                        $dueSub = ($diffDays <= 1) ? '1 day overdue' : "{$diffDays} days overdue";
                                    } elseif (date('Y-m-d', $deadlineTime) === date('Y-m-d')) {
                                        $duePrimary = date('j M', $deadlineTime);
                                        $dueSub = 'Today · ' . date('g:i A', $deadlineTime);
                                    } else {
                                        $duePrimary = date('j M', $deadlineTime);
                                        $dueSub = date('D · g:i A', $deadlineTime);
                                    }
                                }

                                $statusClass = 'todo';
                                $statusLabel = 'To do';
                                if ($t['status'] === 'in_progress') {
                                    $statusClass = 'in-progress';
                                    $statusLabel = 'In progress';
                                } elseif ($t['status'] === 'completed') {
                                    $statusClass = 'completed';
                                    $statusLabel = 'Completed';
                                } elseif ($t['status'] === 'expired') {
                                    $statusClass = 'expired';
                                    $statusLabel = 'Expired';
                                }
                                $rowRevCount = preg_match_all('/\d+/', (string)($t['mentioned_user_ids'] ?? ''), $mRev);
                                if (!$isCompleted && !empty($t['is_submitted_for_review']) && $rowRevCount > 0) {
                                    $statusClass = 'in-review';
                                    $statusLabel = 'In review';
                                }

                                if ($tab === 'assigned_by_me') {
                                    $nextActionName = !empty($t['assignee_name']) ? htmlspecialchars($t['assignee_name']) : 'Assignee';
                                    $nextActionRole = !empty($t['assignee_designation']) ? ' · ' . htmlspecialchars($t['assignee_designation']) : '';
                                } else {
                                    if ((int)$t['assigned_by'] === (int)$userId) {
                                        $nextActionName = 'You';
                                        $nextActionRole = '';
                                    } else {
                                        $nextActionName = !empty($t['assigned_by_name']) ? htmlspecialchars($t['assigned_by_name']) : 'You';
                                        $nextActionRole = !empty($t['assigned_by_designation']) ? ' · ' . htmlspecialchars($t['assigned_by_designation']) : '';
                                    }
                                }

                                $isHighlighted = ($index === 1 && $isBlocked) || ($index === 0 && $isOverdue);
                                ?>
                                <tr class="<?= $isHighlighted ? 'row-selected' : '' ?>" data-task-id="<?= $taskId ?>">
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <div>
                                                <a href="javascript:void(0);" onclick="openTaskDetail(<?= $taskId ?>); return false;" class="mw-task-name">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </a>
                                                <?php if ($isBlocked): ?>
                                                    <span class="mw-badge-blocked">Blocked</span>
                                                <?php endif; ?>
                                                <?php if (!empty($t['remarks']) && stripos($t['remarks'], 'Reviewer Note') !== false): ?>
                                                    <span class="mw-badge-waiting" style="background:#FFF4E5; color:#B76E00; border:1px solid #FFE0B2; padding:2px 7px; border-radius:10px; font-size:11px; font-weight:600; margin-left:6px;"><i class="fa-solid fa-reply me-1"></i>Review Feedback</span>
                                                <?php endif; ?>
                                                <div class="mw-task-sub">
                                                    T-<?= $taskId ?> · <?= htmlspecialchars($t['project_name'] ?? 'General') ?> · <?= date('F', strtotime($t['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="mw-priority-text <?= $t['priority'] === 'urgent' ? 'urgent' : '' ?>">
                                            <?= htmlspecialchars(ucfirst($t['priority'] ?? 'Normal')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="mw-status-pill <?= $statusClass ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="mw-due-date <?= $isOverdue ? 'overdue' : '' ?>">
                                            <?= $duePrimary ?>
                                        </div>
                                        <?php if (!empty($dueSub)): ?>
                                            <div class="mw-due-sub <?= $isOverdue ? 'overdue' : '' ?>">
                                                <?= $dueSub ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="mw-assignee">
                                            <?= $nextActionName ?><?= $nextActionRole ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="mw-actions-group">
                                            <?php if ((int)$t['assigned_by'] === (int)$userId): ?>
                                                <button type="button" class="mw-action-btn edit" onclick="openEditTaskDrawer(<?= $taskId ?>, event)" title="Edit task">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="mw-action-btn delete" onclick="deleteMyWorkTask(<?= $taskId ?>, this)" title="Delete task">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="mw-tasks-footer">
                <div class="mw-footer-info" id="mwFooterInfo">
                    Showing <?= count($tasks) ?> of <?= $openTasksCount ?> open tasks · Sorted by deadline
                </div>
                <a href="javascript:void(0);" class="mw-btn-viewall" id="btnMyWorkViewAll">
                    View all
                </a>
            </div>
        </div>

        <!-- Right: Focus Panel -->
        <div>
            <!-- Card 1: Needs your review -->
            <div class="mw-focus-card" id="mwCardNeedsReview">
                <h4 class="mw-focus-title">Needs your review</h4>
                <div class="mw-focus-subtitle">
                    <?= count($reviewTasks) ?> completed task<?= count($reviewTasks) === 1 ? '' : 's' ?> waiting for your review
                </div>

                <?php if (!empty($reviewTasks)): ?>
                    <?php foreach ($reviewTasks as $idx => $rt): ?>
                        <div class="mw-review-item">
                            <div class="mw-review-item-title"><?= htmlspecialchars($rt['title']) ?></div>
                            <div class="mw-review-item-meta">
                                <?= htmlspecialchars($rt['employee_name'] ?? 'Team Member') ?> · Completed <?= date('j M, g:i A', strtotime($rt['updated_at'] ?? $rt['created_at'])) ?>
                            </div>
                            <?php if ($idx === 0): ?>
                                <button type="button" class="mw-btn-review" onclick="openTaskDetail(<?= (int)$rt['id'] ?>)">
                                    Review submission
                                </button>
                            <?php else: ?>
                                <div class="mw-review-divider"></div>
                                <a href="javascript:void(0);" onclick="openTaskDetail(<?= (int)$rt['id'] ?>)" class="mw-review-link-row">
                                    <span class="mw-review-item-title" style="font-size: 12.5px;">View details</span>
                                    <i class="fa-solid fa-arrow-right mw-arrow-icon"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted small py-2">
                        <i class="fa-regular fa-circle-check text-success me-1"></i> No completed tasks pending your review.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card 2: Task Attention (Priority or response needed) -->
            <div class="mw-focus-card" id="mwCardTaskAttention">
                <h4 class="mw-focus-title">Task Attention</h4>
                <div class="mw-focus-subtitle">Priority or response needed</div>

                <?php if ($blockedTask): ?>
                    <?php 
                        $isRevSub = !empty($blockedTask['is_review_submission']);
                        $badgeText = $isRevSub ? 'Review Feedback' : (!empty($blockedTask['remarks']) ? 'Action Needed' : 'High Priority');
                        $nextActionPerson = !empty($blockedTask['reviewer_name']) 
                            ? $blockedTask['reviewer_name'] 
                            : ($blockedTask['assigned_by_name'] ?? 'Reviewer');
                        $nextActionDate = !empty($blockedTask['last_review_time']) 
                            ? date('j M', strtotime($blockedTask['last_review_time']))
                            : (!empty($blockedTask['deadline']) ? date('j M', strtotime($blockedTask['deadline'])) : 'Soon');
                        
                        // Extract reviewer note if available
                        $displayNote = '';
                        if (!empty($blockedTask['last_review_activity']) && preg_match('/note:\s*["\']?(.*?)["\']?$/is', $blockedTask['last_review_activity'], $mNote)) {
                            $displayNote = trim($mNote[1], " \"'\t\n\r");
                        } elseif (!empty($blockedTask['remarks'])) {
                            if (preg_match('/Reviewer Note \([^)]+\):\s*(.*?)(?=\n|$)/is', $blockedTask['remarks'], $mNote)) {
                                $displayNote = trim($mNote[1]);
                            } else {
                                $displayNote = $blockedTask['remarks'];
                            }
                        }
                    ?>
                    <span class="mw-badge-waiting">
                        <?= htmlspecialchars($badgeText) ?>
                    </span>

                    <p class="mw-blocked-desc" style="cursor: pointer;" onclick="openTaskDetail(<?= (int)$blockedTask['id'] ?>); return false;" title="Click to view task details">
                        <?= htmlspecialchars($blockedTask['title']) ?>
                        <?php if (!empty($displayNote)): ?>
                            <br><small class="text-muted"><i class="fa-regular fa-comment-dots me-1"></i><?= htmlspecialchars(mb_strimwidth($displayNote, 0, 80, '...')) ?></small>
                        <?php endif; ?>
                    </p>

                    <div class="mw-blocked-action">
                        Next action: <?= htmlspecialchars($nextActionPerson) ?> · <?= htmlspecialchars($nextActionDate) ?>
                    </div>

                    <a href="javascript:void(0);" onclick="openTaskDetail(<?= (int)$blockedTask['id'] ?>); return false;" class="mw-btn-opentask" id="btnAttentionOpenTask" title="Open task details">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>Open task</span>
                    </a>
                <?php else: ?>
                    <div class="text-muted small py-2">
                        <i class="fa-regular fa-circle-check text-success me-1"></i> All active tasks are progressing on track!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footnote -->
    <div class="mw-bottom-footnote">
        Open <?= $openTasksCount ?> = Active items. Overdue is a deadline flag within open tasks.
    </div>
</div>

<!-- Slide Modal / Drawer Backdrop (Figma Vector: left: 54.38%; right: 0%; top: 0%; bottom: 0%;) -->
<div class="mw-drawer-backdrop" id="mwDrawerBackdrop"></div>

<!-- Slide Modal / Drawer -->
<div class="mw-drawer" id="mwCreateTaskDrawer">
    <!-- Header -->
    <div class="mw-drawer-header">
        <div class="mw-drawer-title-row">
            <h3 class="mw-drawer-title" id="mwDrawerTitle">Create task</h3>
            <button type="button" class="mw-drawer-close" id="btnCloseCreateTask" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p class="mw-drawer-subtitle" id="mwDrawerSubtitle">Define the output, owner and deadline.</p>
    </div>

    <!-- Status & Required Meta Bar -->
    <div class="mw-drawer-meta-bar">
        <span class="mw-status-badge-todo" id="mwDrawerStatusBadge">Status: To do</span>
        <span class="mw-required-note">Required fields marked <span class="text-danger">*</span></span>
    </div>

    <!-- Form & Body -->
    <form id="mwCreateTaskForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="margin: 0;">
        <input type="hidden" name="edit_task_id" id="drawerEditTaskId" value="">
        <div class="mw-drawer-body">
            <!-- Task Title -->
            <div class="mw-form-group">
                <label class="mw-form-label">Task title <span class="req">*</span></label>
                <input type="text" name="title" id="drawerTaskTitle" class="mw-form-input" 
                       placeholder="Client ledger: verify opening balances" required>
            </div>

            <?php
            // Searchable dropdown (checkbox list) styled like .mw-form-select. $single = pick one (click again to clear).
            $mwMulti = function (string $name, string $placeholder, bool $single = false, ?int $selectedId = null, string $id = '') use ($usersList) { ?>
                <div class="mw-multi" <?= $id !== '' ? 'id="' . htmlspecialchars($id) . '"' : '' ?> data-single="<?= $single ? '1' : '0' ?>" data-clearable="<?= ($single && $selectedId === null) ? '1' : '0' ?>" data-placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <button type="button" class="mw-form-select mw-multi-btn"><span class="mw-multi-text"><?= htmlspecialchars($placeholder) ?></span></button>
                    <div class="mw-multi-panel">
                        <input type="text" class="mw-form-input mw-multi-search" placeholder="Search...">
                        <?php foreach (($usersList ?? []) as $u): if (trim((string)$u['full_name']) === '') continue; 
                            $rawCode = !empty($u['employee_code']) ? trim((string)$u['employee_code']) : '';
                            $empCode = preg_replace('/^EMP-?/i', '', $rawCode);
                            $displayName = trim((string)$u['full_name']) . ($empCode !== '' ? " ({$empCode})" : '');
                        ?>
                            <label class="mw-multi-item">
                                <input type="<?= $single ? 'radio' : 'checkbox' ?>" name="<?= htmlspecialchars($name) ?>" value="<?= (int)$u['id'] ?>" data-name="<?= htmlspecialchars($displayName) ?>" <?= ($selectedId !== null && (int)$u['id'] === $selectedId) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($displayName) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php }; ?>
            <!-- Row 1: Owner & Due Date -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Owner</label>
                    <?php
                    $ownerName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['name'] ?? '');
                    $ownerEmpCode = !empty($_SESSION['user']['employee_id']) ? $_SESSION['user']['employee_id'] : '';
                    foreach (($usersList ?? []) as $u) {
                        if ((int)$u['id'] === (int)$userId) {
                            $ownerName = $u['full_name'];
                            if (!empty($u['employee_code'])) {
                                $ownerEmpCode = $u['employee_code'];
                            }
                            break;
                        }
                    }
                    $ownerEmpCode = preg_replace('/^EMP-?/i', '', trim((string)$ownerEmpCode));
                    $ownerDisplay = trim($ownerName) . ($ownerEmpCode !== '' ? " ({$ownerEmpCode})" : '');
                    ?>
                    <div id="taskOwnerSelect">
                        <input type="text" class="mw-form-input" value="<?= htmlspecialchars($ownerDisplay) ?>" readonly style="background:#F5F7F6;cursor:not-allowed;">
                                            </div>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Deadline <span class="req">*</span></label>
                    <input type="date" name="deadline" id="drawerDeadline" class="mw-form-input" required>
                </div>
            </div>

            <!-- Row 2: Priority -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Priority</label>
                    <select name="priority" class="mw-form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Real estate project -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Real estate project</label>
                    <select name="project_id" class="mw-form-select">
                        <option value="">Not linked</option>
                        <?php foreach (($realEstateProjectsList ?? []) as $rp): ?>
                            <option value="<?= (int)$rp['id'] ?>"><?= htmlspecialchars($rp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">&nbsp;</label>
                    <label class="mw-checkbox-wrap" style="margin-top:8px;">
                        <input type="checkbox" name="is_recurring" value="1" id="chkIsRecurring">
                        <span>Is Recurring</span>
                    </label>
                </div>
            </div>

            <div class="mw-form-group" id="rowRecurrenceEnd" style="display:none;">
                <label class="mw-form-label">Repeat</label>
                <select name="recurrence_type" id="taskRecurrenceType" class="mw-form-select">
                    <option value="">Select...</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>


            <!-- Expected output (textarea) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Expected output</label>
                <textarea name="expected_output" id="drawerExpectedOutput" class="mw-form-textarea" rows="2" 
                          placeholder="Describe the expected deliverable, results, or outcome..."></textarea>
            </div>

            <!-- Acceptance Checklist (multiple allowed, optional) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Acceptance checklist</label>
                <div id="expectedOutputList">
                    <div class="d-flex align-items-start gap-2 mb-2 mw-expected-row">
                        <textarea name="description[]" id="drawerDescription" class="mw-form-textarea" rows="1" 
                                  placeholder="e.g. Verify report totals, send summary to team..."></textarea>
                        <button type="button" class="mw-btn-create" id="btnAddExpectedOutput" title="Add another checklist item" style="white-space:nowrap;">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Assigned to & CC -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Assigned to</label>
                    <?php $mwMulti('assigned_to_id', 'Select Assigned to...', true, (int)$userId, 'taskAssignedToSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">CC</label>
                    <?php $mwMulti('cc_ids[]', 'Select CC...'); ?>
                </div>
            </div>

            <!-- Row 4: Reviewer & Collaborators -->
            <div id="sectionReviewFields" class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Reviewer</label>
                    <?php $mwMulti('reviewer_id', 'Select Reviewer...', true, null, 'taskReviewerSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Collaborators</label>
                    <?php $mwMulti('collaborator_ids[]', 'Select Collaborators...'); ?>
                </div>
            </div>

            <!-- Attachment -->
            <div class="mw-form-group">
                <label class="mw-form-label">Attachment</label>
                <input type="file" name="attachment" id="drawerAttachment" class="mw-form-input">
            </div>

            <!-- Start Date -->
            <div class="mw-form-group">
                    <label class="mw-form-label">Start Date</label>
                    <input type="date" name="start_date" class="mw-form-input">
                </div>

            <!-- Callout Notice Box -->
            <div class="mw-callout-box" id="mwTaskNoticeBox">
                <div class="mw-callout-icon">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="mw-callout-content">
                    <div class="mw-callout-title" id="noticeOwnerText">Owner will receive the assignment.</div>
                    <div class="mw-callout-sub" id="noticeReviewerText">Reviewer will be notified when the task is submitted for review.</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mw-drawer-footer">
            <div class="mw-drawer-footer-actions">
                <div class="d-flex gap-2">
                    <button type="button" class="mw-drawer-btn secondary" id="btnCancelDrawer">Cancel</button>
                </div>
                <button type="submit" class="mw-drawer-btn primary" id="btnSubmitDrawer">
                    <i class="fa-solid fa-plus me-1"></i> Create & assign
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Task detail drawer (UI preview; filled via AJAX on row click) -->
<div class="mw-td-backdrop" id="mwTdBackdrop"></div>
<div class="mw-td" id="mwTd" aria-hidden="true">
    <div class="mw-td-body" id="mwTdBody"></div>
    <div class="mw-td-foot">
        <!-- Reviewer note input box (shown when Reviewer clicks "Request changes") -->
        <div id="mwReviewNoteWrap" style="display:none; margin-bottom: 12px; background: #F8FAF9; border: 1px solid #E2EAE5; border-radius: 8px; padding: 12px;">
            <label class="mw-form-label" style="font-weight: 600; color: #9A6216; margin-bottom: 6px;">
                <i class="fa-solid fa-pen-to-square me-1"></i> Reviewer Improvement Note
            </label>
            <textarea id="mwReviewNoteText" class="mw-form-textarea" rows="2" placeholder="Write what needs to be improved or revised before completion..."></textarea>
            <div class="d-flex justify-content-end gap-2 mt-2">
                <button type="button" class="mw-td-btn" id="btnCancelReviewNote" style="padding: 6px 12px;">Cancel</button>
                <button type="button" class="mw-td-btn" id="btnSubmitReviewNote" style="padding: 6px 14px; background: #9A6216; color: #fff; border-color: #9A6216;">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Note
                </button>
            </div>
        </div>

        <div class="mw-td-foot-actions">
            <!-- Action complete / approve buttons first on left side -->
            <button type="button" class="mw-td-btn primary" id="btnDrawerCompleteTask" disabled>
                <i class="fa-solid fa-check me-1"></i> Complete Task
            </button>
            <button type="button" class="mw-td-btn" id="btnReviewerApprove" style="display:none; background: #087F5B; color: #fff; border-color: #087F5B; font-weight: 700;">
                <i class="fa-solid fa-circle-check me-1"></i> Approve & Done
            </button>
            <button type="button" class="mw-td-btn" id="btnReviewerRequestChanges" style="display:none; border-color: #9A6216; color: #9A6216; font-weight: 700;">
                <i class="fa-solid fa-rotate-left me-1"></i> Request Changes
            </button>
            <button type="button" class="mw-td-btn" disabled>Request extension</button>
        </div>
        <div class="mw-td-note" id="mwTdNote"></div>
    </div>
</div>

<!-- Modern Delete Confirmation Modal -->
<div class="mw-confirm-backdrop" id="mwDeleteModalBackdrop"></div>
<div class="mw-confirm-modal" id="mwDeleteModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="mw-confirm-icon">
        <i class="fa-solid fa-trash-can"></i>
    </div>
    <h3 class="mw-confirm-title">Delete Task?</h3>
    <p class="mw-confirm-desc">Are you sure you want to delete this task? All related comments, activities, and attachments will be permanently removed.</p>
    <div class="mw-confirm-actions">
        <button type="button" class="mw-confirm-btn cancel" id="btnCancelDeleteTask">Cancel</button>
        <button type="button" class="mw-confirm-btn danger" id="btnConfirmDeleteTask">
            <i class="fa-solid fa-trash-can me-1"></i> Delete Task
        </button>
    </div>
</div>

<!-- Request Extension Modal -->
<div class="mw-confirm-backdrop" id="mwExtensionModalBackdrop"></div>
<div class="mw-confirm-modal" id="mwExtensionModal" role="dialog" aria-modal="true" aria-hidden="true" style="max-width: 440px;">
    <div class="mw-confirm-icon" style="background: #E9F5EF; color: #087F5B;">
        <i class="fa-regular fa-calendar-plus"></i>
    </div>
    <h3 class="mw-confirm-title" style="margin-bottom: 4px;">Request Deadline Extension</h3>
    <p class="mw-confirm-desc" style="margin-bottom: 12px;">Propose a new deadline and explain why additional time is required.</p>
    
    <div style="background: #F5F7F6; border: 1px solid #E2EAE5; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; text-align: left; font-size: 12.5px;">
        <span style="color: #65776F;">Current Deadline:</span>
        <strong id="mwExtCurrentDeadline" style="color: #142D26; margin-left: 6px;">—</strong>
    </div>

    <div style="text-align: left; margin-bottom: 14px;">
        <label class="mw-form-label">Proposed New Deadline <span class="req">*</span></label>
        <input type="date" id="mwExtNewDeadline" class="mw-form-input" required>
    </div>

    <div style="text-align: left; margin-bottom: 16px;">
        <label class="mw-form-label">Reason for Extension <span class="req">*</span></label>
        <textarea id="mwExtReason" class="mw-form-textarea" rows="3" placeholder="Explain why extension is needed (e.g. waiting for dependencies, extra revisions required)..." required></textarea>
    </div>

    <div class="mw-confirm-actions">
        <button type="button" class="mw-confirm-btn cancel" id="btnCancelExtension">Cancel</button>
        <button type="button" class="mw-confirm-btn" id="btnSubmitExtension" style="background: #087F5B; color: #FFFFFF;">
            <i class="fa-solid fa-paper-plane me-1"></i> Submit Request
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= $base_url ?>';
    
    // State
    let currentTab = '<?= $tab ?>';
    let currentMetric = '<?= $metricFilter ?>';
    let currentPriority = '<?= $priorityFilter ?>';
    let currentStatus = '<?= $statusFilter ?>';
    let currentViewAll = false;
    let searchTimer = null;

    const tbody = document.getElementById('mwTasksTbody');
    const footerInfo = document.getElementById('mwFooterInfo');
    const badgeOpen = document.getElementById('badgeOpenCount');
    const badgeCompleted = document.getElementById('badgeCompletedCount');
    const textCompleted = document.getElementById('textCompletedCount');

    function syncHeaderStatusTabs(activeStatus) {
        const stVal = typeof activeStatus !== 'undefined' ? activeStatus : currentStatus;
        document.querySelectorAll('.mw-header-tab[data-header-status]').forEach(btn => {
            const st = btn.getAttribute('data-header-status');
            btn.classList.toggle('active', !currentMetric && st === stVal);
        });
    }
    const statOverdue = document.getElementById('statOverdue');
    const statDueToday = document.getElementById('statDueToday');
    const statNext7 = document.getElementById('statNext7');
    const statReview = document.getElementById('statReview');
    const searchInput = document.getElementById('mwSearchInput');

    // Central function to fetch and update workspace without page reload
    function fetchWorkspaceData() {
        if (!tbody) return;

        // Visual feedback (smooth fade)
        tbody.style.opacity = '0.4';

        const params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('tab', currentTab);
        if (currentMetric) params.set('metric', currentMetric);
        if (currentPriority) params.set('priority', currentPriority);
        if (currentStatus) params.set('status', currentStatus);
        if (currentViewAll) params.set('view_all', '1');
        if (searchInput && searchInput.value.trim() !== '') {
            params.set('search', searchInput.value.trim());
        }

        const fetchUrl = window.location.pathname + '?' + params.toString();

        fetch(fetchUrl, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                tbody.innerHTML = data.table_html;
                if (footerInfo && data.footer_info) {
                    footerInfo.innerText = data.footer_info;
                }
                const cardNeedsReview = document.getElementById('mwCardNeedsReview');
                if (cardNeedsReview && data.review_card_html) {
                    cardNeedsReview.innerHTML = data.review_card_html;
                }
                const cardTaskAttention = document.getElementById('mwCardTaskAttention');
                if (cardTaskAttention && data.attention_card_html) {
                    cardTaskAttention.innerHTML = data.attention_card_html;
                }
                if (data.all_stats) {
                    const s = data.all_stats;
                    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.innerText = v; };
                    setTxt('allTotal', s.total);
                    setTxt('allInProgress', s.in_progress);
                    setTxt('allCompleted', s.completed);
                    setTxt('allDeadlineToday', s.deadline_today);
                    setTxt('allDeadlineMissed', s.deadline_missed);
                    setTxt('allCompletionRate', s.completion_rate + '%');
                }
                if (data.tab_counts) {
                    Object.keys(data.tab_counts).forEach(k => {
                        const badgeEl = document.querySelector(`.mw-tab-badge[data-tab-count="${k}"]`);
                        if (badgeEl) {
                            const cnt = parseInt(data.tab_counts[k], 10) || 0;
                            badgeEl.innerText = cnt > 0 ? cnt : '';
                            badgeEl.style.display = cnt > 0 ? '' : 'none';
                        }
                    });
                }
                if (data.counts) {
                    if (badgeOpen) badgeOpen.innerText = `${data.counts.open} open`;
                    if (badgeCompleted) badgeCompleted.innerText = `${data.counts.completed} completed`;
                    if (textCompleted) textCompleted.innerText = `${(data.counts.open || 0) + (data.counts.completed || 0)} total`;
                    if (statOverdue) statOverdue.innerText = data.counts.overdue;
                    if (statDueToday) statDueToday.innerText = data.counts.due_today;
                    if (statNext7) statNext7.innerText = data.counts.next7;
                    if (statReview) statReview.innerText = data.counts.waiting_review;
                }

                if (typeof data.active_status !== 'undefined' && !currentMetric && currentStatus === '') {
                    currentStatus = data.active_status;
                }
                syncHeaderStatusTabs(data.active_status);

                // Update Browser URL state silently without page reload
                params.delete('ajax');
                const newUrl = `${baseUrl}/my-work-dashboard?${params.toString()}`;
                window.history.pushState(null, '', newUrl);
            }
        })
        .catch(err => {
            console.error('Workspace fetch error:', err);
        })
        .finally(() => {
            tbody.style.opacity = '1';
        });
    }

    // 1. Tab Click Event (Assigned to me, Assigned by me, Following)
    document.querySelectorAll('.mw-tab-item').forEach(tabEl => {
        tabEl.addEventListener('click', function (e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            if (tabName === currentTab) return;

            document.querySelectorAll('.mw-tab-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            currentTab = tabName;
            currentMetric = '';
            currentStatus = '';
            currentPriority = '';
            currentViewAll = false;
            const btnVa = document.getElementById('btnMyWorkViewAll');
            if (btnVa) btnVa.innerText = 'View all';
            const filterBtnText = document.getElementById('filterBtnText');
            if (filterBtnText) filterBtnText.innerText = 'Filters';
            document.querySelectorAll('.mw-stat-card').forEach(el => el.classList.remove('active-metric'));
            fetchWorkspaceData();
        });
    });

    // 1b. Header Status Tabs (My Actionable / Completed)
    document.querySelectorAll('.mw-header-tab[data-header-status]').forEach(tabBtn => {
        tabBtn.addEventListener('click', function () {
            const targetStatus = this.getAttribute('data-header-status');
            if (currentStatus === targetStatus && currentMetric === '') return;

            currentStatus = targetStatus;
            currentMetric = '';
            document.querySelectorAll('.mw-stat-card').forEach(el => el.classList.remove('active-metric'));
            const btnDueDateEl = document.getElementById('btnDueDate');
            if (btnDueDateEl) btnDueDateEl.classList.remove('active');

            syncHeaderStatusTabs(targetStatus);
            fetchWorkspaceData();
        });
    });

    // 2. Metric Cards Click Event (Overdue, Due today, Next 7 days, Review)
    document.querySelectorAll('#mwStatsMetric .mw-stat-card, #mwStatsAll .mw-stat-card[data-metric]').forEach(cardEl => {
        cardEl.addEventListener('click', function (e) {
            e.preventDefault();
            const metric = this.getAttribute('data-metric');

            // Clear conflicting dropdown filters so card filter operates cleanly
            currentStatus = '';
            currentPriority = '';
            const priorityText = document.getElementById('priorityText');
            if (priorityText) priorityText.innerText = 'Priority';
            const statusText = document.getElementById('statusText');
            if (statusText) statusText.innerText = 'Status';

            // Toggle filter: If clicking already active metric, reset it
            if (currentMetric === metric) {
                currentMetric = '';
                this.classList.remove('active-metric');
            } else {
                document.querySelectorAll('.mw-stat-card').forEach(el => el.classList.remove('active-metric'));
                this.classList.add('active-metric');
                currentMetric = metric;
            }

            fetchWorkspaceData();
        });
    });

    // 3. Search Box with Debounce (Live Instant Search)
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                fetchWorkspaceData();
            }, 300);
        });
    }

    // Popup Menus Toggle Logic (Image 2 Figma Style)
    const btnDueDate = document.getElementById('btnDueDate');
    const menuDueDate = document.getElementById('menuDueDate');
    const btnFilters = document.getElementById('btnFilters');
    const menuFilters = document.getElementById('menuFilters');

    if (btnDueDate && menuDueDate) {
        btnDueDate.addEventListener('click', function (e) {
            e.stopPropagation();
            if (menuFilters) menuFilters.classList.remove('show');
            menuDueDate.classList.toggle('show');
        });
    }

    if (btnFilters && menuFilters) {
        btnFilters.addEventListener('click', function (e) {
            e.stopPropagation();
            if (menuDueDate) menuDueDate.classList.remove('show');
            menuFilters.classList.toggle('show');
        });
    }

    // Close popups on click outside
    document.addEventListener('click', function () {
        if (menuDueDate) menuDueDate.classList.remove('show');
        if (menuFilters) menuFilters.classList.remove('show');
    });

    // Due Date Popup Items
    if (menuDueDate) {
        menuDueDate.querySelectorAll('.mw-popup-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menuDueDate.classList.remove('show');
                const metric = this.getAttribute('data-metric');
                currentMetric = metric;

                if (metric) {
                    btnDueDate.classList.add('active');
                } else {
                    btnDueDate.classList.remove('active');
                }

                document.querySelectorAll('.mw-stat-card').forEach(el => {
                    if (el.getAttribute('data-metric') === metric && metric) {
                        el.classList.add('active-metric');
                    } else {
                        el.classList.remove('active-metric');
                    }
                });

                fetchWorkspaceData();
            });
        });
    }

    // Filters Popup Items (Priority and Status)
    if (menuFilters) {
        menuFilters.querySelectorAll('.mw-popup-item[data-priority]').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menuFilters.classList.remove('show');
                currentPriority = this.getAttribute('data-priority');
                fetchWorkspaceData();
            });
        });

        menuFilters.querySelectorAll('.mw-popup-item[data-status]').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menuFilters.classList.remove('show');
                currentStatus = this.getAttribute('data-status');
                fetchWorkspaceData();
            });
        });
    }

    // 4. Delete Task with Custom Confirmation Modal
    const deleteModal = document.getElementById('mwDeleteModal');
    const deleteModalBackdrop = document.getElementById('mwDeleteModalBackdrop');
    const btnCancelDelete = document.getElementById('btnCancelDeleteTask');
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteTask');

    let pendingDeleteTaskId = null;
    let pendingDeleteRow = null;

    function closeDeleteModal() {
        if (!deleteModal || !deleteModalBackdrop) return;
        deleteModal.classList.remove('show');
        deleteModalBackdrop.classList.remove('show');
        deleteModal.setAttribute('aria-hidden', 'true');
        pendingDeleteTaskId = null;
        pendingDeleteRow = null;
        if (btnConfirmDelete) {
            btnConfirmDelete.disabled = false;
            btnConfirmDelete.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i> Delete Task';
        }
    }

    if (btnCancelDelete) {
        btnCancelDelete.addEventListener('click', closeDeleteModal);
    }
    if (deleteModalBackdrop) {
        deleteModalBackdrop.addEventListener('click', closeDeleteModal);
    }

    window.deleteMyWorkTask = function(taskId, btnEl) {
        pendingDeleteTaskId = taskId;
        pendingDeleteRow = btnEl ? btnEl.closest('tr') : document.querySelector(`tr[data-task-id="${taskId}"]`);

        if (deleteModal && deleteModalBackdrop) {
            deleteModal.classList.add('show');
            deleteModalBackdrop.classList.add('show');
            deleteModal.setAttribute('aria-hidden', 'false');
        }
    };

    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function() {
            if (!pendingDeleteTaskId) return;

            const taskId = pendingDeleteTaskId;
            const row = pendingDeleteRow;

            btnConfirmDelete.disabled = true;
            btnConfirmDelete.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Deleting...';

            if (row) {
                row.style.opacity = '0.35';
                row.style.pointerEvents = 'none';
            }

            fetch(`${baseUrl}/my-work/delete/${taskId}?ajax=1`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                if (data.status === 'success') {
                    showMwToast(data.message || 'Task deleted successfully.', 'success');
                    if (row) {
                        row.remove();
                    }
                    // Close drawer if this task is currently open in drawer
                    if (typeof currentDetailTaskId !== 'undefined' && currentDetailTaskId === taskId) {
                        if (typeof closeTaskDetail === 'function') closeTaskDetail();
                    }
                    // Refresh statistics and data seamlessly
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || 'Failed to delete task.', 'error');
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                closeDeleteModal();
                showMwToast('An error occurred while deleting the task.', 'error');
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
            });
        });
    }

    // =========================================================================
    // 5. Create Task Slide Modal (Right Drawer) Logic
    // =========================================================================
    const btnOpenCreateTask = document.getElementById('btnOpenCreateTask');
    const drawer = document.getElementById('mwCreateTaskDrawer');
    const backdrop = document.getElementById('mwDrawerBackdrop');
    const btnCloseCreateTask = document.getElementById('btnCloseCreateTask');
    const btnCancelDrawer = document.getElementById('btnCancelDrawer');
    const formCreateTask = document.getElementById('mwCreateTaskForm');
    const btnSubmitDrawer = document.getElementById('btnSubmitDrawer');

    function openDrawer(mode = 'create') {
        if (drawer && backdrop) {
            const isEdit = (mode === 'edit');
            const drawerTitle = document.getElementById('mwDrawerTitle');
            const drawerSubtitle = document.getElementById('mwDrawerSubtitle');
            const editIdInput = document.getElementById('drawerEditTaskId');
            if (drawerTitle) drawerTitle.innerText = isEdit ? 'Edit task' : 'Create task';
            if (drawerSubtitle) drawerSubtitle.innerText = isEdit ? 'Update details, owner and deadline.' : 'Define the output, owner and deadline.';
            if (btnSubmitDrawer) {
                btnSubmitDrawer.innerHTML = isEdit ? '<i class="fa-solid fa-check me-1"></i> Update task' : '<i class="fa-solid fa-plus me-1"></i> Create & assign';
            }

            drawer.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
            const titleInput = document.getElementById('drawerTaskTitle');
            if (titleInput) setTimeout(() => titleInput.focus(), 320);
        }
    }

    function resetDrawerForm() {
        if (!formCreateTask) return;
        formCreateTask.reset();
        const editIdInput = document.getElementById('drawerEditTaskId');
        if (editIdInput) editIdInput.value = '';
        if (expectedList) {
            expectedList.querySelectorAll('.mw-expected-row').forEach((r, idx) => { if (idx > 0) r.remove(); });
        }
        if (rowRecurrenceEnd) rowRecurrenceEnd.style.display = 'none';
        const selRec = document.getElementById('taskRecurrenceType');
        if (selRec) selRec.required = false;
        document.querySelectorAll('.mw-multi').forEach(b => {
            const s = b.querySelector('.mw-multi-search');
            if (s) s.value = '';
            b.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = '');
            if (b._refresh) b._refresh();
        });
        updateNoticeBox();
    }

    function closeDrawer() {
        if (drawer && backdrop) {
            drawer.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
            resetDrawerForm();
            const drawerTitle = document.getElementById('mwDrawerTitle');
            if (drawerTitle) drawerTitle.innerText = 'Create task';
            if (btnSubmitDrawer) btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Create & assign';
        }
    }

    if (btnOpenCreateTask) {
        btnOpenCreateTask.addEventListener('click', function() {
            resetDrawerForm();
            openDrawer('create');
        });
    }
    if (btnCloseCreateTask) btnCloseCreateTask.addEventListener('click', closeDrawer);
    if (btnCancelDrawer) btnCancelDrawer.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    // Global function to open Edit Drawer for a Task
    window.openEditTaskDrawer = function(taskId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        resetDrawerForm();
        const editIdInput = document.getElementById('drawerEditTaskId');
        if (editIdInput) editIdInput.value = taskId;
        openDrawer('edit');

        // Show loading state in title field
        const titleInput = document.getElementById('drawerTaskTitle');
        if (titleInput) titleInput.placeholder = 'Loading task details...';

        fetch(`${baseUrl}/my-work/task-data/${taskId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.task) {
                const t = data.task;
                if (titleInput) {
                    titleInput.value = t.title || '';
                    titleInput.placeholder = 'Client ledger: verify opening balances';
                }
                const deadlineInput = document.getElementById('drawerDeadline');
                if (deadlineInput) deadlineInput.value = t.deadline || '';

                const startDateInput = formCreateTask.querySelector('input[name="start_date"]');
                if (startDateInput) startDateInput.value = t.start_date || '';

                const prioritySelect = formCreateTask.querySelector('select[name="priority"]');
                if (prioritySelect && t.priority) prioritySelect.value = t.priority;

                const projectSelect = formCreateTask.querySelector('select[name="project_id"]');
                if (projectSelect) projectSelect.value = t.project_id || '';

                // Recurring
                const chkRec = document.getElementById('chkIsRecurring');
                if (chkRec) {
                    chkRec.checked = (t.is_recurring === 1);
                    if (rowRecurrenceEnd) rowRecurrenceEnd.style.display = chkRec.checked ? 'block' : 'none';
                    const selRec = document.getElementById('taskRecurrenceType');
                    if (selRec) {
                        selRec.value = t.recurrence_type || '';
                        selRec.required = chkRec.checked;
                    }
                }

                // Expected output field
                const txtExpectedOutput = document.getElementById('drawerExpectedOutput');
                if (txtExpectedOutput) txtExpectedOutput.value = t.expected_output || '';

                // Acceptance checklist rows
                if (expectedList) {
                    expectedList.querySelectorAll('.mw-expected-row').forEach((r, idx) => { if (idx > 0) r.remove(); });
                    const firstTextarea = expectedList.querySelector('textarea');
                    const descs = Array.isArray(t.descriptions) && t.descriptions.length ? t.descriptions : [''];
                    if (firstTextarea) firstTextarea.value = descs[0] || '';
                    for (let i = 1; i < descs.length; i++) {
                        const row = document.createElement('div');
                        row.className = 'd-flex align-items-start gap-2 mb-2 mw-expected-row';
                        row.innerHTML = `<textarea name="description[]" class="mw-form-textarea" rows="1" placeholder="Another checklist item"></textarea>
                            <button type="button" class="mw-btn-create" title="Remove" style="white-space:nowrap;background:#C92A2A;"><i class="fa-solid fa-xmark"></i></button>`;
                        row.querySelector('textarea').value = descs[i];
                        row.querySelector('button').addEventListener('click', () => row.remove());
                        expectedList.appendChild(row);
                    }
                }

                // Assigned To (single select radio)
                const boxAssignedTo = document.getElementById('taskAssignedToSelect');
                if (boxAssignedTo && t.assigned_to_id) {
                    boxAssignedTo.querySelectorAll('input[type=radio]').forEach(r => {
                        r.checked = (parseInt(r.value, 10) === parseInt(t.assigned_to_id, 10));
                    });
                    if (boxAssignedTo._refresh) boxAssignedTo._refresh();
                }

                // Reviewer (single select radio)
                const boxReviewer = document.getElementById('taskReviewerSelect');
                if (boxReviewer) {
                    boxReviewer.querySelectorAll('input[type=radio]').forEach(r => {
                        r.checked = (t.reviewer_id && parseInt(r.value, 10) === parseInt(t.reviewer_id, 10));
                    });
                    if (boxReviewer._refresh) boxReviewer._refresh();
                }

                // CC (multiple select checkbox)
                const boxCc = document.querySelector('.mw-multi[data-placeholder="Select CC..."]');
                if (boxCc) {
                    const ccIds = Array.isArray(t.cc_ids) ? t.cc_ids.map(Number) : [];
                    boxCc.querySelectorAll('input[type=checkbox]').forEach(c => {
                        c.checked = ccIds.includes(parseInt(c.value, 10));
                    });
                    if (boxCc._refresh) boxCc._refresh();
                }

                // Collaborators (multiple select checkbox)
                const boxCollab = document.querySelector('.mw-multi[data-placeholder="Select Collaborators..."]');
                if (boxCollab) {
                    const colIds = Array.isArray(t.collaborator_ids) ? t.collaborator_ids.map(Number) : [];
                    boxCollab.querySelectorAll('input[type=checkbox]').forEach(c => {
                        c.checked = colIds.includes(parseInt(c.value, 10));
                    });
                    if (boxCollab._refresh) boxCollab._refresh();
                }

                updateNoticeBox();
            } else {
                showMwToast(data.message || 'Failed to load task details.', 'error');
                closeDrawer();
            }
        })
        .catch(err => {
            console.error('Error fetching edit task data:', err);
            showMwToast('Could not load task details for editing.', 'error');
            closeDrawer();
        });
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });

    // =========================================================================
    // Task detail drawer (right side) - opens when a task row is clicked
    // =========================================================================
    const tdEl = document.getElementById('mwTd');
    const tdBackdrop = document.getElementById('mwTdBackdrop');
    const tdBody = document.getElementById('mwTdBody');
    const tdNote = document.getElementById('mwTdNote');
    const tdEsc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const tdInitials = n => String(n || '?').replace(/\s*\([^)]*\)/g, '').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
    const tdAvatarHtml = (name, photo) => {
        if (photo) {
            return `<img src="${tdEsc(photo)}" alt="${tdEsc(name)}" class="mw-td-avatar" style="object-fit:cover;" onerror="this.outerHTML='<span class=\\'mw-td-avatar\\'>${tdEsc(tdInitials(name))}</span>'">`;
        }
        return `<span class="mw-td-avatar">${tdEsc(tdInitials(name))}</span>`;
    };
    const tdPeople = list => {
        if (!list || !list.length) return '<span class="mw-td-val plain">—</span>';
        return list.map(item => {
            const name = typeof item === 'object' && item !== null ? item.name : item;
            const photo = typeof item === 'object' && item !== null ? item.photo : null;
            return `<span class="mw-td-val">${tdAvatarHtml(name, photo)}${tdEsc(name)}</span>`;
        }).join('');
    };

    function closeTaskDetail() {
        if (!tdEl) return;
        tdEl.classList.remove('show');
        tdBackdrop.classList.remove('show');
        tdEl.setAttribute('aria-hidden', 'true');
    }

    const btnDrawerCompleteTask = document.getElementById('btnDrawerCompleteTask');
    let currentDetailTaskId = null;

    function renderTaskDetail(t) {
        currentDetailTaskId = t.id;
        const isReviewerView = Boolean(t.is_reviewer && (t.is_completed || t.status === 'completed' || t.status_class === 'in_review'));
        const remindBtnHtml = (t.is_owner && !t.is_completed)
            ? `<button type="button" class="mw-td-remind-btn" id="btnSendTaskReminder" title="Send email reminder to Assignee, Collaborators, Reviewer & CC">
                <i class="fa-regular fa-bell"></i> Remind
               </button>`
            : '';
        const bindRemindBtn = () => {
            const btnRemind = tdBody.querySelector('#btnSendTaskReminder');
            if (!btnRemind) return;
            btnRemind.addEventListener('click', function() {
                btnRemind.disabled = true;
                btnRemind.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

                fetch(`${baseUrl}/my-work/send-reminder/${t.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMwToast(data.message || 'Reminder email sent to all task members!', 'success');
                        const activeTabEl = tdBody.querySelector('.mw-td-tab.active');
                        const activePaneIdx = activeTabEl ? activeTabEl.getAttribute('data-pane') : null;
                        openTaskDetail(t.id, activePaneIdx);
                    } else {
                        showMwToast(data.message || 'Failed to send reminder.', 'error');
                        btnRemind.disabled = false;
                        btnRemind.innerHTML = '<i class="fa-regular fa-bell"></i> Remind';
                    }
                })
                .catch(err => {
                    console.error('Error sending task reminder:', err);
                    showMwToast('Network error while sending reminder.', 'error');
                    btnRemind.disabled = false;
                    btnRemind.innerHTML = '<i class="fa-regular fa-bell"></i> Remind';
                });
            });
        };

        if (isReviewerView) {
            // =========================================================================
            // Reviewer Submission Drawer Layout (Figma Spec)
            // =========================================================================
            const ownerInitial = tdInitials(t.owner);
            const reviewerInitial = tdInitials(t.current_user_name || 'MJ');
            const ownerName = t.owner;
            const reviewerDisplay = `You · ${tdEsc(t.current_user_name || 'Mehedi H. Jony')}`;
            const departmentName = t.department || 'General';
            const dueDateDisplay = t.deadline || 'No deadline';
            const submissionText = (t.expected_output && t.expected_output.trim())
                ? tdEsc(t.expected_output).replace(/\n/g, '<br>')
                : (t.description_html ? t.description_html : 'No submission summary provided.');

            // Acceptance checks (all items completed or submitted)
            let checksHtml = '';
            if (Array.isArray(t.checklist) && t.checklist.length > 0) {
                const totalChecks = t.checklist.length;
                const completedChecks = t.checklist.filter(c => !!c.completed).length;
                checksHtml = `
                    <div class="mw-td-sec-head" style="margin-bottom:14px;">
                        <h4 style="font-size:16px;">Acceptance checks</h4>
                        <span class="mw-cl-counter-badge">${completedChecks} of ${totalChecks} complete</span>
                    </div>
                    <div class="mw-cl-items-wrap" style="margin-bottom:20px;">` +
                    t.checklist.map(item => {
                        const isChecked = !!item.completed;
                        const boxClass = isChecked ? 'checked' : 'unchecked';
                        const textClass = isChecked ? 'checked' : 'unchecked';
                        return `
                        <div class="mw-cl-item" style="cursor:default; margin-bottom:12px;">
                            <span class="mw-cl-box ${boxClass}" style="${isChecked ? 'background:#087F5B; border-color:#087F5B;' : 'background:#FFFFFF; border:1.5px solid #CCD7D0;'}">
                                ${isChecked ? '<svg width="10" height="8" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.25 4L3.75 6.5L8.75 1.5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
                            </span>
                            <span class="mw-cl-item-text ${textClass}" style="font-size:13px;">${tdEsc(item.text)}</span>
                        </div>`;
                    }).join('') +
                    `</div>`;
            }

            // Evidence attachment
            const evidenceHtml = t.attachment_url
                ? `<div class="mw-td-sec" style="margin-top:0;">
                    <h4 style="font-size:16px; margin-bottom:12px;">Evidence</h4>
                    <a class="mw-td-file" href="${tdEsc(t.attachment_url)}" target="_blank">
                        <span class="mw-td-file-left">
                            <i class="fa-regular fa-file-lines mw-td-file-icon"></i>
                            <span style="font-size:13px; font-weight:400;">${tdEsc(t.attachment_name)}</span>
                        </span>
                        <span class="mw-td-file-size">Open</span>
                    </a>
                   </div>`
                : '';

            tdBody.innerHTML = `
                <div class="mw-td-top">
                    <span>${tdEsc(t.code)} · ${tdEsc(t.project)}</span>
                    <button type="button" class="mw-td-close" id="mwTdClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <h2 class="mw-td-title" style="font-size:25px; line-height:30px; margin:14px 0 12px;">${tdEsc(t.title)}</h2>
                <div class="mw-td-pills" style="margin-bottom:18px; align-items:center;">
                    <span class="mw-td-pill in-review">In review</span>
                    ${t.due_badge ? `<span class="mw-td-pill ${t.overdue ? 'overdue' : ''}">${tdEsc(t.due_badge)}</span>` : ''}
                    ${remindBtnHtml ? `<span style="margin-left:auto;">${remindBtnHtml}</span>` : ''}
                </div>

                <!-- Your review is needed banner -->
                <div class="mw-rev-banner">
                    <div class="mw-rev-banner-title">Your review is needed</div>
                    <div class="mw-rev-banner-sub">${tdEsc(ownerName)} submitted this task ${tdEsc(t.submission_time ? 'on ' + t.submission_time : 'for your review')}.</div>
                </div>

                <!-- 4-col Meta Grid -->
                <div class="mw-td-grid" style="margin-bottom:24px;">
                    <div>
                        <div class="mw-td-label">Task owner</div>
                        <div class="mw-td-val">
                            ${tdAvatarHtml(ownerName, t.owner_user ? t.owner_user.photo : null)}
                            <span>${tdEsc(ownerName)}</span>
                        </div>
                    </div>
                    <div>
                        <div class="mw-td-label">Reviewer</div>
                        <div class="mw-td-val">
                            <span class="mw-td-avatar">${tdEsc(reviewerInitial)}</span>
                            <span>${reviewerDisplay}</span>
                        </div>
                    </div>
                    <div>
                        <div class="mw-td-label">Due date</div>
                        <div class="mw-td-val">${tdEsc(dueDateDisplay)}</div>
                    </div>
                    <div>
                        <div class="mw-td-label">Department</div>
                        <div class="mw-td-val plain">${tdEsc(departmentName)}</div>
                    </div>
                </div>

                <!-- Submission summary -->
                <div class="mw-td-sec">
                    <h3 class="mw-rev-summary-title">Submission summary</h3>
                    <div class="mw-rev-summary-text">${submissionText}</div>
                </div>

                <!-- Acceptance checks -->
                ${checksHtml ? `<div class="mw-td-sec">${checksHtml}</div>` : ''}

                <!-- Evidence -->
                ${evidenceHtml}

                <!-- Review note input -->
                ${!t.is_review_approved ? `
                <div class="mw-td-sec" style="border-bottom:none;">
                    <div class="mw-rev-note-head">
                        <span class="mw-rev-note-label">Review note</span>
                        <span class="mw-rev-note-req">Required when requesting changes</span>
                    </div>
                    <textarea id="mwReviewFeedbackInput" class="mw-rev-textarea" placeholder="Add feedback"></textarea>
                    <div style="margin-top:10px; display:flex; justify-content:flex-start;">
                        <button type="button" class="mw-td-btn" id="btnRevRequestChanges">
                            Request changes
                        </button>
                    </div>
                </div>` : ''}
            `;

            tdBody.querySelector('#mwTdClose').addEventListener('click', closeTaskDetail);
            bindRemindBtn();

            // Configure footer for Reviewer
            const footActions = document.querySelector('.mw-td-foot-actions');
            const revNoteWrap = document.getElementById('mwReviewNoteWrap');
            if (revNoteWrap) revNoteWrap.style.display = 'none';

            if (footActions) {
                if (t.is_review_approved) {
                    footActions.innerHTML = `
                        <button type="button" class="mw-td-btn" disabled style="background:#EDF2EF;color:#087F5B;border-color:#EDF2EF; font-weight:700;">
                            <i class="fa-solid fa-circle-check me-1"></i> Approved & completed
                        </button>
                        <button type="button" class="mw-td-btn" id="btnRevBackToTasks">Back to tasks</button>
                    `;
                    document.getElementById('btnRevBackToTasks').addEventListener('click', closeTaskDetail);
                } else {
                    footActions.innerHTML = `
                        <button type="button" class="mw-td-btn green" id="btnRevApproveComplete">
                            <i class="fa-solid fa-check me-1"></i> Approve & complete
                        </button>
                        <button type="button" class="mw-td-btn" id="btnRevBackToTasks">Back to tasks</button>
                    `;

                    document.getElementById('btnRevBackToTasks').addEventListener('click', closeTaskDetail);

                    const btnReqChanges = document.getElementById('btnRevRequestChanges');
                    if (btnReqChanges) {
                        btnReqChanges.addEventListener('click', function() {
                            const feedbackEl = document.getElementById('mwReviewFeedbackInput');
                            const note = feedbackEl ? feedbackEl.value.trim() : '';
                            if (!note) {
                                showMwToast(`Please add a review note for ${ownerName} before requesting changes.`, 'error');
                                if (feedbackEl) feedbackEl.focus();
                                return;
                            }

                            this.disabled = true;
                            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Requesting...';

                            const fd = new FormData();
                            fd.append('review_action', 'request_changes');
                            fd.append('review_note', note);

                            fetch(`${baseUrl}/my-work/reviewer-action/${t.id}`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: fd
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    showMwToast('Feedback sent. Task returned to in progress.', 'success');
                                    closeTaskDetail();
                                    fetchWorkspaceData();
                                } else {
                                    showMwToast(data.message || 'Failed to request changes.', 'error');
                                }
                            })
                            .catch(() => {
                                showMwToast('Network error occurred.', 'error');
                            })
                            .finally(() => {
                                this.disabled = false;
                                this.innerHTML = 'Request changes';
                            });
                        });
                    }

                    document.getElementById('btnRevApproveComplete').addEventListener('click', function() {
                        const feedbackEl = document.getElementById('mwReviewFeedbackInput');
                        const note = feedbackEl ? feedbackEl.value.trim() : '';

                        this.disabled = true;
                        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Completing...';

                        const fd = new FormData();
                        fd.append('review_action', 'approve');
                        if (note) fd.append('review_note', note);

                        fetch(`${baseUrl}/my-work/reviewer-action/${t.id}`, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: fd
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showMwToast('Task approved and marked as completed!', 'success');
                                closeTaskDetail();
                                fetchWorkspaceData();
                            } else {
                                showMwToast(data.message || 'Failed to approve task.', 'error');
                            }
                        })
                        .catch(() => {
                            showMwToast('Network error occurred.', 'error');
                        })
                        .finally(() => {
                            this.disabled = false;
                            this.innerHTML = '<i class="fa-solid fa-check me-1"></i> Approve & complete';
                        });
                    });
                }
            }

            tdNote.innerText = t.is_review_approved
                ? `This task has been approved and completed.`
                : `Approve completes the task. Request changes returns it to In progress with your feedback. ${ownerName} remains the task owner.`;
            return;
        }

        // =========================================================================
        // Standard Assignee / Owner Layout
        // =========================================================================
        const tabsHtml = ['Overview', 'Activity', 'Comments'].map((n, i) =>
            `<button type="button" class="mw-td-tab ${i === 0 ? 'active' : ''}" data-pane="${i}">${n}</button>`).join('');
        const blocked = t.blocked ? `
            <div class="mw-td-blocked"><strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Blocked</strong><span>${tdEsc(t.remarks || '')}</span></div>` : '';
        const expectedOut = t.expected_output && t.expected_output.trim()
            ? `<div class="mw-td-text">${tdEsc(t.expected_output).replace(/\n/g, '<br>')}</div>` : '<div class="mw-td-empty">No expected output specified.</div>';

        // Render Acceptance Checklist matching Figma spec
        let checklistHtml = '';
        if (Array.isArray(t.checklist) && t.checklist.length > 0) {
            const completedCount = t.checklist.filter(c => !!c.completed).length;
            const pct = Math.round((completedCount / t.checklist.length) * 100);
            checklistHtml = `
                <div class="mw-td-sec-head">
                    <h4>Acceptance checklist</h4>
                    <span class="mw-cl-counter-badge">${completedCount} of ${t.checklist.length} complete</span>
                </div>
                <div class="mw-cl-progress-bar">
                    <div class="mw-cl-progress-fill" style="width: ${pct}%;"></div>
                </div>
                <div class="mw-cl-items-wrap">` +
                t.checklist.map((item, idx) => {
                    const isChecked = !!item.completed;
                    const canCheck = !!t.can_check;
                    const boxClass = isChecked ? 'checked' : 'unchecked';
                    const textClass = isChecked ? 'checked' : 'unchecked';
                    return `
                        <div class="mw-cl-item" data-cl-idx="${idx}" style="cursor:${canCheck ? 'pointer' : 'default'};">
                            <span class="mw-cl-box ${boxClass}">
                                ${isChecked ? '<svg width="10" height="8" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.25 4L3.75 6.5L8.75 1.5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
                            </span>
                            <span class="mw-cl-item-text ${textClass}">${tdEsc(item.text)}</span>
                        </div>`;
                }).join('') +
                `</div>`;
        } else {
            checklistHtml = `
                <div class="mw-td-sec-head">
                    <h4>Acceptance checklist</h4>
                </div>
                <div class="mw-td-empty">No checklist items added.</div>`;
        }

        // Precompile sorted teammates regex for full-name @mentions
        const teammatesList = Array.isArray(t.teammates) ? t.teammates : [];
        const sortedTeammateNames = teammatesList
            .map(tm => (tm.name || '').trim())
            .filter(Boolean)
            .sort((a, b) => b.length - a.length);

        const formatMentions = (rawText) => {
            let escaped = tdEsc(rawText);
            if (sortedTeammateNames.length > 0) {
                const escapeReg = s => s.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
                const exactPattern = new RegExp('@(' + sortedTeammateNames.map(escapeReg).join('|') + ')(?=[.,!?]|$|\\s)', 'g');
                escaped = escaped.replace(exactPattern, '<span class="mw-act-mention">@$1</span>');
            }
            // Fallback for any other single-word or hyphenated @mention not in team list
            return escaped.replace(/(^|\s)@([a-zA-Z0-9_.-]+)(?=[.,!?]|$|\s)/g, '$1<span class="mw-act-mention">@$2</span>');
        };

        // Build single timeline item HTML
        const renderActivityItem = (act) => {
            const isSystem = (act.type === 'system');
            const initial = tdInitials(act.user_name || 'U');
            let formattedActivity = formatMentions(act.activity);
            let attachChip = '';
            if (act.attachment_url && act.attachment_name) {
                attachChip = `
                    <div>
                        <a href="${tdEsc(act.attachment_url)}" target="_blank" rel="noopener noreferrer" class="mw-act-attach-chip">
                            <i class="fa-solid fa-paperclip"></i>
                            <span>${tdEsc(act.attachment_name)}</span>
                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px; margin-left:2px;"></i>
                        </a>
                    </div>`;
            }
            const avatarHtml = act.user_photo
                ? `<img src="${tdEsc(act.user_photo)}" alt="${tdEsc(act.user_name)}" class="mw-act-avatar" style="object-fit:cover;" onerror="this.outerHTML='<span class=\\'mw-act-avatar\\'>${tdEsc(initial)}</span>'">`
                : `<span class="mw-act-avatar">${tdEsc(initial)}</span>`;

            const bodyContent = isSystem
                ? `<div class="mw-act-comment" style="color:#65776F; font-style:italic;">${formattedActivity}</div>`
                : `<div class="mw-act-comment">${formattedActivity}</div>`;

            return `
                <div class="mw-activity-item">
                    ${avatarHtml}
                    <div style="flex:1;">
                        <div class="mw-act-head">
                            ${tdEsc(act.user_name)} <span class="mw-act-time">· ${tdEsc(act.created_at)}</span>
                        </div>
                        ${bodyContent}
                        ${attachChip}
                    </div>
                </div>`;
        };

        // Render Latest Activity matching Figma spec
        let activitiesHtml = '';
        if (Array.isArray(t.activities) && t.activities.length > 0) {
            activitiesHtml = `
                <div class="mw-activity-timeline" style="margin-bottom:12px;">` +
                t.activities.map(renderActivityItem).join('') +
                `</div>`;
        } else {
            activitiesHtml = '<div class="mw-td-empty mb-2">No activity yet.</div>';
        }

        // Render Comments Only for Comments Tab (exclude system audit logs)
        const commentsList = (Array.isArray(t.activities) ? t.activities : []).filter(act => act.type === 'comment');
        let commentsOnlyHtml = '';
        if (commentsList.length > 0) {
            commentsOnlyHtml = `
                <div class="mw-activity-timeline" style="margin-bottom:12px;">` +
                commentsList.map(renderActivityItem).join('') +
                `</div>`;
        } else {
            commentsOnlyHtml = '<div class="mw-td-empty mb-2">No comments yet.</div>';
        }

        // Figma input box with trailing file upload button (Enter to post comment)
        const commentBoxHtml = `
            <div class="mw-figma-comment-box" id="mwCommentBoxContainer">
                <input type="text" id="mwCommentInput" class="mw-figma-comment-input" placeholder="Write a comment or @mention a teammate…" autocomplete="off">
                <input type="file" id="mwCommentFileInput" style="display:none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.csv">
                <div class="mw-figma-comment-actions">
                    <button type="button" class="mw-figma-icon-btn" id="mwBtnAttachFile" title="Attach file">
                        <i class="fa-solid fa-paperclip"></i>
                    </button>
                    <button type="button" class="mw-figma-icon-btn" id="mwBtnPostComment" title="Post comment" style="color: #087F5B;">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <!-- Mentions dropdown popup -->
                <div class="mw-mention-popup" id="mwMentionPopup"></div>
            </div>
            <!-- File preview tag -->
            <div class="mw-comment-file-preview" id="mwCommentFilePreview">
                <div class="d-flex align-items-center gap-2 text-truncate">
                    <i class="fa-solid fa-file-lines text-muted"></i>
                    <span id="mwCommentFileName" class="text-truncate"></span>
                </div>
                <span class="file-remove" id="mwBtnRemoveFile" title="Remove file"><i class="fa-solid fa-xmark"></i></span>
            </div>`;

        // Attachments row matching Figma
        const file = t.attachment_url
            ? `<a class="mw-td-file" href="${tdEsc(t.attachment_url)}" target="_blank">
                <span class="mw-td-file-left">
                    <i class="fa-regular fa-file-lines mw-td-file-icon"></i>
                    <span>${tdEsc(t.attachment_name)}</span>
                </span>
                <span class="mw-td-file-size">Open</span>
               </a>`
            : '<div class="mw-td-empty">No attachments.</div>';

        // Owner Feedback & Satisfaction Rating section (Only when task is completed)
        let ownerFeedbackHtml = '';
        if (t.is_completed) {
            const currentRating = parseInt(t.satisfaction_rating, 10) || 0;
            if (t.is_owner) {
                if (currentRating > 0) {
                    // Already rated by owner
                    let starsHtml = '';
                    for (let s = 1; s <= 5; s++) {
                        starsHtml += `<i class="fa-${s <= currentRating ? 'solid' : 'regular'} fa-star"></i>`;
                    }
                    ownerFeedbackHtml = `
                        <div class="mw-feedback-readonly">
                            <div>
                                <div style="font-size:11.5px; font-weight:700; color:#65776F; text-transform:uppercase; margin-bottom:4px;">Your Rating & Feedback</div>
                                <div class="mw-feedback-readonly-stars">
                                    ${starsHtml}
                                    <span style="font-weight:700; color:#142D26; font-size:13px; margin-left:6px;">${currentRating} of 5 Stars</span>
                                </div>
                            </div>
                            <span class="mw-feedback-readonly-badge"><i class="fa-solid fa-check me-1"></i> Feedback Submitted</span>
                        </div>`;
                } else {
                    // Owner can rate and give feedback
                    ownerFeedbackHtml = `
                        <div class="mw-feedback-card" id="mwOwnerFeedbackBox">
                            <div class="mw-feedback-head">
                                <h4 class="mw-feedback-title">
                                    <i class="fa-solid fa-award" style="color:#087F5B;"></i>
                                    Rate & Feedback for ${tdEsc(t.assigned_to)}
                                </h4>
                                <div class="mw-rating-stars-wrap" id="mwStarRatingPicker">
                                    <button type="button" class="mw-star-btn" data-star="1" title="1 Star"><i class="fa-solid fa-star"></i></button>
                                    <button type="button" class="mw-star-btn" data-star="2" title="2 Stars"><i class="fa-solid fa-star"></i></button>
                                    <button type="button" class="mw-star-btn" data-star="3" title="3 Stars"><i class="fa-solid fa-star"></i></button>
                                    <button type="button" class="mw-star-btn" data-star="4" title="4 Stars"><i class="fa-solid fa-star"></i></button>
                                    <button type="button" class="mw-star-btn" data-star="5" title="5 Stars"><i class="fa-solid fa-star"></i></button>
                                </div>
                            </div>
                            <textarea id="mwFeedbackComment" class="mw-feedback-textarea" placeholder="Add a feedback note for ${tdEsc(t.assigned_to)} (optional)…"></textarea>
                            <div style="display:flex; justify-content:flex-end;">
                                <button type="button" class="mw-td-btn green" id="btnSubmitOwnerFeedback" style="padding:6px 14px; font-size:12px;">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Submit Feedback
                                </button>
                            </div>
                        </div>`;
                }
            } else if (currentRating > 0) {
                // Assignee or collaborator viewing the owner's rating
                let starsHtml = '';
                for (let s = 1; s <= 5; s++) {
                    starsHtml += `<i class="fa-${s <= currentRating ? 'solid' : 'regular'} fa-star"></i>`;
                }
                ownerFeedbackHtml = `
                    <div class="mw-feedback-readonly">
                        <div>
                            <div style="font-size:11.5px; font-weight:700; color:#65776F; text-transform:uppercase; margin-bottom:4px;">Task Owner Rating</div>
                            <div class="mw-feedback-readonly-stars">
                                ${starsHtml}
                                <span style="font-weight:700; color:#142D26; font-size:13px; margin-left:6px;">${currentRating} of 5 Stars</span>
                            </div>
                        </div>
                        <span class="mw-feedback-readonly-badge"><i class="fa-solid fa-star me-1"></i> Completed Task</span>
                    </div>`;
            }
        }

        tdBody.innerHTML = `
            <div class="mw-td-top">
                <span>${tdEsc(t.code)} · ${tdEsc(t.project)}</span>
                <button type="button" class="mw-td-close" id="mwTdClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <h2 class="mw-td-title">${tdEsc(t.title)}</h2>
            <div class="mw-td-pills">
                <span class="mw-td-pill status" id="mwTdStatusPill">${tdEsc(t.status_label)}</span>
                <span class="mw-td-pill">${tdEsc(t.priority)} priority</span>
                ${t.overdue ? `<span class="mw-td-pill overdue">${tdEsc(t.overdue)}</span>` : ''}
            </div>
            <div class="mw-td-tabs">${tabsHtml}${remindBtnHtml}</div>
            <div data-td-pane="0">
                ${blocked}
                <div class="mw-td-grid">
                    <div><div class="mw-td-label">Owner</div><div class="mw-td-val">${tdAvatarHtml(t.owner, t.owner_user ? t.owner_user.photo : null)}${tdEsc(t.owner)}</div></div>
                    <div><div class="mw-td-label">Reviewer</div><div>${tdPeople(t.reviewers)}</div></div>
                    <div><div class="mw-td-label">Deadline</div><div class="mw-td-val ${t.overdue ? 'late' : ''}">${tdEsc(t.deadline || 'No deadline')}</div></div>
                    <div><div class="mw-td-label">Collaborators</div><div>${tdPeople(t.collaborators)}</div></div>
                    <div><div class="mw-td-label">Assigned to</div><div class="mw-td-val">${tdAvatarHtml(t.assigned_to, t.assigned_user ? t.assigned_user.photo : null)}${tdEsc(t.assigned_to)}</div></div>
                    <div><div class="mw-td-label">CC</div><div>${tdPeople(t.cc)}</div></div>
                </div>
                ${t.expected_output && t.expected_output.trim() ? `<div class="mw-td-sec"><h4>Expected output</h4>${expectedOut}</div>` : ''}
                <div class="mw-td-sec">${checklistHtml}</div>
                <div class="mw-td-sec"><h4>Attachments</h4>${file}</div>
                <div class="mw-td-sec">
                    <h4>Latest activity</h4>
                    ${activitiesHtml}
                    ${commentBoxHtml}
                    ${ownerFeedbackHtml}
                </div>
            </div>
            <div data-td-pane="1" style="display:none;">
                <div class="mw-td-sec">
                    ${activitiesHtml}
                    ${commentBoxHtml}
                    ${ownerFeedbackHtml}
                </div>
            </div>
            <div data-td-pane="2" style="display:none;">
                <div class="mw-td-sec">
                    ${commentsOnlyHtml}
                    ${commentBoxHtml}
                </div>
            </div>`;
        tdNote.innerText = t.blocked ? 'Resolve the blocker before submitting for review.' : '';
        tdBody.querySelector('#mwTdClose').addEventListener('click', closeTaskDetail);
        bindRemindBtn();
        tdBody.querySelectorAll('.mw-td-tab').forEach(b => b.addEventListener('click', () => {
            tdBody.querySelectorAll('.mw-td-tab').forEach(x => x.classList.toggle('active', x === b));
            tdBody.querySelectorAll('[data-td-pane]').forEach(p => p.style.display = p.dataset.tdPane === b.dataset.pane ? '' : 'none');
        }));

        // Reviewer controls vs Assignee complete controls for standard view
        const footActions = document.querySelector('.mw-td-foot-actions');
        if (footActions) {
            const extBtnDisabled = (!t.can_request_extension || t.is_completed) ? 'disabled' : '';
            const hasReviewers = (Array.isArray(t.reviewers) && t.reviewers.length > 0);
            const isPending = (t.status === 'pending');
            
            let actionBtnHtml = '';
            if (t.is_completed) {
                actionBtnHtml = `<button type="button" class="mw-td-btn" disabled style="background:#EDF2EF;color:#087F5B;border-color:#EDF2EF;"><i class="fa-solid fa-check-double me-1"></i> Completed</button>`;
            } else if (t.status_class === 'in_review') {
                actionBtnHtml = `<button type="button" class="mw-td-btn" disabled style="background:#F4EFFA;color:#7757AB;border-color:#F4EFFA;font-weight:700;"><i class="fa-solid fa-hourglass-half me-1"></i> Submitted for review</button>`;
            } else if (isPending && t.can_check) {
                actionBtnHtml = `<button type="button" class="mw-td-btn primary" id="btnDrawerStartTask" style="background:#087F5B;color:#fff;border-color:#087F5B;"><i class="fa-solid fa-play me-1"></i> Start working</button>`;
            } else if (t.can_complete) {
                const btnLabel = hasReviewers ? 'Submit for review' : 'Complete Task';
                actionBtnHtml = `<button type="button" class="mw-td-btn primary" id="btnDrawerCompleteTask" style="background:#087F5B;color:#fff;border-color:#087F5B;"><i class="fa-solid fa-check me-1"></i> ${btnLabel}</button>`;
            } else {
                const disabledLabel = hasReviewers ? 'Awaiting Review' : 'Complete Task';
                actionBtnHtml = `<button type="button" class="mw-td-btn" disabled style="background:#EDF2EF;color:#8A9992;border-color:#EDF2EF;"><i class="fa-solid fa-check me-1"></i> ${disabledLabel}</button>`;
            }

            footActions.innerHTML = `
                ${actionBtnHtml}
                <button type="button" class="mw-td-btn" id="btnDrawerRequestExtension" ${extBtnDisabled}>Request extension</button>
            `;

            const btnStart = document.getElementById('btnDrawerStartTask');
            if (btnStart) {
                btnStart.addEventListener('click', function() {
                    if (window._mwIsSendingComment) {
                        showMwToast('Please wait for your comment to finish posting.', 'info');
                        return;
                    }
                    btnStart.disabled = true;
                    btnStart.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Starting...';
                    fetch(`${baseUrl}/my-work/start-task/${t.id}`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showMwToast(data.message || 'Task started!', 'success');
                            openTaskDetail(t.id);
                            fetchWorkspaceData();
                        } else {
                            showMwToast(data.message || 'Failed to start task.', 'error');
                            btnStart.disabled = false;
                            btnStart.innerHTML = '<i class="fa-solid fa-play me-1"></i> Start working';
                        }
                    })
                    .catch(err => {
                        console.error('Error starting task:', err);
                        showMwToast('Network error while starting task.', 'error');
                        btnStart.disabled = false;
                        btnStart.innerHTML = '<i class="fa-solid fa-play me-1"></i> Start working';
                    });
                });
            }
        }
        const btnDrawerCompleteTask = document.getElementById('btnDrawerCompleteTask');
        const revNoteWrap = document.getElementById('mwReviewNoteWrap');
        if (revNoteWrap) revNoteWrap.style.display = 'none';

        // Wire up checklist item click handlers (Figma custom checkbox)
        let isChecklistToggling = false;
        tdBody.querySelectorAll('.mw-cl-item').forEach(itemEl => {
            itemEl.addEventListener('click', function() {
                if (!t.can_check || isChecklistToggling) return;
                const itemIdx = parseInt(this.getAttribute('data-cl-idx'), 10);
                const box = this.querySelector('.mw-cl-box');
                const textSpan = this.querySelector('.mw-cl-item-text');
                const willBeChecked = !box.classList.contains('checked');

                isChecklistToggling = true;
                this.style.opacity = '0.6';
                this.style.pointerEvents = 'none';

                // Optimistic UI toggle
                box.classList.toggle('checked', willBeChecked);
                box.classList.toggle('unchecked', !willBeChecked);
                box.innerHTML = willBeChecked ? '<i class="fa-solid fa-check" style="font-size:9px;"></i>' : '';
                if (textSpan) {
                    textSpan.classList.toggle('checked', willBeChecked);
                    textSpan.classList.toggle('unchecked', !willBeChecked);
                }

                // Update local task checklist model and progress counter immediately
                if (Array.isArray(t.checklist) && t.checklist[itemIdx]) {
                    t.checklist[itemIdx].completed = willBeChecked;
                    const doneCount = t.checklist.filter(c => !!c.completed).length;
                    const totalCount = t.checklist.length;
                    const pct = Math.round((doneCount / totalCount) * 100);

                    const badgeEl = tdBody.querySelector('.mw-cl-counter-badge');
                    if (badgeEl) badgeEl.textContent = `${doneCount} of ${totalCount} complete`;

                    const barEl = tdBody.querySelector('.mw-cl-progress-fill');
                    if (barEl) barEl.style.width = `${pct}%`;
                }

                // Send AJAX toggle request
                const fd = new FormData();
                fd.append('index', itemIdx);
                fd.append('completed', willBeChecked ? 1 : 0);

                fetch(`${baseUrl}/my-work/toggle-checklist/${t.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.all_completed) {
                            const hasRev = (Array.isArray(t.reviewers) && t.reviewers.length > 0);
                            showMwToast(hasRev ? 'All acceptance completed. You can now submit for review.' : 'All acceptance completed. You can now complete the task.', 'success');
                            if (window.openTaskDetail && currentDetailTaskId) {
                                window.openTaskDetail(currentDetailTaskId);
                            }
                        } else if (t.status_class === 'in_review') {
                            // Unchecking an item while in review refreshes drawer back to in_progress
                            if (window.openTaskDetail && currentDetailTaskId) {
                                window.openTaskDetail(currentDetailTaskId);
                            }
                        }
                        // Refresh background workspace tables and count cards without destroying open drawer
                        if (typeof fetchWorkspaceData === 'function') {
                            fetchWorkspaceData();
                        }
                    } else {
                        showMwToast(data.message || 'Failed to update checklist item.', 'error');
                        // Rollback
                        box.classList.toggle('checked', !willBeChecked);
                        box.classList.toggle('unchecked', willBeChecked);
                        box.innerHTML = !willBeChecked ? '<i class="fa-solid fa-check" style="font-size:9px;"></i>' : '';
                        if (textSpan) {
                            textSpan.classList.toggle('checked', !willBeChecked);
                            textSpan.classList.toggle('unchecked', willBeChecked);
                        }
                    }
                })
                .catch(err => {
                    console.error('Error toggling checklist:', err);
                    showMwToast('Network error while updating checklist.', 'error');
                    box.classList.toggle('checked', !willBeChecked);
                    box.classList.toggle('unchecked', willBeChecked);
                    box.innerHTML = !willBeChecked ? '<i class="fa-solid fa-check" style="font-size:9px;"></i>' : '';
                    if (textSpan) {
                        textSpan.classList.toggle('checked', !willBeChecked);
                        textSpan.classList.toggle('unchecked', willBeChecked);
                    }
                })
                .finally(() => {
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                    isChecklistToggling = false;
                });
            });
        });
        // Wire up comment send, @mention autocomplete, and file attachment handlers
        const commentBoxes = tdBody.querySelectorAll('.mw-figma-comment-box');
        commentBoxes.forEach(commentBox => {
            const input = commentBox.querySelector('.mw-figma-comment-input');
            const btnAttach = commentBox.querySelector('#mwBtnAttachFile') || commentBox.querySelector('.mw-figma-icon-btn[title="Attach file"]');
            const btnPost = commentBox.querySelector('#mwBtnPostComment') || commentBox.querySelector('.mw-figma-icon-btn[title="Post comment"]');
            const fileInput = commentBox.querySelector('input[type="file"]');
            const filePreview = commentBox.parentElement.querySelector('.mw-comment-file-preview');
            const fileNameSpan = filePreview ? filePreview.querySelector('span.text-truncate') : null;
            const btnRemoveFile = filePreview ? filePreview.querySelector('.file-remove') : null;
            const mentionPopup = commentBox.querySelector('.mw-mention-popup');

            let selectedFile = null;
            let mentionedUserIds = new Set();
            let isSendingComment = false;
            const teammates = Array.isArray(t.teammates) ? t.teammates : [];

            // Attachment trigger
            if (btnAttach && fileInput) {
                btnAttach.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        selectedFile = this.files[0];
                        if (fileNameSpan && filePreview) {
                            fileNameSpan.textContent = selectedFile.name + ' (' + (Math.round(selectedFile.size / 1024)) + ' KB)';
                            filePreview.classList.add('show');
                        }
                    }
                });
            }

            // Remove selected file
            if (btnRemoveFile) {
                btnRemoveFile.addEventListener('click', function() {
                    selectedFile = null;
                    if (fileInput) fileInput.value = '';
                    if (filePreview) filePreview.classList.remove('show');
                });
            }

            // @mention Autocomplete Logic
            let mentionQuery = '';
            let mentionStartIndex = -1;

            const hideMentionPopup = () => {
                if (mentionPopup) {
                    mentionPopup.classList.remove('show');
                    mentionPopup.innerHTML = '';
                }
                mentionStartIndex = -1;
            };

            const renderMentionList = (filterText) => {
                if (!mentionPopup) return;
                const q = filterText.toLowerCase();
                const matched = teammates.filter(u => u.name.toLowerCase().includes(q));

                if (matched.length === 0) {
                    hideMentionPopup();
                    return;
                }

                mentionPopup.innerHTML = matched.map((u, idx) => {
                    const avatar = u.photo
                        ? `<img src="${tdEsc(u.photo)}" class="mw-mention-avatar" alt="${tdEsc(u.name)}">`
                        : `<span class="mw-mention-avatar">${tdEsc(tdInitials(u.name))}</span>`;
                    return `
                        <div class="mw-mention-item${idx === 0 ? ' selected' : ''}" data-uid="${u.id}" data-name="${tdEsc(u.name)}">
                            ${avatar}
                            <span class="mw-mention-name">${tdEsc(u.name)}</span>
                            <span class="mw-mention-role">${tdEsc(u.designation)}</span>
                        </div>`;
                }).join('');

                mentionPopup.classList.add('show');

                mentionPopup.querySelectorAll('.mw-mention-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const uid = parseInt(this.getAttribute('data-uid'), 10);
                        const uname = this.getAttribute('data-name');
                        insertMention(uname, uid);
                    });
                });
            };

            const insertMention = (name, uid) => {
                if (mentionStartIndex < 0 || !input) return;
                const text = input.value;
                const before = text.slice(0, mentionStartIndex);
                const cursor = input.selectionStart || text.length;
                const after = text.slice(cursor);
                input.value = before + '@' + name + ' ' + after;
                mentionedUserIds.add(uid);
                hideMentionPopup();
                input.focus();
            };

            if (input) {
                input.addEventListener('input', function() {
                    const val = this.value;
                    const cursorPos = this.selectionStart || 0;
                    const textBeforeCursor = val.slice(0, cursorPos);
                    const lastAt = textBeforeCursor.lastIndexOf('@');

                    if (lastAt !== -1 && (lastAt === 0 || /\s/.test(textBeforeCursor[lastAt - 1]))) {
                        mentionStartIndex = lastAt;
                        const query = textBeforeCursor.slice(lastAt + 1);
                        if (query.length <= 40) {
                            renderMentionList(query);
                            return;
                        }
                    }
                    hideMentionPopup();
                });
            }

            document.addEventListener('click', function(e) {
                if (commentBox && !commentBox.contains(e.target)) {
                    hideMentionPopup();
                }
            });

            // Comment submission via Enter key or Send button
            window._mwIsSendingComment = false;
            const doSendComment = () => {
                if (isSendingComment || window._mwIsSendingComment || !input) return;
                const commentText = input.value.trim();
                if (!commentText && !selectedFile) return;

                isSendingComment = true;
                window._mwIsSendingComment = true;
                input.disabled = true;
                if (btnPost) btnPost.disabled = true;

                const fd = new FormData();
                fd.append('comment', commentText);
                if (selectedFile) {
                    fd.append('attachment', selectedFile);
                }
                if (mentionedUserIds.size > 0) {
                    fd.append('mentioned_ids', Array.from(mentionedUserIds).join(','));
                }

                // Preserve active tab before refreshing drawer
                const activeTabEl = tdBody.querySelector('.mw-td-tab.active');
                const activePaneIdx = activeTabEl ? activeTabEl.getAttribute('data-pane') : null;

                fetch(`${baseUrl}/my-work/add-comment/${t.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMwToast('Comment posted.', 'success');
                        input.value = '';
                        selectedFile = null;
                        mentionedUserIds.clear();
                        if (fileInput) fileInput.value = '';
                        if (filePreview) filePreview.classList.remove('show');
                        if (window.openTaskDetail && currentDetailTaskId) {
                            window.openTaskDetail(currentDetailTaskId, activePaneIdx);
                        }
                    } else {
                        showMwToast(data.message || 'Failed to post comment.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error posting comment:', err);
                    showMwToast('Network error while posting comment.', 'error');
                })
                .finally(() => {
                    isSendingComment = false;
                    window._mwIsSendingComment = false;
                    if (input) {
                        input.disabled = false;
                        input.focus();
                    }
                    if (btnPost) btnPost.disabled = false;
                });
            };

            if (input) {
                input.addEventListener('keydown', e => {
                    const isPopupOpen = mentionPopup && mentionPopup.classList.contains('show');
                    if (isPopupOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                        const items = Array.from(mentionPopup.querySelectorAll('.mw-mention-item'));
                        if (items.length > 0) {
                            e.preventDefault();
                            let curIdx = items.findIndex(el => el.classList.contains('selected'));
                            if (curIdx >= 0) items[curIdx].classList.remove('selected');
                            curIdx = e.key === 'ArrowDown'
                                ? (curIdx + 1) % items.length
                                : (curIdx <= 0 ? items.length - 1 : curIdx - 1);
                            items[curIdx].classList.add('selected');
                            items[curIdx].scrollIntoView({ block: 'nearest' });
                            return;
                        }
                    }
                    if (e.key === 'Enter') {
                        if (isPopupOpen) {
                            const activeItem = mentionPopup.querySelector('.mw-mention-item.selected') || mentionPopup.querySelector('.mw-mention-item');
                            if (activeItem) {
                                e.preventDefault();
                                activeItem.click();
                                return;
                            }
                        }
                        e.preventDefault();
                        doSendComment();
                    } else if (e.key === 'Escape') {
                        hideMentionPopup();
                    }
                });
            }

            if (btnPost) {
                btnPost.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    doSendComment();
                });
            }
        });

        const btnCompleteDynamic = document.getElementById('btnDrawerCompleteTask');
        if (btnCompleteDynamic && !t.is_completed && t.can_complete) {
            btnCompleteDynamic.addEventListener('click', function() {
                if (window._mwIsSendingComment) {
                    showMwToast('Please wait for your comment to finish posting.', 'info');
                    return;
                }

                // Check if all checklist items are ticked
                if (Array.isArray(t.checklist) && t.checklist.length > 0) {
                    const incompleteCount = t.checklist.filter(c => !c.completed).length;
                    if (incompleteCount > 0) {
                        showMwToast(`Please complete all acceptance (${incompleteCount} remaining) before completing the task.`, 'error');
                        return;
                    }
                }

                btnCompleteDynamic.disabled = true;
                btnCompleteDynamic.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';
                fetch(`${baseUrl}/my-work/complete-task/${t.id}?ajax=1`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMwToast(data.message || 'Task updated successfully.', 'success');
                        openTaskDetail(t.id);
                        fetchWorkspaceData();
                    } else {
                        showMwToast(data.message || 'Failed to complete task.', 'error');
                        btnCompleteDynamic.disabled = false;
                        const hasReviewers = (Array.isArray(t.reviewers) && t.reviewers.length > 0);
                        btnCompleteDynamic.innerHTML = hasReviewers ? '<i class="fa-solid fa-check me-1"></i> Submit for review' : '<i class="fa-solid fa-check me-1"></i> Complete Task';
                    }
                })
                .catch(err => {
                    console.error('Error completing task:', err);
                    showMwToast('An error occurred while updating task.', 'error');
                    btnCompleteDynamic.disabled = false;
                    const hasReviewers = (Array.isArray(t.reviewers) && t.reviewers.length > 0);
                    btnCompleteDynamic.innerHTML = hasReviewers ? '<i class="fa-solid fa-check me-1"></i> Submit for review' : '<i class="fa-solid fa-check me-1"></i> Complete Task';
                });
            });
        }

        // Wire up Request Extension button
        const btnDrawerReqExt = document.getElementById('btnDrawerRequestExtension');
        if (btnDrawerReqExt) {
            btnDrawerReqExt.addEventListener('click', () => {
                if (t.is_completed || !t.can_request_extension) return;
                openExtensionModal(t);
            });
        }

        // Wire up Owner Feedback & Star Rating submission
        const feedbackBox = tdBody.querySelector('#mwOwnerFeedbackBox');
        if (feedbackBox && t.is_completed && t.is_owner) {
            const starWrap = feedbackBox.querySelector('#mwStarRatingPicker');
            const commentInput = feedbackBox.querySelector('#mwFeedbackComment');
            const btnSubmitFeedback = feedbackBox.querySelector('#btnSubmitOwnerFeedback');
            let selectedRating = 0;

            if (starWrap) {
                const starBtns = starWrap.querySelectorAll('.mw-star-btn');
                starBtns.forEach(btn => {
                    const starVal = parseInt(btn.getAttribute('data-star'), 10);
                    btn.addEventListener('mouseenter', () => {
                        starBtns.forEach(b => {
                            const val = parseInt(b.getAttribute('data-star'), 10);
                            b.classList.toggle('hovered', val <= starVal);
                        });
                    });
                    btn.addEventListener('mouseleave', () => {
                        starBtns.forEach(b => b.classList.remove('hovered'));
                    });
                    btn.addEventListener('click', () => {
                        selectedRating = starVal;
                        starBtns.forEach(b => {
                            const val = parseInt(b.getAttribute('data-star'), 10);
                            b.classList.toggle('active', val <= selectedRating);
                        });
                    });
                });
            }

            if (btnSubmitFeedback) {
                btnSubmitFeedback.addEventListener('click', function() {
                    if (selectedRating < 1 || selectedRating > 5) {
                        showMwToast('Please select a star rating (1 to 5).', 'error');
                        return;
                    }
                    const feedbackVal = commentInput ? commentInput.value.trim() : '';

                    btnSubmitFeedback.disabled = true;
                    btnSubmitFeedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...';

                    const fd = new FormData();
                    fd.append('rating', selectedRating);
                    if (feedbackVal) fd.append('feedback', feedbackVal);

                    fetch(`${baseUrl}/my-work/submit-feedback/${t.id}`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showMwToast('Feedback submitted successfully!', 'success');
                            openTaskDetail(t.id, 0);
                            fetchWorkspaceData();
                        } else {
                            showMwToast(data.message || 'Failed to submit feedback.', 'error');
                            btnSubmitFeedback.disabled = false;
                            btnSubmitFeedback.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Feedback';
                        }
                    })
                    .catch(err => {
                        console.error('Feedback submission error:', err);
                        showMwToast('Network error while submitting feedback.', 'error');
                        btnSubmitFeedback.disabled = false;
                        btnSubmitFeedback.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Feedback';
                    });
                });
            }
        }
    }

    // Reviewer Action Handlers
    const btnRevApprove = document.getElementById('btnReviewerApprove');
    const btnRevChanges = document.getElementById('btnReviewerRequestChanges');
    const revNoteWrap = document.getElementById('mwReviewNoteWrap');
    const revNoteText = document.getElementById('mwReviewNoteText');
    const btnCancelReviewNote = document.getElementById('btnCancelReviewNote');
    const btnSubmitReviewNote = document.getElementById('btnSubmitReviewNote');

    if (btnRevChanges) {
        btnRevChanges.addEventListener('click', function() {
            if (revNoteWrap) {
                revNoteWrap.style.display = 'block';
                if (revNoteText) {
                    revNoteText.focus();
                }
            }
        });
    }

    if (btnCancelReviewNote) {
        btnCancelReviewNote.addEventListener('click', function() {
            if (revNoteWrap) revNoteWrap.style.display = 'none';
            if (revNoteText) revNoteText.value = '';
        });
    }

    if (btnSubmitReviewNote) {
        btnSubmitReviewNote.addEventListener('click', function() {
            if (!currentDetailTaskId) return;
            const note = revNoteText ? revNoteText.value.trim() : '';
            if (!note) {
                alert('Please enter a note explaining what needs improvement.');
                if (revNoteText) revNoteText.focus();
                return;
            }

            btnSubmitReviewNote.disabled = true;
            btnSubmitReviewNote.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';

            const formData = new FormData();
            formData.append('review_action', 'request_changes');
            formData.append('review_note', note);

            fetch(`${baseUrl}/my-work/reviewer-action/${currentDetailTaskId}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMwToast(data.message || 'Review note sent successfully.', 'success');
                    if (revNoteWrap) revNoteWrap.style.display = 'none';
                    if (revNoteText) revNoteText.value = '';
                    openTaskDetail(currentDetailTaskId);
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || 'Failed to submit review note.', 'error');
                }
            })
            .catch(err => {
                console.error('Review note error:', err);
                showMwToast('An error occurred while submitting the review note.', 'error');
            })
            .finally(() => {
                btnSubmitReviewNote.disabled = false;
                btnSubmitReviewNote.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Note';
            });
        });
    }

    if (btnRevApprove) {
        btnRevApprove.addEventListener('click', function() {
            if (!currentDetailTaskId) return;
            if (!confirm('Are you sure everything is verified and you want to approve this task as Done?')) {
                return;
            }

            btnRevApprove.disabled = true;
            btnRevApprove.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Approving...';

            const formData = new FormData();
            formData.append('review_action', 'approve');

            fetch(`${baseUrl}/my-work/reviewer-action/${currentDetailTaskId}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMwToast(data.message || 'Task approved as completed.', 'success');
                    openTaskDetail(currentDetailTaskId);
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || 'Failed to approve task.', 'error');
                }
            })
            .catch(err => {
                console.error('Approve task error:', err);
                showMwToast('An error occurred while approving the task.', 'error');
            })
            .finally(() => {
                btnRevApprove.disabled = false;
                btnRevApprove.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Approve & Done';
            });
        });
    }

    if (btnDrawerCompleteTask) {
        btnDrawerCompleteTask.addEventListener('click', function() {
            if (!currentDetailTaskId) return;
            btnDrawerCompleteTask.disabled = true;
            btnDrawerCompleteTask.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...';

            fetch(`${baseUrl}/my-work/complete-task/${currentDetailTaskId}?ajax=1`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMwToast(data.message || 'Task marked as completed successfully.', 'success');
                    openTaskDetail(currentDetailTaskId);
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || 'Failed to complete task.', 'error');
                    btnDrawerCompleteTask.disabled = false;
                    btnDrawerCompleteTask.innerHTML = '<i class="fa-solid fa-check me-1"></i> Complete Task';
                }
            })
            .catch(err => {
                console.error('Error completing task:', err);
                showMwToast('An error occurred while updating the task status.', 'error');
                btnDrawerCompleteTask.disabled = false;
                btnDrawerCompleteTask.innerHTML = '<i class="fa-solid fa-check me-1"></i> Complete Task';
            });
        });
    }
    // Extension Modal Logic
    const extModal = document.getElementById('mwExtensionModal');
    const extBackdrop = document.getElementById('mwExtensionModalBackdrop');
    const extNewDeadlineInput = document.getElementById('mwExtNewDeadline');
    const extReasonInput = document.getElementById('mwExtReason');
    const btnCancelExt = document.getElementById('btnCancelExtension');
    const btnSubmitExt = document.getElementById('btnSubmitExtension');
    let extTaskId = null;

    function openExtensionModal(task) {
        if (!extModal || !extBackdrop) return;
        extTaskId = task.id;

        // Display current deadline clearly
        const curDlSpan = document.getElementById('mwExtCurrentDeadline');
        if (curDlSpan) {
            curDlSpan.textContent = task.deadline || 'No deadline';
        }

        if (extNewDeadlineInput) {
            // Set default date picker based on task's actual deadline + 2 days, or today + 2 days (Date only)
            let baseDate = new Date();
            if (task.raw_deadline) {
                const parsedDl = new Date(task.raw_deadline.replace(' ', 'T'));
                if (!isNaN(parsedDl.getTime())) {
                    baseDate = parsedDl;
                }
            }
            baseDate.setDate(baseDate.getDate() + 2);

            const pad = n => String(n).padStart(2, '0');
            const defaultDateStr = `${baseDate.getFullYear()}-${pad(baseDate.getMonth() + 1)}-${pad(baseDate.getDate())}`;
            extNewDeadlineInput.value = defaultDateStr;

            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            extNewDeadlineInput.min = `${tomorrow.getFullYear()}-${pad(tomorrow.getMonth() + 1)}-${pad(tomorrow.getDate())}`;
        }
        if (extReasonInput) extReasonInput.value = '';
        extModal.classList.add('show');
        extBackdrop.classList.add('show');
    }

    function closeExtensionModal() {
        if (extModal) extModal.classList.remove('show');
        if (extBackdrop) extBackdrop.classList.remove('show');
        extTaskId = null;
    }

    if (btnCancelExt) btnCancelExt.addEventListener('click', closeExtensionModal);
    if (extBackdrop) extBackdrop.addEventListener('click', closeExtensionModal);

    if (btnSubmitExt) {
        btnSubmitExt.addEventListener('click', function() {
            if (!extTaskId) return;
            const newDeadline = extNewDeadlineInput ? extNewDeadlineInput.value.trim() : '';
            const reason = extReasonInput ? extReasonInput.value.trim() : '';

            if (!newDeadline) {
                showMwToast('Please select a proposed new deadline.', 'error');
                if (extNewDeadlineInput) extNewDeadlineInput.focus();
                return;
            }
            if (!reason) {
                showMwToast('Please provide a reason for the extension request.', 'error');
                if (extReasonInput) extReasonInput.focus();
                return;
            }

            btnSubmitExt.disabled = true;
            btnSubmitExt.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...';

            const fd = new FormData();
            fd.append('new_deadline', newDeadline);
            fd.append('reason', reason);

            fetch(`${baseUrl}/my-work/request-extension/${extTaskId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showMwToast(data.message || 'Extension request submitted.', 'success');
                    closeExtensionModal();
                    if (window.openTaskDetail && currentDetailTaskId) {
                        window.openTaskDetail(currentDetailTaskId);
                    }
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || 'Failed to submit extension request.', 'error');
                }
            })
            .catch(err => {
                console.error('Error requesting extension:', err);
                showMwToast('Network error while requesting extension.', 'error');
            })
            .finally(() => {
                btnSubmitExt.disabled = false;
                btnSubmitExt.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Request';
            });
        });
    }

    window.filterReviewSubmissionTasks = function() {
        currentMetric = 'review_submission';
        // Clear conflicting dropdown filters so review submission filter operates cleanly
        currentStatus = '';
        currentPriority = '';
        const priorityText = document.getElementById('priorityText');
        if (priorityText) priorityText.innerText = 'Priority';
        const statusText = document.getElementById('statusText');
        if (statusText) statusText.innerText = 'Status';
        const filterBtnText = document.getElementById('filterBtnText');
        if (filterBtnText) filterBtnText.innerText = 'Filters';

        document.querySelectorAll('.mw-stat-card').forEach(el => el.classList.remove('active-metric'));
        fetchWorkspaceData();

        // Highlight/scroll to table
        const tableWrap = document.querySelector('.mw-table-wrap');
        if (tableWrap) {
            tableWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    window.openTaskDetail = function(taskId, activePaneIndex = null) {
        if (!tdEl) return;
        if (activePaneIndex === null) {
            tdBody.innerHTML = '<div class="mw-td-empty" style="padding:24px 0;">Loading…</div>';
        }
        tdEl.classList.add('show');
        tdBackdrop.classList.add('show');
        tdEl.setAttribute('aria-hidden', 'false');
        fetch(`${baseUrl}/my-work/task-detail/${taskId}`, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    renderTaskDetail(d.task);
                    if (activePaneIndex !== null) {
                        const targetTab = tdBody.querySelector(`.mw-td-tab[data-pane="${activePaneIndex}"]`);
                        if (targetTab) {
                            tdBody.querySelectorAll('.mw-td-tab').forEach(x => x.classList.toggle('active', x === targetTab));
                            tdBody.querySelectorAll('[data-td-pane]').forEach(p => p.style.display = p.dataset.tdPane === String(activePaneIndex) ? '' : 'none');
                        }
                    }
                } else {
                    tdBody.innerHTML = `<div class="mw-td-empty" style="padding:24px 0;">${tdEsc(d.message || 'Could not load task.')}</div>`;
                }
            })
            .catch(() => { tdBody.innerHTML = '<div class="mw-td-empty" style="padding:24px 0;">Could not load task.</div>'; });
    };

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            if (e.target.closest('.mw-actions-group')) return;
            const row = e.target.closest('tr[data-task-id]');
            if (!row) return;
            e.preventDefault();
            openTaskDetail(row.getAttribute('data-task-id'));
        });
        tbody.style.cursor = 'pointer';
    }
    if (tdBackdrop) tdBackdrop.addEventListener('click', closeTaskDetail);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTaskDetail(); });

    // View All Tasks toggle logic
    const btnViewAll = document.getElementById('btnMyWorkViewAll');
    if (btnViewAll) {
        btnViewAll.addEventListener('click', function(e) {
            e.preventDefault();
            currentViewAll = !currentViewAll;
            this.innerText = currentViewAll ? 'View less' : 'View all';
            fetchWorkspaceData();
        });
    }
    // Dynamic Notice Box Text
    // Single-select dropdown helper: { value, name } of the ticked user
    const mwPicked = id => {
        const i = document.querySelector('#' + id + ' input:checked');
        return { value: i ? i.value : '', name: i ? i.dataset.name : '' };
    };
    const taskOwnerSelect = document.getElementById('taskAssignedToSelect');
    const taskReviewerSelect = document.getElementById('taskReviewerSelect');
    const noticeOwnerText = document.getElementById('noticeOwnerText');
    const noticeReviewerText = document.getElementById('noticeReviewerText');

    function updateNoticeBox() {
        if (taskOwnerSelect && noticeOwnerText) {
            const o = mwPicked('taskAssignedToSelect');
            const firstName = (o.name || 'Owner').trim().split(' ')[0];
            noticeOwnerText.innerText = `${firstName} will receive the assignment.`;
        }
        if (taskReviewerSelect && noticeReviewerText) {
            const r = mwPicked('taskReviewerSelect');
            if (r.value) {
                const firstNameR = r.name.trim().split(' ')[0];
                noticeReviewerText.innerText = `${firstNameR} will be notified when the task is submitted for review.`;
                noticeReviewerText.style.display = 'block';
            } else {
                noticeReviewerText.style.display = 'none';
            }
        }
    }

    if (taskOwnerSelect) taskOwnerSelect.addEventListener('change', updateNoticeBox);
    if (taskReviewerSelect) taskReviewerSelect.addEventListener('change', updateNoticeBox);
    updateNoticeBox();

    // Show Repeat options only when "Is Recurring" is ticked
    const chkIsRecurring = document.getElementById('chkIsRecurring');
    const rowRecurrenceEnd = document.getElementById('rowRecurrenceEnd');
    if (chkIsRecurring && rowRecurrenceEnd) {
        chkIsRecurring.addEventListener('change', () => {
            rowRecurrenceEnd.style.display = chkIsRecurring.checked ? 'block' : 'none';
            const selRec = document.getElementById('taskRecurrenceType');
            if (selRec) selRec.required = chkIsRecurring.checked;
        });
    }

    // Multi-select dropdowns (CC, Collaborators)
    document.querySelectorAll('.mw-multi').forEach(box => {
        const txt = box.querySelector('.mw-multi-text');
        const checks = box.querySelectorAll('input[type=checkbox], input[type=radio]');
        const refresh = () => {
            const names = [...checks].filter(i => i.checked).map(i => i.dataset.name);
            txt.innerText = names.length === 0 ? box.dataset.placeholder : (names.length <= 2 ? names.join(', ') : names.length + ' selected');
        };
        box._refresh = refresh;
        box.querySelector('.mw-multi-btn').addEventListener('click', () => {
            const wasOpen = box.classList.contains('open');
            document.querySelectorAll('.mw-multi.open').forEach(b => b.classList.remove('open'));
            if (!wasOpen) box.classList.add('open');
        });
        checks.forEach(i => i.addEventListener('change', () => {
            if (box.dataset.single === '1' && i.checked) {
                checks.forEach(o => { if (o !== i) o.checked = false; });
                box.classList.remove('open');
            }
            refresh();
            box.dispatchEvent(new Event('change', { bubbles: true }));
        }));
        // Optional single-select (e.g. Reviewer): clicking the ticked radio again clears it
        checks.forEach(i => i.addEventListener('click', () => {
            if (i.type !== 'radio' || box.dataset.clearable !== '1') return;
            if (i.dataset.was === '1') {
                i.checked = false;
                i.dataset.was = '0';
                box.classList.remove('open');
                refresh();
                box.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                checks.forEach(o => o.dataset.was = '0');
                i.dataset.was = '1';
            }
        }));
        refresh();
        box.querySelector('.mw-multi-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            box.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = l.innerText.toLowerCase().includes(q) ? '' : 'none');
        });
    });
    // Capture phase: still fires when the drawer stops click propagation
    document.addEventListener('click', e => {
        document.querySelectorAll('.mw-multi.open').forEach(b => { if (!b.contains(e.target)) b.classList.remove('open'); });
    }, true);

    // Acceptance checklist: add / remove extra rows
    const expectedList = document.getElementById('expectedOutputList');
    const btnAddExpected = document.getElementById('btnAddExpectedOutput');
    if (expectedList && btnAddExpected) {
        btnAddExpected.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'd-flex align-items-start gap-2 mb-2 mw-expected-row';
            row.innerHTML = '<textarea name="description[]" class="mw-form-textarea" rows="1" placeholder="Another checklist item"></textarea>' +
                '<button type="button" class="mw-btn-create" title="Remove" style="white-space:nowrap;background:#C92A2A;"><i class="fa-solid fa-xmark"></i></button>';
            row.querySelector('button').addEventListener('click', () => row.remove());
            expectedList.appendChild(row);
            row.querySelector('textarea').focus();
        });
    }

    // Toast notification helper
    function showMwToast(message, type = 'success') {
        let wrap = document.getElementById('mwToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'mwToastWrap';
            wrap.className = 'mw-toast-wrap';
            document.body.appendChild(wrap);
        }
        const el = document.createElement('div');
        const isErr = type === 'error';
        el.className = 'mw-toast' + (isErr ? ' error' : '');
        const icon = isErr ? 'fa-circle-exclamation' : 'fa-circle-check';
        el.innerHTML = `<i class="fa-solid ${icon} mw-toast-icon"></i><span></span><button type="button" class="mw-toast-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>`;
        el.querySelector('span').textContent = message;
        el.querySelector('.mw-toast-close').addEventListener('click', () => el.remove());
        wrap.appendChild(el);
        setTimeout(() => { if (el.parentNode) el.remove(); }, 4500);
    }

    // AJAX Form Submit
    if (formCreateTask) {
        formCreateTask.addEventListener('submit', function(e) {
            e.preventDefault();
            const ownerVal = mwPicked('taskAssignedToSelect').value || '<?= (int)$userId ?>';
            const reviewerVal = mwPicked('taskReviewerSelect').value;
            if (reviewerVal && ownerVal && reviewerVal === ownerVal &&
                !confirm('Assignee and Reviewer are the same person. Continue?')) {
                return;
            }
            const editTaskId = document.getElementById('drawerEditTaskId')?.value;
            const isEditing = !!(editTaskId && editTaskId.trim() !== '');

            if (btnSubmitDrawer) {
                btnSubmitDrawer.disabled = true;
                btnSubmitDrawer.innerHTML = isEditing
                    ? '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...'
                    : '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...';
            }

            const formData = new FormData(formCreateTask);
            const targetUrl = isEditing
                ? `${baseUrl}/my-work/update-task/${encodeURIComponent(editTaskId)}?ajax=1`
                : `${baseUrl}/my-work/store-task?ajax=1`;

            fetch(targetUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); }
                catch (_) { throw new Error('Invalid server response.'); }
            })
            .then(data => {
                if (data.status === 'success') {
                    closeDrawer();
                    showMwToast(data.message || (isEditing ? 'Task updated successfully.' : 'Task created and assigned successfully.'), 'success');
                    // Instant live refresh of workspace table & counters
                    fetchWorkspaceData();
                } else {
                    showMwToast(data.message || (isEditing ? 'Failed to update task.' : 'Failed to create task.'), 'error');
                }
            })
            .catch(err => {
                console.error(isEditing ? 'Error updating task:' : 'Error creating task:', err);
                showMwToast(err.message || (isEditing ? 'An error occurred while updating the task.' : 'An error occurred while creating the task.'), 'error');
            })
            .finally(() => {
                if (btnSubmitDrawer) {
                    btnSubmitDrawer.disabled = false;
                    btnSubmitDrawer.innerHTML = isEditing
                        ? '<i class="fa-solid fa-check me-1"></i> Update task'
                        : '<i class="fa-solid fa-plus me-1"></i> Create & assign';
                }
            });
        });
    }

    const initialTaskId = parseInt(new URLSearchParams(window.location.search).get('task_id'), 10);
    if (initialTaskId > 0 && typeof window.openTaskDetail === 'function') {
        window.openTaskDetail(initialTaskId);
    }
});
</script>
