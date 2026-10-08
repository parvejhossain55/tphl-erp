<?php
$page_title = 'HR ATS - Dropdown Settings';
$baseUrl = $GLOBALS['base_url'];

$categories = [
    'department' => ['title' => 'Departments', 'icon' => 'fa-building', 'desc' => 'Manage departments available in ATS candidate entry'],
    'position' => ['title' => 'Positions / Roles', 'icon' => 'fa-briefcase', 'desc' => 'Manage job roles and designations for recruitment'],
    'source' => ['title' => 'Sources / Channels', 'icon' => 'fa-bullhorn', 'desc' => 'Candidate application channels (LinkedIn, Bdjobs, Walk-in, etc.)'],
    'invitation_status' => ['title' => 'Invitation Call Statuses', 'icon' => 'fa-phone-volume', 'desc' => 'Standardized outcomes when calling candidates (Yes, Didn\'t Pick, etc.)'],
    'interview_location' => ['title' => 'Interview Locations / Sites', 'icon' => 'fa-location-dot', 'desc' => 'Office branches, project sites, or online venues'],
];
?>

<div class="container-fluid py-3 px-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-sliders text-primary"></i>
                ATS Dropdown & Master Data Management
            </h4>
            <p class="text-muted small mb-0">Self-service screen for HR to add, edit, or retire dropdown choices without IT requests.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Candidate Tracker
            </a>
        </div>
    </div>

    <!-- Category Nav Tabs -->
    <ul class="nav nav-pills bg-white p-2 rounded-3 shadow-sm mb-4 gap-1" id="dropdownTabs" role="tablist">
        <?php 
        $first = true;
        foreach ($categories as $catKey => $catMeta): 
        ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm py-2 px-3 fw-semibold <?= $first ? 'active' : '' ?>" 
                        id="tab-btn-<?= $catKey ?>" 
                        data-bs-toggle="pill" 
                        data-bs-target="#tab-pane-<?= $catKey ?>" 
                        type="button" role="tab">
                    <i class="fa-solid <?= $catMeta['icon'] ?> me-1"></i> <?= $catMeta['title'] ?>
                    <span class="badge rounded-pill ms-1" style="background: #e2e8f0; color: #1e293b; font-size: 0.7rem;">
                        <?= count($groupedOptions[$catKey] ?? []) ?>
                    </span>
                </button>
            </li>
        <?php 
            $first = false;
        endforeach; 
        ?>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="dropdownTabsContent">
        <?php 
        $first = true;
        foreach ($categories as $catKey => $catMeta): 
            $optionsList = $groupedOptions[$catKey] ?? [];
        ?>
            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-pane-<?= $catKey ?>" role="tabpanel">
                <div class="row ">
                    <!-- Add Option Card -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-light py-3 border-0">
                                <h6 class="fw-bold my-0 text-dark">
                                    <i class="fa-solid fa-plus-circle text-primary me-1"></i> Add New <?= $catMeta['title'] ?>
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="text-muted small mb-3"><?= $catMeta['desc'] ?></p>
                                <form onsubmit="handleSaveOption(event, '<?= $catKey ?>')">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Option Name / Title <span class="text-danger">*</span></label>
                                        <input type="text" id="input_val_<?= $catKey ?>" class="form-control form-control-sm" placeholder="e.g. New <?= $catMeta['title'] ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Display Order</label>
                                        <input type="number" id="input_order_<?= $catKey ?>" class="form-control form-control-sm" value="0" min="0">
                                        <div class="form-text small" style="font-size: 0.72rem;">Lower numbers appear first in dropdowns.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm">
                                        <i class="fa-solid fa-check me-1"></i> Save to Dropdown
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Options Table -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="card-header bg-light py-3 border-0 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold my-0 text-dark">
                                    Existing <?= $catMeta['title'] ?> List
                                </h6>
                                <span class="badge rounded-pill px-3 py-1" style="background: #e0f2fe; color: #0369a1; font-weight: 600; font-size: 0.75rem;">
                                    <?= count($optionsList) ?> Available
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light text-secondary small" style="text-transform: uppercase;">
                                        <tr>
                                            <th style="width: 50px;">Order</th>
                                            <th>Option Value</th>
                                            <th style="width: 120px;">Status</th>
                                            <th class="text-end px-3" style="width: 140px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($optionsList)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    No options configured yet. Add your first option on the left.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($optionsList as $opt): ?>
                                                <tr>
                                                    <td class="text-muted fw-semibold"><?= (int)$opt['display_order'] ?></td>
                                                    <td class="fw-bold text-dark">
                                                        <?= htmlspecialchars($opt['option_value']) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($opt['is_active']): ?>
                                                            <span class="badge rounded-pill px-3 py-1" style="background: #dcfce7; color: #15803d; font-weight: 600; font-size: 0.75rem; border: 1px solid #bbf7d0;">
                                                                <i class="fa-solid fa-circle-check me-1"></i> Active
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge rounded-pill px-3 py-1" style="background: #f1f5f9; color: #64748b; font-weight: 600; font-size: 0.75rem; border: 1px solid #e2e8f0;">
                                                                <i class="fa-solid fa-ban me-1"></i> Retired
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end px-3">
                                                        <button type="button" 
                                                                class="btn btn-sm <?= $opt['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> py-0 px-2"
                                                                style="font-size: 0.75rem;"
                                                                onclick="toggleOptionStatus(<?= $opt['id'] ?>)">
                                                            <?php if ($opt['is_active']): ?>
                                                                <i class="fa-solid fa-eye-slash me-1"></i> Retire
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-eye me-1"></i> Activate
                                                            <?php endif; ?>
                                                        </button>
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
        <?php 
            $first = false;
        endforeach; 
        ?>
    </div>
</div>

<script>
function handleSaveOption(e, category) {
    e.preventDefault();
    const valInput = document.getElementById(`input_val_${category}`);
    const orderInput = document.getElementById(`input_order_${category}`);

    const val = valInput.value.trim();
    const order = orderInput.value.trim();

    if (!val) {
        Swal.fire('Error', 'Please enter an option name.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('category', category);
    formData.append('option_value', val);
    formData.append('display_order', order);

    fetch('<?= $baseUrl ?>/api/ats/dropdown-save', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: data.message,
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Could not save option.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue.', 'error');
    });
}

function toggleOptionStatus(id) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('<?= $baseUrl ?>/api/ats/dropdown-toggle', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            Swal.fire('Error', data.message || 'Could not toggle status', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}
</script>
