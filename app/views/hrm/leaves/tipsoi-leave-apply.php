<?php
$page_title = "Tipsoi Leave Apply";
$page = 'Tipsoi Leave Apply';
$show_breadcrumb = true;
?>
<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-4">
            <h3 class="mb-4">Tipsoi Employee Leave Apply</h3>
            <div id="applyLeaveError" class="alert alert-danger mt-3 d-none fs-12 py-2" role="alert"></div>
            <div id="applyLeaveSuccess" class="alert alert-success mt-3 d-none fs-12 py-2" role="alert"></div>

            <form id="applyLeaveForm" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Employee Select -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">
                            <label for="employeeSelect" class="form-label">Tipsoi Employee ID <span class="text-danger">*</span></label>
                        </div>
                        <input type="text" id="employeeSelect" name="employee_id" class="form-control" required placeholder="Enter Tipsoi Office ID (e.g., 1001)">
                    </div>

                    <!-- Leave Category Select -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">
                            <label for="leaveCategoryId" class="form-label">Leave Category <span class="text-danger">*</span></label>
                        </div>
                        <select id="leaveCategoryId" name="leaveCategoryId" class="form-select" required>
                            <option value="" disabled selected>Loading...</option>
                        </select>
                    </div>

                    <!-- Start & End Date -->
                    <div class="col-xxl-12 col-12 row mb-2">
                        <div class="col-md-6 col-12">
                            <div class="form__input-title">
                                <label for="leaveStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                            </div>
                            <input type="date" id="leaveStartDate" name="startDate" class="form-control" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form__input-title">
                                <label for="leaveEndDate" class="form-label">End Date <span class="text-danger">*</span></label>
                            </div>
                            <input type="date" id="leaveEndDate" name="endDate" class="form-control" required>
                        </div>
                    </div>

                    <!-- Half Day -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2 d-flex align-items-center mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isHalfDay" name="isHalfDay" value="true">
                            <label class="form-check-label fw-bold" for="isHalfDay">Half Day Leave</label>
                        </div>
                    </div>
                    
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">
                            <label for="leaveAttachment" class="form-label">Attachment <span class="text-muted">(Optional)</span></label>
                        </div>
                        <input type="file" id="leaveAttachment" name="attachment" class="form-control">
                    </div>

                    <!-- Reason -->
                    <div class="col-12 mb-2">
                        <div class="form__input-title">
                            <label for="leaveReason" class="form-label">Reason <span class="text-danger">*</span></label>
                        </div>
                        <textarea id="leaveReason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12 text-center mt-3">
                        <button type="submit" id="btnApplyLeave" class="btn btn-primary px-5">Submit Application</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    const applyLeaveForm = document.getElementById("applyLeaveForm");
    const leaveCategoryId = document.getElementById("leaveCategoryId");

    function loadLeaveCategories() {
        fetch(`${baseUrl}/api/hrm/leave-category`)
            .then(res => res.json())
            .then(data => {
                const list = data.leaveCategoryList || data.data || data;
                if (Array.isArray(list)) {
                    let html = '<option value="" disabled selected>Select Leave Category...</option>';
                    list.forEach(cat => {
                        if (cat.status === undefined || cat.status == 1 || cat.status === true) { 
                            html += `<option value="${cat.id}">${cat.name}</option>`;
                        }
                    });
                    if (leaveCategoryId) leaveCategoryId.innerHTML = html;
                } else {
                    if (leaveCategoryId) leaveCategoryId.innerHTML = '<option value="" disabled selected>Error loading categories</option>';
                }
            })
            .catch(err => {
                if (leaveCategoryId) leaveCategoryId.innerHTML = '<option value="" disabled selected>Error loading categories</option>';
            });
    }

    loadLeaveCategories();

    if (applyLeaveForm) {
        applyLeaveForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const btnSubmit = document.getElementById("btnApplyLeave");
            const errorAlert = document.getElementById("applyLeaveError");
            const successAlert = document.getElementById("applyLeaveSuccess");
            
            errorAlert.classList.add("d-none");
            successAlert.classList.add("d-none");
            
            const startDateVal = document.getElementById("leaveStartDate").value;
            const endDateVal = document.getElementById("leaveEndDate").value;
            
            if (new Date(endDateVal) < new Date(startDateVal)) {
                errorAlert.textContent = "End date cannot be earlier than start date.";
                errorAlert.classList.remove("d-none");
                return;
            }
            
            const tipsoiEmpId = document.getElementById("employeeSelect").value;
            if (!tipsoiEmpId) {
                errorAlert.textContent = "Please provide Tipsoi Employee ID.";
                errorAlert.classList.remove("d-none");
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

            const startYear = new Date(startDateVal).getFullYear();
            const fiscalYearStartDate = new Date(startYear, 0, 1, 0, 0, 0).getTime(); 
            const fiscalYearEndDate = new Date(startYear, 11, 31, 23, 59, 59).getTime();

            const startDateMs = new Date(startDateVal + 'T00:00:00').getTime();
            const endDateMs = new Date(endDateVal + 'T23:59:59').getTime();

            const postBodyData = {
                leaveCategoryId: parseInt(document.getElementById("leaveCategoryId").value),
                reason: document.getElementById("leaveReason").value,
                forceApproval: false,
                fiscalYearStartDate: fiscalYearStartDate,
                fiscalYearEndDate: fiscalYearEndDate,
                startDate: startDateMs,
                endDate: endDateMs,
                isHalfDay: document.getElementById("isHalfDay").checked
            };

            const formData = new FormData();
            formData.append('postBody', JSON.stringify(postBodyData));
            
            const fileInput = document.getElementById("leaveAttachment");
            if (fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);
            }

            fetch(`${baseUrl}/api/hrm/leave-management/apply/employee/${tipsoiEmpId}`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || (data.status && data.status === 'success') || data.message === 'Leave application submitted successfully.') {
                    successAlert.textContent = data.message || "Leave applied successfully!";
                    successAlert.classList.remove("d-none");
                    applyLeaveForm.reset();
                } else {
                    throw new Error(data.message || "Failed to apply leave. Ensure you have balance.");
                }
            })
            .catch(err => {
                errorAlert.textContent = err.message;
                errorAlert.classList.remove("d-none");
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = "Submit Application";
            });
        });
    }
});
</script>

