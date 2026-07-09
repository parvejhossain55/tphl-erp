<?php
$page_title = "User List";
$page = 'Sales User Lists';
$show_breadcrumb = false;
?>
<div class="row mb-4 ">
<div class="d-flex justify-content-evenly align-items-center mb-4">
    <h4 class="mb-0">All Sales Teams</h4>
   
</div>
<form method="GET" action="">
    <div class="row g-20 mb-20 justify-content-between align-items-end">

        <!-- Search by Name / Phone / Email -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4">
            <div class="card__wrapper">
                <div class="search-box">
                    <input type="text" class="form-control" name="q" placeholder="User Name / Phone / Email"
                        value="<?= htmlspecialchars($searchTerm ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Team Filter -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4">
            <div class="card__wrapper">
                <div class="from__input-box">
                    <select class="form-control" name="team_id">
                        <option value="">All Teams</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>" <?= (isset($selectedTeam) && $selectedTeam == $team['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($team['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4">
            <div class="card__wrapper">
                <div class="d-flex align-items-center justify-content-between gap-15">
                    <button type="submit" class="btn btn-secondary">Filters</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/sales-team-view" class="btn btn-primary w-100">Reset</a>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-user" class="d-none btn btn-primary w-100">
                        Add Sales Users
                    </a>
                </div>
            </div>
        </div>

    </div>
</form>

<div class="row">
    <?php if (!empty($salesUsers)): ?>
        <?php foreach ($salesUsers as $user): ?>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6">
                <div class="card__wrapper">
                    <div class="employee__wrapper text-center">
                        <div class="employee__thumb mb-10">

                            <!-- <img src=  "<=getImage('users',filename: $user['photo'])?>" alt="img not found"> -->
                            <img src="<?= getUserProfileImage($user)  ?>" alt="img not found">

                        </div>
                        <div class="employee__content">
                            <div class="employee__meta ">
                                <h4 class="mb-8"><?= htmlspecialchars($user['full_name'] ?: "No Name") ?></h4>
                                <p class="mb-0"><?= htmlspecialchars($user['designation_name'] ?: "No Designation") ?></p>
                                <p class="mb-0"><?= htmlspecialchars($user['email'] ?: "No email") ?></p>
                                <p class="mb-0"> <span class="fw-semibold fs-6 pe-1">Role:</span><?= htmlspecialchars($user['role_name'] ?: "No role ") ?></p>
                                <p class="mb-0"> <span class="fw-semibold fs-6 pe-1">Team:</span><?= htmlspecialchars($user['team_name'] ?: "No Team") ?></p>
                                <p class="mb-2"> <span class="fw-semibold fs-6 pe-1">Total Leads:</span><?= (int)($user['total_assigned_leads'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-danger">No Users Found</p>
    <?php endif; ?>

</div>
<!-- Pagination -->
<div class="row my-4 align-items-center">
    <div class="col-12 col-md-6 text-center text-md-start mb-3 mb-md-0 text-muted">
        <?php 
        $perPage = 10;
        $start = ($currentPage - 1) * $perPage + 1;
        $end = min($currentPage * $perPage, $totalUsers);
        echo "Showing " . ($totalUsers > 0 ? $start : 0) . " to " . $end . " of " . $totalUsers . " entries";
        ?>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-center justify-content-md-end">
        <?= paginateLinksSecondary($base_url . "/sales-team-view", $currentPage, $totalPages); ?>
    </div>
</div>
<script>
    const base_url = "<?=  $GLOBALS['base_url']?>";
</script>