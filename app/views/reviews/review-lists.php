  <?php
    $page_title = "Review List";
    $page = 'Review Lists';
    $show_breadcrumb = true;
    ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <h4 class="mb-2 mb-md-0">Review Management</h4>
      <div class="d-flex gap-2">
          <a href="<?= $GLOBALS['base_url'] ?>/create-review" class="btn btn-primary">
              <i class="fa-light fa-plus me-1"></i> Add Review
          </a>
      </div>
  </div>

  <div class="row align-items-stretch">
      <?php foreach ($reviews as $review): ?>
          <div class="col-xxl-4 col-md-6 col-12 d-flex">
              <div class="card__wrapper flex-column w-100 h-100">

                  <!-- Status & Description -->
                  <div class="task__wrapper flex-grow-1">
                      <div class="task__top-content mb-2">
                          <div class="d-flex align-items-center g-2 text-success">
                              <i class="fa-solid fa-circle blink"></i>
                              <span class="d-block"><?= ucfirst($review['status']) ?></span>
                          </div>
                      </div>

                      <p class="review__description mb-3"><?= $review['review_text'] ?></p>

                      <!-- Video -->
                      <?php if (!empty($review['review_video_url'])): ?>
                          <div class="mb-3">
                              <?= renderReviewVideo($review['review_video_url'], '100%', '200') ?>
                          </div>
                      <?php endif; ?>

                      <small class="d-block mb-3">Review Date: <?= date('d M, Y', strtotime($review['review_date'])) ?></small>
                  </div>

                  <!-- Reviewer Info -->
                  <div class="review__team mb-3 d-flex align-items-center gap-2">
                      <div class="avatar">
                          <img class="img-36 border-circle"
                              src="<?= getImage('reviewers', $review['reviewer_image']) ?>"
                              alt="<?= $review['reviewer_name'] ?>">
                      </div>
                      <div>
                          <span class="fw-bold"><?= $review['reviewer_name'] ?></span><br>
                          <small class="text-muted"><?= $review['reviewer_role'] ?></small>
                      </div>
                  </div>

                  <!-- Rating -->
                  <div class="review__progress mb-3">
                      <span class="review__meta-title mb-1 d-block">Rating</span>
                      <div class="Rating">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                              <?php if ($i <= $review['rating']): ?>
                                  <i class="fa-solid fa-star text-warning"></i>
                              <?php else: ?>
                                  <i class="fa-regular fa-star text-muted"></i>
                              <?php endif; ?>
                          <?php endfor; ?>
                      </div>
                  </div>

                  <!-- Review Actions -->
                  <div class="review__btn mt-auto d-flex justify-content-between">
                      <a class="btn btn-outline-theme-border" href="<?= $base_url ?>/edit-review/<?= $review['id'] ?>">Edit Review</a>
                      <button class="removeBtnCust btn btn-outline-theme-border delete"
                          data-id="<?= $review['id'] ?>"
                          data-name="<?= $review['reviewer_name'] ?>"
                          data-action="delete-review">
                          Delete
                      </button>
                  </div>

              </div>
          </div>
      <?php endforeach; ?>
  </div>

  <script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
  </script>