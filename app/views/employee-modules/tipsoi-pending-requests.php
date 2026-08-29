<!-- PAGE-HEADER -->
<div class="page-header d-flex align-items-center justify-content-between border-bottom mb-4 pb-3">
    <h1 class="page-title">Tipsoi Pending Requests</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Tipsoi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pending Requests Filter</li>
        </ol>
    </div>
</div>
<!-- PAGE-HEADER END -->

<div class="row">
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-header border-bottom">
                <h3 class="card-title">Filter Pending Requests</h3>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="row gy-3">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="filterType" name="type">
                                <option value="">All Types</option>
                                <option value="LEAVE">Leave</option>
                                <option value="LATE">Late</option>
                                <option value="MANUAL_ENTRY">Manual Entry</option>
                                <option value="MISSING_PUNCH">Missing Punch</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Approver Level</label>
                            <select class="form-select" id="filterApproverLevel" name="approverLevel">
                                <option value="">All</option>
                                <option value="1">First Approver</option>
                                <option value="2">Second Approver</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="filterStatus" name="status">
                                <option value="0">Pending</option>
                                <option value="1">Approved</option>
                                <option value="2">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="filterEmployeeId" name="employeeId" placeholder="e.g. 1024">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Keyword</label>
                            <input type="text" class="form-control" id="filterKeyword" name="keyword" placeholder="Search keyword...">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table text-nowrap table-bordered" id="pendingRequestsTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Approver Level</th>
                            </tr>
                        </thead>
                        <tbody id="pendingRequestsTbody">
                            <tr>
                                <td colspan="6" class="text-center">Click Search to load data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterForm = document.getElementById('filterForm');
    const tbody = document.getElementById('pendingRequestsTbody');

    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchData();
    });

    function fetchData() {
        const type = document.getElementById('filterType').value;
        const approverLevel = document.getElementById('filterApproverLevel').value;
        const status = document.getElementById('filterStatus').value;
        const employeeId = document.getElementById('filterEmployeeId').value;
        const keyword = document.getElementById('filterKeyword').value;

        tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>';

        const url = new URL('<?= $base_url ?>/api/employee/tipsoi-pending-requests', window.location.origin);
        if (type) url.searchParams.append('type', type);
        url.searchParams.append('status', status);
        if (approverLevel) url.searchParams.append('approverLevel', approverLevel);
        if (employeeId) url.searchParams.append('employeeId', employeeId);
        if (keyword) url.searchParams.append('keyword', keyword);
        url.searchParams.append('page', 0);
        url.searchParams.append('count', 100);

        fetch(url)
            .then(response => response.json())
            .then(res => {
                if(res.success) {
                    renderTable(res.data);
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${res.message}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to fetch data</td></tr>`;
            });
    }

    function renderTable(data) {
        // According to common Tipsoi response structure, array might be directly data, or in data.content or data.data
        let items = [];
        if (Array.isArray(data)) {
            items = data;
        } else if (data && data.content && Array.isArray(data.content)) {
            items = data.content;
        } else if (data && data.data && Array.isArray(data.data)) {
            items = data.data;
        } else if (data && data.applications && Array.isArray(data.applications)) {
            items = data.applications;
        }

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No pending requests found</td></tr>';
            return;
        }

        let html = '';
        items.forEach(item => {
            const id = item.id || '-';
            const reqType = item.type || item.applicationType || '-';
            
            // Format employee info
            let empName = '-';
            if (item.employee) {
                empName = `${item.employee.employeeName || ''} (${item.employee.employeeOfficeId || ''})`;
            } else if (item.employeeName) {
                empName = item.employeeName;
            }

            const date = item.createdAt || item.date || item.appliedDate || '-';
            
            let statusText = 'Pending';
            let badgeClass = 'bg-warning text-dark';
            if (item.status === 1) { statusText = 'Approved'; badgeClass = 'bg-success'; }
            if (item.status === 2) { statusText = 'Rejected'; badgeClass = 'bg-danger'; }

            const level = item.approverLevel || item.currentApproverLevel || '-';

            html += `
                <tr>
                    <td>${id}</td>
                    <td>${reqType}</td>
                    <td>${empName}</td>
                    <td>${date}</td>
                    <td><span class="badge ${badgeClass}">${statusText}</span></td>
                    <td>${level}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    // Fetch initial data
    fetchData();
});
</script>
<?php $page_script = ob_get_clean(); ?>
