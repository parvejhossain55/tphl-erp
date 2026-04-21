<?php
$page_title = "Edit Flat";
$page = 'Edit Flat';
$show_breadcrumb = true;


$selectedProjectId = $_SESSION['old']['project_id'] ?? $flat['project_id'] ?? null;
$flatNameValue = $_SESSION['old']['flat_name'] ?? $flat['flat_name'] ?? '';
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/update-flat/<?= $flat['id'] ?>" method="POST">
            <div class="row gy-20">

                <!-- Project -->
                <div class="col-xl-6">
                    <label>Project *</label>
                    <select name="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                <?= ($selectedProjectId == $project['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($project['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Flat Name -->
                <div class="col-xl-6">
                    <label>Flat/Unit Name</label>
                    <input type="text" name="flat_name" class="form-control" value="<?= htmlspecialchars($flatNameValue) ?>">
                </div>
                <!-- Sqft -->
                <div class="col-xl-6">
                    <label>Sqft</label>
                    <input type="number" name="sqft" class="form-control" value="<?= $flat['sqft'] ?? '' ?>">
                </div>

                <!-- BHK -->
                <div class="col-xl-6">
                    <label>BHK</label>
                    <input type="number" name="bhk" class="form-control" value="<?= $flat['bhk'] ?? '' ?>">
                </div>

                <!-- Bedroom -->
                <div class="col-xl-6">
                    <label>Bedrooms</label>
                    <input type="number" name="bedroom" class="form-control" value="<?= $flat['bedroom'] ?? '' ?>">
                </div>

                <!-- Bathroom -->
                <div class="col-xl-6">
                    <label>Bathrooms</label>
                    <input type="number" name="bathroom" class="form-control" value="<?= $flat['bathroom'] ?? '' ?>">
                </div>

                <!-- Balcony -->
                <div class="col-xl-6">
                    <label>Balconies</label>
                    <input type="number" name="balcony" class="form-control" value="<?= $flat['balcony'] ?? '' ?>">
                </div>
                <!-- Floor No -->
                <div class="col-xl-6">
                    <label>Floor Number</label>
                    <input type="number" name="floor_no" class="form-control" value="<?= $flat['floor_no'] ?? '' ?>">
                </div>


                <div class="col-xl-6 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="drawing_dining_kitchen" value="1" <?= ($flat['drawing_dining_kitchen'] ?? '') == '1' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Drawing/Dining/Kitchen</span>
                    </label>
                </div>
                <div class="col-xl-6 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="common_dining" value="1" <?= ($flat['common_dining'] ?? '') == '1' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Common Dining</span>
                    </label>

                </div>
                <!-- Facing Site -->
                <div class="col-xl-6">
                    <label>Facing Site</label>
                    <input type="text" name="facing_site" class="form-control" value="<?= $flat['facing_site'] ?? '' ?>">
                </div>
                <!--Land Share Price Value -->
                <div class="col-xl-6">
                    <label>Land Share Value (BDT)</label>
                    <input type="number" step="0.01" name="land_share_value" class="form-control" value="<?= $flat['land_share_value'] ?? '' ?>">
                </div>

                <!-- Development Charges -->
                <div class="col-xl-6">
                    <label>Development Charges (BDT)</label>
                    <input type="number" step="0.01" name="development_charges" class="form-control" value="<?= $flat['development_charges'] ?? '' ?>" placeholder="0.00">
                </div>

                <!-- Price Value -->
                <div class="col-xl-6">
                    <label>Price (BDT)</label>
                    <input type="number" step="0.01" name="price_value" class="form-control" value="<?= $flat['price_value'] ?? '' ?>">
                </div>
              

                <!-- Facilities -->
                <div class="col-xl-6">
                    <label>Facilities</label>
                    <input type="text" name="facilities" class="form-control" value="<?= $flat['facilities'] ?? '' ?>">
                </div>


                <div class="submit__btn text-center">
                    <button class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>