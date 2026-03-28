
    <!-- ══ PAGE: LEAVES ══ -->
   
   <div class="row">
    <div class="card__wrapper">
        <div class="section-header">
            <div class="section-title">Leave Management</div>
            <div class="section-sub">Track and manage your leave quota</div>
        </div>

        <!-- Leave Balance Cards -->
        <div class="row mb-4">
            <?php if (!empty($leaveBalances)): ?>
                <?php foreach ($leaveBalances as $lb): ?>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="stat-icon" style="background:rgba(79,140,255,.12)">
                                    <i class="bi bi-calendar2-check" style="color:var(--accent)"></i>
                                </div>
                                <div>
                                    <div style="font-size:.85rem;font-weight:700"><?= htmlspecialchars($lb['type_name']) ?></div>
                                    <div style="font-size:.72rem;color:var(--muted)">Total: <?= $lb['total_days'] ?> days/year
                                        <?= !$lb['is_paid'] ? '· <span style="color:#e55353">Unpaid</span>' : '' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div class="text-center">
                                    <div style="font-size:1.6rem;font-weight:700;color:var(--accent5);font-family:'Space Mono',monospace"><?= $lb['used'] ?></div>
                                    <div style="font-size:.7rem;color:var(--muted)">Used</div>
                                </div>
                                <div class="text-center">
                                    <div style="font-size:1.6rem;font-weight:700;color:var(--accent3);font-family:'Space Mono',monospace">
                                        <?= $lb['is_paid'] ? $lb['remaining'] : 'N/A' ?>
                                    </div>
                                    <div style="font-size:.7rem;color:var(--muted)">Remaining</div>
                                </div>
                            </div>
                            <?php if ($lb['is_paid']): ?>
                            <div class="att-bar-wrap mt-3">
                                <div class="att-bar" style="width:<?= $lb['pct'] ?>%;background:var(--accent3)"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No leave types configured yet.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Leave Application Link -->
        <div class="mb-3">
            <a href="<?= $GLOBALS['base_url'] ?>/create-self-leave-request" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Apply for Leave
            </a>
        </div>

        <!-- Leave History -->
        <div class="stat-card">
            <div class="section-title mb-3">Leave History</div>
            <div class="table-responsive">
                <table class="leave-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($myLeaves)): ?>
                            <?php foreach ($myLeaves as $i => $leave): ?>
                                <?php
                                $badgeClass = match ($leave['approval_status'] ?? '') {
                                    'approved' => 'badge-present',
                                    'rejected' => 'badge-late',
                                    default    => 'badge-leave',
                                };
                                ?>
                                <tr>
                                    <td style="color:var(--muted)"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><span class="leave-badge badge-leave"><?= htmlspecialchars($leave['leave_type_name'] ?? '') ?></span></td>
                                    <td><?= !empty($leave['start_date']) ? date('M d', strtotime($leave['start_date'])) : '-' ?></td>
                                    <td><?= !empty($leave['end_date']) ? date('M d', strtotime($leave['end_date'])) : '-' ?></td>
                                    <td><?= (int)($leave['requested_days'] ?? 0) ?></td>
                                    <td style="color:var(--muted)"><?= htmlspecialchars($leave['reason'] ?? '-') ?></td>
                                    <td>
                                        <span class="leave-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars(ucfirst($leave['approval_status'] ?? 'Pending')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No leave records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>