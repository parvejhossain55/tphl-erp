<?php
$page_title = "CSV Bulk Import Preview";
?>
<style>
    .main-content {
    margin-left: 90px;
    min-height: 100vh;
    padding: 0;
}
</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-10">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-5">CSV Bulk Import Preview</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb mb-5">
                                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/lead-lists">Leads</a></li>
                                <li class="breadcrumb-item active">Import Preview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Summary -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Import Summary for <?= htmlspecialchars($filename) ?></h5>
                            <?php if (empty($duplicates)): ?>
                                <div class="alert mb-0 border" style="background:rgba(10,179,156,.1); border-color:rgba(10,179,156,.3) !important; color:#10c469;">
                                    <i class="ri-checkbox-circle-line me-2"></i> All <?= $valid_count ?> rows are ok and ready to import.
                                </div>
                            <?php else: ?>
                                <div class="alert mb-3 border" style="background:rgba(245,158,11,.1); border-color:rgba(245,158,11,.3) !important; color:#f9b300;">
                                    <i class="ri-alert-line me-2"></i> Found <?= count($duplicates) ?> duplicate leads matching existing names and phone numbers.
                                </div>
                                <div class="alert mb-0 border" style="background:rgba(10,179,156,.1); border-color:rgba(10,179,156,.3) !important; color:#10c469;">
                                    <i class="ri-checkbox-circle-line me-2"></i> <?= $valid_count ?> rows are ok and ready to import.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($duplicates)): ?>
                <!-- Duplicate Resolution Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Duplicate Leads</h5>
                                <button type="button" class="btn btn-sm btn-warning" id="ignore-all-csv-btn">
                                    <i class="ri-close-circle-line align-bottom me-1"></i>Delete All from CSV
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 80px;">CSV Row</th>
                                                <th scope="col">CSV Lead Data</th>
                                                <th scope="col">Existing ERP Data</th>
                                                <th scope="col" style="width: 250px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($duplicates as $duplicate): ?>
                                                <tr id="duplicate-row-<?= $duplicate['csv_row'] ?>">
                                                    <td>
                                                        <span class="badge bg-secondary">#<?= htmlspecialchars($duplicate['csv_row']) ?></span>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($duplicate['csv_data']['full_name'] ?? '') ?></h6>
                                                        <span class="text-muted"><i class="ri-phone-line align-bottom me-1"></i> <?= htmlspecialchars($duplicate['csv_data']['phone'] ?? $duplicate['csv_data']['phone_number'] ?? '') ?></span>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($duplicate['db_data']['full_name'] ?? '') ?></h6>
                                                        <span class="text-muted"><i class="ri-phone-line align-bottom me-1"></i> <?= htmlspecialchars($duplicate['db_data']['phone_number'] ?? $duplicate['db_data']['phone'] ?? '') ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-danger delete-erp-btn" data-erp-id="<?= $duplicate['db_data']['id'] ?>" data-row-id="<?= $duplicate['csv_row'] ?>">
                                                                <i class="ri-delete-bin-line align-bottom me-1"></i>Delete from ERP Lead
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning ignore-csv-btn" data-row-id="<?= $duplicate['csv_row'] ?>">
                                                                <i class="ri-close-circle-line align-bottom me-1"></i>Delete from CSV Lead
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12 text-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-light me-2">Cancel</a>
                    <form action="<?= $GLOBALS['base_url'] ?>/bulk-leads-import-execute" method="POST" class="d-inline">
                        <button type="submit" class="btn btn-primary" id="btn-import-leads">
                            <i class="ri-file-upload-line align-middle me-1"></i> Import Leads
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const base_url = "<?= $GLOBALS['base_url'] ?>";

        document.querySelectorAll('.delete-erp-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const erpId = this.getAttribute('data-erp-id');
                const rowId = this.getAttribute('data-row-id');

                if (confirm('Are you sure you want to delete this lead from the ERP?')) {
                    fetch(`${base_url}/delete-lead-ajax`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${erpId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Visually remove the row or mark it as resolved
                            document.getElementById(`duplicate-row-${rowId}`).remove();
                            alert('ERP lead deleted successfully. The CSV row will now be imported as a new lead.');
                        } else {
                            alert(data.message || 'Error deleting lead');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('An error occurred');
                    });
                }
            });
        });

        document.querySelectorAll('.ignore-csv-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const rowId = this.getAttribute('data-row-id');

                if (confirm('Are you sure you want to ignore this row from the CSV upload?')) {
                    fetch(`${base_url}/ignore-csv-lead-ajax`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `row_id=${rowId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById(`duplicate-row-${rowId}`).remove();
                            alert('CSV row ignored successfully.');
                        } else {
                            alert(data.message || 'Error ignoring row');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('An error occurred');
                    });
                }
            });
        });

        const ignoreAllBtn = document.getElementById('ignore-all-csv-btn');
        if (ignoreAllBtn) {
            ignoreAllBtn.addEventListener('click', function() {
                const buttons = document.querySelectorAll('.ignore-csv-btn');
                if (buttons.length === 0) return;

                if (confirm('Are you sure you want to delete ALL duplicate rows from the CSV upload?')) {
                    const rowIds = Array.from(buttons).map(btn => btn.getAttribute('data-row-id'));
                    const formData = new URLSearchParams();
                    rowIds.forEach(id => formData.append('row_ids[]', id));

                    fetch(`${base_url}/ignore-csv-lead-ajax`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rowIds.forEach(id => {
                                const row = document.getElementById(`duplicate-row-${id}`);
                                if (row) row.remove();
                            });
                            alert('All CSV duplicates deleted successfully.');
                            
                            // Hide the button since there are no more duplicates
                            ignoreAllBtn.style.display = 'none';
                        } else {
                            alert(data.message || 'Error deleting rows');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('An error occurred');
                    });
                }
            });
        }
    });
</script>
