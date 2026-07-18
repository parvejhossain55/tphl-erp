<?php
$page_title = "Manage Designated Champions";
?>
<div class="content-wrapper">
    <div class="content-header mb-4">
        <h2 class="content-title">Manage Designated Champions</h2>
        <p class="text-muted">Select specific employees to receive email notifications and handle tracking for their departments.</p>
    </div>

    <div class="row">
        <!-- Add Champion Form -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Add Champion</h5>
                </div>
                <div class="card-body">
                    <form action="<?= $GLOBALS['base_url'] ?>/erp-tracker/champions/add" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Department</label>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">-- Choose Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Employee</label>
                            <select name="user_id" id="user_id" class="form-select" required disabled>
                                <option value="">-- Select Department First --</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-1"></i> Add Champion
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Champions List -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Current Champions</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($champions) ?> Assigned</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Department</th>
                                    <th>Employee Name</th>
                                    <th>Email</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($champions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No designated champions added yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($champions as $c): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($c['dept_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($c['user_name']) ?></td>
                                            <td><?= htmlspecialchars($c['email'] ?: 'No email') ?></td>
                                            <td class="text-end">
                                                <a href="<?= $GLOBALS['base_url'] ?>/erp-tracker/champions/remove/<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this champion?');">
                                                    <i class="fas fa-trash"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('department_id');
    const userSelect = document.getElementById('user_id');
    const baseUrl = '<?= $GLOBALS['base_url'] ?>';

    deptSelect.addEventListener('change', function() {
        const deptId = this.value;
        userSelect.innerHTML = '<option value="">Loading...</option>';
        userSelect.disabled = true;

        if (!deptId) {
            userSelect.innerHTML = '<option value="">-- Select Department First --</option>';
            return;
        }

        fetch(`${baseUrl}/erp-tracker/api/get-dept-users/${deptId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.data.length > 0) {
                    userSelect.innerHTML = '<option value="">-- Select Employee --</option>';
                    data.data.forEach(user => {
                        const opt = document.createElement('option');
                        opt.value = user.id;
                        opt.textContent = user.full_name;
                        userSelect.appendChild(opt);
                    });
                    userSelect.disabled = false;
                } else {
                    userSelect.innerHTML = '<option value="">No active users in this department</option>';
                }
            })
            .catch(err => {
                userSelect.innerHTML = '<option value="">Error loading users</option>';
                console.error(err);
            });
    });
});
</script>
