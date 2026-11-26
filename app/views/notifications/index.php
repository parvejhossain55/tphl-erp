<?php
$baseUrl       = $GLOBALS['base_url'] ?? '';
$notifications = $notifications ?? [];
$counts        = $counts ?? ['all' => 0, 'unread' => 0, 'assigned' => 0, 'progress' => 0, 'completed' => 0, 'rated' => 0, 'comment' => 0, 'edited' => 0];
$filters       = $filters ?? ['status' => 'all', 'category' => 'all', 'search' => '', 'date_from' => '', 'date_to' => ''];
$page          = (int)($page ?? 1);
$totalPages    = (int)($total_pages ?? 1);
$total         = (int)($total ?? 0);

$currentStatus   = strtolower((string)($filters['status'] ?? 'all'));
$currentCategory = strtolower((string)($filters['category'] ?? 'all'));
$currentSearch   = (string)($filters['search'] ?? '');
$currentFrom     = (string)($filters['date_from'] ?? '');
$currentTo       = (string)($filters['date_to'] ?? '');

$actionMeta = [
    'task_assigned'      => ['label' => 'Task Assigned',      'icon' => 'fa-user-plus',       'bg' => '#ecfdf5', 'color' => '#006837'],
    'task_reassigned'    => ['label' => 'Reassigned',         'icon' => 'fa-people-arrows',   'bg' => '#ecfdf5', 'color' => '#087F5B'],
    'collaborator_added' => ['label' => 'Collaborator',       'icon' => 'fa-users',           'bg' => '#ecfdf5', 'color' => '#006837'],
    'task_mentioned'     => ['label' => 'Mentioned',          'icon' => 'fa-at',              'bg' => '#ecfdf5', 'color' => '#087F5B'],
    'task_accepted'      => ['label' => 'Accepted',           'icon' => 'fa-handshake',       'bg' => '#ecfdf5', 'color' => '#006837'],
    'status_changed'     => ['label' => 'Status Updated',     'icon' => 'fa-arrows-rotate',   'bg' => '#f0fdf4', 'color' => '#087F5B'],
    'progress_updated'   => ['label' => 'Progress Updated',   'icon' => 'fa-chart-line',      'bg' => '#ecfdf5', 'color' => '#006837'],
    'subtask_updated'    => ['label' => 'Subtask Updated',    'icon' => 'fa-list-check',      'bg' => '#f0fdf4', 'color' => '#087F5B'],
    'task_completed'     => ['label' => 'Completed',          'icon' => 'fa-circle-check',    'bg' => '#dcfce7', 'color' => '#006837'],
    'task_rated'         => ['label' => 'Rated',              'icon' => 'fa-star',            'bg' => '#ecfdf5', 'color' => '#006837'],
    'task_comment'       => ['label' => 'Comment',            'icon' => 'fa-comment-dots',    'bg' => '#f0fdf4', 'color' => '#087F5B'],
    'task_edited'        => ['label' => 'Task Edited',        'icon' => 'fa-pen-to-square',   'bg' => '#ecfdf5', 'color' => '#006837'],
    'task_deleted'       => ['label' => 'Task Deleted',       'icon' => 'fa-trash-can',       'bg' => '#fef2f2', 'color' => '#dc2626'],
];

$formatTimeAgo = function (?string $datetime): string {
    if (empty($datetime)) return '';
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return date('d M, Y h:i A', $ts);
};

$buildQueryUrl = function (array $overrides = []) use ($baseUrl, $filters): string {
    $q = array_merge([
        'status'    => $filters['status'] ?? 'all',
        'category'  => $filters['category'] ?? 'all',
        'search'    => $filters['search'] ?? '',
        'date_from' => $filters['date_from'] ?? '',
        'date_to'   => $filters['date_to'] ?? '',
    ], $overrides);
    $q = array_filter($q, static fn($v) => $v !== '' && $v !== null && $v !== 'all');
    return $baseUrl . '/notifications' . (!empty($q) ? '?' . http_build_query($q) : '');
};
?>

<style>
    .notif-page-header {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 16px;
    }
    .notif-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 13px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        text-decoration: none;
        transition: all .15s ease;
    }
    .notif-pill:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .notif-pill.active {
        background: #006837;
        color: #fff;
        border-color: #006837;
    }
    .notif-pill .pill-cnt {
        font-size: 11px;
        padding: 1px 7px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
    }
    .notif-pill.active .pill-cnt {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }
    .notif-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
    }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s ease;
    }
    .notif-item:last-child {
        border-bottom: none;
    }
    .notif-item.is-clickable {
        cursor: pointer;
    }
    .notif-item:hover {
        background: #f8fafc;
    }
    .notif-item.is-unread {
        background: #f0fdf4;
    }
    .notif-item.is-unread:hover {
        background: #e6f7ed;
    }
    .notif-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
    }
    .notif-unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #006837;
        display: inline-block;
        flex-shrink: 0;
    }
    .notif-open-link {
        color: #006837 !important;
    }
    .notif-open-link:hover {
        color: #087F5B !important;
    }
    .notif-btn-brand {
        background: #006837;
        border-color: #006837;
        color: #fff;
    }
    .notif-btn-brand:hover {
        background: #087F5B;
        border-color: #087F5B;
        color: #fff;
    }
    .notif-btn-brand-outline {
        color: #006837;
        border-color: #006837;
        background: #fff;
    }
    .notif-btn-brand-outline:hover:not(:disabled) {
        background: #006837;
        border-color: #006837;
        color: #fff;
    }
    #notifPaginationContainer .page-item.active .page-link {
        background-color: #006837;
        border-color: #006837;
        color: #fff;
    }
    #notifPaginationContainer .page-link {
        color: #006837;
    }
    .notif-action-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<div class="app__slide-wrapper">
    <!-- Top Header -->
    <div class="notif-page-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-bell text-warning"></i>
                    Notifications
                    <span id="notifPageUnreadBadge" class="badge bg-danger rounded-pill" style="font-size: 12px; <?= $counts['unread'] > 0 ? '' : 'display:none;' ?>">
                        <?= (int)$counts['unread'] ?> Unread
                    </span>
                </h4>
                <p class="text-muted mb-0" style="font-size: 13px;">
                    Track all task assignments, status updates, progress changes, comments, and ratings in one place.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btnMarkAllNotificationsRead" class="btn notif-btn-brand-outline btn-sm fw-semibold" <?= $counts['unread'] > 0 ? '' : 'disabled' ?>>
                    <i class="fa-solid fa-check-double me-1"></i> Mark All as Read
                </button>
            </div>
        </div>

        <!-- Category / Status Pills -->
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top" id="notifFilterPills">
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'all', 'page' => null])) ?>"
               data-status="all" data-category="all"
               class="notif-pill <?= ($currentStatus === 'all' && $currentCategory === 'all') ? 'active' : '' ?>">
                All <span class="pill-cnt" data-count-key="all"><?= (int)$counts['all'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'unread', 'category' => 'all', 'page' => null])) ?>"
               data-status="unread" data-category="all"
               class="notif-pill <?= ($currentStatus === 'unread' && $currentCategory === 'all') ? 'active' : '' ?>">
                Unread <span class="pill-cnt" id="pillUnreadCount" data-count-key="unread"><?= (int)$counts['unread'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'assigned', 'page' => null])) ?>"
               data-status="all" data-category="assigned"
               class="notif-pill <?= $currentCategory === 'assigned' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-plus"></i> Assigned <span class="pill-cnt" data-count-key="assigned"><?= (int)$counts['assigned'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'progress', 'page' => null])) ?>"
               data-status="all" data-category="progress"
               class="notif-pill <?= $currentCategory === 'progress' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> Progress &amp; Status <span class="pill-cnt" data-count-key="progress"><?= (int)$counts['progress'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'completed', 'page' => null])) ?>"
               data-status="all" data-category="completed"
               class="notif-pill <?= $currentCategory === 'completed' ? 'active' : '' ?>">
                <i class="fa-solid fa-circle-check"></i> Completed <span class="pill-cnt" data-count-key="completed"><?= (int)$counts['completed'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'rated', 'page' => null])) ?>"
               data-status="all" data-category="rated"
               class="notif-pill <?= $currentCategory === 'rated' ? 'active' : '' ?>">
                <i class="fa-solid fa-star"></i> Rated <span class="pill-cnt" data-count-key="rated"><?= (int)$counts['rated'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'comment', 'page' => null])) ?>"
               data-status="all" data-category="comment"
               class="notif-pill <?= $currentCategory === 'comment' ? 'active' : '' ?>">
                <i class="fa-solid fa-comment-dots"></i> Comments &amp; Mentions <span class="pill-cnt" data-count-key="comment"><?= (int)$counts['comment'] ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildQueryUrl(['status' => 'all', 'category' => 'edited', 'page' => null])) ?>"
               data-status="all" data-category="edited"
               class="notif-pill <?= $currentCategory === 'edited' ? 'active' : '' ?>">
                <i class="fa-solid fa-pen-to-square"></i> Edited / Deleted <span class="pill-cnt" data-count-key="edited"><?= (int)$counts['edited'] ?></span>
            </a>
        </div>

        <!-- Search & Date Filter -->
        <form id="notifFilterForm" method="GET" action="<?= htmlspecialchars($baseUrl) ?>/notifications" class="row g-2 align-items-end mt-3">
            <div class="col-md-5">
                <label class="form-label mb-1 text-muted" style="font-size: 12px;">Search</label>
                <input type="text" id="notifSearchInput" name="search" class="form-control form-control-sm" placeholder="Search task title, message, or person..." value="<?= htmlspecialchars($currentSearch) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 text-muted" style="font-size: 12px;">From Date</label>
                <input type="date" id="notifDateFrom" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($currentFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 text-muted" style="font-size: 12px;">To Date</label>
                <input type="date" id="notifDateTo" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($currentTo) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn notif-btn-brand btn-sm px-3">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <button type="button" id="btnNotifReset" class="btn btn-outline-secondary btn-sm px-3">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Notification List -->
    <div class="notif-card" id="notifListContainer">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 px-3">
                <i class="fa-regular fa-bell-slash text-muted mb-3" style="font-size: 40px;"></i>
                <h6 class="fw-semibold text-dark mb-1">No notifications found</h6>
                <p class="text-muted mb-0" style="font-size: 13px;">You are all caught up for the selected filter.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $item): ?>
                <?php
                $id         = (int)($item['id'] ?? 0);
                $actKey     = strtolower((string)($item['action_type'] ?? ''));
                $isDeleted  = ($actKey === 'task_deleted') || (isset($item['task_exists']) && (int)$item['task_exists'] === 0);
                $taskId     = 0;
                if (!$isDeleted) {
                    $taskId = (int)($item['task_id'] ?? 0);
                    if ($taskId <= 0 && preg_match('/\bT-(\d+)\b/i', (string)($item['message'] ?? ''), $tm)) {
                        $taskId = (int)$tm[1];
                    }
                }
                $isUnread   = empty($item['is_read']);
                $meta       = $actionMeta[$actKey] ?? ['label' => 'Task Update', 'icon' => 'fa-bell', 'bg' => '#f1f5f9', 'color' => '#475569'];
                $actorName  = !empty($item['actor_name']) ? $item['actor_name'] : 'System';
                $rawUrl     = $isDeleted ? '' : trim((string)($item['target_url'] ?? ''));
                $targetHref = '';
                if ($rawUrl !== '') {
                    if (preg_match('#^https?://#i', $rawUrl) || ($baseUrl !== '' && str_starts_with($rawUrl, $baseUrl))) {
                        $targetHref = $rawUrl;
                    } else {
                        $targetHref = $baseUrl . '/' . ltrim($rawUrl, '/');
                    }
                }
                $createdAt  = (string)($item['created_at'] ?? '');
                $timeAgo    = $formatTimeAgo($createdAt);
                $exactTime  = $createdAt ? date('d M, Y h:i A', strtotime($createdAt)) : '';
                ?>
                <div class="notif-item <?= $isUnread ? 'is-unread' : '' ?> <?= (!$isDeleted && $taskId > 0) ? 'is-clickable' : '' ?>"
                     data-notif-id="<?= $id ?>"
                     data-task-id="<?= $isDeleted ? 0 : $taskId ?>">
                    <div class="notif-icon-box" style="background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>;">
                        <i class="fa-solid <?= $meta['icon'] ?>"></i>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <?php if ($isUnread): ?>
                                <span class="notif-unread-dot" title="Unread"></span>
                            <?php endif; ?>
                            <span class="notif-action-badge" style="background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>;">
                                <?= htmlspecialchars($meta['label']) ?>
                            </span>
                            <span class="text-muted" style="font-size: 12px;">
                                by <strong class="text-dark"><?= htmlspecialchars($actorName) ?></strong>
                            </span>
                            <span class="text-muted ms-auto" style="font-size: 12px;" title="<?= htmlspecialchars($exactTime) ?>">
                                <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($timeAgo) ?>
                            </span>
                        </div>

                        <div class="text-dark" style="font-size: 13.5px; line-height: 1.45;">
                            <?= htmlspecialchars((string)($item['message'] ?? '')) ?>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                            <?php if ($taskId > 0): ?>
                                <a href="<?= htmlspecialchars($baseUrl . '/my-work-dashboard?task_id=' . $taskId) ?>"
                                   class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold notif-open-link"
                                   data-notif-id="<?= $id ?>"
                                   style="font-size: 12.5px;">
                                    View Task Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            <?php elseif ($targetHref !== ''): ?>
                                <a href="<?= htmlspecialchars($targetHref) ?>"
                                   class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold notif-open-link"
                                   data-notif-id="<?= $id ?>"
                                   style="font-size: 12.5px;">
                                    Open <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($isUnread): ?>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 text-muted text-decoration-none notif-mark-read-btn"
                                        data-notif-id="<?= $id ?>"
                                        style="font-size: 12px;">
                                    <i class="fa-solid fa-check me-1"></i>Mark as read
                                </button>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-check-double text-success me-1"></i>Read
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div id="notifPaginationContainer">
    <?php if ($totalPages > 1): ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
            <div class="text-muted" style="font-size: 12.5px;">
                Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= $total ?> total notifications)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link notif-page-link" data-page="<?= max(1, $page - 1) ?>" href="<?= htmlspecialchars($buildQueryUrl(['page' => max(1, $page - 1)])) ?>">Previous</a>
                    </li>
                    <?php
                    $startP = max(1, $page - 2);
                    $endP   = min($totalPages, $page + 2);
                    for ($p = $startP; $p <= $endP; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link notif-page-link" data-page="<?= $p ?>" href="<?= htmlspecialchars($buildQueryUrl(['page' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link notif-page-link" data-page="<?= min($totalPages, $page + 1) ?>" href="<?= htmlspecialchars($buildQueryUrl(['page' => min($totalPages, $page + 1)])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const baseUrl = <?= json_encode($baseUrl) ?>;
    const actionMeta = <?= json_encode($actionMeta) ?>;
    const state = {
        status: <?= json_encode($currentStatus) ?>,
        category: <?= json_encode($currentCategory) ?>,
        search: <?= json_encode($currentSearch) ?>,
        date_from: <?= json_encode($currentFrom) ?>,
        date_to: <?= json_encode($currentTo) ?>,
        page: <?= (int)$page ?>
    };

    const listEl = document.getElementById('notifListContainer');
    const pagEl = document.getElementById('notifPaginationContainer');
    const pillsWrap = document.getElementById('notifFilterPills');
    const filterForm = document.getElementById('notifFilterForm');
    const searchInput = document.getElementById('notifSearchInput');
    const dateFromInput = document.getElementById('notifDateFrom');
    const dateToInput = document.getElementById('notifDateTo');
    const resetBtn = document.getElementById('btnNotifReset');

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function formatTimeAgo(datetime) {
        if (!datetime) return '';
        const ts = Date.parse(datetime.replace(' ', 'T'));
        if (isNaN(ts)) return datetime;
        const diff = Math.floor((Date.now() - ts) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
        if (diff < 604800) {
            const d = Math.floor(diff / 86400);
            return d + ' day' + (d > 1 ? 's' : '') + ' ago';
        }
        return datetime;
    }

    function resolveTargetUrl(rawUrl) {
        const u = String(rawUrl || '').trim();
        if (!u) return '';
        if (/^https?:\/\//i.test(u) || (baseUrl && u.startsWith(baseUrl))) return u;
        return baseUrl + '/' + u.replace(/^\/+/, '');
    }

    function updateUnreadBadges(count) {
        const c = Math.max(0, parseInt(count || 0, 10));
        const pageBadge = document.getElementById('notifPageUnreadBadge');
        if (pageBadge) {
            pageBadge.textContent = c + ' Unread';
            pageBadge.style.display = c > 0 ? '' : 'none';
        }
        const pillBadge = document.getElementById('pillUnreadCount');
        if (pillBadge) pillBadge.textContent = c;
        ['topbarNotificationBadge', 'sidebarNotificationBadge'].forEach(id => {
            const b = document.getElementById(id);
            if (b) {
                b.textContent = c;
                b.style.display = c > 0 ? '' : 'none';
            }
        });
        const markAllBtn = document.getElementById('btnMarkAllNotificationsRead');
        if (markAllBtn) markAllBtn.disabled = c <= 0;
    }

    function updateSummaryCounts(counts) {
        if (!counts) return;
        document.querySelectorAll('[data-count-key]').forEach(el => {
            const key = el.dataset.countKey;
            if (counts[key] !== undefined) el.textContent = counts[key];
        });
        updateUnreadBadges(counts.unread || 0);
    }

    function syncActivePill() {
        if (!pillsWrap) return;
        pillsWrap.querySelectorAll('.notif-pill').forEach(p => {
            const isMatch = (p.dataset.status === state.status) && (p.dataset.category === state.category);
            p.classList.toggle('active', isMatch);
        });
    }

    function renderItems(items) {
        if (!listEl) return;
        if (!items || !items.length) {
            listEl.innerHTML =
                '<div class="text-center py-5 px-3">' +
                    '<i class="fa-regular fa-bell-slash text-muted mb-3" style="font-size: 40px;"></i>' +
                    '<h6 class="fw-semibold text-dark mb-1">No notifications found</h6>' +
                    '<p class="text-muted mb-0" style="font-size: 13px;">You are all caught up for the selected filter.</p>' +
                '</div>';
            return;
        }

        listEl.innerHTML = items.map(item => {
            const id = parseInt(item.id || 0, 10);
            const actKey = String(item.action_type || '').toLowerCase();
            const isDeleted = (actKey === 'task_deleted') || (item.task_exists !== undefined && parseInt(item.task_exists, 10) === 0);
            let taskId = 0;
            if (!isDeleted) {
                taskId = parseInt(item.task_id || 0, 10);
                if (!taskId) {
                    const m = String(item.message || '').match(/\bT-(\d+)\b/i);
                    if (m) taskId = parseInt(m[1], 10);
                }
            }
            const isUnread = !parseInt(item.is_read || 0, 10);
            const meta = actionMeta[actKey] || { label: 'Task Update', icon: 'fa-bell', bg: '#f1f5f9', color: '#475569' };
            const actorName = item.actor_name || 'System';
            const href = isDeleted ? '' : resolveTargetUrl(item.target_url);
            const timeAgo = formatTimeAgo(item.created_at || '');

            return (
                '<div class="notif-item' + (isUnread ? ' is-unread' : '') + (!isDeleted && taskId > 0 ? ' is-clickable' : '') + '" data-notif-id="' + id + '" data-task-id="' + (isDeleted ? 0 : taskId) + '">' +
                    '<div class="notif-icon-box" style="background:' + meta.bg + ';color:' + meta.color + ';">' +
                        '<i class="fa-solid ' + meta.icon + '"></i>' +
                    '</div>' +
                    '<div class="flex-grow-1 min-w-0">' +
                        '<div class="d-flex flex-wrap align-items-center gap-2 mb-1">' +
                            (isUnread ? '<span class="notif-unread-dot" title="Unread"></span>' : '') +
                            '<span class="notif-action-badge" style="background:' + meta.bg + ';color:' + meta.color + ';">' + esc(meta.label) + '</span>' +
                            '<span class="text-muted" style="font-size:12px;">by <strong class="text-dark">' + esc(actorName) + '</strong></span>' +
                            '<span class="text-muted ms-auto" style="font-size:12px;" title="' + esc(item.created_at || '') + '">' +
                                '<i class="fa-regular fa-clock me-1"></i>' + esc(timeAgo) +
                            '</span>' +
                        '</div>' +
                        '<div class="text-dark" style="font-size:13.5px;line-height:1.45;">' + esc(item.message || '') + '</div>' +
                        '<div class="d-flex flex-wrap align-items-center gap-3 mt-2">' +
                            (taskId > 0
                                ? '<a href="' + esc(baseUrl + '/my-work-dashboard?task_id=' + taskId) + '" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold notif-open-link" data-notif-id="' + id + '" style="font-size:12.5px;">View Task Details <i class="fa-solid fa-arrow-right ms-1"></i></a>'
                                : (href ? '<a href="' + esc(href) + '" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold notif-open-link" data-notif-id="' + id + '" style="font-size:12.5px;">Open <i class="fa-solid fa-arrow-right ms-1"></i></a>' : '')) +
                            (isUnread
                                ? '<button type="button" class="btn btn-sm btn-link p-0 text-muted text-decoration-none notif-mark-read-btn" data-notif-id="' + id + '" style="font-size:12px;"><i class="fa-solid fa-check me-1"></i>Mark as read</button>'
                                : '<span class="text-muted" style="font-size:11.5px;"><i class="fa-solid fa-check-double text-success me-1"></i>Read</span>') +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        }).join('');
    }

    function renderPagination(page, totalPages, total) {
        if (!pagEl) return;
        if (totalPages <= 1) {
            pagEl.innerHTML = '';
            return;
        }
        const startP = Math.max(1, page - 2);
        const endP = Math.min(totalPages, page + 2);
        let pagesHtml = '';
        for (let p = startP; p <= endP; p++) {
            pagesHtml += '<li class="page-item' + (p === page ? ' active' : '') + '"><a class="page-link notif-page-link" data-page="' + p + '" href="#">' + p + '</a></li>';
        }
        pagEl.innerHTML =
            '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">' +
                '<div class="text-muted" style="font-size:12.5px;">Showing page <strong>' + page + '</strong> of <strong>' + totalPages + '</strong> (' + total + ' total notifications)</div>' +
                '<nav><ul class="pagination pagination-sm mb-0">' +
                    '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '"><a class="page-link notif-page-link" data-page="' + Math.max(1, page - 1) + '" href="#">Previous</a></li>' +
                    pagesHtml +
                    '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '"><a class="page-link notif-page-link" data-page="' + Math.min(totalPages, page + 1) + '" href="#">Next</a></li>' +
                '</ul></nav>' +
            '</div>';
    }

    function fetchNotifications() {
        const params = new URLSearchParams();
        if (state.status && state.status !== 'all') params.set('status', state.status);
        if (state.category && state.category !== 'all') params.set('category', state.category);
        if (state.search) params.set('search', state.search);
        if (state.date_from) params.set('date_from', state.date_from);
        if (state.date_to) params.set('date_to', state.date_to);
        if (state.page > 1) params.set('page', String(state.page));

        const cleanQuery = params.toString();
        const browserUrl = baseUrl + '/notifications' + (cleanQuery ? '?' + cleanQuery : '');
        window.history.replaceState({}, '', browserUrl);

        params.set('ajax', '1');
        if (listEl) listEl.style.opacity = '0.55';
        syncActivePill();

        fetch(baseUrl + '/notifications?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.status === 'success') {
                renderItems(data.items || []);
                renderPagination(data.page || 1, data.total_pages || 1, data.total || 0);
                updateSummaryCounts(data.counts);
            }
        })
        .catch(() => {})
        .finally(() => {
            if (listEl) listEl.style.opacity = '1';
        });
    }

    function markRowReadUI(row) {
        if (!row) return;
        row.classList.remove('is-unread');
        const dot = row.querySelector('.notif-unread-dot');
        if (dot) dot.remove();
        const btn = row.querySelector('.notif-mark-read-btn');
        if (btn) {
            const span = document.createElement('span');
            span.className = 'text-muted';
            span.style.fontSize = '11.5px';
            span.innerHTML = '<i class="fa-solid fa-check-double text-success me-1"></i>Read';
            btn.replaceWith(span);
        }
    }

    function sendMarkRead(id, keepalive) {
        const fd = new FormData();
        fd.append('id', String(id));
        return fetch(baseUrl + '/notifications/mark-read', {
            method: 'POST',
            body: fd,
            keepalive: !!keepalive
        }).then(r => r.json());
    }

    // Filter pills click (no reload)
    if (pillsWrap) {
        pillsWrap.addEventListener('click', function(e) {
            const pill = e.target.closest('.notif-pill');
            if (!pill) return;
            e.preventDefault();
            state.status = pill.dataset.status || 'all';
            state.category = pill.dataset.category || 'all';
            state.page = 1;
            fetchNotifications();
        });
    }

    // Filter form submit (no reload)
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            state.search = searchInput ? searchInput.value.trim() : '';
            state.date_from = dateFromInput ? dateFromInput.value : '';
            state.date_to = dateToInput ? dateToInput.value : '';
            state.page = 1;
            fetchNotifications();
        });
    }

    // Debounced live search
    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.search = searchInput.value.trim();
                state.page = 1;
                fetchNotifications();
            }, 300);
        });
    }

    // Date change auto-filter
    [dateFromInput, dateToInput].forEach(inp => {
        if (!inp) return;
        inp.addEventListener('change', function() {
            state.date_from = dateFromInput ? dateFromInput.value : '';
            state.date_to = dateToInput ? dateToInput.value : '';
            state.page = 1;
            fetchNotifications();
        });
    });

    // Reset button (no reload)
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            state.status = 'all';
            state.category = 'all';
            state.search = '';
            state.date_from = '';
            state.date_to = '';
            state.page = 1;
            if (searchInput) searchInput.value = '';
            if (dateFromInput) dateFromInput.value = '';
            if (dateToInput) dateToInput.value = '';
            fetchNotifications();
        });
    }

    // Pagination clicks (no reload)
    if (pagEl) {
        pagEl.addEventListener('click', function(e) {
            const link = e.target.closest('.notif-page-link');
            if (!link) return;
            e.preventDefault();
            const li = link.closest('.page-item');
            if (li && (li.classList.contains('disabled') || li.classList.contains('active'))) return;
            const p = parseInt(link.dataset.page || '1', 10);
            if (p > 0) {
                state.page = p;
                fetchNotifications();
            }
        });
    }

    // Delegated Mark as Read & Row/Link Click -> Navigate directly to My Work Task Details
    if (listEl) {
        listEl.addEventListener('click', function(e) {
            const markBtn = e.target.closest('.notif-mark-read-btn');
            if (markBtn) {
                e.stopPropagation();
                const id = markBtn.dataset.notifId;
                const row = markBtn.closest('.notif-item');
                sendMarkRead(id, false).then(data => {
                    if (data && data.status === 'success') {
                        markRowReadUI(row);
                        updateUnreadBadges(data.unread_count);
                    }
                }).catch(() => {});
                return;
            }

            const openLink = e.target.closest('.notif-open-link');
            if (openLink) {
                const id = openLink.dataset.notifId;
                const row = openLink.closest('.notif-item');
                if (row && row.classList.contains('is-unread') && id) {
                    sendMarkRead(id, true).catch(() => {});
                    markRowReadUI(row);
                }
                return;
            }

            const row = e.target.closest('.notif-item');
            if (!row) return;
            const taskId = parseInt(row.dataset.taskId || '0', 10);
            if (taskId > 0) {
                const notifId = parseInt(row.dataset.notifId || '0', 10);
                if (row.classList.contains('is-unread') && notifId > 0) {
                    sendMarkRead(notifId, true).catch(() => {});
                    markRowReadUI(row);
                }
                window.location.href = baseUrl + '/my-work-dashboard?task_id=' + encodeURIComponent(taskId);
            }
        });
    }

    // Mark All as Read
    const markAllBtn = document.getElementById('btnMarkAllNotificationsRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            markAllBtn.disabled = true;
            fetch(baseUrl + '/notifications/mark-all-read', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data && data.status === 'success') {
                        document.querySelectorAll('.notif-item.is-unread').forEach(markRowReadUI);
                        updateUnreadBadges(0);
                    }
                })
                .catch(() => { markAllBtn.disabled = false; });
        });
    }
})();
</script>
