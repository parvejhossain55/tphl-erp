

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h3>Project Other Costs</h3>
           
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                <i class="bi bi-plus-circle me-1"></i> Create Request
            </button>
        </div>
    </div>

    <section class="section">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php 
        $isApprover = in_array($roleId, [1, 3, 6, 9, 13, 15, 20, 24, 25, 30, 32]);
        $showApprovalTab = !empty($approvalRequests) || $isApprover;
        $activeTab = $isApprover ? 'approval' : 'my-requests';
        ?>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'my-requests' ? 'active' : '' ?>" id="my-requests-tab" data-bs-toggle="tab" data-bs-target="#my-requests" type="button" role="tab" aria-controls="my-requests" aria-selected="<?= $activeTab === 'my-requests' ? 'true' : 'false' ?>">My Requests</button>
            </li>
            <?php if($showApprovalTab): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'approval' ? 'active' : '' ?>" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab" aria-controls="approval" aria-selected="<?= $activeTab === 'approval' ? 'true' : 'false' ?>">Approval Requests</button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content mt-3" id="myTabContent">
            
            <!-- My Requests Tab -->
            <div class="tab-pane fade <?= $activeTab === 'my-requests' ? 'show active' : '' ?>" id="my-requests" role="tabpanel" aria-labelledby="my-requests-tab">
                <div class="card shadow-sm">
                    <div class="card-body mt-3 table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Cost Type</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($myRequests)): ?>
                                    <?php foreach ($myRequests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['rcost_type']))) ?></td>
                                            <td><?= number_format($row['request_amount'], 2) ?></td>
                                            <td>
                                                <?php if (!empty($row['creator_attachment'])): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/project_other_costs/<?= $row['creator_attachment'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> View</a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'paid' ? 'success' : 'info') ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y h:i A', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-info shadow-sm" title="View Status">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if (in_array($row['status'], ['pending', 'qc_approved'])): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/delete/<?= $row['id'] ?>" class="btn btn-sm btn-danger shadow-sm" onclick="return confirm('Are you sure you want to delete this request?');" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approval Requests Tab -->
            <?php if($showApprovalTab): ?>
            <div class="tab-pane fade <?= $activeTab === 'approval' ? 'show active' : '' ?>" id="approval" role="tabpanel" aria-labelledby="approval-tab">
                <div class="card shadow-sm">
                    <div class="card-body mt-3 table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Cost Type</th>
                                    <th>Request Amount</th>
                                    <th>Approved Amount</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($approvalRequests)): ?>
                                    <?php foreach ($approvalRequests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['rcost_type']))) ?></td>
                                            <td><?= number_format($row['request_amount'], 2) ?></td>
                                            <td><?= $row['approved_amount'] ? number_format($row['approved_amount'], 2) : '-' ?></td>
                                            <td>
                                                <?php if (!empty($row['creator_attachment'])): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/project_other_costs/<?= $row['creator_attachment'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> View</a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'paid' ? 'success' : 'info') ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                    $canApprove = false;
                                                    
                                                    $isDeptHead = false;
                                                    if ($row['dept_head_status'] === 'pending') {
                                                        $c = new mysqli('127.0.0.1','root','','tphl_db');
                                                        $r = $c->query("SELECT reporting_user_id FROM employees WHERE user_id = " . (int)$row['created_by_id'] . " AND is_deleted = 0");
                                                        if($r && $r->num_rows > 0 && $r->fetch_assoc()['reporting_user_id'] == $userId) {
                                                            $isDeptHead = true;
                                                        }
                                                    }
                                                    
                                                    if ($isDeptHead) $canApprove = true;
                                                    if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && in_array($roleId, [13, 24, 25, 1])) $canApprove = true;
                                                    if ($row['status'] == 'qc_approved' && in_array($roleId, [3, 15, 20, 22, 32, 33, 1])) $canApprove = true;
                                                    if ($row['status'] == 'management_approved' && in_array($roleId, [6, 30, 1])) $canApprove = true;
                                                    ?>
                                                    
                                                    <?php if ($canApprove): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm">
                                                            <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-info rounded-2 px-3 shadow-sm">
                                                            <i class="bi bi-eye me-1"></i>View Module Page
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                </div>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>

    <!-- Create Request Modal -->
    <div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="<?= $GLOBALS['base_url'] ?>/project-other-costs/store" method="POST" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Other Cost Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select select2" required style="width: 100%;">
                                    <option value="">Select Project</option>
                                    <?php if (!empty($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?> (<?= $project['under_construction'] ?? 'null' ?>)</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No projects available (<?= count($projects ?? []) ?> found)</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost Type <span class="text-danger">*</span></label>
                                <select name="rcost_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="electricity_bill">Electricity Bill</option>
                                    <option value="entertainment_bill">Entertainment Bill</option>
                                    <option value="internet_bill">Internet Bill</option>
                                    <option value="conveyance_bill">Conveyance Bill</option>
                                    <option value="labour_bill">Labour Bill</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Request Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="request_amount" class="form-control" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" name="creator_attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Purpose / Description <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="4" required placeholder="Enter purpose of the cost"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- DataTables setup (assuming DataTables is loaded in footer/header) -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.datatable').DataTable();
        }
    });
</script>

