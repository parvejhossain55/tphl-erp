<?php
$page_title = $page_title ?? 'ATS - Job Postings';
$baseUrl = $GLOBALS['base_url'];
$jobs = $jobs ?? [];
$stats = $stats ?? ['total' => 0, 'published' => 0, 'draft' => 0, 'closed' => 0, 'total_applicants' => 0];
$departments = $departments ?? [];
$filters = $filters ?? ['status' => '', 'department_id' => '', 'employment_type' => '', 'search' => ''];
$empTypes = ['Full Time', 'Part Time', 'Contract', 'Internship'];
?>

<style>
#atsJobsPage .card {
    padding: 0 !important;
    overflow: hidden;
}
#atsJobsPage .card-body {
    margin-bottom: 0 !important;
    box-shadow: none !important;
}
#atsJobsPage .jobs-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
#atsJobsPage .jobs-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 0.875rem;
}
#atsJobsPage .jobs-table tbody tr:last-child td {
    border-bottom: none;
}
#atsJobsPage .jobs-table tbody tr:hover {
    background-color: #f8fafc;
}
#atsJobsPage .job-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.95rem;
    margin-right: 12px;
}
#atsJobsPage .job-action-btn {
    width: 32px;
    height: 32px;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 8px;
    line-height: 1 !important;
}
#atsJobsPage .job-action-btn i {
    margin: 0 !important;
    line-height: 1 !important;
}
#atsJobsPage .job-status-select {
    width: 118px;
    height: 30px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 999px;
    padding: 2px 26px 2px 12px;
    cursor: pointer;
}
</style>
<div id="atsJobsPage" class="container-fluid py-3 px-4">
    <!-- Toast Notification Container -->
    <div id="jobToastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-briefcase text-primary"></i>
                Job Postings (Website Career Page)
            </h4>
            <p class="text-muted small mb-0">Manage open positions synced with the official website career page.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 ms-auto">
            <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-users"></i> Candidate Tracker
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1" onclick="openJobModal()">
                <i class="fa-solid fa-plus"></i> Post New Job
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border: 1px solid #e2e8f0 !important;">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Total Jobs</div>
                    <div class="fs-4 fw-bold text-dark" id="stat_total"><?= (int)$stats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border: 1px solid #bbf7d0 !important; background:#f0fdf4;">
                <div class="card-body py-2 px-3">
                    <div class="text-success small fw-semibold">Published (Live)</div>
                    <div class="fs-4 fw-bold text-success" id="stat_published"><?= (int)$stats['published'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border: 1px solid #fde68a !important; background:#fffbeb;">
                <div class="card-body py-2 px-3">
                    <div class="text-warning small fw-semibold">Draft</div>
                    <div class="fs-4 fw-bold text-warning" id="stat_draft"><?= (int)$stats['draft'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border: 1px solid #fecaca !important; background:#fef2f2;">
                <div class="card-body py-2 px-3">
                    <div class="text-danger small fw-semibold">Closed</div>
                    <div class="fs-4 fw-bold text-danger" id="stat_closed"><?= (int)$stats['closed'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border: 1px solid #bfdbfe !important; background:#eff6ff;">
                <div class="card-body py-2 px-3">
                    <div class="text-primary small fw-semibold">Website Applicants</div>
                    <div class="fs-4 fw-bold text-primary" id="stat_applicants"><?= (int)$stats['total_applicants'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <form id="jobsFilterForm" autocomplete="off" onsubmit="event.preventDefault(); loadJobs();" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Search job title, location, department..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="debouncedLoadJobs()">
                </div>
                <div class="col-6 col-md-2">
                    <select id="filter_department_id" name="department_id" class="form-select form-select-sm" autocomplete="off" onchange="loadJobs()">
                        <option value="" <?= ($filters['department_id'] ?? '') === '' ? 'selected' : '' ?>>All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int)$dept['id'] ?>" <?= (string)$filters['department_id'] === (string)$dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select id="filter_employment_type" name="employment_type" class="form-select form-select-sm" autocomplete="off" onchange="loadJobs()">
                        <option value="" <?= ($filters['employment_type'] ?? '') === '' ? 'selected' : '' ?>>All Types</option>
                        <?php foreach ($empTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= ($filters['employment_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select id="filter_status" name="status" class="form-select form-select-sm" autocomplete="off" onchange="loadJobs()">
                        <option value="" <?= ($filters['status'] ?? '') === '' ? 'selected' : '' ?>>All Status</option>
                        <?php foreach (['Published', 'Draft', 'Closed'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetJobFilters()">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Job Postings Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white position-relative" style="border: 1px solid #e2e8f0 !important;">
        <div class="table-responsive">
            <table class="table jobs-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Job Title &amp; Location</th>
                        <th>Department</th>
                        <th>Employment Type</th>
                        <th>Salary Range</th>
                        <th>Deadline</th>
                        <th class="text-center">Applicants</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="jobsTableBody">
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-briefcase fs-2 mb-2 d-block opacity-50"></i>
                                No job postings found. Click <strong>Post New Job</strong> to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <?php
                            $isExpired = !empty($job['deadline']) && $job['deadline'] < date('Y-m-d');
                            $statusClass = match ($job['status']) {
                                'Published' => 'bg-success-subtle text-success border border-success-subtle',
                                'Closed'    => 'bg-danger-subtle text-danger border border-danger-subtle',
                                default     => 'bg-warning-subtle text-warning border border-warning-subtle',
                            };
                            $typeClass = match ($job['employment_type']) {
                                'Part Time'  => 'bg-info-subtle text-info border border-info-subtle',
                                'Contract'   => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'Internship' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                default      => 'bg-primary-subtle text-primary border border-primary-subtle',
                            };
                            ?>
                            <tr id="job-row-<?= (int)$job['id'] ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="job-avatar">
                                            <i class="fa-solid fa-briefcase"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($job['title']) ?></div>
                                            <div class="small text-muted">
                                                <i class="fa-solid fa-location-dot me-1 text-secondary"></i><?= htmlspecialchars($job['location'] ?: 'Gulshan, Dhaka') ?>
                                                <span class="mx-1">&middot;</span>
                                                <span><?= (int)$job['vacancies'] ?> Vacancy</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark fw-medium"><?= htmlspecialchars($job['department_name'] ?: '-') ?></span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1 fw-semibold <?= $typeClass ?>">
                                        <?= htmlspecialchars($job['employment_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($job['salary_range'] ?: 'Negotiable') ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($job['deadline'])): ?>
                                        <span class="<?= $isExpired ? 'text-danger fw-semibold' : 'text-dark' ?>">
                                            <i class="fa-regular fa-calendar me-1 text-muted"></i><?= date('M d, Y', strtotime($job['deadline'])) ?>
                                        </span>
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger-subtle text-danger ms-1">Expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Open</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $baseUrl ?>/ats-tracker?reset_filter=1&position=<?= urlencode($job['title']) ?>"
                                       class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-3 py-1 fw-semibold"
                                       title="View applicants in Candidate Tracker">
                                        <i class="fa-solid fa-users me-1"></i><?= (int)$job['applicants_count'] ?>
                                    </a>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm job-status-select <?= $statusClass ?>"
                                            onchange="changeJobStatus(<?= (int)$job['id'] ?>, this.value)">
                                        <?php foreach (['Draft', 'Published', 'Closed'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $job['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary job-action-btn" onclick="editJob(<?= (int)$job['id'] ?>)" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger job-action-btn" onclick="deleteJob(<?= (int)$job['id'] ?>, '<?= htmlspecialchars(addslashes($job['title'])) ?>')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

<!-- Create / Edit Job Modal -->
<style>
#jobModal .form-control,
#jobModal .form-select {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
}
#jobModal .modal-section {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}
#jobModal .section-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}
</style>
<div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="jobModalTitle">
                    <i class="fa-solid fa-briefcase text-primary"></i>
                    <span>Post New Job</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jobForm" onsubmit="saveJob(event)">
                <input type="hidden" id="job_id" name="id" value="">
                <div class="modal-body p-4">
                    <!-- Section 1: Role & Department -->
                    <div class="modal-section">
                        <span class="section-pill" style="background: rgba(37, 99, 235, 0.12); color: #1d4ed8;">
                            <i class="fa-solid fa-id-badge"></i> 1. Role &amp; Department
                        </span>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-dark mb-1">Job Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="job_title" name="title" placeholder="e.g. Business Analysts" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">Department</label>
                                <select class="form-select form-select-sm" id="job_department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Job Logistics & Status -->
                    <div class="modal-section">
                        <span class="section-pill" style="background: rgba(16, 185, 129, 0.12); color: #047857;">
                            <i class="fa-solid fa-sliders"></i> 2. Type, Salary &amp; Status
                        </span>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Employment Type</label>
                                <select class="form-select form-select-sm" id="job_employment_type" name="employment_type">
                                    <?php foreach ($empTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Location</label>
                                <input type="text" class="form-control form-control-sm" id="job_location" name="location" value="Gulshan, Dhaka" placeholder="Gulshan, Dhaka">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Salary Range</label>
                                <input type="text" class="form-control form-control-sm" id="job_salary_range" name="salary_range" placeholder="e.g. 100k - 150k">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Application Deadline</label>
                                <input type="date" class="form-control form-control-sm" id="job_deadline" name="deadline">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Vacancies</label>
                                <input type="number" class="form-control form-control-sm" id="job_vacancies" name="vacancies" value="1" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Publish Status</label>
                                <select class="form-select form-select-sm" id="job_status" name="status">
                                    <option value="Published">Published (Live on Website)</option>
                                    <option value="Draft">Draft</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Job Description -->
                    <div>
                        <span class="section-pill" style="background: rgba(100, 116, 139, 0.12); color: #334155;">
                            <i class="fa-solid fa-file-lines"></i> 3. Job Description
                        </span>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Description &amp; Requirements</label>
                                <textarea class="form-control form-control-sm" id="job_description" name="description" rows="5" placeholder="Role overview, responsibilities, education, experience &amp; key skills..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" id="jobSubmitBtn">
                        <i class="fa-solid fa-check me-1"></i> Save Job Posting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width: 56px; height: 56px;">
                    <i class="fa-solid fa-trash-can fs-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Delete Job Posting?</h5>
                <p class="text-muted small mb-0">
                    Are you sure you want to delete <strong class="text-dark" id="deleteJobTitle"></strong>? This action cannot be undone.
                </p>
                <input type="hidden" id="delete_job_id" value="">
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-4 fw-semibold" id="confirmDeleteJobBtn" onclick="confirmDeleteJob()">
                    <i class="fa-solid fa-trash me-1"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const atsJobsBaseUrl = '<?= $baseUrl ?>';
let jobModalInstance = null;
let deleteJobModalInstance = null;
let jobSearchTimer = null;

function syncFiltersFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('department_id')) {
        document.getElementById('filter_department_id').value = '';
    }
    if (!urlParams.has('employment_type')) {
        document.getElementById('filter_employment_type').value = '';
    }
    if (!urlParams.has('status')) {
        document.getElementById('filter_status').value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => setTimeout(syncFiltersFromUrl, 0));
window.addEventListener('load', () => setTimeout(syncFiltersFromUrl, 0));
window.addEventListener('pageshow', () => setTimeout(syncFiltersFromUrl, 0));

function getJobModal() {
    if (!jobModalInstance) {
        jobModalInstance = new bootstrap.Modal(document.getElementById('jobModal'));
    }
    return jobModalInstance;
}

function getDeleteJobModal() {
    if (!deleteJobModalInstance) {
        deleteJobModalInstance = new bootstrap.Modal(document.getElementById('deleteJobModal'));
    }
    return deleteJobModalInstance;
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showJobToast(message, type = 'success') {
    const container = document.getElementById('jobToastContainer');
    const bg = type === 'success' ? 'text-bg-success' : (type === 'warning' ? 'text-bg-warning' : 'text-bg-danger');
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const el = document.createElement('div');
    el.className = `toast align-items-center ${bg} border-0 shadow-sm mb-2 show`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body small fw-semibold d-flex align-items-center gap-2">
                <i class="fa-solid ${icon}"></i>
                <span>${escapeHtml(message)}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
        </div>`;
    container.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function debouncedLoadJobs() {
    clearTimeout(jobSearchTimer);
    jobSearchTimer = setTimeout(loadJobs, 300);
}

function resetJobFilters() {
    document.getElementById('filter_q').value = '';
    document.getElementById('filter_department_id').value = '';
    document.getElementById('filter_employment_type').value = '';
    document.getElementById('filter_status').value = '';
    loadJobs();
}

function loadJobs() {
    const params = new URLSearchParams({
        ajax: '1',
        q: document.getElementById('filter_q').value.trim(),
        department_id: document.getElementById('filter_department_id').value,
        employment_type: document.getElementById('filter_employment_type').value,
        status: document.getElementById('filter_status').value,
    });

    fetch(`${atsJobsBaseUrl}/ats-jobs?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            updateKpis(res.stats);
            renderJobsTable(res.jobs);
        })
        .catch(() => showJobToast('Failed to refresh job postings.', 'danger'));
}

function updateKpis(stats) {
    if (!stats) return;
    document.getElementById('stat_total').textContent = stats.total ?? 0;
    document.getElementById('stat_published').textContent = stats.published ?? 0;
    document.getElementById('stat_draft').textContent = stats.draft ?? 0;
    document.getElementById('stat_closed').textContent = stats.closed ?? 0;
    document.getElementById('stat_applicants').textContent = stats.total_applicants ?? 0;
}

function formatDeadline(dateStr) {
    if (!dateStr) return '<span class="text-muted">Open</span>';
    const today = new Date().toISOString().slice(0, 10);
    const isExpired = dateStr < today;
    const d = new Date(dateStr + 'T00:00:00');
    const formatted = d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    return `<span class="${isExpired ? 'text-danger fw-semibold' : 'text-dark'}"><i class="fa-regular fa-calendar me-1 text-muted"></i>${formatted}</span>` +
        (isExpired ? ' <span class="badge bg-danger-subtle text-danger ms-1">Expired</span>' : '');
}

function renderJobsTable(jobs) {
    const tbody = document.getElementById('jobsTableBody');
    if (!jobs || jobs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-briefcase fs-2 mb-2 d-block opacity-50"></i>
                    No job postings found. Click <strong>Post New Job</strong> to create one.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = jobs.map(job => {
        const statusClass = job.status === 'Published'
            ? 'bg-success-subtle text-success border border-success-subtle'
            : (job.status === 'Closed'
                ? 'bg-danger-subtle text-danger border border-danger-subtle'
                : 'bg-warning-subtle text-warning border border-warning-subtle');

        const typeClass = job.employment_type === 'Part Time'
            ? 'bg-info-subtle text-info border border-info-subtle'
            : (job.employment_type === 'Contract'
                ? 'bg-warning-subtle text-warning border border-warning-subtle'
                : (job.employment_type === 'Internship'
                    ? 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                    : 'bg-primary-subtle text-primary border border-primary-subtle'));

        const statusOptions = ['Draft', 'Published', 'Closed'].map(st =>
            `<option value="${st}" ${job.status === st ? 'selected' : ''}>${st}</option>`
        ).join('');

        return `
            <tr id="job-row-${job.id}">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="job-avatar">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0">${escapeHtml(job.title)}</div>
                            <div class="small text-muted">
                                <i class="fa-solid fa-location-dot me-1 text-secondary"></i>${escapeHtml(job.location || 'Gulshan, Dhaka')}
                                <span class="mx-1">&middot;</span>
                                <span>${parseInt(job.vacancies || 1, 10)} Vacancy</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td><span class="text-dark fw-medium">${escapeHtml(job.department_name || '-')}</span></td>
                <td><span class="badge rounded-pill px-3 py-1 fw-semibold ${typeClass}">${escapeHtml(job.employment_type)}</span></td>
                <td><span class="fw-semibold text-dark">${escapeHtml(job.salary_range || 'Negotiable')}</span></td>
                <td>${formatDeadline(job.deadline)}</td>
                <td class="text-center">
                    <a href="${atsJobsBaseUrl}/ats-tracker?reset_filter=1&position=${encodeURIComponent(job.title)}"
                       class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-3 py-1 fw-semibold"
                       title="View applicants in Candidate Tracker">
                        <i class="fa-solid fa-users me-1"></i>${parseInt(job.applicants_count || 0, 10)}
                    </a>
                </td>
                <td>
                    <select class="form-select form-select-sm job-status-select ${statusClass}"
                            onchange="changeJobStatus(${job.id}, this.value)">
                        ${statusOptions}
                    </select>
                </td>
                <td class="text-end">
                    <div class="d-inline-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary job-action-btn" onclick="editJob(${job.id})" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger job-action-btn" onclick="deleteJob(${job.id}, '${escapeHtml(job.title).replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }).join('');
}

function openJobModal() {
    document.getElementById('jobForm').reset();
    document.getElementById('job_id').value = '';
    document.getElementById('job_location').value = 'Gulshan, Dhaka';
    document.getElementById('job_vacancies').value = '1';
    document.getElementById('job_status').value = 'Published';
    document.querySelector('#jobModalTitle span').textContent = 'Post New Job';
    getJobModal().show();
}

function editJob(id) {
    fetch(`${atsJobsBaseUrl}/api/ats/jobs/details/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                showJobToast(res.message || 'Failed to load job details', 'danger');
                return;
            }
            const j = res.job;
            document.getElementById('job_id').value = j.id;
            document.getElementById('job_title').value = j.title || '';
            document.getElementById('job_department_id').value = j.department_id || '';
            document.getElementById('job_employment_type').value = j.employment_type || 'Full Time';
            document.getElementById('job_location').value = j.location || 'Gulshan, Dhaka';
            document.getElementById('job_salary_range').value = j.salary_range || '';
            document.getElementById('job_deadline').value = j.deadline || '';
            document.getElementById('job_vacancies').value = j.vacancies || 1;
            document.getElementById('job_status').value = j.status || 'Draft';
            document.getElementById('job_description').value = j.description || '';
            document.querySelector('#jobModalTitle span').textContent = 'Edit Job Posting';
            getJobModal().show();
        })
        .catch(() => showJobToast('Network error loading job details.', 'danger'));
}

function saveJob(e) {
    e.preventDefault();
    const id = document.getElementById('job_id').value;
    const url = id ? `${atsJobsBaseUrl}/api/ats/jobs/update/${id}` : `${atsJobsBaseUrl}/api/ats/jobs/store`;
    const btn = document.getElementById('jobSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData(document.getElementById('jobForm'));
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            if (res.success) {
                getJobModal().hide();
                showJobToast(res.message || 'Job posting saved!');
                if (!id) {
                    resetJobFilters();
                } else {
                    loadJobs();
                }
            } else {
                showJobToast(res.message || 'Failed to save job posting.', 'danger');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            showJobToast('Network error saving job posting.', 'danger');
        });
}

function changeJobStatus(id, status) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', status);

    fetch(`${atsJobsBaseUrl}/api/ats/jobs/status`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showJobToast(res.message || `Status updated to ${status}`);
                loadJobs();
            } else {
                showJobToast(res.message || 'Failed to update status.', 'danger');
            }
        })
        .catch(() => showJobToast('Network error updating status.', 'danger'));
}

function deleteJob(id, title) {
    document.getElementById('delete_job_id').value = id;
    document.getElementById('deleteJobTitle').textContent = `"${title}"`;
    getDeleteJobModal().show();
}

function confirmDeleteJob() {
    const id = document.getElementById('delete_job_id').value;
    if (!id) return;

    const btn = document.getElementById('confirmDeleteJobBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';

    const fd = new FormData();
    fd.append('id', id);

    fetch(`${atsJobsBaseUrl}/api/ats/jobs/delete`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            if (res.success) {
                getDeleteJobModal().hide();
                showJobToast(res.message || 'Job posting deleted.');
                loadJobs();
            } else {
                showJobToast(res.message || 'Failed to delete job posting.', 'danger');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            showJobToast('Network error deleting job posting.', 'danger');
        });
}
</script>
