  <?php
    $page = 'Manpower Requisition Lists';
    $show_breadcrumb = true;
    ?>

<div class="row">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <h4 class="mb-2 mb-md-0">Manpower Requisition Management</h4>
      <div class="d-flex gap-2">
          <a href="<?= $GLOBALS['base_url'] ?>/create-requisition" class="btn btn-primary">
              <i class="fa-light fa-plus me-1"></i> Add Requisition
          </a>
      </div>
  </div>

    <div class="card__wrapper">

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Department From</th>
                        <th>Requested By</th>
                        <th>Position</th>
                        <th>Target Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                     <?php if (!empty($requisitions)): ?>
                        <?php foreach ($requisitions as $index => $req): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($req['department_name'] ?? 'N/A') ?></td>
                            <td><?= esc($req['requested_by_name'] ?? 'N/A') ?></td>
                            <td><?= esc($req['job_title']) ?></td>
                            <td><?= formatDate($req['to_be_filled_before']) ?></td>
                            <td>
                                <?php 
                                    $statusClass = 'bg-warning';
                                    if ($req['status'] === 'approved') $statusClass = 'bg-success';
                                    if ($req['status'] === 'rejected') $statusClass = 'bg-danger';
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst($req['status']) ?></span>
                            </td>
                            <td>
                                <a href="<?= $GLOBALS['base_url'] ?>/view-requisition/<?= $req['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-light fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                     <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No requisitions found.</td>
                        </tr>
                     <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>