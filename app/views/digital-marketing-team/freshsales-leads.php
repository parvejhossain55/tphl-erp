<style>
  :root {
    --brand: #1a2744;
    --brand-light: #2563eb;
    --surface: #f4f6fb;
    --card-radius: 14px;
  }

  .card__wrapper {
    background: #fff;
    border-radius: var(--card-radius);
    border: 1px solid #e8ecf3;
    padding: 20px;
    margin-bottom: 25px;
  }

  .filter-input {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 8px 12px;
  }

  .status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 12px;
  }

  .status-pending {
    background-color: #e2e8f0;
    color: #4a5568;
  }

  .status-exported {
    background-color: #def7ec;
    color: #03543f;
  }

  .info-tag {
    font-size: 0.72rem;
    background-color: #f7fafc;
    border: 1px solid #edf2f7;
    color: #4a5568;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin: 2px;
  }

  .custom-fields-cell {
    max-width: 300px;
    white-space: normal;
    word-break: break-word;
  }
</style>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <h4 class="mb-0" style="color: var(--brand); font-weight: 700;">
        <i class="bi bi-cloud-arrow-down-fill me-2 text-primary"></i>Freshsales API Leads (Real-time Cache)
      </h4>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= $base_url ?>/digital-marketing-dashboard">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Freshsales Leads</li>
        </ol>
      </nav>
    </div>
  </div>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Filters Row -->
  <div class="card__wrapper shadow-sm border-0">
    <form method="GET" action="<?= $base_url ?>/digital-marketing/freshsales-leads" class="row g-3 align-items-end">
      <div class="col-md-2">
        <label class="form-label font-weight-bold" style="font-size: 0.85rem; color: #4a5568;">Platform</label>
        <select name="platform" class="form-select filter-input">
          <option value="">All Platforms</option>
          <?php foreach ($platforms as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= ($filter['platform'] === $p) ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucfirst($p)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label font-weight-bold" style="font-size: 0.85rem; color: #4a5568;">Campaign Name</label>
        <select name="campaign" class="form-select filter-input">
          <option value="">All Campaigns</option>
          <?php foreach ($campaigns as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($filter['campaign'] === $c) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label font-weight-bold" style="font-size: 0.85rem; color: #4a5568;">Export Status</label>
        <select name="status" class="form-select filter-input">
          <option value="all" <?= ($filter['status'] === 'all') ? 'selected' : '' ?>>All Status</option>
          <option value="0" <?= ($filter['status'] === '0') ? 'selected' : '' ?>>Pending (Unexported)</option>
          <option value="1" <?= ($filter['status'] === '1') ? 'selected' : '' ?>>Exported</option>
        </select>
      </div>

      <div class="col-md-2" style="max-width: 13.5%;">
        <label class="form-label font-weight-bold" style="font-size: 0.85rem; color: #4a5568;">From Date</label>
        <input type="date" name="from_date" class="form-control filter-input" value="<?= htmlspecialchars($filter['from_date']) ?>">
      </div>

      <div class="col-md-2" style="max-width: 13.5%;">
        <label class="form-label font-weight-bold" style="font-size: 0.85rem; color: #4a5568;">To Date</label>
        <input type="date" name="to_date" class="form-control filter-input" value="<?= htmlspecialchars($filter['to_date']) ?>">
      </div>

      <div class="col-md-1 d-flex gap-1" style="min-width: 110px;">
        <button type="submit" class="btn btn-primary p-2 flex-grow-1" style="border-radius: 8px;" title="Filter">
          <i class="bi bi-funnel-fill"></i>
        </button>
        <a href="<?= $base_url ?>/digital-marketing/freshsales-leads" class="btn btn-outline-secondary p-2 flex-grow-1" style="border-radius: 8px;" title="Reset">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Table Card -->
  <div class="card__wrapper shadow-sm border-0">
    <form id="exportForm" method="POST" action="<?= $base_url ?>/digital-marketing/freshsales-export">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div style="font-size: 0.9rem; color: #718096;">
          Showing <span class="font-weight-bold text-dark"><?= count($leads) ?></span> leads
        </div>
        <button type="submit" id="exportBtn" class="btn btn-success px-4 py-2" style="border-radius: 8px;" disabled>
          <i class="bi bi-file-earmark-arrow-down-fill me-1"></i>Export Selected to CSV
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="40" class="text-center">
                <input type="checkbox" id="selectAll" class="form-check-input">
              </th>
              <th>Lead Name</th>
              <th>Phone Number</th>
              <th>Platform / Campaign</th>
              <th class="custom-fields-cell">Questionnaire Answers</th>
              <th>Status</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($leads)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox-fill display-4 d-block mb-2 text-secondary"></i>
                  No leads found in Freshsales cache.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($leads as $lead): ?>
                <tr>
                  <td class="text-center">
                    <input type="checkbox" name="ids[]" value="<?= $lead['id'] ?>" class="form-check-input lead-checkbox">
                  </td>
                  <td>
                    <div class="font-weight-bold text-dark"><?= htmlspecialchars($lead['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($lead['email'] ?: 'No email') ?></div>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($lead['phone_number']) ?></div>
                    <?php if (!empty($lead['alt_phone'])): ?>
                      <div class="text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($lead['alt_phone']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $platform = strtolower($lead['platform']);
                    $icon = 'fa-solid fa-globe';
                    $bg = '#e2e8f0';
                    $fg = '#4a5568';
                    
                    if (str_contains($platform, 'facebook') || $platform === 'fb') {
                        $icon = 'fa-brands fa-facebook-f';
                        $bg = '#e7f3ff';
                        $fg = '#1877f2';
                    } elseif (str_contains($platform, 'instagram') || $platform === 'ig') {
                        $icon = 'fa-brands fa-instagram';
                        $bg = '#fdf0f5';
                        $fg = '#e1306c';
                    } elseif (str_contains($platform, 'whatsapp') || $platform === 'wa') {
                        $icon = 'fa-brands fa-whatsapp';
                        $bg = '#e7faf0';
                        $fg = '#25d366';
                    } elseif (str_contains($platform, 'google')) {
                        $icon = 'fa-brands fa-google';
                        $bg = '#fdebee';
                        $fg = '#db4437';
                    } elseif (str_contains($platform, 'youtube')) {
                        $icon = 'fa-brands fa-youtube';
                        $bg = '#ffebee';
                        $fg = '#ff0000';
                    }
                    ?>
                    <span class="badge text-capitalize px-2 py-1 mb-1 d-inline-flex align-items-center gap-1" 
                          style="font-size: 0.75rem; background-color: <?= $bg ?>; color: <?= $fg ?>; border: 1px solid <?= $fg ?>30; border-radius: 6px;">
                      <i class="<?= $icon ?>"></i> <?= htmlspecialchars($lead['platform']) ?>
                    </span>
                    <div style="font-size: 0.8rem; color: #4a5568; word-break: break-all;">
                      <?= htmlspecialchars($lead['campaign_name'] ?: 'N/A') ?>
                    </div>
                  </td>
                  <td class="custom-fields-cell">
                    <?php if (!empty($lead['which_apartment_size_are_you_interested_in'])): ?>
                      <span class="info-tag">Size: <?= htmlspecialchars($lead['which_apartment_size_are_you_interested_in']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($lead['which_floor_are_you_for_shop'])): ?>
                      <span class="info-tag">Floor: <?= htmlspecialchars($lead['which_floor_are_you_for_shop']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($lead['when_are_you_planning_to_buy'])): ?>
                      <span class="info-tag">When: <?= htmlspecialchars($lead['when_are_you_planning_to_buy']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($lead['would_you_like_to_schedule_a_site_visit'])): ?>
                      <span class="info-tag">Visit: <?= htmlspecialchars($lead['would_you_like_to_schedule_a_site_visit']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($lead['when_is_the_best_time_to_contact_you'])): ?>
                      <span class="info-tag">Time: <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you']) ?></span>
                    <?php endif; ?>
                    <?php if (empty($lead['which_apartment_size_are_you_interested_in']) && empty($lead['which_floor_are_you_for_shop']) && empty($lead['when_are_you_planning_to_buy']) && empty($lead['would_you_like_to_schedule_a_site_visit']) && empty($lead['when_is_the_best_time_to_contact_you'])): ?>
                      <span class="text-muted" style="font-size: 0.8rem;">No questions answered</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($lead['is_exported'] == 1): ?>
                      <span class="status-badge status-exported">
                        <i class="bi bi-check-circle-fill me-1"></i>Exported
                      </span>
                    <?php else: ?>
                      <span class="status-badge status-pending">
                        <i class="bi bi-clock-fill me-1"></i>Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size: 0.8rem; color: #718096;">
                    <?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    const exportBtn = document.getElementById('exportBtn');
    const exportForm = document.getElementById('exportForm');

    function updateExportButtonState() {
      const checkedCount = document.querySelectorAll('.lead-checkbox:checked').length;
      exportBtn.disabled = (checkedCount === 0);
    }

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateExportButtonState();
      });
    }

    checkboxes.forEach(cb => {
      cb.addEventListener('change', function () {
        if (!cb.checked) {
          selectAll.checked = false;
        } else {
          const allChecked = Array.from(checkboxes).every(c => c.checked);
          selectAll.checked = allChecked;
        }
        updateExportButtonState();
      });
    });

    // Refresh page shortly after CSV download is triggered to reflect updated exported status badges
    if (exportForm) {
      exportForm.addEventListener('submit', function () {
        setTimeout(function () {
          window.location.reload();
        }, 1500);
      });
    }
  });
</script>
