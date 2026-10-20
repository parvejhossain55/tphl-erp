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

                            <div class="position-relative d-inline-block" style="width:95px;height:95px;">
                                <img src="<?= $photoPath ?>"
                                    id="profileAvatarImg"
                                    class="profile-avatar"
                                    alt="avatar"
                                    title="Click to view full photo"
                                    style="width:95px;height:95px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.12);cursor:pointer;transition:transform 0.2s ease, box-shadow 0.2s ease;" />
                                <label for="profilePhotoInput" 
                                       id="btnChangeProfilePhoto"
                                       class="position-absolute d-flex align-items-center justify-content-center text-white" 
                                       title="Change Profile Photo"
                                       style="bottom:2px;right:2px;width:32px;height:32px;background:#0d6efd;border-radius:50%;cursor:pointer;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.25);transition:all 0.2s ease;">
                                    <i class="fa-solid fa-camera" id="cameraIcon" style="font-size:13px;"></i>
                                    <div class="spinner-border spinner-border-sm text-white d-none" id="avatarUploadSpinner" role="status" style="width:14px;height:14px;border-width:2px;"></div>
                                </label>
                                <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" />
                            </div>
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

                <!-- ══ PROFILE COMPLETION PROGRESS CARD ══ -->
                <div class="col-12 mt-3">
                    <div class="card__wrapper p-3 border shadow-sm" style="border-radius:14px; background:linear-gradient(135deg, rgba(255,255,255,0.98), rgba(240,248,255,0.95));">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:38px; height:38px; border-radius:10px; background:<?= ($profileCompletionPct ?? 0) >= 100 ? '#10b981' : '#3b82f6' ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid <?= ($profileCompletionPct ?? 0) >= 100 ? 'fa-circle-check' : 'fa-chart-pie' ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Profile Completion</h6>
                                    <small class="text-muted"><?= $completedCount ?? 0 ?> of <?= $totalCompletionFields ?? 8 ?> required items completed</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge fs-12 px-3 py-2 rounded-pill fw-bold" style="background:<?= ($profileCompletionPct ?? 0) >= 100 ? '#d1fae5; color:#065f46;' : (($profileCompletionPct ?? 0) >= 60 ? '#dbeafe; color:#1e40af;' : '#fef3c7; color:#92400e;') ?>">
                                    <?= $profileCompletionPct ?? 0 ?>% Complete
                                </span>
                                <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fa-solid fa-user-pen"></i> <?= ($profileCompletionPct ?? 0) >= 100 ? 'Update Info' : 'Complete Profile' ?>
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress my-2" style="height: 10px; border-radius: 8px; background: #e2e8f0;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                                 style="width: <?= $profileCompletionPct ?? 0 ?>%; background: <?= ($profileCompletionPct ?? 0) >= 100 ? '#10b981' : 'linear-gradient(90deg, #3b82f6, #06b6d4)' ?>;" 
                                 aria-valuenow="<?= $profileCompletionPct ?? 0 ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>

                        <!-- Completion Checklist Badges -->
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php if (!empty($completionFields)): ?>
                                <?php foreach ($completionFields as $key => $item): ?>
                                    <?php if ($item['filled']): ?>
                                        <span class="badge d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:0.75rem; font-weight:500;">
                                            <i class="fa-solid fa-check"></i> <?= htmlspecialchars($item['label']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill" 
                                              style="background:#fffbeb; color:#b45309; border:1px dashed #fcd34d; font-size:0.75rem; font-weight:500; cursor:pointer;"
                                              data-bs-toggle="modal" data-bs-target="#editProfileModal"
                                              title="Click to add <?= htmlspecialchars($item['label']) ?>">
                                            <i class="fa-solid fa-plus"></i> Add <?= htmlspecialchars($item['label']) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Personal Info -->
                <div class="col-md-6 mt-3">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0"><i class="bi bi-person-lines-fill me-2" style="color:var(--accent)"></i>Personal Info</div>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill fs-11" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                            </button>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= htmlspecialchars($employee['full_name'] ?? '') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Father's Name</div>
                                <div class="info-value"><?= !empty($employee['father_name']) ? htmlspecialchars($employee['father_name']) : '<span class="text-warning fs-12">Not set</span>' ?></div>
                            </div>
                            <?php if (!empty($employee['dob'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Date of Birth</div>
                                    <div class="info-value"><?= ($employee['dob'] !== '0000-00-00') ? date('F d, Y', strtotime($employee['dob'])) : '<span class="text-warning fs-12">Not set</span>' ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?= !empty($employee['phone']) ? htmlspecialchars($employee['phone']) : '<span class="text-warning fs-12">Not set</span>' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Emergency Number</div>
                                <div class="info-value"><?= !empty($employee['emergency_contact']) ? htmlspecialchars($employee['emergency_contact']) : '<span class="text-warning fs-12">Not set</span>' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value" style="font-size:.8rem"><?= htmlspecialchars($employee['email'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Qualification</div>
                                <div class="info-value"><?= !empty($employee['qualification']) ? htmlspecialchars($employee['qualification']) : '<span class="text-warning fs-12">Not set</span>' ?></div>
                            </div>
                            <?php if (!empty($employee['social_media1'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Social Media</div>
                                    <div class="info-value">
                                        <a href="<?= htmlspecialchars($employee['social_media1']) ?>" target="_blank" class="text-primary text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View Link
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
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

    <!-- Full Profile Image Modal -->
    <div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: #fff;">
                <div class="modal-header border-0 pb-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="profileImageModalLabel">
                            <?= htmlspecialchars($employee['full_name'] ?? 'Profile Photo') ?>
                        </h6>
                        <span class="text-muted" style="font-size: 0.78rem;">
                            <?= htmlspecialchars($employee['designation_name'] ?? '') ?>
                            <?php if (!empty($employee['department_name'])): ?>
                                • <?= htmlspecialchars($employee['department_name']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <div class="position-relative d-inline-block w-100" style="background:#f8f9fa; border-radius: 12px; overflow: hidden; min-height: 260px; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= $photoPath ?>" 
                             id="fullProfileImg" 
                             alt="Full Profile Photo" 
                             class="img-fluid" 
                             style="max-height: 480px; width: auto; max-width: 100%; object-fit: contain;" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit / Complete Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background:#fff;">
                <div class="modal-header border-bottom py-3 px-4">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editProfileModalLabel">
                            <i class="fa-solid fa-user-pen text-primary me-2"></i>Complete / Edit Profile
                        </h5>
                        <small class="text-muted">Fill in your information to achieve 100% profile completion.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProfileForm">
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Father's Name</label>
                                <input type="text" class="form-control" name="father_name" value="<?= htmlspecialchars($employee['father_name'] ?? '') ?>" placeholder="Enter father's name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Contact Number</label>
                                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>" placeholder="017XXXXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Emergency Contact Number</label>
                                <input type="text" class="form-control" name="emergency_contact" value="<?= htmlspecialchars($employee['emergency_contact'] ?? '') ?>" placeholder="018XXXXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?= (!empty($employee['dob']) && $employee['dob'] !== '0000-00-00') ? htmlspecialchars($employee['dob']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Qualification / Highest Degree</label>
                                <input type="text" class="form-control" name="qualification" value="<?= htmlspecialchars($employee['qualification'] ?? '') ?>" placeholder="e.g. B.Sc in CSE, MBA, etc.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Social Media Link (LinkedIn, Facebook)</label>
                                <input type="text" class="form-control" name="social_media1" value="<?= htmlspecialchars($employee['social_media1'] ?? '') ?>" placeholder="https://linkedin.com/in/... or facebook.com/...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark">Present Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Enter present residential address"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2 px-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btnSaveProfileInfo" class="btn btn-primary d-flex align-items-center gap-2">
                            <span id="saveProfileBtnText">Save Information</span>
                            <div class="spinner-border spinner-border-sm text-white d-none" id="saveProfileSpinner" role="status"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoInput = document.getElementById('profilePhotoInput');
        const avatarImg = document.getElementById('profileAvatarImg');
        const cameraIcon = document.getElementById('cameraIcon');
        const uploadSpinner = document.getElementById('avatarUploadSpinner');
        const changeBtn = document.getElementById('btnChangeProfilePhoto');

        if (!photoInput || !avatarImg) return;

        // Avatar hover effect
        avatarImg.addEventListener('mouseenter', () => {
            avatarImg.style.transform = 'scale(1.05)';
            avatarImg.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
        });
        avatarImg.addEventListener('mouseleave', () => {
            avatarImg.style.transform = 'scale(1.0)';
            avatarImg.style.boxShadow = '0 4px 14px rgba(0,0,0,0.12)';
        });

        // Click avatar to view full image in modal
        avatarImg.addEventListener('click', function() {
            const fullImg = document.getElementById('fullProfileImg');
            if (fullImg) {
                fullImg.src = avatarImg.src;
            }
            const modalEl = document.getElementById('profileImageModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (typeof $ !== 'undefined' && $('#profileImageModal').modal) {
                $('#profileImageModal').modal('show');
            }
        });

        // Subtle hover zoom on camera button
        if (changeBtn) {
            changeBtn.addEventListener('mouseenter', () => changeBtn.style.transform = 'scale(1.1)');
            changeBtn.addEventListener('mouseleave', () => changeBtn.style.transform = 'scale(1.0)');
        }

        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 5MB.'
                    });
                } else {
                    alert('Please select an image smaller than 5MB.');
                }
                this.value = '';
                return;
            }

            // Preview immediately
            const oldSrc = avatarImg.src;
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarImg.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Show uploading spinner & dim image
            if (cameraIcon) cameraIcon.classList.add('d-none');
            if (uploadSpinner) uploadSpinner.classList.remove('d-none');
            avatarImg.style.opacity = '0.65';
            if (changeBtn) changeBtn.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('photo', file);

            fetch('<?= $GLOBALS['base_url'] ?>/my-profile/update-photo', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (cameraIcon) cameraIcon.classList.remove('d-none');
                if (uploadSpinner) uploadSpinner.classList.add('d-none');
                avatarImg.style.opacity = '1';
                if (changeBtn) changeBtn.style.pointerEvents = 'auto';

                if (data.success) {
                    avatarImg.src = data.photo_url;
                    const fullImg = document.getElementById('fullProfileImg');
                    if (fullImg) {
                        fullImg.src = data.photo_url;
                    }
                    // Sync top navbar user avatar if present
                    const navAvatar = document.querySelector('.user__portfolio-thumb img');
                    if (navAvatar) {
                        navAvatar.src = data.photo_url;
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: data.message || 'Photo updated successfully!',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    } else {
                        alert(data.message || 'Photo updated successfully!');
                    }
                } else {
                    avatarImg.src = oldSrc;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: data.message || 'Could not update profile photo.'
                        });
                    } else {
                        alert(data.message || 'Could not update profile photo.');
                    }
                }
                photoInput.value = '';
            })
            .catch(err => {
                if (cameraIcon) cameraIcon.classList.remove('d-none');
                if (uploadSpinner) uploadSpinner.classList.add('d-none');
                avatarImg.style.opacity = '1';
                if (changeBtn) changeBtn.style.pointerEvents = 'auto';
                avatarImg.src = oldSrc;
                photoInput.value = '';
                console.error(err);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while uploading. Please try again.'
                    });
                } else {
                    alert('An error occurred while uploading.');
                }
            });
        });

        // ─── Handle Profile Information Form Submit ──────────────────────────────
        const editProfileForm = document.getElementById('editProfileForm');
        const btnSaveProfileInfo = document.getElementById('btnSaveProfileInfo');
        const saveProfileBtnText = document.getElementById('saveProfileBtnText');
        const saveProfileSpinner = document.getElementById('saveProfileSpinner');

        if (editProfileForm) {
            editProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (saveProfileBtnText) saveProfileBtnText.textContent = 'Saving...';
                if (saveProfileSpinner) saveProfileSpinner.classList.remove('d-none');
                if (btnSaveProfileInfo) btnSaveProfileInfo.disabled = true;

                const formData = new FormData(editProfileForm);

                fetch('<?= $GLOBALS['base_url'] ?>/my-profile/update-info', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (saveProfileBtnText) saveProfileBtnText.textContent = 'Save Information';
                    if (saveProfileSpinner) saveProfileSpinner.classList.add('d-none');
                    if (btnSaveProfileInfo) btnSaveProfileInfo.disabled = false;

                    if (data.success) {
                        const modalEl = document.getElementById('editProfileModal');
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const inst = bootstrap.Modal.getInstance(modalEl);
                            if (inst) inst.hide();
                        } else if (typeof $ !== 'undefined' && $('#editProfileModal').modal) {
                            $('#editProfileModal').modal('hide');
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Profile Updated',
                                text: data.message || 'Profile information updated successfully!',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message || 'Profile information updated successfully!');
                            window.location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Failed',
                                text: data.message || 'Could not update profile.'
                            });
                        } else {
                            alert(data.message || 'Could not update profile.');
                        }
                    }
                })
                .catch(err => {
                    if (saveProfileBtnText) saveProfileBtnText.textContent = 'Save Information';
                    if (saveProfileSpinner) saveProfileSpinner.classList.add('d-none');
                    if (btnSaveProfileInfo) btnSaveProfileInfo.disabled = false;
                    console.error(err);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong while saving profile information.'
                        });
                    } else {
                        alert('Something went wrong while saving profile information.');
                    }
                });
            });
        }
    });
    </script>