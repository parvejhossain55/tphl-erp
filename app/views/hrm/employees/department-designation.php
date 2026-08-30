<?php
$page_title = "Department & Designation Management";
$page = 'Department & Designation';
$show_breadcrumb = true;
?>

<div class="row g-20">
    <!-- Department Management Section -->
    <div class="col-lg-6 col-md-12">
        <div class="card__wrapper h-100 position-relative">
            <div id="deptLoadingOverlay" class="position-absolute w-100 h-100 bg-white align-items-center justify-content-center d-flex" style="z-index: 10; opacity: 0.8; top: 0; left: 0;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between mb-20 flex-wrap gap-10">
                <h5 class="m-0 fw-bold text-dark">Department Management</h5>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="openDeptModal()">
                    <i class="fa-solid fa-plus me-1"></i> Add Department
                </button>
            </div>
            
            <div class="mb-3">
                <input type="text" id="searchDept" class="form-control" placeholder="Search Department...">
            </div>
            
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th scope="col" style="width: 60px;">S/N</th>
                            <th scope="col">Name</th>
                            <th scope="col">Hierarchy</th>
                            <th scope="col">Office ID</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deptTableBody">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
                <div id="deptEmptyState" class="text-center py-4 text-muted" style="display: none;">
                    No departments found.
                </div>
            </div>
        </div>
    </div>

    <!-- Designation Management Section -->
    <div class="col-lg-6 col-md-12">
        <div class="card__wrapper h-100 position-relative">
            <div id="desigLoadingOverlay" class="position-absolute w-100 h-100 bg-white align-items-center justify-content-center d-flex" style="z-index: 10; opacity: 0.8; top: 0; left: 0;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-20 flex-wrap gap-10">
                <h5 class="m-0 fw-bold text-dark">Designation Management</h5>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="openDesigModal()">
                    <i class="fa-solid fa-plus me-1"></i> Add Designation
                </button>
            </div>
            
            <div class="mb-3">
                <input type="text" id="searchDesig" class="form-control" placeholder="Search Designation...">
            </div>
            
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th scope="col" style="width: 60px;">S/N</th>
                            <th scope="col">Name</th>
                            <th scope="col">Hierarchy</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="desigTableBody">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
                <div id="desigEmptyState" class="text-center py-4 text-muted" style="display: none;">
                    No designations found.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deptModalTitle">Create Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deptForm">
                <div class="modal-body">
                    <input type="hidden" id="deptId" name="id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deptName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hierarchy <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="deptHierarchy" name="hierarchy" value="1" required>
                    </div>
                    <div class="mb-3" id="deptOfficeIdGroup">
                        <label class="form-label fw-semibold">Office ID</label>
                        <input type="number" class="form-control" id="deptOfficeId" name="officeId">
                        <small class="text-muted">Tipsoi Office ID.</small>
                    </div>
                    <div class="mb-3" id="deptStatusGroup" style="display:none;">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="deptStatus" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveDept">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Designation Modal -->
<div class="modal fade" id="desigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="desigModalTitle">Create Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="desigForm">
                <div class="modal-body">
                    <input type="hidden" id="desigId" name="id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="desigName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hierarchy <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="desigHierarchy" name="hierarchy" value="1" required>
                    </div>
                    <div class="mb-3" id="desigStatusGroup">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="desigStatus" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveDesig">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let allDepartments = [];
    let allDesignations = [];

    document.addEventListener("DOMContentLoaded", function () {
        loadDepartments();
        loadDesignations();

        // Search Events
        document.getElementById('searchDept').addEventListener('input', function(e) {
            renderDepartments(e.target.value);
        });
        document.getElementById('searchDesig').addEventListener('input', function(e) {
            renderDesignations(e.target.value);
        });

        // Form Submit
        document.getElementById('deptForm').addEventListener('submit', handleDeptSubmit);
        document.getElementById('desigForm').addEventListener('submit', handleDesigSubmit);
    });

    // ----------------------------------------------------
    // DEPARTMENT LOGIC
    // ----------------------------------------------------
    async function loadDepartments() {
        setLoading('deptLoadingOverlay', true);
        try {
            const res = await fetch(`<?= $base_url ?>/api/hrm/departments`);
            const data = await res.json();
            if (data.success) {
                allDepartments = data.departmentList || [];
                renderDepartments();
            } else {
                showError('Failed to load departments');
            }
        } catch (e) {
            console.error(e);
            showError('Network error loading departments');
        } finally {
            setLoading('deptLoadingOverlay', false);
        }
    }

    function renderDepartments(search = '') {
        const tbody = document.getElementById('deptTableBody');
        const emptyState = document.getElementById('deptEmptyState');
        tbody.innerHTML = '';
        
        let filtered = allDepartments;
        if (search.trim() !== '') {
            filtered = allDepartments.filter(d => 
                (d.name || '').toLowerCase().includes(search.toLowerCase())
            );
        }

        if (filtered.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
            filtered.forEach((d, index) => {
                const statusBadge = (d.status == 1) 
                    ? `<span class="badge bg-success">Active</span>` 
                    : `<span class="badge bg-danger">Inactive</span>`;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${escapeHtml(d.name || '-')}</td>
                    <td>${escapeHtml(d.hierarchy || '-')}</td>
                    <td>${escapeHtml(d.officeId || '-')}</td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary px-2 py-1 me-1" onclick='editDept(${JSON.stringify(d)})' title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger px-2 py-1" onclick="deleteDept(${d.id}, '${escapeHtml(d.name || '')}')" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    function openDeptModal() {
        document.getElementById('deptForm').reset();
        document.getElementById('deptId').value = '';
        document.getElementById('deptModalTitle').textContent = 'Create Department';
        document.getElementById('btnSaveDept').textContent = 'Create';
        document.getElementById('deptStatusGroup').style.display = 'none';
        document.getElementById('deptOfficeIdGroup').style.display = 'block';
        new bootstrap.Modal(document.getElementById('deptModal')).show();
    }

    function editDept(d) {
        document.getElementById('deptId').value = d.id;
        document.getElementById('deptName').value = d.name || '';
        document.getElementById('deptHierarchy').value = d.hierarchy || 1;
        document.getElementById('deptStatus').value = d.status || 0;
        
        document.getElementById('deptModalTitle').textContent = 'Update Department';
        document.getElementById('btnSaveDept').textContent = 'Update';
        document.getElementById('deptStatusGroup').style.display = 'block';
        document.getElementById('deptOfficeIdGroup').style.display = 'none'; // Not editable on update as per instruction
        
        new bootstrap.Modal(document.getElementById('deptModal')).show();
    }

    async function handleDeptSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('deptId').value;
        const btn = document.getElementById('btnSaveDept');
        
        const payload = {
            name: document.getElementById('deptName').value,
            hierarchy: parseInt(document.getElementById('deptHierarchy').value)
        };
        
        if (id) {
            payload.status = parseInt(document.getElementById('deptStatus').value);
        } else {
            const oid = document.getElementById('deptOfficeId').value;
            if(oid) payload.officeId = parseInt(oid);
        }

        btn.disabled = true;
        btn.innerHTML = id ? 'Updating...' : 'Creating...';

        try {
            const url = id 
                ? `<?= $base_url ?>/api/hrm/department/update/${id}`
                : `<?= $base_url ?>/api/hrm/department/create`;
            
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) {
                Swal.fire('Success', `Department ${id ? 'updated' : 'created'} successfully`, 'success');
                bootstrap.Modal.getInstance(document.getElementById('deptModal')).hide();
                loadDepartments();
            } else {
                Swal.fire('Error', data.message || 'Failed to save department', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Network error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = id ? 'Update' : 'Create';
        }
    }

    function deleteDept(id, name) {
        Swal.fire({
            title: 'Delete Department?',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`<?= $base_url ?>/api/hrm/department/delete/${id}`, { method: 'POST' });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire('Deleted!', 'Department deleted successfully.', 'success');
                        loadDepartments();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete department', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Network error occurred', 'error');
                }
            }
        });
    }

    // ----------------------------------------------------
    // DESIGNATION LOGIC
    // ----------------------------------------------------
    async function loadDesignations() {
        setLoading('desigLoadingOverlay', true);
        try {
            const res = await fetch(`<?= $base_url ?>/api/hrm/designations`);
            const data = await res.json();
            if (data.success) {
                allDesignations = data.designationList || [];
                renderDesignations();
            } else {
                showError('Failed to load designations');
            }
        } catch (e) {
            console.error(e);
            showError('Network error loading designations');
        } finally {
            setLoading('desigLoadingOverlay', false);
        }
    }

    function renderDesignations(search = '') {
        const tbody = document.getElementById('desigTableBody');
        const emptyState = document.getElementById('desigEmptyState');
        tbody.innerHTML = '';
        
        let filtered = allDesignations;
        if (search.trim() !== '') {
            filtered = allDesignations.filter(d => 
                (d.name || '').toLowerCase().includes(search.toLowerCase())
            );
        }

        if (filtered.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
            filtered.forEach((d, index) => {
                const statusBadge = (d.status == 1) 
                    ? `<span class="badge bg-success">Active</span>` 
                    : `<span class="badge bg-danger">Inactive</span>`;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${escapeHtml(d.name || '-')}</td>
                    <td>${escapeHtml(d.hierarchy || '-')}</td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary px-2 py-1 me-1" onclick='editDesig(${JSON.stringify(d)})' title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger px-2 py-1" onclick="deleteDesig(${d.id}, '${escapeHtml(d.name || '')}')" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    function openDesigModal() {
        document.getElementById('desigForm').reset();
        document.getElementById('desigId').value = '';
        document.getElementById('desigModalTitle').textContent = 'Create Designation';
        document.getElementById('btnSaveDesig').textContent = 'Create';
        new bootstrap.Modal(document.getElementById('desigModal')).show();
    }

    function editDesig(d) {
        document.getElementById('desigId').value = d.id;
        document.getElementById('desigName').value = d.name || '';
        document.getElementById('desigHierarchy').value = d.hierarchy || 1;
        document.getElementById('desigStatus').value = d.status || 0;
        
        document.getElementById('desigModalTitle').textContent = 'Update Designation';
        document.getElementById('btnSaveDesig').textContent = 'Update';
        
        new bootstrap.Modal(document.getElementById('desigModal')).show();
    }

    async function handleDesigSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('desigId').value;
        const btn = document.getElementById('btnSaveDesig');
        
        const payload = {
            name: document.getElementById('desigName').value,
            hierarchy: parseInt(document.getElementById('desigHierarchy').value),
            status: parseInt(document.getElementById('desigStatus').value)
        };

        btn.disabled = true;
        btn.innerHTML = id ? 'Updating...' : 'Creating...';

        try {
            const url = id 
                ? `<?= $base_url ?>/api/hrm/designation/update/${id}`
                : `<?= $base_url ?>/api/hrm/designation/create`;
            
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) {
                Swal.fire('Success', `Designation ${id ? 'updated' : 'created'} successfully`, 'success');
                bootstrap.Modal.getInstance(document.getElementById('desigModal')).hide();
                loadDesignations();
            } else {
                Swal.fire('Error', data.message || 'Failed to save designation', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Network error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = id ? 'Update' : 'Create';
        }
    }

    function deleteDesig(id, name) {
        Swal.fire({
            title: 'Delete Designation?',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`<?= $base_url ?>/api/hrm/designation/delete/${id}`, { method: 'POST' });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire('Deleted!', 'Designation deleted successfully.', 'success');
                        loadDesignations();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete designation', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Network error occurred', 'error');
                }
            }
        });
    }

    // ----------------------------------------------------
    // UTILS
    // ----------------------------------------------------
    function setLoading(elementId, isLoading) {
        const el = document.getElementById(elementId);
        if (el) {
            if (isLoading) {
                el.classList.remove('d-none');
                el.classList.add('d-flex');
            } else {
                el.classList.remove('d-flex');
                el.classList.add('d-none');
            }
        }
    }

    function showError(msg) {
        if(typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            Swal.fire('Error', msg, 'error');
        }
    }

    function escapeHtml(unsafe) {
        if(unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>

