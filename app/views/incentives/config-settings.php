<?php
$page_title = "Incentive Configurations";
$page = 'Incentive Config';
$show_breadcrumb = true;
?>

<div class="row">
    <!-- General Settings -->
    <div class="col-lg-12 mb-4">
        <div class="card__wrapper">
            <h5 class="card__title mb-3">General Incentive Settings</h5>
            <form action="<?= $GLOBALS['base_url'] ?>/incentives/save-settings" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Unassigned Project Incentive (%) <i class="fa-solid fa-info-circle text-muted" title="Percentage paid for sales in unassigned projects once 21% of assigned target is achieved."></i></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="settings[unassigned_project_incentive_pct]" class="form-control" value="<?= htmlspecialchars($settings['unassigned_project_incentive_pct'] ?? '20') ?>" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hierarchy Commissions -->
    <div class="col-lg-12 mb-4">
        <div class="card__wrapper">
            <h5 class="card__title mb-3">Hierarchy Commissions (Per 50 Lakhs)</h5>
            <form action="<?= $GLOBALS['base_url'] ?>/incentives/save-hierarchy" method="POST">
                <div class="row">
                    <?php foreach ($hierarchies as $h): ?>
                        <div class="col-md-6 mb-3">
                            <label><?= ucwords(str_replace('_', ' ', $h['role_type'])) ?> Threshold (Collection)</label>
                            <input type="number" step="0.01" name="thresholds[<?= $h['id'] ?>]" class="form-control mb-2" value="<?= $h['collection_threshold_amount'] ?? 5000000 ?>" required>
                            
                            <label><?= ucwords(str_replace('_', ' ', $h['role_type'])) ?> Reward Amount</label>
                            <input type="number" step="0.01" name="amounts[<?= $h['id'] ?>]" class="form-control" value="<?= $h['reward_amount'] ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update Hierarchy Rates</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Eligibility Rules -->
    <div class="col-lg-12 mb-4">
        <div class="card__wrapper">
            <h5 class="card__title mb-3">Eligibility Criteria (Target Achievement %)</h5>
            <form action="<?= $GLOBALS['base_url'] ?>/incentives/save-eligibility-rules" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Role / Level</th>
                                <th>Min Achievement (%)</th>
                                <th>Max Achievement (%)</th>
                                <th>Payout (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eligibility_rules as $rule): ?>
                                <tr>
                                    <td><strong><?= ucwords(str_replace('_', ' ', $rule['rule_type'])) ?></strong></td>
                                    <td>
                                        <input type="number" step="0.01" name="rules[<?= $rule['id'] ?>][min_achievement_pct]" class="form-control" value="<?= $rule['min_achievement_pct'] ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="rules[<?= $rule['id'] ?>][max_achievement_pct]" class="form-control" value="<?= $rule['max_achievement_pct'] ?>" placeholder="Leave blank for Infinity">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="rules[<?= $rule['id'] ?>][payout_pct]" class="form-control" value="<?= $rule['payout_pct'] ?>" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update Eligibility Rules</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Slabs Management -->
    <div class="col-lg-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title mb-0">Incentive Slabs</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#slabModal" onclick="resetSlabForm()">Add New Slab</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Min Volume</th>
                            <th>Max Volume</th>
                            <th>Percentage</th>
                            <th>Min Cap</th>
                            <th>Max Cap</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slabs)): ?>
                            <tr><td colspan="6" class="text-center">No slabs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($slabs as $slab): ?>
                                <tr>
                                    <td><?= htmlspecialchars($slab['location_name'] ?? 'All Locations') ?></td>
                                    <td><?= htmlspecialchars($slab['category_name'] ?? 'All Categories') ?><?= !empty($slab['main_category']) ? ' - ' . htmlspecialchars($slab['main_category']) : '' ?></td>
                                    <td><?= number_format($slab['min_volume'], 2) ?></td>
                                    <td><?= $slab['max_volume'] ? number_format($slab['max_volume'], 2) : '10000000+' ?></td>
                                    <td><?= number_format($slab['percentage'], 2) ?>%</td>
                                    <td><?= number_format($slab['lowest_incentive_cap'] ?? 0, 2) ?></td>
                                    <td><?= number_format($slab['highest_incentive_cap'] ?? 0, 2) ?></td>
                                    <td>
                                        <button type="button" class="table__icon edit" 
                                            onclick='editSlab(<?= json_encode($slab) ?>)'
                                            data-bs-toggle="modal" data-bs-target="#slabModal">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Slab Modal -->
<div class="modal fade" id="slabModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="slabModalTitle">Add Slab</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/incentives/save-slab" method="POST">
                    <input type="hidden" name="id" id="slab_id">
                    
                    <div class="mb-3">
                        <label>Location (Optional)</label>
                        <select name="location_id" id="slab_location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Category (Optional)</label>
                        <select name="category_id" id="slab_category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?><?= !empty($cat['main_category']) ? ' - ' . htmlspecialchars($cat['main_category']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Min Volume <span>*</span></label>
                        <input type="number" step="0.01" name="min_volume" id="slab_min" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Max Volume (Leave blank for unlimited)</label>
                        <input type="number" step="0.01" name="max_volume" id="slab_max" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Percentage <span>*</span></label>
                        <input type="number" step="0.01" name="percentage" id="slab_perc" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Lowest Incentive Cap</label>
                        <input type="number" step="0.01" name="lowest_incentive_cap" id="slab_min_cap" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Highest Incentive Cap</label>
                        <input type="number" step="0.01" name="highest_incentive_cap" id="slab_max_cap" class="form-control">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Slab</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function resetSlabForm() {
    document.getElementById('slabModalTitle').innerText = 'Add Slab';
    document.getElementById('slab_id').value = '';
    document.getElementById('slab_location').value = '';
    document.getElementById('slab_category').value = '';
    document.getElementById('slab_min').value = '';
    document.getElementById('slab_max').value = '';
    document.getElementById('slab_perc').value = '';
    document.getElementById('slab_min_cap').value = '';
    document.getElementById('slab_max_cap').value = '';
}

function editSlab(slab) {
    document.getElementById('slabModalTitle').innerText = 'Edit Slab';
    document.getElementById('slab_id').value = slab.id;
    document.getElementById('slab_location').value = slab.location_id || '';
    document.getElementById('slab_category').value = slab.category_id || '';
    document.getElementById('slab_min').value = slab.min_volume;
    document.getElementById('slab_max').value = slab.max_volume || '';
    document.getElementById('slab_perc').value = slab.percentage;
    document.getElementById('slab_min_cap').value = slab.lowest_incentive_cap || '';
    document.getElementById('slab_max_cap').value = slab.highest_incentive_cap || '';
}
</script>
