% Mode Target Entry — Final Implementation Plan
Date: 20 June 2026 | Role: Senior Backend Developer + Project Engineer

🎯 Goal সংক্ষেপ
QTY নেই এমন কাজের জন্য % দিয়ে monthly → weekly → daily target ভাগ করা এবং DPR-এ % entry দিয়ে QC ও PM approval করা।


PM sets Monthly % Target (80%)
        ↓
PM breaks into Weekly % (W1:20%, W2:10%, W3:40%, W4:10%)
        ↓
PM/System breaks Weekly into Daily % grid
(W1: D1:4%, D2:2%, D3:4%, D4:4%, D5:2%, D6:2%, D7:2% = 20%)
        ↓
Site Engineer DPR-এ daily pct_done_today entry দেয়
        ↓
QC approves daily log → PM approves weekly
        ↓
actual_progress_pct = SUM(approved pct_done_today)
📊 Affected Pages (সব লিঙ্ক)
Page	Route	File
Monthly Target Detail	/con-monthly-target/{id}	
form.php
Monthly Target List	/con-monthly-targets/{project_id}	
index.php
Site Engineer Targets	/con-project-plan-targets	
site-engineer-targets.php
Weekly Progress / DPR	/con-weekly-progress/{target_id}?week=1	
entry.php
QC Approval	/con-qc/*	
qc/ views
✅ STEP 1 — Database Migration (SQL)
File: নতুন migration SQL চালাতে হবে phpMyAdmin বা directly।

1A. phase_monthly_target_items table আপডেট
sql

ALTER TABLE phase_monthly_target_items
    ADD COLUMN target_mode        ENUM('qty','pct') NOT NULL DEFAULT 'qty'
        AFTER unit,
    ADD COLUMN monthly_target_pct DECIMAL(5,2)      NOT NULL DEFAULT 0.00
        AFTER monthly_target_qty,
    ADD COLUMN week_1_pct         DECIMAL(5,2)      NOT NULL DEFAULT 0.00
        AFTER week_4_qty,
    ADD COLUMN week_2_pct         DECIMAL(5,2)      NOT NULL DEFAULT 0.00
        AFTER week_1_pct,
    ADD COLUMN week_3_pct         DECIMAL(5,2)      NOT NULL DEFAULT 0.00
        AFTER week_2_pct,
    ADD COLUMN week_4_pct         DECIMAL(5,2)      NOT NULL DEFAULT 0.00
        AFTER week_3_pct;
1B. নতুন daily_target_pct_breakdown table (Weekly → Daily % grid)
sql

CREATE TABLE daily_target_pct_breakdown (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    target_item_id INT UNSIGNED NOT NULL,
    week_number    TINYINT      NOT NULL COMMENT '1,2,3,4',
    day_number     TINYINT      NOT NULL COMMENT '1 to 7',
    target_pct     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_week_day (target_item_id, week_number, day_number),
    FOREIGN KEY (target_item_id)
        REFERENCES phase_monthly_target_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
1C. daily_work_item_logs table আপডেট
sql

ALTER TABLE daily_work_item_logs
    ADD COLUMN pct_done_today DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER qty_done_today;
1D. phase_weekly_progress_items table আপডেট (% mode-এর জন্য planned/achieved %)
sql

ALTER TABLE phase_weekly_progress_items
    ADD COLUMN planned_pct  DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER planned_qty,
    ADD COLUMN achieved_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00
        AFTER achieved_qty;
✅ STEP 2 — Model Update
File: 
Con_MonthlyTarget.php

2A. createItem() — নতুন fields যোগ করো
INSERT statement-এ নতুন columns:


target_mode, monthly_target_pct,
week_1_pct, week_2_pct, week_3_pct, week_4_pct
Logic rule:

যদি target_mode = 'pct' হয় → monthly_target_qty = 0, unit = null (optional)
যদি target_mode = 'qty' হয় → monthly_target_pct = 0
2B. batchUpdateItems() — নতুন fields handle করো
একই নতুন columns update statement-এ যোগ করো।

Extra validation logic (PHP-এ):

php

// % mode validation: week_1_pct + week_2_pct + week_3_pct + week_4_pct <= monthly_target_pct
if ($mode === 'pct') {
    $weekTotal = $w1pct + $w2pct + $w3pct + $w4pct;
    if (round($weekTotal, 2) > round($monthlyPct, 2)) {
        throw new Exception("Weekly % total ({$weekTotal}%) exceeds monthly target ({$monthlyPct}%).");
    }
}
2C. নতুন method: saveDailyPctBreakdown(int $item_id, array $grid): bool
php

// $grid = [ ['week'=>1,'day'=>1,'pct'=>4.00], ['week'=>1,'day'=>2,'pct'=>2.00], ... ]
// UPSERT into daily_target_pct_breakdown
// Validate: SUM of day pcts per week == week_N_pct
2D. নতুন method: getDailyPctBreakdown(int $item_id): array
php

SELECT week_number, day_number, target_pct
FROM daily_target_pct_breakdown
WHERE target_item_id = ?
ORDER BY week_number, day_number
2E. getItems() — নতুন columns select করো
SELECT pmti.* এর মধ্যে নতুন columns automatically আসবে। কিন্তু daily breakdown আলাদাভাবে load করতে হবে (controller-এ)।

✅ STEP 3 — Controller Update
File: 
Con_MonthlyTargetController.php

3A. addItem() — নতুন POST fields handle
php

'target_mode'         => $_POST['target_mode'] ?? 'qty',
'monthly_target_pct'  => (float)($_POST['monthly_target_pct'] ?? 0),
'week_1_pct'          => (float)($_POST['week_1_pct'] ?? 0),
'week_2_pct'          => (float)($_POST['week_2_pct'] ?? 0),
'week_3_pct'          => (float)($_POST['week_3_pct'] ?? 0),
'week_4_pct'          => (float)($_POST['week_4_pct'] ?? 0),
// If pct mode: force qty = 0
'monthly_target_qty'  => ($mode === 'pct') ? 0 : (float)($_POST['monthly_target_qty'] ?? 0),
3B. saveItems() — নতুন fields batch save
Same as above, $raw array থেকে নতুন fields নেবে।

3C. নতুন AJAX endpoint: saveDailyPctBreakdown()

POST /con-monthly-item-daily-breakdown
Body: { item_id, grid: [{week,day,pct}, ...] }
Response: { success, message }
Validation:

Week 1 এর 7 দিনের sum == week_1_pct
Week 2 এর 7 দিনের sum == week_2_pct
একই for Week 3, 4
3D. show() — daily breakdown load করো
php

// Load daily breakdown for all pct items
$dailyBreakdowns = [];
foreach ($items as $item) {
    if ($item['target_mode'] === 'pct') {
        $dailyBreakdowns[$item['id']] = $this->model->getDailyPctBreakdown($item['id']);
    }
}
// Pass to view
$this->view('...', ['dailyBreakdowns' => $dailyBreakdowns, ...]);
✅ STEP 4 — Monthly Target Form View Update
File: 
form.php

4A. "Add Item" form-এ Mode Toggle যোগ করো
html

<!-- Mode Toggle (QTY / PCT) -->
<div class="mf-ctrl" style="flex:0 0 160px">
    <label>Entry Mode</label>
    <div class="mode-toggle">
        <button type="button" id="btn_mode_qty" class="mode-btn active" onclick="setMode('qty')">
            📦 QTY Mode
        </button>
        <button type="button" id="btn_mode_pct" class="mode-btn" onclick="setMode('pct')">
            📊 % Mode
        </button>
    </div>
    <input type="hidden" id="wi_mode" value="qty">
</div>
<!-- QTY Mode fields (show/hide) -->
<div id="qty_fields">
    <label>Target Qty</label>
    <input type="number" id="wi_qty" ...>
</div>
<!-- PCT Mode fields (show/hide, hidden by default) -->
<div id="pct_fields" style="display:none">
    <label>Monthly Target %</label>
    <input type="number" id="wi_monthly_pct" step="0.01" min="0" max="100" placeholder="80.00">
    <small>Week breakdown:</small>
    W1: <input type="number" id="wi_w1_pct" style="width:55px"> %
    W2: <input type="number" id="wi_w2_pct" style="width:55px"> %
    W3: <input type="number" id="wi_w3_pct" style="width:55px"> %
    W4: <input type="number" id="wi_w4_pct" style="width:55px"> %
    <span id="pct_sum_check"></span> <!-- ✅ 80% / ❌ 90% -->
</div>
4B. Item Table-এ Mode Badge + PCT columns যোগ করো
Table header-এ:

html

<th>Mode</th>
<th>Monthly Qty / %</th>
<th>Wk1 Qty/% | Wk2 | Wk3 | Wk4</th>
<th>Daily Breakdown</th>  <!-- নতুন column -->
Table row-এ (PHP):

php

// Mode badge
$mode = $item['target_mode'] ?? 'qty';
$modeBadge = $mode === 'pct'
    ? '<span class="badge-pct">📊 %</span>'
    : '<span class="badge-qty">📦 QTY</span>';
// Show qty OR pct based on mode
if ($mode === 'pct') {
    echo $item['monthly_target_pct'] . '%';
    // week cols: week_1_pct, week_2_pct...
} else {
    echo $item['monthly_target_qty'];
    // week cols: week_1_qty, week_2_qty...
}
// Daily breakdown button (only for pct mode)
if ($mode === 'pct') {
    echo '<button onclick="openDailyGrid('.$item['id'].')" class="btn-daily-grid">
              📅 Daily Grid
          </button>';
}
4C. নতুন Daily Breakdown Modal
html

<!-- Daily % Grid Modal -->
<div id="dailyGridModal" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:700px">
        <h5>📅 Daily % Breakdown</h5>
        <p id="dg_item_name" style="color:var(--text-muted)"></p>
        
        <table class="dg-table">
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Day 1</th><th>Day 2</th><th>Day 3</th>
                    <th>Day 4</th><th>Day 5</th><th>Day 6</th><th>Day 7</th>
                    <th>Total</th><th>Target</th><th>✓</th>
                </tr>
            </thead>
            <tbody id="dg_body">
                <!-- JS দিয়ে 4 rows generate হবে -->
            </tbody>
        </table>
        
        <div class="modal-footer">
            <button onclick="autoDistribute()" class="btn-auto">
                ⚡ Auto Distribute
            </button>
            <button onclick="saveDailyGrid()" class="btn-save">
                💾 Save Breakdown
            </button>
            <button onclick="closeDailyGrid()" class="btn-cancel">
                Cancel
            </button>
        </div>
    </div>
</div>
JS Logic:

javascript

// Auto Distribute: প্রতিটি week-এর % কে 7 দিনে সমানভাবে ভাগ করে
function autoDistribute() {
    [1,2,3,4].forEach(w => {
        let weekPct = parseFloat(document.querySelector(`#w${w}_pct_val`).value) || 0;
        let base = Math.floor((weekPct / 7) * 100) / 100;
        let remainder = parseFloat((weekPct - base * 7).toFixed(2));
        for (let d = 1; d <= 7; d++) {
            let val = base + (d === 1 ? remainder : 0);
            document.querySelector(`#cell_w${w}_d${d}`).value = val.toFixed(2);
        }
        updateRowTotal(w);
    });
}
// Validation: row total == week target pct
function updateRowTotal(weekNum) {
    let total = 0;
    for (let d = 1; d <= 7; d++) {
        total += parseFloat(document.querySelector(`#cell_w${weekNum}_d${d}`).value) || 0;
    }
    let target = parseFloat(document.querySelector(`#w${weekNum}_pct_val`).value) || 0;
    let ok = Math.abs(total - target) < 0.01;
    document.querySelector(`#row_check_w${weekNum}`).textContent = ok ? '✅' : `❌ ${total.toFixed(2)}%`;
}
✅ STEP 5 — Weekly Progress / DPR Entry Update
File: 
entry.php
 Controller: 
Con_PhaseWeeklyProgressController.php

5A. DPR Daily Log Form-এ % mode support

প্রতিটি work item row-এ:
- যদি target_mode = 'qty'  → বর্তমান মতো qty_done_today input দেখাবে
- যদি target_mode = 'pct'  → pct_done_today input দেখাবে (%), qty input লুকাবে
View-এ (PHP):

php

if ($item['target_mode'] === 'pct') {
    // PCT Mode — দেখাও daily target pct এবং input
    $todayTarget = $dailyBreakdowns[$item['id']][$weekNum][$dayNum] ?? 0;
    echo '
    <div class="dpr-pct-row">
        <span class="daily-target-pct">Daily Target: ' . $todayTarget . '%</span>
        <input type="number" 
               name="items[' . $item['id'] . '][pct_done_today]"
               step="0.01" min="0" max="100"
               placeholder="e.g. 4.00"
               class="pct-input">
        <span class="pct-unit">%</span>
    </div>';
} else {
    // QTY Mode — বর্তমান মতো
    echo '<input type="number" name="items[' . $item['id'] . '][qty_done_today]" ...>';
}
5B. Controller-এ daily log save-এ pct_done_today store করো
php

// daily_work_item_logs INSERT/UPDATE-এ
$pctDoneToday = (float)($itemData['pct_done_today'] ?? 0);
$qtyDoneToday = ($item['target_mode'] === 'pct') ? 0 : (float)($itemData['qty_done_today'] ?? 0);
5C. Progress calculation — % mode item-এর জন্য
php

// actual_progress_pct (% mode) = SUM(pct_done_today) for approved daily logs
// phase_monthly_target_items UPDATE:
UPDATE phase_monthly_target_items
SET actual_progress_pct = (
    SELECT COALESCE(SUM(dwil.pct_done_today), 0)
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    WHERE dwil.target_item_id = pmti.id
      AND dpl.pm_status = 'Approved'
)
WHERE target_mode = 'pct' AND id = ?
5D. Weekly Progress Items-এ achieved_pct update
sql

UPDATE phase_weekly_progress_items
SET achieved_pct = (
    SELECT COALESCE(SUM(dwil.pct_done_today), 0)
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    WHERE dwil.target_item_id = pwpi.target_item_id
      AND dpl.weekly_progress_id = pwpi.weekly_progress_id
      AND dpl.pm_status = 'Approved'
)
WHERE target_item_id IN (SELECT id FROM phase_monthly_target_items WHERE target_mode = 'pct')
✅ STEP 6 — QC Approval Flow
Views: 
qc/ views
 Controller: 
QCController.php

6A. QC daily log review page-এ % info দেখাও

QC যখন daily log review করবে, সে দেখতে পাবে:
Work Item         | Mode | Today Target | Submitted  | Status
------------------|------|-------------|------------|-------
Brick Work        | QTY  | 120 RFT     | 115.00 RFT | ✅ Good
Plastering Finish | %    | 4.00%       | 4.00%      | ✅ On target
Interior Paint    | %    | 2.00%       | 1.50%      | ⚠️ Short
6B. QC Approval Logic (কোনো পরিবর্তন নেই!)

QC approves/rejects daily_progress_logs.qc_status
→ 'Approved' / 'Rejected'
একই flow বহাল থাকবে — % mode item-এর জন্য আলাদা কোনো approval logic নেই।
পার্থক্য শুধু display-এ।
6C. QC page-এ % mode item-এর জন্য visual indicator
php

if ($item['target_mode'] === 'pct') {
    $todayTarget = $dailyBreakdown[$item['id']][$weekNum][$dayNum] ?? 0;
    $submitted   = $item['pct_done_today'];
    $gap = $todayTarget - $submitted;
    $icon = abs($gap) < 0.1 ? '✅' : ($gap > 0 ? '⚠️ Short' : '📈 Over');
}
✅ STEP 7 — PM Approval Flow
Controller: 
Con_PhaseWeeklyProgressController.php

7A. PM Weekly Review-এ % progress summary দেখাও

Weekly Progress Summary (Week 1):
Item              | Mode | Planned % | Achieved % | Status
Brick Work        | QTY  | 24%       | 23%        | 🟡 Near
Plastering        | %    | 20%       | 18.5%      | 🟡 Short
Interior Paint    | %    | 15%       | 15%        | 🟢 On Target
7B. PM Approval Trigger করলে actual_progress_pct sync হবে
php

// PM approves weekly progress → trigger recalculation
if ($newStatus === 'Approved') {
    // For each pct-mode item in this weekly session:
    $this->recalcPctModeProgress($target_item_id);
}
function recalcPctModeProgress($item_id) {
    // SUM all approved pct_done_today → UPDATE phase_monthly_target_items.actual_progress_pct
    // Then sync → project_phase_items.actual_progress
    // Then sync → project_phases.actual_progress
    // Then sync → projects.actual_progress
}
7C. PM Reject করলে (বর্তমান logic বহাল)

PM rejects → weekly_progress.pm_status = 'Rejected'
→ phase_weekly_progress এর সম্পূর্ণ session Draft-এ যায়
→ Site Engineer পুনরায় submit করবে
(কোনো পরিবর্তন নেই — % mode-এ একই behavior)
📐 Full Data Flow Diagram

PM Input (form.php):
┌────────────────────────────────────────────────────────────┐
│ phase_monthly_target_items                                  │
│   target_mode = 'pct'                                      │
│   monthly_target_pct = 80.00                               │
│   week_1_pct=20, week_2_pct=10, week_3_pct=40, week_4_pct=10│
└────────────────────────────────────────────────────────────┘
              ↓
┌────────────────────────────────────────────────────────────┐
│ daily_target_pct_breakdown                                  │
│   week=1, day=1 → 4.00%                                    │
│   week=1, day=2 → 2.00%   (Total W1 = 20%)               │
│   ...                                                       │
└────────────────────────────────────────────────────────────┘
              ↓ (Site Engineer DPR entry)
┌────────────────────────────────────────────────────────────┐
│ daily_work_item_logs                                        │
│   pct_done_today = 3.50%   (Day 1 of Week 1)              │
│   qty_done_today = 0       (pct mode, always 0)            │
└────────────────────────────────────────────────────────────┘
              ↓ (QC approves daily log)
┌────────────────────────────────────────────────────────────┐
│ daily_progress_logs.qc_status = 'Approved'                 │
└────────────────────────────────────────────────────────────┘
              ↓ (PM approves weekly)
┌────────────────────────────────────────────────────────────┐
│ phase_weekly_progress.pm_status = 'Approved'               │
│ phase_weekly_progress_items.achieved_pct += 3.50%          │
└────────────────────────────────────────────────────────────┘
              ↓ (Auto sync)
┌────────────────────────────────────────────────────────────┐
│ phase_monthly_target_items.actual_progress_pct = SUM(pct)  │
│ project_phase_items.actual_progress (synced)               │
│ project_phases.actual_progress (weighted sum)              │
│ projects.actual_progress (overall)                         │
└────────────────────────────────────────────────────────────┘
📋 Implementation Checklist
[x] STEP 1A — ALTER phase_monthly_target_items (6 নতুন columns)
[x] STEP 1B — CREATE daily_target_pct_breakdown table
[x] STEP 1C — ALTER daily_work_item_logs (pct_done_today column)
[x] STEP 1D — ALTER phase_weekly_progress_items (planned_pct, achieved_pct)
[x] STEP 2A — Model: createItem() আপডেট
[x] STEP 2B — Model: batchUpdateItems() আপডেট (% validation সহ)
[x] STEP 2C — Model: saveDailyPctBreakdown() yeni method
[x] STEP 2D — Model: getDailyPctBreakdown() yeni method
[x] STEP 3A — Controller: addItem() আপডেট
[x] STEP 3B — Controller: saveItems() আপডেট
[x] STEP 3C — Controller: নতুন saveDailyPctBreakdown() AJAX endpoint
[x] STEP 3D — Controller: show() — daily breakdown load
[x] STEP 4A — View form.php: Mode toggle + PCT input fields
[x] STEP 4B — View form.php: Table row-এ mode badge + conditional columns
[x] STEP 4C — View form.php: Daily Breakdown Modal + Auto Distribute
[x] STEP 5A — View entry.php: DPR-এ % mode input
[x] STEP 5B — Controller: DPR save-এ pct_done_today store
[x] STEP 5C — Controller: % mode progress recalculation logic
[x] STEP 5D — phase_weekly_progress_items.achieved_pct update
[x] STEP 6A–C — QC view-এ % mode display + visual indicator
[x] STEP 7A–C — PM Approval-এ % sync + recalc trigger
[ ] End-to-end Test




## ## ## ############### ###
হ্যাঁ, Site Engineer যেখানে entry দেয় (অর্থাৎ DPR/Weekly Progress Entry page - entry.php এ), সেখানে Entry Mode (QTY or %) এর উপর ভিত্তি করে কলামগুলো dynamically পরিবর্তিত হবে।

নিচে বিস্তারিত দেওয়া হলো কীভাবে পরিবর্তন হবে:

Unit (একক) কলাম:

QTY Mode: আগের মতোই RFT, CFT বা SFT দেখাবে।
% Mode: একক হিসেবে সরাসরি % দেখাবে।
Planned Qty & Achieved Qty কলাম:

QTY Mode: এই সপ্তাহের target quantity এবং এ পর্যন্ত অর্জিত quantity দেখাবে (যেমন: Planned: 120.00 | Achieved: 45.00)।
% Mode: এই সপ্তাহের target percent এবং অর্জিত cumulative progress দেখাবে (যেমন: Planned: 20.00% | Achieved: 4.00%)।
D1 থেকে D7 (Daily History) কলাম:

QTY Mode: পূর্ববর্তী দিনগুলোর অর্জিত quantity দেখাবে।
% Mode: পূর্ববর্তী দিনগুলোর অর্জিত percentage দেখাবে (যেমন: 4.00%, 2.00%)।
Today's Progress (Entry Input) কলাম:

QTY Mode: Site Engineer আগের মতোই physical quantity ইনপুট দিতে পারবেন (যেমন: 15.50 RFT)।
% Mode:
ইনপুট বক্সের উপরে আজকের দিনের planned target দেখাবে (যেমন: Target: 4.00%) যা PM সেট করে দিয়েছিল।
ইনপুট বক্সে percentage এন্ট্রি নেওয়ার জন্য placeholder এবং step configuration থাকবে (0.01 step size সহ), যাতে Site Engineer সহজেই আজকের অর্জিত কাজের percentage (যেমন: 4.00 বা 3.50) এন্ট্রি দিতে পারেন।
সবচেয়ে বড় সুবিধা হচ্ছে, একই পেজে একই সাথে কিছু আইটেম QTY মোডে এবং কিছু আইটেম % মোডে থাকতে পারবে। সিস্টেম স্বয়ংক্রিয়ভাবে ডিটেক্ট করে সঠিক ইনপুট ও টার্গেট কলাম প্রদর্শন করবে।