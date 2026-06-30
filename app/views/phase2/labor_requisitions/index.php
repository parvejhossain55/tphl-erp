<?php
$base      = $GLOBALS['base_url'];
$isSE      = in_array($userRole, [12, 22, 1]);
$isPM      = in_array($userRole, [15, 22, 1]);
$isQC      = in_array($userRole, [13, 24, 25, 1]);
$isMgmt    = in_array($userRole, [3, 1]);
$isAccts   = in_array($userRole, [6, 1]);
$filterStatus = $_GET['status'] ?? '';
$isPayView = $isAccts && $filterStatus === 'management_approved';

$statusBadge = [
    'pending'              => 'bg-warning text-dark',
    'pm_approved'          => 'bg-info text-dark',
    'work_completed'       => 'bg-primary',
    'payment_requested'    => 'bg-purple text-white',
    'qc_approved'          => 'bg-teal text-white',
    'pm_payment_approved'  => 'bg-indigo text-white',
    'management_approved'  => 'bg-success',
    'partially_paid'       => 'bg-warning text-dark',
    'paid'                 => 'bg-success',
    'rejected'             => 'bg-danger',
];
$statusLabel = [
    'pending'              => '⏳ Pending PM',
    'pm_approved'          => '✅ PM Approved',
    'work_completed'       => '🔨 Work Done',
    'payment_requested'    => '💸 Payment Req.',
    'qc_approved'          => '🔍 QC Approved',
    'pm_payment_approved'  => '✔️ PM Verified',
    'management_approved'  => '👔 Mgmt Approved',
    'partially_paid'       => '⏳ Partially Paid',
    'paid'                 => '💰 Paid',
    'rejected'             => '❌ Rejected',
];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Labor Requisitions</h1>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/con-dashboard">Construction</a></li>
                <li class="breadcrumb-item active">Labor Requisitions</li>
            </ol></nav>
        </div>
        <?php if ($isSE): ?>
        <a href="<?php echo $base; ?>/con-labor-requisition-create" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Requisition
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card custom-card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?php echo $base; ?>/con-labor-requisitions" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-12 mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (($_GET['project_id'] ?? '') == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-12 mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <?php foreach ($statusLabel as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo (($_GET['status'] ?? '') === $k) ? 'selected' : ''; ?>>
                                <?php echo strip_tags($v); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo $base; ?>/con-labor-requisitions" class="btn btn-light btn-sm border w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title fs-14">
                <i class="fa-solid fa-hard-hat me-2 text-warning"></i>
                <?php if ($isPayView): ?>
                    <span class="text-success">Labor Payment Approvals</span>
                <?php elseif ($filterStatus === 'paid'): ?>
                    <span class="text-primary">Paid Labor Requisitions</span>
                <?php else: ?>
                    All Labor Requisitions
                <?php endif; ?>
                <span class="badge bg-primary ms-2"><?php echo count($requisitions); ?></span>
            </div>
            <?php if ($isPayView && !empty($requisitions)): ?>
            <span class="badge bg-success-transparent text-success fs-12">
                <i class="fa-solid fa-circle-check me-1"></i>
                Management Approved — Ready to Pay
            </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap mb-0 fs-13">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Requisition No</th>
                            <th>Project</th>
                            <th>Labor</th>
                            <th>Rate/Day</th>
                            <th>Days</th>
                            <th class="text-end">Total Amount</th>
                            <th>Purpose</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($requisitions)): ?>
                        <tr><td colspan="12" class="text-center text-muted py-5">
                            <i class="fa-solid fa-inbox fs-30 d-block mb-2 text-muted opacity-50"></i>
                            No labor requisitions found.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($requisitions as $i => $lr): ?>
                        <tr>
                            <td class="text-muted fs-12"><?php echo $i + 1; ?></td>
                            <td>
                                <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="fw-semibold text-primary">
                                    <?php echo htmlspecialchars($lr['requisition_no']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($lr['project_name'] ?? '---'); ?></td>
                            <td>
                                <span class="fw-semibold"><?php echo $lr['labor_count']; ?></span>
                                <small class="text-muted ms-1"><?php echo htmlspecialchars($lr['labor_type']); ?></small>
                            </td>
                            <td>&#2547; <?php echo number_format($lr['per_labor_rate'], 0); ?></td>
                            <td><?php echo $lr['working_days']; ?> days</td>
                            <td class="text-end fw-bold text-success">&#2547; <?php echo number_format($lr['total_amount'], 2); ?></td>
                            <td style="max-width:180px;white-space:normal;font-size:12px"><?php echo htmlspecialchars(mb_substr($lr['purpose'], 0, 60)); ?><?php echo mb_strlen($lr['purpose']) > 60 ? '…' : ''; ?></td>
                            <td><?php echo htmlspecialchars($lr['requested_by_name'] ?? '---'); ?></td>
                            <td><?php echo date('d M Y', strtotime($lr['requisition_date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $statusBadge[$lr['status']] ?? 'bg-secondary'; ?> fs-11">
                                    <?php echo $statusLabel[$lr['status']] ?? $lr['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isAccts && $lr['status'] === 'management_approved'): ?>
                                    <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i> Pay Now
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $base; ?>/con-labor-requisition/<?php echo $lr['id']; ?>" class="btn btn-icon btn-sm btn-light border">
                                        <i class="fa-solid fa-eye fs-11"></i>
                                    </a>
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
