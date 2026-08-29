<?php
$page_title = "Create Employee";
$page = 'Create Employee';
$show_breadcrumb = true;
?>

<div class="card__wrapper shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pb-3 mb-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>CREATE EMPLOYEE</h4>
        <a href="<?= $GLOBALS['base_url'] ?>/hrm-external-employees" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Tipsoi Employees
        </a>
    </div>

    <div class="card-body">
        <div id="alertContainer"></div>

        <form id="createTipsoiEmployeeForm">
            <div class="row ">
                <!-- Basic Info -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required placeholder="Employee Name">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold">Employee ID <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="employeeOfficeId" required placeholder="Employee Office ID">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required placeholder="Email Address">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="1">Male</option>
                        <option value="2">Female</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Password">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmPassword" required placeholder="Confirm Password">
                </div>

                <div class="col-12 mt-4">
                    <button class="btn btn-outline-secondary w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions" aria-expanded="false" aria-controls="advancedOptions">
                        <i class="bi bi-gear-fill me-2"></i> Advanced Options
                    </button>
                </div>
                
                <div class="collapse col-12" id="advancedOptions">
                    <div class="card card-body border-0 bg-light mt-3">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" class="form-control" name="dateOfJoin">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Workplace</label>
                                <select class="form-select" name="workplaceId" id="workplaceId">
                                    <option value="">Select Workplace</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Designation</label>
                                <select class="form-select" name="designationId" id="designationId">
                                    <option value="">Select Designation</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Department</label>
                                <select class="form-select" name="departmentId" id="departmentId">
                                    <option value="">Select Department</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" class="form-control" name="dateOfResignation">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Leave Policy</label>
                                <select class="form-select" name="leavePolicyId" id="leavePolicyId">
                                    <option value="">Select Leave Policy</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Employment Type</label>
                                <select class="form-select" name="employmentTypeId" id="employmentTypeId">
                                    <option value="">Select Employment Type</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Shift Group</label>
                                <select class="form-select" name="shiftGroupId" id="shiftGroupId">
                                    <option value="">Select Shift Group</option>
                                    <option value="" disabled>Loading...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button type="reset" class="btn btn-secondary px-4 me-2">Reset</button>
                <button type="submit" class="btn btn-primary px-5 fw-bold" id="btnSubmit">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= $GLOBALS['base_url'] ?>';
    
    // Load dropdowns from backend
    fetch(`${baseUrl}/api/hrm/tipsoi-dropdowns`)
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                populateDropdown('designationId', res.data.designations, 'name');
                populateDropdown('departmentId', res.data.departments, 'name');
                populateDropdown('leavePolicyId', res.data.leavePolicies, 'name');
                populateDropdown('employmentTypeId', res.data.employmentTypes, 'name');
                populateDropdown('shiftGroupId', res.data.shiftGroups, 'name');
                populateDropdown('workplaceId', res.data.workplaces, 'name');
            } else {
                showAlert('warning', res.message || 'Unable to load some dropdown data. Please try again.');
                clearAllLoading();
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('danger', 'Network error while loading dropdown data.');
            clearAllLoading();
        });

    function clearAllLoading() {
        const ids = ['designationId', 'departmentId', 'leavePolicyId', 'employmentTypeId', 'shiftGroupId', 'workplaceId'];
        ids.forEach(id => populateDropdown(id, [], 'name'));
    }

    function populateDropdown(elementId, items, textKey) {
        const select = document.getElementById(elementId);
        if (!select) return;
        
        let placeholder = select.options.length > 0 ? select.options[0].text : 'Select Option';
        let html = `<option value="">${placeholder}</option>`;
        
        if (items && Array.isArray(items)) {
            items.forEach(item => {
                html += `<option value="${item.id}">${item[textKey] || item.name || item.title || 'Unknown'}</option>`;
            });
        }
        
        select.innerHTML = html;
    }

    function showAlert(type, message) {
        const container = document.getElementById('alertContainer');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        window.scrollTo(0, 0);
    }

    const form = document.getElementById('createTipsoiEmployeeForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            showAlert('danger', 'Passwords do not match.');
            return;
        }

        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch(`${baseUrl}/api/hrm/create-tipsoi-employee`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Create Employee';

            if (res.success) {
                showAlert('success', 'Employee created successfully! Redirecting...');
                form.reset();
                setTimeout(() => {
                    window.location.href = `${baseUrl}/hrm-external-employees`;
                }, 2000);
            } else {
                showAlert('danger', res.message || 'Unable to create employee. Please check the information and try again.');
            }
        })
        .catch(err => {
            console.error(err);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Create Employee';
            showAlert('danger', 'A network error occurred. Please try again.');
        });
    });
});
</script>

