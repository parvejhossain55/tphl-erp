<!-- app-sidebar-start -->
<!-- <pre>
<php print_r($_SESSION['user']['permissions']); ?>
</pre> -->

<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header">
        <a href="?home" class="header-logo">
            <img class="main-logo" src="<?= $GLOBALS['base_url'] ?>/assets/images/logo/tphl_logo.png" alt="logo">
            <img class="dark-logo" src="<?= $GLOBALS['base_url'] ?>/assets/images/logo/logo_white.png" alt="logo">
        </a>
    </div>

    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu">
                <?php if (hasPermission("role & permission")):  ?>
                    <!-- Home -->
                    <li class="slide <?= isActive('home', $current_route) ?>">
                        <a href="<?= $base_url ?>/home" class="sidebar__menu-item <?= isActive('home', $current_route) ?>">
                            <div class="side-menu__icon"><i class="icon-house"></i></div>
                            <span class="sidebar__menu-label">Home</span>
                        </a>
                    </li>
                <?php endif; ?>


                <?php if (hasPermission("sales & business")):  ?>

                    <!--Sales CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">Sales & Business</span>
                    </li>

                    <!-- sales Dashboard -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/sales-dashboard" class="sidebar__menu-item <?= isActive('sales-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Sales Dashboard</span>
                        </a>
                    </li>

                    <!-- Location -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/location-lists" class="sidebar__menu-item <?= isActive('location-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-location-dot"></i></div>
                            <span class="sidebar__menu-label">Location Manage</span>
                        </a>
                    </li>

                    <!-- Community -->
                    <li class="slide">
                        <a href="<?= $base_url ?>/community-lists" class="sidebar__menu-item  <?= isActive('community-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-people-group"></i></div>
                            <span class="sidebar__menu-label">Community Lists</span>
                        </a>
                    </li>

                    <!-- Lead & Booking -->
                    <li class="slide has-sub <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-lead', 'lead-lists', 'onboarding-clients'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">Lead & Booking</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/create-lead" class="sidebar__menu-item <?= isActive('create-lead', $current_route) ?>">Add Lead</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/lead-lists" class="sidebar__menu-item <?= isActive('lead-lists', $current_route) ?>">Lead List</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/onboarding-clients" class="sidebar__menu-item <?= isActive('onboarding-clients', $current_route) ?>">Onboarding Clients</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Project Manage -->
                    <li class="slide has-sub <?= isActive(['create-project', 'project-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-project', 'project-lists'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-building"></i></div>
                            <span class="sidebar__menu-label">Project Manage</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide">
                                <a href="<?= $base_url ?>/create-project" class="sidebar__menu-item <?= isActive('create-project', $current_route) ?>">Add Project</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/project-lists" class="sidebar__menu-item <?= isActive('project-lists', $current_route) ?>">Project List</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Flats Manage -->
                    <li class="slide has-sub <?= isActive(['create-flat', 'flat-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-flat', 'flat-lists'], $current_route) ? 'menu-open active' : '' ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-house"></i></div>
                            <span class="sidebar__menu-label">Flats Manage</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide child2">
                                <a href="<?= $base_url ?>/create-flat" class="sidebar__menu-item <?= isActive('create-flat', $current_route) ?>">Add Flat</a>
                            </li>
                            <li class="slide">
                                <a href="<?= $base_url ?>/flat-lists" class="sidebar__menu-item <?= isActive('flat-lists', $current_route) ?>">Flat List</a>
                            </li>
                        </ul>
                    </li>


                    <!-- Inventroy -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/inventory-lists" class="sidebar__menu-item <?= isActive('inventory-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="bi bi-stack"></i> </div>
                            <span class="sidebar__menu-label">Proejct Wise Inventory</span>
                        </a>
                    </li>

                <?php endif; ?>
                <?php if (hasPermission("add Lead")): ?>
                    <ul class="sidebar-menu child2" style="display:block;">
                        <li class="slide">
                            <a href="<?= $base_url ?>/user-dashboard" class="sidebar__menu-item <?= isActive('user-dashboard', $current_route) ?>">Dashboard</a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/create-lead" class="sidebar__menu-item <?= isActive('create-lead', $current_route) ?>">Add Lead</a>
                        </li>
                        <li class="slide">
                            <a href="<?= $base_url ?>/today-follow-up" class="sidebar__menu-item <?= isActive('today-follow-up', $current_route) ?>">Today Follow Up</a>
                        </li>
                    </ul>
                <?php endif; ?>


                <!--End Sales CATEGORY -->


                <?php if (hasPermission("Hr Manage")):  ?>
                    <!--HRM CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">HR & Payroll </span>
                    </li>

                    <!-- Sales Dashboard -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/hrm-dashboard" class="sidebar__menu-item <?= isActive('hrm-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">HR Dashboard</span>
                        </a>
                    </li>

                    <!-- Location -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/employee-lists" class="sidebar__menu-item <?= isActive('employee-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-users"></i>
                            </div>
                            <span class="sidebar__menu-label">Employee Manage</span>
                        </a>
                    </li>

                    <!-- department -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/departments" class="sidebar__menu-item <?= isActive('departments', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-building"></i></div>
                            <span class="sidebar__menu-label">Department Lists</span>
                        </a>
                    </li>
                    <!-- designation -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/designations" class="sidebar__menu-item <?= isActive('designations', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-id-card"></i></div>
                            <span class="sidebar__menu-label">Designation Lists</span>
                        </a>
                    </li>
                    <li class="slide has-sub <?= isActive(['create-attendance', 'attendance-list'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-attendance', 'attendance-list'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">Attendance Manage</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('create-attendance', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-attendance', $current_route) ?>" href="<?= $base_url ?>/create-attendance">Add Attendance</a>
                            </li>
                            <li class="slide <?= isActive('attendance-list', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('attendance-list', $current_route) ?>" href="<?= $base_url ?>/attendance-list">Attendance List</a>
                            </li>
                        </ul>
                    </li>

                    <li class="slide has-sub <?= isActive(['leave-types', 'leave-lists'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['leave-types', 'leave-lists'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">Leave Manage</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('leave-types', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('leave-types', $current_route) ?>" href="<?= $base_url ?>/leave-types">Add Leave Type</a>
                            </li>
                            <li class="slide <?= isActive('leave-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('leave-lists', $current_route) ?>" href="<?= $base_url ?>/leave-lists">Employee Leave List</a>
                            </li>
                        </ul>
                    </li>


                <?php endif; ?>
                <!--End HRM CATEGORY -->


                <!--Payroll CATEGORY -->
                <?php if (hasPermission("Payroll Manage")):  ?>
                    <li class="sidebar__menu-category ">
                        <span class="category-name">Payroll Manage</span>
                    </li>



                    <!-- payslip items -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/payslip-items" class="sidebar__menu-item <?= isActive('payslip-items', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Payslip Items</span>
                        </a>
                    </li>

                    <!-- Payslip Lists -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/payslip-lists" class="sidebar__menu-item <?= isActive('payslip-lists', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill"></i></div>
                            <span class="sidebar__menu-label">Payslip Lists</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (hasPermission("User Manage")):  ?>
                    <li class="slide has-sub <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                            <span class="sidebar__menu-label">User</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('create-user', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>" href="<?= $base_url ?>/create-user">
                                    Add User
                                </a>
                            </li>
                            <li class="slide <?= isActive('user-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('user-lists', $current_route) ?>" href="<?= $base_url ?>/user-lists">
                                    User List
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php endif; ?>




                <?php if (hasPermission("CED Manage")):  ?>
                    <!--CED CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">CED</span>
                    </li>



                    <!-- Cash In Manage -->
                    <li class="slide has-sub <?= isActive(['clients-cashin-view', 'clients-cash-in-history-view'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['clients-cashin-view', 'clients-cash-in-history-view'], $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                            <span class="sidebar__menu-label">Cash In View</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="<?= isMenuOpen(['clients-cashin-view', 'clients-cashin-history-view'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('clients-cashin-view', $current_route) ?>">
                                <a href="<?= $base_url ?>/clients-cashin-view" class="sidebar__menu-item <?= isActive('clients-cashin-view', $current_route) ?>">Clients</a>
                            </li>

                        </ul>
                    </li>

                <?php endif; ?>
                <!--End HRM CATEGORY -->
                <?php if (hasPermission("accounts manage")):  ?>
                    <!--Accounts CATEGORY -->
                    <li class="sidebar__menu-category">
                        <span class="category-name">Accounts</span>
                    </li>

                    <!-- Accounts Dashboard -->
                    <li class="slide <?= isActive('accounts-dashboard', $current_route) ?>">
                        <a href="<?= $base_url ?>/account-dashboard" class="sidebar__menu-item <?= isActive('account-dashboard', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Accounts Dashboard</span>
                        </a>
                    </li>
                    <li class="slide <?= isActive('central-statement', $current_route) ?>">
                        <a href="<?= $base_url ?>/central-statement" class="sidebar__menu-item <?= isActive('central-statement', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span class="sidebar__menu-label">Central Statement</span>
                        </a>
                    </li>

                    <!-- Cash In Manage -->
                    <li class="slide has-sub <?= isActive(['clients-cash-in', 'management-cash-in'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['clients-cash-in', 'management-cash-in'], $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                            <span class="sidebar__menu-label">Cash In Manage</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="<?= isMenuOpen(['clients-cash-in', 'management-cash-in'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('clients-cash-in', $current_route) ?>">
                                <a href="<?= $base_url ?>/clients-cash-in" class="sidebar__menu-item <?= isActive('clients-cash-in', $current_route) ?>">Clients</a>
                            </li>
                            <li class="slide <?= isActive('management-cash-in', $current_route) ?>">
                                <a href="<?= $base_url ?>/management-cash-in" class="sidebar__menu-item <?= isActive('management-cash-in', $current_route) ?>">Management</a>
                            </li>
                        </ul>
                    </li>
                    <!-- Cash In Manage -->
                    <li class="slide has-sub <?= isActive(['clients-cash-in', 'management-cash-in'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['clients-cash-in', 'management-cash-out'], $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                            <span class="sidebar__menu-label">Manage Cash Out</span>
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                        </a>
                        <ul class="sidebar-menu child2" style="<?= isMenuOpen(['clients-cash-in', 'management-cash-out'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('management-cash-out', $current_route) ?>">
                                <a href="<?= $base_url ?>/management-cash-out" class="sidebar__menu-item <?= isActive('management-cash-out', $current_route) ?>">
                                    <div class="side-menu__icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                                    <span class="sidebar__menu-label">Cash Out Management</span>
                                </a>
                            </li>
                            <li class="slide d-none <?= isActive('management-cash-out', $current_route) ?>">
                                <a href="<?= $base_url ?>/management-cash-out" class="sidebar__menu-item <?= isActive('management-cash-out', $current_route) ?>">Management</a>
                                <span class="sidebar__menu-label">Cash Out </span>
                                </a>
                            </li>
                            <li class="slide <?= isActive('create-cashout-request', $current_route) ?>">
                                <a href="<?= $base_url ?>/create-cashout-request" class="sidebar__menu-item <?= isActive('create-cashout-request', $current_route) ?>">Create Cashout Request</a>
                            </li>
                            <li class="slide <?= isActive('admin-cashout-requests', $current_route) ?>">
                                <a href="<?= $base_url ?>/admin-cashout-requests" class="sidebar__menu-item <?= isActive('admin-cashout-requests', $current_route) ?>">Cashout Requests</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Cash Out -->

                <?php endif ?>
                <?php if (hasPermission("Add User")):  ?>
                    <ul class="sidebar-menu child1" style="display:block;">
                        <li class="slide <?= isActive('create-user', $current_route) ?>">
                            <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>" href="<?= $base_url ?>/create-user">
                                Add User
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <!--END Accounts CATEGORY -->
                <?php if (hasPermission("role & permission")):  ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">Role & Permission</span>
                    </li>

                    <!-- User Submenu -->
                    <li class="slide has-sub <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['create-user', 'user-lists'], $current_route) ? 'active' : '' ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                            <span class="sidebar__menu-label">User</span>
                        </a>
                        <ul class="sidebar-menu child2" style="display:block;">
                            <li class="slide <?= isActive('create-user', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-user', $current_route) ?>" href="<?= $base_url ?>/create-user">
                                    Add User
                                </a>
                            </li>
                            <li class="slide <?= isActive('user-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('user-lists', $current_route) ?>" href="<?= $base_url ?>/user-lists">
                                    User List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Permission -->
                    <li class="slide has-sub <?= isActive(['role-permissions', 'permissions'], $current_route) ? 'active' : '' ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isActive(['role-permissions', 'permissions'], $current_route) ? 'active' : '' ?>">

                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i></div>
                            <span class="sidebar__menu-label">Permission</span>
                        </a>
                        <ul class="sidebar-menu child1 " style="display:block;">
                            <li class="slide <?= isActive('permissions', $current_route) ?>">
                                <a href="<?= $base_url ?>/permissions" class="sidebar__menu-item <?= isActive('permissions', $current_route) ?>"> Permission Lists</a>
                            </li>
                            <li class="slide <?= isActive('role-permissions', $current_route) ?>">
                                <a href="<?= $base_url ?>/role-permissions" class="sidebar__menu-item <?= isActive('role-permissions', $current_route) ?>">Role-Permission</a>
                            </li>
                        </ul>
                    </li>

                <?php endif ?>

                <?php if (hasPermission("task manage")):  ?>
                    <li class="slide has-sub <?= isActive(['create-task', 'task-lists'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['create-task', 'task-lists'], $current_route) ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i>
                            </div>
                            <span class="sidebar__menu-label">Task Manage</span>
                        </a>
                        <ul class="sidebar-menu child1" style="<?= isMenuOpen(['create-task', 'task-lists'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('create-task', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-task', $current_route) ?>" href="<?= $base_url ?>/create-task">Add Task</a>
                            </li>
                            <li class="slide <?= isActive('task-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('task-lists', $current_route) ?>" href="<?= $base_url ?>/task-lists">Task List</a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- Others-->
                <?php if (hasPermission("others")):  ?>
                    <li class="sidebar__menu-category">
                        <span class="category-name">Others</span>
                    </li>


                    <li class="slide has-sub <?= isActive(['create-review', 'review-lists'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['create-review', 'review-lists'], $current_route) ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i>
                            </div>
                            <span class="sidebar__menu-label">Review Manage</span>
                        </a>
                        <ul class="sidebar-menu child1" style="<?= isMenuOpen(['create-review', 'review-lists'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('create-review', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-review', $current_route) ?>" href="<?= $base_url ?>/create-review">Add Review</a>
                            </li>
                            <li class="slide <?= isActive('review-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('review-lists', $current_route) ?>" href="<?= $base_url ?>/review-lists">Review List</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Location -->
                    <li class="slide ">
                        <a href="<?= $base_url ?>/company-bank-accounts" class="sidebar__menu-item <?= isActive('company-bank-accounts', $current_route) ?>">
                            <div class="side-menu__icon"><i class="fa-solid fa-landmark"></i></div>
                            <span class="sidebar__menu-label">Company Bank Accounts</span>
                        </a>
                    </li>


                    <!-- Site Manage -->
                    <li class="slide has-sub <?= isActive(['slider-manage', 'site-settings', 'home-footer-setting'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['slider-manage', 'site-settings', 'home-footer-setting'], $current_route) ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fa-solid fa-gear"></i></div>
                            <span class="sidebar__menu-label">Site Manage</span>
                        </a>
                        <ul class="sidebar-menu child1" style="<?= isMenuOpen(['slider-manage', 'site-settings', 'home-footer-setting'], $current_route) ? 'display:block;' : '' ?>">
                            <li class="slide <?= isActive('slider-manage', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('slider-manage', $current_route) ?>" href="<?= $GLOBALS['base_url'] ?>/slider-manage">Silder Manage </a>
                            </li>
                            <li class="slide <?= isActive('site-settings', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('settings', $current_route) ?>" href="<?= $GLOBALS['base_url'] ?>/site-settings">Setting Manage</a>
                            </li>
                            <li class="slide  <?= isActive('flat-purchase-rules', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('flat-purchase-rules', $current_route) ?>" href="<?= $GLOBALS['base_url'] ?>/flat-purchase-rules">Flat Purchase Rules</a>
                            </li>


                        </ul>
                    </li>

                    <li class="slide has-sub <?= isActive(['create-blog', 'blog-lists'], $current_route) ?>">
                        <a href="javascript:void(0);" class="sidebar__menu-item <?= isMenuOpen(['create-blog', 'blog-lists'], $current_route) ?>">
                            <i class="fa-regular fa-angle-down side-menu__angle"></i>
                            <div class="side-menu__icon"><i class="fas fa-clipboard-list"></i>
                            </div>
                            <span class="sidebar__menu-label">Blog Manage</span>
                        </a>
                        <ul class="sidebar-menu child1" style="<?= isMenuOpen(['create-blog', 'blog-lists'], $current_route) ? 'display:block;' : 'display:none;' ?>">
                            <li class="slide <?= isActive('create-blog', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('create-blog', $current_route) ?>" href="<?= $base_url ?>/create-blog">Add Blog</a>
                            </li>
                            <li class="slide <?= isActive('blog-lists', $current_route) ?>">
                                <a class="sidebar__menu-item <?= isActive('blog-lists', $current_route) ?>" href="<?= $base_url ?>/blog-lists">Blog List</a>
                            </li>
                        </ul>
                    </li>


                <?php endif; ?>

                <li class="slide ">
                    <a class="sidebar__menu-item " href="<?= $GLOBALS['base_url']  ?>/logout"><svg width="18" height="18" viewBox="0 0 18 18"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_643_343)">
                                <path
                                    d="M17.4368 8.43771H10.312C10.0015 8.43771 9.74951 8.18572 9.74951 7.87523C9.74951 7.56474 10.0015 7.31274 10.312 7.31274H17.4368C17.7473 7.31274 17.9993 7.56474 17.9993 7.87523C17.9993 8.18572 17.7473 8.43771 17.4368 8.43771Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M14.6244 11.2502C14.4803 11.2502 14.3364 11.1954 14.2268 11.0852C14.0071 10.8654 14.0071 10.5091 14.2268 10.2894L16.6418 7.87457L14.2268 5.45958C14.0071 5.23986 14.0071 4.88364 14.2268 4.66392C14.4467 4.44406 14.8029 4.44406 15.0226 4.66392L17.835 7.47633C18.0547 7.69605 18.0547 8.05227 17.835 8.27199L15.0226 11.0844C14.9123 11.1954 14.7684 11.2502 14.6244 11.2502Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M5.99986 18.0002C5.83932 18.0002 5.68703 17.9776 5.53488 17.9304L1.02142 16.4267C0.407305 16.2122 0 15.64 0 15.0003V1.50073C0 0.673487 0.672754 0.000732422 1.5 0.000732422C1.66039 0.000732422 1.81269 0.0232537 1.96498 0.0704934L6.4783 1.5742C7.09255 1.7887 7.49972 2.36093 7.49972 3.00059V16.5002C7.49972 17.3274 6.8271 18.0002 5.99986 18.0002ZM1.5 1.1257C1.29374 1.1257 1.12496 1.29447 1.12496 1.50073V15.0003C1.12496 15.16 1.23222 15.3085 1.3852 15.3617L5.8775 16.8587C5.90977 16.8692 5.95179 16.8752 5.99986 16.8752C6.20612 16.8752 6.37475 16.7064 6.37475 16.5002V3.00059C6.37475 2.84088 6.2675 2.69244 6.11452 2.63915L1.62222 1.14218C1.58995 1.13174 1.54793 1.1257 1.5 1.1257Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M11.4371 6.00035C11.1266 6.00035 10.8746 5.74836 10.8746 5.43786V2.06297C10.8746 1.54622 10.454 1.12545 9.93722 1.12545H1.49998C1.18949 1.12545 0.9375 0.873462 0.9375 0.562971C0.9375 0.252479 1.18949 0.000488281 1.49998 0.000488281H9.93722C11.075 0.000488281 11.9996 0.925234 11.9996 2.06297V5.43786C11.9996 5.74836 11.7476 6.00035 11.4371 6.00035Z"
                                    fill="#7A7A7A" />
                                <path
                                    d="M9.93699 15.7501H6.93699C6.6265 15.7501 6.37451 15.4981 6.37451 15.1876C6.37451 14.8771 6.6265 14.6251 6.93699 14.6251H9.93699C10.4537 14.6251 10.8744 14.2044 10.8744 13.6876V10.3127C10.8744 10.0022 11.1264 9.75024 11.4369 9.75024C11.7473 9.75024 11.9993 10.0022 11.9993 10.3127V13.6876C11.9993 14.8254 11.0747 15.7501 9.93699 15.7501Z"
                                    fill="#7A7A7A" />
                            </g>
                            <defs>
                                <clipPath id="clip0_643_343">
                                    <rect width="18" height="18" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                        <span class="sidebar__menu-label ms-2">Log Out</span> </a>
                </li>

            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>

        </nav>

    </div>
</div>
<div class="app__offcanvas-overlay"></div>
<!-- app-sidebar-end -->