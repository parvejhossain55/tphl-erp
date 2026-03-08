<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>


<!-- Stats Grid -->
<div class="row  mb-4 align-items-stretch">
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Contacts</div>
            <div class="stat-value">12</div>
            <div class="stat-change positive d-none">
                <span class=">">
                    <i class="fa-light "></i>
                    34%
                </span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Tagged Buyers</div>
            <div class="stat-value">3</div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 3.1%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Active Leads</div>
            <div class="stat-value">34</div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.2%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Investors</div>
            <div class="stat-value">345</div>
            <div class="stat-change negative d-none">
                <i class="bi bi-arrow-down"></i>
            </div>
        </div>
    </div>



    <!-- Desktop View: Filters and Table -->
    <div class="desktop-only">
        <!-- Filters -->
        <div class="filters">
            <form action="" method="get">
                <div class="row g-2 align-items-center flex-wrap">

                    <!-- Search Contact -->
                    <div class="col-sm-8 col-md-6 col-lg-4">
                        <input
                            class="custom-search-input"
                            type="search"
                            placeholder="Search Contact...">
                    </div>

                    <!-- Search Button -->
                    <div class="col-sm-2 col-md-3 col-lg-2">
                 
                        <button type="submit" class="btn btn-primary py-2">
                                <i class="fa-light fa-search me-2"></i> Search
                            </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-auto ms-auto d-flex gap-2 flex-wrap">

                         <button type="button" class="btn btn-outline-secondary btn-sm" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Export 
                        </button>

                        <!-- Import CSV button outside form submit -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import CSV
                        </button>

                        <a href="/create-lead" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>
                    </div>

                </div>
            </form>
        </div>
        <!-- Table -->
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table class="table no-border-table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th style="min-width:60px;">
                                    <input type="checkbox" id="selectAllLeads" title="Select All">
                                    S/L
                                </th>
                                <th> Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Location</th>
                                <th>Source</th>
                                <th>Tags</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-id="8" class="odd">
                                <td class="sorting_1">
                                    <input type="checkbox" class="me-2 selectedId" data-id="8">
                                    1
                                </td>
                                <td><strong>Shariful Islam Sharif</strong></td>
                                <td>

                                    <a href="https://wa.me/8801781860471" target="_blank">
                                        8801781860471 </a>
                                </td>


                                <td> sharif@gmail.com </td>
                                <td> Dhaka </td>

                                <!-- last_action_form  -->
                                <!-- New Last Action Update Button -->

                                <!-- last_action_form  -->
                                <td>
                                    <div>
                                        23 February, 2026 </div>
                                </td>
                                <td>

                                    <form method="POST" action="/TPHL-ERP/update-last_leadAction/8" class="m-0 p-0">

                                        <select name="last_lead_action" class="form-select form-select-sm last-action-select action-follow up" onchange="this.form.submit()">

                                            <option value=""> Update action </option>
                                            <option value="new">
                                                New
                                            </option>

                                            <option value="booked">
                                                Booked
                                            </option>

                                            <option value="follow up" selected="" class="action-follow_up">
                                                Follow Up
                                            </option>


                                            <option value="visited">
                                                Visited
                                            </option>
                                            <option value="cancel">
                                                Cancel
                                            </option>

                                        </select>

                                    </form>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">


                                        <!-- 3 Dot Dropdown -->
                                        <div class="dropdown">
                                            <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown__item" href="/TPHL-ERP/edit-lead/8">
                                                        <i class="bi bi-pencil-square me-2 text-primary"></i> Edit
                                                    </a>
                                                </li>

                                                <li>
                                                    <a class="dropdown__item" href="/TPHL-ERP/view-lead/8">
                                                        <i class="bi bi-eye me-2 text-success"></i> View
                                                    </a>
                                                </li>

                                                <li>
                                                    <buton class="dropdown__item text-danger removeBtnCust" data-name="Shariful Islam Sharif" data-action="lead-delete" data-id="8">
                                                        <i class="bi bi-trash me-2"></i> Delete
                                                    </buton>
                                                </li>
                                            </ul>
                                        </div>

                                    </div>
                                </td>


                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Mobile View: Filter Button and Cards -->
    <div class="mobile-only">
        <!-- Filter & Sort Button -->

        <!-- Filter Toggle Button -->
        <button class="filter-button w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilterCollapse" aria-expanded="false" aria-controls="mobileFilterCollapse">
            <i class="bi bi-funnel"></i> Filter & Sort
        </button>

        <!-- Collapsible Filter Section -->
        <div class="collapse" id="mobileFilterCollapse">
            <form action="" method="get" class="p-2 border rounded ">
                <div class="d-flex flex-column gap-2">

                    <!-- Year Select -->
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $slectedYear ?? '';
                    ?>
                    <select class="form-control form-select-sm" name="year">
                        <option value="">Year Select</option>
                        <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                            <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endfor; ?>
                    </select>

                    <!-- Month Select -->
                    <?php
                    $months = [
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    ];
                    $selectedMonth = $_GET['month'] ?? '';
                    ?>
                    <select class="form-control form-select-sm" name="month">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status Select -->
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Status: All</option>
                        <option value="new" <?= ($selectedAction ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                        <option value="booked" <?= ($selectedAction ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="follow up" <?= ($selectedAction ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                        <option value="visited" <?= ($selectedAction ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                        <option value="cancel" <?= ($selectedAction ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1">Search</button>
                        <a href="/lead-lists" class="btn btn-primary btn-sm flex-grow-1">Reset</a>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" data-bs-toggle="modal" data-bs-target="#importCsvModal">Import CSV</button>


                        <a href="/create-lead" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-plus-lg"></i> Add Lead</a>

                    </div>

                </div>
            </form>
        </div>


        <!-- Lead Card -->
        <div class="lead-card" data-id="">
            <div class="lead-card-header d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" class="selectedIdMobile me-2" data-id="">
                    <span class="lead-name">ABC</span>
                    <div class="lead-phone">0171132764</div>
                    <div class="lead-location">Dhaka</div>
                </div>

                <!-- 3 Dot Dropdown as Action Button -->
                <div class="dropdown">
                    <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li>
                            <a class="dropdown__item" href="/edit-lead/">
                                <i class="bi bi-pencil-square me-2 text-primary"></i> Edit
                            </a>
                        </li>
                        <li>
                            <a class="dropdown__item" href="/view-lead/">
                                <i class="bi bi-eye me-2 text-success"></i> View
                            </a>
                        </li>
                        <li>
                            <buton class="dropdown__item text-danger removeBtnCust" data-name="ABC"
                                data-action="lead-delete"
                                data-id="">
                                <i class="bi bi-trash me-2"></i> Delete
                            </buton>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Status -->
            <div class="info-label mt-2">Tag</div>
            <div class="lead-info-row">
                <form method="POST" action="/update-last_leadAction/" class="m-0 p-0">
                    <select name="last_lead_action"
                        class="form-select form-select-sm last-action-select "
                        onchange="this.form.submit()">
                        <option value="">Update action</option>
                        <option value="">New</option>
                    </select>
                </form>


            </div>

            <!-- Project Info -->



        </div>

        <!-- FAB Button -->
        <button class="fab-button d-none">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>


</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Leads from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="/leads-import-csv"
                    method="post"
                    enctype="multipart/form-data">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload CSV File <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input type="file"
                                name="csv_file"
                                class="form-control"
                                accept=".csv"
                                required>
                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Import</button>
                    </div>
                </form>

                <p class="mt-15 text-muted small">
                    CSV header must match lead table columns.
                </p>
            </div>
        </div>
    </div>
</div>





<!-- view full rule description -->
<div id="viewProjectCampaignName" class="modal custom-modal fade">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Project Campaing Name</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <textarea id="view_campaign_id" class="form-control" rows="2" readonly></textarea>

            </div>

        </div>
    </div>
</div>