<style>
    :root {
        --primary: #0f2c4a;
        --accent: #f4a623;
        --accent2: #2dd4bf;
        --light-bg: #f0f4f9;
        --card-bg: #ffffff;
        --border: #dce4ef;
        --text-main: #0f2c4a;
        --text-muted: #6b82a0;
        --done-bg: #d1fae5;
        --done-text: #065f46;
        --pending-bg: #fef3c7;
        --pending-text: #92400e;
        --inprogress-bg: #dbeafe;
        --inprogress-text: #1e40af;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--light-bg);
        color: var(--text-main);
        min-height: 100vh;
    }

    /* HEADER */
    .top-header {
        background: var(--primary);
        padding: 18px 36px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 16px rgba(15, 44, 74, 0.18);
    }

    .top-header .brand {
        font-family: 'Syne', sans-serif;
        font-weight: 800;
        font-size: 1.5rem;
        color: #fff;
        letter-spacing: -0.5px;
    }

    .top-header .brand span {
        color: var(--accent);
    }

    .top-header .meta {
        font-size: 0.82rem;
        color: rgba(255, 255, 255, 0.55);
    }

    /* FILTER SECTION */
    .filter-section {

        border-bottom: 1px solid var(--border);
        padding: 20px 36px;
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group label {
        display: block;
        font-size: 0.73rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin-bottom: 6px;
    }

    .filter-group select,
    .filter-group input[type="date"] {
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 8px 14px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        color: var(--text-main);
        background: #fff;
        min-width: 180px;
        outline: none;
        transition: border-color 0.2s;
        appearance: none;
        -webkit-appearance: none;
    }

    .filter-group select:focus,
    .filter-group input[type="date"]:focus {
        border-color: var(--accent);
    }

    .btn-filter {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 22px;
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-filter:hover {
        background: #1a3f66;
    }

    .btn-reset {
        background: transparent;
        color: var(--text-muted);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-reset:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    /* STAT CARDS */
    .stats-row {
        display: flex;
        gap: 16px;
        padding: 24px 36px 10px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 18px 24px;
        flex: 1;
        min-width: 160px;
        border: 1.5px solid var(--border);
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 6px rgba(15, 44, 74, 0.04);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }

    .stat-card.total .stat-icon {
        background: #e0e9f5;
        color: var(--primary);
    }

    .stat-card.done .stat-icon {
        background: var(--done-bg);
        color: var(--done-text);
    }

    .stat-card.pending .stat-icon {
        background: var(--pending-bg);
        color: var(--pending-text);
    }

    .stat-card.inprogress .stat-icon {
        background: var(--inprogress-bg);
        color: var(--inprogress-text);
    }

    .stat-val {
        font-family: 'Syne', sans-serif;
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-weight: 500;
        margin-top: 2px;
    }

    /* TABLE SECTION */
    .table-section {
        padding: 10px 36px 40px;
    }

    .table-wrapper {
        border-radius: 14px;
        border: 1.5px solid var(--border);
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(15, 44, 74, 0.06);
    }

    .table-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px 14px;
    }

    .table-title {
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: 1rem;
    }

    .table-subtitle {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .btn-export {
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: 7px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.2s;
    }

    .btn-export:hover {
        opacity: 0.88;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    thead th {
        background: #f7fafd;
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        padding: 11px 16px;
        border-bottom: 1.5px solid var(--border);
        white-space: nowrap;
    }

    tbody tr {
        border-bottom: 1px solid #edf1f7;
        transition: background 0.15s;
    }

    tbody tr:last-child {
        border-bottom: none;
    }

    tbody tr:hover {
        background: #f7fafd;
    }

    tbody td {
        padding: 12px 16px;
        color: var(--text-main);
        vertical-align: middle;
    }

    .emp-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .emp-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: 0.75rem;
        color: #fff;
        flex-shrink: 0;
    }

    .emp-info .name {
        font-weight: 600;
        font-size: 0.88rem;
    }

    .emp-info .dept {
        font-size: 0.73rem;
        color: var(--text-muted);
    }

    .status-pill {
        display: inline-block;
        padding: 3px 11px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-pill.Done {
        background: var(--done-bg);
        color: var(--done-text);
    }

    .status-pill.Pending {
        background: var(--pending-bg);
        color: var(--pending-text);
    }

    .status-pill.In-Progress {
        background: var(--inprogress-bg);
        color: var(--inprogress-text);
    }

    .hours-bar-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hours-bar {
        height: 6px;
        border-radius: 4px;
        background: #e0e9f5;
        flex: 1;
        min-width: 60px;
        overflow: hidden;
    }

    .hours-bar-fill {
        height: 100%;
        border-radius: 4px;
        background: var(--accent2);
    }

    .hours-val {
        font-weight: 600;
        font-size: 0.82rem;
        white-space: nowrap;
    }

    .remarks-cell {
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-muted);
        font-size: 0.82rem;
    }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }

    .no-data i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        display: block;
        opacity: 0.35;
    }

    /* Responsive */
    @media (max-width: 768px) {

        .filter-section,
        .stats-row,
        .table-section {
            padding-left: 16px;
            padding-right: 16px;
        }

        .top-header {
            padding: 14px 16px;
        }

        .table-wrapper {
            border-radius: 10px;
        }

        table {
            font-size: 0.8rem;
        }

        tbody td,
        thead th {
            padding: 10px 10px;
        }

        .stat-card {
            min-width: 130px;
        }
    }
</style>


<!-- HEADER -->


<!-- FILTERS -->
<div class="card__wrapper">
    <div class="top-header">
        <div class="brand">KPI <span>TaskSheet</span></div>
        <div class="meta" id="currentDate"></div>
    </div>

    <form method="GET">

        <div class="filter-section">

            <div class="filter-group">
                <label>Department</label>
                <select name="department_id">
                    <option value="">All</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"
                            <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Employee</label>
                <select name="employee_id">
                    <option value="">All</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"
                            <?= ($filters['employee_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="Done" <?= ($filters['status'] ?? '') == 'Done' ? 'selected' : '' ?>>Done</option>
                    <option value="Pending" <?= ($filters['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= ($filters['status'] ?? '') == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                </select>
            </div>

            <div class="filter-group">
                <label>From</label>
                <input type="date" name="date_from" value="<?= $filters['date_from'] ?? '' ?>">
            </div>

            <div class="filter-group">
                <label>To</label>
                <input type="date" name="date_to" value="<?= $filters['date_to'] ?? '' ?>">
            </div>

            <div class="filter-group">
                <label>Month</label>
                <input type="month" name="month" value="<?= $currentMonth ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= $GLOBALS['base_url'] ?>/department-kpi" class="btn btn-success">Reset</a>
                <a href="<?= $GLOBALS['base_url'] ?>/department-kpi-pdf?<?= http_build_query($_GET) ?>"
                    class="btn btn-danger">
                    Export PDF
                </a>
            </div>
        </div>
    </form>
</div>

<!-- TABLE -->
<div class="row">

    <div class="table-header-row">
        <div>
            <div class="table-title text-muted">Employee Task Records</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20 multiple_tables">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Work Date</th>
                            <th>Task Description</th>
                            <th>Hours Spent</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        1
                        <?php foreach ($reportData as $i => $row): ?>
                            <tr>
                                <td><?= $row['employee_id']?></td>
                                <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                <td><?= $row['work_date'] ?></td>
                                <td><?= htmlspecialchars($row['task_description']) ?></td>
                                <td><?= $row['hours_spent'] ?></td>
                                <td><?= ucfirst($row['status']) ?></td>
                                <td><?= $row['remarks'] ?? '-' ?></td>
                                <td><?= $row['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // =============================================
        // SAMPLE DATA — Replace with your actual DB fetch
        // =============================================
        const DEPARTMENTS = [{
                id: 1,
                name: "Software Development"
            },
            {
                id: 2,
                name: "Human Resources"
            },
            {
                id: 3,
                name: "Finance & Accounts"
            },
            {
                id: 4,
                name: "Marketing"
            },
            {
                id: 5,
                name: "Operations"
            },
        ];

        const EMPLOYEES = [{
                id: 1,
                name: "Rafiul Islam",
                dept_id: 1
            },
            {
                id: 2,
                name: "Sadia Akter",
                dept_id: 2
            },
            {
                id: 3,
                name: "Tanvir Ahmed",
                dept_id: 1
            },
            {
                id: 4,
                name: "Nusrat Jahan",
                dept_id: 3
            },
            {
                id: 5,
                name: "Karim Uddin",
                dept_id: 4
            },
            {
                id: 6,
                name: "Farzana Hossain",
                dept_id: 5
            },
        ];

        const AVATAR_COLORS = ["#0f2c4a", "#f4a623", "#2dd4bf", "#e11d48", "#7c3aed", "#059669"];

        const TASKS = [{
                id: 1,
                employee_id: 2,
                work_date: "2026-03-18",
                task_description: "Lead 2",
                hours_spent: 2.00,
                status: "Done",
                remarks: "",
                created_at: "2026-03-18 12:44:17"
            },
            {
                id: 2,
                employee_id: 1,
                work_date: "2026-03-18",
                task_description: "API Integration",
                hours_spent: 3.50,
                status: "Done",
                remarks: "Merged to main",
                created_at: "2026-03-18 09:10:00"
            },
            {
                id: 3,
                employee_id: 3,
                work_date: "2026-03-17",
                task_description: "Bug Fix #402",
                hours_spent: 1.50,
                status: "Done",
                remarks: "Closed ticket",
                created_at: "2026-03-17 14:22:10"
            },
            {
                id: 4,
                employee_id: 2,
                work_date: "2026-03-17",
                task_description: "Team Onboarding",
                hours_spent: 4.00,
                status: "Pending",
                remarks: "Awaiting docs",
                created_at: "2026-03-17 10:00:00"
            },
            {
                id: 5,
                employee_id: 4,
                work_date: "2026-03-18",
                task_description: "Monthly P&L Report",
                hours_spent: 5.00,
                status: "In-Progress",
                remarks: "50% done",
                created_at: "2026-03-18 08:30:00"
            },
            {
                id: 6,
                employee_id: 5,
                work_date: "2026-03-16",
                task_description: "Campaign Analytics",
                hours_spent: 2.50,
                status: "Done",
                remarks: "Sent to CEO",
                created_at: "2026-03-16 13:00:00"
            },
            {
                id: 7,
                employee_id: 6,
                work_date: "2026-03-15",
                task_description: "Supply Chain Audit",
                hours_spent: 6.00,
                status: "Pending",
                remarks: "",
                created_at: "2026-03-15 09:45:00"
            },
            {
                id: 8,
                employee_id: 1,
                work_date: "2026-03-15",
                task_description: "DB Schema Design",
                hours_spent: 3.00,
                status: "Done",
                remarks: "Reviewed",
                created_at: "2026-03-15 11:20:00"
            },
            {
                id: 9,
                employee_id: 3,
                work_date: "2026-03-14",
                task_description: "Unit Tests",
                hours_spent: 2.00,
                status: "In-Progress",
                remarks: "80% coverage",
                created_at: "2026-03-14 15:00:00"
            },
            {
                id: 10,
                employee_id: 4,
                work_date: "2026-03-14",
                task_description: "Invoice Processing",
                hours_spent: 3.50,
                status: "Done",
                remarks: "Sent",
                created_at: "2026-03-14 10:10:00"
            },
        ];

        // =============================================
        let filteredTasks = [...TASKS];

        function getEmployee(id) {
            return EMPLOYEES.find(e => e.id === id) || {
                name: "Unknown",
                dept_id: 0
            };
        }

        function getDept(id) {
            return DEPARTMENTS.find(d => d.id === id) || {
                name: "—"
            };
        }

        function getAvatarColor(id) {
            return AVATAR_COLORS[(id - 1) % AVATAR_COLORS.length];
        }

        function getInitials(name) {
            return name.split(" ").map(p => p[0]).join("").substring(0, 2).toUpperCase();
        }

        // Populate Dept filter
        const deptSel = document.getElementById("deptFilter");
        DEPARTMENTS.forEach(d => {
            const o = document.createElement("option");
            o.value = d.id;
            o.textContent = d.name;
            deptSel.appendChild(o);
        });

        // Populate Employee filter (dependent on dept)
        function populateEmployeeFilter(deptId) {
            const empSel = document.getElementById("empFilter");
            const currentVal = empSel.value;
            empSel.innerHTML = '<option value="">All Employees</option>';
            const list = deptId ? EMPLOYEES.filter(e => e.dept_id == deptId) : EMPLOYEES;
            list.forEach(e => {
                const o = document.createElement("option");
                o.value = e.id;
                o.textContent = e.name;
                empSel.appendChild(o);
            });
            empSel.value = currentVal;
            if (!list.find(e => e.id == currentVal)) empSel.value = "";
        }

        document.getElementById("deptFilter").addEventListener("change", function() {
            populateEmployeeFilter(this.value);
            applyFilters();
        });

        populateEmployeeFilter("");

        function applyFilters() {
            const dept = document.getElementById("deptFilter").value;
            const emp = document.getElementById("empFilter").value;
            const status = document.getElementById("statusFilter").value;
            const from = document.getElementById("dateFrom").value;
            const to = document.getElementById("dateTo").value;

            filteredTasks = TASKS.filter(t => {
                const e = getEmployee(t.employee_id);
                if (dept && e.dept_id != dept) return false;
                if (emp && t.employee_id != emp) return false;
                if (status && t.status !== status) return false;
                if (from && t.work_date < from) return false;
                if (to && t.work_date > to) return false;
                return true;
            });
            renderTable();
            updateStats();
        }

        const maxHours = Math.max(...TASKS.map(t => t.hours_spent));

        function renderTable() {
            const tbody = document.getElementById("tableBody");
            tbody.innerHTML = "";
            if (filteredTasks.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9"><div class="no-data"><i class="bi bi-inbox"></i>No records found</div></td></tr>`;
                document.getElementById("recordCount").textContent = "No records found";
                return;
            }
            document.getElementById("recordCount").textContent = `Showing ${filteredTasks.length} record${filteredTasks.length>1?"s":""}`;

            filteredTasks.forEach((t, i) => {
                const emp = getEmployee(t.employee_id);
                const dept = getDept(emp.dept_id);
                const color = getAvatarColor(t.employee_id);
                const pct = Math.round((t.hours_spent / maxHours) * 100);
                const sc = t.status.replace(/\s+/g, "-");

                const tr = document.createElement("tr");
                tr.innerHTML = `
        <td style="color:var(--text-muted);font-size:0.8rem;">${i+1}</td>
        <td>
          <div class="emp-badge">
            <div class="emp-avatar" style="background:${color}">${getInitials(emp.name)}</div>
            <div class="emp-info">
              <div class="name">${emp.name}</div>
              <div class="dept">#${t.employee_id}</div>
            </div>
          </div>
        </td>
        <td><span style="font-size:0.82rem;color:var(--text-muted);">${dept.name}</span></td>
        <td style="white-space:nowrap;">${t.work_date}</td>
        <td style="max-width:200px;">${t.task_description}</td>
        <td>
          <div class="hours-bar-wrap">
            <div class="hours-bar"><div class="hours-bar-fill" style="width:${pct}%"></div></div>
            <span class="hours-val">${t.hours_spent}h</span>
          </div>
        </td>
        <td><span class="status-pill ${sc}">${t.status}</span></td>
        <td><div class="remarks-cell" title="${t.remarks}">${t.remarks || "—"}</div></td>
        <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;">${t.created_at}</td>
      `;
                tbody.appendChild(tr);
            });
        }

        function updateStats() {
            document.getElementById("statTotal").textContent = filteredTasks.length;
            document.getElementById("statDone").textContent = filteredTasks.filter(t => t.status === "Done").length;
            document.getElementById("statPending").textContent = filteredTasks.filter(t => t.status === "Pending").length;
            document.getElementById("statInProgress").textContent = filteredTasks.filter(t => t.status === "In-Progress").length;
            const hrs = filteredTasks.reduce((s, t) => s + t.hours_spent, 0);
            document.getElementById("statHours").textContent = hrs % 1 === 0 ? hrs : hrs.toFixed(1);
        }

        function resetFilters() {
            document.getElementById("deptFilter").value = "";
            document.getElementById("empFilter").value = "";
            document.getElementById("statusFilter").value = "";
            document.getElementById("dateFrom").value = "";
            document.getElementById("dateTo").value = "";
            populateEmployeeFilter("");
            filteredTasks = [...TASKS];
            renderTable();
            updateStats();
        }

        function exportCSV() {
            const headers = ["ID", "Employee", "Department", "Work Date", "Task Description", "Hours Spent", "Status", "Remarks", "Created At"];
            const rows = filteredTasks.map(t => {
                const emp = getEmployee(t.employee_id);
                const dept = getDept(emp.dept_id);
                return [t.id, emp.name, dept.name, t.work_date, `"${t.task_description}"`, t.hours_spent, t.status, `"${t.remarks}"`, t.created_at].join(",");
            });
            const csv = [headers.join(","), ...rows].join("\n");
            const blob = new Blob([csv], {
                type: "text/csv"
            });
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = `kpi_tasks_${new Date().toISOString().split("T")[0]}.csv`;
            a.click();
        }

        // Date header
        document.getElementById("currentDate").textContent = new Date().toLocaleDateString("en-BD", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric"
        });

        // Init
        renderTable();
        updateStats();
    </script>