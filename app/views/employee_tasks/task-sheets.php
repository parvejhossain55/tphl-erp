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

    /* Page Wrapper overrides */
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
        padding: 18px 24px;
        margin-bottom: 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
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
        box-shadow: 0 0 0 3px var(--accent-dim);
        background: white;
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

    /* Main Grid */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
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
    }

    .toolbar-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
        margin-right: auto;
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
        background: var(--bg-main);
        color: var(--primary);
        border-color: #cbd5e1;
    }

    .btn-export-excel {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #10b981;
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
        background: #059669;
        color: #ffffff !important;
        box-shadow: var(--shadow-md);
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
        padding: 14px 24px;
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
        cursor: pointer;
    }

    .table__wrapper tbody tr:last-child {
        border-bottom: none;
    }

    .table__wrapper tbody tr:hover,
    .table__wrapper tbody tr.selected {
        background: var(--accent-dim);
    }

    .table__wrapper tbody td {
        padding: 16px 24px;
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

    .row-date {
        font-weight: 600;
        color: var(--text-main);
    }

    .row-hours {
        font-weight: 600;
        color: var(--text-main);
    }

    .row-desc {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-muted);
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
    }

    .status-done {
        color: #047857;
        background: var(--green-dim);
        border-color: #a7f3d0;
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

    .action-btn {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 16px;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        color: var(--primary);
        background: var(--accent-dim);
    }

    .empty-row td {
        padding: 60px 20px !important;
        text-align: center !important;
        color: var(--text-muted) !important;
        font-size: 0.95rem !important;
        cursor: default !important;
    }

    /* Form Panel */
    .form-panel {
        background: var(--paper);
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        border: 1px solid var(--rule);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: sticky;
        top: 24px;
        margin-bottom: 24px;
    }

    .form-header {
        padding: 24px;
        background: linear-gradient(135deg, var(--primary), #22c55e);
        color: white;
        transition: background 0.3s ease;
    }

    .form-header h2 {
        font-family: var(--font-main);
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .form-header p {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.85);
        margin-top: 4px;
        margin-bottom: 0;
    }
    
    .form-panel.mode-new .form-header {
        background: linear-gradient(135deg, var(--green), #34d399);
    }

    .form-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
    }

    .form-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    .summary-cell {
        background: #f8fafc;
        border: 1px solid var(--rule);
        border-radius: 10px;
        padding: 16px;
        text-align: center;
    }

    .s-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }

    .s-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text-main);
        margin-top: 8px;
    }

    .field-group {
        margin-bottom: 20px;
    }

    .field-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .field-group label .req {
        color: #ef4444;
        margin-left: 2px;
    }

    .form-input {
        width: 100%;
        font-family: var(--font-main);
        font-size: 0.95rem;
        background: white;
        border: 1px solid var(--rule);
        border-radius: 8px;
        padding: 12px 14px;
        color: var(--text-main);
        outline: none;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .form-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }

    .form-input::placeholder {
        color: #9ca3af;
    }

    textarea.form-input {
        resize: vertical;
        min-height: 100px;
        line-height: 1.5;
    }

    select.form-input {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 16px;
        padding-right: 40px;
    }

    .input-wrap {
        position: relative;
    }

    .input-unit {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        pointer-events: none;
    }

    .form-footer {
        padding: 24px;
        background: #f8fafc;
        border-top: 1px solid var(--rule);
        display: flex;
        gap: 16px;
    }

    .btn-save {
        flex: 2;
        font-family: var(--font-main);
        font-size: 0.95rem;
        font-weight: 600;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: var(--shadow-sm);
    }

    .btn-save:hover {
        background: var(--primary-hover);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
    }

    .btn-clear {
        flex: 1;
        font-family: var(--font-main);
        font-size: 0.95rem;
        font-weight: 600;
        background: white;
        color: var(--text-main);
        border: 1px solid var(--rule);
        border-radius: 8px;
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .btn-clear:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .form-panel.mode-new .btn-save {
        background: var(--green);
    }

    .form-panel.mode-new .btn-save:hover {
        background: #059669;
    }

    /* Footer */
    .ledger-footer {
        background: var(--paper);
        border-radius: var(--radius);
        border: 1px solid var(--rule);
        padding: 24px 32px;
        display: flex;
        gap: 32px;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
    }

    .footer-stat {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .footer-label {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .footer-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .footer-sep {
        width: 1px;
        height: 32px;
        background: var(--rule);
    }

    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
        
        .form-panel {
            position: static;
        }
    }

    @media print {
        /* Force background to white and hide anything outside printable area without breaking layout */
        body {
            background: #fff !important;
        }
        
        body * {
            visibility: hidden;
        }

        #printable-area,
        #printable-area * {
            visibility: visible;
            color: #000 !important; /* Force black text for print */
        }

        #printable-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        /* Fix flexbox rendering in print */
        .ledger-header {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-bottom: 1px solid #ddd !important;
            padding-bottom: 20px !important;
            margin-bottom: 20px !important;
        }

        .brand-icon {
            background: none !important;
            border: 2px solid #000 !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        .btn-icon,
        .btn-export-excel,
        .action-btn,
        .month-input,
        .filter-bar,
        .form-panel {
            display: none !important;
            visibility: hidden !important;
        }

        .main-grid {
            display: block !important;
        }

        .table__wrapper table {
            font-size: 11pt !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .table__wrapper th,
        .table__wrapper td {
            border: 1px solid #ccc !important;
            padding: 10px !important;
            color: #000 !important;
        }

        .table__wrapper th {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
        }
        
        .card__wrapper, .sheet-panel, .filter-bar {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<div id="printable-area">
<!-- Header -->
<div class="card__wrapper">

    <header class="ledger-header">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-journal-text"></i></div>
            <h1>
                <span>Employee Ledger</span>
                Monthly Task Sheet
            </h1>
        </div>
        <div class="header-meta">
            <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Employee') ?></strong>
            <span style="display:block; font-size:0.85rem; color:var(--text-muted); margin-bottom: 4px; text-transform: capitalize;">
                <?= htmlspecialchars($_SESSION['user']['designation_name'] ?? 'Designation') ?> 
                | <?= htmlspecialchars($_SESSION['user']['department_name'] ?? 'Department') ?>
            </span>
            <strong>ID: <?= htmlspecialchars($_SESSION['user']['employee_id'] ?? '—') ?></strong>
        </div>
    </header>
</div>
<!-- Filter Bar -->
<?php
$totalTasks   = count($taskSheets);
$doneTasks    = count(array_filter($taskSheets, fn($t) => strtolower(trim($t['status'] ?? '')) === 'done'));
$pendingTasks = count(array_filter($taskSheets, fn($t) => strtolower(trim($t['status'] ?? '')) === 'pending'));
$totalHours   = array_sum(array_column($taskSheets, 'hours_spent'));
$filter       = $filter ?? [
    'mode' => 'month_year',
    'year' => date('Y'),
    'month' => date('m'),
    'date_from' => '',
    'date_to' => '',
    'all_months' => false,
    'period_label' => date('F Y')
];
$exportQueryString = $exportQueryString ?? ('month=' . date('Y-m'));
?>
<div class="filter-bar">
    <form method="GET" action="<?= $GLOBALS['base_url'] ?>/task-sheets" id="filterForm" class="filter-form-wrapper">
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
            <label class="filter-checkbox-label" title="নির্বাচিত বছরের সকল মাসের ডাটা ফিল্টার ছাড়াই একসাথে দেখতে টিক দিন">
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
            <a href="<?= $GLOBALS['base_url'] ?>/task-sheets?all=1" class="btn-filter-action btn-all-time <?= ($filter['mode'] === 'all') ? 'active' : '' ?>" title="শুরু থেকে আজ পর্যন্ত সকল টাস্ক একসাথে দেখুন">
                <i class="bi bi-collection-play"></i> All Time (সব ডাটা)
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/task-sheets" class="btn-filter-action btn-reset" title="বর্তমান মাসে ফিরে যান">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        </div>
    </form>

    <!-- Filter Status Row -->
    <div class="filter-status-row">
        <div class="filter-pills">
            <div class="stat-pill" title="মোট টাস্ক">
                Total: <strong><?= $totalTasks ?></strong>
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

        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/task-sheets-export?<?= $exportQueryString ?>" 
               class="btn-export-excel" 
               title="বর্তমান ফিল্টারের টাস্ক শিট এক্সেলে ডাউনলোড করুন">
                <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<!-- Main Grid -->
<div class="main-grid">
    <!-- Sheet / Table -->
    <div class="sheet-panel">
        <div class="sheet-toolbar">
            <span class="toolbar-label">Task Records — <?= htmlspecialchars($filter['period_label']) ?></span>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/task-sheets-export?<?= $exportQueryString ?>" 
                   class="btn-export-excel" 
                   title="Download Excel">
                    <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
                </a>
                <button class="btn-icon" title="Print" onclick="window.print()"><i class="bi bi-printer"></i></button>
            </div>
        </div>

        <div class="table__wrapper table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Task Description</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($taskSheets)): ?>
                        <tr class="empty-row">
                            <td colspan="7">
                                <i class="bi bi-journal-x" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                                No tasks recorded for <?= htmlspecialchars($filter['period_label']) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($taskSheets as $i => $task): ?>
                            <tr id="row-<?= (int)$task['id'] ?>"
                                onclick="loadTask(<?= htmlspecialchars(json_encode($task), ENT_QUOTES) ?>)"
                                data-date="<?= htmlspecialchars($task['work_date']) ?>">
                                <td><?= $i + 1 ?></td>
                                <td class="row-date"><?= date('d M', strtotime($task['work_date'])) ?></td>
                                <td class="row-desc"><?= htmlspecialchars($task['task_description']) ?></td>
                                <td class="row-hours"><?= number_format($task['hours_spent'], 2) ?><span style="color:#bbb;font-size:.62rem">h</span></td>
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
                                <td class="row-desc" style="max-width:150px"><?= htmlspecialchars($task['remarks'] ?? '—') ?></td>
                                <td>
                                    <button class="action-btn" title="Edit"
                                        onclick="event.stopPropagation();loadTask(<?= htmlspecialchars(json_encode($task), ENT_QUOTES) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="card__wrapper form-panel mode-new" id="formPanel">
        <div class="form-header">
            <h2 id="formTitle">New Entry</h2>
            <p id="formSub">Fill in the task details and save</p>
        </div>

        <div class="form-body">

            <!-- Summary strip -->
            <div class="form-summary">
                <div class="summary-cell">
                    <div class="s-label">Total Tasks</div>
                    <div class="s-value"><?= $totalTasks ?></div>
                </div>
                <div class="summary-cell">
                    <div class="s-label">Hours Logged</div>
                    <div class="s-value"><?= number_format($totalHours, 2) ?>h</div>
                </div>
            </div>


            <form method="POST"
                action="<?= $GLOBALS['base_url'] ?>/store-update-task"
                id="taskForm">

                <input type="hidden" name="id" id="taskId">

                <div class="field-group">
                    <label>Work Date <span class="req">*</span></label>
                    <input type="date" name="work_date" id="workDate"
                        class="form-input"
                        value="<?= date('Y-m-d') ?>"
                        required>
                </div>

                <div class="field-group">
                    <label>Task Description <span class="req">*</span></label>
                    <textarea name="task_description" id="taskDesc"
                        class="form-input"
                        placeholder="Describe the work performed…"
                        required></textarea>
                </div>

                <div class="field-group">
                    <label>Hours Spent <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="number" name="hours_spent" id="hoursSpent"
                            class="form-input"
                            placeholder="0.00"
                            min="0" max="24" step="0.01"
                            required>
                        <span class="input-unit">HRS</span>
                    </div>
                </div>

                <div class="field-group">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" id="taskStatus" class="form-input" required>
                        <option value="">— Select —</option>
                        <option value="Done">Done</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>

                <div class="field-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" id="taskRemarks"
                        class="form-input"
                        placeholder="Optional notes…">
                </div>

            </form>
        </div><!-- /form-body -->

        <div class="form-footer">
            <button type="button" class="btn-clear" onclick="resetForm()">
                <i class="bi bi-x-lg"></i> Clear
            </button>
            <button type="submit" form="taskForm" class="btn-save">
                <i class="bi bi-check-lg"></i>
                <span id="saveLabel">Add Task</span>
            </button>
        </div>
    </div><!-- /form-panel -->

</div><!-- /main-grid -->

<!-- Footer -->
<footer class="ledger-footer">
    <div class="footer-stat">
        <span class="footer-label">Period</span>
        <span class="footer-value"><?= htmlspecialchars($filter['period_label'] ?? $currentMonth) ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Entries</span>
        <span class="footer-value"><?= $totalTasks ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Hours</span>
        <span class="footer-value"><?= number_format($totalHours, 2) ?>h</span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Completed</span>
        <span class="footer-value"><?= $doneTasks ?> / <?= $totalTasks ?></span>
    </div>
</footer>

</div><!-- /printable-area -->
</div><!-- /page-wrapper -->

<script>
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

document.addEventListener('DOMContentLoaded', function() {
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
});

function onDateRangeChange() {
    const df = document.getElementById('dateFrom')?.value;
    const dt = document.getElementById('dateTo')?.value;
    if (df || dt) {
        const allMonthsCheck = document.getElementById('allMonthsCheck');
        if (allMonthsCheck) allMonthsCheck.checked = false;
    }
}

function loadTask(task) {
    console.log(task); // debug

    document.getElementById('taskId').value = task.id;
    document.getElementById('workDate').value = task.work_date;
    document.getElementById('taskDesc').value = task.task_description;
    document.getElementById('hoursSpent').value = task.hours_spent;
    document.getElementById('taskRemarks').value = task.remarks ?? '';

    // status mapping
    let rawStatus = (task.status || '').trim();
    let statusMap = {
        'done': 'Done',
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'in progress': 'In Progress',
        'Done': 'Done',
        'Pending': 'Pending',
        'In Progress': 'In Progress'
    };

    document.getElementById('taskStatus').value =
        statusMap[rawStatus.toLowerCase()] ?? rawStatus;

    // UI change
    document.getElementById('formTitle').innerText = "Edit Task";
    document.getElementById('saveLabel').innerText = "Update Task";

    const formPanel = document.getElementById('formPanel');
    if (formPanel) {
        formPanel.classList.remove('mode-new');
    }
}

function resetForm() {
    document.getElementById('taskId').value = "";
    document.getElementById('workDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('taskDesc').value = "";
    document.getElementById('hoursSpent').value = "";
    document.getElementById('taskStatus').value = "";
    document.getElementById('taskRemarks').value = "";
    
    // Reset UI
    document.getElementById('formTitle').innerText = "New Entry";
    document.getElementById('saveLabel').innerText = "Add Task";
    const formPanel = document.getElementById('formPanel');
    if (formPanel) {
        formPanel.classList.add('mode-new');
    }
}
</script>
