<?php
$page_title = "Onboard Lead";
$page = 'Onboard Leads';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<style>
    .locked-option {
        color: #dc3545 !important;
        font-weight: 600;
    }
    .booked-option {
        color: #6c757d;
    }
</style>
<div class="row">
    <div clas="card__wrapper">
        <a href="<?= $base_url ?>/edit-lead/<?= $lead['id'] ?>"
            class="btn btn-sm btn-primary">
            Complete Client Profile
        </a>
    </div>

    <div class="row mb-4">

    </div>
    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <h5 class="mb-3">Booking Flat/Unit</h5>


            <form action="<?= $GLOBALS['base_url'] ?>/booking-store" method="POST" enctype="multipart/form-data">
                <div class="row gy-3">
                    <!-- Lead ID (hidden) -->
                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?? '' ?>">
                    <!-- Auth User -->
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?? 1 ?>">

                    <!-- Lead Name -->
                    <div class="col-lg-6 ">
                        <div class="form__input-title">

                            <label class="form-label">Client Name</label>
                        </div>
                        <input type="text" class="form-control"
                            value="<?= $lead['full_name'] ?? '' ?>" readonly>
                    </div>

                    <!-- Booking Date -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Booking Date</label>
                        </div>
                        <input type="date" name="booking_date" class="form-control"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Project -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Project *</label>
                        </div>
                        <select name="project_id" id="projectSelect" class="form-select select2" data-placeholder="Select Project" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $isUpcoming = ($project['project_sale_status'] === 'Upcoming');
                                    $isSoldOut = ($project['project_sale_status'] === 'Sold Out');
                                    $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= $isUpcoming ? 'disabled style="color: red;"' : '' ?>
                                    <?= $isSoldOut ? 'style="color: red;"' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Flat / Unit *</label>
                        </div>
                        <select id="flatSelect" name="flat_id" class="form-select select2" data-placeholder="Select Flat">

                            <option value="">Select Flat</option>
                        </select>
                    </div>

                    <!-- Plot / Flat Price -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Total Flat Value <span>(Approx)</span> </label>
                        </div>
                        <input type="text"
                            class="form-control"
                            id="flatPrice"
                            readonly>
                    </div>
                    <!-- Land Share Value -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label"> Land Share amount  </label>
                        </div>
                        <input type="text"
                            class="form-control"
                            id="landValue"
                            readonly>
                    </div>

                    <!-- Agreement Value -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Booking Money *</label>
                        </div>
                        <input type="number" name="agreement_value" id="agreementValueInput" class="form-control"
                            placeholder="e.g. 5000000" required>
                    </div>
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Discount Amount</label>
                        </div>
                        <input type="number" name="discount_amount" id="discount_amount_input" class="form-control"
                            placeholder="" >
                    </div>

                    <!-- Dynamic Discount Attachment (Visible when Discount Amount > 0) -->
                    <div class="col-lg-6" id="discount_attachment_col" style="display: none;">
                        <div class="form__input-title">
                            <label class="form-label fw-bold">Discount Attachment / Supporting Slip</label>
                        </div>
                        <input type="file" name="discount_attachment" id="discount_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted" style="font-size: 11px;">Upload client application, approval slip, or supporting note.</small>
                    </div>
                    
                    <!-- Already Approved Options (Hidden) -->
                    <div class="col-lg-12 mt-3 d-none" id="already_approved_container" style="display: none !important;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_already_approved" id="is_already_approved" value="1">
                            <label class="form-check-label" for="is_already_approved">
                                Is Discount Already Approved? (Backlog Data)
                            </label>
                        </div>
                        <div class="row mt-2" id="already_approved_fields" style="display: none;">
                            <div class="col-lg-6">
                                <label class="form-label">Approved By *</label>
                                <select name="approved_by" id="approved_by" class="form-select select2">
                                    <option value="">Select Management User</option>
                                    <?php if(!empty($managementUsers)): ?>
                                        <?php foreach($managementUsers as $mu): ?>
                                            <option value="<?= $mu['id'] ?>"><?= htmlspecialchars($mu['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Approval Attachment *</label>
                                <input type="file" name="approval_attachment" id="approval_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Locked Booking</button>
                </div>

            </form>
        </div>
    </div>
</div>


<!-- Onboard Records Table -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table table-striped table-bordered multiple_tables" id="bookingTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Sales By</th>
                            <th>Created By</th>
                            <th>Discount Approved Status /Amount </th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($bookingRecords)): ?>
                            <?php foreach ($bookingRecords as $index => $or): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                              


                                    <td><?= htmlspecialchars($lead['full_name'] ?? '-') ?></td>

                                    <td><?= htmlspecialchars($or['project_name'] ?? '-') ?></td>

                                    <td>
                                        <?= htmlspecialchars($or['flat_name'] ?? '-') ?>
                                        <br>
                                        <small class="text-muted">
                                            Floor: <?= $or['floor_no'] ?? '-' ?>,
                                            <?= $or['sqft'] ?? '-' ?> sqft
                                        </small>
                                    </td>

                                    <td><?= $or['sales_user'] ?? 'N/A'?></td>
                                    <td><?= $or['created_user'] ?? 'N/A'?></td>

                                    <!-- Discount Status -->
                                    <td>
                                        <?php if ($or['approval_status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending Approval</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format((float)($or['discount_amount'] ?? 0), 2) ?></div>
                                        <?php elseif ($or['approval_status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format((float)($or['approved_amount'] ?? $or['discount_amount'] ?? 0), 2) ?></div>
                                        <?php elseif ($or['approval_status'] === 'partial'): ?>
                                            <span class="badge bg-success">Partial Approved</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format((float)($or['approved_amount'] ?? 0), 2) ?></div>
                                        <?php elseif ($or['approval_status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php elseif (!empty($or['discount_amount']) && (float)$or['discount_amount'] > 0): ?>
                                            <span class="badge bg-warning">Pending Approval</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format((float)$or['discount_amount'], 2) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-info">No Discount</span>
                                        <?php endif; ?>

                                        <?php if (!empty($or['past_approval_attachment'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($or['past_approval_attachment']) ?>" target="_blank" class="badge bg-light text-primary border" title="View discount attachment">
                                                    <i class="fa-solid fa-paperclip me-1"></i> Attachment
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">

                                             <?php 
                                                $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
                                                $currentUserRole = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
                                                $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
                                                $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                                $userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

                                                $isManagement = in_array($currentUserRole, [1, 3, 20, 32, 33, 35]) ||
                                                                in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                                                                (strpos($userRoleName, 'admin') !== false) ||
                                                                (strpos($userRoleName, 'management') !== false) ||
                                                                (strpos($userRoleName, 'ceo') !== false) ||
                                                                (strpos($userRoleName, 'chief') !== false) ||
                                                                (strpos($userName, 'management') !== false);

                                                $isSalesHead = in_array($currentUserRole, [5, 9]) ||
                                                               in_array($secondaryRoleId, [5, 9]) ||
                                                               (strpos($userRoleName, 'sales head') !== false) ||
                                                               (strpos($userRoleName, 'team head') !== false);

                                                $isManagementOrSalesHead = ($isManagement || $isSalesHead);
                                                $isBookingVerified = (!empty($or['verified_payment_count']) && (int)$or['verified_payment_count'] > 0);

                                                // Specific salesperson for this booking:
                                                $isThisBookingSalesperson = (!empty($or['sales_by_id']) && (int)$or['sales_by_id'] === $currentUserId)
                                                                         || (empty($or['sales_by_id']) && !empty($lead['assigned_id']) && (int)$lead['assigned_id'] === $currentUserId);

                                                $canAccessPayment = ($isManagementOrSalesHead || $isThisBookingSalesperson);
                                                $canEditBooking   = ($isManagementOrSalesHead || ($isThisBookingSalesperson && !$isBookingVerified));
                                                $canSalesRefund   = ($isManagementOrSalesHead || $isThisBookingSalesperson);
                                            ?>

                                            <!-- Edit & Delete (Enabled only if authorized and payments not verified, OR if Management/Sales Head) -->
                                            <?php if ($canEditBooking && !in_array($or['booking_status'] ?? '', ['refunded', 'cancelled'])): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary editBookingBtn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModalBooking"
                                                    data-id="<?= $or['id'] ?>"
                                                    data-project="<?= $or['project_id'] ?>"
                                                    data-flat="<?= $or['flat_id'] ?>"
                                                    data-date="<?= $or['booking_date'] ?>"
                                                    data-agreement="<?= $or['agreement_value'] ?>"
                                                    data-discount="<?= $or['discount_amount'] ?? 0 ?>"
                                                    data-attachment="<?= htmlspecialchars($or['past_approval_attachment'] ?? '') ?>"
                                                    data-approval="<?= $or['approval_status'] ?? '' ?>"
                                                    data-approved-amt="<?= $or['approved_amount'] ?? '' ?>"
                                                    data-status="<?= $or['booking_status'] ?>"
                                                    data-sales-agent="<?= $or['sales_by_id'] ?? '' ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>

                                                <?php if (!$isBookingVerified): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/booking-delete/<?= $or['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this booking? All unverified payment records will be removed and the flat will be reset to available status.');">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Payments -->
                                            <?php if (($or['booking_status'] ?? '') === 'refunded'): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Refunded
                                                </button>
                                            <?php elseif (($or['booking_status'] ?? '') === 'cancelled'): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Cancelled
                                                </button>
                                            <?php elseif ($or['approval_status'] === 'rejected'): ?>
                                                <button class="btn btn-sm btn-danger" disabled
                                                    title="Discount rejected by management">
                                                    Rejected
                                                </button>
                                            <?php else: ?>
                                                <?php 
                                                    $paymentLabel = (isset($or['payment_status']) && $or['payment_status'] === 'paid') ? 'Registration Payment' : 'Booking Payment';
                                                    $paymentBtnClass = (isset($or['payment_status']) && $or['payment_status'] === 'paid') ? 'btn-info' : 'btn-success';
                                                ?>
                                                <?php if ($canAccessPayment): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $or['id'] ?>"
                                                        class="btn btn-sm <?= $paymentBtnClass ?>">
                                                        <?= $paymentLabel ?>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-secondary opacity-50" disabled
                                                        title="Payment locked: Only <?= htmlspecialchars($or['sales_agent_display'] ?? 'the assigned salesperson') ?> or Management/Sales Head can enter payment for this unit">
                                                        <i class="fa-solid fa-lock me-1"></i><?= $paymentLabel ?>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($canSalesRefund): ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger quickRefundBtn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#quickRefundModal"
                                                        data-id="<?= $or['id'] ?>"
                                                        data-paid="<?= (float)($or['total_verified_paid'] ?? 0) ?>"
                                                        data-flat="<?= htmlspecialchars($or['flat_name'] ?? '') ?>"
                                                        data-project="<?= htmlspecialchars($or['project_name'] ?? '') ?>"
                                                        data-agreement="<?= number_format($or['agreement_value'] ?? 0, 2) ?>"
                                                        data-client="<?= htmlspecialchars($lead['full_name'] ?? '') ?>">
                                                        <i class="fa-solid fa-rotate-left"></i> Refund
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($or['approval_status'] === 'pending'): ?>
                                                    <small class="text-warning d-block w-100" style="font-size:11px;">
                                                        <i class="fa-solid fa-clock"></i> Discount Pending Approval
                                                     </small>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            No Data Available
                            <!-- Empty State -->
                            <!-- <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No booking records found
                                </td>
                            </tr> -->
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>


<!-- Quick Refund Confirmation Modal -->
<?php 
    $bdBanksList = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];
?>
<div id="quickRefundModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold text-white">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Initiate Client Refund Request
                </h5>
                <button type="button" class="bd-btn-close btn-close-white" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large text-white"></i>
                </button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/booking-quick-refund" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-3">
                    <input type="hidden" name="booking_id" id="refund_booking_id">

                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 py-2 px-3">
                        <i class="fa-solid fa-circle-info fs-5 mt-0.5 text-warning flex-shrink-0"></i>
                        <div class="small">
                            <strong>Notice:</strong> Initiating refund will set booking status to <strong>Refunded</strong>, instantly release the flat unit as <strong>Available</strong>, and submit this refund request for Management &amp; Accounts processing.
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-2 mb-3 border">
                        <div class="row g-2 small">
                            <div class="col-sm-6"><strong>Client:</strong> <span id="refund_client_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Project:</strong> <span id="refund_project_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Flat Unit:</strong> <span id="refund_flat_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Agreement Value:</strong> ৳<span id="refund_agreement_val" class="font-monospace">0.00</span></div>
                        </div>
                    </div>

                    <!-- Total Verified Paid & Optional Expected Refund Date -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Total Verified Paid (আদায়কৃত টাকা)</label>
                            <input type="text" id="refund_total_paid_display" class="form-control form-control-sm bg-light fw-bold text-success font-monospace" readonly value="৳ 0.00">
                            <small class="text-muted d-block mt-1" id="refund_paid_helper" style="font-size: 11px;">
                                <i class="fa-light fa-circle-check text-success me-1"></i>Verified collection from client
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Expected Refund Date <span class="text-muted">(Optional)</span></label>
                            <input type="date" name="expected_refund_date" id="refund_expected_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                <i class="fa-light fa-calendar me-1"></i>Client's expected payout date if requested
                            </small>
                        </div>
                    </div>

                    <!-- Refund Reason -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Refund Reason (রিফান্ডের কারণ) <span class="text-danger">*</span></label>
                        <textarea name="reason" id="refund_reason" class="form-control form-control-sm" rows="2" required placeholder="State why the client is requesting this refund..."></textarea>
                    </div>

                    <!-- Client Payout Bank & Payment Information -->
                    <div class="card custom-card border mb-3 shadow-none">
                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center">
                            <i class="fa-solid fa-building-columns text-primary me-2"></i>
                            <h6 class="card-title mb-0 fs-13 fw-semibold text-primary">Client Payout Bank &amp; Payment Details</h6>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="row g-2">
                                <!-- Preferred Payment Method -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Payment Method</label>
                                    <select name="preferred_payment_method" id="refund_preferred_method" class="form-select form-select-sm">
                                        <option value="bank" selected>Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>

                                <!-- Client Bank Name -->
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold small mb-1">Client Bank Name</label>
                                    <select name="client_bank_name" id="refund_client_bank_name" class="form-select form-select-sm">
                                        <option value="">-- Select Client Bank --</option>
                                        <option value="Other Country / Foreign Bank">Other Country / Foreign Bank</option>
                                        <option value="Other Bank">Other Bank</option>
                                        <?php foreach ($bdBanksList as $bName): ?>
                                            <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Bank Account Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">A/C Holder / Client Name</label>
                                    <input type="text" name="client_bank_account_name" id="refund_client_bank_acc_name" class="form-control form-control-sm" placeholder="Name as per bank account">
                                </div>

                                <!-- Bank Account Number -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Bank Account Number</label>
                                    <input type="text" name="client_bank_account_no" id="refund_client_bank_acc_no" class="form-control form-control-sm" placeholder="Client bank account number">
                                </div>

                                <!-- Bank Branch Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Branch Name</label>
                                    <input type="text" name="client_bank_branch" id="refund_client_bank_branch" class="form-control form-control-sm" placeholder="e.g. Gulshan Branch / Principal Branch">
                                </div>

                                <!-- Routing Number (Optional) -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Routing Number <span class="text-muted">(Optional)</span></label>
                                    <input type="text" name="client_bank_routing_no" id="refund_client_bank_routing" class="form-control form-control-sm" placeholder="e.g. 090271234">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Application Attachment (PDF / Image) -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-light fa-paperclip text-primary me-1"></i> Client Refund Application / Cheque Copy / Document Attachment
                            <span class="text-muted small ms-1">(PDF, JPG, JPEG, PNG — max 10MB)</span>
                        </label>
                        <input type="file" name="refund_attachment" id="refund_attachment_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted fs-11 d-block mt-1">
                            Upload client's written refund application letter, bank cheque scan, or ID document.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold px-3" onclick="return confirm('Are you sure you want to release this flat and initiate refund request?');">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Refund Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div id="editModalBooking" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Booking</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">

                <form action="<?= $GLOBALS['base_url'] ?>/booking-update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" id="edit_booking_id">
                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?? '' ?>">

                    <div class="row gy-3">
                        <!-- Booking Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Booking Date *</label>
                            <input type="date" name="booking_date" id="edit_booking_date" class="form-control" required>
                        </div>

                        <!-- Agreement Value -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agreement Value / Booking Target *</label>
                            <input type="number" step="any" name="agreement_value" id="edit_agreement" class="form-control" required>
                        </div>

                        <!-- Project -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Project *</label>
                            <select name="project_id" id="edit_project" class="form-select select2" data-placeholder="Select Project" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <?php 
                                        $isUpcoming = ($project['project_sale_status'] === 'Upcoming');
                                        $isSoldOut = ($project['project_sale_status'] === 'Sold Out');
                                        $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                    ?>
                                    <option value="<?= $project['id'] ?>" 
                                        <?= $isUpcoming ? 'disabled style="color: red;"' : '' ?>
                                        <?= $isSoldOut ? 'style="color: red;"' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flat / Unit *</label>
                            <select name="flat_id" id="edit_flat" class="form-select select2" data-placeholder="Select Flat" required>
                                <option value="">Select Flat</option>
                            </select>
                        </div>

                        <!-- Discount Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center mb-1">
                                <span>Discount Amount</span>
                                <span id="edit_discount_lock_badge" class="badge bg-success d-none" style="font-size:11px;">
                                    <i class="fa-solid fa-lock me-1"></i> Approved by Management
                                </span>
                            </label>
                            <input type="number" step="any" name="discount_amount" id="edit_discount" class="form-control" value="0">
                            <small id="edit_discount_lock_help" class="text-muted d-none" style="font-size:11px;">
                                <i class="fa-solid fa-info-circle"></i> Discount was already approved by management and cannot be modified.
                            </small>
                        </div>

                        <!-- Discount Attachment -->
                        <div class="col-md-6 mb-3" id="edit_discount_attachment_col">
                            <label class="form-label">Discount Attachment / Supporting Slip</label>
                            <input type="file" name="discount_attachment" id="edit_discount_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <div id="edit_current_attachment_preview" class="mt-1 d-none">
                                <small class="text-muted">Current Attachment: </small>
                                <a href="" id="edit_current_attachment_link" target="_blank" class="badge bg-light text-primary border">
                                    <i class="fa-solid fa-paperclip me-1"></i> View Document
                                </a>
                            </div>
                        </div>
                        
                        <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 9, 20, 33])): ?>
                        <!-- Sales Agent Update -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sales By (Agent)</label>
                            <select name="sales_by_id" id="edit_sales_agent" class="form-select select2" data-placeholder="Select Sales Agent">
                                <option value="">Select Sales Agent</option>
                                <?php if(!empty($salesUsers)): ?>
                                    <?php foreach($salesUsers as $su): ?>
                                        <option value="<?= $su['id'] ?>">
                                            <?php
                                                $rname = $su['role_name'] ?? '';
                                                $sname = $su['sec_role_name'] ?? '';
                                                $displayRole = $rname ?: $sname ?: 'Sales';
                                            ?>
                                            <?= htmlspecialchars($su['full_name']) ?> (<?= htmlspecialchars($displayRole) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Booking</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectSelect = document.getElementById('projectSelect');
        const flatSelect = document.getElementById('flatSelect');
        const flatPriceInput = document.getElementById('flatPrice');
        const landValueInput = document.getElementById('landValue');

        function refreshSelect2(el) {
            if (window.jQuery && $(el).data('select2')) {
                $(el).trigger('change.select2');
            }
        }

        let flatData = []; // store flats

        if (projectSelect) {
            $(projectSelect).on('change', function() {
                const projectId = this.value;
                flatSelect.innerHTML = '<option value="">Loading...</option>';
                refreshSelect2(flatSelect);

                flatPriceInput.value = '';
                landValueInput.value = '';

                if (!projectId) {
                    flatSelect.innerHTML = '<option value="">Select Flat</option>';
                    refreshSelect2(flatSelect);
                    return;
                }

                fetch("<?= $GLOBALS['base_url'] ?>/get-flats-by-project/" + projectId)
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        flatData = data;
                        flatSelect.innerHTML = '<option value="">Select Flat</option>';

                        if (data.length === 0) {
                            flatSelect.innerHTML = '<option value="">No flats available</option>';
                            refreshSelect2(flatSelect);
                            return;
                        }

                        data.forEach(flat => {
                            const option = document.createElement('option');
                            option.value = flat.flat_id;

                            const isBooked = (flat.is_booked == 1) || (flat.booking_status === 'booked') || (flat.booking_status === 'completed');

                            if (isBooked) {
                                const clientLabel = flat.booked_client_name ? ` [Booked - ${flat.booked_client_name}]` : ' [Booked]';
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft${clientLabel}`;
                                option.className = 'booked-option text-danger';
                                option.disabled = true;
                                option.style.color = '#dc3545';
                            } else {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                            }

                            flatSelect.appendChild(option);
                        });
                        refreshSelect2(flatSelect);
                    })
                    .catch(error => {
                        console.error('Error fetching flats:', error);
                        flatSelect.innerHTML = '<option value="">Error loading flats</option>';
                        refreshSelect2(flatSelect);
                    });
            });
        }
        
        // Toggle Already Approved fields and Discount Attachment
        const discountInput = document.getElementById('discount_amount_input');
        const discountAttachmentCol = document.getElementById('discount_attachment_col');
        const discountAttachmentInput = document.getElementById('discount_attachment');
        const alreadyApprovedContainer = document.getElementById('already_approved_container');
        const isAlreadyApprovedCheckbox = document.getElementById('is_already_approved');
        const alreadyApprovedFields = document.getElementById('already_approved_fields');
        const approvedBySelect = document.getElementById('approved_by');
        const approvalAttachmentInput = document.getElementById('approval_attachment');

        if (discountInput) {
            discountInput.addEventListener('input', function() {
                const val = parseFloat(this.value);
                if (val > 0) {
                    if (discountAttachmentCol) discountAttachmentCol.style.display = 'block';
                } else {
                    if (discountAttachmentCol) {
                        discountAttachmentCol.style.display = 'none';
                        if (discountAttachmentInput) discountAttachmentInput.value = '';
                    }
                }
            });
        }

        if (isAlreadyApprovedCheckbox) {
            isAlreadyApprovedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    alreadyApprovedFields.style.display = 'flex';
                    approvedBySelect.required = true;
                    approvalAttachmentInput.required = true;
                } else {
                    alreadyApprovedFields.style.display = 'none';
                    approvedBySelect.required = false;
                    approvalAttachmentInput.required = false;
                }
            });
        }

        // Show price when flat selected
        $(flatSelect).on('change', function() {
            const selectedFlatId = this.value;
            const flat = flatData.find(f => f.flat_id == selectedFlatId);
            flatPriceInput.value = flat ? Number(flat.price_value).toLocaleString() : '';
            if (flat) {
                let sumValue = (Number(flat.booking_money) || 0) + (Number(flat.registration_money) || 0);
                if (sumValue > 0) {
                    landValueInput.value = sumValue.toLocaleString();
                } else {
                    landValueInput.value = Number(flat.land_share_value).toLocaleString();
                }
                
                const agreementValueInput = document.getElementById('agreementValueInput');
                if (agreementValueInput) {
                    if (flat.booking_money && Number(flat.booking_money) > 0) {
                        agreementValueInput.value = Number(flat.booking_money);
                    } else {
                        agreementValueInput.value = '';
                    }
                }
            } else {
                landValueInput.value = '';
                const agreementValueInput = document.getElementById('agreementValueInput');
                if (agreementValueInput) agreementValueInput.value = '';
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let flatDataEdit = []; // store flats for edit modal
        let currentEditingFlatId = null;

        const editProjectSelect = document.getElementById('edit_project');
        const editFlatSelect = document.getElementById('edit_flat');

        function refreshSelect2(el) {
            if (window.jQuery && $(el).data('select2')) {
                $(el).trigger('change.select2');
            }
        }

        function loadEditFlats(projectId, targetFlatId) {
            if (!editFlatSelect) return;
            editFlatSelect.innerHTML = '<option value="">Loading...</option>';
            refreshSelect2(editFlatSelect);

            if (!projectId) {
                editFlatSelect.innerHTML = '<option value="">Select Flat</option>';
                refreshSelect2(editFlatSelect);
                return;
            }

            fetch("<?= $GLOBALS['base_url'] ?>/get-flats-by-project/" + projectId)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    flatDataEdit = data;
                    editFlatSelect.innerHTML = '<option value="">Select Flat</option>';

                    data.forEach(flat => {
                        const opt = document.createElement('option');
                        opt.value = flat.flat_id;

                        const isCurrentFlat = (targetFlatId && flat.flat_id == targetFlatId);
                        const isBooked = (flat.is_booked == 1) || (flat.booking_status === 'booked') || (flat.booking_status === 'completed');

                        if (isBooked && !isCurrentFlat) {
                            opt.disabled = true;
                            const clientLabel = flat.booked_client_name ? ` [Booked - ${flat.booked_client_name}]` : ' [Booked]';
                            opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft${clientLabel}`;
                            opt.className = 'booked-option text-danger';
                            opt.style.color = '#dc3545';
                        } else {
                            opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft` + (isCurrentFlat ? ' (Current)' : '');
                        }
                        editFlatSelect.appendChild(opt);
                    });

                    if (targetFlatId) {
                        editFlatSelect.value = targetFlatId;
                    }
                    refreshSelect2(editFlatSelect);
                })
                .catch(error => {
                    console.error('Error fetching flats (edit):', error);
                    editFlatSelect.innerHTML = '<option value="">Error loading flats</option>';
                    refreshSelect2(editFlatSelect);
                });
        }

        if (editProjectSelect) {
            $(editProjectSelect).on('change', function() {
                loadEditFlats(this.value, currentEditingFlatId);
            });
        }

        document.querySelectorAll('.editBookingBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.id;
                const projectId = this.dataset.project;
                const flatId = this.dataset.flat;
                const agreement = this.dataset.agreement;
                const date = this.dataset.date;
                const discount = this.dataset.discount;
                const approval = this.dataset.approval;
                const salesAgent = this.dataset.salesAgent;
                const attachment = this.dataset.attachment;

                currentEditingFlatId = flatId;

                document.getElementById('edit_booking_id').value = bookingId;
                document.getElementById('edit_agreement').value = agreement;
                if (document.getElementById('edit_booking_date')) {
                    document.getElementById('edit_booking_date').value = date;
                }
            
                const discountInput = document.getElementById('edit_discount');
                const lockBadge = document.getElementById('edit_discount_lock_badge');
                const lockHelp = document.getElementById('edit_discount_lock_help');
                const editAttachPreview = document.getElementById('edit_current_attachment_preview');
                const editAttachLink = document.getElementById('edit_current_attachment_link');
                const editAttachInput = document.getElementById('edit_discount_attachment');

                if (editAttachInput) editAttachInput.value = '';

                if (attachment && attachment.trim() !== '') {
                    if (editAttachLink) editAttachLink.href = '<?= $GLOBALS['base_url'] ?>/' + attachment;
                    if (editAttachPreview) editAttachPreview.classList.remove('d-none');
                } else {
                    if (editAttachPreview) editAttachPreview.classList.add('d-none');
                }

                if (discountInput) {
                    discountInput.value = discount || 0;
                    if (approval === 'approved' || approval === 'partial') {
                        discountInput.readOnly = true;
                        discountInput.classList.add('bg-light');
                        if (lockBadge) lockBadge.classList.remove('d-none');
                        if (lockHelp) lockHelp.classList.remove('d-none');
                    } else {
                        discountInput.readOnly = false;
                        discountInput.classList.remove('bg-light');
                        if (lockBadge) lockBadge.classList.add('d-none');
                        if (lockHelp) lockHelp.classList.add('d-none');
                    }
                }

                $(editProjectSelect).val(projectId);
                refreshSelect2(editProjectSelect);

                const salesAgentSelect = document.getElementById('edit_sales_agent');
                if (salesAgentSelect) {
                    $(salesAgentSelect).val(salesAgent);
                    refreshSelect2(salesAgentSelect);
                }

                loadEditFlats(projectId, flatId);
            });
        });

        // Quick Refund Modal data population & live verified payment fetch
        document.querySelectorAll('.quickRefundBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.id || '';
                const client = this.dataset.client || '—';
                const project = this.dataset.project || '—';
                const flat = this.dataset.flat || '—';
                const agreement = this.dataset.agreement || '0.00';
                const datasetPaid = parseFloat(this.dataset.paid) || 0;

                const idInput = document.getElementById('refund_booking_id');
                const clientSpan = document.getElementById('refund_client_name');
                const projectSpan = document.getElementById('refund_project_name');
                const flatSpan = document.getElementById('refund_flat_name');
                const agreementSpan = document.getElementById('refund_agreement_val');
                const reasonInput = document.getElementById('refund_reason');
                const paidInput = document.getElementById('refund_total_paid_display');
                const paidHelper = document.getElementById('refund_paid_helper');
                const expectedDateInput = document.getElementById('refund_expected_date');
                const accNameInput = document.getElementById('refund_client_bank_acc_name');
                const bankNameInput = document.getElementById('refund_client_bank_name');
                const bankBranchInput = document.getElementById('refund_client_bank_branch');
                const bankAccNoInput = document.getElementById('refund_client_bank_acc_no');
                const bankRoutingInput = document.getElementById('refund_client_bank_routing');
                const attachmentInput = document.getElementById('refund_attachment_file');

                if (idInput) idInput.value = bookingId;
                if (clientSpan) clientSpan.textContent = client;
                if (projectSpan) projectSpan.textContent = project;
                if (flatSpan) flatSpan.textContent = flat;
                if (agreementSpan) agreementSpan.textContent = agreement;
                if (reasonInput) reasonInput.value = '';
                if (expectedDateInput) expectedDateInput.value = '';

                // Set initial verified paid from dataset
                if (paidInput) {
                    paidInput.value = '৳ ' + datasetPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                if (paidHelper) {
                    paidHelper.innerHTML = datasetPaid > 0 
                        ? '<span class="text-success"><i class="fa-light fa-circle-check me-1"></i>Verified collection from client</span>'
                        : '<span class="text-muted"><i class="fa-light fa-info-circle me-1"></i>Checking verified payments...</span>';
                }

                // Pre-fill client name into Bank Account Name and reset other inputs
                if (accNameInput) accNameInput.value = (client && client !== '—') ? client : '';
                if (bankNameInput) bankNameInput.value = '';
                if (bankBranchInput) bankBranchInput.value = '';
                if (bankAccNoInput) bankAccNoInput.value = '';
                if (bankRoutingInput) bankRoutingInput.value = '';
                if (attachmentInput) attachmentInput.value = '';

                // Live AJAX fetch to guarantee accurate real-time verified payment total
                if (bookingId) {
                    fetch('<?= $GLOBALS['base_url'] ?>/get-booking-payment-summary/' + bookingId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const vPaid = parseFloat(data.total_verified_paid) || 0;
                                const pPaid = parseFloat(data.total_pending_paid) || 0;
                                if (paidInput) {
                                    paidInput.value = '৳ ' + vPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                if (paidHelper) {
                                    if (vPaid > 0) {
                                        paidHelper.innerHTML = `<span class="text-success"><i class="fa-light fa-circle-check me-1"></i>Verified: ৳ ${vPaid.toLocaleString(undefined, {minimumFractionDigits: 2})} (${data.verified_count} transaction${data.verified_count > 1 ? 's' : ''})</span>`;
                                    } else if (pPaid > 0) {
                                        paidHelper.innerHTML = `<span class="text-warning"><i class="fa-light fa-clock me-1"></i>৳ ${pPaid.toLocaleString(undefined, {minimumFractionDigits: 2})} pending Accounts verification</span>`;
                                    } else {
                                        paidHelper.innerHTML = '<span class="text-muted"><i class="fa-light fa-circle-exclamation me-1"></i>No payment recorded yet for this booking</span>';
                                    }
                                }
                            }
                        })
                        .catch(err => console.error('Failed fetching payment summary:', err));
                }
            });
        });
    });
</script>