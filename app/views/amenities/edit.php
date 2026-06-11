<?php
$page = 'Edit Amenities';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card__wrapper">
            <h4 class="mb-4">Edit Amenities</h4>
            <form action="<?= $GLOBALS['base_url'] ?>/update-amenity/<?= $location_id ?>" method="POST" enctype="multipart/form-data">
                <div class="row gy-4">
                    <!-- Location Selection -->
                    <div class="col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="location_id">Select Location<span>*</span></label>
                            </div>
                            <div class="form__input">
                                <select class="form-select" name="location_id" id="location_id" required>
                                    <option value="" disabled>Select a location...</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= $loc['id'] ?>" <?= ($loc['id'] == $location_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Amenities Section -->
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0">Amenities List</h5>
                            <button type="button" class="btn btn-sm btn-info" id="addAmenityBtn">
                                <i class="fa fa-plus"></i> Add Amenity
                            </button>
                        </div>

                        <div id="amenitiesContainer">
                            <?php if (!empty($amenities)): ?>
                                <?php foreach ($amenities as $amenity): ?>
                                    <div class="amenity-row card p-3 mb-3 border-light shadow-sm">
                                        <input type="hidden" name="amenity_id[]" value="<?= $amenity['id'] ?>">
                                        <input type="hidden" name="old_image[]" value="<?= htmlspecialchars($amenity['image']) ?>">
                                        
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="amenity_name[]" class="form-control" value="<?= htmlspecialchars($amenity['name']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Image (Leave empty to keep current)</label>
                                                    <input type="file" name="amenity_image[]" class="form-control image-input" accept="image/*">
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <?php $imgSrc = !empty($amenity['image']) ? $GLOBALS['base_url'] . '/public/uploads/amenities/' . $amenity['image'] : ''; ?>
                                                <img src="<?= $imgSrc ?>" class="img-preview rounded" style="max-height: 60px; max-width: 100%; <?= empty($imgSrc) ? 'display: none;' : 'display: inline-block;' ?>" alt="Preview">
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-amenity" title="Remove">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback if somehow there are no amenities -->
                                <div class="amenity-row card p-3 mb-3 border-light shadow-sm">
                                    <input type="hidden" name="amenity_id[]" value="">
                                    <input type="hidden" name="old_image[]" value="">
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="amenity_name[]" class="form-control" placeholder="e.g. Swimming Pool" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Image <span class="text-danger">*</span></label>
                                                <input type="file" name="amenity_image[]" class="form-control image-input" accept="image/*" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <img src="" class="img-preview rounded" style="max-height: 60px; max-width: 100%; display: none;" alt="Preview">
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-danger remove-amenity" title="Remove">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-primary">Update Amenities</button>
                        <a href="<?= $GLOBALS['base_url'] ?>/amenities" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById("amenitiesContainer");
    const addBtn = document.getElementById("addAmenityBtn");

    // Add new row
    addBtn.addEventListener("click", function() {
        const rowHTML = `
            <div class="amenity-row card p-3 mb-3 border-light shadow-sm">
                <input type="hidden" name="amenity_id[]" value="">
                <input type="hidden" name="old_image[]" value="">
                
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="amenity_name[]" class="form-control" placeholder="e.g. Gym" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Image <span class="text-danger">*</span></label>
                            <input type="file" name="amenity_image[]" class="form-control image-input" accept="image/*" required>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <img src="" class="img-preview rounded" style="max-height: 60px; max-width: 100%; display: none;" alt="Preview">
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-danger remove-amenity" title="Remove">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', rowHTML);
    });

    // Handle remove and image preview dynamically using event delegation
    container.addEventListener("click", function(e) {
        // Remove row
        if (e.target.closest(".remove-amenity")) {
            const rows = container.querySelectorAll(".amenity-row");
            if (rows.length > 1) {
                e.target.closest(".amenity-row").remove();
            } else {
                alert("You must have at least one amenity.");
            }
        }
    });

    container.addEventListener("change", function(e) {
        // Image preview
        if (e.target.classList.contains("image-input")) {
            const file = e.target.files[0];
            const row = e.target.closest(".amenity-row");
            const preview = row.querySelector(".img-preview");

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = "inline-block";
                };
                reader.readAsDataURL(file);
            } else {
                // If they clear the file input but there's an old image
                const oldImageInput = row.querySelector("input[name='old_image[]']");
                if (oldImageInput && oldImageInput.value) {
                    preview.src = "<?= $GLOBALS['base_url'] ?>/public/uploads/amenities/" + oldImageInput.value;
                    preview.style.display = "inline-block";
                } else {
                    preview.src = "";
                    preview.style.display = "none";
                }
            }
        }
    });
});
</script>
