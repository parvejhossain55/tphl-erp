<?php
$page_title = "Edit Review";
?>

<div class="row">
    <div class="card__wrapper p-4 w-100">

        <h4 class="mb-4">Edit Review</h4>

        <form action="<?= $GLOBALS['base_url'] ?>/update-review/<?= $review['id'] ?>" method="POST" enctype="multipart/form-data">

            <div class="row">

                <!-- Reviewer Name -->
                <div class="col-6 mb-3">
                    <label for="reviewer_name" class="title">Reviewer Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" value="<?= htmlspecialchars($review['reviewer_name']) ?>" required>
                </div>

                <!-- Reviewer Role -->
                <div class="col-6 mb-3">
                    <label for="reviewer_role" class="title">Role</label>
                    <input type="text" class="form-control" id="reviewer_role" name="reviewer_role" value="<?= htmlspecialchars($review['reviewer_role']) ?>" placeholder="Buyer / Investor / Tenant">
                </div>

                <!-- Avatar Image -->
                <div class="col-6 mb-3">
                    <label for="reviewer_image" class="title">Avatar Image</label>
                    <?php if(!empty($review['reviewer_image'])): ?>
                        <div class="mb-2">
                            <img src="<?= getImage('reviewers', $review['reviewer_image']) ?>" alt="Reviewer Image" class="img-thumbnail" style="height:80px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="reviewer_image" name="reviewer_image" accept="image/*">
                </div>

                <!-- Video URL -->
                <div class="col-6 mb-3">
                    <label for="review_video_url" class="title">Video URL (YouTube/Vimeo)</label>
                    <input type="text" class="form-control" id="review_video_url" name="review_video_url" value="<?= htmlspecialchars($review['review_video_url']) ?>" placeholder="https://youtube.com/...">
                </div>

                <!-- Rating -->
                <div class="col-6 mb-3">
                    <label for="rating" class="title">Rating</label>
                    <select class="form-select" id="rating" name="rating" required>
                        <option value="">Select Rating</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $review['rating']==$i?'selected':'' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Review Text -->
                <div class="col-6 mb-3">
                    <label for="review_text" class="title">Review Text <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="review_text" name="review_text" rows="5" required><?= htmlspecialchars($review['review_text']) ?></textarea>
                </div>

                <!-- Review Date -->
                <div class="col-6 mb-3">
                    <label for="review_date" class="title">Review Date</label>
                    <input type="date" class="form-control" id="review_date" name="review_date" value="<?= $review['review_date'] ?? date('Y-m-d') ?>">
                </div>

                <!-- Status -->
                <div class="col-6 mb-3">
                    <label for="status" class="title">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $review['status']=='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $review['status']=='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>

            </div> <!-- End row -->

            <!-- Submit Buttons -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update Review</button>
                <a href="<?= $base_url ?>/reviews" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>
