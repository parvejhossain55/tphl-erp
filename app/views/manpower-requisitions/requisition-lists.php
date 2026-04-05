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
        <h4 class="mb-4">Manpower Requisition Lists</h4>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Requested By</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Sample Data -->
                    <tr>
                        <td>1</td>
                        <td>Software Engineer</td>
                        <td>IT Department</td>
                        <td>John Doe</td>
                        <td>2024-06-01</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Marketing Manager</td>
                        <td>Marketing Department</td>
                        <td>Jane Smith</td>
                        <td>2024-05-28</td>
                        <td><span class="badge bg-success">Approved</span></td>
                    </tr>
                    <!-- Add more rows as needed -->
                </tbody>
            </table>
        </div>
    </div>
</div>