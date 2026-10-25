<?php
$page_title = 'HR ATS - Candidate Tracker';
$baseUrl = $GLOBALS['base_url'];

$currentSortBy = $sortBy ?? '';
$currentSortOrder = strtolower($sortOrder ?? 'desc');

$renderSortHeader = function($columnKey, $label, $minWidth = '', $align = 'start') use ($currentSortBy, $currentSortOrder, $baseUrl) {
    $isActive = ($currentSortBy === $columnKey);
    $nextOrder = ($isActive && $currentSortOrder === 'asc') ? 'desc' : 'asc';

    $params = $_GET;
    $params['sort_by'] = $columnKey;
    $params['sort_order'] = $nextOrder;
    $params['page'] = 1;
    $url = $baseUrl . '/ats-tracker?' . http_build_query($params);

    if ($isActive) {
        $icon = ($currentSortOrder === 'asc')
            ? '<i class="fa-solid fa-arrow-up-short-wide text-primary ms-1"></i>'
            : '<i class="fa-solid fa-arrow-down-wide-short text-primary ms-1"></i>';
        $textClass = 'text-primary fw-bold';
    } else {
        $icon = '<i class="fa-solid fa-sort text-muted ms-1 opacity-50" style="font-size: 0.72rem;"></i>';
        $textClass = 'text-secondary';
    }

    $style = $minWidth ? "min-width: {$minWidth}; cursor: pointer; user-select: none;" : "cursor: pointer; user-select: none;";
    
    return "<th style=\"{$style}\" class=\"text-{$align}\" onclick=\"window.location.href='{$url}'\" title=\"Click to sort by {$label}\">"
        . "<div class=\"d-inline-flex align-items-center gap-1 {$textClass}\">"
        . "<span>{$label}</span>"
        . $icon
        . "</div></th>";
};

$renderPaginationBar = function($position = 'bottom') use ($total, $currentPage, $perPage, $rawPerPage, $totalPages, $baseUrl) {
    ob_start();
    $containerClass = ($position === 'top') 
        ? "card-header bg-white border-bottom py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2"
        : "card-footer bg-white border-top py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2";
    ?>
    <div class="<?= $containerClass ?>">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <!-- Showing X to Y of Total -->
            <span class="small text-muted">
                <?php if ($total > 0): ?>
                    Showing <span class="fw-semibold text-dark"><?= ($rawPerPage === 'all') ? 1 : min(($currentPage - 1) * $perPage + 1, $total) ?></span> to 
                    <span class="fw-semibold text-dark"><?= ($rawPerPage === 'all') ? $total : min($currentPage * $perPage, $total) ?></span> of 
                    <span class="fw-semibold text-dark"><?= number_format($total) ?></span> candidates
                <?php else: ?>
                    Showing 0 candidates
                <?php endif; ?>
            </span>

            <!-- View Count (Show: 10, 20, 50, 100, All) -->
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0">Show:</label>
                <select class="form-select form-select-sm py-1 ps-2 pe-4" style="width: 80px; height: 30px; font-size: 0.82rem;" onchange="changePerPage(this.value)">
                    <option value="10" <?= ($rawPerPage === '10') ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= ($rawPerPage === '20') ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= ($rawPerPage === '50') ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($rawPerPage === '100') ? 'selected' : '' ?>>100</option>
                    <option value="all" <?= ($rawPerPage === 'all') ? 'selected' : '' ?>>All</option>
                </select>
                <span class="small text-muted">per page</span>
            </div>
        </div>

        <!-- Pagination Navigation (Next / Previous) -->
        <?php if ($totalPages > 1 && $rawPerPage !== 'all'): ?>
            <nav aria-label="Candidates Pagination <?= $position ?>">
                <ul class="pagination pagination-sm mb-0">
                    <?php 
                    $queryParams = $_GET;
                    ?>
                    <!-- Previous Button -->
                    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                        <?php $queryParams['page'] = $currentPage - 1; ?>
                        <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>" <?= ($currentPage <= 1) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php 
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    if ($startPage > 1): 
                        $queryParams['page'] = 1;
                    ?>
                        <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>">1</a></li>
                        <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <?php $queryParams['page'] = $p; ?>
                        <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): 
                        $queryParams['page'] = $totalPages;
                    ?>
                        <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                        <?php $queryParams['page'] = $currentPage + 1; ?>
                        <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>" <?= ($currentPage >= $totalPages) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};
?>

<div class="container-fluid py-3 px-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-address-book text-primary"></i>
                Candidate Tracker (ATS)
            </h4>
            <p class="text-muted small mb-0">Track applicants, interview appointments, call outcomes, and hiring pipeline.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end ms-auto">
            <button type="button" class="btn btn-outline-success btn-sm px-3 shadow-sm" onclick="exportAtsExcel()" title="Export Excel (.xlsx)">
                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm" onclick="exportAtsCsv()" title="Export CSV (.csv)">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm" onclick="exportAtsPdf()" title="Export PDF (.pdf)">
                <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#csvUploadModal">
                <i class="fa-solid fa-file-csv me-1"></i> Batch CSV Upload
            </button>
            <a href="<?= $baseUrl ?>/ats-dashboard" class="btn btn-outline-info btn-sm px-3">
                <i class="fa-solid fa-chart-pie me-1"></i> Recruitment Dashboard
            </a>
            <a href="<?= $baseUrl ?>/ats-dropdown-settings" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-sliders me-1"></i> Dropdown Settings
            </a>
            <a href="<?= $baseUrl ?>/ats-trash" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center" title="View Trash & Restorable Records">
                <i class="fa-solid fa-trash-can me-1"></i> Trash
                <?php if (!empty($trashCount) && $trashCount > 0): ?>
                    <span class="badge bg-danger rounded-pill px-1 ms-1"><?= $trashCount ?></span>
                <?php endif; ?>
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openAddModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Candidate
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Summary Statistics Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=all" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'all') ? 'border-primary border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Applicants</span>
                            <h4 class="fw-bold my-0 text-dark"><?= (int)($tabCounts['all'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                            <i class="fa-solid fa-users fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=calling_queue" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'calling_queue') ? 'border-warning border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Calling Queue</span>
                            <h4 class="fw-bold my-0 text-warning"><?= (int)($tabCounts['calling_queue'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                            <i class="fa-solid fa-phone-volume fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=today" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'today') ? 'border-danger border-2 bg-light' : '' ?>" style="background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Today's Interview</span>
                            <h4 class="fw-bold my-0 text-danger"><?= (int)($tabCounts['today'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                            <i class="fa-solid fa-calendar-day fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=upcoming" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'upcoming') ? 'border-info border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Upcoming</span>
                            <h4 class="fw-bold my-0 text-info"><?= (int)($tabCounts['upcoming'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                            <i class="fa-solid fa-calendar-week fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=shortlisted" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'shortlisted') ? 'border-primary border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Attended / Viva</span>
                            <h4 class="fw-bold my-0 text-primary"><?= (int)($tabCounts['shortlisted'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                            <i class="fa-solid fa-user-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=hired" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'hired') ? 'border-success border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Hired / Onboard</span>
                            <h4 class="fw-bold my-0 text-success"><?= (int)($tabCounts['hired'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.15); color: #059669;">
                            <i class="fa-solid fa-briefcase fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php
    // Helper to render a sleek, compact multi-select checkbox dropdown
    $renderMultiDropdown = function($id, $name, $placeholder, $items, $selectedVals, $hasSearch = false) {
        $selectedCount = 0;
        if (is_array($selectedVals)) {
            $selectedCount = count(array_filter($selectedVals, fn($v) => $v !== ''));
        } elseif ($selectedVals !== null && $selectedVals !== '') {
            $selectedCount = 1;
        }

        $displayText = $placeholder;
        if ($selectedCount === 1) {
            $val = is_array($selectedVals) ? reset($selectedVals) : $selectedVals;
            foreach ($items as $item) {
                $optVal = is_array($item) ? ($item['value'] ?? $item['option_value'] ?? '') : $item;
                $optLabel = is_array($item) ? ($item['label'] ?? $item['option_value'] ?? '') : $item;
                if ((string)$optVal === (string)$val) {
                    $displayText = $optLabel;
                    break;
                }
            }
        } elseif ($selectedCount > 1) {
            $firstLabel = '';
            $val = is_array($selectedVals) ? reset($selectedVals) : $selectedVals;
            foreach ($items as $item) {
                $optVal = is_array($item) ? ($item['value'] ?? $item['option_value'] ?? '') : $item;
                $optLabel = is_array($item) ? ($item['label'] ?? $item['option_value'] ?? '') : $item;
                if ((string)$optVal === (string)$val) {
                    $firstLabel = $optLabel;
                    break;
                }
            }
            $displayText = $firstLabel ? ($firstLabel . ' (+' . ($selectedCount - 1) . ')') : ($selectedCount . ' selected');
        }
    ?>
        <div class="dropdown ats-multi-dropdown w-100" id="dropdown_<?= $id ?>">
            <button class="form-select form-select-sm w-100 text-start d-flex align-items-center justify-content-between bg-white shadow-none" 
                    type="button" 
                    id="btn_<?= $id ?>" 
                    data-placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES) ?>"
                    data-bs-toggle="dropdown" 
                    data-bs-auto-close="outside" 
                    aria-expanded="false"
                    style="height: 34px; font-size: 0.82rem; border-color: #cbd5e1; padding-right: 1.75rem;">
                <span class="ats-multi-btn-text flex-grow-1 text-truncate <?= $selectedCount > 0 ? 'text-dark fw-semibold' : 'text-muted' ?>" style="min-width: 0;">
                    <?= htmlspecialchars($displayText) ?>
                </span>
                <?php if ($selectedCount > 1): ?>
                    <span class="badge bg-primary rounded-pill px-2 py-1 ms-1 flex-shrink-0" style="font-size: 0.68rem;"><?= $selectedCount ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu shadow-lg border-0 p-2" aria-labelledby="btn_<?= $id ?>" style="min-width: 230px; max-width: 320px; z-index: 1055; font-size: 0.8125rem;">
                <?php if ($hasSearch && count($items) > 5): ?>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm ats-dropdown-search" placeholder="Search..." onkeyup="atsFilterDropdownSearch(this, '<?= $id ?>')">
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center px-1 mb-1 pb-1 border-bottom">
                    <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none small text-primary fw-semibold" onclick="atsSelectAllDropdown('<?= $id ?>', true)">Select All</button>
                    <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none small text-secondary fw-semibold" onclick="atsSelectAllDropdown('<?= $id ?>', false)">Clear</button>
                </div>
                <div class="ats-dropdown-items-list" style="max-height: 220px; overflow-y: auto;">
                    <?php foreach ($items as $item): 
                        $optVal = is_array($item) ? ($item['value'] ?? $item['option_value'] ?? '') : $item;
                        $optLabel = is_array($item) ? ($item['label'] ?? $item['option_value'] ?? '') : $item;
                        $isChecked = is_array($selectedVals) ? in_array((string)$optVal, array_map('strval', $selectedVals), true) : ((string)$selectedVals === (string)$optVal && (string)$selectedVals !== '');
                    ?>
                        <div class="form-check py-1 px-3 m-0 rounded hover-bg ats-dropdown-item">
                            <input class="form-check-input cursor-pointer" type="checkbox" name="<?= $name ?>[]" value="<?= htmlspecialchars($optVal) ?>" id="<?= $id ?>_<?= md5($optVal) ?>" <?= $isChecked ? 'checked' : '' ?> onchange="atsUpdateDropdownText('<?= $id ?>', '<?= htmlspecialchars($placeholder, ENT_QUOTES) ?>')">
                            <label class="form-check-label text-dark text-truncate d-block cursor-pointer small ps-1" for="<?= $id ?>_<?= md5($optVal) ?>" title="<?= htmlspecialchars($optLabel) ?>">
                                <?= htmlspecialchars($optLabel) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php
    };

    // Calculate active filter count
    $activeFilterCount = 0;
    if (!empty($filters['search'])) $activeFilterCount++;
    if (!empty($filters['department']) && count((array)$filters['department']) > 0) $activeFilterCount++;
    if (!empty($filters['position']) && count((array)$filters['position']) > 0) $activeFilterCount++;
    if (!empty($filters['invitation_status']) && count((array)$filters['invitation_status']) > 0) $activeFilterCount++;
    if (!empty($filters['interview_attendance']) && count((array)$filters['interview_attendance']) > 0) $activeFilterCount++;
    if (!empty($filters['interview_location']) && count((array)$filters['interview_location']) > 0) $activeFilterCount++;
    if (!empty($filters['hired']) && count((array)$filters['hired']) > 0) $activeFilterCount++;
    if (!empty($filters['cv_uploaded'])) $activeFilterCount++;
    if (!empty($filters['email_sent'])) $activeFilterCount++;
    if (!empty($filters['sms_sent'])) $activeFilterCount++;
    if (!empty($filters['date_from']) || !empty($filters['date_to'])) $activeFilterCount++;
    ?>

    <style>
    /* ATS Filter Bar Styling */
    .ats-filter-card {
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
    }
    .ats-filter-card .card-header {
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .ats-filter-card .card-body {
        background: #fafbfc;
    }
    .ats-multi-dropdown {
        width: 100% !important;
    }
    .ats-multi-dropdown .form-select {
        width: 100% !important;
        height: 34px !important;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.82rem;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease-in-out;
    }
    .ats-multi-dropdown .form-select:hover {
        border-color: #94a3b8;
        background-color: #f8fafc;
    }
    .ats-multi-dropdown .form-select:focus,
    .ats-multi-dropdown .form-select.show {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        background-color: #ffffff;
    }
    .ats-multi-dropdown .dropdown-menu {
        border-radius: 0.375rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    }
    .ats-filter-input {
        height: 34px !important;
        font-size: 0.82rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .ats-filter-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .hover-bg:hover {
        background-color: #f1f5f9;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .ats-dropdown-items-list::-webkit-scrollbar {
        width: 5px;
    }
    .ats-dropdown-items-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    </style>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 ats-filter-card">
        <!-- Filter Header -->
        <div class="card-header py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                    <i class="fa-solid fa-sliders" style="font-size: 0.8rem;"></i>
                </div>
                <span class="fw-bold text-dark" style="font-size: 0.88rem;">Filter Candidates</span>
                <?php if ($activeFilterCount > 0): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-circle-check me-1"></i><?= $activeFilterCount ?> active filter<?= $activeFilterCount > 1 ? 's' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $baseUrl ?>/ats-tracker?reset_filter=1" class="btn btn-outline-danger btn-sm px-3 d-inline-flex align-items-center gap-1 shadow-none" style="height: 31px; font-size: 0.78rem;" title="Reset all filters to default">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
                <button type="submit" form="filterForm" class="btn btn-primary btn-sm px-3 d-inline-flex align-items-center gap-1 shadow-sm fw-semibold" style="height: 31px; font-size: 0.78rem;" title="Apply Filter">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <form method="GET" action="<?= $baseUrl ?>/ats-tracker" id="filterForm">
                <input type="hidden" name="filter_applied" value="1">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($filters['tab']) ?>">
                <input type="hidden" name="per_page" value="<?= htmlspecialchars($rawPerPage ?? '20') ?>">
                
                <!-- Primary Filter Row -->
                <div class="row g-2 align-items-end">
                    <!-- Search input -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-magnifying-glass text-primary me-1"></i>Search Candidate
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1;"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" class="form-control ats-filter-input border-start-0" placeholder="Search name, phone, email..." value="<?= htmlspecialchars($filters['search']) ?>" style="height: 34px;">
                        </div>
                    </div>
                    <!-- Department -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-building text-info me-1"></i>Department
                        </label>
                        <?php $renderMultiDropdown('dept', 'department', 'All Departments', $options['department'] ?? [], $filters['department'], true); ?>
                    </div>
                    <!-- Position -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-briefcase text-secondary me-1"></i>Position
                        </label>
                        <?php $renderMultiDropdown('pos', 'position', 'All Positions', $options['position'] ?? [], $filters['position'], true); ?>
                    </div>
                    <!-- Call / Invitation Status -->
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-phone text-warning me-1"></i>Call Status
                        </label>
                        <?php $renderMultiDropdown('call_status', 'invitation_status', 'All Statuses', $options['invitation_status'] ?? [], $filters['invitation_status'], false); ?>
                    </div>
                    <!-- Attendance Status -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-user-check text-success me-1"></i>Attendance
                        </label>
                        <?php 
                        $attItems = [
                            ['value' => 'pending', 'label' => 'Pending'],
                            ['value' => 'present', 'label' => 'Present'],
                            ['value' => 'absent', 'label' => 'Absent'],
                        ];
                        $renderMultiDropdown('att', 'interview_attendance', 'All Attendance', $attItems, $filters['interview_attendance'], false); 
                        ?>
                    </div>
                </div>

                <!-- Secondary Filter Row -->
                <div class="row g-2 mt-2 pt-2 border-top align-items-end" style="border-color: #e2e8f0 !important;">
                    <!-- Interview Location -->
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i>Location
                        </label>
                        <?php $renderMultiDropdown('loc', 'interview_location', 'All Locations', $options['interview_location'] ?? [], $filters['interview_location'], false); ?>
                    </div>
                    <!-- Hiring Status -->
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-award text-primary me-1"></i>Hiring Status
                        </label>
                        <?php 
                        $hireItems = [
                            ['value' => 'pending', 'label' => 'Pending'],
                            ['value' => 'yes', 'label' => 'Hired / Selected'],
                            ['value' => 'no', 'label' => 'Not Selected'],
                        ];
                        $renderMultiDropdown('hire_st', 'hired', 'All Decisions', $hireItems, $filters['hired'], false); 
                        ?>
                    </div>
                    <!-- CV Uploaded -->
                    <div class="col-4 col-lg-1">
                        <label class="form-label small fw-semibold text-secondary mb-1 text-truncate d-block">
                            <i class="fa-solid fa-file-pdf text-danger me-1"></i>CV
                        </label>
                        <select name="cv_uploaded" class="form-select form-select-sm ats-filter-input">
                            <option value="">All</option>
                            <option value="yes" <?= ($filters['cv_uploaded'] === 'yes') ? 'selected' : '' ?>>Yes</option>
                            <option value="no" <?= ($filters['cv_uploaded'] === 'no') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <!-- Email Sent -->
                    <div class="col-4 col-lg-1">
                        <label class="form-label small fw-semibold text-secondary mb-1 text-truncate d-block">
                            <i class="fa-solid fa-envelope text-primary me-1"></i>Email
                        </label>
                        <select name="email_sent" class="form-select form-select-sm ats-filter-input">
                            <option value="">All</option>
                            <option value="yes" <?= ($filters['email_sent'] === 'yes') ? 'selected' : '' ?>>Yes</option>
                            <option value="no" <?= ($filters['email_sent'] === 'no') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <!-- SMS Sent -->
                    <div class="col-4 col-lg-1">
                        <label class="form-label small fw-semibold text-secondary mb-1 text-truncate d-block">
                            <i class="fa-solid fa-comment-sms text-success me-1"></i>SMS
                        </label>
                        <select name="sms_sent" class="form-select form-select-sm ats-filter-input">
                            <option value="">All</option>
                            <option value="yes" <?= ($filters['sms_sent'] === 'yes') ? 'selected' : '' ?>>Yes</option>
                            <option value="no" <?= ($filters['sms_sent'] === 'no') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <!-- Date Range -->
                    <div class="col-12 col-lg-5">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-regular fa-calendar-days text-info me-1"></i>Date Range
                        </label>
                        <div class="input-group input-group-sm">
                            <select name="date_type" class="form-select form-select-sm ats-filter-input" style="max-width: 140px;">
                                <option value="interview_date" <?= ($filters['date_type'] === 'interview_date') ? 'selected' : '' ?>>Interview Date</option>
                                <option value="interview_call_date" <?= ($filters['date_type'] === 'interview_call_date') ? 'selected' : '' ?>>Call Date</option>
                                <option value="created_at" <?= ($filters['date_type'] === 'created_at') ? 'selected' : '' ?>>Created Date</option>
                            </select>
                            <input type="date" name="date_from" class="form-control ats-filter-input" value="<?= htmlspecialchars($filters['date_from']) ?>" title="Date From">
                            <span class="input-group-text bg-light text-muted px-2 small border-start-0 border-end-0" style="border-color: #cbd5e1;">to</span>
                            <input type="date" name="date_to" class="form-control ats-filter-input" value="<?= htmlspecialchars($filters['date_to']) ?>" title="Date To">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Actions Bar (Visible when rows selected) -->
    <div id="batchActionBar" class="alert alert-secondary py-2 px-3 mb-2 rounded-3 d-none align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-2" id="selectedCountBadge">0</span>
            <span class="small fw-semibold text-dark">candidate(s) selected</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('invitation_status')">
                <i class="fa-solid fa-phone me-1"></i> Change Call Status
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('position_dept')">
                <i class="fa-solid fa-user-gear me-1"></i> Change Position & Dept
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('interview_attendance')">
                <i class="fa-solid fa-user-check me-1"></i> Change Attendance
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('schedule')">
                <i class="fa-solid fa-calendar-days me-1"></i> Set Interview Schedule
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('hired')">
                <i class="fa-solid fa-briefcase me-1"></i> Change Hired Status
            </button>
            <button type="button" class="btn btn-outline-success btn-sm py-1" onclick="openBulkUpdateModal('communication')">
                <i class="fa-solid fa-paper-plane me-1"></i> Email / SMS Status
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm py-1 ms-auto" onclick="executeBulkDelete()">
                <i class="fa-solid fa-trash-can me-1"></i> Move to Trash
            </button>
        </div>
    </div>

    <!-- Main Spreadsheet Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <?= $renderPaginationBar('top') ?>
        <div class="table-responsive" style="max-height: 650px;">
            <table class="table table-hover align-middle mb-0" id="candidatesTable" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top shadow-sm" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <tr class="text-secondary">
                        <th class="text-center px-2" style="width: 35px;">
                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <?= $renderSortHeader('id', '#', '40px') ?>
                        <?= $renderSortHeader('name', 'Candidate Details', '220px') ?>
                        <?= $renderSortHeader('position', 'Position & Dept', '170px') ?>
                        <?= $renderSortHeader('invitation_status', 'Call Status', '130px') ?>
                        <?= $renderSortHeader('interview_date', 'Interview Schedule', '180px') ?>
                        <?= $renderSortHeader('interview_attendance', 'Attendance', '165px') ?>
                        <?= $renderSortHeader('hired', 'Hired / Status', '120px') ?>
                        <?= $renderSortHeader('email_sent', 'Email & SMS', '130px') ?>
                        <?= $renderSortHeader('cv_file_path', 'CV / File', '110px') ?>
                        <th class="text-end px-3" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-folder-open fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No candidates found matching your criteria.
                                <div class="mt-2">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal()">
                                        <i class="fa-solid fa-plus me-1"></i> Add First Candidate
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $sl = ($currentPage - 1) * $perPage;
                        foreach ($candidates as $c): 
                            $sl++;
                            $isToday = !empty($c['interview_date']) && $c['interview_date'] === date('Y-m-d');
                            $isPast = !empty($c['interview_date']) && $c['interview_date'] < date('Y-m-d');
                        ?>
                            <tr id="candidateRow_<?= $c['id'] ?>" class="<?= $isToday ? 'table-warning bg-opacity-25' : '' ?>">
                                <!-- Checkbox -->
                                <td class="text-center px-2">
                                    <input type="checkbox" class="form-check-input row-select-box" value="<?= $c['id'] ?>" onchange="handleRowSelect()">
                                </td>
                                
                                <!-- Serial -->
                                <td class="text-muted fw-semibold px-2"><?= $sl ?></td>

                                <!-- Candidate Name & Contact -->
                                <td>
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="fw-bold text-dark candidate-name-display-<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <a href="tel:<?= htmlspecialchars($c['phone']) ?>" class="text-decoration-none text-primary small candidate-phone-link-<?= $c['id'] ?>" title="Call">
                                                    <i class="fa-solid fa-phone me-1"></i><span class="candidate-phone-display-<?= $c['id'] ?>"><?= htmlspecialchars($c['phone']) ?></span>
                                                </a>
                                                <a href="https://wa.me/88<?= preg_replace('/[^0-9]/', '', $c['phone']) ?>" target="_blank" class="text-success small candidate-wa-link-<?= $c['id'] ?>" title="WhatsApp Message">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                </a>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <i class="fa-regular fa-envelope me-1"></i><span class="candidate-email-display-<?= $c['id'] ?>"><?= !empty($c['email']) ? htmlspecialchars($c['email']) : '<span class="text-muted fst-italic">No email</span>' ?></span>
                                            </div>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-secondary p-0 ms-2 text-decoration-none" 
                                                title="Edit Candidate Details" 
                                                onclick="openQuickContactModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($c['phone']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($c['email'] ?? ''), ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Position & Department -->
                                <td>
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="fw-semibold text-dark candidate-pos-display-<?= $c['id'] ?>"><?= htmlspecialchars($c['position']) ?></div>
                                            <span class="badge bg-light text-secondary border mt-1 candidate-dept-display-<?= $c['id'] ?>" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($c['department']) ?>
                                            </span>
                                            <?php if (!empty($c['source'])): ?>
                                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                                    <i class="fa-solid fa-bullhorn me-1 opacity-75"></i><?= htmlspecialchars($c['source']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-secondary p-0 ms-2 text-decoration-none" 
                                                title="Edit Position & Department" 
                                                onclick="openQuickPositionModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($c['position']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($c['department']), ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Call / Invitation Status (Quick Dropdown) -->
                                <td>
                                    <select class="form-select form-select-sm py-1 status-select" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'invitation_status', this.value)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="Pending" <?= ($c['invitation_status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                        <?php foreach ($options['invitation_status'] ?? [] as $st): ?>
                                            <option value="<?= htmlspecialchars($st['option_value']) ?>" <?= ($c['invitation_status'] === $st['option_value']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($st['option_value']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($c['interview_call_date'])): ?>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                            <i class="fa-regular fa-calendar-check me-1"></i>Called: <?= date('d M Y', strtotime($c['interview_call_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Interview Schedule -->
                                <td>
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <?php if (!empty($c['interview_date'])): ?>
                                                <div class="fw-semibold <?= $isToday ? 'text-danger fw-bold' : 'text-dark' ?>">
                                                    <i class="fa-regular fa-calendar me-1 text-danger"></i><?= date('d M Y', strtotime($c['interview_date'])) ?>
                                                    <?php if ($isToday): ?>
                                                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">TODAY</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($c['interview_time'])): ?>
                                                    <div class="text-muted small" style="font-size: 0.74rem;">
                                                        <i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($c['interview_time'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($c['interview_location'])): ?>
                                                    <div class="small mt-1" style="font-size: 0.72rem; color: #0d9488;">
                                                        <i class="fa-solid fa-location-dot me-1 text-danger opacity-75"></i><?= htmlspecialchars($c['interview_location']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">Not scheduled</span>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-secondary p-0 ms-2 text-decoration-none" 
                                                title="Edit Interview Schedule"
                                                onclick="openSingleScheduleModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>', '<?= $c['interview_date'] ?? '' ?>', '<?= !empty($c['interview_time']) ? date('H:i', strtotime($c['interview_time'])) : '' ?>', '<?= htmlspecialchars(addslashes($c['interview_location'] ?? '')) ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Attendance Status (Quick Dropdown) & Remarks -->
                                <td>
                                    <select class="form-select form-select-sm py-1 attendance-select <?= ($c['interview_attendance'] === 'present') ? 'bg-success text-white' : (($c['interview_attendance'] === 'absent') ? 'bg-danger text-white' : '') ?>" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'interview_attendance', this.value, this)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="pending" <?= ($c['interview_attendance'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="present" <?= ($c['interview_attendance'] === 'present') ? 'selected' : '' ?>>Present</option>
                                        <option value="absent" <?= ($c['interview_attendance'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                                    </select>
                                    <?php if ($isPast && $c['interview_attendance'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;">Awaiting Attendance</span>
                                    <?php endif; ?>

                                    <!-- Remarks Display & Edit -->
                                    <div class="mt-2 pt-1 border-top d-flex align-items-center justify-content-between gap-1" style="font-size: 0.74rem;">
                                        <div class="text-truncate candidate-remarks-wrapper-<?= $c['id'] ?>" style="max-width: 135px; line-height: 1.25;" title="<?= htmlspecialchars($c['remarks'] ?? '', ENT_QUOTES) ?>">
                                            <i class="fa-regular fa-comment-dots text-primary me-1"></i><span class="candidate-remarks-display-<?= $c['id'] ?> <?= empty($c['remarks']) ? 'text-muted fst-italic' : 'text-dark fw-medium' ?>"><?= !empty($c['remarks']) ? htmlspecialchars($c['remarks']) : 'No remarks' ?></span>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-secondary p-0 text-decoration-none btn-quick-remarks-<?= $c['id'] ?>" 
                                                title="Edit Remarks" 
                                                data-id="<?= $c['id'] ?>"
                                                data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                                                data-remarks="<?= htmlspecialchars($c['remarks'] ?? '', ENT_QUOTES) ?>"
                                                onclick="openQuickRemarksModal(this)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Hired Status -->
                                <td>
                                    <select class="form-select form-select-sm py-1 <?= ($c['hired'] === 'yes') ? 'bg-success text-white' : (($c['hired'] === 'no') ? 'bg-secondary text-white' : '') ?>" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'hired', this.value, this)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="pending" <?= ($c['hired'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="yes" <?= ($c['hired'] === 'yes') ? 'selected' : '' ?>>Hired</option>
                                        <option value="no" <?= ($c['hired'] === 'no') ? 'selected' : '' ?>>Not Selected</option>
                                    </select>
                                    <?php if ($c['hired'] === 'yes' && !empty($c['onboarding_salary'])): ?>
                                        <div class="text-success fw-bold mt-1" style="font-size: 0.7rem;">
                                            ৳ <?= number_format($c['onboarding_salary'], 0) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Email & SMS Quick Toggle -->
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <!-- Email Toggle -->
                                        <?php $emailActive = (!empty($c['email_sent']) && ((int)$c['email_sent'] === 1 || $c['email_sent'] === '1')); ?>
                                        <button type="button" 
                                                id="emailBtn_<?= $c['id'] ?>"
                                                class="btn btn-xs d-inline-flex align-items-center justify-content-between px-2 py-0 border <?= $emailActive ? 'btn-success text-white' : 'btn-light text-muted' ?>"
                                                style="font-size: 0.72rem; height: 22px; width: 105px;"
                                                onclick="toggleComms(<?= $c['id'] ?>, 'email_sent', <?= $emailActive ? 0 : 1 ?>, this)"
                                                title="Click to toggle Email Sent status">
                                            <span><i class="fa-solid fa-envelope me-1"></i>Email</span>
                                            <i class="fa-solid <?= $emailActive ? 'fa-check' : 'fa-xmark opacity-50' ?>"></i>
                                        </button>
                                        <!-- SMS Toggle -->
                                        <?php $smsActive = (!empty($c['sms_sent']) && ((int)$c['sms_sent'] === 1 || $c['sms_sent'] === '1')); ?>
                                        <button type="button" 
                                                id="smsBtn_<?= $c['id'] ?>"
                                                class="btn btn-xs d-inline-flex align-items-center justify-content-between px-2 py-0 border <?= $smsActive ? 'btn-primary text-white' : 'btn-light text-muted' ?>"
                                                style="font-size: 0.72rem; height: 22px; width: 105px;"
                                                onclick="toggleComms(<?= $c['id'] ?>, 'sms_sent', <?= $smsActive ? 0 : 1 ?>, this)"
                                                title="Click to toggle SMS Sent status">
                                            <span><i class="fa-solid fa-comment-sms me-1"></i>SMS</span>
                                            <i class="fa-solid <?= $smsActive ? 'fa-check' : 'fa-xmark opacity-50' ?>"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- CV Document -->
                                <td>
                                    <?php if ($c['cv_source'] === 'file' && !empty($c['cv_file_path'])): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="previewCv('<?= $baseUrl ?>/<?= htmlspecialchars($c['cv_file_path']) ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>')">
                                            <i class="fa-solid fa-file-pdf me-1 text-danger"></i> View CV
                                        </button>
                                    <?php elseif ($c['cv_source'] === 'drive_link' && !empty($c['cv_drive_link'])): ?>
                                        <a href="<?= htmlspecialchars($c['cv_drive_link']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                            <i class="fa-brands fa-google-drive me-1 text-warning"></i> Drive Link
                                        </a>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="text-muted small fst-italic">No CV</span>
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm py-0 px-2 shadow-none" 
                                                    style="font-size: 0.72rem; height: 23px;"
                                                    onclick="openQuickCvModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')"
                                                    title="Upload CV for this candidate">
                                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-end px-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light btn-sm text-primary" onclick="openEditModal(<?= $c['id'] ?>)" title="Edit Candidate">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm text-danger" onclick="deleteCandidate(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="Move to Trash">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <?= $renderPaginationBar('bottom') ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Single Candidate Add & Edit Form -->
<!-- ========================================================================= -->
<div class="modal fade" id="candidateFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="candidateForm" method="POST" action="<?= $baseUrl ?>/ats-candidate-store" enctype="multipart/form-data">
                <input type="hidden" name="candidate_id" id="formCandidateId" value="">
                
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold" id="candidateModalTitle">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <!-- Core Details (Mandatory) -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(37, 99, 235, 0.12); color: #1d4ed8; font-size: 0.78rem;">
                            <i class="fa-solid fa-user me-1"></i> 1. Core Information (Mandatory)
                        </span>
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Candidate Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="cName" class="form-control form-control-sm" required placeholder="Full Name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="cPhone" class="form-control form-control-sm" required placeholder="017XXXXXXXX">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="email" id="cEmail" class="form-control form-control-sm" placeholder="name@example.com (Optional)">
                            </div>
                        </div>
                    </div>

                    <!-- Role & Source Details -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(100, 116, 139, 0.12); color: #334155; font-size: 0.78rem;">
                            <i class="fa-solid fa-briefcase me-1"></i> 2. Position & Source
                        </span>
                        <div class="row ">
                            <!-- Department -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Department</label>
                                <select name="department" id="cDepartment" class="form-select form-select-sm" onchange="checkOtherOption('cDepartment', 'cDepartmentOther')">
                                    <option value="">Select Department</option>
                                    <?php foreach ($options['department'] ?? [] as $d): ?>
                                        <option value="<?= htmlspecialchars($d['option_value']) ?>"><?= htmlspecialchars($d['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="department_other" id="cDepartmentOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom department name">
                            </div>
                            <!-- Position -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Position / Role</label>
                                <select name="position" id="cPosition" class="form-select form-select-sm" onchange="checkOtherOption('cPosition', 'cPositionOther')">
                                    <option value="">Select Position</option>
                                    <?php foreach ($options['position'] ?? [] as $p): ?>
                                        <option value="<?= htmlspecialchars($p['option_value']) ?>"><?= htmlspecialchars($p['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="position_other" id="cPositionOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom position name">
                            </div>
                            <!-- Source -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Source / Channel</label>
                                <select name="source" id="cSource" class="form-select form-select-sm" onchange="checkOtherOption('cSource', 'cSourceOther')">
                                    <option value="">Select Source</option>
                                    <?php foreach ($options['source'] ?? [] as $s): ?>
                                        <option value="<?= htmlspecialchars($s['option_value']) ?>"><?= htmlspecialchars($s['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="source_other" id="cSourceOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom source">
                            </div>
                            <!-- Referred By -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Referred By (Optional)</label>
                                <input type="text" name="referred_by" id="cReferredBy" class="form-control form-control-sm" placeholder="Employee / Person name">
                            </div>
                            <!-- Invitation Status -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Invitation Call Status</label>
                                <select name="invitation_status" id="cInvitationStatus" class="form-select form-select-sm">
                                    <option value="Pending">Pending</option>
                                    <?php foreach ($options['invitation_status'] ?? [] as $st): ?>
                                        <option value="<?= htmlspecialchars($st['option_value']) ?>"><?= htmlspecialchars($st['option_value']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Scheduling -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(2, 132, 199, 0.12); color: #0369a1; font-size: 0.78rem;">
                            <i class="fa-solid fa-calendar me-1"></i> 3. Interview Details
                        </span>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Call Date</label>
                                <input type="date" name="interview_call_date" id="cCallDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Date</label>
                                <input type="date" name="interview_date" id="cInterviewDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Time</label>
                                <input type="time" name="interview_time" id="cInterviewTime" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Location</label>
                                <select name="interview_location" id="cLocation" class="form-select form-select-sm" onchange="checkOtherOption('cLocation', 'cLocationOther')">
                                    <option value="">Select Location</option>
                                    <?php foreach ($options['interview_location'] ?? [] as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc['option_value']) ?>"><?= htmlspecialchars($loc['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="interview_location_other" id="cLocationOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter location name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Attendance Outcome</label>
                                <select name="interview_attendance" id="cAttendance" class="form-select form-select-sm">
                                    <option value="pending">Pending</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Remarks / Notes</label>
                                <input type="text" name="remarks" id="cRemarks" class="form-control form-control-sm" placeholder="Free-form interview notes">
                            </div>
                            <!-- Communication Status Switches -->
                            <div class="col-12 mt-3 pt-2 border-top">
                                <div class="d-flex flex-wrap align-items-center gap-4">
                                    <span class="small fw-bold text-secondary"><i class="fa-solid fa-paper-plane me-1"></i>Notification Tracking:</span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="email_sent" id="cEmailSent" value="1">
                                        <label class="form-check-label small fw-semibold text-dark" for="cEmailSent">
                                            <i class="fa-solid fa-envelope text-success me-1"></i> Email Invitation Sent
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="sms_sent" id="cSmsSent" value="1">
                                        <label class="form-check-label small fw-semibold text-dark" for="cSmsSent">
                                            <i class="fa-solid fa-comment-sms text-primary me-1"></i> SMS Notification Sent
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CV Upload / Google Drive Link -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(16, 185, 129, 0.15); color: #047857; font-size: 0.78rem;">
                            <i class="fa-solid fa-file-pdf me-1"></i> 4. CV / Resume Document
                        </span>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">CV Source</label>
                                <select name="cv_source" id="cCvSource" class="form-select form-select-sm" onchange="toggleCvSource()">
                                    <option value="file">Upload File (PDF/Word)</option>
                                    <option value="drive_link">Google Drive Link</option>
                                </select>
                            </div>
                            <div class="col-md-8" id="cvFileInputGroup">
                                <label class="form-label small fw-semibold">Attach File (PDF, DOCX)</label>
                                <input type="file" name="cv_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                                <div id="existingCvNotice" class="small text-muted mt-1 d-none"></div>
                            </div>
                            <div class="col-md-8 d-none" id="cvLinkInputGroup">
                                <label class="form-label small fw-semibold">Google Drive / URL Link</label>
                                <input type="url" name="cv_drive_link" id="cCvDriveLink" class="form-control form-control-sm" placeholder="https://drive.google.com/...">
                            </div>
                        </div>
                    </div>

                    <!-- Hiring & Onboarding -->
                    <div>
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(245, 158, 11, 0.15); color: #b45309; font-size: 0.78rem;">
                            <i class="fa-solid fa-handshake me-1"></i> 5. Hiring & Onboarding Decision
                        </span>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Final Selection (Hired)</label>
                                <select name="hired" id="cHired" class="form-select form-select-sm">
                                    <option value="pending">Decision Pending</option>
                                    <option value="yes">Yes - Selected / Hired</option>
                                    <option value="no">No - Not Selected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Expected Joining Date</label>
                                <input type="date" name="joining_date" id="cJoiningDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Onboarding Salary (BDT)</label>
                                <input type="number" step="0.01" name="onboarding_salary" id="cSalary" class="form-control form-control-sm" placeholder="e.g. 35000">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4" id="submitBtn">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Batch CSV Upload -->
<!-- ========================================================================= -->
<div class="modal fade" id="csvUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $baseUrl ?>/ats-csv-upload" enctype="multipart/form-data">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold">Batch Candidate Entry (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fa-solid fa-file-csv text-primary" style="font-size: 3rem;"></i>
                        <h6 class="fw-bold mt-2">Upload Candidate Spreadsheet</h6>
                        <p class="text-muted small">Ideal for walk-in drives or high-volume candidate lists.</p>
                    </div>

                    <div class="alert alert-light border text-start small mb-3">
                        <div class="fw-bold mb-1 text-dark"><i class="fa-solid fa-circle-info text-primary me-1"></i>Required Columns:</div>
                        <code>Name, Phone</code>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">Sample CSV contains only Name and Phone. Other fields (Email, Position, Department, Source, Interview Date, Location, Remarks) are optional.</div>
                    </div>

                    <div class="mb-3">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="<?= $baseUrl ?>/ats-sample-csv" class="btn btn-link btn-sm text-decoration-none p-0">
                            <i class="fa-solid fa-download me-1"></i> Download Sample CSV
                        </a>
                        <div>
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">Upload & Import</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Bulk Action -->
<!-- ========================================================================= -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="bulkActionTitle">Bulk Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="bulkActionType" value="">
                
                <!-- Status Options -->
                <div id="bulkStatusSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Call Status for Selected Candidates</label>
                    <select id="bulkStatusValue" class="form-select">
                        <option value="Yes">Yes (Agreed to interview)</option>
                        <option value="No">No (Declined)</option>
                        <option value="Didn't Pick">Didn't Pick</option>
                        <option value="Busy">Busy</option>
                        <option value="Number Off">Number Off</option>
                        <option value="Will Inform Later">Will Inform Later</option>
                    </select>
                </div>

                <!-- Attendance Options -->
                <div id="bulkAttendanceSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Attendance Outcome</label>
                    <select id="bulkAttendanceValue" class="form-select">
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <!-- Schedule Options -->
                <div id="bulkScheduleSection" class="d-none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Interview Date</label>
                        <input type="date" id="bulkInterviewDate" class="form-control">
                        <div class="form-text" style="font-size: 0.75rem;">Leave empty if you don't want to change date.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Interview Time</label>
                        <input type="time" id="bulkInterviewTime" class="form-control">
                        <div class="form-text" style="font-size: 0.75rem;">Leave empty if you don't want to change time.</div>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Interview Location</label>
                        <select id="bulkInterviewLocation" class="form-select">
                            <option value="">-- Keep Current Location --</option>
                            <?php foreach ($options['interview_location'] ?? [] as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['option_value']) ?>"><?= htmlspecialchars($loc['option_value']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Hired Options -->
                <div id="bulkHiredSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Final Decision</label>
                    <select id="bulkHiredValue" class="form-select">
                        <option value="yes">Hired / Selected</option>
                        <option value="no">Not Selected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <!-- Communication (Email / SMS) Options -->
                <div id="bulkCommunicationSection" class="d-none">
                    <label class="form-label small fw-semibold mb-2">Update Notification Delivery Status</label>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Email Invitation Status:</label>
                        <select id="bulkEmailSentValue" class="form-select form-select-sm">
                            <option value="">-- No Change to Email Status --</option>
                            <option value="1">Mark Email as SENT</option>
                            <option value="0">Mark Email as NOT SENT</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">SMS Notification Status:</label>
                        <select id="bulkSmsSentValue" class="form-select form-select-sm">
                            <option value="">-- No Change to SMS Status --</option>
                            <option value="1">Mark SMS as SENT</option>
                            <option value="0">Mark SMS as NOT SENT</option>
                        </select>
                    </div>
                </div>

                <!-- Position & Department Options -->
                <div id="bulkPositionDeptSection" class="d-none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Position</label>
                        <select id="bulkPositionSelect" class="form-select form-select-sm" onchange="toggleQuickOther('bulkPositionSelect', 'bulkPositionOther')">
                            <option value="">-- Keep Current Position --</option>
                            <?php foreach ($options['position'] ?? [] as $pos): ?>
                                <option value="<?= htmlspecialchars($pos['option_value']) ?>"><?= htmlspecialchars($pos['option_value']) ?></option>
                            <?php endforeach; ?>
                            <option value="Other">+ Other (Type below)</option>
                        </select>
                        <input type="text" id="bulkPositionOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom position">
                        <div class="form-text" style="font-size: 0.72rem;">Leave as "Keep Current Position" if you only want to change department.</div>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">New Department</label>
                        <select id="bulkDepartmentSelect" class="form-select form-select-sm" onchange="toggleQuickOther('bulkDepartmentSelect', 'bulkDepartmentOther')">
                            <option value="">-- Keep Current Department --</option>
                            <?php foreach ($options['department'] ?? [] as $dep): ?>
                                <option value="<?= htmlspecialchars($dep['option_value']) ?>"><?= htmlspecialchars($dep['option_value']) ?></option>
                            <?php endforeach; ?>
                            <option value="Other">+ Other (Type below)</option>
                        </select>
                        <input type="text" id="bulkDepartmentOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom department">
                        <div class="form-text" style="font-size: 0.72rem;">Leave as "Keep Current Department" if you only want to change position.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" onclick="executeBulkUpdate()">Apply to Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Quick Edit Candidate Details (Name, Phone, Email) -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-user-pen me-1 text-primary"></i> Edit Candidate Details
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="quickContactCandidateId">
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Full Name <span class="text-danger">*</span></label>
                    <input type="text" id="quickContactName" class="form-control form-control-sm" placeholder="Candidate Name" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" id="quickContactPhone" class="form-control form-control-sm" placeholder="017XXXXXXXX" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Email Address</label>
                    <input type="email" id="quickContactEmail" class="form-control form-control-sm" placeholder="candidate@example.com">
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveQuickContact" onclick="saveQuickContact()">
                    <i class="fa-solid fa-check me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Quick Edit Position & Department -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickPositionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-briefcase me-1 text-primary"></i> Edit Position & Dept
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="small fw-semibold text-truncate mb-2 text-dark" id="quickPositionCandidateTitle"></div>
                <input type="hidden" id="quickPositionCandidateId">
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Position <span class="text-danger">*</span></label>
                    <select id="quickPositionSelect" class="form-select form-select-sm" onchange="toggleQuickOther('quickPositionSelect', 'quickPositionOther')">
                        <option value="">-- Select Position --</option>
                        <?php foreach ($options['position'] ?? [] as $pos): ?>
                            <option value="<?= htmlspecialchars($pos['option_value']) ?>"><?= htmlspecialchars($pos['option_value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">+ Other (Type below)</option>
                    </select>
                    <input type="text" id="quickPositionOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom position">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Department <span class="text-danger">*</span></label>
                    <select id="quickDepartmentSelect" class="form-select form-select-sm" onchange="toggleQuickOther('quickDepartmentSelect', 'quickDepartmentOther')">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($options['department'] ?? [] as $dep): ?>
                            <option value="<?= htmlspecialchars($dep['option_value']) ?>"><?= htmlspecialchars($dep['option_value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">+ Other (Type below)</option>
                    </select>
                    <input type="text" id="quickDepartmentOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom department">
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveQuickPosition" onclick="saveQuickPosition()">
                    <i class="fa-solid fa-check me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Quick Edit Remarks -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickRemarksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fa-regular fa-comment-dots me-1 text-primary"></i> Edit Remarks
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="small fw-semibold text-truncate mb-2 text-dark" id="quickRemarksCandidateTitle"></div>
                <input type="hidden" id="quickRemarksCandidateId">
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Remarks / Notes</label>
                    <textarea id="quickRemarksText" class="form-control form-control-sm" rows="3" placeholder="Enter remarks or interview notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveQuickRemarks" onclick="saveQuickRemarks()">
                    <i class="fa-solid fa-check me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Single Candidate Quick Schedule Edit -->
<!-- ========================================================================= -->
<div class="modal fade" id="singleScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fa-regular fa-calendar-check me-1 text-primary"></i> Edit Schedule
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="small fw-semibold text-truncate mb-2 text-dark" id="modalCandidateNameTitle"></div>
                <input type="hidden" id="modalCandidateId">
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Interview Date</label>
                    <input type="date" id="modalInterviewDate" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Interview Time</label>
                    <input type="time" id="modalInterviewTime" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Interview Location</label>
                    <select id="modalInterviewLocation" class="form-select form-select-sm">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($options['interview_location'] ?? [] as $loc): ?>
                            <option value="<?= htmlspecialchars($loc['option_value']) ?>"><?= htmlspecialchars($loc['option_value']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveSingleSchedule" onclick="saveSingleSchedule()">
                    <i class="fa-solid fa-check me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Quick CV Upload for Candidate -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickCvUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-cloud-arrow-up me-1 text-primary"></i> Upload Candidate CV
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickCvUploadForm" onsubmit="submitQuickCvUpload(event)">
                <div class="modal-body p-3">
                    <div class="small fw-semibold text-truncate mb-2 text-dark" id="cvModalCandidateName"></div>
                    <input type="hidden" id="cvModalCandidateId" name="candidate_id">

                    <!-- CV Source Selection Tabs -->
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cv_source" id="cvSourceFile" value="file" checked onchange="toggleQuickCvSource('file')">
                            <label class="form-check-label small" for="cvSourceFile">Upload File</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cv_source" id="cvSourceDrive" value="drive_link" onchange="toggleQuickCvSource('drive')">
                            <label class="form-check-label small" for="cvSourceDrive">Drive Link</label>
                        </div>
                    </div>

                    <!-- File input -->
                    <div id="quickCvFileSection" class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Select File (PDF, DOCX, Image)</label>
                        <input type="file" name="cv_file" id="quickCvFileInput" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="text-muted mt-1" style="font-size: 0.7rem;">Max 5MB. PDF or DOC preferred.</div>
                    </div>

                    <!-- Drive Link input -->
                    <div id="quickCvDriveSection" class="mb-2 d-none">
                        <label class="form-label small fw-semibold mb-1">Google Drive Link</label>
                        <input type="url" name="cv_drive_link" id="quickCvDriveInput" class="form-control form-control-sm" placeholder="https://drive.google.com/...">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitQuickCv">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload CV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: CV Viewer (PDF Preview) -->
<!-- ========================================================================= -->
<div class="modal fade" id="cvViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content border-0 shadow h-100">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title fw-bold" id="cvViewerTitle">Candidate CV</h6>
                <div>
                    <a href="#" id="cvDownloadBtn" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 me-2">
                        <i class="fa-solid fa-download me-1"></i> Open in New Tab
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 h-100">
                <iframe id="cvViewerFrame" src="" class="w-100 h-100 border-0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// =========================================================================
// JavaScript Logic for ATS Tracker
// =========================================================================

// Check "+ Other" selection
function checkOtherOption(selectId, otherInputId) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(otherInputId);
    if (sel.value === 'Other') {
        inp.classList.remove('d-none');
        inp.focus();
    } else {
        inp.classList.add('d-none');
        inp.value = '';
    }
}

// Toggle CV file vs link
function toggleCvSource() {
    const src = document.getElementById('cCvSource').value;
    if (src === 'file') {
        document.getElementById('cvFileInputGroup').classList.remove('d-none');
        document.getElementById('cvLinkInputGroup').classList.add('d-none');
    } else {
        document.getElementById('cvFileInputGroup').classList.add('d-none');
        document.getElementById('cvLinkInputGroup').classList.remove('d-none');
    }
}

// Open Add Candidate Modal
function openAddModal() {
    document.getElementById('candidateModalTitle').textContent = 'Add New Candidate';
    document.getElementById('candidateForm').action = '<?= $baseUrl ?>/ats-candidate-store';
    document.getElementById('formCandidateId').value = '';
    document.getElementById('candidateForm').reset();
    document.getElementById('existingCvNotice').classList.add('d-none');
    
    // Hide other inputs
    document.getElementById('cDepartmentOther').classList.add('d-none');
    document.getElementById('cPositionOther').classList.add('d-none');
    document.getElementById('cSourceOther').classList.add('d-none');
    document.getElementById('cLocationOther').classList.add('d-none');
    document.getElementById('cEmailSent').checked = false;
    document.getElementById('cSmsSent').checked = false;
    toggleCvSource();

    new bootstrap.Modal(document.getElementById('candidateFormModal')).show();
}

// Open Edit Candidate Modal
function openEditModal(id) {
    fetch(`<?= $baseUrl ?>/api/ats/candidate-details/${id}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'Could not load candidate', 'error');
                return;
            }
            const c = res.data;
            document.getElementById('candidateModalTitle').textContent = 'Edit Candidate: ' + c.name;
            document.getElementById('candidateForm').action = `<?= $baseUrl ?>/ats-candidate-update/${c.id}`;
            document.getElementById('formCandidateId').value = c.id;

            document.getElementById('cName').value = c.name || '';
            document.getElementById('cPhone').value = c.phone || '';
            document.getElementById('cEmail').value = c.email || '';

            // Dropdown values with "Other" check
            setSelectOrOther('cDepartment', 'cDepartmentOther', c.department);
            setSelectOrOther('cPosition', 'cPositionOther', c.position);
            setSelectOrOther('cSource', 'cSourceOther', c.source);
            setSelectOrOther('cLocation', 'cLocationOther', c.interview_location);

            document.getElementById('cReferredBy').value = c.referred_by || '';
            document.getElementById('cInvitationStatus').value = c.invitation_status || 'Pending';
            document.getElementById('cCallDate').value = c.interview_call_date || '';
            document.getElementById('cInterviewDate').value = c.interview_date || '';
            document.getElementById('cInterviewTime').value = c.interview_time || '';
            document.getElementById('cAttendance').value = c.interview_attendance || 'pending';
            document.getElementById('cRemarks').value = c.remarks || '';
            document.getElementById('cEmailSent').checked = (c.email_sent == 1 || c.email_sent === '1');
            document.getElementById('cSmsSent').checked = (c.sms_sent == 1 || c.sms_sent === '1');

            document.getElementById('cCvSource').value = c.cv_source || 'file';
            document.getElementById('cCvDriveLink').value = c.cv_drive_link || '';
            toggleCvSource();

            if (c.cv_file_path) {
                const notice = document.getElementById('existingCvNotice');
                notice.innerHTML = `<i class="fa-solid fa-file-pdf text-danger me-1"></i> Current CV: <a href="<?= $baseUrl ?>/${c.cv_file_path}" target="_blank">View File</a>`;
                notice.classList.remove('d-none');
            } else {
                document.getElementById('existingCvNotice').classList.add('d-none');
            }

            document.getElementById('cHired').value = c.hired || 'pending';
            document.getElementById('cJoiningDate').value = c.joining_date || '';
            document.getElementById('cSalary').value = c.onboarding_salary || '';

            new bootstrap.Modal(document.getElementById('candidateFormModal')).show();
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Network error loading candidate details', 'error');
        });
}

function setSelectOrOther(selectId, otherInputId, val) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(otherInputId);
    if (!val) {
        sel.value = '';
        inp.classList.add('d-none');
        inp.value = '';
        return;
    }
    let found = false;
    for (let opt of sel.options) {
        if (opt.value.toLowerCase() === val.toLowerCase()) {
            sel.value = opt.value;
            found = true;
            break;
        }
    }
    if (!found) {
        sel.value = 'Other';
        inp.classList.remove('d-none');
        inp.value = val;
    } else {
        inp.classList.add('d-none');
        inp.value = '';
    }
}

// 1-Click Fast Inline Update (AJAX)
function quickUpdateField(id, field, value, element = null) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('field', field);
    formData.append('value', value);

    fetch('<?= $baseUrl ?>/api/ats/quick-update', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(data => {
        if (data.success) {
            // Update UI pill color if attendance
            if (element && field === 'interview_attendance') {
                element.classList.remove('bg-success', 'bg-danger', 'text-white');
                if (value === 'present') element.classList.add('bg-success', 'text-white');
                if (value === 'absent') element.classList.add('bg-danger', 'text-white');
            }
            if (element && field === 'hired') {
                element.classList.remove('bg-success', 'bg-secondary', 'text-white');
                if (value === 'yes') element.classList.add('bg-success', 'text-white');
                if (value === 'no') element.classList.add('bg-secondary', 'text-white');
            }
            // Flash toast notification safely
            atsShowToast('Updated successfully!');
        } else {
            Swal.fire('Update Failed', data.message || 'Could not update status', 'error');
        }
    })
    .catch(err => {
        console.error('Quick update error:', err);            Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

// 1-Click Fast Toggle for Email & SMS Status
function toggleComms(id, field, nextValue, btn) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('field', field);
    formData.append('value', nextValue);

    fetch('<?= $baseUrl ?>/api/ats/quick-update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const isSent = (parseInt(nextValue) === 1);
            if (field === 'email_sent') {
                btn.className = `btn btn-xs d-inline-flex align-items-center justify-content-between px-2 py-0 border ${isSent ? 'btn-success text-white' : 'btn-light text-muted'}`;
                btn.setAttribute('onclick', `toggleComms(${id}, 'email_sent', ${isSent ? 0 : 1}, this)`);
                btn.innerHTML = `<span><i class="fa-solid fa-envelope me-1"></i>Email</span><i class="fa-solid ${isSent ? 'fa-check' : 'fa-xmark opacity-50'}"></i>`;
                atsShowToast(isSent ? 'Email marked as SENT' : 'Email marked as NOT SENT');
            } else if (field === 'sms_sent') {
                btn.className = `btn btn-xs d-inline-flex align-items-center justify-content-between px-2 py-0 border ${isSent ? 'btn-primary text-white' : 'btn-light text-muted'}`;
                btn.setAttribute('onclick', `toggleComms(${id}, 'sms_sent', ${isSent ? 0 : 1}, this)`);
                btn.innerHTML = `<span><i class="fa-solid fa-comment-sms me-1"></i>SMS</span><i class="fa-solid ${isSent ? 'fa-check' : 'fa-xmark opacity-50'}"></i>`;
                atsShowToast(isSent ? 'SMS marked as SENT' : 'SMS marked as NOT SENT');
            }
        } else {
            Swal.fire('Update Failed', data.message || 'Could not update status', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Network connection issue', 'error');
    });
}

// Single Candidate Delete (Move to Trash)
function deleteCandidate(id, name) {
    Swal.fire({
        title: 'Move to Trash?',
        text: `Candidate "${name}" will be moved to Trash. You can restore it anytime from the Trash page.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Move to Trash',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('<?= $baseUrl ?>/api/ats/candidate-delete', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const row = document.getElementById(`candidateRow_${id}`);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    atsShowToast(res.message || 'Candidate moved to trash');
                } else {
                    Swal.fire('Error', res.message || 'Failed to move candidate to trash', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Bulk Move to Trash Candidates
function executeBulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        Swal.fire('No Selection', 'Please select at least one candidate.', 'warning');
        return;
    }

    Swal.fire({
        title: `Move ${ids.length} Candidate(s) to Trash?`,
        text: 'Selected candidates will be moved to Trash. You can restore them anytime from the Trash page.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `<i class="fa-solid fa-trash-can me-1"></i> Yes, Move to Trash`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('ids', ids.join(','));

            fetch('<?= $baseUrl ?>/api/ats/bulk-update', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Moved to Trash!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to move candidates to trash', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Preview CV in Modal
function previewCv(url, name) {
    document.getElementById('cvViewerTitle').textContent = 'Resume / CV: ' + name;
    document.getElementById('cvViewerFrame').src = url;
    document.getElementById('cvDownloadBtn').href = url;
    new bootstrap.Modal(document.getElementById('cvViewerModal')).show();
}

// Row Selection & Batch Actions
function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.row-select-box');
    boxes.forEach(b => b.checked = master.checked);
    handleRowSelect();
}

function handleRowSelect() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    
    if (selected.length > 0) {
        bar.classList.remove('d-none');
        bar.classList.add('d-flex');
        badge.textContent = selected.length;
    } else {
        bar.classList.add('d-none');
        bar.classList.remove('d-flex');
    }
}

function getSelectedIds() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    return Array.from(selected).map(cb => cb.value);
}

function openBulkUpdateModal(actionType) {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        Swal.fire('No Selection', 'Please select at least one candidate.', 'warning');
        return;
    }

    document.getElementById('bulkActionType').value = actionType;
    document.getElementById('bulkStatusSection').classList.add('d-none');
    document.getElementById('bulkAttendanceSection').classList.add('d-none');
    document.getElementById('bulkScheduleSection').classList.add('d-none');
    document.getElementById('bulkHiredSection').classList.add('d-none');
    document.getElementById('bulkCommunicationSection').classList.add('d-none');
    document.getElementById('bulkPositionDeptSection').classList.add('d-none');

    if (actionType === 'invitation_status') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Call Status (${ids.length} candidates)`;
        document.getElementById('bulkStatusSection').classList.remove('d-none');
    } else if (actionType === 'position_dept') {
        document.getElementById('bulkActionTitle').textContent = `Change Position & Department (${ids.length} candidates)`;
        document.getElementById('bulkPositionSelect').value = '';
        document.getElementById('bulkPositionOther').value = '';
        document.getElementById('bulkPositionOther').classList.add('d-none');
        document.getElementById('bulkDepartmentSelect').value = '';
        document.getElementById('bulkDepartmentOther').value = '';
        document.getElementById('bulkDepartmentOther').classList.add('d-none');
        document.getElementById('bulkPositionDeptSection').classList.remove('d-none');
    } else if (actionType === 'interview_attendance') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Attendance (${ids.length} candidates)`;
        document.getElementById('bulkAttendanceSection').classList.remove('d-none');
    } else if (actionType === 'schedule') {
        document.getElementById('bulkActionTitle').textContent = `Set Interview Schedule (${ids.length} candidates)`;
        document.getElementById('bulkInterviewDate').value = '';
        document.getElementById('bulkInterviewTime').value = '';
        document.getElementById('bulkInterviewLocation').value = '';
        document.getElementById('bulkScheduleSection').classList.remove('d-none');
    } else if (actionType === 'hired') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Hired Decision (${ids.length} candidates)`;
        document.getElementById('bulkHiredSection').classList.remove('d-none');
    } else if (actionType === 'communication') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Email & SMS Status (${ids.length} candidates)`;
        document.getElementById('bulkCommunicationSection').classList.remove('d-none');
    }

    new bootstrap.Modal(document.getElementById('bulkActionModal')).show();
}

function executeBulkUpdate() {
    const ids = getSelectedIds();
    const actionType = document.getElementById('bulkActionType').value;
    const formData = new FormData();
    formData.append('action', actionType);
    formData.append('ids', ids.join(','));

    if (actionType === 'invitation_status') {
        formData.append('invitation_status', document.getElementById('bulkStatusValue').value);
    } else if (actionType === 'position_dept') {
        const posSel = document.getElementById('bulkPositionSelect');
        const posOth = document.getElementById('bulkPositionOther');
        const depSel = document.getElementById('bulkDepartmentSelect');
        const depOth = document.getElementById('bulkDepartmentOther');

        let pos = (posSel.value === 'Other') ? posOth.value.trim() : posSel.value.trim();
        let dep = (depSel.value === 'Other') ? depOth.value.trim() : depSel.value.trim();

        if (!pos && !dep) {
            Swal.fire('No Selection', 'Please select at least a new Position or a new Department to update.', 'warning');
            return;
        }

        if (pos) {
            formData.append('position', pos);
            if (posSel.value === 'Other') formData.append('position_other', posOth.value.trim());
        }
        if (dep) {
            formData.append('department', dep);
            if (depSel.value === 'Other') formData.append('department_other', depOth.value.trim());
        }
    } else if (actionType === 'interview_attendance') {
        formData.append('interview_attendance', document.getElementById('bulkAttendanceValue').value);
    } else if (actionType === 'schedule') {
        const dVal = document.getElementById('bulkInterviewDate').value;
        const tVal = document.getElementById('bulkInterviewTime').value;
        const lVal = document.getElementById('bulkInterviewLocation').value;
        if (dVal !== '') formData.append('interview_date', dVal);
        if (tVal !== '') formData.append('interview_time', tVal);
        if (lVal !== '') formData.append('interview_location', lVal);
    } else if (actionType === 'hired') {
        formData.append('hired', document.getElementById('bulkHiredValue').value);
    } else if (actionType === 'communication') {
        const emailVal = document.getElementById('bulkEmailSentValue').value;
        const smsVal = document.getElementById('bulkSmsSentValue').value;
        if (emailVal !== '') formData.append('email_sent', emailVal);
        if (smsVal !== '') formData.append('sms_sent', smsVal);
    }

    fetch('<?= $baseUrl ?>/api/ats/bulk-update', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(res => {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', res.message || 'Bulk update failed', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

function atsShowToast(msg) {
    try {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: msg,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: false,
                backdrop: false
            });
        }
    } catch (e) {
        console.warn('Toast display failed:', e);
    }
}

function getAtsExportUrl(endpoint) {
    const filterForm = document.getElementById('filterForm');
    let url = '<?= $baseUrl ?>/' + endpoint;
    if (filterForm) {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value && value.toString().trim() !== '') {
                params.append(key, value.toString().trim());
            }
        }
        const queryString = params.toString();
        if (queryString) {
            url += '?' + queryString;
        }
    }
    return url;
}

function exportAtsExcel() {
    window.location.href = getAtsExportUrl('ats-export-excel');
}

function exportAtsCsv() {
    window.location.href = getAtsExportUrl('ats-export-csv');
}

function exportAtsPdf() {
    window.location.href = getAtsExportUrl('ats-export-pdf');
}

// Single Candidate Interview Schedule Modal Handlers
function openSingleScheduleModal(id, name, date, time, location) {
    document.getElementById('modalCandidateId').value = id;
    document.getElementById('modalCandidateNameTitle').textContent = name;
    document.getElementById('modalInterviewDate').value = date || '';
    document.getElementById('modalInterviewTime').value = time || '';
    
    const locSelect = document.getElementById('modalInterviewLocation');
    locSelect.value = location || '';
    if (location && locSelect.value !== location) {
        let opt = document.createElement('option');
        opt.value = location;
        opt.textContent = location;
        opt.selected = true;
        locSelect.appendChild(opt);
    }
    
    new bootstrap.Modal(document.getElementById('singleScheduleModal')).show();
}

function saveSingleSchedule() {
    const id = document.getElementById('modalCandidateId').value;
    const date = document.getElementById('modalInterviewDate').value;
    const time = document.getElementById('modalInterviewTime').value;
    const location = document.getElementById('modalInterviewLocation').value;

    const btn = document.getElementById('btnSaveSingleSchedule');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData();
    formData.append('action', 'schedule');
    formData.append('ids', id);
    formData.append('interview_date', date);
    formData.append('interview_time', time);
    formData.append('interview_location', location);

    fetch('<?= $baseUrl ?>/api/ats/bulk-update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid server response');
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        if (res.success) {
            const modalEl = document.getElementById('singleScheduleModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            
            atsShowToast('Schedule updated successfully!');
            setTimeout(() => window.location.reload(), 600);
        } else {
            Swal.fire('Error', res.message || 'Failed to update schedule', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Quick CV Upload Handlers
function openQuickCvModal(id, name) {
    document.getElementById('cvModalCandidateId').value = id;
    document.getElementById('cvModalCandidateName').textContent = name;
    document.getElementById('cvSourceFile').checked = true;
    document.getElementById('quickCvFileSection').classList.remove('d-none');
    document.getElementById('quickCvDriveSection').classList.add('d-none');
    document.getElementById('quickCvFileInput').value = '';
    document.getElementById('quickCvDriveInput').value = '';

    new bootstrap.Modal(document.getElementById('quickCvUploadModal')).show();
}

function toggleQuickCvSource(type) {
    const fileSec = document.getElementById('quickCvFileSection');
    const driveSec = document.getElementById('quickCvDriveSection');
    if (type === 'drive') {
        fileSec.classList.add('d-none');
        driveSec.classList.remove('d-none');
    } else {
        fileSec.classList.remove('d-none');
        driveSec.classList.add('d-none');
    }
}

function submitQuickCvUpload(e) {
    e.preventDefault();
    const form = document.getElementById('quickCvUploadForm');
    const formData = new FormData(form);

    const btn = document.getElementById('btnSubmitQuickCv');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';

    fetch('<?= $baseUrl ?>/api/ats/upload-cv', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid response from server');
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload CV';
        if (res.success) {
            const modalEl = document.getElementById('quickCvUploadModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            atsShowToast(res.message || 'CV uploaded successfully!');
            setTimeout(() => location.reload(), 600);
        } else {
            Swal.fire('Error', res.message || 'Failed to upload CV', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload CV';
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

// =========================================================================
// Quick Contact Details (Name, Phone, Email) Modal Handlers
// =========================================================================
function openQuickContactModal(id, name, phone, email) {
    document.getElementById('quickContactCandidateId').value = id;
    document.getElementById('quickContactName').value = name || '';
    document.getElementById('quickContactPhone').value = phone || '';
    document.getElementById('quickContactEmail').value = email || '';
    new bootstrap.Modal(document.getElementById('quickContactModal')).show();
}

function saveQuickContact() {
    const id = document.getElementById('quickContactCandidateId').value;
    const name = document.getElementById('quickContactName').value.trim();
    const phone = document.getElementById('quickContactPhone').value.trim();
    const email = document.getElementById('quickContactEmail').value.trim();

    if (!name || !phone) {
        Swal.fire('Required Fields', 'Candidate Name and Phone Number are required.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSaveQuickContact');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData();
    formData.append('id', id);
    formData.append('type', 'contact');
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('email', email);

    fetch('<?= $baseUrl ?>/api/ats/quick-edit-details', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned invalid response');
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        if (res.success) {
            // Update table DOM directly
            const nameEl = document.querySelector('.candidate-name-display-' + id);
            if (nameEl) nameEl.textContent = res.data.name;

            const phoneEl = document.querySelector('.candidate-phone-display-' + id);
            if (phoneEl) phoneEl.textContent = res.data.phone;

            const phoneLink = document.querySelector('.candidate-phone-link-' + id);
            if (phoneLink) phoneLink.href = 'tel:' + encodeURIComponent(res.data.phone);

            const waLink = document.querySelector('.candidate-wa-link-' + id);
            if (waLink) waLink.href = 'https://wa.me/88' + res.data.wa_phone;

            const emailEl = document.querySelector('.candidate-email-display-' + id);
            if (emailEl) {
                if (res.data.email) {
                    emailEl.textContent = res.data.email;
                    emailEl.classList.remove('fst-italic');
                } else {
                    emailEl.innerHTML = '<span class="text-muted fst-italic">No email</span>';
                }
            }

            // Update onclick for Candidate Details button
            const contactBtn = document.querySelector(`button[onclick*="openQuickContactModal(${id},"]`);
            if (contactBtn) {
                const escName = res.data.name.replace(/'/g, "\\'");
                const escPhone = res.data.phone.replace(/'/g, "\\'");
                const escEmail = (res.data.email || '').replace(/'/g, "\\'");
                contactBtn.setAttribute('onclick', `openQuickContactModal(${id}, '${escName}', '${escPhone}', '${escEmail}')`);
            }

            // Close modal
            const modalEl = document.getElementById('quickContactModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            atsShowToast(res.message || 'Candidate details updated successfully');
        } else {
            Swal.fire('Error', res.message || 'Failed to update candidate details', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        Swal.fire('Error', err.message || 'Network error while updating candidate details', 'error');
    });
}

// =========================================================================
// Quick Position & Department Modal Handlers
// =========================================================================
function toggleQuickOther(selectId, inputId) {
    const sel = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    if (sel.value === 'Other') {
        input.classList.remove('d-none');
        input.focus();
    } else {
        input.classList.add('d-none');
        input.value = '';
    }
}

function openQuickPositionModal(id, name, position, department) {
    document.getElementById('quickPositionCandidateId').value = id;
    document.getElementById('quickPositionCandidateTitle').textContent = name;

    const posSelect = document.getElementById('quickPositionSelect');
    const posOther = document.getElementById('quickPositionOther');
    posSelect.value = position || '';
    if (position && posSelect.value !== position) {
        let found = false;
        for (let opt of posSelect.options) {
            if (opt.value.toLowerCase() === position.toLowerCase()) {
                posSelect.value = opt.value;
                found = true;
                break;
            }
        }
        if (!found) {
            posSelect.value = 'Other';
            posOther.value = position;
            posOther.classList.remove('d-none');
        } else {
            posOther.classList.add('d-none');
        }
    } else {
        posOther.classList.add('d-none');
        posOther.value = '';
    }

    const deptSelect = document.getElementById('quickDepartmentSelect');
    const deptOther = document.getElementById('quickDepartmentOther');
    deptSelect.value = department || '';
    if (department && deptSelect.value !== department) {
        let found = false;
        for (let opt of deptSelect.options) {
            if (opt.value.toLowerCase() === department.toLowerCase()) {
                deptSelect.value = opt.value;
                found = true;
                break;
            }
        }
        if (!found) {
            deptSelect.value = 'Other';
            deptOther.value = department;
            deptOther.classList.remove('d-none');
        } else {
            deptOther.classList.add('d-none');
        }
    } else {
        deptOther.classList.add('d-none');
        deptOther.value = '';
    }

    new bootstrap.Modal(document.getElementById('quickPositionModal')).show();
}

function saveQuickPosition() {
    const id = document.getElementById('quickPositionCandidateId').value;
    const posSelect = document.getElementById('quickPositionSelect');
    const posOther = document.getElementById('quickPositionOther');
    const deptSelect = document.getElementById('quickDepartmentSelect');
    const deptOther = document.getElementById('quickDepartmentOther');

    let position = (posSelect.value === 'Other') ? posOther.value.trim() : posSelect.value.trim();
    let department = (deptSelect.value === 'Other') ? deptOther.value.trim() : deptSelect.value.trim();

    if (!position || !department) {
        Swal.fire('Required Fields', 'Position and Department are required.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSaveQuickPosition');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData();
    formData.append('id', id);
    formData.append('type', 'position_dept');
    formData.append('position', position);
    if (posSelect.value === 'Other') {
        formData.append('position_other', posOther.value.trim());
    }
    formData.append('department', department);
    if (deptSelect.value === 'Other') {
        formData.append('department_other', deptOther.value.trim());
    }

    fetch('<?= $baseUrl ?>/api/ats/quick-edit-details', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned invalid response');
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        if (res.success) {
            // Update table DOM directly
            const posEl = document.querySelector('.candidate-pos-display-' + id);
            if (posEl) posEl.textContent = res.data.position;

            const deptEl = document.querySelector('.candidate-dept-display-' + id);
            if (deptEl) deptEl.textContent = res.data.department;

            // Update onclick for Position & Dept button
            const posBtn = document.querySelector(`button[onclick*="openQuickPositionModal(${id},"]`);
            if (posBtn) {
                const nameEl = document.querySelector('.candidate-name-display-' + id);
                const currentName = (nameEl ? nameEl.textContent : '').replace(/'/g, "\\'");
                const escPos = res.data.position.replace(/'/g, "\\'");
                const escDept = res.data.department.replace(/'/g, "\\'");
                posBtn.setAttribute('onclick', `openQuickPositionModal(${id}, '${currentName}', '${escPos}', '${escDept}')`);
            }

            // Close modal
            const modalEl = document.getElementById('quickPositionModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            atsShowToast(res.message || 'Position & Department updated successfully');
        } else {
            Swal.fire('Error', res.message || 'Failed to update Position & Department', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        Swal.fire('Error', err.message || 'Network error while updating Position & Department', 'error');
    });
}

// =========================================================================
// Quick Remarks Modal Handlers
// =========================================================================
function openQuickRemarksModal(btn) {
    const id = btn.dataset.id;
    const name = btn.dataset.name || '';
    const remarks = btn.dataset.remarks || '';
    document.getElementById('quickRemarksCandidateId').value = id;
    document.getElementById('quickRemarksCandidateTitle').textContent = name;
    document.getElementById('quickRemarksText').value = remarks;
    new bootstrap.Modal(document.getElementById('quickRemarksModal')).show();
}

function saveQuickRemarks() {
    const id = document.getElementById('quickRemarksCandidateId').value;
    const remarks = document.getElementById('quickRemarksText').value.trim();

    const btn = document.getElementById('btnSaveQuickRemarks');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData();
    formData.append('id', id);
    formData.append('field', 'remarks');
    formData.append('value', remarks);

    fetch('<?= $baseUrl ?>/api/ats/quick-update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned invalid response');
        }
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        if (res.success) {
            // Update table DOM directly
            const displayEl = document.querySelector('.candidate-remarks-display-' + id);
            const wrapperEl = document.querySelector('.candidate-remarks-wrapper-' + id);
            if (displayEl) {
                if (remarks) {
                    displayEl.textContent = remarks;
                    displayEl.className = 'candidate-remarks-display-' + id + ' text-dark fw-medium';
                } else {
                    displayEl.textContent = 'No remarks';
                    displayEl.className = 'candidate-remarks-display-' + id + ' text-muted fst-italic';
                }
            }
            if (wrapperEl) {
                wrapperEl.setAttribute('title', remarks);
            }

            // Update data-remarks on button so next edit has the latest remarks (including newlines)
            const remarksBtn = document.querySelector(`.btn-quick-remarks-${id}`);
            if (remarksBtn) {
                remarksBtn.dataset.remarks = remarks;
            }

            // Close modal
            const modalEl = document.getElementById('quickRemarksModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            atsShowToast('Remarks updated successfully');
        } else {
            Swal.fire('Error', res.message || 'Failed to update remarks', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
        Swal.fire('Error', err.message || 'Network error while updating remarks', 'error');
    });
}

// =========================================================================
// Multi-Select Checkbox Dropdown Handlers
// =========================================================================
function atsUpdateDropdownText(dropdownId, placeholder) {
    const container = document.getElementById('dropdown_' + dropdownId);
    if (!container) return;
    const btn = document.getElementById('btn_' + dropdownId);
    if (!btn) return;
    const textEl = btn.querySelector('.ats-multi-btn-text');
    let badgeEl = btn.querySelector('.badge');

    const checkedBoxes = container.querySelectorAll('.ats-dropdown-items-list input[type="checkbox"]:checked');
    const count = checkedBoxes.length;

    if (count === 0) {
        textEl.textContent = placeholder;
        textEl.classList.remove('text-dark', 'fw-semibold');
        textEl.classList.add('text-muted');
        if (badgeEl) badgeEl.remove();
    } else if (count === 1) {
        const label = checkedBoxes[0].closest('.form-check').querySelector('label').textContent.trim();
        textEl.textContent = label;
        textEl.classList.remove('text-muted');
        textEl.classList.add('text-dark', 'fw-semibold');
        if (badgeEl) badgeEl.remove();
    } else {
        const firstLabel = checkedBoxes[0].closest('.form-check').querySelector('label').textContent.trim();
        textEl.textContent = firstLabel + ' (+' + (count - 1) + ')';
        textEl.classList.remove('text-muted');
        textEl.classList.add('text-dark', 'fw-semibold');
        if (!badgeEl) {
            badgeEl = document.createElement('span');
            badgeEl.className = 'badge bg-primary rounded-pill px-1 ms-1';
            badgeEl.style.fontSize = '0.68rem';
            btn.appendChild(badgeEl);
        }
        badgeEl.textContent = count;
    }
}

function atsSelectAllDropdown(dropdownId, selectAll) {
    const container = document.getElementById('dropdown_' + dropdownId);
    if (!container) return;
    const checkboxes = container.querySelectorAll('.ats-dropdown-items-list input[type="checkbox"]');
    checkboxes.forEach(cb => {
        if (cb.closest('.form-check').style.display !== 'none') {
            cb.checked = selectAll;
        }
    });
    const btn = document.getElementById('btn_' + dropdownId);
    const placeholder = btn.dataset.placeholder || 'Select';
    atsUpdateDropdownText(dropdownId, placeholder);
}

function atsFilterDropdownSearch(input, dropdownId) {
    const q = input.value.toLowerCase().trim();
    const container = document.getElementById('dropdown_' + dropdownId);
    if (!container) return;
    const items = container.querySelectorAll('.ats-dropdown-items-list .ats-dropdown-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });
}

function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
