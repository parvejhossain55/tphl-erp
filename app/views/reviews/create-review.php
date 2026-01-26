<?php
$page_title = "Create Review";
?>

<div class="row">
    <div class="card__wrapper p-4 w-100">

        <h4 class="mb-4">Add New Review</h4>

        <form action="<?= $GLOBALS['base_url'] ?>/store-review" method="POST" enctype="multipart/form-data">

            <div class="row ">

                <!-- Reviewer Name -->
                <div class="col-6 mb-4">
                    <label for="reviewer_name" class="title">Reviewer Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" required>
                </div>

                <!-- Reviewer Role -->
                <div class="col-6 mb-3">
                    <label for="reviewer_role" class="title">Role</label>
                    <input type="text" class="form-control" id="reviewer_role" name="reviewer_role" placeholder="Buyer / Investor / Tenant">
                </div>

                <!-- Avatar Image -->
                <div class="col-6 mb-3">
                    <label for="reviewer_image" class="title">Avatar Image</label>
                    <input type="file" class="form-control" id="reviewer_image" name="reviewer_image" accept="image/*">
                </div>

                <!-- Video URL -->
                <div class="col-6 mb-3">
                    <label for="review_video_url" class="title">Video URL (YouTube/Vimeo)</label>
                    <input type="text" class="form-control" id="review_video_url" name="review_video_url" placeholder="https://youtube.com/...">
                </div>

                <!-- Optional Video Upload -->
                <!-- <div class="col-6 mb-3">
                    <label for="review_video_file" class="title">Or Upload Video (MP4/WebM/Ogg)</label>
                    <input type="file" class="form-control" id="review_video_file" name="review_video_file" accept="video/*">
                </div> -->

                <!-- Rating -->
                <div class="col-6 mb-3">
                    <label for="rating" class="title">Rating</label>
                    <select class="form-select" id="rating" name="rating" required>
                        <option value="">Select Rating</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Review Text (Full Width) -->
                <div class="col-6">
                    <label for="review_text" class="title">Review Text <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="review_text" name="review_text" rows="5" required></textarea>
                </div>

                <!-- Review Date -->
                <div class="col-6 mb-3">
                    <label for="review_date" class="title">Review Date</label>
                    <input type="date" class="form-control" id="review_date" name="review_date" value="<?= date('Y-m-d') ?>">
                </div>

                <!-- Status -->
                <div class="col-6 mb-3">
                    <label for="status" class="title">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

            </div> <!-- End row -->

            <!-- Submit Buttons -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Review</button>
                <a href="<?= $base_url ?>/reviews" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>