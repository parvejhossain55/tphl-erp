<?php
$page_title = "Create flat";
$page = 'Add flat';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/store-flat" method="POST">
            <div class="row gy-20">

                <!-- Project -->
                <div class="col-xl-6">
                    <label>Project *</label>
                    <select name="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Unit Type -->
                <div class="col-xl-6">
                    <label>Unit Type *</label>
                    <select name="unit_type" id="unitType" class="form-control" required>
                        <option value="residential">Residential (Flat)</option>
                        <option value="commercial">Commercial (Shop)</option>
                    </select>
                </div>

                <!-- Is Shop Checkbox -->
                <div class="col-xl-6 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="is_shop" id="isShopCheck" value="1">
                        <i class="bi bi-shop text-warning"></i>
                        <span class="facility-label">This is a Shop</span>
                    </label>
                </div>

                <!-- Flat / Shop Name -->
                <div class="col-xl-6">
                    <label id="flatNameLabel">Flat / Unit Name</label>
                    <input type="text" name="flat_name" class="form-control">
                </div>

                <!-- Sqft -->
                <div class="col-xl-6">
                    <label>Sqft</label>
                    <input type="number" name="sqft" class="form-control">
                </div>

                <!-- BHK (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bhkRow">
                    <label>BHK</label>
                    <input type="number" name="bhk" class="form-control">
                </div>

                <!-- Bedroom (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bedroomRow">
                    <label>Bedrooms</label>
                    <input type="number" name="bedroom" class="form-control">
                </div>

                <!-- Bathroom (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="bathroomRow">
                    <label>Bathrooms</label>
                    <input type="number" name="bathroom" class="form-control">
                </div>

                <!-- Balcony (hidden for commercial) -->
                <div class="col-xl-6 residential-only" id="balconyRow">
                    <label>Balconies</label>
                    <input type="number" name="balcony" class="form-control">
                </div>

                <!-- Floor No -->
                <div class="col-xl-6">
                    <label>Floor Number</label>
                    <input type="number" name="floor_no" class="form-control">
                </div>

                <!-- Drawing/Dining/Kitchen (hidden for commercial) -->
                <div class="col-xl-6 col-md-6 residential-only" id="drawingRow">
                    <label class="facility-box">
                        <input type="checkbox" name="drawing_dining_kitchen" value="1">
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Drawing/Dining/Kitchen</span>
                    </label>
                </div>
                <div class="col-xl-6 col-md-6 residential-only" id="commonDiningRow">
                    <label class="facility-box">
                        <input type="checkbox" name="common_dining" value="1">
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Common Dining</span>
                    </label>
                </div>

                <!-- Facing Site -->
                <div class="col-xl-6">
                    <label>Facing Site</label>
                    <input type="text" name="facing_site" class="form-control" placeholder="e.g., North, East">
                </div>

                <!-- Price Value -->
                <div class="col-xl-6">
                    <label>Price (BDT) <span class="text-danger">(Approx. Changeable)</span></label>
                    <input type="number" step="0.01" name="price_value" id="priceValue" class="form-control">
                </div>

                <!-- Shop Service Charge % (commercial only) -->
                <div class="col-xl-6 commercial-only" id="shopServiceRow" style="display:none;">
                    <label>Shop Service Charge <span class="text-warning">(%)</span></label>
                    <div class="input-group">
                        <input type="number" min="0" max="100" name="shop_service_charge" id="shopServiceCharge"
                            class="form-control" placeholder="e.g. 10">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Enter percentage only (e.g. 10 = 10%)</small>
                </div>

                <!-- Land Share Value -->
                <div class="col-xl-6">
                    <label>Land Share Value (BDT)
                        <span id="landShareAutoNote" class="text-info small" style="display:none;">(Auto: 30% of Price — editable)</span>
                    </label>
                    <input type="number" step="0.01" name="land_share_value" id="landShareValue" class="form-control">
                </div>

                <!-- Development Charges (residential only) -->
                <div class="col-xl-6 residential-only">
                    <label>Development Charges (BDT)</label>
                    <input type="number" step="0.01" name="development_charges" class="form-control" placeholder="0.00">
                </div>

                <!-- Facilities (residential only) -->
                <div class="col-xl-6 residential-only">
                    <label>Facilities</label>
                    <input type="text" name="facilities" class="form-control" placeholder="e.g., Gym, Pool, Parking">
                </div>

                <!-- Status -->
                <div class="col-xl-6">
                    <label>Status</label>
                    <div class="d-flex gap-3 mt-1">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="radio" name="status" id="available" value="available" checked>
                            <label class="form-check-label" for="available">Available</label>
                        </div>
                        <div class="form-check me-2">
                            <input class="form-check-input" type="radio" name="status" id="sold" value="sold">
                            <label class="form-check-label" for="sold">Sold</label>
                        </div>
                    </div>
                </div>

                <div class="submit__btn text-center">
                    <button class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const unitType      = document.getElementById('unitType');
    const isShopCheck   = document.getElementById('isShopCheck');
    const flatNameLabel = document.getElementById('flatNameLabel');
    const priceInput    = document.getElementById('priceValue');
    const landShare     = document.getElementById('landShareValue');
    const landShareNote = document.getElementById('landShareAutoNote');

    function applyUnitType() {
        const isCommercial = unitType.value === 'commercial';

        // Show/hide residential-only fields
        document.querySelectorAll('.residential-only').forEach(el => {
            el.style.display = isCommercial ? 'none' : '';
        });

        // Show/hide commercial-only fields
        document.querySelectorAll('.commercial-only').forEach(el => {
            el.style.display = isCommercial ? '' : 'none';
        });

        // Change label
        flatNameLabel.textContent = isCommercial ? 'Shop Name' : 'Flat / Unit Name';

        // Show/hide land share auto note
        landShareNote.style.display = isCommercial ? 'inline' : 'none';

        // Auto-fill land share only for commercial
        if (isCommercial) {
            autoFillLandShare();
        }
    }

    function autoFillLandShare() {
        if (unitType.value !== 'commercial') return;
        const price = parseFloat(priceInput.value) || 0;
        if (price > 0) {
            landShare.value = (price * 0.30).toFixed(2);
        }
    }

    unitType.addEventListener('change', applyUnitType);
    priceInput.addEventListener('input', autoFillLandShare);

    applyUnitType(); // run on load
});
</script>