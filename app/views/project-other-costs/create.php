

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1>Create Other Cost Request</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/project-other-costs">Project Other Costs</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs" class="btn btn-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body mt-3">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= $GLOBALS['base_url'] ?>/project-other-costs/store" method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 fw-semibold">Project(s) <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" id="pageSelectAllProjects" style="font-size: 0.75rem;">Select All</button>
                                            <span class="text-muted" style="font-size: 0.75rem;">|</span>
                                            <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none text-muted" id="pageClearAllProjects" style="font-size: 0.75rem;">Clear</button>
                                        </div>
                                    </div>
                                    <select name="project_ids[]" id="pageProjectSelect" class="form-select select2" multiple="multiple" required data-placeholder="Select single or multiple projects...">
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" data-name="<?= htmlspecialchars($project['name']) ?>"><?= htmlspecialchars($project['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted mt-1" style="font-size: 0.78rem;">
                                        <i class="bi bi-info-circle me-1 text-primary"></i>Select 1 or multiple projects. Total amount will be distributed equally per project.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Cost Type <span class="text-danger">*</span></label>
                                    <select name="rcost_type" id="pageCostTypeSelect" class="form-select select2" required style="width: 100%;" data-placeholder="Select or search cost type...">
                                        <option value="">Select or search cost type...</option>
                                        <?php 
                                        $types = $costTypes ?? (class_exists('ProjectOtherCost') ? ProjectOtherCost::getCostTypes() : []);
                                        foreach ($types as $val => $label): 
                                        ?>
                                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'othercostcreate') ?>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Total Request Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold bg-light">৳</span>
                                    <input type="number" step="0.01" min="0.01" name="request_amount" id="pageRequestAmount" class="form-control" required placeholder="0.00">
                                </div>

                                <!-- Live Project Distribution Preview -->
                                <div id="pageDistributionPreview" class="alert alert-info py-2 px-3 mt-2 mb-0 border-info-subtle rounded-3" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span class="extra-small">
                                            <i class="bi bi-calculator me-1 text-primary"></i>
                                            <strong>Cost Distribution:</strong> 
                                            <span id="pageDistPerProject" class="fw-bold text-success fs-14">৳ 0.00</span> / project 
                                            (<span id="pageDistProjectCount" class="fw-semibold">0</span> projects selected)
                                        </span>
                                        <span class="badge bg-primary px-2 py-1" id="pageDistTotalBadge">Total: ৳ 0.00</span>
                                    </div>
                                    <div class="mt-2 pt-1 border-top border-info-subtle d-flex flex-wrap gap-1" id="pageDistList">
                                        <!-- Dynamic project chips with allocated amounts -->
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Attachment (Optional)</label>
                                <input type="file" name="creator_attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Purpose / Description <span class="text-danger">*</span></label>
                                <textarea name="purpose" class="form-control" rows="3" required placeholder="Enter purpose of the cost"></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check2-circle me-1"></i> Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('#pageCostTypeSelect').select2({
                placeholder: 'Select or search cost type...',
                allowClear: true,
                width: '100%'
            });
            $('#pageProjectSelect').select2({
                placeholder: 'Select single or multiple projects...',
                width: '100%'
            });
        }

        // Live Cost Distribution Calculation Helper
        function updatePageDistribution() {
            const select = $('#pageProjectSelect');
            const selectedOptions = select.find('option:selected');
            const count = selectedOptions.length;
            const totalAmount = parseFloat($('#pageRequestAmount').val()) || 0;
            const previewEl = $('#pageDistributionPreview');

            if (count > 0 && totalAmount > 0) {
                const perProject = (totalAmount / count);
                $('#pageDistPerProject').text('৳ ' + perProject.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#pageDistProjectCount').text(count);
                $('#pageDistTotalBadge').text('Total: ৳ ' + totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                let chipsHtml = '';
                selectedOptions.each(function() {
                    const pName = $(this).data('name') || $(this).text();
                    chipsHtml += `<span class="badge bg-white text-dark border extra-small px-2 py-1"><i class="bi bi-building text-primary me-1"></i>${pName}: <strong class="text-success">৳ ${perProject.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>`;
                });
                $('#pageDistList').html(chipsHtml);
                previewEl.slideDown(150);
            } else {
                previewEl.slideUp(150);
            }
        }

        // Bind events for live calculation
        $('#pageProjectSelect').on('change', updatePageDistribution);
        $('#pageRequestAmount').on('input', updatePageDistribution);

        // Select All / Clear buttons
        $('#pageSelectAllProjects').on('click', function(e) {
            e.preventDefault();
            $('#pageProjectSelect option').prop('selected', true);
            $('#pageProjectSelect').trigger('change');
        });

        $('#pageClearAllProjects').on('click', function(e) {
            e.preventDefault();
            $('#pageProjectSelect option').prop('selected', false);
            $('#pageProjectSelect').trigger('change');
        });
    });
</script>

<style>
    /* Select2 Multi-Select Choice Badges */
    .select2-container--default .select2-selection--multiple {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        min-height: 40px !important;
        padding: 4px 8px !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        box-sizing: border-box !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e8f5e9 !important;
        border: 1px solid #a3cfbb !important;
        border-radius: 6px !important;
        color: #0f5132 !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        padding: 3px 10px 3px 24px !important;
        margin: 2px 4px 2px 0 !important;
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        color: #0f5132 !important;
        font-weight: 600 !important;
        padding-left: 2px !important;
        padding-right: 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        position: absolute !important;
        left: 6px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        border: none !important;
        background: transparent !important;
        color: #0f5132 !important;
        font-size: 14px !important;
        font-weight: bold !important;
        line-height: 1 !important;
        padding: 0 !important;
        cursor: pointer !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #dc3545 !important;
        background: transparent !important;
    }
    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
        margin: 0 !important;
        padding: 2px 6px !important;
        height: 28px !important;
        line-height: 28px !important;
        font-size: 13px !important;
        color: #333333 !important;
        width: auto !important;
        min-width: 60px !important;
        resize: none !important;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgba(25, 135, 84, 0.25) !important;
        border-color: rgba(25, 135, 84, 0.45) !important;
        color: #86efac !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice__display,
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #86efac !important;
    }
    /* Single Select (Cost Type & COA) */
    .select2-container--default .select2-selection--single {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        height: 40px !important;
        padding: 5px 12px !important;
        display: flex !important;
        align-items: center !important;
        box-sizing: border-box !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 0 !important;
        color: #212529 !important;
        font-size: 14px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
        right: 10px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }

    /* Dark Mode Enhancements for Select2 */
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single {
        background-color: #1b2430 !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #94a3b8 transparent transparent transparent !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown {
        background-color: #1b2430 !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown .select2-search__field {
        background-color: #0f172a !important;
        border: 1px solid #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-results__option {
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-results__option--highlighted,
    [bd-theme="bd-theme-dark"] .select2-results__option--highlighted[aria-selected] {
        background-color: rgba(25, 135, 84, 0.25) !important;
        color: #34d399 !important;
    }
</style>


