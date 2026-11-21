<?php
$base_url     = $GLOBALS['base_url'];
$teamRefunds  = $teamRefunds ?? [];
$teamBookings = $teamBookings ?? [];
$projects     = $projects ?? [];
$filters      = $filters ?? [];
$isTeamLeader = $isTeamLeader ?? false;
$isSalesExec  = $isSalesExec ?? false;

$stageLabels = [
    'sales_manager' => '1. Sales Manager',
    'qc'            => '2. QC Review',
    'op_head'       => '3. Operation Head',
    'management'    => '4. Super Admin',
];
$statusBadgeMap = [
    'pending'  => ['label' => 'Pending',  'bg' => '#fef3c7', 'color' => '#92400e'],
    'approved' => ['label' => 'Approved', 'bg' => '#e0f2fe', 'color' => '#075985'],
    'refunded' => ['label' => 'Paid',     'bg' => '#dcfce7', 'color' => '#166534'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#fee2e2', 'color' => '#991b1b'],
];
?>

<div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb">
    <div>
        <h4 class="page-title fw-bold fs-18 mb-1">
            <i class="fa-light fa-rotate-left text-primary me-2"></i>Team Refunds Portal
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-12">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/team-dashboard">Sales</a></li>
                <li class="breadcrumb-item active" aria-current="page">Refunds</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3 rounded-3">
    <div class="card-body p-3">
        <form method="GET" action="<?= $base_url ?>/team-refunds" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fs-11 fw-semibold text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Client, phone, flat, salesperson..."
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-11 fw-semibold text-muted mb-1">Project</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Projects</option>
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= (!empty($filters['project_id']) && $filters['project_id'] == $pr['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-11 fw-semibold text-muted mb-1">Refund Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="refunded" <?= ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Paid</option>
                    <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-light fa-filter me-1"></i>Filter</button>
                <a href="<?= $base_url ?>/team-refunds" class="btn btn-outline-secondary btn-sm"><i class="fa-light fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabs: Team Refund Requests & Team Onboarded Projects -->
<ul class="nav nav-tabs mb-3" id="teamRefundTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="tab-refunds-btn" data-bs-toggle="tab" data-bs-target="#tab-refunds" type="button" role="tab">
            <i class="fa-light fa-list-check me-1"></i> Team Refunds
            <span class="badge bg-primary-subtle text-primary ms-1"><?= count($teamRefunds) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-onboarded-btn" data-bs-toggle="tab" data-bs-target="#tab-onboarded" type="button" role="tab">
            <i class="fa-light fa-building-check me-1"></i> All Onboarding Projects (Team)
            <span class="badge bg-success-subtle text-success ms-1"><?= count($teamBookings) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: Team Refunds List -->
    <div class="tab-pane fade show active" id="tab-refunds" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="fs-12 text-muted text-uppercase">
                                <th class="ps-3">#</th>
                                <th>Client &amp; Salesperson</th>
                                <th>Project &amp; Flat</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Retention</th>
                                <th class="text-end">Net Refund</th>
                                <th>Requested By</th>
                                <th class="text-center">Stage / Status</th>
                                <th class="text-center pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teamRefunds)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fa-light fa-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        No refund requests found for your team.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teamRefunds as $idx => $r):
                                    $st = $statusBadgeMap[$r['status']] ?? ['label' => ucfirst($r['status']), 'bg' => '#e2e8f0', 'color' => '#334155'];
                                    $stageKey = $r['approve_role'] ?? 'sales_manager';
                                    $stageTxt = $stageLabels[$stageKey] ?? ucfirst($stageKey);
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted font-monospace fs-12"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($r['client_name'] ?? '—') ?></div>
                                        <div class="text-muted fs-11"><?= htmlspecialchars($r['client_phone'] ?? '') ?></div>
                                        <?php if (!empty($r['salesperson_name'])): ?>
                                            <span class="badge bg-light text-secondary border fs-10 mt-1">Sales: <?= htmlspecialchars($r['salesperson_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fs-11 mb-1"><?= htmlspecialchars($r['project_name'] ?? '—') ?></span>
                                        <div class="fs-12 fw-medium"><?= htmlspecialchars($r['flat_name'] ?? '—') ?> <?= !empty($r['floor_no']) ? "(Floor {$r['floor_no']})" : '' ?></div>
                                    </td>
                                    <td class="text-end font-monospace fs-13">৳ <?= number_format($r['total_paid'], 2) ?></td>
                                    <td class="text-end font-monospace fs-13 text-danger">৳ <?= number_format($r['retention_amount'], 2) ?></td>
                                    <td class="text-end font-monospace fs-13 fw-bold text-success">৳ <?= number_format($r['refund_amount'], 2) ?></td>
                                    <td class="fs-12">
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($r['requested_by_name'] ?? '—') ?></div>
                                        <span class="text-muted fs-11 d-block">
                                            <?= !empty($r['created_at']) ? date('d M Y, h:i A', strtotime($r['created_at'])) : '—' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-2 py-1 fs-11" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
                                            <?= $st['label'] ?>
                                        </span>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <div class="text-muted fs-10 mt-1">At: <?= htmlspecialchars($stageTxt) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <a href="<?= $base_url ?>/client-refund/<?= (int)$r['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                            <i class="fa-light fa-eye me-1"></i> View
                                        </a>
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

    <!-- TAB 2: Team Onboarded Projects (Create Refund from here) -->
    <div class="tab-pane fade" id="tab-onboarded" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="fs-12 text-muted text-uppercase">
                                <th class="ps-3">#</th>
                                <th>Client &amp; Salesperson</th>
                                <th>Project &amp; Unit</th>
                                <th class="text-end">Agreement Value</th>
                                <th class="text-end">Verified Paid</th>
                                <th class="text-center">Booking Status</th>
                                <th class="text-center pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teamBookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-light fa-building fs-2 d-block mb-2 opacity-50"></i>
                                        No active onboarded units found for your team.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teamBookings as $idx => $b): ?>
                                <tr>
                                    <td class="ps-3 text-muted font-monospace fs-12"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($b['client_name'] ?? '—') ?></div>
                                        <div class="text-muted fs-11"><?= htmlspecialchars($b['client_phone'] ?? '') ?></div>
                                        <span class="badge bg-light text-secondary border fs-10 mt-1">Sales: <?= htmlspecialchars($b['salesperson_name'] ?? '—') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fs-11 mb-1"><?= htmlspecialchars($b['project_name'] ?? '—') ?></span>
                                        <div class="fs-12 fw-medium"><?= htmlspecialchars($b['flat_name'] ?? '—') ?> <?= !empty($b['floor_no']) ? "(Floor {$b['floor_no']})" : '' ?></div>
                                    </td>
                                    <td class="text-end font-monospace fs-13">৳ <?= number_format($b['agreement_value'] ?? 0, 2) ?></td>
                                    <td class="text-end font-monospace fs-13 fw-semibold text-success">৳ <?= number_format($b['total_paid'] ?? 0, 2) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fs-11"><?= ucfirst($b['booking_status']) ?></span>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if (!empty($b['active_refund_id'])): ?>
                                            <a href="<?= $base_url ?>/client-refund/<?= (int)$b['active_refund_id'] ?>" class="btn btn-xs btn-outline-info py-1 px-2">
                                                Refund <?= ucfirst($b['active_refund_status']) ?>
                                            </a>
                                        <?php elseif ($isTeamLeader || in_array((int)($userRole ?? 0), [1, 3, 9, 20, 38], true)): ?>
                                            <button type="button"
                                                class="btn btn-xs btn-danger py-1 px-2 quickRefundBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#quickRefundModal"
                                                data-id="<?= (int)$b['booking_id'] ?>"
                                                data-paid="<?= (float)($b['total_paid'] ?? 0) ?>"
                                                data-flat="<?= htmlspecialchars(($b['flat_name'] ?? '') . (!empty($b['floor_no']) ? ' (Floor ' . $b['floor_no'] . ')' : '')) ?>"
                                                data-project="<?= htmlspecialchars($b['project_name'] ?? '') ?>"
                                                data-agreement="<?= number_format((float)($b['agreement_value'] ?? 0), 2) ?>"
                                                data-client="<?= htmlspecialchars($b['client_name'] ?? '') ?>">
                                                <i class="fa-light fa-rotate-left me-1"></i> Request Refund
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2"
                                                    onclick="if(typeof Swal !== 'undefined'){ Swal.fire({ icon: 'info', title: 'Restricted', text: 'Only your sales leaders can create refund request.' }); } else { alert('Only your sales leaders can create refund request.'); }">
                                                <i class="fa-light fa-rotate-left me-1"></i> Request Refund
                                            </button>
                                        <?php endif; ?>
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
</div>

<?php include __DIR__ . '/../../components/quick-refund-modal.php'; ?>
