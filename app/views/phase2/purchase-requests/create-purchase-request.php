<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-cart-plus me-2"></i>Create Purchase Request
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-purchase-request-store" method="POST" id="prForm">

                        <!-- Header Fields -->
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="prProjectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Urgency, purpose...">
                            </div>
                        </div>

                        <!-- BOQ Remaining Info -->
                        <div id="boqInfoBox" class="alert alert-info d-none mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="boqInfoText">Select a project to see BOQ balance.</span>
                        </div>

                        <hr class="my-3">

                        <!-- PR Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="prItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:45%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:20%;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:20%;">BOQ Remaining</th>
                                        <th style="width:15%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="prItemsBody">
                                    <tr>
                                        <td>
                                            <select name="material_id[]" class="form-select pr-material" required>
                                                <option value="">-- Select Material --</option>
                                                <?php foreach ($materials as $m): ?>
                                                    <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                                        <?= htmlspecialchars($m['name']) ?> (<?= $m['unit'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.001" name="quantity[]"
                                                       class="form-control pr-qty" required min="0.001">
                                                <span class="input-group-text pr-unit">-</span>
                                            </div>
                                        </td>
                                        <td class="text-center pr-boq-remaining text-muted">—</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger pr-remove-row"
                                                    style="display:none;" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="prAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Material
                            </button>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Submit Purchase Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbody   = document.getElementById('prItemsBody');
    var infoBox = document.getElementById('boqInfoBox');
    var boqData = {}; // { material_id: remaining_qty }

    // ── Project select হলে BOQ data fetch করো ──────────────────────────────
    document.getElementById('prProjectSelect').addEventListener('change', function () {
        var projectId = this.value;
        boqData = {};

        if (!projectId) {
            infoBox.classList.add('d-none');
            updateAllRowsBOQ();
            return;
        }

        fetch('<?= $base_url ?>/con-boq-remaining/' + projectId)
            .then(function (res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function (data) {
                // material_id → remaining_qty map বানাও
                data.forEach(function (item) {
                    boqData[item.material_id] = item.remaining_qty;
                });

                infoBox.classList.remove('d-none');
                document.getElementById('boqInfoText').textContent =
                    data.length > 0
                        ? 'BOQ balance loaded (' + data.length + ' materials).'
                        : 'No approved BOQ found for this project.';

                updateAllRowsBOQ();
            })
            .catch(function (err) {
                console.error('BOQ fetch error:', err);
                infoBox.classList.remove('d-none');
                document.getElementById('boqInfoText').textContent = 'Could not load BOQ data.';
            });
    });

    // ── সব row-এর BOQ remaining column আপডেট করো ──────────────────────────
    function updateAllRowsBOQ() {
        tbody.querySelectorAll('tr').forEach(function (row) {
            updateRowBOQ(row);
        });
    }

    // ── একটা row-এর BOQ remaining cell আপডেট করো ──────────────────────────
    function updateRowBOQ(row) {
        var select = row.querySelector('.pr-material');
        var cell   = row.querySelector('.pr-boq-remaining');
        if (!select || !cell) return;

        var mid = select.value;
        if (!mid) {
            cell.textContent = '—';
            cell.className = 'text-center pr-boq-remaining text-muted';
            return;
        }

        if (boqData.hasOwnProperty(mid)) {
            var qty = parseFloat(boqData[mid]);
            cell.textContent = qty.toLocaleString(undefined, { maximumFractionDigits: 3 });
            // কম থাকলে red, ঠিক থাকলে green
            cell.className = 'text-center pr-boq-remaining fw-bold ' +
                             (qty <= 0 ? 'text-danger' : 'text-success');
        } else {
            cell.textContent = 'N/A';
            cell.className = 'text-center pr-boq-remaining text-muted';
        }
    }

    // ── Material change → unit label + BOQ remaining ────────────────────────
    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('pr-material')) {
            var opt  = e.target.options[e.target.selectedIndex];
            var unit = opt.getAttribute('data-unit') || '-';
            e.target.closest('tr').querySelector('.pr-unit').textContent = unit;

            // BOQ remaining update
            updateRowBOQ(e.target.closest('tr'));
        }
    });

    // ── Row Add ──────────────────────────────────────────────────────────────
    document.getElementById('prAddRow').addEventListener('click', function () {
        var firstRow = tbody.querySelector('tr');
        var newRow   = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        newRow.querySelector('.pr-material').selectedIndex = 0;
        newRow.querySelector('.pr-unit').textContent = '-';
        newRow.querySelector('.pr-boq-remaining').textContent = '—';
        newRow.querySelector('.pr-boq-remaining').className = 'text-center pr-boq-remaining text-muted';
        newRow.querySelector('.pr-remove-row').style.display = '';
        tbody.appendChild(newRow);
    });

    // ── Row Remove ───────────────────────────────────────────────────────────
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.pr-remove-row');
        if (btn) btn.closest('tr').remove();
    });
});
</script>
