<?php
/**
 * CED Project Polls View
 * TPHL-ERP - Client Engagement Department
 */
$page_title = "Project Polls";
$page = 'Project Polls';
$show_breadcrumb = true;

$projectsJson = json_encode($projects ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<style>
    .project-polls-container {
        padding-bottom: 40px;
    }

    .theme-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: #e8f5e9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0d5c3a;
        font-size: 22px;
    }

    .card-custom {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 24px;
        margin-bottom: 24px;
    }

    .card-title-divider {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Custom Searchable Project Select Dropdown */
    .project-select-trigger {
        width: 100%;
        background-color: #ffffff;
        border: 1px solid #dcdfe4;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .project-select-trigger:hover,
    .project-select-trigger:focus {
        border-color: #0d5c3a;
        box-shadow: 0 0 0 3px rgba(13, 92, 58, 0.1);
    }

    .project-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        margin-top: 6px;
        padding: 10px;
        display: none;
    }

    .project-dropdown-menu.show {
        display: block;
    }

    .project-search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        margin-bottom: 8px;
        outline: none;
    }

    .project-search-input:focus {
        border-color: #0d5c3a;
    }

    .project-options-list {
        max-height: 250px;
        overflow-y: auto;
    }

    .project-option-item {
        padding: 9px 12px;
        font-size: 13.5px;
        color: #334155;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.15s;
    }

    .project-option-item:hover {
        background-color: #f1f5f9;
        color: #0d5c3a;
        font-weight: 500;
    }

    .project-option-item.selected {
        background-color: #e8f5e9;
        color: #0d5c3a;
        font-weight: 600;
    }

    /* Option Row styling */
    .poll-option-row {
        position: relative;
    }

    .poll-option-row .btn-remove-option {
        border-color: #e2e8f0;
        color: #ef4444;
        background-color: #ffffff;
    }

    .poll-option-row .btn-remove-option:hover {
        background-color: #fee2e2;
        border-color: #fca5a5;
    }

    .btn-add-option-dashed {
        border: 1.5px dashed #0d5c3a;
        color: #0d5c3a;
        background-color: #f0fdf4;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .btn-add-option-dashed:hover {
        background-color: #0d5c3a;
        color: #ffffff;
    }

    /* Upload Box */
    .attachment-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background-color: #f8fafc;
        padding: 20px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .attachment-dropzone:hover {
        border-color: #0d5c3a;
        background-color: #f0fdf4;
    }

    .upload-link-text {
        color: #0d5c3a;
        font-weight: 600;
        font-size: 14px;
        text-decoration: underline;
    }

    /* Submit Button */
    .btn-post-poll {
        background-color: #0d5c3a !important;
        border-color: #0d5c3a !important;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 15px;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-post-poll:hover {
        background-color: #09472c !important;
        border-color: #09472c !important;
        box-shadow: 0 4px 12px rgba(13, 92, 58, 0.25);
    }

    /* Poll Card Styling */
    .poll-feed-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .poll-feed-card:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .poll-thumbnail-wrap {
        position: relative;
        display: inline-block;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        max-width: 260px;
        cursor: pointer;
    }

    .poll-thumbnail-wrap img {
        width: 100%;
        height: 140px;
        object-fit: cover;
        transition: transform 0.2s ease;
    }

    .poll-thumbnail-wrap:hover img {
        transform: scale(1.04);
    }

    .poll-thumbnail-badge {
        position: absolute;
        bottom: 6px;
        right: 6px;
        background: rgba(0, 0, 0, 0.65);
        color: #ffffff;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* Results section inside card */
    .poll-results-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        margin-top: 14px;
    }

    .poll-option-result-item {
        margin-bottom: 12px;
    }

    .poll-option-result-item:last-child {
        margin-bottom: 0;
    }

    .poll-progress-custom {
        height: 9px;
        background-color: #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
    }

    .poll-progress-bar-custom {
        background-color: #0d5c3a;
        transition: width 0.6s ease;
    }

    .poll-filter-pill {
        font-size: 12.5px;
        padding: 5px 12px;
        border-radius: 20px;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .poll-filter-pill:hover,
    .poll-filter-pill.active {
        background-color: #0d5c3a;
        border-color: #0d5c3a;
        color: #ffffff;
    }

    .poll-voters-clickable {
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .poll-voters-clickable:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12) !important;
        filter: brightness(0.96);
    }

    .empty-state-text {
        font-size: 14.5px;
        color: #64748b;
    }
</style>

<div class="project-polls-container">

    <!-- ══ HEADER ══ -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="theme-header-icon">
                <i class="fa-solid fa-square-poll-vertical"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0" style="color: #0d5c3a; letter-spacing: -0.5px;">Project Polls</h3>
                <p class="text-muted mb-0 small">Create project-wise polls and monitor voting results in real time</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Refresh Button -->
            <button type="button" class="btn btn-sm btn-light border px-3 shadow-sm text-secondary" onclick="refreshCurrentProjectPolls()" title="Refresh Polls">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ══ MAIN 2-COLUMN GRID ══ -->
    <div class="row">

        <!-- ── LEFT COLUMN (COL-LG-4) ── -->
        <div class="col-lg-4 col-md-5">

            <!-- Card 1: Select Project -->
            <div class="card-custom">
                <div class="card-title-divider">Select Project</div>

                <div class="position-relative" id="projectSelectContainer">
                    <div class="project-select-trigger" id="projectSelectTrigger" onclick="toggleProjectDropdown()">
                        <span id="selectedProjectDisplay" class="text-truncate">Loading projects...</span>
                        <i class="fa-solid fa-chevron-down text-muted ms-2" style="font-size: 12px;"></i>
                    </div>

                    <!-- Dropdown Menu -->
                    <div class="project-dropdown-menu" id="projectDropdownMenu">
                        <input type="text" class="project-search-input" id="projectSearchInput" placeholder="Search project by name or ID..." onkeyup="filterProjectOptions()">
                        <div class="project-options-list" id="projectOptionsList">
                            <div class="text-center py-3 text-muted small">Loading projects...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Create New Poll -->
            <div class="card-custom">
                <div class="card-title-divider">Create New Poll</div>

                <form id="createPollForm" onsubmit="event.preventDefault(); handlePostPoll();">
                    <!-- Project Scope Label -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Target Project</label>
                        <div class="p-2 px-3 bg-light border rounded small fw-semibold text-truncate text-success" id="formTargetProjectDisplay">
                            All Projects
                        </div>
                    </div>

                    <!-- Poll Title / Question * -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Question / Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pollTitleInput" placeholder="e.g. Which rooftop garden design do you prefer?" required autocomplete="off">
                    </div>

                    <!-- Poll Description -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Description (Optional)</label>
                        <textarea class="form-control" id="pollDescInput" rows="3" placeholder="Provide extra context or instructions..."></textarea>
                    </div>

                    <!-- Poll Options * -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1 d-flex justify-content-between">
                            <span>Poll Options <span class="text-danger">*</span> <small class="text-muted">(Min 2)</small></span>
                            <span class="text-muted small" id="optionsCountDisplay">2 options</span>
                        </label>
                        
                        <div id="pollOptionsContainer" class="d-flex flex-column gap-2 mb-2">
                            <!-- Option 1 -->
                            <div class="input-group poll-option-row">
                                <span class="input-group-text bg-light text-muted small fw-bold" style="width: 38px; justify-content: center;">1</span>
                                <input type="text" class="form-control poll-option-input" placeholder="Option 1 (e.g. Zen Garden)" required>
                            </div>
                            <!-- Option 2 -->
                            <div class="input-group poll-option-row">
                                <span class="input-group-text bg-light text-muted small fw-bold" style="width: 38px; justify-content: center;">2</span>
                                <input type="text" class="form-control poll-option-input" placeholder="Option 2 (e.g. Coffee Corner)" required>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-add-option-dashed w-100 py-2 d-flex align-items-center justify-content-center gap-1" onclick="addPollOption()">
                            <i class="fa-solid fa-circle-plus"></i> Add Option
                        </button>
                    </div>

                    <!-- Dates Row: Start Date & End Date -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small fw-semibold mb-1">Start Date</label>
                            <input type="date" class="form-control form-control-sm" id="pollStartDateInput" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small fw-semibold mb-1">End Date (Deadline)</label>
                            <input type="date" class="form-control form-control-sm" id="pollEndDateInput">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Status</label>
                        <select class="form-select form-select-sm" id="pollStatusInput">
                            <option value="active" selected>Active (Open for Voting)</option>
                            <option value="draft">Draft (Hidden from Clients)</option>
                            <option value="closed">Closed (Voting Ended)</option>
                        </select>
                    </div>

                    <!-- Attachment (Image) -->
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-semibold mb-1">Banner Image (Optional)</label>
                        
                        <!-- Upload Dropzone -->
                        <div class="attachment-dropzone" id="attachmentDropzone" onclick="document.getElementById('pollImageFile').click()">
                            <i class="fa-regular fa-image fa-2x text-secondary mb-2"></i>
                            <div>
                                <span class="upload-link-text">Upload banner image</span>
                            </div>
                            <small class="text-muted d-block mt-1">PNG, JPG, WEBP up to 10MB</small>
                            <input type="file" id="pollImageFile" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif" style="display:none;" onchange="handleImageSelected(event)">
                        </div>

                        <!-- Image Preview Box -->
                        <div id="imagePreviewContainer" class="mt-2 p-2 bg-light border rounded position-relative" style="display:none;">
                            <div class="d-flex align-items-center gap-2">
                                <img id="imagePreviewThumb" src="" alt="Preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                <div class="flex-grow-1 text-truncate">
                                    <div class="small fw-semibold text-truncate" id="imagePreviewName">filename.jpg</div>
                                    <div class="text-muted" style="font-size: 11px;" id="imagePreviewSize">0 KB</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeSelectedImage()" title="Remove image">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Post Poll Button -->
                    <button type="submit" id="postPollBtn" class="btn w-100 btn-post-poll d-flex align-items-center justify-content-center gap-2 shadow-sm">
                        <span id="postBtnText">Create Poll</span>
                        <i class="fa-solid fa-square-plus ms-1" id="postBtnIcon"></i>
                        <span class="spinner-border spinner-border-sm d-none" id="postBtnSpinner" role="status"></span>
                    </button>
                </form>
            </div>

        </div>

        <!-- ── RIGHT COLUMN (COL-LG-8) ── -->
        <div class="col-lg-8 col-md-7">
            <div class="card-custom h-100">
                <div class="card-title-divider d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span id="rightSectionHeader">Polls for: <span id="headerProjectName" class="text-success fw-bold">All Projects</span></span>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Filter Pills -->
                        <div class="d-flex gap-1" id="pollFilterGroup">
                            <span class="poll-filter-pill active" onclick="applyPollFilter('all')">All</span>
                            <span class="poll-filter-pill" onclick="applyPollFilter('active')">Active</span>
                            <span class="poll-filter-pill" onclick="applyPollFilter('closed')">Closed</span>
                        </div>
                        <span id="totalPollsCountBadge" class="badge bg-light text-secondary border">0 Polls</span>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="pollsLoadingState" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="text-muted small mt-2">Loading polls & results...</p>
                </div>

                <!-- Empty State -->
                <div id="pollsEmptyState" class="text-center py-5">
                    <div class="text-muted mb-2"><i class="fa-solid fa-square-poll-vertical fa-3x" style="color: #cbd5e1;"></i></div>
                    <p class="empty-state-text mb-1" id="emptyStateMessage">No polls found for the selected project.</p>
                    <p class="text-muted small">Use the form on the left to launch a new poll.</p>
                </div>

                <!-- Polls Feed List -->
                <div id="pollsListFeed" style="display:none;">
                    <!-- Injected via JavaScript -->
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ══ MODAL: FULL IMAGE PREVIEW ══ -->
<div class="modal fade" id="fullImageModal" tabindex="-1" aria-labelledby="fullImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 position-relative text-center">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="z-index: 1060; background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 8px;"></button>
                <img id="fullImageModalImg" src="" alt="Poll Banner" class="img-fluid rounded shadow" style="max-height: 85vh; object-fit: contain; background: #fff;">
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: VOTERS LIST ══ -->
<div class="modal fade" id="votersListModal" tabindex="-1" aria-labelledby="votersListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom py-3" style="background-color: #f8fafc;">
                <div class="d-flex align-items-center gap-2">
                    <div class="theme-header-icon" style="width: 38px; height: 38px; font-size: 18px;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="votersListModalLabel" style="font-size: 16px;">
                            Voters List
                            <span class="badge bg-success-subtle text-success border border-success-subtle small fw-semibold" id="modalVotersCountBadge">0 Voters</span>
                        </h5>
                        <div class="text-muted small" id="modalPollQuestionSubtitle">Poll Question</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Filter Badge info if filtered by option -->
                <div id="modalOptionFilterNotice" class="alert alert-light border py-2 px-3 small d-flex align-items-center justify-content-between mb-3" style="display: none;">
                    <div>
                        <span class="text-muted">Filtered by Choice:</span> 
                        <strong class="text-success ms-1" id="modalFilteredOptionName">Option</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-secondary" onclick="viewAllPollVoters()" title="View All Voters">
                        View All Voters &rarr;
                    </button>
                </div>

                <!-- Search input -->
                <div class="mb-3 position-relative">
                    <input type="text" class="form-control form-control-sm ps-4" id="voterSearchInput" placeholder="Search by client name, flat/unit, phone..." onkeyup="filterVotersList()">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-2 text-muted small"></i>
                </div>

                <!-- Loading state -->
                <div id="votersModalLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Loading voters list...</p>
                </div>

                <!-- Empty state -->
                <div id="votersModalEmpty" class="text-center py-4" style="display: none;">
                    <div class="text-muted mb-1"><i class="fa-regular fa-folder-open fa-2x" style="color: #cbd5e1;"></i></div>
                    <p class="text-muted small mb-0" id="votersModalEmptyText">No votes recorded yet for this selection.</p>
                </div>

                <!-- Voters Table -->
                <div class="table-responsive" id="votersTableContainer" style="display: none; max-height: 380px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th>Client / User Name</th>
                                <th>Unit / Flat No</th>
                                <th>Selected Option</th>
                                <th>Voted At</th>
                            </tr>
                        </thead>
                        <tbody id="votersTableBody">
                            <!-- Injected via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-2 bg-light">
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Configuration Variables ──────────────────────────────────────
    const BASE_URL = "<?= rtrim($GLOBALS['base_url'] ?? '', '/') ?>";
    const API_BASE_URL = BASE_URL + "/api/polls";

    // State Variables
    let allProjects = <?= $projectsJson ?> || [];
    let selectedProject = null; // null = All Projects, or { id, name }
    let rawPolls = [];
    let currentFilter = 'all';
    let selectedImageFile = null;

    // Elements
    const projectSelectTrigger = document.getElementById('projectSelectTrigger');
    const projectDropdownMenu = document.getElementById('projectDropdownMenu');
    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectOptionsList = document.getElementById('projectOptionsList');
    const selectedProjectDisplay = document.getElementById('selectedProjectDisplay');
    const formTargetProjectDisplay = document.getElementById('formTargetProjectDisplay');
    const rightSectionHeader = document.getElementById('rightSectionHeader');
    const totalPollsCountBadge = document.getElementById('totalPollsCountBadge');
    const pollsLoadingState = document.getElementById('pollsLoadingState');
    const pollsEmptyState = document.getElementById('pollsEmptyState');
    const pollsListFeed = document.getElementById('pollsListFeed');
    const emptyStateMessage = document.getElementById('emptyStateMessage');
    const pollOptionsContainer = document.getElementById('pollOptionsContainer');
    const optionsCountDisplay = document.getElementById('optionsCountDisplay');

    // ── Initialization ──────────────────────────────────────────────
    document.addEventListener("DOMContentLoaded", function() {
        initProjectDropdown();

        // Close project dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('projectSelectContainer');
            if (container && !container.contains(e.target)) {
                closeProjectDropdown();
            }
        });

        // Load initial polls (defaults to all projects)
        loadPolls();
    });

    // ── Project Dropdown Logic ──────────────────────────────────────
    function initProjectDropdown() {
        renderProjectOptions(allProjects);
        // Default to "All Projects"
        selectAllProjectsOption();
    }

    window.toggleProjectDropdown = function() {
        const isShown = projectDropdownMenu.classList.contains('show');
        if (isShown) {
            closeProjectDropdown();
        } else {
            openProjectDropdown();
        }
    };

    function openProjectDropdown() {
        projectDropdownMenu.classList.add('show');
        projectSearchInput.value = '';
        filterProjectOptions();
        setTimeout(() => projectSearchInput.focus(), 50);
    }

    function closeProjectDropdown() {
        projectDropdownMenu.classList.remove('show');
    }

    window.filterProjectOptions = function() {
        const query = projectSearchInput.value.toLowerCase().trim();
        const filtered = allProjects.filter(p => {
            const name = (p.name || '').toLowerCase();
            const id = String(p.id || '');
            return name.includes(query) || id.includes(query);
        });
        renderProjectOptions(filtered);
    };

    function renderProjectOptions(projects) {
        projectOptionsList.innerHTML = '';

        // Add "All Projects" option at top
        const allItem = document.createElement('div');
        const isAllSelected = selectedProject === null;
        allItem.className = 'project-option-item' + (isAllSelected ? ' selected' : '');
        allItem.innerHTML = `
            <span class="text-truncate"><i class="fa-solid fa-globe me-1 text-muted"></i> All Projects</span>
            ${isAllSelected ? '<i class="fa-solid fa-check text-success small"></i>' : ''}
        `;
        allItem.addEventListener('click', function() {
            selectAllProjectsOption();
            closeProjectDropdown();
        });
        projectOptionsList.appendChild(allItem);

        if (projects.length === 0) {
            const emptyNotice = document.createElement('div');
            emptyNotice.className = 'text-center py-2 text-muted small';
            emptyNotice.innerText = 'No matching projects found';
            projectOptionsList.appendChild(emptyNotice);
            return;
        }

        projects.forEach(project => {
            const item = document.createElement('div');
            const isSelected = selectedProject && selectedProject.id === project.id;
            item.className = 'project-option-item' + (isSelected ? ' selected' : '');
            item.innerHTML = `
                <span class="text-truncate">${escapeHtml(project.name || 'Project #' + project.id)} (ID: ${project.id})</span>
                ${isSelected ? '<i class="fa-solid fa-check text-success small"></i>' : ''}
            `;
            item.addEventListener('click', function() {
                selectProject(project);
                closeProjectDropdown();
            });
            projectOptionsList.appendChild(item);
        });
    }

    function selectAllProjectsOption() {
        selectedProject = null;
        selectedProjectDisplay.innerHTML = '<i class="fa-solid fa-globe me-1 text-success"></i> All Projects';
        formTargetProjectDisplay.innerHTML = '<i class="fa-solid fa-globe me-1 text-success"></i> All Projects (General)';
        rightSectionHeader.innerHTML = 'Polls for: <span class="text-success fw-bold">All Projects</span>';
        renderProjectOptions(allProjects);
        loadPolls();
    }

    function selectProject(project) {
        selectedProject = project;
        selectedProjectDisplay.innerText = `${project.name || 'Project #' + project.id} (ID: ${project.id})`;
        selectedProjectDisplay.title = `${project.name || 'Project #' + project.id} (ID: ${project.id})`;
        formTargetProjectDisplay.innerText = `${project.name || 'Project #' + project.id} (ID: ${project.id})`;
        rightSectionHeader.innerHTML = `Polls for: <strong class="text-success">${escapeHtml(project.name || 'Project')}</strong> (ID: ${project.id})`;
        renderProjectOptions(allProjects);
        loadPolls(project.id);
    }

    // ── Fetch Polls API ──────────────────────────────────────────────
    async function loadPolls(projectId = null) {
        pollsLoadingState.style.display = 'block';
        pollsEmptyState.style.display = 'none';
        pollsListFeed.style.display = 'none';

        try {
            let url = `${API_BASE_URL}?is_admin=1`;
            if (projectId) {
                url += `&project_id=${projectId}`;
            }

            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const resData = await response.json();
            rawPolls = Array.isArray(resData.data) ? resData.data : [];

            renderPollsFeed();

        } catch (error) {
            console.error("Failed to load polls:", error);
            pollsLoadingState.style.display = 'none';
            pollsEmptyState.style.display = 'block';
            emptyStateMessage.innerText = "Error loading polls from server.";
            totalPollsCountBadge.innerText = "0 Polls";
        }
    }

    window.refreshCurrentProjectPolls = function() {
        loadPolls(selectedProject ? selectedProject.id : null);
    };

    // ── Filter Polls ────────────────────────────────────────────────
    window.applyPollFilter = function(filter) {
        currentFilter = filter;
        document.querySelectorAll('#pollFilterGroup .poll-filter-pill').forEach(pill => {
            pill.classList.remove('active');
        });
        const activePill = Array.from(document.querySelectorAll('#pollFilterGroup .poll-filter-pill')).find(p => p.innerText.toLowerCase() === filter);
        if (activePill) activePill.classList.add('active');

        renderPollsFeed();
    };

    function renderPollsFeed() {
        pollsLoadingState.style.display = 'none';

        let filtered = rawPolls;
        if (currentFilter === 'active') {
            filtered = rawPolls.filter(p => p.status === 'active' && !p.is_expired);
        } else if (currentFilter === 'closed') {
            filtered = rawPolls.filter(p => p.status === 'closed' || p.is_expired);
        }

        totalPollsCountBadge.innerText = `${filtered.length} Poll${filtered.length === 1 ? '' : 's'}`;

        if (filtered.length === 0) {
            pollsEmptyState.style.display = 'block';
            pollsListFeed.style.display = 'none';
            emptyStateMessage.innerText = rawPolls.length > 0 
                ? "No polls match the selected filter." 
                : (selectedProject 
                    ? `No polls have been created for ${selectedProject.name || 'this project'} yet.` 
                    : "No polls available yet.");
            return;
        }

        pollsEmptyState.style.display = 'none';
        pollsListFeed.innerHTML = '';

        filtered.forEach(poll => {
            const card = document.createElement('div');
            card.className = 'poll-feed-card';

            // Status Badge
            let statusBadge = '';
            if (poll.status === 'draft') {
                statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-pencil me-1"></i>Draft</span>';
            } else if (poll.is_expired) {
                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-regular fa-clock me-1"></i>Expired</span>';
            } else if (poll.status === 'closed') {
                statusBadge = '<span class="badge bg-secondary-subtle text-secondary border"><i class="fa-solid fa-lock me-1"></i>Closed</span>';
            } else {
                statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-circle-check me-1"></i>Active</span>';
            }

            // Project Badge
            const projectName = poll.project_name ? escapeHtml(poll.project_name) : 'All Projects';
            const projectBadge = `<span class="badge bg-light text-dark border"><i class="fa-solid fa-building me-1 text-secondary"></i>${projectName}</span>`;

            // Dates string
            let datesHtml = '';
            if (poll.start_date || poll.end_date) {
                datesHtml = `<span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i>${poll.start_date || 'N/A'} &rarr; ${poll.end_date || 'Ongoing'}</span>`;
            }

            // Image Thumbnail
            let imageHtml = '';
            if (poll.image_url) {
                imageHtml = `
                    <div class="mt-3">
                        <div class="poll-thumbnail-wrap" onclick="viewFullImage('${poll.image_url}')">
                            <img src="${poll.image_url}" alt="Poll Banner" loading="lazy">
                            <span class="poll-thumbnail-badge"><i class="fa-solid fa-expand me-1"></i>View Banner</span>
                        </div>
                    </div>
                `;
            }

            // Voting Options & Results
            const totalVotes = poll.total_votes || 0;
            let optionsHtml = '';

            if (poll.options && poll.options.length > 0) {
                poll.options.forEach(opt => {
                    const pct = opt.percentage !== undefined ? opt.percentage : (totalVotes > 0 ? Math.round(((opt.votes_count || 0) / totalVotes) * 100) : 0);
                    const count = opt.votes_count || 0;

                    optionsHtml += `
                        <div class="poll-option-result-item">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark text-truncate me-2">
                                    <i class="fa-regular fa-circle-dot text-muted me-1"></i> ${escapeHtml(opt.option_text)}
                                </span>
                                <span class="badge bg-white text-secondary border flex-shrink-0 poll-voters-clickable" 
                                      onclick="openVotersModal(${poll.id}, ${opt.id})" 
                                      title="Click to view voters for this option">
                                    <strong>${count}</strong> ${count === 1 ? 'vote' : 'votes'} (${pct}%)
                                    <i class="fa-solid fa-users ms-1 text-success" style="font-size: 10px;"></i>
                                </span>
                            </div>
                            <div class="poll-progress-custom">
                                <div class="poll-progress-bar-custom h-100" style="width: ${pct}%;"></div>
                            </div>
                        </div>
                    `;
                });
            }

            const toggleStatusBtnText = poll.status === 'active' ? 'Close Poll' : 'Activate Poll';
            const toggleStatusBtnIcon = poll.status === 'active' ? 'fa-lock' : 'fa-circle-check';
            const nextStatus = poll.status === 'active' ? 'closed' : 'active';

            card.innerHTML = `
                <!-- Top Row: Meta Badges & Dates -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        ${projectBadge}
                        ${statusBadge}
                    </div>
                    ${datesHtml}
                </div>

                <!-- Poll Title & Description -->
                <h5 class="fw-bold text-dark mt-2 mb-1" style="font-size: 16.5px; line-height: 1.4;">${escapeHtml(poll.title)}</h5>
                ${poll.description ? `<p class="text-secondary small mt-1 mb-2" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(poll.description)}</p>` : ''}

                <!-- Banner Image -->
                ${imageHtml}

                <!-- Voting Results Box -->
                <div class="poll-results-box">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="fw-bold small text-dark">
                            <i class="fa-solid fa-square-poll-vertical text-success me-1"></i> Voting Results
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 poll-voters-clickable" 
                              onclick="openVotersModal(${poll.id}, null)" 
                              title="Click to view all voters">
                            <i class="fa-solid fa-users me-1"></i> <strong>${totalVotes}</strong> Total Votes
                        </span>
                    </div>

                    ${optionsHtml}
                </div>

                <!-- Footer Actions -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
                    <small class="text-muted" style="font-size: 11.5px;">
                        <i class="fa-regular fa-clock me-1"></i> Created: ${formatDate(poll.created_at)}
                    </small>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border px-2 text-secondary" onclick="togglePollStatus(${poll.id}, '${nextStatus}')" title="${toggleStatusBtnText}">
                            <i class="fa-solid ${toggleStatusBtnIcon} me-1"></i> ${toggleStatusBtnText}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger border px-2" onclick="confirmDeletePoll(${poll.id})" title="Delete Poll">
                            <i class="fa-regular fa-trash-can me-1"></i> Delete
                        </button>
                    </div>
                </div>
            `;

            pollsListFeed.appendChild(card);
        });

        pollsListFeed.style.display = 'block';
    }

    // ── Dynamic Option Inputs in Form ────────────────────────────────
    window.addPollOption = function() {
        const rows = pollOptionsContainer.querySelectorAll('.poll-option-row');
        if (rows.length >= 10) {
            showWarningNotification("You can add up to 10 options maximum.");
            return;
        }

        const nextNum = rows.length + 1;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'input-group poll-option-row';
        rowDiv.innerHTML = `
            <span class="input-group-text bg-light text-muted small fw-bold" style="width: 38px; justify-content: center;">${nextNum}</span>
            <input type="text" class="form-control poll-option-input" placeholder="Option ${nextNum}" required>
            <button type="button" class="btn btn-outline-danger btn-remove-option" onclick="removePollOption(this)" title="Remove Option">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        `;

        pollOptionsContainer.appendChild(rowDiv);
        updateOptionsIndices();
    };

    window.removePollOption = function(btn) {
        const rows = pollOptionsContainer.querySelectorAll('.poll-option-row');
        if (rows.length <= 2) {
            showWarningNotification("A poll requires at least 2 options.");
            return;
        }

        const row = btn.closest('.poll-option-row');
        if (row) {
            row.remove();
            updateOptionsIndices();
        }
    };

    function updateOptionsIndices() {
        const rows = pollOptionsContainer.querySelectorAll('.poll-option-row');
        rows.forEach((row, idx) => {
            const numSpan = row.querySelector('.input-group-text');
            if (numSpan) numSpan.innerText = idx + 1;
            const input = row.querySelector('.poll-option-input');
            if (input && !input.value) input.placeholder = `Option ${idx + 1}`;
        });
        optionsCountDisplay.innerText = `${rows.length} options`;
    }

    // ── Image Handling ──────────────────────────────────────────────
    window.handleImageSelected = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            showErrorNotification("Image size exceeds the 10MB limit. Please choose a smaller file.");
            e.target.value = '';
            return;
        }

        selectedImageFile = file;
        document.getElementById('imagePreviewName').innerText = file.name;
        document.getElementById('imagePreviewSize').innerText = formatFileSize(file.size);

        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imagePreviewThumb').src = evt.target.result;
            document.getElementById('imagePreviewContainer').style.display = 'block';
            document.getElementById('attachmentDropzone').style.display = 'none';
        };
        reader.readAsDataURL(file);
    };

    window.removeSelectedImage = function() {
        selectedImageFile = null;
        document.getElementById('pollImageFile').value = '';
        document.getElementById('imagePreviewThumb').src = '';
        document.getElementById('imagePreviewContainer').style.display = 'none';
        document.getElementById('attachmentDropzone').style.display = 'block';
    };

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    window.viewFullImage = function(url) {
        const modalImg = document.getElementById('fullImageModalImg');
        modalImg.src = url;
        const modal = new bootstrap.Modal(document.getElementById('fullImageModal'));
        modal.show();
    };

    // ── Post Poll Action ────────────────────────────────────────────
    window.handlePostPoll = async function() {
        const title = document.getElementById('pollTitleInput').value.trim();
        if (!title) {
            showWarningNotification("Poll title / question is required.");
            document.getElementById('pollTitleInput').focus();
            return;
        }

        const optionInputs = document.querySelectorAll('.poll-option-input');
        const options = [];
        optionInputs.forEach(inp => {
            const val = inp.value.trim();
            if (val) options.push(val);
        });

        if (options.length < 2) {
            showWarningNotification("Please provide at least 2 non-empty options for the poll.");
            return;
        }

        const description = document.getElementById('pollDescInput').value.trim();
        const startDate = document.getElementById('pollStartDateInput').value;
        const endDate = document.getElementById('pollEndDateInput').value;
        const status = document.getElementById('pollStatusInput').value;

        // UI loading state
        const postBtn = document.getElementById('postPollBtn');
        const postBtnText = document.getElementById('postBtnText');
        const postBtnIcon = document.getElementById('postBtnIcon');
        const postBtnSpinner = document.getElementById('postBtnSpinner');

        postBtn.disabled = true;
        postBtnText.innerText = "Creating...";
        postBtnIcon.classList.add('d-none');
        postBtnSpinner.classList.remove('d-none');

        const payload = {
            title: title,
            description: description,
            project_id: selectedProject ? selectedProject.id : null,
            options: options,
            start_date: startDate || null,
            end_date: endDate || null,
            status: status
        };

        const formData = new FormData();
        formData.append('data', JSON.stringify(payload));
        if (selectedImageFile) {
            formData.append('image', selectedImageFile);
        }

        try {
            const response = await fetch(`${API_BASE_URL}/create`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || `Failed to create poll (Status ${response.status})`);
            }

            showSuccessNotification("Poll created successfully!");

            // Reset form
            document.getElementById('pollTitleInput').value = '';
            document.getElementById('pollDescInput').value = '';
            document.getElementById('pollEndDateInput').value = '';
            document.getElementById('pollStatusInput').value = 'active';
            removeSelectedImage();

            // Reset options to initial 2
            resetOptionsForm();

            // Refresh current polls list
            loadPolls(selectedProject ? selectedProject.id : null);

        } catch (error) {
            console.error("Error creating poll:", error);
            showErrorNotification(error.message || "Failed to create poll. Please try again.");
        } finally {
            postBtn.disabled = false;
            postBtnText.innerText = "Create Poll";
            postBtnIcon.classList.remove('d-none');
            postBtnSpinner.classList.add('d-none');
        }
    };

    function resetOptionsForm() {
        pollOptionsContainer.innerHTML = `
            <div class="input-group poll-option-row">
                <span class="input-group-text bg-light text-muted small fw-bold" style="width: 38px; justify-content: center;">1</span>
                <input type="text" class="form-control poll-option-input" placeholder="Option 1" required>
            </div>
            <div class="input-group poll-option-row">
                <span class="input-group-text bg-light text-muted small fw-bold" style="width: 38px; justify-content: center;">2</span>
                <input type="text" class="form-control poll-option-input" placeholder="Option 2" required>
            </div>
        `;
        optionsCountDisplay.innerText = "2 options";
    }

    // ── Toggle Poll Status Action ────────────────────────────────────
    window.togglePollStatus = async function(pollId, newStatus) {
        if (!pollId) return;

        try {
            const formData = new FormData();
            formData.append('status', newStatus);

            const response = await fetch(`${API_BASE_URL}/update/${pollId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || `Failed to update poll status`);
            }

            showSuccessNotification(`Poll status changed to ${newStatus}.`);
            loadPolls(selectedProject ? selectedProject.id : null);

        } catch (error) {
            console.error("Error updating poll status:", error);
            showErrorNotification(error.message || "Failed to update poll status.");
        }
    };

    // ── Delete Poll Action ──────────────────────────────────────────
    window.confirmDeletePoll = function(pollId) {
        if (!pollId) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Poll?',
                text: 'Are you sure you want to delete this poll? All voting data and options will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDeletePoll(pollId);
                }
            });
        } else {
            if (confirm("Are you sure you want to delete this poll? This action cannot be undone.")) {
                executeDeletePoll(pollId);
            }
        }
    };

    async function executeDeletePoll(pollId) {
        try {
            const response = await fetch(`${API_BASE_URL}/delete/${pollId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || `Failed to delete poll (Status ${response.status})`);
            }

            showSuccessNotification("Poll deleted successfully.");
            loadPolls(selectedProject ? selectedProject.id : null);

        } catch (error) {
            console.error("Error deleting poll:", error);
            showErrorNotification(error.message || "Failed to delete poll.");
        }
    }

    // ── Notifications Helper ────────────────────────────────────────
    function showSuccessNotification(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    }

    function showWarningNotification(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Notice',
                text: message,
                timer: 3500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    }

    function showErrorNotification(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                timer: 4000,
                showConfirmButton: true
            });
        } else {
            alert("Error: " + message);
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateStr;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ── Voters Modal Logic ──────────────────────────────────────────
    let currentVotersList = [];
    let activePollIdForVoters = null;
    let votersListModalInstance = null;

    window.openVotersModal = async function(pollId, optionId = null) {
        if (!pollId) return;

        activePollIdForVoters = pollId;

        const poll = rawPolls.find(p => p.id === pollId);
        const pollTitle = poll ? poll.title : `Poll #${pollId}`;
        let optionText = null;

        if (optionId && poll && poll.options) {
            const opt = poll.options.find(o => o.id === optionId);
            if (opt) optionText = opt.option_text;
        }

        // Subtitles and labels
        document.getElementById('modalPollQuestionSubtitle').innerText = pollTitle;
        const filterNotice = document.getElementById('modalOptionFilterNotice');
        if (optionText) {
            document.getElementById('modalFilteredOptionName').innerText = optionText;
            filterNotice.style.display = 'flex';
        } else {
            filterNotice.style.display = 'none';
        }

        // Reset UI states
        document.getElementById('voterSearchInput').value = '';
        document.getElementById('votersModalLoading').style.display = 'block';
        document.getElementById('votersModalEmpty').style.display = 'none';
        document.getElementById('votersTableContainer').style.display = 'none';
        document.getElementById('modalVotersCountBadge').innerText = 'Loading...';

        // Show Modal
        const modalEl = document.getElementById('votersListModal');
        votersListModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        votersListModalInstance.show();

        try {
            let url = `${API_BASE_URL}/${pollId}/voters`;
            if (optionId) {
                url += `?option_id=${optionId}`;
            }

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const resData = await response.json();
            currentVotersList = Array.isArray(resData.data) ? resData.data : [];

            document.getElementById('votersModalLoading').style.display = 'none';
            renderVotersTable(currentVotersList);

        } catch (error) {
            console.error("Failed to load voters:", error);
            document.getElementById('votersModalLoading').style.display = 'none';
            document.getElementById('votersModalEmpty').style.display = 'block';
            document.getElementById('votersModalEmptyText').innerText = "Failed to load voters from server.";
            document.getElementById('modalVotersCountBadge').innerText = '0 Voters';
        }
    };

    window.viewAllPollVoters = function() {
        if (activePollIdForVoters) {
            openVotersModal(activePollIdForVoters, null);
        }
    };

    window.filterVotersList = function() {
        const query = document.getElementById('voterSearchInput').value.toLowerCase().trim();
        if (!query) {
            renderVotersTable(currentVotersList);
            return;
        }

        const filtered = currentVotersList.filter(v => {
            const name = (v.client_name || '').toLowerCase();
            const flat = (v.flat_name || '').toLowerCase();
            const phone = (v.client_phone || '').toLowerCase();
            const opt = (v.option_text || '').toLowerCase();
            return name.includes(query) || flat.includes(query) || phone.includes(query) || opt.includes(query);
        });

        renderVotersTable(filtered);
    };

    function renderVotersTable(voters) {
        const tbody = document.getElementById('votersTableBody');
        const emptyBox = document.getElementById('votersModalEmpty');
        const tableContainer = document.getElementById('votersTableContainer');
        const countBadge = document.getElementById('modalVotersCountBadge');

        countBadge.innerText = `${voters.length} Voter${voters.length === 1 ? '' : 's'}`;
        tbody.innerHTML = '';

        if (!voters || voters.length === 0) {
            tableContainer.style.display = 'none';
            emptyBox.style.display = 'block';
            document.getElementById('votersModalEmptyText').innerText = currentVotersList.length > 0 
                ? "No voters match your search query." 
                : "No votes recorded yet for this selection.";
            return;
        }

        emptyBox.style.display = 'none';
        tableContainer.style.display = 'block';

        voters.forEach((v, index) => {
            const tr = document.createElement('tr');

            // Flat / Unit pill
            let flatHtml = '<span class="text-muted">-</span>';
            if (v.flat_name && v.flat_name !== 'N/A') {
                flatHtml = `<span class="badge bg-light text-dark border px-2 py-1"><i class="fa-solid fa-door-closed me-1 text-success"></i>${escapeHtml(v.flat_name)}</span>`;
            }

            // Phone
            const phoneHtml = v.client_phone ? `<div class="text-muted" style="font-size: 11px;"><i class="fa-solid fa-phone me-1"></i>${escapeHtml(v.client_phone)}</div>` : '';

            // Remarks
            const remarksHtml = v.remarks ? `<div class="text-muted fst-italic mt-1" style="font-size: 11px;">&ldquo;${escapeHtml(v.remarks)}&rdquo;</div>` : '';

            tr.innerHTML = `
                <td class="text-center text-muted fw-bold">${index + 1}</td>
                <td>
                    <div class="fw-semibold text-dark d-flex align-items-center gap-1">
                        <i class="fa-regular fa-user text-secondary" style="font-size: 11px;"></i>
                        <span>${escapeHtml(v.client_name || 'Client #' + v.client_id)}</span>
                    </div>
                    ${phoneHtml}
                </td>
                <td>${flatHtml}</td>
                <td>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fa-regular fa-circle-check me-1"></i>${escapeHtml(v.option_text)}
                    </span>
                    ${remarksHtml}
                </td>
                <td class="text-muted small">${formatDate(v.voted_at)}</td>
            `;

            tbody.appendChild(tr);
        });
    }

})();
</script>
