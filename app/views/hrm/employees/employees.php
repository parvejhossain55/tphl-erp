<?php
$page_title = "employees lists ";
$page = 'Employee Lists';
$show_breadcrumb = true;

?>
<form action="" method="get" class="mb-20">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Employee Directory</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $GLOBALS['base_url'] ?>/employees-pdf-download?<?= $_SERVER['QUERY_STRING'] ?? '' ?>" class="btn btn-sm btn-outline-danger shadow-sm">
                <i class="fa-solid fa-file-pdf me-1"></i> PDF Download
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/employees-excel-download?<?= $_SERVER['QUERY_STRING'] ?? '' ?>" class="btn btn-sm btn-outline-success shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Excel Download
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/create-employee" class="btn btn-sm btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Add Employee
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
                        
                    <div id="employeeSuggestions" class="dropdown-menu w-100 shadow-sm" style="display:none; position: absolute; max-height:250px; overflow-y:auto; top:100%; left:0; z-index:1050; padding:0; margin-top:2px;">
                    </div>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                <select class="form-select" name="department_id">
                    <option value="">Employee Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>"
                            <?= $selectedDepartment == $department['id'] ? 'selected' : '' ?>>
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
                        <option value="<?= $team['id'] ?>"
                            <?= ($selectedTeam ?? '') == $team['id'] ? 'selected' : '' ?>>
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
                        <option value="<?= htmlspecialchars($wp['workplace']) ?>"
                            <?= ($selectedWorkplace ?? '') === $wp['workplace'] ? 'selected' : '' ?>>
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

<?php
$currentQuery = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$listReturnUrl = $GLOBALS['base_url'] . '/employee-lists' . $currentQuery;
?>
<div class="row align-items-stretch">
    <?php foreach ($employees as $employee): ?>
        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 px-2">
            <div class="card__wrapper">
                <div class="employee__wrapper text-center">
                    <div class="employee__thumb mb-15">
                        <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>">
                            <img src="<?= getImage('employees', $employee['photo']) ?? '' ?>" alt="image"></a>
                    </div>
                    <div class="employee__content">
                        <div class="employee__meta mb-15">
                            <h4 class="mb-8"><a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>"><?= $employee['full_name'] ?? '' ?></a></h4>
                            <p><?= $employee['department_name'] ?> - <strong><?= $employee['designation_name'] ?? '' ?></strong></p>

                            <p>Employee ID - <?= $employee['employee_id'] ?> 
                            <?php if (!empty($employee['team_name'])): ?>
                               - <strong><?= $employee['team_name'] ?></strong>
                            <?php endif; ?>
                            </p>
                            <p class="fs-12 text-primary mb-1"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($employee['office_branch_name'] ?? 'Ashulia Site Office') ?></p>
                        </div>
                        <div class="common-social mb-20">
                            <a href="<?= formatUrl($employee['social_media1']) ?>"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="<?= formatUrl($employee['social_media2']) ?>" target="_blank" rel="noopener" title="LinkedIn">
                                <i class="fa-brands fa-linkedin"></i>
                            </a>
                            <a href="tel:<?= htmlspecialchars($employee['phone']) ?>" title="Call">
                                <i class="fa-solid fa-phone"></i>

                            </a>

                        </div>
                        <div class="employee__btn">
                            <div class="d-flex align-items-center justify-content-center gap-10">

                                <a class="btn btn-outline-theme-border" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>">View</a>
                                <!-- <a href="< $GLOBALS['base_url'] ?>/edit-employee?id=< $employee['id'] ?>">Edit</a> -->
                                <a class="btn btn-outline-theme-border" href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>?return_url=<?= urlencode($listReturnUrl) ?>">Edit</a>
                                <button class="removeBtnCust btn btn-outline-theme-border delete"
                                    data-id="<?= $employee['id'] ?>"
                                    data-name="<?= $employee['full_name'] ?>"
                                    data-action="delete-employee">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>


    <!-- Count Info + Pagination -->
    <?php
        $currentPageCount = count($employees);
        $startRecord = $totalEmployees > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $endRecord   = min($startRecord + $currentPageCount - 1, $totalEmployees);
    ?>
    <div class="row mt-4 mb-2 align-items-center">
        <div class="col-12">
            <div class="card__wrapper py-2 px-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-primary fs-5"></i>
                        <span class="fw-semibold">
                            Showing
                            <span class="text-primary fw-bold"><?= $startRecord ?></span>
                            –
                            <span class="text-primary fw-bold"><?= $endRecord ?></span>
                            of
                            <span class="text-success fw-bold"><?= $totalEmployees ?></span>
                            employees
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 13px;">
                        <i class="bi bi-file-earmark-person"></i>
                        Page <strong><?= $currentPage ?></strong> of <strong><?= $totalPages ?></strong>
                        &nbsp;|&nbsp;
                        <strong><?= $currentPageCount ?></strong> shown on this page
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-center">
            <?= paginateLinksSecondary($paginationRoute ?? ($base_url . "/employee-lists"), $currentPage, $totalPages); ?>
        </div>
    </div>
</div>
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('employeeName');
        const suggestionsBox = document.getElementById('employeeSuggestions');
        let timeout = null;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                fetch(`${base_url}/api/employee/search-suggestions?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(emp => {
                                const item = document.createElement('a');
                                item.className = 'dropdown-item px-3 py-2 border-bottom';
                                item.style.cursor = 'pointer';
                                item.innerHTML = `<strong>${emp.full_name}</strong> <small class="text-muted">(${emp.employee_id || 'N/A'})</small><br><small class="text-primary">@${emp.username || 'N/A'}</small>`;
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    searchInput.value = emp.full_name;
                                    suggestionsBox.style.display = 'none';
                                    searchInput.closest('form').submit(); // optional: auto-submit on click
                                });
                                suggestionsBox.appendChild(item);
                            });
                            suggestionsBox.style.display = 'block';
                        } else {
                            suggestionsBox.innerHTML = '<div class="px-3 py-2 text-muted">No suggestions found</div>';
                            suggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error("Error fetching suggestions", err));
            }, 300);
        });

        // Hide when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
    });
</script>