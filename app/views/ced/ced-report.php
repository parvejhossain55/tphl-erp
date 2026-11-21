<style>
    .report-card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: none;
        transition: transform 0.3s ease;
    }
    .report-card:hover {
        transform: translateY(-2px);
    }
    .summary-card {
        border-radius: 16px;
        padding: 20px;
        color: white;
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important;
    }
    .summary-icon {
        background: rgba(255,255,255,0.2);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }
    .table-modern {
        border-collapse: separate;
        border-spacing: 0 10px;
        border: none;
    }
    .table-modern thead th {
        border: none;
        background: #f8f9fa;
        color: #5a5c69;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
    }
    .table-modern tbody tr {
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        background: white;
        border-radius: 8px;
    }
    .table-modern tbody td {
        border-top: 1px solid #f2f2f2;
        border-bottom: 1px solid #f2f2f2;
        padding: 15px;
        vertical-align: middle;
    }
    .table-modern tbody td:first-child {
        border-left: 1px solid #f2f2f2;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    .table-modern tbody td:last-child {
        border-right: 1px solid #f2f2f2;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    .note-text {
        font-size: 0.9rem;
        color: #444;
        line-height: 1.5;
        background: #f8f9fc;
        padding: 10px;
        border-radius: 8px;
        border-left: 3px solid #4e73df;
    }

    @media print {
        .app-sidebar, .app-header, .page-header, .no-print, .main-footer {
            display: none !important;
        }
        .report-card, .summary-card, .table-modern tbody tr {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .summary-card {
            color: black !important;
            background: white !important;
        }
        .print-summary-card {
            background: white !important;
            border: 2px solid #ccc !important;
            color: black !important;
        }
        .print-summary-card ., .print-summary-card .-50 {
            color: black !important;
        }
        .print-only-header {
            display: block !important;
        }
    }
</style>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb no-print">
            <h1 class="page-title fw-semibold fs-18 mb-0">CED Activity Report</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">CED</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Report</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Print Only Header -->
        <div class="d-none print-only-header mb-4 border-bottom pb-3">
            <div class="text-center mb-3">
                <p class="text-muted fs-14 mb-0 fw-bold">Activity Report</p>
            </div>
            <div class="row text-dark">
                <div class="col-6">
                    <p class="mb-1"><strong>Name:</strong> <?= isset($agentInfo['full_name']) ? htmlspecialchars($agentInfo['full_name']) : 'All Agents' ?></p>
                    <p class="mb-1"><strong>Employee ID:</strong> <?= isset($agentInfo['employee_id']) ? htmlspecialchars($agentInfo['employee_id']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Department:</strong> <?= isset($agentInfo['department_name']) ? htmlspecialchars($agentInfo['department_name']) : 'N/A' ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Designation:</strong> <?= isset($agentInfo['designation_name']) ? htmlspecialchars($agentInfo['designation_name']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Office Branch:</strong> <?= isset($agentInfo['office_branch']) ? htmlspecialchars($agentInfo['office_branch']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Date Range:</strong> <?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?></p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card report-card no-print mb-4">
            <div class="card-body p-4">
                <form method="GET" action="" class="row align-items-end" id="filterForm">
                    
                    <div class="col-md-2">
                        <label class="form-label text-muted fw-bold">Select CED Agent</label>
                        <select name="agent_id" class="form-select shadow-sm">
                            <option value="">-- All Agents --</option>
                            <?php foreach ($cedEmployees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= ($agentId == $emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name'] ?? '') ?> (<?= htmlspecialchars($emp['role_name'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fw-bold">Report Mode</label>
                        <select name="report_mode" id="reportMode" class="form-select shadow-sm" onchange="toggleInputs()">
                            <option value="daily" <?= ($reportMode == 'daily') ? 'selected' : '' ?>>Daily (Today)</option>
                            <option value="monthly" <?= ($reportMode == 'monthly') ? 'selected' : '' ?>>Monthly Breakdown</option>
                            <option value="yearly" <?= ($reportMode == 'yearly') ? 'selected' : '' ?>>Yearly Breakdown</option>
                            <option value="custom" <?= ($reportMode == 'custom') ? 'selected' : '' ?>>Custom Range</option>
                        </select>
                    </div>

                    <!-- Monthly / Yearly inputs -->
                    <div class="col-md-2 mode-input mode-monthly d-none">
                        <label class="form-label text-muted fw-bold">Month</label>
                        <select name="report_month" class="form-select shadow-sm">
                            <?php for($i=1; $i<=12; $i++): $mStr = sprintf("%02d", $i); ?>
                                <option value="<?= $mStr ?>" <?= ($reportMonth == $mStr) ? 'selected' : '' ?>>
                                    <?= date("F", mktime(0, 0, 0, $i, 10)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2 mode-input mode-monthly mode-yearly d-none">
                        <label class="form-label text-muted fw-bold">Year</label>
                        <select name="report_year" class="form-select shadow-sm">
                            <?php for($y=2025; $y<=2035; $y++): ?>
                                <option value="<?= $y ?>" <?= ($reportYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Custom inputs -->
                    <div class="col-md-2 mode-input mode-custom d-none">
                        <label class="form-label text-muted fw-bold">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control shadow-sm" value="<?= htmlspecialchars($startDate ?? '') ?>">
                    </div>
                    <div class="col-md-2 mode-input mode-custom d-none">
                        <label class="form-label text-muted fw-bold">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control shadow-sm" value="<?= htmlspecialchars($endDate ?? '') ?>">
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary shadow-sm px-4"><i class="bi bi-filter"></i> Filter</button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm px-4" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function toggleInputs() {
                const mode = document.getElementById('reportMode').value;
                document.querySelectorAll('.mode-input').forEach(el => el.classList.add('d-none'));
                document.querySelectorAll('.mode-' + mode).forEach(el => el.classList.remove('d-none'));
                
                // toggle required attribute
                if (mode === 'custom') {
                    document.getElementById('start_date').setAttribute('required', 'required');
                    document.getElementById('end_date').setAttribute('required', 'required');
                } else {
                    document.getElementById('start_date').removeAttribute('required');
                    document.getElementById('end_date').removeAttribute('required');
                }
            }
            document.addEventListener("DOMContentLoaded", toggleInputs);
        </script>
        <!-- Breakdown Section -->
        <?php if ($reportMode != 'custom' && $reportMode != 'daily' && !empty($breakdownData)): ?>
        <div class="card report-card mb-4 no-print">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                <div class="card-title fw-bold text-dark fs-16">
                    <i class="bi bi-bar-chart-fill text-success me-2"></i> 
                    <?= ucfirst($reportMode) ?> Breakdown 
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-modern w-100 text-center">
                        <thead>
                            <tr>
                                <th><?= $reportMode == 'monthly' ? 'Date' : 'Month' ?></th>
                                <th>Follow-ups Done</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalBreakdown = 0;
                            foreach ($breakdownData as $bRow): 
                                $totalBreakdown += $bRow['count'];
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($bRow['label'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-success-transparent text-success px-3 py-2 rounded-pill fs-12">
                                        <?= $bRow['count'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-light">
                                <td class="fw-bold text-dark text-end pe-4">Total</td>
                                <td class="fw-bold text-success fs-15"><?= $totalBreakdown ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary Section -->
        <div class="card report-card print-summary-card mb-3" style="background: #0e5013; border: none;">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-6 border-end border-light border-opacity-25">
                        <div class="d-flex align-items-center justify-content-between pe-2">
                            <div>
                                <h6 class="mb-1 -50 text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 11px;">Total Clients Followed Up</h6>
                                <h2 class="mb-0 fw-bold " style="font-size: 2rem;"><?= $summary['total_clients'] ?></h2>
                            </div>
                            <div class="summary-icon  opacity-50 fs-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center justify-content-between ps-2">
                            <div>
                                <h6 class="mb-1 -50 text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 11px;">Total Follow-up Notes</h6>
                                <h2 class="mb-0 fw-bold " style="font-size: 2rem;"><?= $summary['total_notes'] ?></h2>
                            </div>
                            <div class="summary-icon  opacity-50 fs-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Table -->
        <?php
            $pgPage = (int)($pagination['page'] ?? 1);
            $pgPerPage = (int)($pagination['per_page'] ?? 50);
            $pgTotal = (int)($pagination['total_records'] ?? count($details));
            $pgTotalPages = (int)($pagination['total_pages'] ?? 1);
            $pgStart = $pgTotal > 0 ? (($pgPage - 1) * $pgPerPage) + 1 : 0;
            $pgEnd = min($pgTotal, $pgPage * $pgPerPage);
            $baseQueryParams = array_filter([
                'agent_id' => $agentId ?: null,
                'report_mode' => $reportMode ?? null,
                'report_month' => $reportMonth ?? null,
                'report_year' => $reportYear ?? null,
                'start_date' => $startDate ?? null,
                'end_date' => $endDate ?? null,
                'per_page' => $pgPerPage,
            ], static fn($v) => $v !== null && $v !== '');
        ?>
        <div class="card report-card">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title fw-bold text-dark fs-16">
                    <i class="bi bi-list-columns-reverse text-primary me-2"></i> 
                    Activity Footprints (<?= htmlspecialchars($startDate ?? '') ?> to <?= htmlspecialchars($endDate ?? '') ?>)
                </div>
                <div class="text-muted fs-13">
                    Showing <strong><?= number_format($pgStart) ?>–<?= number_format($pgEnd) ?></strong> of <strong><?= number_format($pgTotal) ?></strong> records
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">Added Date & Time</th>
                                <th style="width: 15%">Agent</th>
                                <th style="width: 20%">Client Details</th>
                                <th style="width: 20%">Project / Flat</th>
                                <th style="width: 25%">Discussion Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($details)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1" style="opacity: 0.3"></i>
                                            <p class="mt-2">No activities found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $i = $pgStart; foreach ($details as $row): ?>
                                    <tr>
                                        <td class="fw-bold text-muted align-middle"><?= $i++ ?></td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['follow_up_date'])) ?></div>
                                            <div class="text-muted fs-12"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($row['follow_up_time'] ?? '') ?></div>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge bg-light text-primary border border-primary-subtle p-2">
                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($row['created_by_name'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['client_name'] ?? '') ?></div>
                                            <div class="text-muted fs-12"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['client_phone'] ?? '') ?></div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['project_name'] ?? '') ?></div>
                                            <div class="text-muted fs-12"><?= htmlspecialchars($row['flat_name'] ?? '') ?></div>
                                        </td>
                                        <td class="align-middle">
                                            <?php if (!empty($row['follow_up_type'])): ?>
                                                <div class="mb-2">
                                                    <span class="badge bg-success"><i class="bi bi-tag me-1"></i>Follow Up for: <?= htmlspecialchars($row['follow_up_type'] ?? '') ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="note-text p-2 bg-light rounded text-dark fs-13">
                                                <?= nl2br(htmlspecialchars($row['discussion_note'] ?? 'No note provided')) ?>
                                            </div>
                                            <?php if (!empty($row['activity_date']) || !empty($row['activity_mode']) || !empty($row['activity_reason'])): ?>
                                                <div class="mt-2 p-2 bg-white border border-secondary-subtle rounded fs-12">
                                                    <?php if (!empty($row['activity_date'])): ?>
                                                        <div class="mb-1"><i class="bi bi-calendar-event me-1 text-muted"></i><span class="fw-semibold text-muted">Activity Date:</span> <?= date('d M Y', strtotime($row['activity_date'])) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['activity_mode'])): ?>
                                                        <div class="mb-1"><i class="bi bi-info-circle me-1 text-muted"></i><span class="fw-semibold text-muted">Mode:</span> <?= htmlspecialchars($row['activity_mode'] ?? '') ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['activity_reason'])): ?>
                                                        <div><i class="bi bi-question-circle me-1 text-muted"></i><span class="fw-semibold text-muted">Reason:</span> <?= htmlspecialchars($row['activity_reason'] ?? '') ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pgTotalPages > 1): ?>
                    <nav class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top no-print gap-2" aria-label="CED report pagination">
                        <div class="text-muted fs-13">
                            Page <strong><?= $pgPage ?></strong> of <strong><?= $pgTotalPages ?></strong>
                        </div>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                                $buildPageUrl = static function ($targetPage) use ($baseQueryParams) {
                                    return '?' . http_build_query(array_merge($baseQueryParams, ['page' => $targetPage]));
                                };
                                $winStart = max(1, $pgPage - 2);
                                $winEnd = min($pgTotalPages, $pgPage + 2);
                            ?>
                            <li class="page-item <?= ($pgPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($pgPage > 1) ? htmlspecialchars($buildPageUrl($pgPage - 1)) : '#' ?>">Prev</a>
                            </li>
                            <?php if ($winStart > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($buildPageUrl(1)) ?>">1</a></li>
                                <?php if ($winStart > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <?php endif; ?>
                            <?php for ($p = $winStart; $p <= $winEnd; $p++): ?>
                                <li class="page-item <?= ($p === $pgPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($p)) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($winEnd < $pgTotalPages): ?>
                                <?php if ($winEnd < $pgTotalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($buildPageUrl($pgTotalPages)) ?>"><?= $pgTotalPages ?></a></li>
                            <?php endif; ?>
                            <li class="page-item <?= ($pgPage >= $pgTotalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($pgPage < $pgTotalPages) ? htmlspecialchars($buildPageUrl($pgPage + 1)) : '#' ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
