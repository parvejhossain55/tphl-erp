<?php
$page_title = "Teams Management";
$page = 'Teams';
$addPage = true;
$show_breadcrumb = true;
?>

<style>
    /* Team Card Styles */
    .team-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        background: #ffffff;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .team-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    .team-card-header {
        padding: 16px 18px 12px 18px;
        border-bottom: 1px solid #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .team-card-body {
        padding: 16px 18px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .team-card-leader-box {
        background: #fff9e6;
        border: 1px solid #ffe8a1;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .team-card-leader-box.no-leader {
        background: #f8f9fa;
        border: 1px dashed #dee2e6;
    }
    .team-card-leader-box .leader-crown {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #ffd166;
        color: #855d00;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .team-card-members-box {
        flex: 1;
    }
    .member-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f1f3f5;
        color: #495057;
        border-radius: 16px;
        padding: 3px 9px;
        font-size: 11.5px;
        font-weight: 500;
        margin: 2px;
        border: 1px solid #e9ecef;
    }
    .member-chip.is-leader-chip {
        background: #fff3cd;
        color: #856404;
        border-color: #ffeeba;
        font-weight: 600;
    }
    .team-card-footer {
        padding: 12px 18px;
        background: #fafbfc;
        border-top: 1px solid #f1f3f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .team-member-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .team-member-badge:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Modern Inline Member & Leader Selection Widget */
    .team-member-manager-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
    }
    .employee-list-scroll {
        max-height: 320px;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .employee-select-row {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.15s ease;
        cursor: pointer;
        user-select: none;
    }
    .employee-select-row:last-child {
        border-bottom: none;
    }
    .employee-select-row:hover {
        background-color: #f8fafc;
    }
    .employee-select-row.row-selected {
        background-color: #f0fdf4;
    }
    .employee-select-row.row-leader {
        background-color: #fffbeb !important;
        border-left: 4px solid #f59e0b !important;
    }
    .leader-btn-pill {
        font-size: 12px;
        font-weight: 600;
        padding: 5px 14px;
        border-radius: 20px;
        cursor: pointer;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #4b5563;
        transition: all 0.15s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        user-select: none;
    }
    .leader-btn-pill:hover {
        border-color: #f59e0b;
        color: #b45309;
        background: #fffbeb;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
    }
    .leader-btn-pill.active-leader {
        background: #fef3c7 !important;
        border-color: #f59e0b !important;
        color: #92400e !important;
        box-shadow: 0 1px 4px rgba(245, 158, 11, 0.3) !important;
        font-weight: 700 !important;
    }
    .custom-modal .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
    }
</style>

<!-- Top Actions Bar -->
<div class="row align-items-center mb-4 g-2">
    <div class="col-md-6 col-lg-4">
        <div class="position-relative">
            <input type="text" id="teamSearchFilter" class="form-control" placeholder="Search team, leader or member...">
            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 14px; top: 12px;"></i>
        </div>
    </div>
    <div class="col-md-6 col-lg-8 text-md-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewTeam">
            <i class="fa-solid fa-plus me-1"></i> Add Team
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. TEAM CARDS SECTION (Top Grid)                                          -->
<!-- ========================================================================= -->
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fa-solid fa-users-rectangle text-primary me-2"></i> Sales Teams Overview
        </h5>
        <span class="badge bg-primary rounded-pill px-3 py-2 fs-12">
            <?= count($teams) ?> Teams Total
        </span>
    </div>

    <div class="row gy-20" id="teamCardsContainer">
        <?php foreach ($teams as $team): ?>
            <?php 
                $members = $team['members'] ?? [];
                $memberCount = count($members);
                $memberIds = $team['member_ids'] ?? [];
                $leader = $team['leader'] ?? null;
                $leaderId = $team['leader_id'] ?? 0;
                $searchKeywords = strtolower($team['name'] . ' ' . ($team['leader_name'] ?? '') . ' ' . implode(' ', array_column($members, 'full_name')));
            ?>
            <div class="col-xxl-3 col-xl-4 col-md-6 col-sm-12 team-card-wrapper" data-search="<?= htmlspecialchars($searchKeywords) ?>">
                <div class="card h-100 team-card">
                    <!-- Header -->
                    <div class="team-card-header">
                        <div>
                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($team['name']) ?></h6>
                            <?php if ($team['status'] == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-1 px-2" style="font-size: 11px;">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill py-1 px-2" style="font-size: 11px;">Inactive</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle p-1" style="width: 32px; height: 32px;"
                                data-bs-toggle="modal"
                                data-bs-target="#editTeam"
                                data-id="<?= $team['id'] ?>"
                                data-name="<?= htmlspecialchars($team['name']) ?>"
                                data-status="<?= $team['status'] ?>"
                                data-leader-id="<?= $leaderId ?>"
                                data-members='<?= htmlspecialchars(json_encode($memberIds), ENT_QUOTES, 'UTF-8') ?>'
                                title="Edit Team">
                                <i class="fa-sharp fa-light fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 removeBtnCust" style="width: 32px; height: 32px;"
                                data-id="<?= $team['id'] ?>"
                                data-name="<?= htmlspecialchars($team['name']) ?>"
                                data-action="delete-team"
                                title="Delete Team">
                                <i class="fa-regular fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="team-card-body">
                        <!-- Leader Box -->
                        <?php if ($leader): ?>
                            <div class="team-card-leader-box">
                                <div class="leader-crown" title="Sales Team Leader">
                                    <i class="fa-solid fa-crown"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Team Leader</span>
                                    <strong class="text-dark d-block text-truncate" title="<?= htmlspecialchars($leader['full_name']) ?>">
                                        <?= htmlspecialchars($leader['full_name']) ?>
                                    </strong>
                                    <small class="text-muted">ID: <?= htmlspecialchars($leader['employee_id']) ?></small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="team-card-leader-box no-leader">
                                <div class="leader-crown" style="background: #e9ecef; color: #6c757d;">
                                    <i class="fa-regular fa-user-xmark"></i>
                                </div>
                                <div>
                                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px; letter-spacing: 0.5px;">Team Leader</span>
                                    <span class="text-muted small fst-italic">No Leader Assigned</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Members List -->
                        <div class="team-card-members-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Team Members</span>
                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-2" style="font-size: 11px;">
                                    <?= $memberCount ?> <?= $memberCount === 1 ? 'Person' : 'Persons' ?>
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $m): ?>
                                        <?php $isL = (int)($m['is_team_leader'] ?? 0) === 1; ?>
                                        <span class="member-chip <?= $isL ? 'is-leader-chip' : '' ?>" title="<?= htmlspecialchars($m['full_name']) ?>">
                                            <?php if ($isL): ?>
                                                <i class="fa-solid fa-crown text-warning me-1" style="font-size: 10px;"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-user text-muted me-1" style="font-size: 10px;"></i>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($m['full_name']) ?></span>
                                            <span class="opacity-75">[<?= htmlspecialchars($m['employee_id']) ?>]</span>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic py-2">No members assigned to this team yet.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="team-card-footer">
                        <?php if ($memberCount > 0): ?>
                            <button type="button" 
                                    class="btn btn-sm btn-link text-decoration-none p-0 text-primary fw-semibold fs-12 view-members-btn"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewMembersModal"
                                    data-team-name="<?= htmlspecialchars($team['name']) ?>"
                                    data-members='<?= htmlspecialchars(json_encode($members), ENT_QUOTES, 'UTF-8') ?>'>
                                <i class="fa-solid fa-eye me-1"></i> View All Members
                            </button>
                        <?php else: ?>
                            <span class="text-muted fs-12">Empty Team</span>
                        <?php endif; ?>

                        <button type="button" 
                                class="btn btn-sm btn-outline-primary py-1 px-3 fs-12 rounded-pill"
                                data-bs-toggle="modal"
                                data-bs-target="#editTeam"
                                data-id="<?= $team['id'] ?>"
                                data-name="<?= htmlspecialchars($team['name']) ?>"
                                data-status="<?= $team['status'] ?>"
                                data-leader-id="<?= $leaderId ?>"
                                data-members='<?= htmlspecialchars(json_encode($memberIds), ENT_QUOTES, 'UTF-8') ?>'>
                            <i class="fa-regular fa-user-pen me-1"></i> Manage
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. TEAM TABLE VIEW                                                        -->
<!-- ========================================================================= -->
<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card-header bg-transparent border-0 px-0 pt-0 pb-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-table-list text-primary me-2"></i> All Teams Table List
                </h6>
            </div>
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>S/L</th>
                            <th>Team Name</th>
                            <th>Team Leader</th>
                            <th>Assigned Members</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($teams as $index => $team): ?>
                            <?php 
                                $members = $team['members'] ?? [];
                                $memberCount = count($members);
                                $memberIds = $team['member_ids'] ?? [];
                                $leader = $team['leader'] ?? null;
                                $leaderId = $team['leader_id'] ?? 0;
                            ?>
                            <tr>
                                <td>#<?= $index + 1 ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($team['name']) ?></td>
                                <td>
                                    <?php if ($leader): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-2 px-3 rounded-pill">
                                            <i class="fa-solid fa-crown text-warning me-1"></i>
                                            <strong><?= htmlspecialchars($leader['full_name']) ?></strong> [<?= htmlspecialchars($leader['employee_id']) ?>]
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border py-1 px-2">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($memberCount > 0): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary team-member-badge view-members-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewMembersModal"
                                                data-team-name="<?= htmlspecialchars($team['name']) ?>"
                                                data-members='<?= htmlspecialchars(json_encode($members), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="fa-solid fa-users"></i>
                                            <span><strong><?= $memberCount ?></strong> <?= $memberCount === 1 ? 'Member' : 'Members' ?></span>
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border py-2 px-3">0 Members</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($team['status'] == 1): ?>
                                        <span class="bd-badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTeam"
                                            data-id="<?= $team['id'] ?>"
                                            data-name="<?= htmlspecialchars($team['name']) ?>"
                                            data-status="<?= $team['status'] ?>"
                                            data-leader-id="<?= $leaderId ?>"
                                            data-members='<?= htmlspecialchars(json_encode($memberIds), ENT_QUOTES, 'UTF-8') ?>'
                                            title="Edit Team">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $team['id'] ?>"
                                            data-name="<?= htmlspecialchars($team['name']) ?>"
                                            data-action="delete-team"
                                            title="Delete Team">
                                            <i class="fa-regular fa-trash"></i>
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

<!-- ========================================================================= -->
<!-- 3. ADD NEW TEAM MODAL (Full Interactive UI)                               -->
<!-- ========================================================================= -->
<div id="addNewTeam" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <form action="<?= $GLOBALS['base_url'] ?>/store-team" method="POST" id="addTeamForm">
                <!-- Modal Header -->
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-users-gear text-primary me-2"></i> Add New Team
                    </h5>
                    <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark-large"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <!-- Team Name & Status -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark">Team Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" id="add_name" type="text" placeholder="Enter team name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark">Team Status</label>
                            <div class="d-flex align-items-center gap-3 pt-2">
                                <div class="form-check">
                                    <input name="status" class="form-check-input" type="radio" value="1" id="addStatusActive" checked>
                                    <label class="form-check-label" for="addStatusActive">Active</label>
                                </div>
                                <div class="form-check">
                                    <input name="status" class="form-check-input" type="radio" value="0" id="addStatusInactive">
                                    <label class="form-check-label text-muted" for="addStatusInactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Member & Leader Selection Widget -->
                    <div class="team-member-manager-box" id="addTeamManagerWidget">
                        <!-- Hidden Leader Input directly inside widget -->
                        <input type="hidden" name="leader_id" id="add_leader_id" class="team-leader-id-input" value="0">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold text-dark fs-14">
                                    <i class="fa-solid fa-user-check text-primary me-1"></i> Team Members & Leadership
                                </span>
                            </div>
                            <span class="badge bg-primary rounded-pill selection-count-badge">0 Selected</span>
                        </div>

                        <!-- Current Leader Status Bar -->
                        <div class="d-flex justify-content-between align-items-center bg-white border rounded px-3 py-2 mb-3 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-crown text-warning fs-5"></i>
                                <span class="fw-semibold text-dark fs-13">Assigned Leader:</span>
                                <span class="leader-display-text badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 fs-12">
                                    None
                                </span>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 remove-leader-btn d-none" style="font-size: 11px;">
                                <i class="fa-solid fa-xmark me-1"></i> Clear Leader
                            </button>
                        </div>

                        <!-- Search & Quick Action Controls -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div class="position-relative flex-grow-1" style="min-width: 220px;">
                                <input type="text" class="form-control form-control-sm widget-search-input" placeholder="Search sales employee by name or ID...">
                                <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 10px; top: 8px; font-size: 11px;"></i>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-primary btn-select-all py-1 px-2" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-check-double me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-deselect-all py-1 px-2" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-xmark me-1"></i> Deselect All
                                </button>
                            </div>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                            <button type="button" class="btn btn-outline-secondary filter-tab-btn active py-1" data-filter="all">All (<?= count($salesEmployees) ?>)</button>
                            <button type="button" class="btn btn-outline-primary filter-tab-btn py-1" data-filter="selected">Selected (<span class="tab-selected-count">0</span>)</button>
                            <button type="button" class="btn btn-outline-secondary filter-tab-btn py-1" data-filter="available">No Team</button>
                        </div>

                        <!-- Scrollable Employee Rows -->
                        <div class="employee-list-scroll">
                            <?php foreach ($salesEmployees as $emp): ?>
                                <?php 
                                    $hasTeam = !empty($emp['team_id']);
                                    $teamLabel = $hasTeam ? "In: {$emp['current_team_name']}" : "No Team";
                                    $searchStr = strtolower($emp['full_name'] . ' ' . $emp['employee_id'] . ' ' . $teamLabel);
                                ?>
                                <div class="employee-select-row" 
                                     data-emp-id="<?= $emp['id'] ?>"
                                     data-emp-name="<?= htmlspecialchars($emp['full_name']) ?>"
                                     data-emp-code="<?= htmlspecialchars($emp['employee_id']) ?>"
                                     data-has-team="<?= $hasTeam ? '1' : '0' ?>"
                                     data-search="<?= htmlspecialchars($searchStr) ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" name="employee_ids[]" value="<?= $emp['id'] ?>" class="form-check-input member-checkbox m-0">
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($emp['full_name']) ?></span>
                                            <small class="text-muted">ID: <?= htmlspecialchars($emp['employee_id']) ?> · <span class="<?= $hasTeam ? 'text-primary' : 'text-muted' ?>"><?= htmlspecialchars($teamLabel) ?></span></small>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="leader-btn-pill btn-leader-toggle" data-emp-id="<?= $emp['id'] ?>" title="Click to assign or toggle Team Leader">
                                            <i class="fa-solid fa-crown me-1"></i> <span class="leader-label-text">Set Leader</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-2 text-muted" style="font-size: 11.5px;">
                            <i class="fa-regular fa-circle-check text-success me-1"></i> Check members to add to this team. Click <strong>Set Leader</strong> on any member to appoint them as Team Leader (updates <code>is_team_leader = 1</code>).
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Always Visible at bottom) -->
                <div class="modal-footer d-flex justify-content-between align-items-center px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="fa-solid fa-check me-1"></i> Create Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. EDIT TEAM MODAL (Full Interactive UI)                                  -->
<!-- ========================================================================= -->
<div id="editTeam" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <form action="<?= $GLOBALS['base_url'] ?>/update-team" method="POST" id="editTeamForm">
                <input type="hidden" name="id" id="edit_team_id">

                <!-- Modal Header -->
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-users-gear text-primary me-2"></i> Edit Team
                    </h5>
                    <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark-large"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <!-- Team Name & Status -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark">Team Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" id="edit_name" type="text" placeholder="Enter team name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark">Team Status</label>
                            <div class="d-flex align-items-center gap-3 pt-2">
                                <div class="form-check">
                                    <input name="status" class="form-check-input" type="radio" value="1" id="editStatusActive">
                                    <label class="form-check-label" for="editStatusActive">Active</label>
                                </div>
                                <div class="form-check">
                                    <input name="status" class="form-check-input" type="radio" value="0" id="editStatusInactive">
                                    <label class="form-check-label text-muted" for="editStatusInactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Member & Leader Selection Widget -->
                    <div class="team-member-manager-box" id="editTeamManagerWidget">
                        <!-- Hidden Leader Input directly inside widget -->
                        <input type="hidden" name="leader_id" id="edit_leader_id" class="team-leader-id-input" value="0">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold text-dark fs-14">
                                    <i class="fa-solid fa-user-check text-primary me-1"></i> Team Members & Leadership
                                </span>
                            </div>
                            <span class="badge bg-primary rounded-pill selection-count-badge">0 Selected</span>
                        </div>

                        <!-- Current Leader Status Bar -->
                        <div class="d-flex justify-content-between align-items-center bg-white border rounded px-3 py-2 mb-3 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-crown text-warning fs-5"></i>
                                <span class="fw-semibold text-dark fs-13">Assigned Leader:</span>
                                <span class="leader-display-text badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 fs-12">
                                    None
                                </span>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 remove-leader-btn d-none" style="font-size: 11px;">
                                <i class="fa-solid fa-xmark me-1"></i> Clear Leader
                            </button>
                        </div>

                        <!-- Search & Quick Action Controls -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div class="position-relative flex-grow-1" style="min-width: 220px;">
                                <input type="text" class="form-control form-control-sm widget-search-input" placeholder="Search sales employee by name or ID...">
                                <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 10px; top: 8px; font-size: 11px;"></i>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-primary btn-select-all py-1 px-2" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-check-double me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-deselect-all py-1 px-2" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-xmark me-1"></i> Deselect All
                                </button>
                            </div>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                            <button type="button" class="btn btn-outline-secondary filter-tab-btn active py-1" data-filter="all">All (<?= count($salesEmployees) ?>)</button>
                            <button type="button" class="btn btn-outline-primary filter-tab-btn py-1" data-filter="selected">Selected (<span class="tab-selected-count">0</span>)</button>
                            <button type="button" class="btn btn-outline-secondary filter-tab-btn py-1" data-filter="available">No Team</button>
                        </div>

                        <!-- Scrollable Employee Rows -->
                        <div class="employee-list-scroll">
                            <?php foreach ($salesEmployees as $emp): ?>
                                <?php 
                                    $hasTeam = !empty($emp['team_id']);
                                    $teamLabel = $hasTeam ? "In: {$emp['current_team_name']}" : "No Team";
                                    $searchStr = strtolower($emp['full_name'] . ' ' . $emp['employee_id'] . ' ' . $teamLabel);
                                ?>
                                <div class="employee-select-row" 
                                     data-emp-id="<?= $emp['id'] ?>"
                                     data-emp-name="<?= htmlspecialchars($emp['full_name']) ?>"
                                     data-emp-code="<?= htmlspecialchars($emp['employee_id']) ?>"
                                     data-has-team="<?= $hasTeam ? '1' : '0' ?>"
                                     data-search="<?= htmlspecialchars($searchStr) ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" name="employee_ids[]" value="<?= $emp['id'] ?>" class="form-check-input member-checkbox m-0">
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($emp['full_name']) ?></span>
                                            <small class="text-muted">ID: <?= htmlspecialchars($emp['employee_id']) ?> · <span class="<?= $hasTeam ? 'text-primary' : 'text-muted' ?>"><?= htmlspecialchars($teamLabel) ?></span></small>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="leader-btn-pill btn-leader-toggle" data-emp-id="<?= $emp['id'] ?>" title="Click to assign or toggle Team Leader">
                                            <i class="fa-solid fa-crown me-1"></i> <span class="leader-label-text">Set Leader</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-2 text-muted" style="font-size: 11.5px;">
                            <i class="fa-regular fa-circle-check text-success me-1"></i> Uncheck any member to remove them from this team. Click <strong>Set Leader</strong> on any member to switch leadership immediately.
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Always Visible at bottom) -->
                <div class="modal-footer d-flex justify-content-between align-items-center px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="fa-solid fa-check me-1"></i> Update Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. VIEW ALL MEMBERS MODAL                                                 -->
<!-- ========================================================================= -->
<div id="viewMembersModal" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-users text-primary me-2"></i> Team Members: <span id="viewModalTeamName" class="text-primary"></span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-3">
                <ul class="list-group list-group-flush" id="viewModalMembersList" style="max-height: 420px; overflow-y: auto;">
                    <!-- Populated dynamically via JS -->
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
// Team Management Interactive System
(function($) {
    'use strict';

    // Helper: Initialize and bind a Member/Leader Manager Widget inside a container
    function initTeamWidget($widget) {
        // Guarantee finding the leader input element
        var $leaderInput = $widget.find('.team-leader-id-input');
        if (!$leaderInput.length) {
            $leaderInput = $widget.closest('form').find('.team-leader-id-input');
        }
        if (!$leaderInput.length) {
            var isEdit = $widget.attr('id') === 'editTeamManagerWidget';
            $leaderInput = $(isEdit ? '#edit_leader_id' : '#add_leader_id');
        }

        var $searchInput = $widget.find('.widget-search-input');
        var $filterBtns = $widget.find('.filter-tab-btn');
        var $leaderText = $widget.find('.leader-display-text');
        var $removeLeaderBtn = $widget.find('.remove-leader-btn');
        var $countBadge = $widget.find('.selection-count-badge');
        var $tabSelectedCount = $widget.find('.tab-selected-count');
        var $selectAllBtn = $widget.find('.btn-select-all');
        var $deselectAllBtn = $widget.find('.btn-deselect-all');

        // Update counts and leader display state
        function updateWidgetState() {
            var currentLeaderId = String($leaderInput.val() || '0');
            var selectedCount = 0;
            var leaderFound = false;
            var activeLeaderName = '';
            var activeLeaderCode = '';

            $widget.find('.employee-select-row').each(function() {
                var $row = $(this);
                var empId = String($row.attr('data-emp-id'));
                var isChecked = $row.find('.member-checkbox').is(':checked');
                var isLeader = (currentLeaderId !== '0' && currentLeaderId === empId);

                if (isChecked) {
                    selectedCount++;
                    $row.addClass('row-selected');
                } else {
                    $row.removeClass('row-selected');
                    // If member is unchecked but was marked as leader, unset leader!
                    if (isLeader) {
                        currentLeaderId = '0';
                        $leaderInput.val('0');
                        isLeader = false;
                    }
                }

                var $leaderBtn = $row.find('.btn-leader-toggle');
                if (isLeader) {
                    leaderFound = true;
                    activeLeaderName = $row.attr('data-emp-name') || '';
                    activeLeaderCode = $row.attr('data-emp-code') || '';
                    $row.addClass('row-leader');
                    $leaderBtn.addClass('active-leader');
                    $leaderBtn.html('<i class="fa-solid fa-crown text-warning me-1"></i> <span class="leader-label-text">Team Leader</span> <i class="fa-solid fa-check ms-1 text-success"></i>');
                } else {
                    $row.removeClass('row-leader');
                    $leaderBtn.removeClass('active-leader');
                    $leaderBtn.html('<i class="fa-solid fa-crown me-1"></i> <span class="leader-label-text">Set Leader</span>');
                }
            });

            if (leaderFound && currentLeaderId !== '0') {
                $leaderText.html('<i class="fa-solid fa-crown text-warning me-1"></i> <strong>' + activeLeaderName + '</strong> [' + activeLeaderCode + ']');
                $removeLeaderBtn.removeClass('d-none');
            } else {
                $leaderText.text('None');
                $leaderInput.val('0');
                $removeLeaderBtn.addClass('d-none');
            }

            $countBadge.text(selectedCount + ' Selected');
            $tabSelectedCount.text(selectedCount);
        }

        // Filter rows based on search input and active tab filter
        function applyFilter() {
            var search = $searchInput.val().toLowerCase().trim();
            var activeFilter = $widget.find('.filter-tab-btn.active').attr('data-filter') || 'all';

            $widget.find('.employee-select-row').each(function() {
                var $row = $(this);
                var isChecked = $row.find('.member-checkbox').is(':checked');
                var hasTeam = $row.attr('data-has-team') === '1';
                var searchData = $row.attr('data-search') || '';

                var matchesSearch = !search || searchData.indexOf(search) > -1;
                var matchesFilter = true;

                if (activeFilter === 'selected') {
                    matchesFilter = isChecked;
                } else if (activeFilter === 'available') {
                    matchesFilter = !hasTeam || isChecked;
                }

                if (matchesSearch && matchesFilter) {
                    $row.removeClass('d-none');
                } else {
                    $row.addClass('d-none');
                }
            });
        }

        // Event Delegation: Row click toggles checkbox (unless leader button or direct checkbox was clicked)
        $widget.off('click', '.employee-select-row').on('click', '.employee-select-row', function(e) {
            if ($(e.target).closest('.btn-leader-toggle').length || $(e.target).is('.member-checkbox')) {
                return; // Let direct clicks handle themselves
            }
            var $cb = $(this).find('.member-checkbox');
            $cb.prop('checked', !$cb.is(':checked')).trigger('change');
        });

        // Event Delegation: Checkbox change
        $widget.off('change', '.member-checkbox').on('change', '.member-checkbox', function() {
            updateWidgetState();
        });

        // Event Delegation: Leader Toggle Button click
        $widget.off('click', '.btn-leader-toggle').on('click', '.btn-leader-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var empId = String($(this).attr('data-emp-id'));
            var currentLeaderId = String($leaderInput.val() || '0');
            var $row = $(this).closest('.employee-select-row');
            var $cb = $row.find('.member-checkbox');

            if (currentLeaderId === empId) {
                // Already leader -> toggle off!
                $leaderInput.val('0');
            } else {
                // New leader selected -> ensure member is checked into team and appoint as leader
                $cb.prop('checked', true);
                $leaderInput.val(empId);
            }

            updateWidgetState();
            applyFilter();
        });

        // Event: Clear Leader Button
        $removeLeaderBtn.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $leaderInput.val('0');
            updateWidgetState();
        });

        // Event: Select All (visible rows)
        $selectAllBtn.off('click').on('click', function(e) {
            e.preventDefault();
            $widget.find('.employee-select-row:not(.d-none)').each(function() {
                $(this).find('.member-checkbox').prop('checked', true);
            });
            updateWidgetState();
        });

        // Event: Deselect All (visible rows)
        $deselectAllBtn.off('click').on('click', function(e) {
            e.preventDefault();
            $widget.find('.employee-select-row:not(.d-none)').each(function() {
                var empId = String($(this).attr('data-emp-id'));
                $(this).find('.member-checkbox').prop('checked', false);
                if (String($leaderInput.val()) === empId) {
                    $leaderInput.val('0');
                }
            });
            updateWidgetState();
        });

        // Event: Search Input
        $searchInput.off('input').on('input', applyFilter);

        // Event: Filter Tab Buttons
        $filterBtns.off('click').on('click', function(e) {
            e.preventDefault();
            $filterBtns.removeClass('active');
            $(this).addClass('active');
            applyFilter();
        });

        // Initial state update
        updateWidgetState();
    }

    $(document).ready(function() {
        var $addWidget = $('#addTeamManagerWidget');
        var $editWidget = $('#editTeamManagerWidget');

        // Initialize both widgets once on ready
        initTeamWidget($addWidget);
        initTeamWidget($editWidget);

        // Live card search filter for top cards
        $('#teamSearchFilter').on('input', function() {
            var term = $(this).val().toLowerCase().trim();
            $('.team-card-wrapper').each(function() {
                var searchData = $(this).attr('data-search') || '';
                if (!term || searchData.indexOf(term) > -1) {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none');
                }
            });
        });

        // When Add Modal Opens: Reset everything cleanly
        $('#addNewTeam').on('show.bs.modal', function() {
            $('#add_name').val('');
            $('#addStatusActive').prop('checked', true);
            $addWidget.find('.member-checkbox').prop('checked', false);
            $('#add_leader_id').val('0');
            $addWidget.find('.team-leader-id-input').val('0');
            $addWidget.find('.widget-search-input').val('');
            $addWidget.find('.filter-tab-btn[data-filter="all"]').trigger('click');
            initTeamWidget($addWidget);
        });

        // When Edit Modal Opens: Populate fields and check corresponding members/leader
        $('#editTeam').on('show.bs.modal', function(event) {
            var button = event.relatedTarget ? $(event.relatedTarget).closest('[data-bs-target="#editTeam"]')[0] || event.relatedTarget : null;
            if (!button) return;

            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var status = button.getAttribute('data-status');
            var leaderId = String(button.getAttribute('data-leader-id') || '0');
            var membersJson = button.getAttribute('data-members') || '[]';

            $('#edit_team_id').val(id);
            $('#edit_name').val(name);

            if (status === '1') {
                $('#editStatusActive').prop('checked', true);
            } else {
                $('#editStatusInactive').prop('checked', true);
            }

            var memberIds = [];
            try {
                memberIds = JSON.parse(membersJson);
            } catch (e) {
                memberIds = [];
            }
            memberIds = memberIds.map(function(m) { return String(m); });

            // Reset all checkboxes first
            $editWidget.find('.member-checkbox').prop('checked', false);
            // Set leader id in both input handles
            $('#edit_leader_id').val(leaderId);
            $editWidget.find('.team-leader-id-input').val(leaderId);
            $editWidget.find('.widget-search-input').val('');

            // Pre-check all members of this team
            memberIds.forEach(function(mId) {
                $editWidget.find('.member-checkbox[value="' + mId + '"]').prop('checked', true);
            });

            // Default to 'all' filter and refresh widget UI state
            $editWidget.find('.filter-tab-btn[data-filter="all"]').trigger('click');
            initTeamWidget($editWidget);
        });

        // View Members Modal Open Event
        $('#viewMembersModal').on('show.bs.modal', function(event) {
            var button = event.relatedTarget ? $(event.relatedTarget).closest('.view-members-btn')[0] || event.relatedTarget : null;
            if (!button) return;

            var teamName = button.getAttribute('data-team-name');
            var membersJson = button.getAttribute('data-members') || '[]';

            document.getElementById('viewModalTeamName').innerText = teamName;

            var members = [];
            try {
                members = JSON.parse(membersJson);
            } catch (e) {
                members = [];
            }

            var listContainer = document.getElementById('viewModalMembersList');
            listContainer.innerHTML = '';

            if (members.length === 0) {
                listContainer.innerHTML = '<li class="list-group-item text-muted text-center py-4">No members currently assigned to this team.</li>';
            } else {
                members.forEach(function(m, idx) {
                    var isLeader = parseInt(m.is_team_leader || 0) === 1;
                    var li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';
                    
                    var leaderBadge = isLeader 
                        ? '<span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-2"><i class="fa-solid fa-crown text-warning me-1"></i>Team Leader</span>' 
                        : '';

                    li.innerHTML = 
                        '<div class="d-flex align-items-center gap-2">' +
                            '<span class="badge bg-primary-subtle text-primary border rounded-pill">' + (idx + 1) + '</span>' +
                            '<span class="fw-semibold text-dark">' + m.full_name + '</span>' +
                            leaderBadge +
                        '</div>' +
                        '<span class="badge bg-light text-muted border">ID: ' + m.employee_id + '</span>';
                    listContainer.appendChild(li);
                });
            }
        });
    });
})(jQuery);
<?php $page_script = ob_get_clean(); ?>