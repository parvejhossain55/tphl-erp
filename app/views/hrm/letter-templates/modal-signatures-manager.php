<!-- ═══ Official HR Digital Signatures Management Modal ═════════════════════════ -->
<div id="officialSignatureModal" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fa-solid fa-file-signature text-primary me-2"></i>Official HR Digital Signatures
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Manage multiple senior signatories, set company default, and remove resigned executives.</p>
                </div>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body p-4">
                <div id="modal_sig_alert" class="alert d-none mb-3" role="alert"></div>

                <!-- ── Section 1: Active Registered Signatures ── -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold text-dark mb-0">
                            <i class="fa-solid fa-users me-1 text-secondary"></i> Registered Signatories
                        </label>
                        <span class="badge bg-light text-dark border" id="sig_count_badge">
                            <?= !empty($officialSignatures) ? count($officialSignatures) : 0 ?> Registered
                        </span>
                    </div>

                    <div id="signatures_list_container" class="border rounded p-2" style="max-height: 280px; overflow-y: auto; background-color: #f8fafc;">
                        <?php if (!empty($officialSignatures)): ?>
                            <div class="list-group list-group-flush gap-2">
                                <?php foreach ($officialSignatures as $sig): ?>
                                    <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded border bg-white shadow-sm" id="sig_item_<?= $sig['id'] ?>">
                                        <div class="d-flex align-items-center gap-3">
                                            <!-- Transparent Checkerboard Container -->
                                            <div class="border rounded p-1 text-center" style="width: 110px; height: 50px; display: flex; align-items: center; justify-content: center; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 10px 10px;">
                                                <img src="<?= $GLOBALS['base_url'] . '/' . ltrim($sig['signature_path'], '/\\') . '?v=' . time() ?>" alt="<?= htmlspecialchars($sig['signatory_name']) ?>" style="max-height: 42px; max-width: 100px; object-fit: contain;">
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">
                                                    <?= htmlspecialchars($sig['signatory_name']) ?>
                                                    <?php if ($sig['is_default'] == 1): ?>
                                                        <span class="badge bg-success ms-1"><i class="fa-solid fa-check me-1"></i> Default</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small"><?= htmlspecialchars($sig['signatory_title']) ?></div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-1">
                                            <?php if ($sig['is_default'] != 1): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-set-default py-1 px-2" data-id="<?= $sig['id'] ?>" title="Set as default signatory">
                                                    <i class="fa-regular fa-star me-1"></i> Make Default
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-sig py-1 px-2" data-id="<?= $sig['id'] ?>" data-name="<?= htmlspecialchars($sig['signatory_name']) ?>" title="Delete signature (resigned or retired)">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted" id="no_signatures_msg">
                                <i class="fa-solid fa-signature fs-3 text-secondary mb-2 d-block"></i>
                                <span>No official signatures added yet.</span><br>
                                <small class="text-secondary">Upload a new signatory below to get started.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Section 2: Add New Signatory Form ── -->
                <div class="card border border-primary-subtle rounded shadow-sm bg-white">
                    <div class="card-header bg-light py-2">
                        <span class="fw-bold text-dark">
                            <i class="fa-solid fa-user-plus text-primary me-2"></i>Add New Official Signatory
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <form id="officialSigForm" action="<?= $GLOBALS['base_url'] ?>/save-hr-signature" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="is_ajax" value="1">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark mb-1">Signatory Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="signatory_name" id="modal_sig_name" class="form-control form-control-sm" placeholder="e.g. Md. Abdur Rahim" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark mb-1">Designation / Title <span class="text-danger">*</span></label>
                                    <input type="text" name="signatory_title" id="modal_sig_title" class="form-control form-control-sm" placeholder="e.g. Head of HR & Admin" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark mb-1">Signature Photo / Paper Scan <span class="text-danger">*</span></label>
                                    <input type="file" name="official_signature" id="modal_official_sig_file" class="form-control form-control-sm" accept="image/*" required>
                                    <div class="form-text small">Photo of signature on paper. AI will remove shadows & background.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark mb-1">Live Transparent Output</label>
                                    <div id="modal_sig_preview_box" class="p-2 border rounded text-center" style="height: 58px; display: flex; align-items: center; justify-content: center; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 10px 10px;">
                                        <span id="modal_sig_preview_placeholder" class="text-muted small">Select an image to preview</span>
                                        <canvas id="modal_sig_preview_canvas" style="display: none; max-height: 48px; max-width: 100%; object-fit: contain;"></canvas>
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="modal_sig_is_default" <?= empty($officialSignatures) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-dark" for="modal_sig_is_default">
                                            Set as company default signatory for new letters
                                        </label>
                                    </div>

                                    <button type="submit" id="btn_save_official_sig" class="btn btn-primary btn-sm px-3">
                                        <span id="btn_save_text"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Extract & Save Signatory</span>
                                        <span id="btn_save_spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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
    var btnSave = document.getElementById('btn_save_official_sig');
    var btnSaveText = document.getElementById('btn_save_text');
    var btnSaveSpinner = document.getElementById('btn_save_spinner');
    var listContainer = document.getElementById('signatures_list_container');
    var countBadge = document.getElementById('sig_count_badge');

    // ─── Client-side instant transparent preview ──────────────────────────────
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
                            var srcA = data[idx + 3];

                            // If pixel is already transparent in source PNG (transparent background)
                            if (srcA <= 20) {
                                data[idx + 3] = 0;
                                continue;
                            }

                            var r = data[idx];
                            var g = data[idx + 1];
                            var b = data[idx + 2];
                            var lum = 0.299 * r + 0.587 * g + 0.114 * b;

                            if (lum >= threshold) {
                                data[idx + 3] = 0;
                            } else {
                                var alphaRatio = (threshold - lum) / threshold;
                                var calculatedAlpha = Math.min(255, Math.floor(alphaRatio * 255 * 1.5));
                                var finalAlpha = (srcA < 255) ? Math.min(srcA, calculatedAlpha) : calculatedAlpha;
                                data[idx + 3] = finalAlpha;
                                data[idx] = Math.max(0, r - 50);
                                data[idx + 1] = Math.max(0, g - 50);
                                data[idx + 2] = Math.max(0, b - 40);

                                if (finalAlpha > 30) {
                                    if (x < minX) minX = x;
                                    if (x > maxX) maxX = x;
                                    if (y < minY) minY = y;
                                    if (y > maxY) maxY = y;
                                }
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

    // ─── Render Dynamic Signature List in Modal ──────────────────────────────
    function renderSignatureList(signatures, baseUrl) {
        if (!listContainer) return;

        if (countBadge) {
            countBadge.textContent = signatures.length + ' Registered';
        }

        if (!signatures || signatures.length === 0) {
            listContainer.innerHTML = '<div class="text-center py-4 text-muted" id="no_signatures_msg">' +
                '<i class="fa-solid fa-signature fs-3 text-secondary mb-2 d-block"></i>' +
                '<span>No official signatures added yet.</span><br>' +
                '<small class="text-secondary">Upload a new signatory below to get started.</small>' +
                '</div>';
            return;
        }

        var html = '<div class="list-group list-group-flush gap-2">';
        signatures.forEach(function (sig) {
            var isDefault = (sig.is_default == 1);
            var path = sig.signature_path.replace(/^[/\\]+/, '');
            var fullUrl = baseUrl + '/' + path + '?v=' + Date.now();

            html += '<div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded border bg-white shadow-sm" id="sig_item_' + sig.id + '">';
            html += '  <div class="d-flex align-items-center gap-3">';
            html += '    <div class="border rounded p-1 text-center" style="width: 110px; height: 50px; display: flex; align-items: center; justify-content: center; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 10px 10px;">';
            html += '      <img src="' + fullUrl + '" alt="' + escapeHtml(sig.signatory_name) + '" style="max-height: 42px; max-width: 100px; object-fit: contain;">';
            html += '    </div>';
            html += '    <div>';
            html += '      <div class="fw-bold text-dark">' + escapeHtml(sig.signatory_name) + (isDefault ? ' <span class="badge bg-success ms-1"><i class="fa-solid fa-check me-1"></i> Default</span>' : '') + '</div>';
            html += '      <div class="text-muted small">' + escapeHtml(sig.signatory_title) + '</div>';
            html += '    </div>';
            html += '  </div>';

            html += '  <div class="d-flex align-items-center gap-1">';
            if (!isDefault) {
                html += '    <button type="button" class="btn btn-outline-primary btn-sm btn-set-default py-1 px-2" data-id="' + sig.id + '" title="Set as default signatory">';
                html += '      <i class="fa-regular fa-star me-1"></i> Make Default';
                html += '    </button>';
            }
            html += '    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-sig py-1 px-2" data-id="' + sig.id + '" data-name="' + escapeHtml(sig.signatory_name) + '" title="Delete signature">';
            html += '      <i class="fa-solid fa-trash-can"></i>';
            html += '    </button>';
            html += '  </div>';

            html += '</div>';
        });
        html += '</div>';

        listContainer.innerHTML = html;
        bindListButtons();
        syncSignatureSelect(signatures);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (m) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
        });
    }

    // ─── Update Signature Select Dropdown on Create/Edit Letter Pages ─────────
    function syncSignatureSelect(signatures) {
        var sigSelect = document.getElementById('signature_choice');
        if (!sigSelect) return;

        var curVal = sigSelect.value;
        var html = '';

        if (signatures && signatures.length > 0) {
            signatures.forEach(function (sig) {
                var isDef = (sig.is_default == 1);
                var optVal = 'official_' + sig.id;
                var isSelected = (curVal === optVal || (isDef && (curVal === 'official' || !curVal)));
                html += '<option value="' + optVal + '" data-name="' + escapeHtml(sig.signatory_name) + '" data-title="' + escapeHtml(sig.signatory_title) + '" ' + (isSelected ? 'selected' : '') + '>';
                html += 'Official: ' + escapeHtml(sig.signatory_name) + ' (' + escapeHtml(sig.signatory_title) + ')' + (isDef ? ' [Default]' : '');
                html += '</option>';
            });
        }

        html += '<option value="custom" ' + (curVal === 'custom' ? 'selected' : '') + '>🖊️ Upload Custom Signature (Paper Photo/Scan)</option>';
        html += '<option value="none" ' + (curVal === 'none' || curVal === '0' ? 'selected' : '') + '>No — Leave Blank (Physical Sign)</option>';

        sigSelect.innerHTML = html;

        // Auto populate signatory name and title if an official option is selected
        if (typeof handleSignatureChoice === 'function') {
            handleSignatureChoice(sigSelect.value);
        }
    }

    // ─── Attach Events to Dynamically Rendered List Buttons ──────────────────
    function bindListButtons() {
        // Set Default Buttons
        document.querySelectorAll('.btn-set-default').forEach(function (btn) {
            btn.onclick = function () {
                var sigId = this.getAttribute('data-id');
                var formData = new FormData();
                formData.append('id', sigId);

                fetch('<?= $GLOBALS['base_url'] ?>/set-default-hr-signature', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showAlert('success', data.message);
                        renderSignatureList(data.signatures, data.base_url);
                    } else {
                        showAlert('danger', data.message || 'Failed to update default signature.');
                    }
                })
                .catch(function () {
                    showAlert('danger', 'Error updating default signature.');
                });
            };
        });

        // Delete Buttons
        document.querySelectorAll('.btn-delete-sig').forEach(function (btn) {
            btn.onclick = function () {
                var sigId = this.getAttribute('data-id');
                var sigName = this.getAttribute('data-name');
                if (!confirm('Are you sure you want to delete the signature of "' + sigName + '"? This will remove them from the active signatory list.')) {
                    return;
                }

                var formData = new FormData();
                formData.append('id', sigId);

                fetch('<?= $GLOBALS['base_url'] ?>/delete-hr-signature', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showAlert('success', data.message);
                        renderSignatureList(data.signatures, data.base_url);
                    } else {
                        showAlert('danger', data.message || 'Failed to delete signature.');
                    }
                })
                .catch(function () {
                    showAlert('danger', 'Error deleting signature.');
                });
            };
        });
    }

    function showAlert(type, message) {
        if (!alertBox) return;
        alertBox.className = 'alert alert-' + type;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
        setTimeout(function () {
            alertBox.classList.add('d-none');
        }, 3500);
    }

    // Initial binding for server-rendered buttons
    bindListButtons();

    // ─── Handle Form Submission (Add New Signatory) ───────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var nameInput = document.getElementById('modal_sig_name');
            var titleInput = document.getElementById('modal_sig_title');

            if (!nameInput.value.trim() || !titleInput.value.trim()) {
                showAlert('danger', 'Please enter both signatory name and designation/title.');
                return;
            }

            if (!modalFileInput.files || !modalFileInput.files[0]) {
                showAlert('danger', 'Please choose a signature image or photo.');
                return;
            }

            var formData = new FormData(form);
            btnSave.disabled = true;
            btnSaveSpinner.classList.remove('d-none');

            fetch('<?= $GLOBALS['base_url'] ?>/save-hr-signature', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btnSave.disabled = false;
                btnSaveSpinner.classList.add('d-none');

                if (data.success) {
                    showAlert('success', data.message);
                    form.reset();
                    if (canvas) canvas.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'inline-block';

                    renderSignatureList(data.signatures, data.base_url);
                } else {
                    showAlert('danger', data.message || 'Failed to save signature.');
                }
            })
            .catch(function () {
                btnSave.disabled = false;
                btnSaveSpinner.classList.add('d-none');
                showAlert('danger', 'Server error occurred while saving signature.');
            });
        });
    }
});
</script>

