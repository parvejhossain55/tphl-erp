<!-- ════ MAIN CONTENT ════ -->
<div class="app__slide-wrapper">


    <div class="section-header">
        <div class="section-title">Good morning, Raihan </div>
        <div class="section-sub">Here's your monthly summary for December 2025</div>
    </div>

    <!-- ATTENDANCE STATS -->
    <div class="row align-items-stretch">
        <div class="col-xxl-3 col-xl-3 col-12">
            <div class="card__wrapper stat-card">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"><i class="bi bi-arrow-up"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Present Days</h6>
                        <h4 class="card__title text-break text-success mb-10">22</h4>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xxl-3  col-12">
            <div class="card__wrapper stat-card">

                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"> <i class="bi bi-clock-history" style="color:var(--accent4)"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Late Arrivals</h6>
                        <span class="chip badge-late"><i class="bi bi-dot"></i> Check-in &gt; 9:15 AM - <strong class="ms-2">2</strong></span>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3  col-12">
            <div class="card__wrapper stat-card">

                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"> <i class="bi bi-x-circle-fill" style="color:var(--accent5)"></i></span></div>
                    <div class="card__title-wrap">

                        <h6 class="card__sub-title mb-10">Absent Days</h6>
                        <span class="chip badge-absent"> 2</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3  col-12">
            <div class="card__wrapper stat-card">

                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present">  <i class="bi bi-umbrella-fill" style="color:var(--accent2)"></i></span></div>
                    <div class="card__title-wrap">

                        <h6 class="card__sub-title mb-10">Leave Days Taken</h6>
                       <span class="chip badge-leave"> 2 Approved</span>
                    </div>
                </div>
            </div>
        </div>



       
    </div>

    <!-- ATTENDANCE VISUAL + LEAVE SUMMARY -->
    <div class="row mb-4">
        <!-- Attendance Bar Chart -->
        <div class="col-lg-7">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="section-title">Monthly Attendance</div>
                        <div class="section-sub">Week-by-week breakdown</div>
                    </div>
                    <div class="ring-wrap">
                        <svg width="72" height="72" viewBox="0 0 72 72">
                            <circle cx="36" cy="36" r="30" fill="none" stroke="var(--border)" stroke-width="7" />
                            <circle cx="36" cy="36" r="30" fill="none" stroke="var(--accent3)" stroke-width="7"
                                stroke-dasharray="188.5" stroke-dashoffset="35" stroke-linecap="round" />
                        </svg>
                        <div class="ring-center">
                            <div class="ring-pct" style="color:var(--accent3)">81%</div>
                            <div class="ring-lbl">Rate</div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Present</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent3)">22 days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:81%;background:var(--accent3)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Late</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent4)">3 days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:11%;background:var(--accent4)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Absent</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent5)">2 days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:7%;background:var(--accent5)"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Leave</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent2)">4 days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:15%;background:var(--accent2)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Summary -->
        <div class="col-lg-5">
            <div class="stat-card h-100">
                <div class="section-title mb-3">Leave Balance</div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div style="font-size:.72rem;color:var(--muted)">Medical Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Used 3 / Total 12</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--accent3);font-family:'Space Mono',monospace">9</div>
                            <div style="font-size:.68rem;color:var(--muted)">remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:25%;background:var(--accent3)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div style="font-size:.72rem;color:var(--muted)">Casual Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Used 2 / Total 10</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">8</div>
                            <div style="font-size:.68rem;color:var(--muted)">remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:20%;background:var(--accent)"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div style="font-size:.72rem;color:var(--muted)">Annual Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Used 8 / Total 20</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--accent2);font-family:'Space Mono',monospace">12</div>
                            <div style="font-size:.68rem;color:var(--muted)">remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:40%;background:var(--accent2)"></div>
                    </div>
                </div>
                <div class="mt-4 p-3 rounded-3" style="background:rgba(79,140,255,.07);border:1px solid rgba(79,140,255,.2)">
                    <div style="font-size:.75rem;color:var(--muted)">Total Leave Taken (YTD)</div>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">13 <span style="font-size:.9rem;font-weight:400;color:var(--muted)">/ 42 days</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TASKS SUMMARY -->
    <div class="row">
        <div class="col-12">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="section-title">Tasks This Month</div>
                        <div class="section-sub">December 2025 · 12 total tasks</div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="chip task-status-done">7 Done</span>
                        <span class="chip task-status-prog">3 In Progress</span>
                        <span class="chip task-status-pend">2 Pending</span>
                    </div>
                </div>
                <div class="att-bar-wrap mb-4" style="height:12px;border-radius:8px">
                    <div class="att-bar" style="width:58%;background:linear-gradient(90deg,var(--accent3),var(--accent));border-radius:8px;height:100%"></div>
                </div>
                <div class="row" id="dash-tasks">
                    <!-- tasks injected by JS -->
                </div>
            </div>
        </div>
    </div>
</div>



<!-- ══ PAGE: TASKS ══ -->
<div class="page" id="page-tasks">
    <div class="section-header">
        <div class="section-title">My Tasks</div>
        <div class="section-sub">December 2025 · All assigned tasks</div>
    </div>
    <div class="row mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--text)">12</div>
                <div class="stat-label">Total Tasks</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent3)">7</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent)">3</div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent4)">2</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="row" id="all-tasks"></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* ── DATA ── */
    const tasks = [{
            title: 'API Integration Sprint',
            desc: 'Backend REST endpoints',
            priority: 'high',
            status: 'done',
            due: 'Dec 8'
        },
        {
            title: 'Dashboard UI Redesign',
            desc: 'Figma to code',
            priority: 'high',
            status: 'prog',
            due: 'Dec 18'
        },
        {
            title: 'Database Migration',
            desc: 'PostgreSQL upgrade',
            priority: 'med',
            status: 'done',
            due: 'Dec 5'
        },
        {
            title: 'Unit Test Coverage',
            desc: 'Jest + 80% coverage',
            priority: 'med',
            status: 'done',
            due: 'Dec 10'
        },
        {
            title: 'Code Review #42',
            desc: 'PR review for auth module',
            priority: 'low',
            status: 'done',
            due: 'Dec 4'
        },
        {
            title: 'CI/CD Pipeline Setup',
            desc: 'GitHub Actions workflow',
            priority: 'high',
            status: 'prog',
            due: 'Dec 20'
        },
        {
            title: 'Mobile Responsive Fix',
            desc: 'Tablet & phone layout',
            priority: 'med',
            status: 'pend',
            due: 'Dec 22'
        },
        {
            title: 'Email Notification Service',
            desc: 'SMTP integration',
            priority: 'low',
            status: 'done',
            due: 'Dec 9'
        },
        {
            title: 'Security Audit',
            desc: 'OWASP top 10 check',
            priority: 'high',
            status: 'pend',
            due: 'Dec 28'
        },
        {
            title: 'Documentation Update',
            desc: 'API docs & README',
            priority: 'low',
            status: 'done',
            due: 'Dec 7'
        },
        {
            title: 'Performance Profiling',
            desc: 'Lighthouse audit & fix',
            priority: 'med',
            status: 'prog',
            due: 'Dec 24'
        },
        {
            title: 'Deployment to Staging',
            desc: 'AWS EC2 staging env',
            priority: 'med',
            status: 'done',
            due: 'Dec 12'
        },
    ];

    const attData = [{
            date: 'Dec 1',
            day: 'Mon',
            in: '09:02',
            out: '18:05',
            h: '9h 03m',
            s: 'present'
        },
        {
            date: 'Dec 2',
            day: 'Tue',
            in: '09:18',
            out: '18:10',
            h: '8h 52m',
            s: 'late'
        },
        {
            date: 'Dec 3',
            day: 'Wed',
            in: '09:45',
            out: '18:20',
            h: '8h 35m',
            s: 'late'
        },
        {
            date: 'Dec 4',
            day: 'Thu',
            in: '09:05',
            out: '18:00',
            h: '8h 55m',
            s: 'present'
        },
        {
            date: 'Dec 5',
            day: 'Fri',
            in: '—',
            out: '—',
            h: '—',
            s: 'leave'
        },
        {
            date: 'Dec 8',
            day: 'Mon',
            in: '09:00',
            out: '18:10',
            h: '9h 10m',
            s: 'present'
        },
        {
            date: 'Dec 9',
            day: 'Tue',
            in: '09:10',
            out: '18:05',
            h: '8h 55m',
            s: 'present'
        },
        {
            date: 'Dec 10',
            day: 'Wed',
            in: '—',
            out: '—',
            h: '—',
            s: 'absent'
        },
        {
            date: 'Dec 11',
            day: 'Thu',
            in: '09:03',
            out: '18:00',
            h: '8h 57m',
            s: 'present'
        },
        {
            date: 'Dec 12',
            day: 'Fri',
            in: '09:07',
            out: '18:15',
            h: '9h 08m',
            s: 'present'
        },
        {
            date: 'Dec 15',
            day: 'Mon',
            in: '09:01',
            out: '18:00',
            h: '8h 59m',
            s: 'present'
        },
        {
            date: 'Dec 16',
            day: 'Tue',
            in: '—',
            out: '—',
            h: '—',
            s: 'absent'
        },
        {
            date: 'Dec 17',
            day: 'Wed',
            in: '09:22',
            out: '18:10',
            h: '8h 48m',
            s: 'late'
        },
        {
            date: 'Dec 18',
            day: 'Thu',
            in: '09:04',
            out: '18:05',
            h: '9h 01m',
            s: 'present'
        },
        {
            date: 'Dec 19',
            day: 'Fri',
            in: '09:00',
            out: '18:00',
            h: '9h 00m',
            s: 'present'
        },
        {
            date: 'Dec 22',
            day: 'Mon',
            in: '09:06',
            out: '18:10',
            h: '9h 04m',
            s: 'present'
        },
        {
            date: 'Dec 23',
            day: 'Tue',
            in: '09:02',
            out: '18:05',
            h: '9h 03m',
            s: 'present'
        },
        {
            date: 'Dec 24',
            day: 'Wed',
            in: '09:08',
            out: '18:00',
            h: '8h 52m',
            s: 'present'
        },
        {
            date: 'Dec 25',
            day: 'Thu',
            in: '—',
            out: '—',
            h: '—',
            s: 'leave'
        },
        {
            date: 'Dec 26',
            day: 'Fri',
            in: '—',
            out: '—',
            h: '—',
            s: 'leave'
        },
        {
            date: 'Dec 29',
            day: 'Mon',
            in: '09:03',
            out: '18:05',
            h: '9h 02m',
            s: 'present'
        },
        {
            date: 'Dec 30',
            day: 'Tue',
            in: '09:05',
            out: '18:00',
            h: '8h 55m',
            s: 'present'
        },
        {
            date: 'Dec 31',
            day: 'Wed',
            in: '—',
            out: '—',
            h: '—',
            s: 'leave'
        },
    ];

    /* ── BUILD ATTENDANCE TABLE ── */
    const attBody = document.getElementById('att-table-body');
    attData.forEach(r => {
        const map = {
            present: 'badge-present',
            late: 'badge-late',
            absent: 'badge-absent',
            leave: 'badge-leave'
        };
        const label = {
            present: 'Present',
            late: 'Late',
            absent: 'Absent',
            leave: 'On Leave'
        };
        attBody.innerHTML += `<tr>
      <td>${r.date}</td>
      <td style="color:var(--muted)">${r.day}</td>
      <td>${r.in}</td><td>${r.out}</td>
      <td style="font-family:'Space Mono',monospace;font-size:.8rem">${r.h}</td>
      <td><span class="leave-badge ${map[r.s]}">${label[r.s]}</span></td>
    </tr>`;
    });

    /* ── BUILD TASK CARDS (dashboard preview) ── */
    function buildTaskCard(t, slim) {
        const pMap = {
            high: 'priority-high',
            med: 'priority-med',
            low: 'priority-low'
        };
        const pLabel = {
            high: 'High',
            med: 'Medium',
            low: 'Low'
        };
        const sMap = {
            done: 'task-status-done',
            prog: 'task-status-prog',
            pend: 'task-status-pend'
        };
        const sLabel = {
            done: 'Done',
            prog: 'In Progress',
            pend: 'Pending'
        };
        return `<div class="${slim?'col-md-4':'col-md-6 col-lg-4'}">
      <div class="task-card">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div class="task-title">${t.title}</div>
          <span class="task-priority ${pMap[t.priority]}">${pLabel[t.priority]}</span>
        </div>
        <div class="task-meta">${t.desc} · Due ${t.due}</div>
        <div class="mt-2"><span class="chip ${sMap[t.status]}" style="font-size:.7rem">${sLabel[t.status]}</span></div>
      </div>
    </div>`;
    }

    // Dashboard: show 6
    document.getElementById('dash-tasks').innerHTML = tasks.slice(0, 6).map(t => buildTaskCard(t, false)).join('');
    // All tasks page
    document.getElementById('all-tasks').innerHTML = tasks.map(t => buildTaskCard(t, false)).join('');

    /* ── PAGE NAVIGATION ── */
    const titles = {
        dashboard: 'Dashboard Overview',
        attendance: 'Attendance Details',
        leaves: 'Leave Management',
        tasks: 'My Tasks',
        profile: 'My Profile'
    };

    function showPage(id, btn) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.getElementById('page-' + id).classList.add('active');
        document.getElementById('topbar-title').textContent = titles[id];
        if (btn) btn.classList.add('active');
        document.getElementById('sidebar').classList.remove('open');
    }

    /* ── MONTH CHANGE (demo) ── */
    function updateMonth() {
        const m = document.getElementById('monthSelect').value;
        const presents = [20, 18, 22, 21, 20, 19, 23, 22, 21, 22, 21, 22];
        const lates = [2, 3, 1, 2, 3, 1, 2, 2, 3, 1, 2, 3];
        const absents = [1, 2, 0, 1, 2, 1, 0, 1, 2, 0, 1, 2];
        const leaves = [3, 2, 3, 2, 1, 4, 1, 1, 2, 3, 2, 4];
        document.getElementById('dash-present').textContent = presents[m];
        document.getElementById('dash-late').textContent = lates[m];
        document.getElementById('dash-absent').textContent = absents[m];
        document.getElementById('dash-leave').textContent = leaves[m];
    }
</script>