

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
                                    <label class="form-label">Project <span class="text-danger">*</span></label>
                                    <select name="project_id" class="form-select select2" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cost Type <span class="text-danger">*</span></label>
                                    <select name="rcost_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="electricity_bill">Electricity Bill</option>
                                        <option value="entertainment_bill">Entertainment Bill</option>
                                        <option value="internet_bill">Internet Bill</option>
                                        <option value="conveyance_bill">Conveyance Bill</option>
                                        <option value="labour_bill">Labour Bill</option>
                                        <option value="others">Others</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Request Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="request_amount" class="form-control" required placeholder="0.00">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Attachment (Optional)</label>
                                <input type="file" name="creator_attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Purpose / Description <span class="text-danger">*</span></label>
                                <textarea name="purpose" class="form-control" rows="4" required placeholder="Enter purpose of the cost"></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Submit Request</button>
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
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
    });
</script>


