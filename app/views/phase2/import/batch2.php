<?php require_once __DIR__ . '/../../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<!-- PAGE-HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Batch 2: Pre-Construction Import</h1>
    </div>
    <div class="ms-auto pageheader-btn">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Batch 2 Import</li>
        </ol>
    </div>
</div>
<!-- PAGE-HEADER END -->

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h3 class="card-title">Bulk Import Pre-Construction Data</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Select the type of data you want to import. A file upload field will appear. Make sure your CSV file matches the required column headers.</p>

                <div class="form-group mb-4">
                    <label class="form-label">Select Import Type <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="importTypeSelect">
                        <option value="">-- Select --</option>
                        <option value="land_visibility">Land Visibility Study</option>
                        <option value="soil_test">Soil Test</option>
                        <option value="project_drawing">Project Drawings</option>
                        <option value="architect_engineering">Architect & Engineering</option>
                    </select>
                </div>

                <!-- Land Visibility Study Form -->
                <div class="import-form-wrapper" id="form_land_visibility" style="display:none;">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-land-visibility-import" method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <strong>Required Columns:</strong> Project_Name, Land_Legal_Doc_Status, Digital_Survey_Report_Link, Govt_Approval_Doc_Link, Others_Doc_Link
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import Land Visibility Studies</button>
                    </form>
                </div>

                <!-- Soil Test Form -->
                <div class="import-form-wrapper" id="form_soil_test" style="display:none;">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-soil-test-import" method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <strong>Required Columns:</strong> Project_Name, Testing_Agency, Report_Date, Bearing_Capacity, Report_Link
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import Soil Tests</button>
                    </form>
                </div>

                <!-- Project Drawing Form -->
                <div class="import-form-wrapper" id="form_project_drawing" style="display:none;">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-project-drawing-import" method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <strong>Required Columns:</strong> Project_Name, Design_Type_Name, Version, Submission_Date, Remarks
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import Project Drawings</button>
                    </form>
                </div>

                <!-- Architect Engineering Form -->
                <div class="import-form-wrapper" id="form_architect_engineering" style="display:none;">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-architect-engineering-import" method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <strong>Required Columns:</strong> Project_Name, Consultant_Name, Design_Stage, Submission_Date, Remarks
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import Architect & Engineering</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('importTypeSelect');
    const wrappers = document.querySelectorAll('.import-form-wrapper');

    // If select2 is initialized, hook into its change event
    $('#importTypeSelect').on('change', function() {
        const val = $(this).val();
        wrappers.forEach(w => w.style.display = 'none');
        if (val) {
            const activeForm = document.getElementById('form_' + val);
            if (activeForm) activeForm.style.display = 'block';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
