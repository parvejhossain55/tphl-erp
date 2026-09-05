<?php
$page_title = "Edit Flat";
$page = 'Edit Flat';
$show_breadcrumb = true;

$selectedProjectId = $_SESSION['old']['project_id'] ?? $flat['project_id'] ?? null;
$flatNameValue     = $_SESSION['old']['flat_name']   ?? $flat['flat_name']   ?? '';
$unitType          = $_SESSION['old']['unit_type']   ?? $flat['unit_type']   ?? 'residential';
$isShop            = (int)($flat['is_shop'] ?? 0);
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/update-flat/<?= $flat['id'] ?>" method="POST">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? $GLOBALS['base_url'] . '/flat-lists') ?>">
            <div class="row gy-20">

                <!-- Project -->
                <div class="col-xl-6">
                    <label>Project *</label>
                    <select name="project_id" class="form-control select2" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Unit Type -->
                <div class="col-xl-6">
                    <label>Unit Type *</label>
                    <select name="unit_type" id="unitType" class="form-control" required>
                        <option value="residential" <?= $unitType === 'residential' ? 'selected' : '' ?>>Residential (Flat)</option>
                        <option value="commercial"  <?= $unitType === 'commercial'  ? 'selected' : '' ?>>Commercial (Shop)</option>
                    </select>
                </div>

                <!-- Is Shop Checkbox -->
                <div class="col-xl-6 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="is_shop" id="isShopCheck" value="1" <?= $isShop ? 'checked' : '' ?>>
                        <i class="bi bi-shop text-warning"></i>
                        <span class="facility-label">This is a Shop</span>
                    </label>
                </div>

                <!-- Flat / Shop Name -->
                <div class="col-xl-6">
                    <label id="flatNameLabel"><?= $unitType === 'commercial' ? 'Shop Name' : 'Flat / Unit Name' ?></label>
                    <input type="text" name="flat_name" class="form-control" value="<?= htmlspecialchars($flatNameValue) ?>">
                </div>

                <!-- Sqft -->
                <div class="col-xl-6">
                    <label>Sqft</label>
                    <input type="number" name="sqft" class="form-control" value="<?= $flat['sqft'] ?? '' ?>">
                </div>

                <!-- BHK (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bhkRow">
                    <label>BHK</label>
                    <input type="number" name="bhk" class="form-control" value="<?= $flat['bhk'] ?? '' ?>">
                </div>

                <!-- Bedroom (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bedroomRow">
                    <label>Bedrooms</label>
                    <input type="number" name="bedroom" class="form-control" value="<?= $flat['bedroom'] ?? '' ?>">
                </div>

                <!-- Bathroom (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bathroomRow">
                    <label>Bathrooms</label>
                    <input type="number" name="bathroom" class="form-control" value="<?= $flat['bathroom'] ?? '' ?>">
                </div>

                <!-- Balcony (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="balconyRow">
                    <label>Balconies</label>
                    <input type="number" name="balcony" class="form-control" value="<?= $flat['balcony'] ?? '' ?>">
                </div>

                <!-- Floor No -->
                <div class="col-xl-6">
                    <label>Floor Number</label>
                    <input type="number" name="floor_no" class="form-control" value="<?= $flat['floor_no'] ?? '' ?>">
                </div>

                <!-- Drawing/Dining/Kitchen (hidden for commercial) -->
                <div class="col-xl-6 col-md-6 residential-only" id="drawingRow">
                    <label class="facility-box">
                        <input type="checkbox" name="drawing_dining_kitchen" value="1"
                            <?= ($flat['drawing_dining_kitchen'] ?? '') == '1' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Drawing/Dining/Kitchen</span>
                    </label>
                </div>
                <div class="col-xl-6 col-md-6 residential-only" id="commonDiningRow">
                    <label class="facility-box">
                        <input type="checkbox" name="common_dining" value="1"
                            <?= ($flat['common_dining'] ?? '') == '1' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Common Dining</span>
                    </label>
                </div>

                <!-- Facing Site -->
                <div class="col-xl-6">
                    <label>Facing Site</label>
                    <input type="text" name="facing_site" class="form-control" value="<?= $flat['facing_site'] ?? '' ?>">
                </div>

                <!-- Price Value -->
                <div class="col-xl-6">
                    <label>Price (BDT)</label>
                    <input type="number" step="0.01" name="price_value" id="priceValue" class="form-control" value="<?= $flat['price_value'] ?? '' ?>">
                </div>

                <!-- Shop Service Charge % (commercial only) -->
                <div class="col-xl-6 commercial-only" id="shopServiceRow" style="<?= $unitType === 'commercial' ? '' : 'display:none;' ?>">
                    <label>Shop Service Charge <span class="text-warning">(%)</span></label>
                    <div class="input-group">
                        <input type="number" min="0" max="100" name="shop_service_charge"
                            class="form-control" value="<?= $flat['shop_service_charge'] ?? '' ?>" placeholder="e.g. 10">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Enter percentage only (e.g. 10 = 10%)</small>
                </div>

                <!-- Land Share Value -->
                <div class="col-xl-6">
                    <label>Land Share Value (BDT)
                        <span id="landShareAutoNote" class="text-info small" style="display:none;">(Auto: 30% of Price — editable)</span>
                    </label>
                    <input type="number" step="0.01" name="land_share_value" id="landShareValue" class="form-control" value="<?= $flat['land_share_value'] ?? '' ?>">
                </div>

                <!-- Development Charges (residential only) -->
                <div class="col-xl-6 residential-only">
                    <label>Development Charges (BDT)</label>
                    <input type="number" step="0.01" name="development_charges" class="form-control"
                        value="<?= $flat['development_charges'] ?? '' ?>" placeholder="0.00">
                </div>

                <!-- Facilities (residential only) -->
                <div class="col-xl-6 residential-only">
                    <label>Facilities</label>
                    <input type="text" name="facilities" class="form-control" value="<?= $flat['facilities'] ?? '' ?>">
                </div>

                <div class="submit__btn text-center">
                    <button class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const unitType      = document.getElementById('unitType');
    const flatNameLabel = document.getElementById('flatNameLabel');
    const landShareNote = document.getElementById('landShareAutoNote');

    function applyUnitType(autoFill) {
        const isCommercial = unitType.value === 'commercial';

        document.querySelectorAll('.residential-only').forEach(el => {
            el.style.display = isCommercial ? 'none' : '';
        });

        document.querySelectorAll('.commercial-only').forEach(el => {
            el.style.display = isCommercial ? '' : 'none';
        });

        flatNameLabel.textContent = isCommercial ? 'Shop Name' : 'Flat / Unit Name';
        // land_share_value auto-fill disabled on edit — existing value is kept as-is
        landShareNote.style.display = 'none';
    }

    unitType.addEventListener('change', () => applyUnitType(false));

    // On page load: apply UI without overwriting existing land_share_value
    applyUnitType(false);
});
</script>