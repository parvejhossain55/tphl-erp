<div class="row justify-content-center">
    <div class="col-xxl-12 col-xl-12 col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-20 gap-3">
            <h4 class="mb-0"><i class="fa-solid fa-money-bill-transfer me-2"></i> Advance Salary Requests</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal"><i
                    class="fa-solid fa-plus me-2"></i> Request Advance</button>
        </div>
        <div class="card__wrapper shadow-sm border-0 mb-30"
            style="border-radius: 12px; overflow: hidden; background: #fff;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advances as $adv): ?>
                            <tr>
                                <td>#
                                    <?= $adv['id'] ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($adv['employee_name']) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($adv['emp_code']) ?>
                                    </div>
                                </td>
                                <td class="fw-semibold">৳
                                    <?= number_format($adv['amount'], 2) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($adv['reason']) ?>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($adv['request_date'])) ?>
                                </td>
                                <td>
                                    <?php if ($adv['status'] == 'pending_hr' || $adv['status'] == 'pending'): ?>
                                        <div class="mb-1"><span class="badge bg-warning text-dark px-2 py-1 rounded">Pending
                                                approval of HR Head</span></div>
                                        <span class="badge bg-warning"><i class="fa-solid fa-clock me-1"></i>HR Pending
                                            Approval</span>
                                    <?php elseif ($adv['status'] == 'pending_accounts'): ?>
                                        <span class="badge bg-info text-dark"><i
                                                class="fa-solid fa-hourglass-half me-1"></i>Management Pending Approval</span>
                                    <?php elseif ($adv['status'] == 'approved'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Account Pending
                                            Payment</span>
                                    <?php elseif ($adv['status'] == 'paid' || $adv['status'] == 'deducted'): ?>
                                        <span class="badge bg-primary"><i class="fa-solid fa-check-double me-1"></i>All
                                            Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= ucfirst($adv['status']) ?></span>
                                    <?php endif; ?>

                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        <?= $adv['approved_date'] ? '<i class="fa-regular fa-clock me-1"></i>Approved: ' . date('d M Y h:i A', strtotime($adv['approved_date'])) : '' ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <?php
                                    $userRole = (int) ($_SESSION['user']['role_id'] ?? 0);
                                    $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

                                    $hasHrPerm = (function_exists('hasPermission') && hasPermission('Hr Manage'));

                                    $canHrApprove = ($userRole === 1 || $userRole === 4 || $userRole === 18 || $roleName === 'hr');
                                    $canMgmtApprove = (in_array($userRole, [1, 3, 20, 32]) || strpos($roleName, 'management') !== false || $roleName === 'admin' || $roleName === 'super admin');
                                    $canAccountsApprove = (in_array($userRole, [1, 6]) || strpos($roleName, 'account') !== false);

                                    $isHrActive = ($adv['status'] == 'pending_hr' || $adv['status'] == 'pending');
                                    $isMgmtActive = ($adv['status'] == 'pending_accounts');
                                    $isPayActive = ($adv['status'] == 'approved');
                                    ?>
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <!-- HR Button -->
                                        <?php if ($isHrActive && $canHrApprove): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/approve-advance-salary/<?= $adv['id'] ?>?step=hr"
                                                class="btn btn-sm btn-outline-primary shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500;"
                                                onclick="confirmApproval(event, 'Approve as HR?', 'Are you sure you want to approve this request as the HR Head?')">
                                                HR Pending Approval
                                            </a>
                                        <?php elseif (in_array($adv['status'], ['pending_accounts', 'approved', 'paid', 'deducted'])): ?>
                                            <span class="btn btn-sm btn-success text-white shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500; cursor: default;">
                                                <i class="fa-solid fa-check me-1"></i> HR Approved
                                            </span>
                                        <?php else: ?>
                                            <a href="javascript:void(0)"
                                                class="btn btn-sm btn-light text-muted rounded-2 border shadow-sm"
                                                style="padding: 5px 12px; font-weight: 500;" title="HR Approval Required">
                                                <i class="fa-solid fa-lock me-1 text-warning"></i>HR Only
                                            </a>
                                        <?php endif; ?>

                                        <!-- Management Button -->
                                        <?php if ($isMgmtActive && $canMgmtApprove): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/approve-advance-salary/<?= $adv['id'] ?>?step=mgmt"
                                                class="btn btn-sm btn-outline-info text-dark shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500;"
                                                onclick="confirmApproval(event, 'Approve as Management?', 'Are you sure you want to approve this request as Management?')">
                                                Management Pending Approval
                                            </a>
                                        <?php elseif (in_array($adv['status'], ['approved', 'paid', 'deducted'])): ?>
                                            <span class="btn btn-sm btn-success text-white shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500; cursor: default;">
                                                <i class="fa-solid fa-check me-1"></i> Management Approved
                                            </span>
                                        <?php else: ?>
                                            <a href="javascript:void(0)"
                                                class="btn btn-sm btn-light text-muted rounded-2 border shadow-sm"
                                                style="padding: 5px 12px; font-weight: 500;"
                                                title="Management Approval Required">
                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Management Only
                                            </a>
                                        <?php endif; ?>

                                        <!-- Pay Button -->
                                        <?php if ($isPayActive && $canAccountsApprove): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500;"
                                                data-bs-toggle="modal" data-bs-target="#payModal<?= $adv['id'] ?>">
                                                Pending Payment
                                            </button>
                                            
                                            <!-- Pay Advance Modal for this Request -->
                                            <div class="modal fade" id="payModal<?= $adv['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Accounts Payout Processing</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start border-top">
                                                            <form action="<?= $GLOBALS['base_url'] ?>/pay-advance-salary" method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="advance_salary_id" value="<?= $adv['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Employee Name</label>
                                                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($adv['employee_name'] ?? '') ?>" readonly>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Requested Amount</label>
                                                                    <input type="text" class="form-control bg-light" value="৳ <?= number_format($adv['amount'] ?? 0, 2) ?>" readonly>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Approved By</label>
                                                                    <input type="text" class="form-control bg-light" value="Management Team" readonly>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Final Payout Amount <span class="text-danger">*</span></label>
                                                                    <input type="number" step="0.01" class="form-control" name="paid_amount" value="<?= htmlspecialchars($adv['amount'] ?? '') ?>" placeholder="Enter approved amount" required>
                                                                </div>
                                                                
                                                                <div class="mb-4">
                                                                    <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Payment Document (Receipt/Voucher)</label>
                                                                    <input type="file" class="form-control" name="payment_document" accept=".jpg,.jpeg,.png,.pdf">
                                                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Max size 2MB (JPG, PNG, PDF)</small>
                                                                </div>
                                                                
                                                                <div class="modal-footer px-0 pb-0 border-top-0">
                                                                    <button type="button" class="btn border" data-bs-dismiss="modal" style="background:#fff;">Cancel</button>
                                                                    <button type="submit" class="btn btn-success">Submit Payment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($adv['status'] === 'paid'): ?>
                                            <span class="btn btn-sm btn-success text-white shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500; cursor: default;">
                                                <i class="fa-solid fa-check-double me-1"></i> Paid
                                            </span>
                                        <?php elseif ($adv['status'] === 'deducted'): ?>
                                            <span class="btn btn-sm btn-success text-white shadow-sm rounded"
                                                style="padding: 5px 12px; font-weight: 500; cursor: default;">
                                                <i class="fa-solid fa-flag-checkered me-1"></i> Deducted
                                            </span>
                                        <?php else: ?>
                                            <!-- Empty for other states to not clutter UI unnecessarily -->
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($advances)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No Advance Salary Requests Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Request Advance Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $GLOBALS['base_url'] ?>/store-advance-salary" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel"><i class="fa-solid fa-money-bill-transfer me-2"></i>
                        Request Advance Salary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Employee <span
                                class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2 w-100" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Advance Amount <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" name="amount" class="form-control" step="0.01" min="1"
                                placeholder="Enter amount" required>
                        </div>
                    </div>
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Request Date <span
                                class="text-danger">*</span></label>
                        <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>"
                            required>
                    </div>
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Reason for Advance <span
                                class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="State reason..."
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary border" style="background:#fff; color:#000;" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"
                        style="background-color: #0f766e; border-color: #0f766e;"><i
                            class="fa-solid fa-paper-plane me-2"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
                const employee = button.getAttribute('data-employee');
                const amount = button.getAttribute('data-amount');

                this.querySelector('#payModalId').value = id;
                this.querySelector('#payModalEmployee').value = employee;
                this.querySelector('#payModalRequestedAmount').value = amount;
                this.querySelector('#payModalPaidAmount').value = amount;
            });
        }

        function confirmApproval(event, title, text) {
            event.preventDefault();
            const url = event.currentTarget.getAttribute('href');

            if (typeof Swal === 'undefined') {
                if (confirm(text)) window.location.href = url;
                return;
            }

            Swal.fire({
                title: title || 'Are you sure?',
                text: text || 'Do you want to proceed?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>