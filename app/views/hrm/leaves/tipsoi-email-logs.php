<div class="content__wrapper">
    <div class="page-title-box">
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="page-title"><?= $page_title ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">HRM</a></li>
                    <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h4 class="card__title">Tipsoi Email Dispatch History</h4>
                    <button id="refreshLogsBtn" class="btn btn-sm btn-primary"><i class="fa fa-sync"></i> Refresh Logs</button>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="emailLogsTable" class="table table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date Sent</th>
                                    <th>Approver Name</th>
                                    <th>Email Address</th>
                                    <th>Pending Level</th>
                                    <th>Request Details</th>
                                    <th>Status</th>
                                    <th>Error Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    let table = $('#emailLogsTable').DataTable({
        ajax: {
            url: '<?= $GLOBALS['base_url'] ?>/api/hrm/tipsoi-email-logs',
            dataSrc: 'logs'
        },
        columns: [
            { data: 'id', defaultContent: '-' },
            { 
                data: 'sent_at',
                defaultContent: '-',
                render: function(data) {
                    if (!data || data === '-') return '-';
                    let d = new Date(data);
                    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                           d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                }
            },
            { data: 'approver_name', defaultContent: '-' },
            { data: 'approver_email', defaultContent: '-' },
            { data: 'pending_level', defaultContent: '-' },
            { 
                data: 'request_details',
                defaultContent: '-',
                render: function(data) {
                    return data && data !== '-' ? data.replace(/\n/g, '<br>') : '-';
                }
            },
            { 
                data: 'status',
                defaultContent: '-',
                render: function(data) {
                    if (data === 'Success') {
                        return '<span class="badge bg-success">Success</span>';
                    }
                    return '<span class="badge bg-danger">Failed</span>';
                }
            },
            { data: 'error_message', defaultContent: '-' }
        ],
        order: [[0, 'desc']]
    });

    $('#refreshLogsBtn').on('click', function() {
        table.ajax.reload();
    });
});
</script>
<?php $page_script = ob_get_clean(); ?>
