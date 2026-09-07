<?php
/**
 * CED Project Notices View
 * TPHL-ERP - Customer Execution Department
 */
$page_title = "Project Notices";
$page = 'Project Notices';
$show_breadcrumb = true;
?>

<style>
    .project-notices-container {
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

    /* Upload Box */
    .attachment-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background-color: #f8fafc;
        padding: 24px 16px;
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
    .btn-post-notice {
        background-color: #0d5c3a !important;
        border-color: #0d5c3a !important;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 15px;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-post-notice:hover {
        background-color: #09472c !important;
        border-color: #09472c !important;
        box-shadow: 0 4px 12px rgba(13, 92, 58, 0.25);
    }

    /* Notice Card Styling */
    .notice-feed-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 18px 20px;
        margin-bottom: 16px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .notice-feed-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .notice-thumbnail-wrap {
        position: relative;
        display: inline-block;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        max-width: 220px;
        cursor: pointer;
    }

    .notice-thumbnail-wrap img {
        width: 100%;
        height: 130px;
        object-fit: cover;
        transition: transform 0.2s ease;
    }

    .notice-thumbnail-wrap:hover img {
        transform: scale(1.04);
    }

    .notice-thumbnail-badge {
        position: absolute;
        bottom: 6px;
        right: 6px;
        background: rgba(0, 0, 0, 0.65);
        color: #ffffff;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .empty-state-text {
        font-size: 14.5px;
        color: #64748b;
    }
</style>

<div class="project-notices-container">

    <!-- ══ HEADER ══ -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="theme-header-icon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0" style="color: #0d5c3a; letter-spacing: -0.5px;">Project Notices</h3>
                <p class="text-muted mb-0 small">Send broadcast notices to project owners</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Refresh Button -->
            <button type="button" class="btn btn-sm btn-light border px-3 shadow-sm text-secondary" onclick="refreshCurrentProjectNotices()" title="Refresh Notices">
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

            <!-- Card 2: Create New Notice -->
            <div class="card-custom">
                <div class="card-title-divider">Create New Notice</div>

                <form id="createNoticeForm" onsubmit="event.preventDefault(); handlePostNotice();">
                    <!-- Title (Optional) -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Title (Optional)</label>
                        <input type="text" class="form-control" id="noticeTitleInput" placeholder="e.g. Construction Update" autocomplete="off">
                    </div>

                    <!-- Notice Description * -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold mb-1">Notice Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="noticeDescInput" rows="5" placeholder="Type your notice message here..." required></textarea>
                    </div>

                    <!-- Attachment (Image) -->
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-semibold mb-1">Attachment (Image)</label>
                        
                        <!-- Upload Dropzone -->
                        <div class="attachment-dropzone" id="attachmentDropzone" onclick="document.getElementById('noticeImageFile').click()">
                            <i class="fa-regular fa-image fa-2x text-secondary mb-2"></i>
                            <div>
                                <span class="upload-link-text">Upload a file</span>
                            </div>
                            <small class="text-muted d-block mt-1">PNG, JPG, GIF up to 5MB</small>
                            <input type="file" id="noticeImageFile" accept="image/png, image/jpeg, image/jpg, image/gif" style="display:none;" onchange="handleImageSelected(event)">
                        </div>

                        <!-- Image Preview Box -->
                        <div id="imagePreviewContainer" class="mt-2 p-2 bg-light border rounded position-relative" style="display:none;">
                            <div class="d-flex align-items-center gap-2">
                                <img id="imagePreviewThumb" src="" alt="Preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                <div class="flex-grow-1 text-truncate">
                                    <div class="small fw-semibold text-truncate" id="imagePreviewName">filename.jpg</div>
                                    <div class="text-muted" style="font-size: 11px;" id="imagePreviewSize">0 KB</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeSelectedImage()" title="Remove file">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Post Notice Button -->
                    <button type="submit" id="postNoticeBtn" class="btn w-100 btn-post-notice d-flex align-items-center justify-content-center gap-2 shadow-sm">
                        <span id="postBtnText">Post Notice</span>
                        <i class="fa-solid fa-paper-plane ms-1" id="postBtnIcon"></i>
                        <span class="spinner-border spinner-border-sm d-none" id="postBtnSpinner" role="status"></span>
                    </button>
                </form>
            </div>

        </div>

        <!-- ── RIGHT COLUMN (COL-LG-8) ── -->
        <div class="col-lg-8 col-md-7">
            <div class="card-custom h-100">
                <div class="card-title-divider d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span id="rightSectionHeader">Notices for Project ID: <span id="headerProjectId" class="text-success fw-bold">--</span></span>
                    <span id="totalNoticesCountBadge" class="badge bg-light text-secondary border d-none">0 Notices</span>
                </div>

                <!-- Loading State -->
                <div id="noticesLoadingState" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="text-muted small mt-2">Loading notices for this project...</p>
                </div>

                <!-- Empty State -->
                <div id="noticesEmptyState" class="text-center py-5">
                    <p class="empty-state-text mb-0" id="emptyStateMessage">No notices have been posted for this project yet.</p>
                </div>

                <!-- Notices Feed List -->
                <div id="noticesListFeed" style="display:none;">
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
                <img id="fullImageModalImg" src="" alt="Notice Image" class="img-fluid rounded shadow" style="max-height: 85vh; object-fit: contain; background: #fff;">
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Configuration Variables ──────────────────────────────────────
    const API_BASE_URL = "<?= rtrim($api_base_url, '/') ?>";
    const TOKEN_STORAGE_KEY = "tphl_project_notices_token";

    // State Variables
    let allProjects = [];
    let selectedProject = null;
    let currentNotices = [];
    let selectedImageFile = null;

    // Elements
    const projectSelectTrigger = document.getElementById('projectSelectTrigger');
    const projectDropdownMenu = document.getElementById('projectDropdownMenu');
    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectOptionsList = document.getElementById('projectOptionsList');
    const selectedProjectDisplay = document.getElementById('selectedProjectDisplay');
    const rightSectionHeader = document.getElementById('rightSectionHeader');
    const headerProjectId = document.getElementById('headerProjectId');
    const totalNoticesCountBadge = document.getElementById('totalNoticesCountBadge');
    const noticesLoadingState = document.getElementById('noticesLoadingState');
    const noticesEmptyState = document.getElementById('noticesEmptyState');
    const noticesListFeed = document.getElementById('noticesListFeed');
    const emptyStateMessage = document.getElementById('emptyStateMessage');
    const tokenStatusLabel = document.getElementById('tokenStatusLabel');

    // ── Initialization ──────────────────────────────────────────────
    document.addEventListener("DOMContentLoaded", function() {
        fetchProjects();

        // Close project dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('projectSelectContainer');
            if (container && !container.contains(e.target)) {
                closeProjectDropdown();
            }
        });
    });

    // ── Project Dropdown Logic ──────────────────────────────────────
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
            const community = (p.community || '').toLowerCase();
            return name.includes(query) || id.includes(query) || community.includes(query);
        });
        renderProjectOptions(filtered);
    };

    function renderProjectOptions(projects) {
        projectOptionsList.innerHTML = '';
        if (projects.length === 0) {
            projectOptionsList.innerHTML = '<div class="text-center py-3 text-muted small">No projects match your search</div>';
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

    function selectProject(project) {
        selectedProject = project;
        selectedProjectDisplay.innerText = `${project.name || 'Project #' + project.id} (ID: ${project.id})`;
        selectedProjectDisplay.title = `${project.name || 'Project #' + project.id} (ID: ${project.id})`;
        headerProjectId.innerText = project.id;
        rightSectionHeader.innerHTML = `Notices for <strong>${escapeHtml(project.name || 'Project')}</strong> (ID: <span class="text-success">${project.id}</span>)`;
        
        loadNoticesForProject(project.id);
    }

    // ── Fetch Projects API ──────────────────────────────────────────
    async function fetchProjects() {
        selectedProjectDisplay.innerText = "Loading projects...";
        projectOptionsList.innerHTML = '<div class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-2 text-success"></span>Loading projects...</div>';

        try {
            const response = await fetch(`${API_BASE_URL}/projects`, {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const data = await response.json();
            // Contract can be array or { projects: [...] } or { data: [...] }
            allProjects = Array.isArray(data) ? data : (data.projects || data.data || []);

            if (allProjects.length === 0) {
                selectedProjectDisplay.innerText = "No projects found";
                projectOptionsList.innerHTML = '<div class="text-center py-3 text-muted small">No projects found.</div>';
                emptyStateMessage.innerText = "No projects available in the system.";
                return;
            }

            renderProjectOptions(allProjects);

            // Auto-select first project
            selectProject(allProjects[0]);

        } catch (error) {
            console.error("Failed to fetch projects:", error);
            selectedProjectDisplay.innerText = "Failed to load projects";
            projectOptionsList.innerHTML = `<div class="text-center py-3 text-danger small">Error loading projects. <br><button class="btn btn-sm btn-link text-danger p-0" onclick="fetchProjects()">Try again</button></div>`;
            emptyStateMessage.innerText = "Failed to load projects from server.";
        }
    }

    // ── Fetch Notices for Selected Project ──────────────────────────
    async function loadNoticesForProject(projectId) {
        if (!projectId) return;

        noticesLoadingState.style.display = 'block';
        noticesEmptyState.style.display = 'none';
        noticesListFeed.style.display = 'none';
        totalNoticesCountBadge.classList.add('d-none');

        try {
            const response = await fetch(`${API_BASE_URL}/projects/${projectId}/notices`, {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const data = await response.json();
            currentNotices = Array.isArray(data) ? data : (data.notices || data.data || []);

            noticesLoadingState.style.display = 'none';

            if (!currentNotices || currentNotices.length === 0) {
                emptyStateMessage.innerText = "No notices have been posted for this project yet.";
                noticesEmptyState.style.display = 'block';
                noticesListFeed.style.display = 'none';
                totalNoticesCountBadge.classList.add('d-none');
            } else {
                noticesEmptyState.style.display = 'none';
                totalNoticesCountBadge.innerText = `${currentNotices.length} Notice${currentNotices.length > 1 ? 's' : ''}`;
                totalNoticesCountBadge.classList.remove('d-none');
                renderNotices(currentNotices);
            }

        } catch (error) {
            console.error("Failed to load project notices:", error);
            noticesLoadingState.style.display = 'none';
            emptyStateMessage.innerHTML = `<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error loading notices: ${escapeHtml(error.message)}</span>`;
            noticesEmptyState.style.display = 'block';
            noticesListFeed.style.display = 'none';
        }
    }

    window.refreshCurrentProjectNotices = function() {
        if (selectedProject && selectedProject.id) {
            loadNoticesForProject(selectedProject.id);
        } else {
            fetchProjects();
        }
    };

    // ── Render Notices ──────────────────────────────────────────────
    function renderNotices(notices) {
        noticesListFeed.innerHTML = '';

        notices.forEach(notice => {
            const card = document.createElement('div');
            card.className = 'notice-feed-card';

            // Resolve Image URL
            let imageHtml = '';
            if (notice.image) {
                let fullImageUrl = notice.image;
                if (!/^https?:\/\//i.test(fullImageUrl)) {
                    const cleanBase = API_BASE_URL.replace(/\/api\/?$/i, '');
                    const cleanPath = notice.image.replace(/^\//, '');
                    fullImageUrl = `${cleanBase}/${cleanPath}`;
                }

                imageHtml = `
                    <div class="mt-3">
                        <div class="notice-thumbnail-wrap" onclick="viewFullImage('${escapeHtml(fullImageUrl)}')">
                            <img src="${escapeHtml(fullImageUrl)}" alt="${escapeHtml(notice.title || 'Notice Image')}" loading="lazy" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'100\\' height=\\'80\\' viewBox=\\'0 0 100 80\\'><rect fill=\\'%23eee\\' width=\\'100\\' height=\\'80\\'/><text fill=\\'%23aaa\\' x=\\'50%\\' y=\\'50%\\' text-anchor=\\'middle\\' dominant-baseline=\\'middle\\' font-size=\\'12\\'>Image Error</text></svg>'">
                            <span class="notice-thumbnail-badge"><i class="fa-solid fa-magnifying-glass-plus me-1"></i>View</span>
                        </div>
                    </div>
                `;
            }

            // Format Date
            let formattedDate = 'Recently';
            if (notice.created_at) {
                try {
                    const d = new Date(notice.created_at);
                    formattedDate = d.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch(e) {
                    formattedDate = notice.created_at;
                }
            }

            const viewsCount = notice.views_count !== undefined ? notice.views_count : 0;
            const titleHtml = notice.title ? `<h6 class="fw-bold text-dark mb-1">${escapeHtml(notice.title)}</h6>` : '';

            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        ${titleHtml}
                        <div class="d-flex align-items-center flex-wrap gap-2 text-muted small">
                            <span><i class="fa-regular fa-clock me-1"></i>${formattedDate}</span>
                            <span>&bull;</span>
                            <span class="badge bg-light text-secondary border"><i class="fa-regular fa-eye me-1"></i>${viewsCount} views</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 px-2" onclick="confirmDeleteNotice(${notice.id})" title="Delete Notice">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
                <div class="text-secondary small mt-2" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(notice.description || '')}</div>
                ${imageHtml}
            `;

            noticesListFeed.appendChild(card);
        });

        noticesListFeed.style.display = 'block';
    }

    // ── Image Handling ──────────────────────────────────────────────
    window.handleImageSelected = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            showErrorNotification("Image size exceeds the 5MB limit. Please choose a smaller image.");
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
        document.getElementById('noticeImageFile').value = '';
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

    // ── Post Notice Action ──────────────────────────────────────────
    window.handlePostNotice = async function() {
        if (!selectedProject || !selectedProject.id) {
            showWarningNotification("Please select a project first.");
            return;
        }

        const description = document.getElementById('noticeDescInput').value.trim();
        if (!description) {
            showWarningNotification("Notice Description is required.");
            document.getElementById('noticeDescInput').focus();
            return;
        }

        const title = document.getElementById('noticeTitleInput').value.trim();

        // Setup UI loading state
        const postBtn = document.getElementById('postNoticeBtn');
        const postBtnText = document.getElementById('postBtnText');
        const postBtnIcon = document.getElementById('postBtnIcon');
        const postBtnSpinner = document.getElementById('postBtnSpinner');

        postBtn.disabled = true;
        postBtnText.innerText = "Posting...";
        postBtnIcon.classList.add('d-none');
        postBtnSpinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('project_id', selectedProject.id);
        formData.append('description', description);
        if (title) formData.append('title', title);
        if (selectedImageFile) formData.append('image', selectedImageFile);

        try {
            const response = await fetch(`${API_BASE_URL}/project-notices`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                    // Note: Do NOT set Content-Type header when using FormData
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || `Failed to post notice (Status ${response.status})`);
            }

            showSuccessNotification("Notice broadcasted successfully!");

            // Reset form
            document.getElementById('noticeTitleInput').value = '';
            document.getElementById('noticeDescInput').value = '';
            removeSelectedImage();

            // Refresh current project notices
            loadNoticesForProject(selectedProject.id);

        } catch (error) {
            console.error("Error posting notice:", error);
            showErrorNotification(error.message || "Failed to post notice. Please check your network and try again.");
        } finally {
            postBtn.disabled = false;
            postBtnText.innerText = "Post Notice";
            postBtnIcon.classList.remove('d-none');
            postBtnSpinner.classList.add('d-none');
        }
    };

    // ── Delete Notice Action ────────────────────────────────────────
    window.confirmDeleteNotice = function(noticeId) {
        if (!noticeId) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Notice?',
                text: 'Are you sure you want to delete this notice? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDeleteNotice(noticeId);
                }
            });
        } else {
            if (confirm("Are you sure you want to delete this notice?")) {
                executeDeleteNotice(noticeId);
            }
        }
    };

    async function executeDeleteNotice(noticeId) {
        try {
            const response = await fetch(`${API_BASE_URL}/project-notices/${noticeId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || `Failed to delete notice (Status ${response.status})`);
            }

            showSuccessNotification("Notice deleted successfully.");
            if (selectedProject && selectedProject.id) {
                loadNoticesForProject(selectedProject.id);
            }

        } catch (error) {
            console.error("Error deleting notice:", error);
            showErrorNotification(error.message || "Failed to delete notice.");
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

    function showErrorNotification(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#0d5c3a'
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
                confirmButtonColor: '#0d5c3a'
            });
        } else {
            alert(message);
        }
    }

    function showInfoNotification(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

})();
</script>

