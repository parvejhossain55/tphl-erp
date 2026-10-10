<?php
$addPage = true;
$show_breadcrumb = false;
?>

<style>
/* Premium Theme-Aware Inventory CSS */
.kpi-card.card__wrapper,
.kpi-card {
    background-color: var(--clr-bg-card, #ffffff);
    border: 1px solid var(--clr-border-1, rgba(0,0,0,0.06));
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    padding: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}
.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-right: 15px;
    flex-shrink: 0;
}
.kpi-primary { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
.kpi-success { background: rgba(25, 135, 84, 0.12); color: #198754; }
.kpi-danger  { background: rgba(220, 53, 69, 0.12); color: #dc3545; }
.kpi-info    { background: rgba(13, 202, 240, 0.12); color: #0dcaf0; }

.project-accordion .accordion-item {
    border: 1px solid var(--clr-border-1, rgba(0,0,0,0.06));
    background-color: var(--clr-bg-card, #ffffff);
    border-radius: 15px !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    overflow: hidden;
}
.project-accordion .accordion-button {
    background-color: var(--clr-bg-card, #ffffff);
    padding: 18px 22px;
    border: none;
    box-shadow: none;
    font-weight: 600;
    color: var(--clr-text-body, #2b3445);
}
.project-accordion .accordion-button:not(.collapsed) {
    background-color: var(--clr-bg-card, #ffffff);
    border-bottom: 1px solid var(--clr-border-1, rgba(0,0,0,0.06));
    box-shadow: none;
}
.project-accordion .accordion-button:focus {
    box-shadow: none;
}
.project-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.flat-card {
    background-color: var(--clr-bg-card, #ffffff);
    border-radius: 12px;
    border: 1px solid var(--clr-border-1, rgba(0,0,0,0.08));
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
}
.flat-card:hover {
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    transform: translateY(-3px);
}
.flat-header {
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--clr-border-1, rgba(0,0,0,0.06));
}
.flat-available .flat-header { border-top: 4px solid #198754; background: rgba(25, 135, 84, 0.05); }
.flat-booked .flat-header { border-top: 4px solid #dc3545; background: rgba(220, 53, 69, 0.05); }
.flat-sold .flat-header { border-top: 4px solid #6c757d; background: rgba(108, 117, 125, 0.05); }

.flat-title { font-size: 16px; font-weight: 700; color: var(--clr-text-body, #2b3445); margin: 0; }
.flat-body { padding: 18px; }
.flat-detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 13.5px;
    color: var(--clr-text-muted, #6c757d);
}
.flat-detail-val { font-weight: 600; color: var(--clr-text-body, #212529); }
.flat-footer {
    padding: 14px 18px;
    background-color: rgba(0, 0, 0, 0.02);
    border-top: 1px solid var(--clr-border-1, rgba(0,0,0,0.06));
    display: flex;
    gap: 10px;
}
.price-tag {
    font-size: 18px;
    font-weight: 800;
    color: var(--clr-theme-primary, #198754);
    margin-top: 4px;
    margin-bottom: 4px;
    display: block;
}
.booking-money-tag {
    font-size: 13px;
    color: #dc3545;
    font-weight: 700;
    background: rgba(220,53,69,0.1);
    padding: 6px 12px;
    border-radius: 8px;
    display: inline-block;
}

/* Dark theme explicit overrides */
[bd-theme="bd-theme-dark"] .kpi-card,
[bd-theme="bd-theme-dark"] .project-accordion .accordion-item,
[bd-theme="bd-theme-dark"] .project-accordion .accordion-button,
[bd-theme="bd-theme-dark"] .flat-card {
    background-color: var(--clr-bg-card, #1f2937) !important;
    border-color: var(--clr-border-1, #374151) !important;
    color: var(--clr-text-body, #f9fafb) !important;
}

[bd-theme="bd-theme-dark"] .project-accordion .accordion-button:not(.collapsed) {
    background-color: rgba(255, 255, 255, 0.02) !important;
    border-bottom-color: var(--clr-border-1, #374151) !important;
}

[bd-theme="bd-theme-dark"] .flat-footer {
    background-color: rgba(255, 255, 255, 0.03) !important;
    border-color: var(--clr-border-1, #374151) !important;
}

[bd-theme="bd-theme-dark"] .price-tag {
    color: #34d399 !important;
}

[bd-theme="bd-theme-dark"] .flat-detail-val {
    color: #f9fafb !important;
}

[bd-theme="bd-theme-dark"] .flat-available .flat-header {
    background: rgba(52, 211, 153, 0.12) !important;
    border-top-color: #34d399 !important;
}

[bd-theme="bd-theme-dark"] .flat-booked .flat-header {
    background: rgba(248, 113, 113, 0.12) !important;
    border-top-color: #f87171 !important;
}

[bd-theme="bd-theme-dark"] .flat-sold .flat-header {
    background: rgba(156, 163, 175, 0.12) !important;
    border-top-color: #9ca3af !important;
}

[bd-theme="bd-theme-dark"] .booking-money-tag {
    background: rgba(248, 113, 113, 0.15) !important;
    color: #f87171 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .project-accordion .accordion-button > div {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 15px;
    }
}
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">Project Inventory Dashboard</h4>
    </div>

    <!-- Filters Panel -->
    <div class="card__wrapper p-4 mb-4" style="border: 1px solid rgba(0,0,0,0.05); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <div class="row">
            <!-- Project Filter (Searchable Select2) -->
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-13 text-muted"><i class="fa-solid fa-building me-1"></i> Filter by Project</label>
                <select id="filterProject" class="form-control select2" data-placeholder="Search or Select Project">
                    <option value="all">All Projects</option>
                    <?php foreach ($inventoryProjects as $projRow): ?>
                        <option value="<?= htmlspecialchars($projRow['project_name']) ?>"><?= htmlspecialchars($projRow['project_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Status Filter -->
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-13 text-muted"><i class="fa-solid fa-circle-info me-1"></i> Filter by Status</label>
                <select id="filterStatus" class="form-control rounded-pill">
                    <option value="all">All Statuses (Booked & Available)</option>
                    <option value="available">Available Only</option>
                    <option value="booked">Booked Only</option>
                    <option value="sold">Sold Only</option>
                </select>
            </div>
            <!-- Search Text -->
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-13 text-muted"><i class="fa-solid fa-magnifying-glass me-1"></i> Search Flat/Shop</label>
                <input type="text" id="searchFlat" class="form-control rounded-pill" placeholder="Type Flat name (e.g. 3A, Shop-1)...">
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-card card__wrapper d-flex align-items-center">
                <div class="kpi-icon kpi-primary"><i class="fa-solid fa-building"></i></div>
                <div>
                    <p class="text-muted mb-1 fs-13 fw-semibold">Total Projects</p>
                    <h3 class="mb-0 fw-bold"><?= $totalProjects ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-card card__wrapper d-flex align-items-center">
                <div class="kpi-icon kpi-info"><i class="fa-solid fa-city"></i></div>
                <div>
                    <p class="text-muted mb-1 fs-13 fw-semibold">Total Flats</p>
                    <h3 class="mb-0 fw-bold"><?= $totalFlatCount ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-card card__wrapper d-flex align-items-center">
                <div class="kpi-icon kpi-success"><i class="fa-solid fa-door-open"></i></div>
                <div>
                    <p class="text-muted mb-1 fs-13 fw-semibold">Available Flats</p>
                    <h3 class="mb-0 fw-bold"><?= $availableFlats ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-card card__wrapper d-flex align-items-center">
                <div class="kpi-icon kpi-danger"><i class="fa-solid fa-lock"></i></div>
                <div>
                    <p class="text-muted mb-1 fs-13 fw-semibold">Booked Flats</p>
                    <h3 class="mb-0 fw-bold"><?= $bookedFlats ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Accordion -->
    <div class="accordion project-accordion" id="inventoryAccordion">
        <?php foreach ($inventoryProjects as $index => $row): ?>
            <div class="accordion-item" data-project-name="<?= htmlspecialchars($row['project_name']) ?>">
                <h2 class="accordion-header" id="heading<?= $index ?>">
                    <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                        <div class="d-flex w-100 align-items-center justify-content-between pe-3">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-building me-2"></i> <?= htmlspecialchars($row['project_name']) ?></h5>
                                <span class="badge bg-secondary rounded-pill">Total Flats: <?= $row['total_flats'] ?></span>
                                <span class="badge bg-info rounded-pill matching-count-badge ms-2" style="display:none;"></span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="project-badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-door-open me-1"></i> Available: <?= $row['available_flats'] ?>
                                </span>
                                <span class="project-badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fa-solid fa-lock me-1"></i> Booked: <?= $row['booked_flats'] ?>
                                </span>
                                <?php if (!empty($row['sold_flats']) && $row['sold_flats'] > 0): ?>
                                    <span class="project-badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <i class="fa-solid fa-handshake me-1"></i> Sold: <?= $row['sold_flats'] ?>
                                    </span>
                                <?php endif; ?>
                                <span class="project-badge bg-primary-subtle text-primary border border-primary-subtle ms-lg-2">
                                    <i class="fa-solid fa-sack-dollar me-1"></i> Value: <?= format_bdt($row['total_flat_value']) ?> ৳
                                </span>
                            </div>
                        </div>
                    </button>
                </h2>
                
                <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#inventoryAccordion">
                    <div class="accordion-body  p-4">
                        
                        <?php if (!empty($row['flats'])): ?>
                            <div class="row g-4">
                                <?php foreach ($row['flats'] as $flat): 
                                    $flatStatus = $flat['status'];
                                    if ($flat['is_booked'] == 1 && $flatStatus !== 'sold') {
                                        $flatStatus = 'booked';
                                    }
                                    
                                    $statusClass = 'flat-available';
                                    $statusBadge = '<span class="badge bg-success px-3 py-2 rounded-pill">Available</span>';
                                    if ($flatStatus == 'booked') {
                                        $statusClass = 'flat-booked';
                                        $statusBadge = '<span class="badge bg-danger px-3 py-2 rounded-pill">Booked</span>';
                                    } elseif ($flatStatus == 'sold') {
                                        $statusClass = 'flat-sold';
                                        $statusBadge = '<span class="badge bg-dark px-3 py-2 rounded-pill">Sold</span>';
                                    }
                                ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 flat-container" data-status="<?= htmlspecialchars($flatStatus) ?>" data-flat-name="<?= htmlspecialchars($flat['flat_name']) ?>">
                                        <div class="flat-card <?= $statusClass ?>">
                                            <div class="flat-header">
                                                <h4 class="flat-title">Flat: <?= htmlspecialchars($flat['flat_name']) ?></h4>
                                                <?= $statusBadge ?>
                                            </div>
                                            <div class="flat-body">
                                                <div class="flat-detail-item">
                                                    <span><i class="fa-solid fa-vector-square text-muted me-2"></i>Size</span>
                                                    <span class="flat-detail-val"><?= $flat['sqft'] ?> sqft</span>
                                                </div>
                                                <div class="flat-detail-item">
                                                    <span><i class="fa-solid fa-bed text-muted me-2"></i>BHK</span>
                                                    <span class="flat-detail-val"><?= $flat['bhk'] ?></span>
                                                </div>
                                                
                                                <div class="mt-4 pt-3 border-top border-light">
                                                    <span class="text-muted fs-13 d-block text-uppercase fw-semibold">Net Price</span>
                                                    <span class="price-tag"><?= format_bdt($flat['price_value']) ?> ৳</span>
                                                </div>
                                                
                                                <?php if ($flatStatus === 'booked' || $flatStatus === 'sold'): ?>
                                                    <?php if (isset($flat['agreement_value']) && (float)$flat['agreement_value'] > 0): ?>
                                                        <div class="booking-money-tag mt-3 w-100 text-center">
                                                            <i class="fa-solid fa-money-check-dollar me-1"></i> Booking: <?= format_bdt($flat['agreement_value']) ?> ৳
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="booking-money-tag mt-3 w-100 text-center">
                                                            <i class="fa-solid fa-lock me-1"></i> <?= ucfirst($flatStatus) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="text-muted mt-3 fs-13 w-100 text-center py-1 opacity-75">
                                                        <i class="fa-solid fa-circle-check me-1"></i> Available for Booking
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flat-footer">
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-flat/<?= $flat['id'] ?>" class="btn btn-outline-secondary btn-sm w-50 rounded-pill fw-semibold"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
                                                <a href="<?= $GLOBALS['base_url'] ?>/flat-details/<?= $flat['id'] ?>" class="btn btn-primary btn-sm w-50 rounded-pill fw-semibold"><i class="fa-solid fa-eye me-1"></i> Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 card__wrapper rounded-3 border">
                                <div class="text-muted mb-3"><i class="fa-regular fa-folder-open display-4"></i></div>
                                <h5 class="text-muted">No flats found for this project.</h5>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- No matching results message -->
    <div id="noInventoryMessage" class="text-center py-5 card__wrapper rounded-3 border mt-4" style="display: none;">
        <div class="text-muted mb-3"><i class="fa-solid fa-circle-exclamation display-4 text-warning"></i></div>
        <h5 class="text-muted fw-semibold">No matching inventory found</h5>
        <p class="text-muted fs-13 mb-0">Try adjusting your filters or search query.</p>
    </div>
</div>

<script>
(function() {
    function initInventoryScripts() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initInventoryScripts, 50);
            return;
        }

        jQuery(function($) {
            const $filterProject = $('#filterProject');
            const $filterStatus  = $('#filterStatus');
            const $searchFlat    = $('#searchFlat');
            const $accordionItems = $('#inventoryAccordion .accordion-item');
            const $noInventoryMessage = $('#noInventoryMessage');

            // Initialize Select2 if available and not initialized
            if ($.fn.select2 && $filterProject.length) {
                if (!$filterProject.hasClass('select2-hidden-accessible')) {
                    $filterProject.select2({
                        width: '100%',
                        placeholder: 'Search or Select Project',
                        allowClear: false
                    });
                }
            }

            function applyFilters() {
                const selectedProject = $filterProject.val() || 'all';
                const selectedStatus  = $filterStatus.val() || 'all';
                const searchText      = ($searchFlat.val() || '').toLowerCase().trim();

                let visibleProjectsCount = 0;

                $accordionItems.each(function() {
                    const $item = $(this);
                    const projectName = $item.attr('data-project-name');
                    const $flats = $item.find('.flat-container');
                    let visibleFlatsCount = 0;
                    const totalFlatsCount = $flats.length;

                    // Check if project matches selected project
                    const projectMatches = (selectedProject === 'all' || selectedProject === projectName);

                    $flats.each(function() {
                        const $flat = $(this);
                        const status = $flat.attr('data-status');
                        const flatName = ($flat.attr('data-flat-name') || '').toLowerCase();

                        // Check if status matches
                        const statusMatches = (selectedStatus === 'all' || selectedStatus === status);

                        // Check if search text matches (search in flat name)
                        const searchMatches = (searchText === '' || flatName.includes(searchText));

                        if (projectMatches && statusMatches && searchMatches) {
                            $flat.show();
                            visibleFlatsCount++;
                        } else {
                            $flat.hide();
                        }
                    });

                    const $matchingBadge = $item.find('.matching-count-badge');

                    if (projectMatches && visibleFlatsCount > 0) {
                        $item.show();
                        visibleProjectsCount++;
                        
                        // Show matching flats count badge
                        if ($matchingBadge.length) {
                            $matchingBadge.text(`Showing: ${visibleFlatsCount} of ${totalFlatsCount}`).show();
                        }
                    } else {
                        $item.hide();
                        if ($matchingBadge.length) {
                            $matchingBadge.hide();
                        }
                    }
                });

                // Show/hide no results message
                if (visibleProjectsCount === 0) {
                    $noInventoryMessage.show();
                } else {
                    $noInventoryMessage.hide();
                }
            }

            // Bind change events
            $filterProject.on('change select2:select select2:clear', applyFilters);
            $filterStatus.on('change', applyFilters);
            $searchFlat.on('input keyup', applyFilters);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInventoryScripts);
    } else {
        initInventoryScripts();
    }
})();
</script>