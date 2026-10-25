<?php
$page_title = "Incentive Configurations";
$page = 'Incentive Config';
$show_breadcrumb = true;
?>

<div class="container-fluid py-2">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Sales Incentive Modality Settings
            </h4>
            <p class="text-muted small mb-0">Configure target-wise slabs, floor incentives, team leader commissions, and key eligibility rules.</p>
        </div>
        <div>
            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-check-circle me-1"></i> Modality Active
            </span>
        </div>
    </div>

    <!-- Alert / Key Rules Notice -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary bg-primary-subtle bg-opacity-10">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-circle-info me-2"></i>Policy Guidelines & Modality Rules
            </h6>
            <div class="row g-2 text-dark small">
                <div class="col-md-6 col-lg-3">
                    <div class="p-2 rounded bg-white shadow-2xs border">
                        <strong class="d-block text-secondary extra-small text-uppercase">1. Minimum Eligibility</strong>
                        <span>Minimum sales: <strong>BDT 25 Lakh</strong> (Land Share)</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-2 rounded bg-white shadow-2xs border">
                        <strong class="d-block text-secondary extra-small text-uppercase">2. Project Scope</strong>
                        <span>Applicable <strong>only within assigned project</strong></span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-2 rounded bg-white shadow-2xs border">
                        <strong class="d-block text-secondary extra-small text-uppercase">3. Team Leader Rule</strong>
                        <span><strong>0.05%</strong> per eligible member achieving target</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-2 rounded bg-white shadow-2xs border">
                        <strong class="d-block text-secondary extra-small text-uppercase">4. Speed Bonus</strong>
                        <span><strong>+0.20%</strong> if completed within 2 months deadline</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form action="<?= $GLOBALS['base_url'] ?>/incentives/save-settings" method="POST">
        <div class="row g-4">
            
            <!-- Section 1: Base Rates & Eligibility -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-building me-2 text-primary"></i>Base Rates & Minimum Threshold
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">
                                Minimum Eligible Sales Amount (BDT)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Minimum monthly sales required to qualify for any incentive."></i>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted fw-bold">৳</span>
                                <input type="number" step="1000" name="settings[min_eligible_sales]" class="form-control fw-bold" 
                                       value="<?= htmlspecialchars($settings['min_eligible_sales'] ?? '2500000') ?>" required>
                            </div>
                            <span class="text-muted extra-small">Default: 2,500,000 (25 Lakh)</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">
                                Flat Sales Incentive Base Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Standard incentive rate on land share for general flats."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[flat_base_rate]" class="form-control fw-bold" 
                                       value="<?= htmlspecialchars($settings['flat_base_rate'] ?? '0.70') ?>" required>
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <span class="text-muted extra-small">Standard rate: 0.70% on Land Share Value</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">
                                2nd Floor & Below Top Floor Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Special incentive rate for 2nd floor and below top floor sales."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[floor_special_rate]" class="form-control fw-bold" 
                                       value="<?= htmlspecialchars($settings['floor_special_rate'] ?? '0.80') ?>" required>
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <span class="text-muted extra-small">Specific floor sale rate: 0.80% on Land Share Value</span>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small text-dark">
                                Unassigned Project Sales Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Applicable only if company allows unassigned sales. Leave 0.00 for policy compliance."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[unassigned_project_incentive_pct]" class="form-control text-muted" 
                                       value="<?= htmlspecialchars($settings['unassigned_project_incentive_pct'] ?? '0.00') ?>" required>
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <span class="text-muted extra-small">Policy rule: Incentive is only applicable within assigned project (0.00%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Super Achievement Slabs & Speed Bonus -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-chart-line-up me-2 text-success"></i>Achievement Slabs & Bonuses
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">
                                200% – 299% Target Achievement Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Incentive rate when sales target achievement is between 200% and 299%."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[slab_200_299_rate]" class="form-control fw-bold text-success" 
                                       value="<?= htmlspecialchars($settings['slab_200_299_rate'] ?? '1.00') ?>" required>
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <span class="text-muted extra-small">Target Over-achievement rate: 1.00%</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">
                                300%+ Target Achievement Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Top tier incentive rate when sales target achievement is 300% or above."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[slab_300_plus_rate]" class="form-control fw-bold text-success" 
                                       value="<?= htmlspecialchars($settings['slab_300_plus_rate'] ?? '1.20') ?>" required>
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <span class="text-muted extra-small">Top Tier over-achievement rate: 1.20%</span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-7">
                                <label class="form-label fw-semibold small text-dark">
                                    Timely Closure Speed Bonus (%)
                                    <i class="fa-solid fa-circle-question text-muted ms-1" title="Additional bonus applied when the deal is completed within defined deadline."></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-success fw-bold">+</span>
                                    <input type="number" step="0.01" name="settings[speed_bonus_rate]" class="form-control fw-bold text-primary" 
                                           value="<?= htmlspecialchars($settings['speed_bonus_rate'] ?? '0.20') ?>" required>
                                    <span class="input-group-text bg-light text-muted fw-bold">%</span>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label fw-semibold small text-dark">
                                    Deadline (Months)
                                </label>
                                <div class="input-group">
                                    <input type="number" step="1" name="settings[speed_bonus_months]" class="form-control fw-bold" 
                                           value="<?= htmlspecialchars($settings['speed_bonus_months'] ?? '2') ?>" required>
                                    <span class="input-group-text bg-light text-muted">Month(s)</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <span class="text-muted extra-small">Applies only within assigned project & within defined deadline</span>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small text-dark">
                                Team Leader Commission Rate (%)
                                <i class="fa-solid fa-circle-question text-muted ms-1" title="Applied per eligible sales member who achieves monthly target in the team."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="settings[team_leader_rate]" class="form-control fw-bold text-primary" 
                                       value="<?= htmlspecialchars($settings['team_leader_rate'] ?? '0.05') ?>" required>
                                <span class="input-group-text bg-light text-muted fw-bold">%</span>
                            </div>
                            <span class="text-muted extra-small">0.05% per eligible sales member on their total sales</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button Row -->
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-pill">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Save Modality Configurations
                </button>
            </div>
        </div>
    </form>
</div>
