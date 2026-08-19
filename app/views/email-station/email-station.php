<?php
$page_title = "email station ";
$page = 'Email Station';
$show_breadcrumb = true;
?>

<form action="" method="get" class="mb-20">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Email Station</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $GLOBALS['base_url'] ?>/create-email-template" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Add Template
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/create-employee" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Create Email Alert
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card__wrapper p-3 shadow-sm">
        <div class="row g-2 align-items-center">

            <!-- Search -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                <div class="search-box position-relative">
                    <input type="text" class="form-control" name="q" id="employeeName"
                        placeholder="Employee Name / Username / ID" autocomplete="off"
                        value="<?= htmlspecialchars($searchTerm ?? '') ?>">

                    <div id="employeeSuggestions" class="dropdown-menu w-100 shadow-sm"
                        style="display:none; position: absolute; max-height:250px; overflow-y:auto; top:100%; left:0; z-index:1050; padding:0; margin-top:2px;">
                    </div>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                <select class="form-select" name="department_id">
                    <option value="">Employee Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>" <?= $selectedDepartment == $department['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Team Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <select class="form-select" name="team_id">
                    <option value="">All Teams</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>" <?= ($selectedTeam ?? '') == $team['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Workplace Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <select class="form-select" name="workplace">
                    <option value="">All Workplaces</option>
                    <?php foreach ($workplaces ?? [] as $wp): ?>
                        <option value="<?= htmlspecialchars($wp['workplace']) ?>" <?= ($selectedWorkplace ?? '') === $wp['workplace'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wp['workplace']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="col-xxl-2 col-lg-2 col-md-12 col-12">
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-secondary flex-fill text-nowrap">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/employee-lists"
                        class="btn btn-outline-secondary flex-fill text-nowrap">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>