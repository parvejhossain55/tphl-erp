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


                <!-- Flat -->
                <div class="col-xl-6">
                    <label>Flat Name </label>
                    <input type="text" name="flat_name" class="form-control">
                </div>
                <!-- Sqft -->
                <div class="col-xl-6">
                    <label>Sqft</label>
                    <input type="number" name="sqft" class="form-control">
                </div>

                <!-- BHK -->
                <div class="col-xl-6">
                    <label>BHK</label>
                    <input type="number" name="bhk" class="form-control">
                </div>

                <!-- Bedroom -->
                <div class="col-xl-6">
                    <label>Bedrooms</label>
                    <input type="number" name="bedroom" class="form-control">
                </div>

                <!-- Bathroom -->
                <div class="col-xl-6">
                    <label>Bathrooms</label>
                    <input type="number" name="bathroom" class="form-control">
                </div>

                <!-- Balcony -->
                <div class="col-xl-6">
                    <label>Balconies</label>
                    <input type="number" name="balcony" class="form-control">
                </div>
                <!-- Floor No -->
                <div class="col-xl-6">
                    <label>Floor Number</label>
                    <input type="number" name="floor_no" class="form-control">
                </div>


                <div class="col-xl-6 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="drawing_dining_kitchen" value="1">
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Drawing/Dining/Kitchen</span>
                    </label>
                </div>
                <div class="col-xl-6 col-md-6">
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

                <!-- Land Share Value -->
                <div class="col-xl-6">
                    <label>Land Share Value (BDT)</label>
                    <input type="number" step="0.01" name="land_share_value" class="form-control">
                </div>

                <!-- Development Charges -->
                <div class="col-xl-6">
                    <label>Development Charges (BDT)</label>
                    <input type="number" step="0.01" name="development_charges" class="form-control" placeholder="0.00">
                </div>

                <!--Price -->
                <div class="col-xl-6">
                    <label>Price (BDT) <span class="text-danger"> (Approx.Changable)</span></label>
                    <input type="number" step="0.01" name="price_value" class="form-control">
                </div>

                <!-- Facilities -->
                <div class="col-xl-6">
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
                        <!-- <div class="form-check me-2">
                            <input class="form-check-input" type="radio" name="status" id="booked" value="booked">
                            <label class="form-check-label" for="booked">Booked</label>
                        </div> -->
                    </div>
                </div>


                <div class="submit__btn text-center">
                    <button class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>