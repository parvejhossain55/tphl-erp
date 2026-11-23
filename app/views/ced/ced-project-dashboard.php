<?php
$page_title      = $page_title ?? 'CED Project Dashboard';
$page            = 'CED';
$show_breadcrumb = true;
$base_url        = $base_url ?? ($GLOBALS['base_url'] ?? '');

$filters      = $filters ?? ['project_id' => 0, 'agent_id' => 0, 'search' => ''];
$projectRows  = $project_rows ?? [];
$grand        = $grand_totals ?? [];
$agentsList      = $agents_list ?? [];
$projectsList    = $projects_list ?? [];
$projectAgentMap = $project_agent_map ?? [];
$selectedAgentId = (int)($selected_agent_id ?? ($filters['agent_id'] ?? 0));

function cedPdMoney($v): string
{
    return number_format((float)$v, 0);
}
?>

<style>
.ced-pd-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.ced-pd-kpi {
    border-radius: 10px;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
    height: 100%;
}
.ced-pd-kpi .kpi-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    font-weight: 600;
}
.ced-pd-kpi .kpi-value {
    font-size: 20px;
    font-weight: 700;
    margin-top: 4px;
    color: #0f172a;
}
.ced-pd-table-wrap {
    overflow-x: auto;
    max-height: 72vh;
}
.ced-pd-table {
    font-size: 12px;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    margin-bottom: 0;
}
.ced-pd-table thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    white-space: nowrap;
    vertical-align: middle;
    font-weight: 600;
    font-size: 11px;
    border-bottom: 1px solid #cbd5e1;
    padding: 8px 10px;
}
.ced-pd-table thead tr.group-row th {
    top: 0;
    z-index: 4;
    text-align: center;
    font-size: 11px;
    letter-spacing: 0.3px;
}
.ced-pd-table thead tr.col-row th {
    top: 32px;
    z-index: 3;
    background: #f8fafc;
    color: #334155;
}
.ced-pd-table tbody tr.project-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.ced-pd-table tbody tr.project-row:hover {
    background-color: #f1f5f9;
}
.ced-pd-table tbody tr.project-row.is-open {
    background-color: #eff6ff;
}
.ced-pd-table td {
    white-space: nowrap;
    vertical-align: middle;
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
}
.ced-pd-table tfoot td {
    position: sticky;
    bottom: 0;
    background: #0f172a;
    color: #fff;
    font-weight: 700;
    padding: 9px 10px;
    z-index: 2;
}
.ced-pd-expand-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #334155;
    font-size: 10px;
    margin-right: 6px;
    transition: transform 0.2s ease, background 0.2s ease;
}
tr.project-row.is-open .ced-pd-expand-icon {
    transform: rotate(90deg);
    background: #2563eb;
    color: #fff;
}
.ced-pd-drill-row td {
    padding: 0 !important;
    background: #f8fafc;
    white-space: normal;
}
.ced-pd-drill-box {
    padding: 12px 16px 16px 28px;
    border-left: 4px solid #2563eb;
    background: #f8fafc;
}
.ced-pd-drill-scroll-wrap {
    max-height: 480px;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.ced-pd-drill-table {
    width: 100%;
    font-size: 11.5px;
    background: #fff;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
}
.ced-pd-drill-table th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #eef2ff;
    color: #1e3a8a;
    font-weight: 600;
    padding: 7px 8px;
    white-space: nowrap;
    border-bottom: 2px solid #cbd5e1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.ced-pd-drill-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.ced-pd-drill-table tfoot td {
    position: sticky;
    bottom: 0;
    z-index: 4;
    background: #e2e8f0;
    color: #0f172a;
    font-weight: 700;
    border-top: 2px solid #cbd5e1;
    box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.05);
}
.grp-inv { background: #1e293b !important; color: #fff !important; }
.grp-ls  { background: #0f766e !important; color: #fff !important; }
.grp-dc  { background: #1d4ed8 !important; color: #fff !important; }
.grp-emi { background: #6d28d9 !important; color: #fff !important; }
.grp-exp { background: #b45309 !important; color: #fff !important; }
</style>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-chart-line text-primary me-2"></i>Project-wise Dashboard
            </h4>
            <small class="text-muted">Under Due Tracking &amp; Management — Click any project row to expand client-level financial breakdown</small>
        </div>
        <div class="d-flex gap-2">
            <?php
            $pdRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $pdSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
            $pdCanBroadcast = in_array($pdRoleId, [1, 3, 33, 38, 39], true)
                || in_array($pdSecRoleId, [1, 3, 38, 39], true)
                || (function_exists('isHeadOfClientExperience') && isHeadOfClientExperience());
            if ($pdCanBroadcast):
                $bcUrl = $base_url . '/ced-client-broadcast' . (!empty($filters['project_id']) ? '?project_id=' . (int)$filters['project_id'] : '');
            ?>
                <a href="<?= $bcUrl ?>" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-paper-plane me-1"></i>Send SMS / Email
                </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>/client-wise-due-tracking" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Back to Due Tracking &amp; Mgt
            </a>
            <a href="<?= $base_url ?>/ced-collection-statement" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i>Collection Statement
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="ced-pd-card p-3 mb-3">
        <form method="GET" action="<?= $base_url ?>/ced-project-dashboard" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Project</label>
                <select name="project_id" id="filterProjectSelect" class="form-select form-select-sm">
                    <option value="">All Projects</option>
                    <?php foreach ($projectsList as $proj): ?>
                        <?php if (trim((string)($proj['name'] ?? '')) === '') continue; ?>
                        <option value="<?= (int)$proj['id'] ?>" <?= ((int)$filters['project_id'] === (int)$proj['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proj['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Assigned CED Agent</label>
                <select name="agent_id" id="filterAgentSelect" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    <?php foreach ($agentsList as $ag): ?>
                        <?php if (trim((string)($ag['full_name'] ?? '')) === '') continue; ?>
                        <option value="<?= (int)$ag['user_id'] ?>" <?= ($selectedAgentId === (int)$ag['user_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ag['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Quick Search (Project / Agent)</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       class="form-control form-control-sm" placeholder="Type project or agent name...">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i>Filter
                </button>
                <a href="<?= $base_url ?>/ced-project-dashboard" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Projects / Clients</div>
                <div class="kpi-value">
                    <?= (int)($grand['project_count'] ?? 0) ?>
                    <span class="fs-6 text-muted fw-normal">/ <?= (int)($grand['total_client'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Land Share Due</div>
                <div class="kpi-value text-danger">৳<?= cedPdMoney($grand['land_share_due'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Dev. Charge Due</div>
                <div class="kpi-value text-warning">৳<?= cedPdMoney($grand['dc_due'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Net Regular EMI</div>
                <div class="kpi-value text-success">৳<?= cedPdMoney($grand['net_regular_emi_collected'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Regular EMI Due</div>
                <div class="kpi-value text-danger">৳<?= cedPdMoney($grand['regular_emi_due'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ced-pd-kpi">
                <div class="kpi-label">Project Expenditure</div>
                <div class="kpi-value text-dark">৳<?= cedPdMoney($grand['project_expenditure'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Project-wise 18-Column Table -->
    <div class="ced-pd-card overflow-hidden">
        <div class="ced-pd-table-wrap">
            <table class="ced-pd-table" id="cedProjectDashboardTable">
                <thead>
                    <tr class="group-row">
                        <th colspan="5" class="grp-inv">A. Client &amp; Inventory Information</th>
                        <th colspan="3" class="grp-ls">B. Land Share Financials</th>
                        <th colspan="3" class="grp-dc">C. Development Charge Financials</th>
                        <th colspan="6" class="grp-emi">D. EMI Financials</th>
                        <th colspan="1" class="grp-exp">E. Expenditure</th>
                    </tr>
                    <tr class="col-row">
                        <th>1. Assigned Person</th>
                        <th>2. Project Name</th>
                        <th class="text-end">3. Total Units / Flats</th>
                        <th class="text-end">4. Registered</th>
                        <th class="text-end">5. Remaining Share</th>

                        <th class="text-end">6. Total Land Share</th>
                        <th class="text-end">7. Land Share Paid</th>
                        <th class="text-end">8. Land Share Due</th>

                        <th class="text-end">9. Total DC Ask</th>
                        <th class="text-end">10. Total DC Received</th>
                        <th class="text-end">11. DC Due</th>

                        <th class="text-end">12. EMI Demand (#)</th>
                        <th class="text-end">13. Regular EMI Amount</th>
                        <th class="text-end" title="Total EMI Received (Regular + Advance)">14. Regular EMI Received</th>
                        <th class="text-end">15. Advanced Received</th>
                        <th class="text-end" title="Regular EMI Received - Advanced Received">16. Net Regular EMI</th>
                        <th class="text-end" title="Regular EMI Amount - Net Regular EMI">17. Regular EMI Due</th>

                        <th class="text-end">18. Project Expenditure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projectRows)): ?>
                        <tr>
                            <td colspan="18" class="text-center py-4 text-muted">
                                No projects found matching your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projectRows as $row): ?>
                            <?php $pId = (int)$row['project_id']; ?>
                            <tr class="project-row" data-project-id="<?= $pId ?>" data-project-name="<?= htmlspecialchars($row['project_name']) ?>">
                                <td>
                                    <span class="ced-pd-expand-icon"><i class="fa-solid fa-chevron-right"></i></span>
                                    <span class="<?= $row['assigned_person'] === 'Unassigned' ? 'text-muted fst-italic' : 'fw-medium' ?>">
                                        <?= htmlspecialchars($row['assigned_person']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name']) ?></td>
                                <td class="text-end"><?= (int)$row['total_client'] ?></td>
                                <td class="text-end text-success fw-semibold"><?= (int)$row['registered_client'] ?></td>
                                <td class="text-end"><?= (int)$row['remaining_share'] ?></td>

                                <td class="text-end">৳<?= cedPdMoney($row['total_land_share']) ?></td>
                                <td class="text-end text-success">৳<?= cedPdMoney($row['land_share_paid']) ?></td>
                                <td class="text-end <?= $row['land_share_due'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    ৳<?= cedPdMoney($row['land_share_due']) ?>
                                </td>

                                <td class="text-end">৳<?= cedPdMoney($row['total_dc_ask']) ?></td>
                                <td class="text-end text-success">৳<?= cedPdMoney($row['total_dc_received']) ?></td>
                                <td class="text-end <?= $row['dc_due'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    ৳<?= cedPdMoney($row['dc_due']) ?>
                                </td>

                                <td class="text-end"><?= (int)$row['regular_emi_demand_count'] ?></td>
                                <td class="text-end">৳<?= cedPdMoney($row['regular_emi_amount']) ?></td>
                                <td class="text-end text-primary">৳<?= cedPdMoney($row['regular_emi_received']) ?></td>
                                <td class="text-end text-info">৳<?= cedPdMoney($row['advanced_received']) ?></td>
                                <td class="text-end text-success fw-semibold">৳<?= cedPdMoney($row['net_regular_emi_collected']) ?></td>
                                <td class="text-end <?= $row['regular_emi_due'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    ৳<?= cedPdMoney($row['regular_emi_due']) ?>
                                </td>

                                <td class="text-end fw-semibold">৳<?= cedPdMoney($row['project_expenditure']) ?></td>
                            </tr>
                            <tr class="ced-pd-drill-row d-none" id="drill-row-<?= $pId ?>">
                                <td colspan="18">
                                    <div class="ced-pd-drill-box" id="drill-box-<?= $pId ?>">
                                        <div class="text-muted small py-2">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Loading client-level breakdown...
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($projectRows)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2">GRAND TOTAL (<?= (int)($grand['project_count'] ?? 0) ?> Projects)</td>
                        <td class="text-end"><?= (int)($grand['total_client'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($grand['registered_client'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($grand['remaining_share'] ?? 0) ?></td>

                        <td class="text-end">৳<?= cedPdMoney($grand['total_land_share'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['land_share_paid'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['land_share_due'] ?? 0) ?></td>

                        <td class="text-end">৳<?= cedPdMoney($grand['total_dc_ask'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['total_dc_received'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['dc_due'] ?? 0) ?></td>

                        <td class="text-end"><?= (int)($grand['regular_emi_demand_count'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['regular_emi_amount'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['regular_emi_received'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['advanced_received'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['net_regular_emi_collected'] ?? 0) ?></td>
                        <td class="text-end">৳<?= cedPdMoney($grand['regular_emi_due'] ?? 0) ?></td>

                        <td class="text-end">৳<?= cedPdMoney($grand['project_expenditure'] ?? 0) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = <?= json_encode($base_url) ?>;
    const projectAgentMap = <?= json_encode($projectAgentMap) ?>;
    const loadedCache = {};

    const projectSelectEl = document.getElementById('filterProjectSelect');
    const agentSelectEl = document.getElementById('filterAgentSelect');
    if (projectSelectEl && agentSelectEl) {
        projectSelectEl.addEventListener('change', function() {
            const pId = this.value;
            const mappedAgentId = pId && projectAgentMap[pId] ? String(projectAgentMap[pId]) : '';
            if (mappedAgentId && agentSelectEl.querySelector('option[value="' + mappedAgentId + '"]')) {
                agentSelectEl.value = mappedAgentId;
            } else {
                agentSelectEl.value = '';
            }
        });
    }

    function fmtMoney(v) {
        const n = Number(v || 0);
        return '৳' + Math.round(n).toLocaleString('en-US');
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function statusBadge(c) {
        if (c && c.is_registered) return '<span class="badge bg-success">Registered</span>';
        const status = c && c.booking_status ? c.booking_status : '';
        const st = String(status).toLowerCase();
        if (st === 'completed') return '<span class="badge bg-success">Registered</span>';
        if (st === 'booked') return '<span class="badge bg-primary">Booked</span>';
        return '<span class="badge bg-secondary">' + escHtml(status || 'Active') + '</span>';
    }

    function renderClientsTable(projectName, rows, totals) {
        if (!rows || rows.length === 0) {
            return '<div class="text-muted small py-2">No active clients found in <strong>' + escHtml(projectName) + '</strong>.</div>';
        }

        let bodyHtml = '';
        rows.forEach((c, idx) => {
            bodyHtml += `
                <tr>
                    <td>${idx + 1}</td>
                    <td class="fw-semibold">${escHtml(c.client_name)}${c.phone ? '<div class="text-muted" style="font-size:10px;">' + escHtml(c.phone) + '</div>' : ''}</td>
                    <td>${escHtml(c.flat_name)}</td>
                    <td>${statusBadge(c)}</td>
                    <td class="text-end">${fmtMoney(c.total_land_share)}</td>
                    <td class="text-end text-success">${fmtMoney(c.land_share_paid)}</td>
                    <td class="text-end ${c.land_share_due > 0 ? 'text-danger fw-semibold' : 'text-muted'}">${fmtMoney(c.land_share_due)}</td>
                    <td class="text-end">${fmtMoney(c.total_dc_ask)}</td>
                    <td class="text-end text-success">${fmtMoney(c.total_dc_received)}</td>
                    <td class="text-end ${c.dc_due > 0 ? 'text-danger fw-semibold' : 'text-muted'}">${fmtMoney(c.dc_due)}</td>
                    <td class="text-end">${Number(c.regular_emi_demand_count || 0)}</td>
                    <td class="text-end">${fmtMoney(c.regular_emi_amount)}</td>
                    <td class="text-end text-primary">${fmtMoney(c.regular_emi_received)}</td>
                    <td class="text-end text-info">${fmtMoney(c.advanced_received)}</td>
                    <td class="text-end text-success fw-semibold">${fmtMoney(c.net_regular_emi_collected)}</td>
                    <td class="text-end ${c.regular_emi_due > 0 ? 'text-danger fw-semibold' : 'text-muted'}">${fmtMoney(c.regular_emi_due)}</td>
                </tr>
            `;
        });

        return `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold text-primary small">
                    <i class="fa-solid fa-users me-1"></i>Client-Level Breakdown — ${escHtml(projectName)} (${totals.client_count} Active Clients, ${totals.registered_count} Registered)
                </div>
            </div>
            <div class="table-responsive ced-pd-drill-scroll-wrap">
                <table class="ced-pd-drill-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Unit / Flat</th>
                            <th>Status</th>
                            <th class="text-end">Total Land Share</th>
                            <th class="text-end">Land Share Paid</th>
                            <th class="text-end">Land Share Due</th>
                            <th class="text-end">Total DC Ask</th>
                            <th class="text-end">DC Received</th>
                            <th class="text-end">DC Due</th>
                            <th class="text-end">EMI Demand (#)</th>
                            <th class="text-end">Regular EMI Amt</th>
                            <th class="text-end">EMI Received</th>
                            <th class="text-end">Advanced Rcvd</th>
                            <th class="text-end">Net Regular EMI</th>
                            <th class="text-end">Regular EMI Due</th>
                        </tr>
                    </thead>
                    <tbody>${bodyHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">Project Client Subtotal (${totals.client_count})</td>
                            <td class="text-end">${fmtMoney(totals.total_land_share)}</td>
                            <td class="text-end">${fmtMoney(totals.land_share_paid)}</td>
                            <td class="text-end">${fmtMoney(totals.land_share_due)}</td>
                            <td class="text-end">${fmtMoney(totals.total_dc_ask)}</td>
                            <td class="text-end">${fmtMoney(totals.total_dc_received)}</td>
                            <td class="text-end">${fmtMoney(totals.dc_due)}</td>
                            <td class="text-end">${Number(totals.regular_emi_demand_count || 0)}</td>
                            <td class="text-end">${fmtMoney(totals.regular_emi_amount)}</td>
                            <td class="text-end">${fmtMoney(totals.regular_emi_received)}</td>
                            <td class="text-end">${fmtMoney(totals.advanced_received)}</td>
                            <td class="text-end">${fmtMoney(totals.net_regular_emi_collected)}</td>
                            <td class="text-end">${fmtMoney(totals.regular_emi_due)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    document.querySelectorAll('#cedProjectDashboardTable tr.project-row').forEach(row => {
        row.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;
            const drillRow = document.getElementById('drill-row-' + projectId);
            const drillBox = document.getElementById('drill-box-' + projectId);
            if (!drillRow || !drillBox) return;

            const isOpen = this.classList.contains('is-open');
            if (isOpen) {
                this.classList.remove('is-open');
                drillRow.classList.add('d-none');
                return;
            }

            this.classList.add('is-open');
            drillRow.classList.remove('d-none');

            if (loadedCache[projectId]) {
                return;
            }

            fetch(baseUrl + '/ced-project-dashboard/client-drilldown?project_id=' + encodeURIComponent(projectId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    loadedCache[projectId] = true;
                    drillBox.innerHTML = renderClientsTable(projectName, res.rows, res.totals);
                } else {
                    drillBox.innerHTML = '<div class="text-danger small py-2">' + escHtml(res.message || 'Failed to load clients.') + '</div>';
                }
            })
            .catch(() => {
                drillBox.innerHTML = '<div class="text-danger small py-2">Network error loading client breakdown.</div>';
            });
        });
    });
})();
</script>
