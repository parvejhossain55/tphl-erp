<?php
$page_title = "Add Project Drawing";
$page = 'Add Project Drawing';
$show_breadcrumb = true;
$blockedProjects = $blockedProjects ?? [];
$projects        = $projects        ?? [];
?>

<?php if (!empty($blockedProjects) && empty($projects)): ?>
<!-- FULLY BLOCKED: No eligible projects yet -->
<div class="row"><div class="card__wrapper">
  <div style="text-align:center;padding:40px 20px;">
    <div style="width:70px;height:70px;background:rgba(245,158,11,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#f59e0b;">
      <i class="fal fa-lock-keyhole"></i>
    </div>
    <h5 style="font-weight:800;margin-bottom:6px;">Project Drawings Locked</h5>
    <p style="color:var(--text-muted);font-size:13px;max-width:480px;margin:0 auto 20px;">
      You cannot upload drawings yet. The <strong>Architect &amp; Engineering</strong> step must be marked as
      <strong>Fully Complete</strong> before you can proceed.
    </p>
    <!-- Blocked project chips -->
    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:24px;">
      <?php foreach ($blockedProjects as $bp): ?>
      <div style="background:var(--card-bg,#fff);border:1px solid rgba(0,0,0,.09);border-radius:12px;padding:10px 16px;display:flex;align-items:center;gap:10px;">
        <i class="fal fa-building" style="color:var(--text-muted);font-size:16px;"></i>
        <div style="text-align:left;">
          <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($bp['name']) ?></div>
          <div style="font-size:10px;color:#ef4444;font-weight:700;">
            <i class="fal fa-clock me-1"></i>A&amp;E Status: Pending
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering" class="btn btn-sm" style="background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border-radius:10px;font-size:12px;font-weight:700;padding:10px 24px;">
      <i class="fal fa-building me-2"></i>View Architect &amp; Engineering Status
    </a>
    <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings" class="btn btn-sm btn-secondary ms-2" style="border-radius:10px;font-size:12px;font-weight:700;padding:10px 24px;">
      <i class="fal fa-arrow-left me-2"></i>Back to Drawings
    </a>
  </div>
</div></div>

<?php else: ?>

<?php if (!empty($blockedProjects)): ?>
<!-- Some projects are blocked, but others are available — show info strip -->
<div style="background:rgba(245,158,11,.1);border:1px solid #f59e0b;border-radius:12px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
  <i class="fal fa-triangle-exclamation" style="color:#f59e0b;font-size:18px;flex-shrink:0;"></i>
  <div style="font-size:12px;">
    <strong>Note:</strong>
    <?= count($blockedProjects) ?> of your assigned project(s) are locked because
    Architect &amp; Engineering is not yet <strong>Fully Complete</strong>:
    <?= implode(', ', array_map(fn($p) => '<em>' . htmlspecialchars($p['name']) . '</em>', $blockedProjects)) ?>.
    You can only create drawings for the eligible projects listed below.
  </div>
</div>
<?php endif; ?>

<div class="row"><div class="card__wrapper">
    <form action="<?= $GLOBALS['base_url'] ?>/con-project-drawings/store" method="POST">
        <div class="row gy-20">

            <div class="col-xl-6">
                <label>Project <span class="text-danger">*</span></label>
                <select name="project_id" class="form-control select2" required>
                    <option value="">Select Project (A&amp;E Complete)</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6">
                <label>Design Type</label>
                <select name="design_type_id" class="form-control">
                    <option value="">Select Design Type</option>
                    <?php foreach ($design_types as $dt): ?>
                        <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-6">
                <label>Designer Type</label>
                <select name="is_in_house" class="form-control" id="designer_type">
                    <option value="1">In-House (Internal)</option>
                    <option value="0">External</option>
                </select>
            </div>

            <!-- Internal: auto from session, show current user info -->
            <div class="col-xl-6" id="internal_designer_wrap">
                <label>Internal Designer</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Current User') ?>"
                       readonly style="cursor:not-allowed">
                <small class="text-muted">Auto-filled from your login session</small>
            </div>

            <!-- External: show these fields -->
            <div class="col-xl-6 d-none" id="ext_name_wrap">
                <label>External Designer Name <span class="text-danger">*</span></label>
                <input type="text" name="designer_name" class="form-control" placeholder="Full Name">
            </div>
            <div class="col-xl-6 d-none" id="ext_desig_wrap">
                <label>Designer Designation</label>
                <input type="text" name="designer_designation" class="form-control" placeholder="e.g. Architect">
            </div>
            <div class="col-xl-6 d-none" id="ext_company_wrap">
                <label>Designer Company</label>
                <input type="text" name="designer_company" class="form-control" placeholder="Company Name">
            </div>

            <div class="col-xl-6">
                <label>Design Link</label>
                <input type="url" name="design_link" class="form-control" placeholder="https://...">
            </div>

            <div class="col-xl-3">
                <label>Version</label>
                <input type="text" name="version" class="form-control" value="1.0" placeholder="1.0">
            </div>

            <div class="col-xl-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Draft">Draft</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-5">Save Drawing</button>
                <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings" class="btn btn-secondary ms-2">Cancel</a>
            </div>

        </div>
    </form>
</div></div>

<?php endif; ?>

<script>
document.getElementById('designer_type')?.addEventListener('change', function () {
    const inHouse = this.value === '1';
    document.getElementById('internal_designer_wrap').classList.toggle('d-none', !inHouse);
    document.getElementById('ext_name_wrap').classList.toggle('d-none', inHouse);
    document.getElementById('ext_desig_wrap').classList.toggle('d-none', inHouse);
    document.getElementById('ext_company_wrap').classList.toggle('d-none', inHouse);
});
</script>
