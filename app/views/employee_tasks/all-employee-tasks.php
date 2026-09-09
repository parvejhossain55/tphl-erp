<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary: var(--primary-green, #16a34a);
        --primary-hover: #15803d;
        --primary-dark: #166534;
        --accent: var(--primary-green, #16a34a);
        --accent-dim: #f0fdf4;
        --accent-light: #dcfce7;
        --accent-border: #bbf7d0;
        --bg-main: #f8fafc;
        --paper: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --rule: #e2e8f0;
        
        --green: var(--primary-green, #16a34a);
        --green-dim: #dcfce7;
        --gold: #f59e0b;
        --gold-dim: #fef3c7;
        --blue: #3b82f6;
        --blue-dim: #dbeafe;
        
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --font-main: 'Inter', sans-serif;
    }

    body {
        background-color: var(--bg-main) !important;
        font-family: var(--font-main) !important;
        color: var(--text-main);
    }

    .card__wrapper {
        background: var(--paper);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        margin-bottom: 24px;
        border: 1px solid var(--rule);
        overflow: hidden;
    }

    /* Header */
    .ledger-header {
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        background: var(--paper);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .brand-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary), #22c55e);
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 22px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    }

    .brand h1 {
        font-family: var(--font-main);
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1.2;
        margin: 0;
        color: var(--text-main);
    }

    .brand h1 span {
        display: block;
        font-size: 0.75rem;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        margin-bottom: 4px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .header-meta {
        text-align: right;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .header-meta strong {
        display: block;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 2px;
    }

    /* Filter Bar */
    .filter-bar {
        background: var(--paper);
        border-radius: var(--radius);
        border: 1px solid var(--rule);
        box-shadow: var(--shadow-sm);
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .filter-form-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-item-dept {
        width: 210px;
        min-width: 190px;
        max-width: 230px;
        flex: 1 1 210px;
    }

    .filter-item-emp {
        width: 240px;
        min-width: 210px;
        max-width: 260px;
        flex: 1.2 1 240px;
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }

    .filter-control {
        font-family: var(--font-main);
        font-size: 0.88rem;
        font-weight: 500;
        border: 1px solid var(--rule);
        border-radius: 8px;
        padding: 7px 12px;
        color: var(--text-main);
        outline: none;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
        min-height: 38px;
    }

    .filter-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        background: white;
    }

    /* Select2 Custom Styling matching --primary-green */
    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        background-color: #f8fafc !important;
        border: 1px solid var(--rule) !important;
        border-radius: 8px !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease !important;
        box-shadow: none !important;
    }

    .select2-container .select2-selection--single:hover {
        border-color: #cbd5e1 !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15) !important;
        background-color: #ffffff !important;
        outline: none !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main) !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        padding-left: 10px !important;
        padding-right: 24px !important;
        line-height: 36px !important;
        font-family: var(--font-main) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 6px !important;
        top: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: var(--text-muted) transparent transparent transparent !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent var(--primary) transparent !important;
    }

    .select2-dropdown {
        border: 1px solid var(--accent-border) !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden !important;
        z-index: 1060 !important;
        background: #ffffff !important;
        max-width: 280px !important;
    }

    .select2-search--dropdown {
        padding: 6px 8px !important;
        background: #f8fafc !important;
        border-bottom: 1px solid var(--rule) !important;
    }

    .select2-search--dropdown .select2-search__field {
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        padding: 4px 8px !important;
        height: 32px !important;
        font-size: 0.82rem !important;
        outline: none !important;
        width: 100% !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        font-family: var(--font-main) !important;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2) !important;
    }

    .select2-results__options {
        max-height: 220px !important;
        padding: 3px !important;
    }

    .select2-container--default .select2-results__option {
        font-size: 0.82rem !important;
        padding: 6px 10px !important;
        color: var(--text-main) !important;
        border-radius: 6px !important;
        margin-bottom: 2px !important;
        font-family: var(--font-main) !important;
        transition: background-color 0.15s ease !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary) !important;
        color: #ffffff !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: var(--accent-dim) !important;
        color: var(--primary) !important;
        font-weight: 600 !important;
    }

    .filter-checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.83rem;
        font-weight: 600;
        color: var(--text-main);
        cursor: pointer;
        padding: 7px 12px;
        background: #f1f5f9;
        border: 1px solid var(--rule);
        border-radius: 8px;
        transition: all 0.2s;
        user-select: none;
        min-height: 38px;
    }

    .filter-checkbox-label:hover {
        background: #e2e8f0;
    }

    .filter-checkbox-label input[type="checkbox"] {
        accent-color: var(--primary);
        width: 15px;
        height: 15px;
        cursor: pointer;
    }

    .filter-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 2px;
        align-self: flex-end;
        height: 38px;
    }

    .filter-divider span {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--text-muted);
        background: #e2e8f0;
        padding: 2px 7px;
        border-radius: 10px;
        letter-spacing: 0.05em;
    }

    .filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .btn-filter-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: var(--font-main);
        font-size: 0.82rem;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none !important;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 38px;
        border: 1px solid transparent;
        line-height: 1;
    }

    .btn-apply {
        background: var(--primary);
        color: white !important;
        box-shadow: var(--shadow-sm);
    }

    .btn-apply:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.28);
    }

    .btn-all-time {
        background: var(--accent-dim);
        color: var(--primary) !important;
        border-color: var(--accent-border);
    }

    .btn-all-time:hover,
    .btn-all-time.active {
        background: var(--primary);
        color: white !important;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    }

    .btn-reset {
        background: white;
        color: var(--text-muted) !important;
        border-color: var(--rule);
    }

    .btn-reset:hover {
        background: #f1f5f9;
        color: var(--text-main) !important;
        border-color: #cbd5e1;
    }

    .filter-status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        border-top: 1px solid var(--rule);
        padding-top: 12px;
        margin-top: 2px;
    }

    .filter-pills {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 500;
        padding: 5px 14px;
        border-radius: 20px;
        border: 1px solid var(--rule);
        background: #f8fafc;
        color: var(--text-muted);
    }
    
    .stat-pill strong {
        font-weight: 600;
        color: var(--text-main);
        font-size: 0.9rem;
    }

    /* Single Column Layout (No Right Sidebar Form) */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }

    /* Sheet Panel */
    .sheet-panel {
        background: var(--paper);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--rule);
        overflow: hidden;
    }

    .sheet-toolbar {
        padding: 16px 24px;
        border-bottom: 1px solid var(--rule);
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        flex-wrap: wrap;
    }

    .toolbar-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
        margin-right: auto;
    }

    .filter-indicator-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--accent-dim);
        color: var(--primary);
        border: 1px solid var(--accent-border);
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid var(--rule);
        background: white;
        display: grid;
        place-items: center;
        font-size: 16px;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .btn-icon:hover {
        background: var(--accent-dim);
        color: var(--primary);
        border-color: var(--accent-border);
    }

    .btn-export-excel {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--primary);
        color: #ffffff !important;
        font-family: var(--font-main);
        font-size: 0.82rem;
        font-weight: 600;
        padding: 7px 14px;
        border-radius: 8px;
        text-decoration: none !important;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
        border: none;
        line-height: 1.2;
    }

    .btn-export-excel:hover {
        background: var(--primary-hover);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.28);
        transform: translateY(-1px);
    }

    /* Spreadsheet Table */
    .table__wrapper {
        overflow-x: auto;
        padding: 0;
    }

    .table__wrapper table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        margin: 0;
    }

    .table__wrapper thead th {
        background: #f8fafc;
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        padding: 14px 20px;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid var(--rule);
        letter-spacing: 0.05em;
    }
    
    .table__wrapper thead th:first-child {
        width: 40px;
        text-align: center;
    }

    .table__wrapper tbody tr {
        border-bottom: 1px solid var(--rule);
        transition: all 0.2s;
    }

    .table__wrapper tbody tr:last-child {
        border-bottom: none;
    }

    .table__wrapper tbody tr:hover {
        background: #f8fafc;
    }

    .table__wrapper tbody td {
        padding: 14px 20px;
        vertical-align: middle;
        color: var(--text-main);
        border: none;
    }
    
    .table__wrapper tbody td:first-child {
        text-align: center;
        color: var(--text-muted);
        font-size: 0.8rem;
        font-weight: 500;
    }

    .row-emp-name {
        font-weight: 600;
        color: var(--text-main);
        display: block;
    }

    .row-emp-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .row-emp-code {
        font-family: monospace;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--primary);
        background: var(--accent-dim);
        border: 1px solid var(--accent-border);
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    .row-dept {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .row-date {
        font-weight: 600;
        color: var(--text-main);
        white-space: nowrap;
    }

    .row-hours {
        font-weight: 600;
        color: var(--text-main);
        white-space: nowrap;
    }

    .row-desc {
        max-width: 320px;
        color: var(--text-muted);
        line-height: 1.4;
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 20px;
        letter-spacing: 0.05em;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-done {
        color: #15803d;
        background: var(--green-dim);
        border-color: var(--accent-border);
    }

    .status-pending {
        color: #b45309;
        background: var(--gold-dim);
        border-color: #fde68a;
    }

    .status-wip {
        color: #1d4ed8;
        background: var(--blue-dim);
        border-color: #bfdbfe;
    }

    /* Empty state card when no search is performed */
    .empty-search-box {
        padding: 70px 24px;
        text-align: center;
        background: #ffffff;
    }

    .empty-search-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px auto;
        border-radius: 50%;
        background: var(--accent-dim);
        border: 1px solid var(--accent-border);
        display: grid;
        place-items: center;
        font-size: 28px;
        color: var(--primary);
    }

    .empty-search-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .empty-search-sub {
        font-size: 0.88rem;
        color: var(--text-muted);
        max-width: 480px;
        margin: 0 auto;
        line-height: 1.5;
    }

    /* Footer */
    .ledger-footer {
        background: var(--paper);
        border-radius: var(--radius);
        border: 1px solid var(--rule);
        padding: 20px 32px;
        display: flex;
        gap: 32px;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
        margin-top: 24px;
    }

    .footer-stat {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .footer-label {
        color: var(--text-muted);
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .footer-value {
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .footer-sep {
        width: 1px;
        height: 28px;
        background: var(--rule);
    }

    @media print {
        body {
            background: #fff !important;
        }
        
        body * {
            visibility: hidden;
        }

        #printable-area,
        #printable-area * {
            visibility: visible;
            color: #000 !important;
        }

        #printable-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        .filter-bar,
        .btn-icon,
        .btn-export-excel {
            display: none !important;
            visibility: hidden !important;
        }

        .table__wrapper table {
            font-size: 10pt !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .table__wrapper th,
        .table__wrapper td {
            border: 1px solid #ccc !important;
            padding: 8px !important;
            color: #000 !important;
        }

        .table__wrapper th {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>

<div id="printable-area">
<!-- Header -->
<div class="card__wrapper">
    <header class="ledger-header">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-journal-check"></i></div>
            <h1>
                <span>HR & Management Portal</span>
                All Employees Task Sheet
            </h1>
        </div>
        <div class="header-meta">
            <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'HR / Management') ?></strong>
            <span style="display:block; font-size:0.85rem; color:var(--text-muted); margin-bottom: 4px;">
                Role: <?= htmlspecialchars($_SESSION['user']['role_name'] ?? 'Management') ?>
            </span>
            <span>Date: <?= date('d M Y') ?></span>
        </div>
    </header>
</div>

<!-- Filter Bar -->
<?php
$totalTasks   = count($taskSheets);
$doneTasks    = count(array_filter($taskSheets, fn($t) => strtolower(trim($t['status'] ?? '')) === 'done'));
$pendingTasks = count(array_filter($taskSheets, fn($t) => strtolower(trim($t['status'] ?? '')) === 'pending'));
$totalHours   = array_sum(array_column($taskSheets, 'hours_spent'));
?>
<div class="filter-bar">
    <form method="GET" action="<?= $GLOBALS['base_url'] ?>/all-employee-tasks" id="filterForm" class="filter-form-wrapper">
        <input type="hidden" name="filter" value="1">

        <!-- Department Filter -->
        <div class="filter-item filter-item-dept">
            <label for="filterDept" class="filter-label"><i class="bi bi-building"></i> Department / বিভাগ</label>
            <select name="department_id" id="filterDept" class="filter-control select2-searchable">
                <option value="all">All Departments (সকল বিভাগ)</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= ((string)($filter['department_id'] ?? '') === (string)$dept['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Employee Filter (Active employees with employee_id only) -->
        <div class="filter-item filter-item-emp">
            <label for="filterEmployee" class="filter-label"><i class="bi bi-person-badge"></i> Employee / কর্মী</label>
            <select name="employee_id" id="filterEmployee" class="filter-control select2-searchable">
                <option value="all">All Employees (সকল কর্মী)</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" 
                            data-dept="<?= $emp['department_id'] ?>"
                            <?= ((string)($filter['employee_id'] ?? '') === (string)$emp['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Year Select (2025 - 2035) -->
        <div class="filter-item">
            <label for="filterYear" class="filter-label"><i class="bi bi-calendar"></i> Year / বছর</label>
            <select name="year" id="filterYear" class="filter-control">
                <option value="all" <?= ($filter['year'] === 'all') ? 'selected' : '' ?>>All Years (সব বছর)</option>
                <?php for ($y = 2025; $y <= 2035; $y++): ?>
                    <option value="<?= $y ?>" <?= ((string)$filter['year'] === (string)$y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Month Select -->
        <div class="filter-item">
            <label for="filterMonth" class="filter-label"><i class="bi bi-calendar3"></i> Month / মাস</label>
            <select name="month" id="filterMonth" class="filter-control">
                <option value="all" <?= ($filter['month'] === 'all') ? 'selected' : '' ?>>All Months (সব মাস)</option>
                <?php
                $monthsList = [
                    '01' => '01 - Jan (জানুয়ারি)',
                    '02' => '02 - Feb (ফেব্রুয়ারি)',
                    '03' => '03 - Mar (মার্চ)',
                    '04' => '04 - Apr (এপ্রিল)',
                    '05' => '05 - May (মে)',
                    '06' => '06 - Jun (জুন)',
                    '07' => '07 - Jul (জুলাই)',
                    '08' => '08 - Aug (আগস্ট)',
                    '09' => '09 - Sep (সেপ্টেম্বর)',
                    '10' => '10 - Oct (অক্টোবর)',
                    '11' => '11 - Nov (নভেম্বর)',
                    '12' => '12 - Dec (ডিসেম্বর)',
                ];
                foreach ($monthsList as $mCode => $mText): ?>
                    <option value="<?= $mCode ?>" <?= ($filter['month'] === $mCode) ? 'selected' : '' ?>><?= $mText ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- All Months Checkbox Option -->
        <div class="filter-item">
            <label class="filter-checkbox-label" title="নির্বাচিত বছরের সকল মাসের ডাটা একসাথে দেখতে টিক দিন">
                <input type="checkbox" name="all_months" id="allMonthsCheck" value="1" <?= !empty($filter['all_months']) ? 'checked' : '' ?> onchange="toggleAllMonths(this.checked)">
                <span>All Months / সব মাস</span>
            </label>
        </div>

        <div class="filter-divider">
            <span>OR</span>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-item">
            <label for="dateFrom" class="filter-label"><i class="bi bi-calendar-event"></i> From / শুরু</label>
            <input type="date" name="date_from" id="dateFrom" class="filter-control"
                   value="<?= htmlspecialchars($filter['date_from'] ?? '') ?>"
                   onchange="onDateRangeChange()">
        </div>

        <div class="filter-item">
            <label for="dateTo" class="filter-label"><i class="bi bi-calendar-check"></i> To / শেষ</label>
            <input type="date" name="date_to" id="dateTo" class="filter-control"
                   value="<?= htmlspecialchars($filter['date_to'] ?? '') ?>"
                   onchange="onDateRangeChange()">
        </div>

        <!-- Action Buttons -->
        <div class="filter-actions">
            <button type="submit" class="btn-filter-action btn-apply" title="ফিল্টার প্রয়োগ করুন">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="<?= $GLOBALS['base_url'] ?>/all-employee-tasks?all=1" class="btn-filter-action btn-all-time <?= ($filter['mode'] === 'all') ? 'active' : '' ?>" title="শুরু থেকে আজ পর্যন্ত সকল টাস্ক একসাথে দেখুন">
                <i class="bi bi-collection-play"></i> All Time (সব ডাটা)
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/all-employee-tasks" class="btn-filter-action btn-reset" title="রিসেট করুন">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        </div>
    </form>

    <!-- Filter Status Row -->
    <div class="filter-status-row">
        <div class="filter-pills">
            <div class="stat-pill" title="মোট টাস্ক">
                Total Tasks: <strong><?= $totalTasks ?></strong>
            </div>
            <div class="stat-pill" title="সম্পন্ন টাস্ক">
                Done: <strong style="color:var(--green);"><?= $doneTasks ?></strong>
            </div>
            <div class="stat-pill" title="চলমান / অপেক্ষমাণ টাস্ক">
                Pending: <strong style="color:var(--gold);"><?= $pendingTasks ?></strong>
            </div>
            <div class="stat-pill" title="মোট ব্যয়িত ঘণ্টা">
                Total Hours: <strong style="color:var(--primary);"><?= number_format($totalHours, 2) ?>h</strong>
            </div>
        </div>

        <?php if ($hasSearched && !empty($taskSheets)): ?>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/all-employee-tasks-export?<?= $exportQueryString ?>" 
               class="btn-export-excel" 
               title="বর্তমান ফিল্টারের টাস্ক শিট এক্সেলে ডাউনলোড করুন">
                <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Main Grid (Single Column, No Right Form Sidebar) -->
<div class="main-grid">
    <div class="sheet-panel">
        <div class="sheet-toolbar">
            <span class="toolbar-label">
                Task Records — <?= htmlspecialchars($filter['period_label']) ?>
            </span>

            <?php if (!empty($selectedDeptName)): ?>
                <span class="filter-indicator-badge">
                    <i class="bi bi-building"></i> Dept: <?= htmlspecialchars($selectedDeptName) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($selectedEmpName)): ?>
                <span class="filter-indicator-badge">
                    <i class="bi bi-person"></i> <?= htmlspecialchars($selectedEmpName) ?>
                </span>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <?php if ($hasSearched && !empty($taskSheets)): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/all-employee-tasks-export?<?= $exportQueryString ?>" 
                       class="btn-export-excel" 
                       title="Download Excel">
                        <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
                    </a>
                    <button class="btn-icon" title="Print" onclick="window.print()"><i class="bi bi-printer"></i></button>
                <?php endif; ?>
            </div>
        </div>

        <div class="table__wrapper table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Emp ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Task Description</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$hasSearched): ?>
                        <!-- Default view: No rows displayed initially -->
                        <tr>
                            <td colspan="9" style="padding: 0; border: none;">
                                <div class="empty-search-box">
                                    <div class="empty-search-icon">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <div class="empty-search-title">Select Department or Employee to View Tasks</div>
                                    <div class="empty-search-sub">
                                        ডিফল্টভাবে কোনো রেকর্ড লোড করা নেই। টাস্ক দেখার জন্য অনুগ্রহ করে উপরের ফিল্টার থেকে ডিপার্টমেন্ট অথবা নির্দিষ্ট কর্মী নির্বাচন করে <strong>Filter</strong> বাটনে ক্লিক করুন।
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php elseif (empty($taskSheets)): ?>
                        <!-- Filter applied but no records found -->
                        <tr class="empty-row">
                            <td colspan="9" style="padding: 60px 20px; text-align: center; color: var(--text-muted);">
                                <i class="bi bi-journal-x" style="font-size:2rem; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                                <span style="font-weight: 600; font-size: 1rem; color: #475569;">No tasks found</span>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                                    নির্বাচিত ফিল্টার (<?= htmlspecialchars($filter['period_label']) ?>) অনুযায়ী কোনো টাস্ক পাওয়া যায়নি।
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Tasks listing -->
                        <?php foreach ($taskSheets as $i => $task): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="row-date"><?= date('d M Y', strtotime($task['work_date'])) ?></td>
                                <td>
                                    <span class="row-emp-code"><?= htmlspecialchars($task['emp_code'] ?? '—') ?></span>
                                </td>
                                <td>
                                    <span class="row-emp-name"><?= htmlspecialchars($task['emp_name'] ?? '—') ?></span>
                                    <?php if (!empty($task['designation_name'])): ?>
                                        <span class="row-emp-sub"><?= htmlspecialchars($task['designation_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="row-dept"><?= htmlspecialchars($task['department_name'] ?? '—') ?></span>
                                </td>
                                <td class="row-desc"><?= htmlspecialchars($task['task_description']) ?></td>
                                <td class="row-hours">
                                    <?= number_format($task['hours_spent'], 2) ?><span style="color:#bbb;font-size:.62rem">h</span>
                                </td>
                                <td>
                                    <?php
                                    $rawStatus = $task['status'] ?? '';
                                    $s   = strtolower(str_replace(' ', '_', trim($rawStatus)));
                                    $cls = match ($s) {
                                        'done' => 'status-done',
                                        'pending' => 'status-pending',
                                        default => 'status-wip'
                                    };
                                    $lbl = match ($s) {
                                        'done' => 'Done',
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        default => ucfirst($rawStatus)
                                    };
                                    ?>
                                    <span class="status-badge <?= $cls ?>"><?= $lbl ?></span>
                                </td>
                                <td class="row-desc" style="max-width:180px"><?= htmlspecialchars($task['remarks'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($hasSearched && !empty($taskSheets)): ?>
<!-- Footer -->
<footer class="ledger-footer">
    <div class="footer-stat">
        <span class="footer-label">Period</span>
        <span class="footer-value"><?= htmlspecialchars($filter['period_label']) ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Total Entries</span>
        <span class="footer-value"><?= $totalTasks ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Total Hours</span>
        <span class="footer-value"><?= number_format($totalHours, 2) ?>h</span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Completed</span>
        <span class="footer-value"><?= $doneTasks ?> / <?= $totalTasks ?></span>
    </div>
</footer>
<?php endif; ?>

</div><!-- /printable-area -->

<script>
// Cache all initial employee options for fast in-memory department filtering
let allEmpOptions = [];

function toggleAllMonths(isChecked) {
    const monthSelect = document.getElementById('filterMonth');
    if (isChecked) {
        monthSelect.value = 'all';
    } else {
        if (monthSelect.value === 'all') {
            const now = new Date();
            const currentM = String(now.getMonth() + 1).padStart(2, '0');
            monthSelect.value = currentM;
        }
    }
}

function onDepartmentChange(deptId) {
    const empSelect = document.getElementById('filterEmployee');
    if (!empSelect || allEmpOptions.length === 0) return;

    const currentSelectedVal = empSelect.value;
    empSelect.innerHTML = '';

    let hasSelectedVal = false;
    allEmpOptions.forEach(opt => {
        if (opt.value === 'all' || deptId === 'all' || !deptId || opt.dept === String(deptId)) {
            const isSelected = (opt.value === currentSelectedVal);
            const newOpt = new Option(opt.text, opt.value, false, isSelected);
            if (opt.dept) {
                newOpt.setAttribute('data-dept', opt.dept);
            }
            empSelect.appendChild(newOpt);
            if (isSelected) {
                hasSelectedVal = true;
            }
        }
    });

    if (!hasSelectedVal) {
        empSelect.value = 'all';
    }

    // If Select2 is active on employee select, notify it
    if (window.jQuery && typeof window.jQuery(empSelect).data('select2') !== 'undefined') {
        window.jQuery(empSelect).trigger('change.select2');
    }
}

function onDateRangeChange() {
    const df = document.getElementById('dateFrom')?.value;
    const dt = document.getElementById('dateTo')?.value;
    if (df || dt) {
        const allMonthsCheck = document.getElementById('allMonthsCheck');
        if (allMonthsCheck) allMonthsCheck.checked = false;
    }
}

function initSearchableSelects() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        setTimeout(initSearchableSelects, 50);
        return;
    }

    jQuery(function($) {
        // Cache employee options once at the very beginning
        const empSelectEl = document.getElementById('filterEmployee');
        if (empSelectEl && allEmpOptions.length === 0) {
            allEmpOptions = Array.from(empSelectEl.querySelectorAll('option')).map(opt => ({
                value: opt.value,
                text: opt.textContent.trim(),
                dept: opt.getAttribute('data-dept') || ''
            }));
        }

        // Initialize Select2 on Department with search
        $('#filterDept').select2({
            placeholder: "Search Department...",
            allowClear: false,
            width: '100%',
            dropdownAutoWidth: false
        });

        // Initialize Select2 on Employee with search
        $('#filterEmployee').select2({
            placeholder: "Search Employee...",
            allowClear: false,
            width: '100%',
            dropdownAutoWidth: false
        });

        // Auto-focus search box when Select2 dropdown opens
        $(document).on('select2:open', function() {
            setTimeout(function() {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 10);
        });

        // On Department change, cascade filter to employee dropdown
        $('#filterDept').on('change select2:select', function() {
            onDepartmentChange($(this).val());
        });

        // Initial department cascade filter if already selected
        const currentDept = $('#filterDept').val();
        if (currentDept && currentDept !== 'all') {
            onDepartmentChange(currentDept);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const empSelect = document.getElementById('filterEmployee');
    if (empSelect && allEmpOptions.length === 0) {
        allEmpOptions = Array.from(empSelect.querySelectorAll('option')).map(opt => ({
            value: opt.value,
            text: opt.textContent.trim(),
            dept: opt.getAttribute('data-dept') || ''
        }));
    }

    const currentDept = document.getElementById('filterDept')?.value;
    if (currentDept && currentDept !== 'all') {
        onDepartmentChange(currentDept);
    }

    const filterMonthEl = document.getElementById('filterMonth');
    const allMonthsCheck = document.getElementById('allMonthsCheck');
    const filterYearEl = document.getElementById('filterYear');
    const dateFromEl = document.getElementById('dateFrom');
    const dateToEl = document.getElementById('dateTo');

    if (filterMonthEl) {
        filterMonthEl.addEventListener('change', function() {
            if (allMonthsCheck) {
                allMonthsCheck.checked = (this.value === 'all');
            }
            if (dateFromEl) dateFromEl.value = '';
            if (dateToEl) dateToEl.value = '';
        });
    }

    if (filterYearEl) {
        filterYearEl.addEventListener('change', function() {
            if (dateFromEl) dateFromEl.value = '';
            if (dateToEl) dateToEl.value = '';
        });
    }

    initSearchableSelects();
});

// Also trigger on window load to ensure select2 script from footer is ready
window.addEventListener('load', initSearchableSelects);
</script>
