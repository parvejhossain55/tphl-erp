<style>
  :root {
    --brand: #1a2744;
    --brand-light: #2563eb;
    --surface: #f4f6fb;
    --card-radius: 14px;
  }

  /* ── reuse your existing wrappers ── */
  .card__wrapper {
    background: #fff;
    border-radius: var(--card-radius);
    border: 1px solid #e8ecf3;
    padding: 18px 20px;
    height: 100%;
  }

  .table__wrapper {
    border-radius: 0 0 var(--card-radius) var(--card-radius);
    overflow: hidden;
  }

  /* ── stat cards ── */
  .stat-card .label {
    font-size: .74rem;
    color: #78909c;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
  }

  .stat-card .value {
    font-size: 1.7rem;
    font-weight: 700;
    color: #1a2744;
    line-height: 1.1;
  }

  .stat-card .change {
    font-size: .76rem;
    margin-top: 4px;
  }

  /* ── section title ── */
  .section-title {
    font-size: .82rem;
    font-weight: 700;
    color: #1a2744;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 14px;
  }

  /* ── team cards ── */
  .team-card {
    background: #fff;
    border: 1.5px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 16px;
    cursor: pointer;
    transition: border-color .18s, box-shadow .18s;
  }

  .team-card:hover {
    border-color: #2563eb;
    box-shadow: 0 2px 12px rgba(37, 99, 235, .08);
  }

  .team-card.active {
    border-color: #2563eb;
    background: #f0f5ff;
  }

  .team-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 700;
    margin-bottom: 10px;
  }

  .team-name {
    font-size: .85rem;
    font-weight: 700;
    color: #1a2744;
  }

  .team-lead {
    font-size: .74rem;
    color: #78909c;
    margin-bottom: 8px;
  }

  .team-spend {
    font-size: .76rem;
    color: #546e7a;
    margin-top: 5px;
  }

  /* ── progress bar ── */
  .prog-bg {
    height: 5px;
    background: #edf0f6;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
  }

  .prog-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .4s ease;
  }

  /* ── platform cards ── */
  .plat-card {
    background: #fff;
    border: 1px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 16px;
  }

  .plat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
  }

  .plat-budget {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a2744;
    margin: 8px 0 2px;
  }

  .plat-meta {
    font-size: .74rem;
    color: #78909c;
  }

  /* ── summary strip ── */
  .sum-strip {
    background: #fff;
    border: 1px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 14px 20px;
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .sum-item .s-label {
    font-size: .72rem;
    color: #78909c;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .sum-item .s-val {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a2744;
  }

  /* ── tabs ── */
  .nav-pills .nav-link {
    font-size: .8rem;
    border-radius: 8px;
    color: #546e7a;
    padding: 6px 16px;
  }

  .nav-pills .nav-link.active {
    background: #1a2744;
    color: #fff;
  }

  /* ── add-row ── */
  .add-row select,
  .add-row input {
    font-size: .82rem;
    border-radius: 8px;
    border: 1px solid #dde3ef;
    padding: 7px 10px;
    color: #1a2744;
  }

  .add-row .btn-brand {
    background: #1a2744;
    color: #fff;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    padding: 7px 18px;
    border: none;
  }

  .add-row .btn-brand:hover {
    background: #2563eb;
  }

  /* ── table ── */
  .table thead th {
    background: #f7f9fc;
    font-size: .76rem;
    font-weight: 700;
    color: #78909c;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid #e8ecf3;
    padding: 10px 14px;
  }

  .table tbody td {
    font-size: .83rem;
    color: #1a2744;
    padding: 10px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f3f8;
  }

  .table tbody tr:hover td {
    background: #f7f9fc;
  }

  /* ── badges ── */
  .bg-info-soft {
    background: #e3f2fd;
  }

  .text-info-dark {
    color: #0d47a1;
  }

  .bg-warn-soft {
    background: #fff8e1;
  }

  .text-warn-dark {
    color: #f57f17;
  }

  .bg-success-soft {
    background: #e8f5e9;
  }

  .text-success-dark {
    color: #1b5e20;
  }

  .bg-danger-soft {
    background: #fce4ec;
  }

  .text-danger-dark {
    color: #880e4f;
  }

  .bg-purple-soft {
    background: #ede7f6;
  }

  .text-purple-dark {
    color: #4527a0;
  }

  /* ── chart legend ── */
  .chart-legend span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .76rem;
    color: #546e7a;
  }

  .leg-box {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    display: inline-block;
  }
</style>


<div class="app__slide-wrapper">
  <div class="row mb-4">
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Total Monthly Budget</div>
        <div class="value">৳18,50,000</div>
        <div class="change text-success"><i class="bi bi-arrow-up-short"></i>5 Active Teams</div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Total Spent</div>
        <div class="value">৳11,37,500</div>
        <div class="change text-danger"><i class="bi bi-arrow-up-short"></i>61.5% of budget</div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Remaining</div>
        <div class="value">৳7,12,500</div>
        <div class="change text-muted"><i class="bi bi-clock"></i> 12 days left</div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Top Platform</div>
        <div class="value">Facebook</div>
        <div class="change text-success"><i class="bi bi-graph-up"></i> ৳4,50,000 spent</div>
      </div>
    </div>
  </div>

  <!-- ════ TEAMS ════ -->
  <div class="section-title"><i class="bi bi-people-fill me-2 text-primary"></i>Marketing Teams</div>
  <div class="row mb-4" id="teamGrid">

    <!-- Team Alpha -->
    <div class="col-xl-2 col-lg-3 col-sm-4 col-6 mb-3">
      <div class="team-card active" onclick="selectTeam(this,'alpha')">
        <div class="team-avatar" style="background:#dbeafe;color:#1d4ed8;">A</div>
        <div class="team-name">Team Alpha</div>
        <div class="team-lead"><i class="bi bi-person-fill me-1"></i>Lead: Rahim</div>
        <div class="team-spend">৳2,30,000 / ৳4,00,000</div>
        <div class="prog-bg">
          <div class="prog-fill" style="width:57%;background:#2563eb;"></div>
        </div>
      </div>
    </div>

    <!-- Team Beta -->
    <div class="col-xl-2 col-lg-3 col-sm-4 col-6 mb-3">
      <div class="team-card" onclick="selectTeam(this,'beta')">
        <div class="team-avatar" style="background:#fce7f3;color:#be185d;">B</div>
        <div class="team-name">Team Beta</div>
        <div class="team-lead"><i class="bi bi-person-fill me-1"></i>Lead: Karim</div>
        <div class="team-spend">৳2,80,000 / ৳3,50,000</div>
        <div class="prog-bg">
          <div class="prog-fill" style="width:80%;background:#be185d;"></div>
        </div>
      </div>
    </div>

    <!-- Team Gamma -->
    <div class="col-xl-2 col-lg-3 col-sm-4 col-6 mb-3">
      <div class="team-card" onclick="selectTeam(this,'gamma')">
        <div class="team-avatar" style="background:#fef9c3;color:#92400e;">G</div>
        <div class="team-name">Team Gamma</div>
        <div class="team-lead"><i class="bi bi-person-fill me-1"></i>Lead: Nadia</div>
        <div class="team-spend">৳1,95,000 / ৳3,00,000</div>
        <div class="prog-bg">
          <div class="prog-fill" style="width:65%;background:#d97706;"></div>
        </div>
      </div>
    </div>

    <!-- Team Delta -->
    <div class="col-xl-2 col-lg-3 col-sm-4 col-6 mb-3">
      <div class="team-card" onclick="selectTeam(this,'delta')">
        <div class="team-avatar" style="background:#dcfce7;color:#166534;">D</div>
        <div class="team-name">Team Delta</div>
        <div class="team-lead"><i class="bi bi-person-fill me-1"></i>Lead: Sadia</div>
        <div class="team-spend">৳2,00,000 / ৳4,00,000</div>
        <div class="prog-bg">
          <div class="prog-fill" style="width:50%;background:#16a34a;"></div>
        </div>
      </div>
    </div>

    <!-- Team Omega -->
    <div class="col-xl-2 col-lg-3 col-sm-4 col-6 mb-3">
      <div class="team-card" onclick="selectTeam(this,'omega')">
        <div class="team-avatar" style="background:#ede9fe;color:#6d28d9;">O</div>
        <div class="team-name">Team Omega</div>
        <div class="team-lead"><i class="bi bi-person-fill me-1"></i>Lead: Tariq</div>
        <div class="team-spend">৳1,32,500 / ৳2,00,000</div>
        <div class="prog-bg">
          <div class="prog-fill" style="width:66%;background:#7c3aed;"></div>
        </div>
      </div>
    </div>

  </div><!-- /team row -->

  <!-- ════ TABS ════ -->
  <ul class="nav nav-pills mb-3" id="budgetTabs">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="showTab('platform',this);return false;">
        <i class="bi bi-pie-chart-fill me-1"></i>Platform Budget</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('add',this);return false;">
        <i class="bi bi-plus-circle-fill me-1"></i>Add Budget</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('log',this);return false;">
        <i class="bi bi-clock-history me-1"></i>Budget Log</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('chart',this);return false;">
        <i class="bi bi-bar-chart-line-fill me-1"></i>Chart View</a></li>
  </ul>

  <!-- ── TAB: Platform Budget ── -->
  <div id="tab-platform">

    <!-- summary strip -->
    <div class="sum-strip mb-4">
      <div class="sum-item">
        <div class="s-label">Team Budget</div>
        <div class="s-val">৳4,00,000</div>
      </div>
      <div class="sum-item">
        <div class="s-label">Spent</div>
        <div class="s-val" style="color:#e53e3e;">৳2,30,000</div>
      </div>
      <div class="sum-item">
        <div class="s-label">Remaining</div>
        <div class="s-val" style="color:#16a34a;">৳1,70,000</div>
      </div>
      <div class="sum-item">
        <div class="s-label">Active Platforms</div>
        <div class="s-val">4</div>
      </div>
    </div>

    <!-- platform cards -->
    <div class="row g-3 mb-4">

      <!-- Facebook -->
      <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
        <div class="plat-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="plat-icon" style="background:#dbeafe;color:#1d4ed8;">FB</div>
            <span class="fw-semibold" style="font-size:.85rem;">Facebook</span>
          </div>
          <div class="plat-budget">৳1,20,000</div>
          <div class="plat-meta">Spent: ৳95,000 (79%)</div>
          <div class="prog-bg mt-2">
            <div class="prog-fill" style="width:79%;background:#2563eb;"></div>
          </div>
        </div>
      </div>

      <!-- YouTube -->
      <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
        <div class="plat-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="plat-icon" style="background:#fee2e2;color:#b91c1c;">YT</div>
            <span class="fw-semibold" style="font-size:.85rem;">YouTube</span>
          </div>
          <div class="plat-budget">৳80,000</div>
          <div class="plat-meta">Spent: ৳45,000 (56%)</div>
          <div class="prog-bg mt-2">
            <div class="prog-fill" style="width:56%;background:#dc2626;"></div>
          </div>
        </div>
      </div>

      <!-- Instagram -->
      <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
        <div class="plat-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="plat-icon" style="background:#fce7f3;color:#be185d;">IG</div>
            <span class="fw-semibold" style="font-size:.85rem;">Instagram</span>
          </div>
          <div class="plat-budget">৳1,00,000</div>
          <div class="plat-meta">Spent: ৳70,000 (70%)</div>
          <div class="prog-bg mt-2">
            <div class="prog-fill" style="width:70%;background:#be185d;"></div>
          </div>
        </div>
      </div>

      <!-- Google Ads -->
      <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
        <div class="plat-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="plat-icon" style="background:#dcfce7;color:#166534;">GA</div>
            <span class="fw-semibold" style="font-size:.85rem;">Google Ads</span>
          </div>
          <div class="plat-budget">৳70,000</div>
          <div class="plat-meta">Spent: ৳20,000 (29%)</div>
          <div class="prog-bg mt-2">
            <div class="prog-fill" style="width:29%;background:#16a34a;"></div>
          </div>
        </div>
      </div>

      <!-- TikTok -->
      <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
        <div class="plat-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="plat-icon" style="background:#f3f4f6;color:#111827;">TT</div>
            <span class="fw-semibold" style="font-size:.85rem;">TikTok</span>
          </div>
          <div class="plat-budget">৳30,000</div>
          <div class="plat-meta">Spent: ৳0 (0%)</div>
          <div class="prog-bg mt-2">
            <div class="prog-fill" style="width:0%;background:#374151;"></div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /tab-platform -->

  <!-- ── TAB: Add Budget ── -->
  <div id="tab-add" style="display:none;">
    <div class="card__wrapper mb-4">
      <div class="section-title mb-3"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add / Update Platform Budget</div>
      <div class="row g-2 add-row align-items-end">
        <div class="d-none col-xl-2 col-md-3 col-6">
          <label class="form-label fw-semibold" style="font-size:.78rem;">Team</label>
          <select class="form-select w-100">
            <option>Team Alpha</option>
            <option>Team Beta</option>
            <option>Team Gamma</option>
            <option>Team Delta</option>
            <option>Team Omega</option>
          </select>
        </div>
        <div class="col-xl-2 col-md-3 col-6">
          <label class="form-label fw-semibold" style="font-size:.78rem;">Platform</label>
          <select class="form-select w-100">
            <option>Facebook</option>
            <option>YouTube</option>
            <option>Instagram</option>
            <option>TikTok</option>
            <option>Google Ads</option>
            <option>LinkedIn</option>
            <option>Twitter/X</option>
          </select>
        </div>
        <div class="col-xl-2 col-md-3 col-6">
          <label class="form-label fw-semibold" style="font-size:.78rem;">Month</label>
          <input type="month" class="form-control" value="2025-05" />
        </div>
        <div class="col-xl-2 col-md-3 col-6">
          <label class="form-label fw-semibold" style="font-size:.78rem;">Budget (৳)</label>
          <input type="number" class="form-control" placeholder="e.g. 150000" />
        </div>
        <div class="col-xl-2 col-md-3 col-6">
          <label class="form-label fw-semibold" style="font-size:.78rem;">Spent (৳)</label>
          <input type="number" class="form-control" placeholder="e.g. 80000" />
        </div>
        <div class="col-xl-1 col-md-2 col-6">
          <button class="btn-brand w-100" style="padding:8px 0;border-radius:8px;font-size:.82rem;font-weight:600;background:#1a2744;color:#fff;border:none;cursor:pointer;">
            <i class="bi bi-plus-lg me-1"></i>Add
          </button>
        </div>
      </div>
    </div>
  </div><!-- /tab-add -->

  <!-- ── TAB: Budget Log ── -->
  <div id="tab-log" style="display:none;">
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--card-radius);">
      <div class="card-header bg-white border-bottom fw-bold d-flex justify-content-between align-items-center py-3">
        <span style="font-size:.88rem;color:#1a2744;"><i class="bi bi-clock-history me-2 text-primary"></i>Monthly Budget Log</span>
        <span class="badge bg-primary-soft text-primary rounded-pill">8 Entries</span>
      </div>
      <div class="table__wrapper table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Team</th>
              <th>Platform</th>
              <th>Month</th>
              <th class="text-end">Budget (৳)</th>
              <th class="text-end">Spent (৳)</th>
              <th class="text-end">Remaining</th>
              <th class="text-end pe-4">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="badge bg-info-soft text-info-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Alpha</span></td>
              <td><i class="bi bi-facebook text-primary me-1"></i>Facebook</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">1,20,000</td>
              <td class="text-end">95,000</td>
              <td class="text-end text-success fw-semibold">25,000</td>
              <td class="text-end pe-4"><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">79% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-info-soft text-info-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Alpha</span></td>
              <td><i class="bi bi-youtube text-danger me-1"></i>YouTube</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">80,000</td>
              <td class="text-end">45,000</td>
              <td class="text-end text-success fw-semibold">35,000</td>
              <td class="text-end pe-4"><span class="badge bg-success-soft text-success-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">56% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-danger-soft text-danger-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Beta</span></td>
              <td><i class="bi bi-instagram text-danger me-1"></i>Instagram</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">1,50,000</td>
              <td class="text-end">1,48,000</td>
              <td class="text-end text-success fw-semibold">2,000</td>
              <td class="text-end pe-4"><span class="badge bg-danger-soft text-danger-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">99% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-danger-soft text-danger-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Beta</span></td>
              <td><i class="bi bi-google text-success me-1"></i>Google Ads</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">1,00,000</td>
              <td class="text-end">70,000</td>
              <td class="text-end text-success fw-semibold">30,000</td>
              <td class="text-end pe-4"><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">70% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Gamma</span></td>
              <td><i class="bi bi-facebook text-primary me-1"></i>Facebook</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">1,20,000</td>
              <td class="text-end">85,000</td>
              <td class="text-end text-success fw-semibold">35,000</td>
              <td class="text-end pe-4"><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">71% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Gamma</span></td>
              <td><i class="bi bi-tiktok me-1"></i>TikTok</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">80,000</td>
              <td class="text-end">50,000</td>
              <td class="text-end text-success fw-semibold">30,000</td>
              <td class="text-end pe-4"><span class="badge bg-success-soft text-success-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">63% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-success-soft text-success-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Delta</span></td>
              <td><i class="bi bi-youtube text-danger me-1"></i>YouTube</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">2,00,000</td>
              <td class="text-end">1,10,000</td>
              <td class="text-end text-success fw-semibold">90,000</td>
              <td class="text-end pe-4"><span class="badge bg-success-soft text-success-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">55% used</span></td>
            </tr>
            <tr>
              <td><span class="badge bg-purple-soft text-purple-dark px-2 py-1 rounded-pill" style="font-size:.74rem;">Team Omega</span></td>
              <td><i class="bi bi-instagram text-danger me-1"></i>Instagram</td>
              <td>May 2025</td>
              <td class="text-end fw-semibold">1,00,000</td>
              <td class="text-end">72,500</td>
              <td class="text-end text-success fw-semibold">27,500</td>
              <td class="text-end pe-4"><span class="badge bg-warn-soft text-warn-dark px-2 py-1 rounded-pill" style="font-size:.72rem;">73% used</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab-log -->

  <!-- ── TAB: Chart ── -->
  <div id="tab-chart" style="display:none;">
    <div class="card__wrapper mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Budget vs Spent by Platform</div>
        <div class="chart-legend d-flex gap-3">
          <span><span class="leg-box" style="background:#3b82f6;"></span> Budget</span>
          <span><span class="leg-box" style="background:#f472b6;"></span> Spent</span>
        </div>
      </div>
      <div style="position:relative;width:100%;height:280px;">
        <canvas id="mainChart"></canvas>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card__wrapper">
          <div class="section-title mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Spend Distribution</div>
          <div style="position:relative;width:100%;height:220px;">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card__wrapper">
          <div class="section-title mb-3"><i class="bi bi-people-fill me-2 text-primary"></i>Team Budget Overview</div>
          <div style="position:relative;width:100%;height:220px;">
            <canvas id="teamChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div><!-- /tab-chart -->



  <!-- Recent Leads Table -->
  <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10): ?>
    <div class="row mt-4">
      <div class="col-12">
        <div class="history-card shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Uploaded Leads (Last 7 Days)</h6>
            <span class="badge bg-primary-soft text-primary rounded-pill"><?= count($leads) ?> Leads</span>
          </div>
          <div class="table__wrapper table-responsive">
            <table class="table mb-20 ">
              <thead class="">
                <tr>
                  <th class="ps-4">Lead Name</th>
                  <th>Contact Info</th>
                  <th>Platform & Campaign</th>
                  <th>Team Name</th>
                  <th class="pe-4 text-end">Upload Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($leads)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5">
                      <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No leads uploaded in the last 7 days.
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($leads as $lead): ?>
                    <tr>
                      <td class="ps-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></div>
                        <small class="text-muted"><?= htmlspecialchars($lead['job_title'] ?? 'N/A') ?></small>
                      </td>
                      <td>
                        <div class="d-flex flex-column">
                          <span><i class="bi bi-telephone text-muted me-2"></i><?= htmlspecialchars($lead['phone_number'] ?? 'N/A') ?></span>
                          <small class="text-muted"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($lead['email'] ?? 'N/A') ?></small>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-info-soft text-info mb-1 d-inline-block"><?= htmlspecialchars($lead['platform'] ?? 'N/A') ?></span>
                        <div class="small text-muted"><?= htmlspecialchars($lead['campaign_name'] ?? 'N/A') ?></div>
                      </td>
                      <td>
                        <span class="text-dark"><?= htmlspecialchars($lead['team_name'] ?? 'N/A') ?></span>
                      </td>
                      <td class="pe-4 text-end">
                        <div class="fw-semibold"><?= date('M d, Y', strtotime($lead['created_at'])) ?></div>
                        <small class="text-muted"><?= date('h:i A', strtotime($lead['created_at'])) ?></small>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
  /* ── tab switching ── */
  function showTab(name, el) {
    ['platform', 'add', 'log', 'chart'].forEach(t => {
      document.getElementById('tab-' + t).style.display = t === name ? '' : 'none';
    });
    document.querySelectorAll('#budgetTabs .nav-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    if (name === 'chart') buildCharts();
  }

  /* ── team card select ── */
  function selectTeam(el) {
    document.querySelectorAll('.team-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
  }

  /* ── charts ── */
  let chartsBuilt = false;

  function buildCharts() {
    if (chartsBuilt) return;
    chartsBuilt = true;

    /* bar — budget vs spent per platform */
    new Chart(document.getElementById('mainChart'), {
      type: 'bar',
      data: {
        labels: ['Facebook', 'YouTube', 'Instagram', 'Google Ads', 'TikTok', 'LinkedIn'],
        datasets: [{
            label: 'Budget',
            data: [450000, 280000, 350000, 200000, 110000, 80000],
            backgroundColor: '#3b82f6',
            borderRadius: 5
          },
          {
            label: 'Spent',
            data: [345000, 200000, 290500, 140000, 62500, 40000],
            backgroundColor: '#f472b6',
            borderRadius: 5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            ticks: {
              callback: v => '৳' + (v / 1000) + 'k'
            },
            grid: {
              color: '#f0f3f8'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    /* pie — spend share */
    new Chart(document.getElementById('pieChart'), {
      type: 'doughnut',
      data: {
        labels: ['Facebook', 'YouTube', 'Instagram', 'Google Ads', 'TikTok', 'LinkedIn'],
        datasets: [{
          data: [345000, 200000, 290500, 140000, 62500, 40000],
          backgroundColor: ['#3b82f6', '#dc2626', '#be185d', '#16a34a', '#374151', '#0d47a1'],
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              font: {
                size: 11
              },
              boxWidth: 10
            }
          }
        }
      }
    });

    /* horizontal bar — team budget */
    new Chart(document.getElementById('teamChart'), {
      type: 'bar',
      data: {
        labels: ['Team Alpha', 'Team Beta', 'Team Gamma', 'Team Delta', 'Team Omega'],
        datasets: [{
            label: 'Budget',
            data: [400000, 350000, 300000, 400000, 200000],
            backgroundColor: '#c7d2fe',
            borderRadius: 4
          },
          {
            label: 'Spent',
            data: [230000, 280000, 195000, 200000, 132500],
            backgroundColor: '#6366f1',
            borderRadius: 4
          }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            ticks: {
              callback: v => '৳' + (v / 1000) + 'k'
            },
            grid: {
              color: '#f0f3f8'
            }
          },
          y: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }
</script>
</body>

</html>