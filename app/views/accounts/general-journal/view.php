<?php 
// Layout handled by Controller via master.php
?>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="fa-light fa-file-invoice"></i> Journal Entry Details
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <?php 
                $role_id = $_SESSION['user']['role_id'] ?? 0;
                if (in_array($role_id, [1, 3, 6, 30])): 
            ?>
                <a href="<?= $base_url ?>/general-journal/edit/<?= $journal['id'] ?>" class="btn btn-primary me-2">
                    <i class="fa-light fa-edit"></i> Edit
                </a>
                <?php if (strtolower($journal['status']) === 'pending'): ?>
                    <a href="<?= $base_url ?>/general-journal/approve/<?= $journal['id'] ?>" class="btn btn-success me-2" onclick="return confirm('Are you sure you want to approve this journal entry?')">
                        <i class="fa-light fa-check-circle"></i> Approve
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <button class="btn btn-outline-primary me-2" onclick="window.print()">
                <i class="fa-light fa-print"></i> Print
            </button>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary">
                <i class="fa-light fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Status Badge & Meta Info (Screen Only) -->
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <div>
            <span class="text-muted fw-bold me-2">Voucher Type:</span> <?= htmlspecialchars($journal['voucher_type']) ?>
            <span class="mx-3 text-muted">|</span>
            <span class="text-muted fw-bold me-2">Project:</span> <span class="bd-badge bg-success border fw-bold"><?= htmlspecialchars($journal['project_name'] ?: 'Head Office / General') ?></span>
            <span class="mx-3 text-muted">|</span>
            <span class="text-muted fw-bold me-2">Office:</span> <?= htmlspecialchars($journal['office'] ?: 'Corporate Office') ?>
        </div>
        <div>
            <?php 
                $status = strtolower($journal['status']);
                $badge = 'bg-secondary';
                if ($status == 'approved' || $status == 'posted') $badge = 'bg-success';
                elseif ($status == 'pending') $badge = 'bg-warning ';
            ?>
            <span class="badge <?= $badge ?> px-3 py-2 text-uppercase fs-6">
                Status: <?= htmlspecialchars($journal['status']) ?>
            </span>
        </div>
    </div>

    <div class="printable-area d-flex justify-content-center">
        <div class="classic-journal-wrapper w-100" style="max-width: 900px; margin: 0 auto;">
            <!-- Header -->
            <div class="journal-header p-3 pb-2 ">
                <div class="text-center mb-3">
                    <h3 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">THE PREMIUM HOMES LTD</h3>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <div class="fw-bold fs-5">General Journal</div>
                        <div class="small mt-1">
                            <strong>Project:</strong> <span class="fw-bold text-primary"><?= htmlspecialchars($journal['project_name'] ?: 'Head Office / General') ?></span>
                            <span class="mx-1">|</span> 
                            <strong>Office:</strong> <?= htmlspecialchars($journal['office'] ?: 'Corporate Office') ?>
                        </div>
                    </div>
                    <div class="text-end small">
                        Journal No: <?= htmlspecialchars($journal['journal_number']) ?><br>
                        Page No. 01
                    </div>
                </div>
            </div>

            <!-- Classic Journal Table -->
            <div class="table-responsive">
                <table class="table mb-0 classic-journal-table border-dark">
                    <thead>
                        <tr>
                            <th class="text-center align-middle" style="width: 12%;">DATE</th>
                            <th class="text-center align-middle" style="width: 43%;">PARTICULARS</th>
                            <th class="text-center align-middle" style="width: 15%;">POST REF</th>
                            <th class="text-center align-middle" style="width: 15%;">DEBIT</th>
                            <th class="text-center align-middle" style="width: 15%;">CREDIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $year = date('Y', strtotime($journal['date']));
                        $dateStr = date('M. d', strtotime($journal['date']));
                        ?>
                        <!-- Year Row -->
                        <tr>
                            <td class="text-center fw-bold border-bottom-0 pb-0"><?= $year ?></td>
                            <td class="border-bottom-0"></td>
                            <td class="border-bottom-0"></td>
                            <td class="border-bottom-0"></td>
                            <td class="border-bottom-0"></td>
                        </tr>

                        <?php 
                        $debits = array_filter($details, function($r) { return $r['debit'] > 0; });
                        $credits = array_filter($details, function($r) { return $r['credit'] > 0; });
                        $isFirstRow = true;
                        
                        // Print Debits
                        foreach ($debits as $row): 
                        ?>
                        <tr>
                            <td class="text-center fw-bold pt-0 <?= $isFirstRow ? '' : 'border-top-0' ?>">
                                <?= $isFirstRow ? $dateStr : '' ?>
                            </td>
                            <td class="px-3">
                                <?php if(!empty($row['parent_name'])): ?>
                                    <span class="text-muted small"><?= htmlspecialchars($row['parent_name']) ?> &gt; </span>
                                <?php endif; ?>
                                <strong class=" fw-bolder fs-6 bg-light border px-2 py-1 rounded d-inline-block shadow-sm">
                                    <i class="fa-solid fa-folder text-primary me-1"></i><?= htmlspecialchars($row['account_name']) ?>
                                </strong>
                                <?php if(!empty($row['narration'])): ?>
                                    <br><span class="fw-normal text-muted fst-italic ms-3">- <?= htmlspecialchars($row['narration']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold font-monospace "><?= htmlspecialchars($row['account_code']) ?></td>
                            <td class="text-end fw-bold px-3 fs-6 "><?= number_format($row['debit'], 2) ?></td>
                            <td class="text-end"></td>
                        </tr>
                        <?php $isFirstRow = false; endforeach; ?>
                        
                        <?php 
                        // Print Credits
                        foreach ($credits as $row): 
                        ?>
                        <tr>
                            <td class="border-top-0"></td>
                            <td class="px-5">
                                <?php if(!empty($row['parent_name'])): ?>
                                    <span class="text-muted small"><?= htmlspecialchars($row['parent_name']) ?> &gt; </span>
                                <?php endif; ?>
                                <strong class=" fw-bolder fs-6 bg-light border px-2 py-1 rounded d-inline-block shadow-sm">
                                    <i class="fa-solid fa-folder text-info me-1"></i><?= htmlspecialchars($row['account_name']) ?>
                                </strong>
                                <?php if(!empty($row['narration'])): ?>
                                    <br><span class="text-muted fst-italic ms-3">- <?= htmlspecialchars($row['narration']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold font-monospace "><?= htmlspecialchars($row['account_code']) ?></td>
                            <td class="text-end"></td>
                            <td class="text-end fw-bold px-3 fs-6 "><?= number_format($row['credit'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                        // Print Main Narration Row
                        if (!empty(trim($journal['narration']))): 
                        ?>
                        <tr class="narration-row">
                            <td class="border-top-0"></td>
                            <td class="fst-italic px-3 ">
                                To record: <?= htmlspecialchars($journal['narration']) ?>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold pe-4">TOTAL:</td>
                            <td class="text-end fw-bold px-3"><?= number_format($journal['total_debit'], 2) ?></td>
                            <td class="text-end fw-bold px-3"><?= number_format($journal['total_credit'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures -->
            <div class="row m-0 p-4 pb-5 d-print-flex">
                <div class="col-6">
                    <p class="text-muted small mb-0">Created By: <strong><?= htmlspecialchars($journal['created_by_name'] ?? 'System') ?></strong></p>
                    <p class="text-muted small">Created At: <?= date('d M Y, h:i A', strtotime($journal['created_at'])) ?></p>
                </div>
                <div class="col-6 text-end d-none d-print-block mt-5">
                    <div class="d-inline-block text-center pt-2" style="width: 200px; border-top: 1px solid #1a3c63;">
                        Authorized Signature
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Specific styling for classic journal layout matching standard accounting format */
.classic-journal-wrapper {
    background-color: transparent !important;
    border: none !important;
    border-radius: 0;
}
.journal-header {
    background-color: transparent !important;
    color: inherit !important;
    border-bottom: none !important;
}
.classic-journal-table {
    border: 2px solid #000 !important;
}
.classic-journal-table th {
    background-color: transparent !important;
    color: inherit !important;
    border: 1px solid #000 !important;
    border-bottom: 2px solid #000 !important;
    font-size: 0.9em;
}
.classic-journal-table td {
    border: 1px solid #000 !important;
    border-top: none !important; /* Top border is managed per cell logic */
    color: inherit !important;
    background-color: transparent !important;
    vertical-align: top;
}
/* Ensure the first cell (Date) has consistent borders */
.classic-journal-table td:first-child {
    border-left: none !important;
}
/* Ensure the last cell has consistent borders */
.classic-journal-table td:last-child {
    border-right: none !important;
}

/* Horizontal borders within the tbody */
.classic-journal-table tbody tr {
    border-bottom: 1px solid #000 !important;
}

/* Narration Highlight Row */
.narration-row td {
    background-color: transparent !important;
}

.classic-journal-table tfoot td {
    border-top: 2px solid #000 !important;
    border-bottom: 0 !important;
    background-color: transparent !important;
}

@media print {
    @page { margin: 10mm; }
    html, body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    body * {
        visibility: hidden;
    }
    .printable-area, .printable-area * {
        visibility: visible;
        color: #000000 !important;
        background-color: transparent !important;
    }
    .printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
        background-color: #ffffff !important;
    }
    .classic-journal-wrapper {
        border: none !important;
    }
    .journal-header {
        background-color: transparent !important;
        color: #000 !important;
        border-bottom: none !important;
    }
    .classic-journal-table {
        border: 2px solid #000 !important;
    }
    .classic-journal-table th,
    .classic-journal-table td {
        border-color: #000 !important;
        color: #000 !important;
    }
    .text-muted {
        color: #6c757d !important;
    }
}
</style>

