<style>
@media (max-width: 767.98px) {
    /* Stack Title and Add Button */
    .card__title-wrap {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    
    .card__title-wrap a {
        width: 100% !important;
        text-align: center !important;
    }

    /* Side-by-side filter buttons on mobile */
    .submit__btn {
        display: flex !important;
        gap: 10px !important;
        width: 100% !important;
    }

    .submit__btn button,
    .submit__btn a {
        flex: 1 !important;
        margin: 0 !important;
        text-align: center !important;
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        height: 42px !important; /* Force a clean and consistent height */
        font-size: 14px !important;
    }
}
</style>

<!-- DEBUG: Show what's in letters array -->
<?php if (!empty($letters)): ?>
    <!-- DEBUG DATA (remove later) -->
    <div style="display:none;">
        <?php foreach ($letters as $letter): ?>
            <!-- id=<?= isset($letter['id']) ? $letter['id'] : 'NOT SET' ?>, employee_id=<?= $letter['employee_id'] ?>, type=<?= $letter['letter_type'] ?> -->
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Flash Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-20" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Issued Employee Letters</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#officialSignatureModal">
                        <i class="fa-solid fa-file-signature me-1"></i> Official HR Signature
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-letter-template" class="btn btn-primary btn-sm">
                        + Generate New Letter
                    </a>
                </div>
            </div>

            <!-- Filter bar -->
            <form method="GET" action="" class="card__wrapper mb-20">
                <div class="row g-15 gy-15 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_search">Search Employee / Subject</label>
                            </div>
                            <div class="form__input">
                                <input type="text" class="form-control" name="search" id="filter_search"
                                    placeholder="Name, ID, Subject, Ref..."
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_designation">Designation</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control select2" name="designation_id" id="filter_designation">
                                    <option value="">— All Designations —</option>
                                    <?php foreach ($designations as $designation): ?>
                                        <option value="<?= $designation['id'] ?>"
                                            <?= (isset($_GET['designation_id']) && $_GET['designation_id'] == $designation['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($designation['designation_name'] . ' (' . ($designation['department_name'] ?? 'N/A') . ')') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_type">Letter Type</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control" name="type" id="filter_type">
                                    <option value="">— All Types —</option>
                                    <option value="appointment" <?= ((isset($_GET['type']) && $_GET['type'] == 'appointment') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'appointment')) ? 'selected' : '' ?>>Appointment</option>
                                    <option value="joining"     <?= ((isset($_GET['type']) && $_GET['type'] == 'joining') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'joining'))         ? 'selected' : '' ?>>Joining</option>
                                    <option value="promotion"   <?= ((isset($_GET['type']) && $_GET['type'] == 'promotion') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'promotion'))     ? 'selected' : '' ?>>Promotion</option>
                                    <option value="increment"   <?= ((isset($_GET['type']) && $_GET['type'] == 'increment') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'increment'))     ? 'selected' : '' ?>>Increment</option>
                                    <option value="experience"  <?= ((isset($_GET['type']) && $_GET['type'] == 'experience') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'experience'))   ? 'selected' : '' ?>>Experience</option>
                                    <option value="noc"         <?= ((isset($_GET['type']) && $_GET['type'] == 'noc') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'noc'))                 ? 'selected' : '' ?>>NOC</option>
                                    <option value="salary_certificate" <?= ((isset($_GET['type']) && $_GET['type'] == 'salary_certificate') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'salary_certificate')) ? 'selected' : '' ?>>Salary Certificate</option>
                                    <option value="show_cause"  <?= ((isset($_GET['type']) && $_GET['type'] == 'show_cause') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'show_cause'))   ? 'selected' : '' ?>>Show Cause</option>
                                    <option value="warning"     <?= ((isset($_GET['type']) && $_GET['type'] == 'warning') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'warning'))         ? 'selected' : '' ?>>Warning</option>
                                    <option value="caution"     <?= ((isset($_GET['type']) && $_GET['type'] == 'caution') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'caution'))         ? 'selected' : '' ?>>Caution</option>
                                    <option value="improvement" <?= ((isset($_GET['type']) && $_GET['type'] == 'improvement') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'improvement')) ? 'selected' : '' ?>>Improvement</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="submit__btn d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit"><i class="fa fa-filter me-1"></i> Filter</button>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary flex-grow-1"><i class="fa fa-redo me-1"></i> Reset</a>
                        </div>
                    </div>

                </div>
            </form>

            <!-- Table -->
            <div class="card__wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Designation</th>
                                <th>Letter Type</th>
                                <th>Subject</th>
                                <th>Letter Date</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($letters)): ?>
                                <?php foreach ($letters as $index => $letter): ?>
                                <tr>
                                    <td><?= $index + 1 + ((($currentPage ?? 1) - 1) * ($limit ?? 15)) ?></td>
                                    <td>
                                        <strong><?= $letter['full_name'] ?></strong><br>
                                        <small class="text-muted"><?= $letter['employee_code'] ?></small>
                                    </td>
                                    <td><?= $letter['designation_name'] ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                                echo match($letter['letter_type']) {
                                                    'appointment'        => 'bg-primary',
                                                    'joining'            => 'bg-success',
                                                    'promotion'          => 'bg-info',
                                                    'increment'          => 'bg-warning text-dark',
                                                    'experience'         => 'bg-secondary',
                                                    'noc'                => 'bg-dark',
                                                    'caution'            => 'bg-danger text-white',
                                                    'warning'            => 'bg-danger text-white',
                                                    'show_cause'         => 'bg-danger text-white',
                                                    'improvement'        => 'bg-warning text-dark',
                                                    'salary_certificate' => 'text-white',
                                                    default              => 'bg-secondary',
                                                };
                                            ?>"
                                            <?= $letter['letter_type'] === 'salary_certificate' ? 'style="background:#0d7377;"' : '' ?>>
                                            <?= ucwords(str_replace('_', ' ', $letter['letter_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($letter['subject']) ?></td>
                                    <td><?= date('d M Y', strtotime($letter['letter_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= $letter['status'] == 'issued' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= ucfirst($letter['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="<?= $GLOBALS['base_url'] ?>/view-letter/<?= $letter['id'] ?>" class="btn btn-info btn-sm" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-letter/<?= $letter['id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm" target="_blank" title="PDF">
                                                <i class="fa fa-file-pdf"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>" class="btn btn-secondary btn-sm" target="_blank" title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/delete-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No issued letters found.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <?php if (!empty($totalPages) && $totalPages > 1): ?>
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                    $startItem = ((($currentPage ?? 1) - 1) * ($limit ?? 15)) + 1;
                    $endItem = min($totalLetters ?? count($letters), ($currentPage ?? 1) * ($limit ?? 15));
                    ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-20 pt-15 border-top">
                        <div class="text-muted small">
                            Showing <?= $startItem ?> to <?= $endItem ?> of <?= $totalLetters ?? count($letters) ?> letters
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= (($currentPage ?? 1) <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= ($currentPage ?? 1) - 1 ?><?= $queryString ?>">
                                        <i class="fa fa-chevron-left"></i> Prev
                                    </a>
                                </li>
                                <?php
                                $cur = $currentPage ?? 1;
                                $startPage = max(1, $cur - 2);
                                $endPage = min($totalPages, $cur + 2);
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=1<?= $queryString ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                    <li class="page-item <?= ($p == $cur) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $p ?><?= $queryString ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $totalPages ?><?= $queryString ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item <?= ($cur >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $cur + 1 ?><?= $queryString ?>">
                                        Next <i class="fa fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
            <!-- End Table -->

        </div>
    </div>
</div>

<!-- ═══ Official HR Signature Modal ═══════════════════════════════════════════ -->
<div id="officialSignatureModal" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-signature text-primary me-2"></i>Official HR Digital Signature
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="officialSigForm" action="<?= $GLOBALS['base_url'] ?>/update-hr-signature" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="is_ajax" value="1">

                    <div id="modal_sig_alert" class="alert d-none mb-3" role="alert"></div>

                    <!-- Current Official Signature -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark mb-1">Current Official Signature</label>
                        <p class="text-muted small mb-2">This is the default signature printed on all official company letters and salary certificates.</p>
                        <?php
                        $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
                        $officialSigExists = file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png');
                        ?>
                        <div class="p-3 border rounded text-center" style="background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 16px 16px; background-position: 0 0, 8px 8px;">
                            <div id="no_official_sig_placeholder" class="<?= $officialSigExists ? 'd-none' : '' ?> text-muted py-2">
                                <i class="fa-solid fa-signature fs-3 text-secondary mb-2 d-block"></i>
                                <span>No official signature uploaded yet.</span><br>
                                <small class="text-secondary">Upload a signature photo or scan below to set the company default signature.</small>
                            </div>
                            <img id="current_official_sig_img" src="<?= $officialSigExists ? ($GLOBALS['base_url'] . '/public/assets/images/hrm/hr_signature.png?v=' . time()) : '' ?>" alt="Official HR Signature" class="<?= $officialSigExists ? '' : 'd-none' ?>" style="max-height: 70px; max-width: 250px; object-fit: contain;">
                        </div>
                    </div>

                    <hr>

                    <!-- Upload New Signature Photo -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Upload New Signature Photo / Scan</label>
                        <p class="text-muted small mb-2">
                            Take a photo of the signature on paper with your mobile phone or scanner. Our AI background extractor will automatically remove shadows and paper texture, enhance ink lines, and save it with 100% transparent background.
                        </p>
                        <input type="file" name="official_signature" id="modal_official_sig_file" class="form-control" accept="image/*" required>
                    </div>

                    <!-- Live Extraction Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Live Extraction Preview (Instant Result)</label>
                        <div id="modal_sig_preview_box" class="p-3 border rounded text-center" style="min-height: 90px; display: flex; align-items: center; justify-content: center; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 16px 16px; background-position: 0 0, 8px 8px;">
                            <span id="modal_sig_preview_placeholder" class="text-muted small">Choose a file above to preview background removal</span>
                            <canvas id="modal_sig_preview_canvas" style="display: none; max-height: 80px; max-width: 100%; object-fit: contain;"></canvas>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn_save_official_sig" class="btn btn-primary">
                            <span id="btn_save_text"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Extract & Save Signature</span>
                            <span id="btn_save_spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalFileInput = document.getElementById('modal_official_sig_file');
    var canvas = document.getElementById('modal_sig_preview_canvas');
    var placeholder = document.getElementById('modal_sig_preview_placeholder');
    var form = document.getElementById('officialSigForm');
    var alertBox = document.getElementById('modal_sig_alert');
    var currentImg = document.getElementById('current_official_sig_img');
    var btnSave = document.getElementById('btn_save_official_sig');
    var btnSaveText = document.getElementById('btn_save_text');
    var btnSaveSpinner = document.getElementById('btn_save_spinner');

    if (modalFileInput) {
        modalFileInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function (event) {
                var img = new Image();
                img.onload = function () {
                    var w = img.width;
                    var h = img.height;
                    var maxD = 1200;
                    if (w > maxD || h > maxD) {
                        var scale = Math.min(maxD / w, maxD / h);
                        w = Math.floor(w * scale);
                        h = Math.floor(h * scale);
                    }

                    var offCanvas = document.createElement('canvas');
                    offCanvas.width = w;
                    offCanvas.height = h;
                    var offCtx = offCanvas.getContext('2d');
                    offCtx.drawImage(img, 0, 0, w, h);

                    var imgData = offCtx.getImageData(0, 0, w, h);
                    var data = imgData.data;

                    var minX = w, maxX = 0, minY = h, maxY = 0;
                    var threshold = 205;

                    for (var y = 0; y < h; y++) {
                        for (var x = 0; x < w; x++) {
                            var idx = (y * w + x) * 4;
                            var r = data[idx];
                            var g = data[idx + 1];
                            var b = data[idx + 2];
                            var lum = 0.299 * r + 0.587 * g + 0.114 * b;

                            if (lum >= threshold) {
                                data[idx + 3] = 0; // Remove paper background
                            } else {
                                var alphaRatio = (threshold - lum) / threshold;
                                var alpha = Math.min(255, Math.floor(alphaRatio * 255 * 1.5));
                                data[idx + 3] = alpha;
                                data[idx] = Math.max(0, r - 50);
                                data[idx + 1] = Math.max(0, g - 50);
                                data[idx + 2] = Math.max(0, b - 40);

                                if (x < minX) minX = x;
                                if (x > maxX) maxX = x;
                                if (y < minY) minY = y;
                                if (y > maxY) maxY = y;
                            }
                        }
                    }

                    offCtx.putImageData(imgData, 0, 0);

                    if (maxX >= minX && maxY >= minY) {
                        var pad = 10;
                        minX = Math.max(0, minX - pad);
                        minY = Math.max(0, minY - pad);
                        maxX = Math.min(w - 1, maxX + pad);
                        maxY = Math.min(h - 1, maxY + pad);
                        var cropW = maxX - minX + 1;
                        var cropH = maxY - minY + 1;

                        canvas.width = cropW;
                        canvas.height = cropH;
                        var ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, cropW, cropH);
                        ctx.drawImage(offCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);
                    } else {
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(offCanvas, 0, 0);
                    }

                    if (placeholder) placeholder.style.display = 'none';
                    canvas.style.display = 'inline-block';
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!modalFileInput.files || !modalFileInput.files[0]) {
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = 'Please choose a signature file first.';
                alertBox.classList.remove('d-none');
                return;
            }

            var formData = new FormData(form);
            btnSave.disabled = true;
            btnSaveSpinner.classList.remove('d-none');
            alertBox.classList.add('d-none');

            fetch('<?= $GLOBALS['base_url'] ?>/update-hr-signature', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btnSave.disabled = false;
                btnSaveSpinner.classList.add('d-none');

                if (data.success) {
                    alertBox.className = 'alert alert-success';
                    alertBox.textContent = data.message || 'Signature updated successfully!';
                    alertBox.classList.remove('d-none');

                    if (currentImg && data.url) {
                        currentImg.src = data.url;
                        currentImg.classList.remove('d-none');
                        var noSigEl = document.getElementById('no_official_sig_placeholder');
                        if (noSigEl) noSigEl.classList.add('d-none');
                    }
                    setTimeout(function () {
                        var modalEl = document.getElementById('officialSignatureModal');
                        if (typeof bootstrap !== 'undefined') {
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                    }, 1200);
                } else {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = data.message || 'Failed to update signature.';
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(function (err) {
                btnSave.disabled = false;
                btnSaveSpinner.classList.add('d-none');
                alertBox.className = 'alert alert-danger';
                alertBox.textContent = 'Server error occurred. Please try again.';
                alertBox.classList.remove('d-none');
            });
        });
    }
});
</script>