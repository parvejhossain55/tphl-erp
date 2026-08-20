<?php
$page_title = "create email alert";
$page = 'Create Email Alert';
$show_breadcrumb = true;

// Ensure filter_type has a fallback default value
$filter_type = $_GET['filter_type'] ?? $filter_type ?? 'all_employees';
?>

<form action="<?= $GLOBALS['base_url'] ?>/store-email-alert" method="POST" class="mb-20">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Create Email Alert</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $GLOBALS['base_url'] ?>/create-email-template" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Add Template
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card__wrapper p-3 shadow-sm mb-3">
        <div class="row g-2 align-items-center">
            <!-- Send Email To Filter -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                <label>Send Email To</label>
                <select class="form-select" name="filter_type" id="filter_type">
                    <option value="all_employees" <?= $filter_type == 'all_employees' ? 'selected' : '' ?>>All Employees
                    </option>
                    <option value="department" <?= $filter_type == 'department' ? 'selected' : '' ?>>By Department
                    </option>
                    <option value="designation" <?= $filter_type == 'designation' ? 'selected' : '' ?>>By Designation
                    </option>
                    <option value="specific_employees" <?= $filter_type == 'specific_employees' ? 'selected' : '' ?>>
                        Specific Employees</option>
                </select>
            </div>

            <!-- Department Filter Wrapper -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12 filter-wrapper" id="department_wrapper"
                style="<?= $filter_type == 'department' ? '' : 'display: none;' ?>">
                <label>Select Department</label>
                <select multiple name="targetIds[]" class="select2 form-select" data-live-search="true">
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>">
                            <?= htmlspecialchars($department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Designation Filter Wrapper -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12 filter-wrapper" id="designation_wrapper"
                style="<?= $filter_type == 'designation' ? '' : 'display: none;' ?>">
                <label>Select Designation</label>

                <select multiple name="targetIds[]" class="select2 form-select" data-live-search="true">
                    <?php foreach ($designations as $designation): ?>
                        <option value="<?= $designation['id'] ?>" <?= ($selectedDesignation ?? '') == $designation['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($designation['designation_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Employee Filter Wrapper -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12 filter-wrapper" id="employee_wrapper"
                style="<?= $filter_type == 'specific_employees' ? '' : 'display: none;' ?>">
                <label>Select Employee</label>
                <select multiple name="targetIds[]" class="select2 form-select" data-live-search="true">
                    <?php foreach ($employees ?? [] as $employee): ?>
                        <option value="<?= htmlspecialchars($employee['id']) ?>">
                            <?= htmlspecialchars($employee['full_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Email Details Card -->
    <div class="card__wrapper p-3 mb-20 shadow-sm">
        <div class="col-lg-12 mb-3">
            <label class="fw-bold mb-1">Select Template</label>
            <select class="form-select" name="email_template_id" id="email_template_select">
                <option value="">Select Email Template</option>
                <?php foreach ($emailTemplates as $et): ?>
                    <option value="<?= $et['id'] ?>">
                        <?= htmlspecialchars($et['name']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="col-lg-12 mb-3">
            <div class="form__input-box">
                <div class="form__input-title">
                    <label for="subject" class="fw-bold mb-1">Subject <span class="text-danger">*</span></label>
                </div>
                <div class="form__input">
                    <input class="form-control" name="subject" id="subject" type="text"
                        placeholder="Enter email subject">
                </div>
            </div>
        </div>

        <div class="form__input-box mb-3">
            <div class="form__input-title">
                <label class="fw-bold mb-1">Body <span class="text-danger">*</span></label>
            </div>
            <div class="form__input-box">
                <textarea id="tinymce_simple_textarea" name="body" class="form-control"></textarea>
            </div>
        </div>

        <div class="submit__btn text-center mt-4">
            <button type="submit" name="submission_type" value="send" class="btn btn-primary px-4">
                <i class="fa-solid fa-paper-plane me-1"></i>Send
            </button>

            <button type="submit" name="submission_type" value="draft" class="btn btn-warning px-4">
                <i class="fa-solid fa-floppy-disk me-1"></i>Save as Draft
            </button>
        </div>

    </div>
</form>

<!-- Pass PHP Templates Array to JavaScript -->
<script>
    const emailTemplatesMap = <?= json_encode(array_column($emailTemplates ?? [], null, 'id')) ?>;
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Dynamic Filter Section Visibility Logic
        const filterSelect = document.getElementById("filter_type");
        const deptWrapper = document.getElementById("department_wrapper");
        const desigWrapper = document.getElementById("designation_wrapper");
        const empWrapper = document.getElementById("employee_wrapper");

        function toggleFilters() {
            const val = filterSelect.value;
            deptWrapper.style.display = (val === 'department') ? 'block' : 'none';
            desigWrapper.style.display = (val === 'designation') ? 'block' : 'none';
            empWrapper.style.display = (val === 'specific_employees') ? 'block' : 'none';
        }

        if (filterSelect) {
            filterSelect.addEventListener("change", toggleFilters);
        }

        // 2. Auto-Populate Subject & Body Logic
        const templateSelect = document.getElementById("email_template_select");
        const subjectInput = document.getElementById("subject");
        const textareaBody = document.getElementById("tinymce_simple_textarea");

        if (templateSelect) {
            templateSelect.addEventListener("change", function () {
                const templateId = this.value;

                if (templateId && emailTemplatesMap[templateId]) {
                    const selectedTemplate = emailTemplatesMap[templateId];

                    // Populate Subject
                    subjectInput.value = selectedTemplate.subject || '';

                    // Populate TinyMCE Editor or Fallback Textarea
                    const bodyContent = selectedTemplate.body || '';

                    if (window.tinymce && tinymce.get("tinymce_simple_textarea")) {
                        tinymce.get("tinymce_simple_textarea").setContent(bodyContent);
                    } else {
                        textareaBody.value = bodyContent;
                    }
                } else {
                    // Clear fields if default option chosen
                    subjectInput.value = '';
                    if (window.tinymce && tinymce.get("tinymce_simple_textarea")) {
                        tinymce.get("tinymce_simple_textarea").setContent('');
                    } else {
                        textareaBody.value = '';
                    }
                }
            });
        }
    });
</script>