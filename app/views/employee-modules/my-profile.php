    <div class="row">
        <div class="card__wrapper">

            <!-- ══ PAGE: PROFILE ══ -->
            <div class="section-header">
                <div class="section-title">My Profile</div>
                <div class="section-sub">Personal &amp; professional information</div>
            </div>
            <div class="row">
                <!-- Profile Hero -->
                <div class="col-12">
                    <div class="card__wrapper">
                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <?php
                            $photoPath = getImage('employees', $employee['photo'])
                            ?>

                            <img src="<?= $photoPath ?>"
                                class="profile-avatar"
                                alt="avatar"
                                style="width:90px;height:90px;border-radius:50%;object-fit:cover;" />
                            <div>
                                <div class="profile-name"><?= htmlspecialchars($employee['full_name'] ?? '') ?></div>
                                <div class="profile-role mt-1"><?= htmlspecialchars($employee['designation_name'] ?? '') ?></div>
                                <div class="d-flex gap-2 mt-3 flex-wrap">
                                    <span class="chip" style="background:rgba(52,211,153,.12);color:var(--accent3)">
                                        <i class="bi bi-circle-fill" style="font-size:.5rem"></i>
                                        <?= htmlspecialchars(ucfirst($employee['employee_status'] ?? 'Active')) ?>
                                    </span>
                                    <span class="chip" style="background:rgba(79,140,255,.12);color:var(--accent)">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($employee['department_name'] ?? 'N/A') ?>
                                    </span>
                                    <?php if (!empty($employee['workplace'])): ?>
                                        <span class="chip" style="background:rgba(167,139,250,.12);color:var(--accent2)">
                                            <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars($employee['workplace']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ms-auto text-end d-none d-md-block">
                                <div style="font-family:'Space Mono',monospace;font-size:.75rem;color:var(--muted)">Employee ID</div>
                                <div style="font-size:1.3rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">
                                    #<?= htmlspecialchars($employee['employee_id'] ?? '') ?>
                                </div>
                                <?php if (!empty($employee['joining_date'])): ?>
                                    <div style="font-size:.72rem;color:var(--muted);margin-top:4px">
                                        Joined: <?= date('F d, Y', strtotime($employee['joining_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Info -->
                <div class="col-md-6">
                    <div class="stat-card h-100">
                        <div class="section-title mb-3"><i class="bi bi-person-lines-fill me-2" style="color:var(--accent)"></i>Personal Info</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= htmlspecialchars($employee['full_name'] ?? '') ?></div>
                            </div>
                            <?php if (!empty($employee['dob'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Date of Birth</div>
                                    <div class="info-value"><?= date('F d, Y', strtotime($employee['dob'])) ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?= htmlspecialchars($employee['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value" style="font-size:.8rem"><?= htmlspecialchars($employee['email'] ?? 'N/A') ?></div>
                            </div>
                            <?php if (!empty($employee['address'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?= htmlspecialchars($employee['address']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($employee['nid'])): ?>
                                <div class="info-item">
                                    <div class="info-label">NID</div>
                                    <div class="info-value"><?= htmlspecialchars($employee['nid']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Job Info -->
                <div class="col-md-6">
                    <div class="stat-card h-100">
                        <div class="section-title mb-3"><i class="bi bi-briefcase-fill me-2" style="color:var(--accent2)"></i>Job Information</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value"><?= htmlspecialchars($employee['department_name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Designation</div>
                                <div class="info-value"><?= htmlspecialchars($employee['designation_name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Employee Type</div>
                                <div class="info-value"><?= htmlspecialchars(ucfirst($employee['employee_type'] ?? 'N/A')) ?></div>
                            </div>
                            <?php if (!empty($employee['workplace'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Workplace</div>
                                    <div class="info-value"><?= htmlspecialchars($employee['workplace']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($employee['joining_date'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Joining Date</div>
                                    <div class="info-value"><?= date('F d, Y', strtotime($employee['joining_date'])) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($yearsOfService)): ?>
                                <div class="info-item">
                                    <div class="info-label">Years of Service</div>
                                    <div class="info-value" style="color:var(--accent3)"><?= htmlspecialchars($yearsOfService) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- YTD Stats -->
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="section-title mb-3"><i class="bi bi-bar-chart-fill me-2" style="color:var(--accent3)"></i>Year-to-Date Stats</div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.82rem">Overall Attendance</span>
                                <span style="font-size:.82rem;font-weight:700;color:var(--accent3)"><?= $attendancePct ?? 0 ?>%</span>
                            </div>
                            <div class="att-bar-wrap">
                                <div class="att-bar" style="width:<?= $attendancePct ?? 0 ?>%;background:var(--accent3)"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.82rem">Task Completion Rate</span>
                                <span style="font-size:.82rem;font-weight:700;color:var(--accent)"><?= $taskPct ?? 0 ?>%</span>
                            </div>
                            <div class="att-bar-wrap">
                                <div class="att-bar" style="width:<?= $taskPct ?? 0 ?>%;background:var(--accent)"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.82rem">Leave Utilization</span>
                                <span style="font-size:.82rem;font-weight:700;color:var(--accent2)"><?= $leavePct ?? 0 ?>%</span>
                            </div>
                            <div class="att-bar-wrap">
                                <div class="att-bar" style="width:<?= $leavePct ?? 0 ?>%;background:var(--accent2)"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="section-title mb-3">Recent Activity</div>
                        <div class="timeline">
                            <?php if (!empty($recentActivity)): ?>
                                <?php foreach ($recentActivity as $i => $act): ?>
                                    <?php
                                    $dotColor = $act['activity_type'] === 'leave' ? 'var(--accent2)' : 'var(--accent3)';
                                    $icon     = $act['activity_type'] === 'leave' ? 'Leave Applied' : 'Task';
                                    $status   = htmlspecialchars(ucfirst($act['status'] ?? ''));
                                    $title    = htmlspecialchars($act['title'] ?? '');
                                    $date     = !empty($act['activity_date']) ? date('M d, Y', strtotime($act['activity_date'])) : '';
                                    ?>
                                    <div class="tl-item" <?= $i === count($recentActivity) - 1 ? 'style="margin-bottom:0"' : '' ?>>

                                        <div class="tl-title"> <?= $title ?></div>
                                        <div class="tl-sub"><?= $date ?> <?= $status ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted" style="font-size:.85rem">No recent activity found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>