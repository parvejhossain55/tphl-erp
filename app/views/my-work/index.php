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
    display: inline-block;
    position: relative;
    cursor: pointer;
    transition: color 0.15s ease;
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

/* Multi-select dropdown */
.mw-multi { position: relative; }
.mw-multi-btn { text-align: left; width: 100%; cursor: pointer; }
.mw-multi-panel { display: none; position: absolute; z-index: 50; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #E2EAE5; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.08); max-height: 240px; overflow-y: auto; padding: 8px; }
.mw-multi.open .mw-multi-panel { display: block; }
.mw-multi-search { margin-bottom: 6px; }
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
    justify-content: space-between;
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
    <div class="mw-tabs-wrap">
        <a class="mw-tab-item <?= $tab === 'all' ? 'active' : '' ?>" 
           data-tab="all" href="javascript:void(0);">All</a>
        <a class="mw-tab-item <?= $tab === 'assigned_to_me' ? 'active' : '' ?>" 
           data-tab="assigned_to_me" href="javascript:void(0);">Assigned to me</a>
        <a class="mw-tab-item <?= $tab === 'assigned_by_me' ? 'active' : '' ?>" 
           data-tab="assigned_by_me" href="javascript:void(0);">Assigned by me</a>
        <a class="mw-tab-item <?= $tab === 'following' ? 'active' : '' ?>" 
           data-tab="following" href="javascript:void(0);">Following</a>
        <a class="mw-tab-item <?= $tab === 'reviewer' ? 'active' : '' ?>" 
           data-tab="reviewer" href="javascript:void(0);">Reviewer</a>
        <a class="mw-tab-item <?= $tab === 'realestate_project' ? 'active' : '' ?>" 
           data-tab="realestate_project" href="javascript:void(0);">Realestate Project</a>
        <a class="mw-tab-item <?= $tab === 'self_initiated' ? 'active' : '' ?>" 
           data-tab="self_initiated" href="javascript:void(0);">Self-Initiated Task</a>
        <a class="mw-tab-item <?= $tab === 'recurring' ? 'active' : '' ?>" 
           data-tab="recurring" href="javascript:void(0);">Recurring Task</a>
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
                <div class="mw-tasks-title-group">
                    <h3 class="mw-tasks-title">My tasks</h3>
                    <span class="mw-badge-open" id="badgeOpenCount"><?= $openTasksCount ?> open</span>
                </div>
                <div class="mw-tasks-completed-text" id="textCompletedCount">
                    <?= $completedTasksCount ?> completed
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

                <!-- Due date Pill Button -->
                <div class="position-relative d-inline-block">
                    <button type="button" class="mw-pill-btn" id="btnDueDate">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Due date</span>
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
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="">All Statuses</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="open">Open only</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="pending">To do</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="in_progress">In progress</a>
                        <a class="mw-popup-item" href="javascript:void(0);" data-status="completed">Completed</a>
                    </div>
                </div>

                <!-- List view Pill Button -->
                <a href="<?= $base_url ?>/task-lists" class="mw-pill-btn" title="Detailed List View">
                    <i class="fa-solid fa-bars-staggered"></i>
                    <span>List view</span>
                </a>

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
                            <th style="width: 150px;">DUE</th>
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
                                if (!$isCompleted && (int)$t['progress'] >= 80) {
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
                                            <input type="checkbox" class="mw-task-check" <?= $isCompleted ? 'checked' : '' ?> disabled>
                                            <div>
                                                <a href="<?= $base_url ?>/show-task/<?= $taskId ?>" class="mw-task-name">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </a>
                                                <?php if ($isBlocked): ?>
                                                    <span class="mw-badge-blocked">Blocked</span>
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
                                            <a href="<?= $base_url ?>/show-task/<?= $taskId ?>" class="mw-action-btn view" title="View task">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="<?= $base_url ?>/edit-task/<?= $taskId ?>" class="mw-action-btn edit" title="Edit task">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <button type="button" class="mw-action-btn delete" onclick="deleteMyWorkTask(<?= $taskId ?>, this)" title="Delete task">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
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
                <a href="<?= $base_url ?>/task-lists" class="mw-btn-viewall">
                    View all
                </a>
            </div>
        </div>

        <!-- Right: Focus Panel -->
        <div>
            <!-- Card 1: Needs your review -->
            <div class="mw-focus-card">
                <h4 class="mw-focus-title">Needs your review</h4>
                <div class="mw-focus-subtitle">
                    <?= count($reviewTasks) ?> task<?= count($reviewTasks) === 1 ? '' : 's' ?> submitted by your team
                </div>

                <?php if (!empty($reviewTasks)): ?>
                    <?php foreach ($reviewTasks as $idx => $rt): ?>
                        <div class="mw-review-item">
                            <div class="mw-review-item-title"><?= htmlspecialchars($rt['title']) ?></div>
                            <div class="mw-review-item-meta">
                                <?= htmlspecialchars($rt['employee_name'] ?? 'Team Member') ?> · Submitted <?= date('j M, g:i A', strtotime($rt['updated_at'] ?? $rt['created_at'])) ?>
                            </div>
                            <?php if ($idx === 0): ?>
                                <a href="<?= $base_url ?>/show-task/<?= (int)$rt['id'] ?>" class="mw-btn-review">
                                    Review submission
                                </a>
                            <?php else: ?>
                                <div class="mw-review-divider"></div>
                                <a href="<?= $base_url ?>/show-task/<?= (int)$rt['id'] ?>" class="mw-review-link-row">
                                    <span class="mw-review-item-title" style="font-size: 12.5px;">View details</span>
                                    <i class="fa-solid fa-arrow-right mw-arrow-icon"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted small py-2">
                        <i class="fa-regular fa-circle-check text-success me-1"></i> No submissions pending your review.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card 2: One task is blocked / Needs Attention -->
            <div class="mw-focus-card">
                <h4 class="mw-focus-title">Task Attention</h4>
                <div class="mw-focus-subtitle">Priority or response needed</div>

                <?php if ($blockedTask): ?>
                    <span class="mw-badge-waiting">
                        <?= !empty($blockedTask['remarks']) ? 'Action Needed' : 'High Priority' ?>
                    </span>

                    <p class="mw-blocked-desc">
                        <?= htmlspecialchars($blockedTask['title']) ?>
                        <?php if (!empty($blockedTask['remarks'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($blockedTask['remarks'], 0, 80, '...')) ?></small>
                        <?php endif; ?>
                    </p>

                    <div class="mw-blocked-action">
                        Next action: <?= htmlspecialchars($blockedTask['assigned_by_name'] ?? 'Team') ?> · <?= !empty($blockedTask['deadline']) ? date('j M', strtotime($blockedTask['deadline'])) : 'Soon' ?>
                    </div>

                    <a href="<?= $base_url ?>/show-task/<?= (int)$blockedTask['id'] ?>" class="mw-btn-opentask">
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
            <h3 class="mw-drawer-title">Create task</h3>
            <button type="button" class="mw-drawer-close" id="btnCloseCreateTask" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p class="mw-drawer-subtitle">Define the output, owner and deadline.</p>
    </div>

    <!-- Status & Required Meta Bar -->
    <div class="mw-drawer-meta-bar">
        <span class="mw-status-badge-todo">Status: To do</span>
        <span class="mw-required-note">Required fields marked <span class="text-danger">*</span></span>
    </div>

    <!-- Form & Body -->
    <form id="mwCreateTaskForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="margin: 0;">
        <div class="mw-drawer-body">
            <!-- Task Title -->
            <div class="mw-form-group">
                <label class="mw-form-label">Task title <span class="req">*</span></label>
                <input type="text" name="title" id="drawerTaskTitle" class="mw-form-input" 
                       placeholder="Client ledger: verify opening balances" required>
            </div>

            <!-- Row 1: Owner & Due Date -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Owner <span class="req">*</span></label>
                    <select name="user_id" id="taskOwnerSelect" class="mw-form-select" required>
                        <?php foreach (($usersList ?? []) as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id'] === (int)$userId) ? 'selected' : '' ?> data-name="<?= htmlspecialchars($u['full_name']) ?>">
                                <?= htmlspecialchars($u['full_name']) ?><?= !empty($u['designation']) ? ' (' . htmlspecialchars($u['designation']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                        <option value="medium" selected>Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
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

            <div class="mw-form-row mw-form-group" id="rowRecurrenceEnd" style="display:none;">
                <div class="mw-form-col">
                    <label class="mw-form-label">Repeat</label>
                    <select name="recurrence_type" id="taskRecurrenceType" class="mw-form-select">
                        <option value="">Select...</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Repeat until</label>
                    <input type="date" name="recurrence_end_date" class="mw-form-input">
                </div>
            </div>


            <!-- Expected Output (multiple allowed) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Expected output <span class="req">*</span></label>
                <div id="expectedOutputList">
                    <div class="d-flex align-items-start gap-2 mb-2 mw-expected-row">
                        <textarea name="description[]" id="drawerDescription" class="mw-form-textarea" rows="1" 
                                  placeholder="Opening balances and verified transactions reconcile for five sample accounts." required></textarea>
                        <button type="button" class="mw-btn-create" id="btnAddExpectedOutput" title="Add another expected output" style="white-space:nowrap;">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <?php
            // Multi-select dropdown (checkbox list) styled like .mw-form-select
            $mwMulti = function (string $name, string $placeholder) use ($usersList) { ?>
                <div class="mw-multi" data-placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <button type="button" class="mw-form-select mw-multi-btn"><span class="mw-multi-text"><?= htmlspecialchars($placeholder) ?></span></button>
                    <div class="mw-multi-panel">
                        <input type="text" class="mw-form-input mw-multi-search" placeholder="Search...">
                        <?php foreach (($usersList ?? []) as $u): if (trim((string)$u['full_name']) === '') continue; ?>
                            <label class="mw-multi-item">
                                <input type="checkbox" name="<?= htmlspecialchars($name) ?>" value="<?= (int)$u['id'] ?>" data-name="<?= htmlspecialchars($u['full_name']) ?>">
                                <span><?= htmlspecialchars($u['full_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php }; ?>
            <!-- Assigned by & CC -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Assigned by</label>
                    <select name="assigned_by_id" id="taskAssignedBySelect" class="mw-form-select">
                        <?php foreach (($usersList ?? []) as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id'] === (int)$userId) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <select name="reviewer_id" id="taskReviewerSelect" class="mw-form-select">
                        <option value="">Select Reviewer (optional)...</option>
                        <?php foreach (($usersList ?? []) as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" data-name="<?= htmlspecialchars($u['full_name']) ?>">
                                <?= htmlspecialchars($u['full_name']) ?><?= !empty($u['designation']) ? ' (' . htmlspecialchars($u['designation']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <button type="button" class="mw-drawer-btn secondary" id="btnSaveDraft">Save draft</button>
                    <button type="button" class="mw-drawer-btn secondary" id="btnCancelDrawer">Cancel</button>
                </div>
                <button type="submit" class="mw-drawer-btn primary" id="btnSubmitDrawer">
                    <i class="fa-solid fa-plus me-1"></i> Create & assign
                </button>
            </div>
            <div class="mw-drawer-foot-note">Drafts can be saved before assignment details are complete.</div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= $base_url ?>';
    
    // State
    let currentTab = '<?= $tab ?>';
    let currentMetric = '<?= $metricFilter ?>';
    let currentPriority = '<?= $priorityFilter ?>';
    let currentStatus = '<?= $statusFilter ?>';
    let searchTimer = null;

    const tbody = document.getElementById('mwTasksTbody');
    const footerInfo = document.getElementById('mwFooterInfo');
    const badgeOpen = document.getElementById('badgeOpenCount');
    const textCompleted = document.getElementById('textCompletedCount');
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
                if (data.counts) {
                    if (badgeOpen) badgeOpen.innerText = `${data.counts.open} open`;
                    if (textCompleted) textCompleted.innerText = `${data.counts.completed} completed`;
                    if (statOverdue) statOverdue.innerText = data.counts.overdue;
                    if (statDueToday) statDueToday.innerText = data.counts.due_today;
                    if (statNext7) statNext7.innerText = data.counts.next7;
                    if (statReview) statReview.innerText = data.counts.waiting_review;
                }

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
            document.querySelectorAll('.mw-stat-card').forEach(el => el.classList.remove('active-metric'));
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

    // 4. Delete Task (Single-Page AJAX)
    window.deleteMyWorkTask = function(taskId, btnEl) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        const row = btnEl ? btnEl.closest('tr') : document.querySelector(`tr[data-task-id="${taskId}"]`);
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
            if (data.status === 'success') {
                if (row) {
                    row.remove();
                }
                // Refresh statistics and data seamlessly
                fetchWorkspaceData();
            } else {
                alert(data.message || 'Failed to delete task.');
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            alert('An error occurred while deleting the task.');
            if (row) {
                row.style.opacity = '1';
                row.style.pointerEvents = 'auto';
            }
        });
    };

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
    const btnSaveDraft = document.getElementById('btnSaveDraft');

    function openDrawer() {
        if (drawer && backdrop) {
            drawer.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
            const titleInput = document.getElementById('drawerTaskTitle');
            if (titleInput) setTimeout(() => titleInput.focus(), 320);
        }
    }

    function closeDrawer() {
        if (drawer && backdrop) {
            drawer.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (btnOpenCreateTask) btnOpenCreateTask.addEventListener('click', openDrawer);
    if (btnCloseCreateTask) btnCloseCreateTask.addEventListener('click', closeDrawer);
    if (btnCancelDrawer) btnCancelDrawer.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });

    // Dynamic Notice Box Text
    const taskOwnerSelect = document.getElementById('taskOwnerSelect');
    const taskReviewerSelect = document.getElementById('taskReviewerSelect');
    const noticeOwnerText = document.getElementById('noticeOwnerText');
    const noticeReviewerText = document.getElementById('noticeReviewerText');

    function updateNoticeBox() {
        if (taskOwnerSelect && noticeOwnerText) {
            const opt = taskOwnerSelect.options[taskOwnerSelect.selectedIndex];
            const fullName = opt ? (opt.getAttribute('data-name') || opt.text) : 'Owner';
            const firstName = fullName.trim().split(' ')[0];
            noticeOwnerText.innerText = `${firstName} will receive the assignment.`;
        }
        if (taskReviewerSelect && noticeReviewerText) {
            if (taskReviewerSelect.value) {
                const optR = taskReviewerSelect.options[taskReviewerSelect.selectedIndex];
                const fullNameR = optR ? (optR.getAttribute('data-name') || optR.text) : 'Reviewer';
                const firstNameR = fullNameR.trim().split(' ')[0];
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
            rowRecurrenceEnd.style.display = chkIsRecurring.checked ? 'flex' : 'none';
            const selRec = document.getElementById('taskRecurrenceType');
            if (selRec) selRec.required = chkIsRecurring.checked;
        });
    }

    // Multi-select dropdowns (CC, Collaborators)
    document.querySelectorAll('.mw-multi').forEach(box => {
        const txt = box.querySelector('.mw-multi-text');
        const checks = box.querySelectorAll('input[type=checkbox]');
        const refresh = () => {
            const names = [...checks].filter(i => i.checked).map(i => i.dataset.name);
            txt.innerText = names.length === 0 ? box.dataset.placeholder : (names.length <= 2 ? names.join(', ') : names.length + ' selected');
        };
        box.querySelector('.mw-multi-btn').addEventListener('click', () => {
            const wasOpen = box.classList.contains('open');
            document.querySelectorAll('.mw-multi.open').forEach(b => b.classList.remove('open'));
            if (!wasOpen) box.classList.add('open');
        });
        checks.forEach(i => i.addEventListener('change', refresh));
        box.querySelector('.mw-multi-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            box.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = l.innerText.toLowerCase().includes(q) ? '' : 'none');
        });
    });
    // Capture phase: still fires when the drawer stops click propagation
    document.addEventListener('click', e => {
        document.querySelectorAll('.mw-multi.open').forEach(b => { if (!b.contains(e.target)) b.classList.remove('open'); });
    }, true);

    // Expected output: add / remove extra rows
    const expectedList = document.getElementById('expectedOutputList');
    const btnAddExpected = document.getElementById('btnAddExpectedOutput');
    if (expectedList && btnAddExpected) {
        btnAddExpected.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'd-flex align-items-start gap-2 mb-2 mw-expected-row';
            row.innerHTML = '<textarea name="description[]" class="mw-form-textarea" rows="3" placeholder="Another expected output" required></textarea>' +
                '<button type="button" class="mw-btn-create" title="Remove" style="white-space:nowrap;background:#C92A2A;"><i class="fa-solid fa-xmark"></i></button>';
            row.querySelector('button').addEventListener('click', () => row.remove());
            expectedList.appendChild(row);
            row.querySelector('textarea').focus();
        });
    }

    // AJAX Form Submit
    if (formCreateTask) {
        formCreateTask.addEventListener('submit', function(e) {
            e.preventDefault();
            const ownerVal = document.getElementById('taskOwnerSelect')?.value;
            const reviewerVal = document.getElementById('taskReviewerSelect')?.value;
            if (reviewerVal && ownerVal && reviewerVal === ownerVal &&
                !confirm('Owner and Reviewer are the same person. Continue?')) {
                return;
            }
            if (btnSubmitDrawer) {
                btnSubmitDrawer.disabled = true;
                btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...';
            }

            const formData = new FormData(formCreateTask);

            fetch(`${baseUrl}/my-work/store-task?ajax=1`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeDrawer();
                    formCreateTask.reset();
                    updateNoticeBox();
                    // Instant live refresh of workspace table & counters
                    fetchWorkspaceData();
                } else {
                    alert(data.message || 'Failed to create task.');
                }
            })
            .catch(err => {
                console.error('Error creating task:', err);
                alert('An error occurred while creating the task.');
            })
            .finally(() => {
                if (btnSubmitDrawer) {
                    btnSubmitDrawer.disabled = false;
                    btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Create & assign';
                }
            });
        });
    }

    // Save Draft Button
    if (btnSaveDraft && formCreateTask) {
        btnSaveDraft.addEventListener('click', function() {
            const titleInput = document.getElementById('drawerTaskTitle');
            if (!titleInput || !titleInput.value.trim()) {
                alert('Please enter a Task title to save draft.');
                if (titleInput) titleInput.focus();
                return;
            }
            formCreateTask.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        });
    }
});
</script>
