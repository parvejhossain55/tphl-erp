<style>
    /* Header */
    .ledger-header {
        padding: 26px 36px 22px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .brand-icon {
        width: 44px;
        height: 44px;
        border: 2px solid var(--accent);
        display: grid;
        place-items: center;
        font-size: 20px;
        color: var(--accent);
        flex-shrink: 0;
    }

    .brand h1 {
        font-family: var(--serif);
        font-size: 1.55rem;
        font-weight: 300;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .brand h1 span {
        display: block;
        font-size: 0.68rem;
        letter-spacing: 0.18em;
        color: #999;
        margin-bottom: 3px;
        text-transform: uppercase;
    }

    .header-meta {
        text-align: right;
        font-size: 0.7rem;
        color: #999;
        letter-spacing: 0.06em;
    }

    .header-meta strong {
        display: block;
        font-size: 1.05rem;
        font-family: var(--serif);
        color: var(--paper);
        font-weight: 300;
        letter-spacing: 0;
    }

    /* Filter Bar */
    .filter-bar {
        border-bottom: 2px solid #0f0f0f;
        padding: 12px 36px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .filter-label {
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #888;
    }

    .month-input {
        font-family: var(--mono);
        font-size: 0.82rem;
        border: 1.5px solid var(--rule);
        padding: 6px 10px;
        color: #0f0f0f;
        outline: none;
        cursor: pointer;
        transition: border-color .2s;
    }

    .month-input:focus {
        border-color: #0f0f0f;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        padding: 5px 11px;
        border: 1.5px solid var(--rule);
        background: var(--paper);
    }

    .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    /* Main Grid */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        min-height: calc(100vh - 138px);
    }

    /* Sheet Panel */
    .sheet-panel {
        border-right: 2px solid #0f0f0f;
        overflow-x: auto;
    }

    .sheet-toolbar {
        padding: 11px 18px;
        border-bottom: 1.5px solid var(--rule);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .toolbar-label {
        font-size: 0.66rem;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #999;
        margin-right: auto;
    }

    .btn-icon {
        width: 30px;
        height: 30px;
        border: 1.5px solid var(--rule);
        background: transparent;
        display: grid;
        place-items: center;
        font-size: 13px;
        /* color: #0f0f0f; */
        cursor: pointer;
        transition: all .15s;
    }

    .btn-icon:hover {
        background: #0f0f0f;
        color: var(--paper);
        border-color: #0f0f0f;
    }

    /* Spreadsheet */
    .spreadsheet {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.79rem;
    }

    .spreadsheet thead th {
        background: #0f0f0f;
        color: var(--paper);
        font-size: 0.63rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 9px 13px;
        text-align: left;
        white-space: nowrap;
        font-weight: 500;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .spreadsheet thead th:first-child {
        width: 38px;
        text-align: center;
        color: #888;
    }

    .spreadsheet tbody tr {
        border-bottom: 1px solid var(--rule);
        transition: background .12s;
        cursor: pointer;
    }

    .spreadsheet tbody tr:hover {
        background: var(--accent-dim);
    }

    .spreadsheet tbody tr.selected {
        background: var(--accent-dim);
    }

    .spreadsheet tbody td {
        padding: 10px 13px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .spreadsheet tbody td:first-child {
        text-align: center;
        color: #aaa;
        font-size: 0.62rem;
        user-select: none;
    }

    .row-date {
        font-weight: 500;
        color: var(--blue);
    }

    .row-hours {
        font-variant-numeric: tabular-nums;
        font-weight: 500;
    }

    .row-desc {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #444;
    }

    .status-badge {
        display: inline-block;
        font-size: 0.59rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 3px 8px;
        border: 1.5px solid;
        font-weight: 500;
    }

    .status-done {
        color: var(--green);
        border-color: var(--green);
        background: var(--green-dim);
    }

    .status-pending {
        color: var(--gold);
        border-color: var(--gold);
        background: var(--gold-dim);
    }

    .status-wip {
        color: var(--blue);
        border-color: var(--blue);
        background: var(--blue-dim);
    }

    .action-btn {
        background: none;
        border: none;
        color: #bbb;
        font-size: 13px;
        cursor: pointer;
        padding: 2px 5px;
        transition: color .15s;
    }

    .action-btn:hover {
        color: var(--accent);
    }

    .empty-row td {
        padding: 48px 20px;
        text-align: center;
        color: #ccc;
        font-size: 0.76rem;
        letter-spacing: 0.06em;
        cursor: default !important;
    }

    /* Form Panel */
    .form-panel {
        background: var(--paper);
        display: flex;
        flex-direction: column;
    }

    .form-header {
        padding: 15px 22px;
        border-bottom: 2px solid #0f0f0f;
    }

    .form-header h2 {
        font-family: var(--serif);
        font-size: 1.05rem;
        font-weight: 300;
        letter-spacing: -0.01em;
    }

    .form-header p {
        font-size: 0.66rem;
        color: #999;
        letter-spacing: 0.07em;
        margin-top: 2px;
    }

    .form-body {
        padding: 20px 22px;
        flex: 1;
        overflow-y: auto;
    }

    .form-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3px;
        background: var(--rule);
        margin-bottom: 18px;
    }

    .summary-cell {
        border: 1px solid #5a5757;
        padding: 9px 13px;
    }

    .s-label {
        font-size: 0.58rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #aaa;
    }

    .s-value {
        font-size: 1.05rem;
        font-family: var(--serif);
        font-weight: 600;
        margin-top: 1px;
    }

    .field-group {
        margin-bottom: 16px;
    }

    .field-group label {
        display: block;
        font-size: 0.63rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #888;
        margin-bottom: 5px;
    }

    .field-group label .req {
        color: var(--accent);
        margin-left: 2px;
    }

    .form-input {
        width: 100%;
        font-family: var(--mono);
        font-size: 0.81rem;
        /* background: var(--paper); */
        /* border: 1.5px solid var(--rule); */
        padding: 8px 11px;
        /* color: #0f0f0f; */
        outline: none;
        transition: border-color .2s;
    }

    .form-input:focus {
        border-color: #0f0f0f;
    }

    .form-input::placeholder {
        color: #ccc;
    }

    textarea.form-input {
        resize: vertical;
        min-height: 80px;
        line-height: 1.5;
    }

    select.form-input {
        cursor: pointer;
    }

    .input-wrap {
        position: relative;
    }

    .input-wrap .form-input {
        padding-right: 42px;
    }

    .input-unit {
        position: absolute;
        right: 11px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.62rem;
        color: #bbb;
        letter-spacing: 0.1em;
        pointer-events: none;
    }

    input[name="id"] {
        display: none;
    }

    .form-footer {
        padding: 14px 22px;
        border-top: 2px solid var(--rule);
        display: flex;
        gap: 8px;
    }

    .btn-save {
        flex: 1;
        font-family: var(--mono);
        font-size: 0.72rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        background: #0f0f0f;
        color: var(--paper);
        border: 2px solid #0f0f0f;
        padding: 10px 16px;
        cursor: pointer;
        transition: all .18s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }

    .btn-save:hover {
        background: var(--accent);
        border-color: var(--accent);
    }

    .btn-clear {
        font-family: var(--mono);
        font-size: 0.72rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        background: transparent;
        border: 2px solid var(--rule);
        padding: 10px 14px;
        cursor: pointer;
        transition: all .18s;
    }

    .btn-clear:hover {
        border-color: #0f0f0f;
    }

    .form-panel.mode-new .btn-save {
        background: #139b26;
        border-color: var(--green);
        color: #fff;
    }

    .form-panel.mode-new .btn-save:hover {
        background: #0f0f0f;
        border-color: #0f0f0f;
    }

    /* Footer */
    .ledger-footer {
        border-top: 2px solid #0f0f0f;
        color: var(--paper);
        padding: 13px 36px;
        display: flex;
        gap: 28px;
        align-items: center;
        flex-wrap: wrap;
        font-size: 0.7rem;
        letter-spacing: 0.07em;
    }

    .footer-stat {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .footer-label {
        color: #888;
    }

    .footer-value {
        /* font-family: var(--serif); */
        font-size: 1rem;
        font-weight: 300;
    }

    .footer-sep {
        width: 1px;
        height: 18px;
        background: #333;
    }

    /* Editing indicator */
    .edit-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        background: var(--accent);
        border-radius: 50%;
        margin-right: 6px;
        animation: blink 1.1s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: 0.15
        }
    }

    ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }

    ::-webkit-scrollbar-track {
        background: #ede8dc;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--rule);
    }

    @media (max-width: 860px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .sheet-panel {
            border-right: none;
            border-bottom: 2px solid #0f0f0f;
        }

        .ledger-header,
        .filter-bar,
        .ledger-footer {
            padding-left: 18px;
            padding-right: 18px;
        }
    }

    @media print {

        /* Hide everything first */
        body * {
            visibility: hidden;
        }

        /* Show only main grid + filter bar + table */
        .main-grid,
        .main-grid *,
        .filter-bar,
        .filter-bar * {
            visibility: visible;
        }

        /* Position everything at top */
        .main-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        /* Hide buttons and interactive elements */
        .btn-icon,
        .action-btn,
        .month-input {
            display: none !important;
        }

        /* Table styling */
        table {
            font-size: 12pt;
            border-collapse: collapse;
            width: 100%;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        table th {
            background-color: #f2f2f2;
            -webkit-print-color-adjust: exact;
            /* preserve colors */
        }

        /* Optional: ensure status dots/colors print */
        .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .stat-pill {
            margin-bottom: 4px;
        }
    }
</style>


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
            <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Employee') ?></strong>
            ID — <?= htmlspecialchars($_SESSION['user']['employee_id'] ?? '—') ?>
        </div>
    </header>
</div>

<!-- Filter Bar -->
<?php
$totalTasks   = count($taskSheets);
$doneTasks    = count(array_filter($taskSheets, fn($t) => $t['status'] === 'Done'));
$pendingTasks = count(array_filter($taskSheets, fn($t) => $t['status'] === 'Pending'));
$totalHours   = array_sum(array_column($taskSheets, 'hours_spent'));
$currentMonth = $_GET['month'] ?? date('Y-m');
?>
<div class="filter-bar">
    <span class="filter-label">Month</span>
    <input type="month" class="month-input" id="monthFilter"
        value="<?= htmlspecialchars($currentMonth) ?>"
        onchange="filterMonth(this.value)">

    <div class="stat-pill">
        <span class="dot" style="background:var(--green)"></span>
        Done: <strong><?= $doneTasks ?></strong>
    </div>
    <div class="stat-pill">
        <span class="dot" style="background:var(--gold)"></span>
        Pending: <strong><?= $pendingTasks ?></strong>
    </div>
    <div class="stat-pill">
        <span class="dot" style="background:var(--blue)"></span>
        <?= number_format($totalHours, 1) ?> hrs
    </div>
</div>

<!-- Main Grid -->
<div class="main-grid">
    <!-- Sheet / Table -->
    <div class="sheet-panel">
        <div class="sheet-toolbar">
            <span class="toolbar-label">Task Records — <?= date('F Y', strtotime($currentMonth . '-01')) ?></span>
            <button class="btn-icon" title="Print" onclick="window.print()"><i class="bi bi-printer"></i></button>
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
                                No tasks recorded this month
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
                                <td class="row-hours"><?= number_format($task['hours_spent'], 1) ?><span style="color:#bbb;font-size:.62rem">h</span></td>
                                <td>
                                    <?php
                                    $s   = $task['status'];
                                    $cls = match ($s) {
                                        'done' => 'status-done',
                                        'pending' => 'status-pending',
                                        default => 'status-wip'
                                    };
                                    $lbl = match ($s) {
                                        'done' => 'Done',
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        default => ucfirst($s)
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
                    <div class="s-value"><?= number_format($totalHours, 1) ?>h</div>
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
                            placeholder="0.0"
                            min="0" max="24" step="0.5"
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
        <span class="footer-label">Month</span>
        <span class="footer-value"><?= date('F Y', strtotime($currentMonth . '-01')) ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Entries</span>
        <span class="footer-value"><?= $totalTasks ?></span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Hours</span>
        <span class="footer-value"><?= number_format($totalHours, 1) ?>h</span>
    </div>
    <div class="footer-sep"></div>
    <div class="footer-stat">
        <span class="footer-label">Completed</span>
        <span class="footer-value"><?= $doneTasks ?> / <?= $totalTasks ?></span>
    </div>
</footer>

</div><!-- /page-wrapper -->

<script>
    async function applyFilters() {
        const dept = document.getElementById('deptFilter').value;
        const emp = document.getElementById('empFilter').value;
        const status = document.getElementById('statusFilter').value;
        const from = document.getElementById('dateFrom').value;
        const to = document.getElementById('dateTo').value;

        let url = `index.php?controller=task&action=getTaskData
        &department_id=${dept}
        &employee_id=${emp}
        &status=${status}
        &date_from=${from}
        &date_to=${to}`;

        const res = await fetch(url);
        const data = await res.json();

        renderTable(data);
    }

    function renderTable(data) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">No data found</td></tr>`;
            return;
        }

        data.forEach((row, i) => {
            tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>${row.emp_name}</td>
                <td>${row.department_name}</td>
                <td>${row.work_date}</td>
                <td>${row.task_description}</td>
                <td>${row.hours_spent}</td>
                <td>${row.status}</td>
                <td>${row.remarks ?? '-'}</td>
                <td>${row.created_at}</td>
            </tr>
        `;
        });

        document.getElementById('recordCount').innerText =
            "Total Records: " + data.length;
    }

    // Reset filters
    function resetFilters() {
        document.getElementById('deptFilter').value = "";
        document.getElementById('empFilter').value = "";
        document.getElementById('statusFilter').value = "";
        document.getElementById('dateFrom').value = "";
        document.getElementById('dateTo').value = "";

        applyFilters();
    }

    // Load on page start
    window.onload = applyFilters;
</script>


<script>
    function exportCSV() {
        let table = document.getElementById("taskTable");
        let rows = table.querySelectorAll("tr");

        let csv = [];
        rows.forEach(row => {
            let cols = row.querySelectorAll("td, th");
            let rowData = [];
            cols.forEach(col => rowData.push(col.innerText));
            csv.push(rowData.join(","));
        });

        let blob = new Blob([csv.join("\n")], {
            type: 'text/csv'
        });
        let a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "task_report.csv";
        a.click();
    }
</script>


<script>
function exportCSV() {
    let table = document.getElementById("taskTable");
    let rows = table.querySelectorAll("tr");

    let csv = [];
    rows.forEach(row => {
        let cols = row.querySelectorAll("td, th");
        let rowData = [];
        cols.forEach(col => rowData.push(col.innerText));
        csv.push(rowData.join(","));
    });

    let blob = new Blob([csv.join("\n")], { type: 'text/csv' });
    let a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "task_report.csv";
    a.click();
}
</script>

<script>
function loadTask(task) {
    console.log(task); // debug

    document.getElementById('taskId').value = task.id;
    document.getElementById('workDate').value = task.work_date;
    document.getElementById('taskDesc').value = task.task_description;
    document.getElementById('hoursSpent').value = task.hours_spent;
    document.getElementById('taskRemarks').value = task.remarks ?? '';

    // status mapping (IMPORTANT)
    let statusMap = {
        'done': 'Done',
        'pending': 'Pending',
        'in_progress': 'In Progress'
    };

    document.getElementById('taskStatus').value =
        statusMap[task.status] ?? task.status;

    // UI change
    document.getElementById('formTitle').innerText = "Edit Task";
    document.getElementById('saveLabel').innerText = "Update Task";

    document.getElementById('formPanel').classList.remove('mode-new');
}
</script>
