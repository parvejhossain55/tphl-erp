<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'] ?? '';

if (!function_exists('coFmt')) {
    function coFmt($n)
    {
        $n = (float)$n;
        if ($n >= 10000000) return '৳' . number_format($n / 10000000, 2) . ' Cr';
        if ($n >= 100000) return '৳' . number_format($n / 100000, 2) . ' L';
        return '৳' . number_format($n, 0);
    }
}
?>
<style>
.co-wrap { padding: 1.25rem 1.5rem 2.5rem; background: #f8fafc; min-height: 100vh; }
.co-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
.co-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.co-sub { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.co-filter-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; }
.co-filter-bar .form-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.25rem; }
.co-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.9rem; margin-bottom: 1.35rem; }
.co-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.1rem; }
.co-kpi-label { font-size: 0.74rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.co-kpi-val { font-size: 1.45rem; font-weight: 700; color: #0f172a; margin: 0.3rem 0 0.15rem; }
.co-kpi-meta { font-size: 0.75rem; color: #64748b; }
.co-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 1.25rem; }
.co-card-hd { padding: 0.9rem 1.15rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.co-card-title { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
.co-tabs { display: flex; gap: 0.4rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 1.1rem; }
.co-tab-btn { border: none; background: transparent; padding: 0.65rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; cursor: pointer; }
.co-tab-btn.active { color: #0f172a; border-bottom-color: #2563eb; }
.co-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.co-table th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.73rem; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.7rem 1rem; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.co-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
.co-table tbody tr:hover { background: #f8fafc; }
.co-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.22rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; }
.co-badge-pd { background: #dbeafe; color: #1d4ed8; }
.co-badge-pc { background: #fef3c7; color: #b45309; }
.co-badge-mgmt { background: #dcfce7; color: #15803d; }
.co-badge-warn { background: #fee2e2; color: #b91c1c; }
.co-badge-ok { background: #dcfce7; color: #15803d; }
</style>

<div class="co-wrap">
    <div class="co-header">
        <div>
            <h1 class="co-title">Central Construction Oversight</h1>
            <p class="co-sub">Unified view of Dept. Head &amp; PC approvals, material deliveries, stock, and budget health</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $base ?>/con-stocks" class="btn btn-sm btn-outline-secondary">Stock Levels</a>
            <a href="<?= $base ?>/con-project-materials-report" class="btn btn-sm btn-outline-secondary">Material Report</a>
            <a href="<?= $base ?>/con-purchase-pending-report" class="btn btn-sm btn-outline-secondary">PR Pending Report</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?= $base ?>/con-central-oversight" class="co-filter-bar">
        <div style="min-width: 210px; flex: 1;">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-select form-select-sm">
                <option value="">All Construction Projects</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ((int)($filters['project_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 210px; flex: 1;">
            <label class="form-label">Approver (PD / PC / Mgmt)</label>
            <select name="approver_id" class="form-select form-select-sm">
                <option value="">All Approvers</option>
                <?php foreach ($approversList as $ap): ?>
                    <?php
                        $rTag = ((int)$ap['role_id'] === 22) ? 'Dept. Head / PD' : (((int)$ap['role_id'] === 15) ? 'PC (On behalf of Dept. Head)' : ($ap['role_name'] ?? 'Mgmt'));
                    ?>
                    <option value="<?= (int)$ap['id'] ?>" <?= ((int)($filters['approver_id'] ?? 0) === (int)$ap['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ap['full_name']) ?> — <?= htmlspecialchars($rTag) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 180px;">
            <label class="form-label">Requisition Type</label>
            <select name="req_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="purchase_requests" <?= ($filters['req_type'] ?? '') === 'purchase_requests' ? 'selected' : '' ?>>Purchase Request (PR)</option>
                <option value="con_labor_requisitions" <?= ($filters['req_type'] ?? '') === 'con_labor_requisitions' ? 'selected' : '' ?>>Labor Requisition</option>
                <option value="fund_requisitions" <?= ($filters['req_type'] ?? '') === 'fund_requisitions' ? 'selected' : '' ?>>Fund Requisition</option>
                <option value="contractor_bill_requests" <?= ($filters['req_type'] ?? '') === 'contractor_bill_requests' ? 'selected' : '' ?>>Contractor Bill</option>
                <option value="initial_estimate_budgets" <?= ($filters['req_type'] ?? '') === 'initial_estimate_budgets' ? 'selected' : '' ?>>Initial Estimate</option>
                <option value="boq_master" <?= ($filters['req_type'] ?? '') === 'boq_master' ? 'selected' : '' ?>>BOQ</option>
            </select>
        </div>
        <div style="min-width: 150px;">
            <label class="form-label">Period</label>
            <select name="range" id="coRangeSelect" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                <option value="all" <?= ($filters['range'] ?? '') === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= ($filters['range'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="7d" <?= ($filters['range'] ?? '') === '7d' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30d" <?= ($filters['range'] ?? '') === '30d' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="month" <?= ($filters['range'] ?? '') === 'month' ? 'selected' : '' ?>>This Month</option>
                <option value="custom" <?= ($filters['range'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
            </select>
        </div>
        <div id="coCustomDateWrap" style="display: <?= ($filters['range'] ?? '') === 'custom' ? 'flex' : 'none' ?>; gap: 0.5rem;">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary px-3">Apply</button>
            <a href="<?= $base ?>/con-central-oversight" class="btn btn-sm btn-light border">Reset</a>
        </div>
    </form>

    <!-- Minimal KPI Strip -->
    <div class="co-kpi-grid">
        <div class="co-kpi">
            <div class="co-kpi-label">Active Projects</div>
            <div class="co-kpi-val"><?= (int)$kpis['active_projects'] ?></div>
            <div class="co-kpi-meta">Under construction</div>
        </div>
        <div class="co-kpi">
            <div class="co-kpi-label">Total Approved (Filtered)</div>
            <div class="co-kpi-val"><?= (int)$kpis['total_approved_count'] ?></div>
            <div class="co-kpi-meta">Value: <?= coFmt($kpis['total_approved_value']) ?></div>
        </div>
        <div class="co-kpi">
            <div class="co-kpi-label">PO Ordered vs Delivered</div>
            <div class="co-kpi-val"><?= coFmt($kpis['delivered_value']) ?></div>
            <div class="co-kpi-meta">of <?= coFmt($kpis['po_ordered_value']) ?> ordered</div>
        </div>
        <div class="co-kpi">
            <div class="co-kpi-label">Current Stock Value</div>
            <div class="co-kpi-val"><?= coFmt($kpis['stock_value']) ?></div>
            <div class="co-kpi-meta">Warehouse &amp; site stock</div>
        </div>
        <div class="co-kpi">
            <div class="co-kpi-label">Pending / Delayed</div>
            <div class="co-kpi-val"><?= (int)$kpis['pending_approvals'] ?> <span class="text-danger fs-6">/ <?= (int)$kpis['delayed_items'] ?> delayed</span></div>
            <div class="co-kpi-meta">Awaiting Dept. Head or Mgmt</div>
        </div>
    </div>

    <!-- Clean Tabs -->
    <div class="co-tabs" role="tablist">
        <button type="button" class="co-tab-btn active" onclick="switchCoTab('approvals', this)">Approver Accountability</button>
        <button type="button" class="co-tab-btn" onclick="switchCoTab('materials', this)">Materials &amp; Delivery</button>
        <button type="button" class="co-tab-btn" onclick="switchCoTab('financial', this)">Budget &amp; Financial</button>
    </div>

    <!-- TAB 1: Approver Accountability -->
    <div id="co-tab-approvals" class="co-tab-pane">
        <div class="co-card">
            <div class="co-card-hd">
                <h2 class="co-card-title">Approval Summary by Person (PD / Dept. Head &amp; PCs)</h2>
                <span class="text-muted small">Click a row to view exact approved items</span>
            </div>
            <div class="table-responsive">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Approver</th>
                            <th>Authority / Capacity</th>
                            <th class="text-end">PRs</th>
                            <th class="text-end">Labor Reqs</th>
                            <th class="text-end">Fund Reqs</th>
                            <th class="text-end">Estimates / BOQs</th>
                            <th class="text-end">Bills</th>
                            <th class="text-end">Total Approved</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approverSummary)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">No approval records found for the selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($approverSummary as $row): ?>
                                <?php
                                    $rId = (int)$row['role_id'];
                                    $badgeCls = ($rId === 22) ? 'co-badge-pd' : (($rId === 15) ? 'co-badge-pc' : 'co-badge-mgmt');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['approver_name']) ?></td>
                                    <td><span class="co-badge <?= $badgeCls ?>"><?= htmlspecialchars($row['role_label']) ?></span></td>
                                    <td class="text-end"><?= (int)$row['pr_count'] ?></td>
                                    <td class="text-end"><?= (int)$row['labor_count'] ?></td>
                                    <td class="text-end"><?= (int)$row['fund_count'] ?></td>
                                    <td class="text-end"><?= (int)$row['est_boq_count'] ?></td>
                                    <td class="text-end"><?= (int)$row['bill_count'] ?></td>
                                    <td class="text-end fw-bold"><?= (int)$row['total_count'] ?></td>
                                    <td class="text-end fw-bold"><?= coFmt($row['total_amount']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2"
                                                onclick="openApproverModal(<?= (int)$row['approver_id'] ?>, '<?= htmlspecialchars(addslashes($row['approver_name'])) ?>')">
                                            View Items
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Materials & Delivery -->
    <div id="co-tab-materials" class="co-tab-pane" style="display:none;">
        <div class="co-card">
            <div class="co-card-hd">
                <h2 class="co-card-title">Project-wise Material Orders &amp; Delivery Status</h2>
                <span class="text-muted small">Click Breakdown to inspect individual materials</span>
            </div>
            <div class="table-responsive">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="text-end">PRs</th>
                            <th class="text-end">POs</th>
                            <th class="text-end">PO Ordered Value</th>
                            <th class="text-end">Delivered (QC Accepted)</th>
                            <th class="text-end">Pending Delivery</th>
                            <th class="text-end">Delayed PRs</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materialPipeline as $mp): ?>
                            <?php $pendingVal = max(0, (float)$mp['po_ordered_value'] - (float)$mp['delivered_value']); ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($mp['project_name']) ?></td>
                                <td class="text-end"><?= (int)$mp['pr_count'] ?></td>
                                <td class="text-end"><?= (int)$mp['po_count'] ?></td>
                                <td class="text-end"><?= coFmt($mp['po_ordered_value']) ?></td>
                                <td class="text-end text-success fw-semibold"><?= coFmt($mp['delivered_value']) ?></td>
                                <td class="text-end"><?= coFmt($pendingVal) ?></td>
                                <td class="text-end">
                                    <?php if ((int)$mp['delayed_prs'] > 0): ?>
                                        <span class="co-badge co-badge-warn"><?= (int)$mp['delayed_prs'] ?> delayed</span>
                                    <?php else: ?>
                                        <span class="co-badge co-badge-ok">On Track</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2"
                                            onclick="openProjectMatModal(<?= (int)$mp['project_id'] ?>, '<?= htmlspecialchars(addslashes($mp['project_name'])) ?>')">
                                        Breakdown
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Financial Snapshot -->
    <div id="co-tab-financial" class="co-tab-pane" style="display:none;">
        <div class="co-card">
            <div class="co-card-hd">
                <h2 class="co-card-title">Budget vs. PO Committed vs. Paid</h2>
            </div>
            <div class="table-responsive">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="text-end">Approved Initial Budget</th>
                            <th class="text-end">PO Committed</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Remaining Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financialSnapshot as $fs): ?>
                            <?php $rem = (float)$fs['approved_budget'] - (float)$fs['total_paid']; ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($fs['project_name']) ?></td>
                                <td class="text-end"><?= coFmt($fs['approved_budget']) ?></td>
                                <td class="text-end"><?= coFmt($fs['po_committed']) ?></td>
                                <td class="text-end fw-semibold"><?= coFmt($fs['total_paid']) ?></td>
                                <td class="text-end <?= $rem < 0 ? 'text-danger fw-bold' : '' ?>"><?= coFmt($rem) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Approver Details -->
<div class="modal fade" id="coApproverModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="coApproverModalTitle">Approved Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="coApproverModalBody">Loading...</div>
        </div>
    </div>
</div>

<!-- Modal: Project Materials -->
<div class="modal fade" id="coProjectMatModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="coProjectMatModalTitle">Project Materials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="coProjectMatModalBody">Loading...</div>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(val) {
    document.getElementById('coCustomDateWrap').style.display = (val === 'custom') ? 'flex' : 'none';
}

function switchCoTab(tab, btn) {
    document.querySelectorAll('.co-tab-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.co-tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('co-tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

function openApproverModal(approverId, name) {
    document.getElementById('coApproverModalTitle').textContent = 'Approvals by ' + name;
    document.getElementById('coApproverModalBody').innerHTML = '<p class="text-muted">Loading...</p>';
    new bootstrap.Modal(document.getElementById('coApproverModal')).show();

    const params = new URLSearchParams({
        approver_id: approverId,
        project_id: '<?= (int)($filters['project_id'] ?? 0) ?>',
        req_type: '<?= htmlspecialchars($filters['req_type'] ?? '') ?>',
        start_date: '<?= htmlspecialchars($filters['start_date'] ?? '') ?>',
        end_date: '<?= htmlspecialchars($filters['end_date'] ?? '') ?>'
    });

    fetch('<?= $base ?>/api/con-oversight/approver-details?' + params.toString())
        .then(r => r.json())
        .then(res => {
            const logs = res.logs || [];
            if (!logs.length) {
                document.getElementById('coApproverModalBody').innerHTML = '<p class="text-muted">No items found.</p>';
                return;
            }
            let html = '<table class="co-table"><thead><tr><th>Document</th><th>Project</th><th>Attribution</th><th class="text-end">Amount</th><th>Date</th></tr></thead><tbody>';
            logs.forEach(l => {
                html += `<tr>
                    <td><a href="<?= $base ?>/${l.doc_url}" target="_blank">${l.doc_type} #${l.ref_id}</a></td>
                    <td>${l.project_name || '—'}</td>
                    <td>${l.approval_label || ''}</td>
                    <td class="text-end">৳${Number(l.amount || 0).toLocaleString()}</td>
                    <td>${l.created_at ? l.created_at.substring(0, 10) : '—'}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('coApproverModalBody').innerHTML = html;
        });
}

function openProjectMatModal(projectId, name) {
    document.getElementById('coProjectMatModalTitle').textContent = 'Material Breakdown — ' + name;
    document.getElementById('coProjectMatModalBody').innerHTML = '<p class="text-muted">Loading...</p>';
    new bootstrap.Modal(document.getElementById('coProjectMatModal')).show();

    fetch('<?= $base ?>/api/con-oversight/project-materials?project_id=' + projectId)
        .then(r => r.json())
        .then(res => {
            const items = res.items || [];
            if (!items.length) {
                document.getElementById('coProjectMatModalBody').innerHTML = '<p class="text-muted">No PO materials found for this project.</p>';
                return;
            }
            let html = '<table class="co-table"><thead><tr><th>Material</th><th>Unit</th><th class="text-end">Ordered Qty</th><th class="text-end">Delivered Qty</th><th class="text-end">Pending Qty</th><th class="text-end">Order Value</th></tr></thead><tbody>';
            items.forEach(it => {
                const pend = Math.max(0, Number(it.ordered_qty) - Number(it.delivered_qty));
                html += `<tr>
                    <td class="fw-semibold">${it.material_name}</td>
                    <td>${it.unit || ''}</td>
                    <td class="text-end">${Number(it.ordered_qty).toLocaleString()}</td>
                    <td class="text-end text-success">${Number(it.delivered_qty).toLocaleString()}</td>
                    <td class="text-end">${pend.toLocaleString()}</td>
                    <td class="text-end">৳${Number(it.ordered_val || 0).toLocaleString()}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('coProjectMatModalBody').innerHTML = html;
        });
}
</script>
