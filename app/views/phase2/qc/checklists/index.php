<?php $base = $GLOBALS['base_url']; ?>
<div class="row">
  <div class="col-12">
    <style>
      .cl-hero {
        background: #059669;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 22px;
        color: #fff;
        position: relative;
        overflow: hidden;
      }

      .cl-hero::before {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .06);
        top: -80px;
        right: -50px;
      }

      .cl-panel {
        border-radius: 14px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: var(--clr-bg-card, #fff);
        padding: 20px;
        margin-bottom: 18px;
      }

      .cl-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .25);
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
      }

      .wt-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all .15s;
      }

      .wt-pill.active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
      }

      .wt-pill:not(.active) {
        background: rgba(99, 102, 241, .08);
        color: #6366f1;
      }

      .wt-pill:hover:not(.active) {
        background: rgba(99, 102, 241, .15);
      }

      .cl-item-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, .06);
        margin-bottom: 8px;
        background: var(--clr-bg-card, #fff);
        transition: box-shadow .15s;
      }

      .cl-item-row:hover {
        box-shadow: 0 3px 12px rgba(99, 102, 241, .12);
      }

      .cl-num {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(99, 102, 241, .1);
        color: #6366f1;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .cl-name {
        flex: 1;
        font-size: 13px;
        font-weight: 600;
      }

      .cl-actions {
        display: flex;
        gap: 6px;
      }

      .cl-btn {
        border: none;
        border-radius: 8px;
        padding: 5px 11px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: opacity .15s;
      }

      .cl-btn:hover {
        opacity: .85;
      }

      .cl-btn-edit {
        background: rgba(59, 130, 246, .1);
        color: #2563eb;
      }

      .cl-btn-del {
        background: rgba(239, 68, 68, .1);
        color: #dc2626;
      }

      .add-form {
        background: rgba(99, 102, 241, .04);
        border: 1.5px dashed rgba(99, 102, 241, .3);
        border-radius: 12px;
        padding: 16px;
        margin-top: 14px;
      }

      .empty-msg {
        text-align: center;
        padding: 30px 0;
        color: var(--text-muted);
        font-size: 12px;
      }
    </style>

    <!-- Hero -->
    <div class="cl-hero">
      <div
        style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
        <div>
          <div class="cl-badge"><i class="fal fa-clipboard-list"></i> QC Module</div>
          <h4 style="font-size:22px;font-weight:900;margin:10px 0 4px">Checklist Management</h4>
          <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0">Manage inspection checklist items per Work Type
          </p>
        </div>
        <div>
          <a href="<?= $base ?>/qc-inspection-create"
            style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:8px 18px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="fal fa-clipboard-check"></i> Go to Inspection
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'];
        unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'];
        unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Left: Work Type Selector -->
      <div class="col-lg-3 mb-3">
        <div class="cl-panel" style="position:sticky;top:70px">
          <div
            style="font-size:10px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px">
            <i class="fal fa-layer-group me-1"></i> Work Types
          </div>
          <div style="display:flex;flex-direction:column;gap:6px" id="workTypeList">
            <?php foreach ($workTypes as $wt): ?>
              <button class="wt-pill <?= ($selectedWorkTypeId == $wt['id']) ? 'active' : '' ?>"
                onclick="window.location.href='<?= $base ?>/qc-checklists?work_type_id=<?= $wt['id'] ?>'">
                <i class="fal fa-tag"></i>
                <?= htmlspecialchars($wt['work_type_name']) ?>
                <span
                  style="margin-left:auto;background:rgba(255,255,255,.25);padding:1px 7px;border-radius:10px;font-size:9px">
                  <?= $wt['checklist_count'] ?? 0 ?>
                </span>
              </button>
            <?php endforeach; ?>
            <?php if (empty($workTypes)): ?>
              <div class="empty-msg" style="padding:16px 0;font-size:11px">
                <i class="fal fa-exclamation-circle d-block mb-2" style="font-size:22px;opacity:.3"></i>
                No work types found.<br>
                <a href="<?= $base ?>/con-work-types" style="color:#6366f1;font-size:11px">Add Work Types first</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Checklist Items -->
      <div class="col-lg-9">
        <?php if ($selectedWorkTypeId && !empty($selectedWorkType)): ?>
          <div class="cl-panel">
            <div
              style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
              <div>
                <div
                  style="font-size:10px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:#6366f1;margin-bottom:4px">
                  <i class="fal fa-clipboard-list me-1"></i> Checklist Items
                </div>
                <div style="font-size:15px;font-weight:800"><?= htmlspecialchars($selectedWorkType['work_type_name']) ?>
                </div>
                <div style="font-size:11px;color:var(--text-muted)"><?= count($checklists) ?> items configured</div>
              </div>
              <button onclick="toggleAddForm()"
                style="background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <i class="fal fa-plus"></i> Add Checklist Item
              </button>
            </div>

            <!-- Add Form -->
            <div class="add-form" id="addForm" style="display:none">
              <div style="font-size:11px;font-weight:700;color:#6366f1;margin-bottom:10px"><i
                  class="fal fa-plus-circle me-1"></i> New Checklist Item for
                "<?= htmlspecialchars($selectedWorkType['work_type_name']) ?>"</div>
              <form action="<?= $base ?>/qc-checklist-store" method="POST"
                style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <input type="hidden" name="work_type_id" value="<?= $selectedWorkTypeId ?>">
                <div style="flex:1;min-width:260px">
                  <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Checklist Item Name
                    *</label>
                  <input type="text" name="checklist_item_name" class="form-control form-control-sm"
                    placeholder="e.g., Footing depth matches drawing" required>
                </div>
                <div style="display:flex;gap:8px">
                  <button type="submit"
                    style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:8px;padding:7px 18px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap">
                    <i class="fal fa-save me-1"></i> Save Item
                  </button>
                  <button type="button" onclick="toggleAddForm()"
                    style="background:rgba(0,0,0,.06);border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer">
                    Cancel
                  </button>
                </div>
              </form>
            </div>

            <!-- Items List -->
            <div id="checklistItems" style="margin-top:14px">
              <?php if (!empty($checklists)): ?>
                <?php foreach ($checklists as $i => $cl): ?>
                  <div class="cl-item-row" id="item-<?= $cl['id'] ?>">
                    <div class="cl-num"><?= $i + 1 ?></div>
                    <div class="cl-name" id="name-<?= $cl['id'] ?>"><?= htmlspecialchars($cl['checklist_item_name']) ?></div>

                    <!-- Inline Edit Form (hidden) -->
                    <form id="editForm-<?= $cl['id'] ?>" action="<?= $base ?>/qc-checklist-update" method="POST"
                      style="display:none;flex:1;display:none;align-items:center;gap:8px">
                      <input type="hidden" name="id" value="<?= $cl['id'] ?>">
                      <input type="hidden" name="work_type_id" value="<?= $selectedWorkTypeId ?>">
                      <input type="text" name="checklist_item_name" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($cl['checklist_item_name']) ?>" required style="flex:1">
                      <button type="submit" class="cl-btn"
                        style="background:rgba(16,185,129,.1);color:#059669;white-space:nowrap">
                        <i class="fal fa-check"></i> Save
                      </button>
                      <button type="button" onclick="cancelEdit(<?= $cl['id'] ?>)" class="cl-btn"
                        style="background:rgba(0,0,0,.06);color:var(--text-muted)">
                        <i class="fal fa-times"></i>
                      </button>
                    </form>

                    <div class="cl-actions" id="actions-<?= $cl['id'] ?>">
                      <button class="cl-btn cl-btn-edit" onclick="startEdit(<?= $cl['id'] ?>)">
                        <i class="fal fa-edit"></i> Edit
                      </button>
                      <form action="<?= $base ?>/qc-checklist-delete" method="POST" style="margin:0"
                        onsubmit="return confirm('Delete this checklist item?')">
                        <input type="hidden" name="id" value="<?= $cl['id'] ?>">
                        <input type="hidden" name="work_type_id" value="<?= $selectedWorkTypeId ?>">
                        <button type="submit" class="cl-btn cl-btn-del">
                          <i class="fal fa-trash-alt"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="empty-msg">
                  <i class="fal fa-clipboard fa-2x" style="opacity:.25;display:block;margin-bottom:8px"></i>
                  No checklist items yet for this work type.<br>
                  <span style="font-size:11px">Click <strong>"Add Checklist Item"</strong> above to start.</span>
                </div>
              <?php endif; ?>
            </div>

          </div>
        <?php elseif (!empty($workTypes)): ?>
          <div class="cl-panel" style="text-align:center;padding:50px 20px">
            <i class="fal fa-hand-pointer fa-3x" style="color:#6366f1;opacity:.3;display:block;margin-bottom:14px"></i>
            <div style="font-size:14px;font-weight:700;color:var(--text-muted)">Select a Work Type</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Choose a work type from the left panel to
              manage its checklist items.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
  function toggleAddForm() {
    const f = document.getElementById('addForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
    if (f.style.display === 'block') f.querySelector('input[name=checklist_item_name]').focus();
  }

  function startEdit(id) {
    document.getElementById('name-' + id).style.display = 'none';
    document.getElementById('actions-' + id).style.display = 'none';
    const ef = document.getElementById('editForm-' + id);
    ef.style.display = 'flex';
    ef.querySelector('input[name=checklist_item_name]').focus();
  }

  function cancelEdit(id) {
    document.getElementById('name-' + id).style.display = '';
    document.getElementById('actions-' + id).style.display = 'flex';
    document.getElementById('editForm-' + id).style.display = 'none';
  }
</script>