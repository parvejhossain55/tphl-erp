<div class="app__slide-wrapper">
    <!-- Topbar/Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="page-title text-dark fw-bold mb-0">
                    <a href="<?= $GLOBALS['base_url'] ?>/forms" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-2"></i></a>
                    Form Responses <span id="formCampaignName" class="text-primary"></span>
                </h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" id="btnExportAllNew">Export All New</button>
                    <button class="btn btn-primary" id="btnExportSelected">Export Selected</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Responses</h6>
                    <h3 class="fw-bold mb-0" id="statTotal">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-body">
                    <h6 class="text-muted mb-2">New / Exported</h6>
                    <h3 class="fw-bold mb-0"><span id="statNew" class="text-warning">0</span> / <span id="statExported" class="text-success">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Weekly Collected</h6>
                    <h3 class="fw-bold mb-0" id="statWeekly">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Monthly Collected</h6>
                    <h3 class="fw-bold mb-0" id="statMonthly">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table no-border-table" id="formResponsesTable">
                            <thead class="py-3" id="formResponsesHead">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <!-- Dynamic headers will be injected here -->
                                </tr>
                            </thead>
                            <tbody id="formResponsesBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">Loading responses...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const apiUrl = "<?= $api_url ?>";
        const apiKey = "<?= $api_key ?>";
        const formId = <?= $form_id ?>;
        
        let allResponses = [];
        let dynamicKeys = [];

        function fetchResponses() {
            fetch(`${apiUrl}/forms/${formId}/responses`, {
                method: "GET",
                headers: {
                    "X-API-KEY": apiKey,
                    "Accept": "application/json"
                }
            })
            .then(res => {
                if(res.status === 401) {
                    throw new Error("Unauthorized: API Key is invalid or missing.");
                }
                return res.json();
            })
            .then(data => {
                if (data.success && data.responses) {
                    allResponses = data.responses;
                    if(data.form) {
                        document.getElementById('formCampaignName').textContent = `- ${data.form}`;
                    }
                    renderTable(data.responses);
                    updateStats(data.responses);
                } else {
                    document.getElementById('formResponsesBody').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load responses</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('formResponsesBody').innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${err.message}</td></tr>`;
            });
        }

        function extractKeys(responses) {
            const keys = new Set();
            responses.forEach(res => {
                if (res.response_json && typeof res.response_json === 'object') {
                    Object.keys(res.response_json).forEach(k => keys.add(k));
                }
            });
            return Array.from(keys);
        }

        function updateStats(responses) {
            const now = new Date();
            const oneWeekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            const oneMonthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);

            let total = responses.length;
            let exported = 0;
            let newResponses = 0;
            let weekly = 0;
            let monthly = 0;

            responses.forEach(res => {
                if (res.is_exported) exported++;
                else newResponses++;

                const resDate = new Date(res.created_at);
                if (resDate >= oneWeekAgo) weekly++;
                if (resDate >= oneMonthAgo) monthly++;
            });

            document.getElementById('statTotal').innerText = total;
            document.getElementById('statNew').innerText = newResponses;
            document.getElementById('statExported').innerText = exported;
            document.getElementById('statWeekly').innerText = weekly;
            document.getElementById('statMonthly').innerText = monthly;
        }

        function renderTable(responses) {
            const thead = document.getElementById('formResponsesHead');
            const tbody = document.getElementById('formResponsesBody');
            tbody.innerHTML = '';

            if (responses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No responses found.</td></tr>';
                return;
            }

            dynamicKeys = extractKeys(responses);

            // Re-render header
            thead.innerHTML = `
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Date</th>
                    ${dynamicKeys.map(k => `<th>${k}</th>`).join('')}
                </tr>
            `;

            // Re-attach selectAll event
            document.getElementById('selectAll').addEventListener('change', function(e) {
                document.querySelectorAll('.response-checkbox').forEach(cb => {
                    cb.checked = e.target.checked;
                });
            });

            // Render rows
            responses.forEach(res => {
                const tr = document.createElement('tr');
                
                let dynamicCells = '';
                dynamicKeys.forEach(k => {
                    const val = res.response_json && res.response_json[k] ? res.response_json[k] : '';
                    dynamicCells += `<td>${val}</td>`;
                });

                tr.innerHTML = `
                    <td><input type="checkbox" class="response-checkbox" value="${res.id}"></td>
                    <td>${res.id}</td>
                    <td>${res.is_exported ? '<span class="badge bg-success text-white">Exported</span>' : '<span class="badge bg-warning text-dark">New</span>'}</td>
                    <td class="text-muted small">${new Date(res.created_at).toLocaleString()}</td>
                    ${dynamicCells}
                `;
                tbody.appendChild(tr);
            });
        }

        function downloadCSV(data, filename) {
            if (!data || !data.length) return;
            const header = Object.keys(data[0]).join(',');
            const rows = data.map(obj => 
                Object.values(obj).map(val => {
                    let str = val === null || val === undefined ? '' : String(val);
                    str = str.replace(/"/g, '""');
                    if (str.search(/("|,|\n)/g) >= 0) {
                        str = `"${str}"`;
                    }
                    return str;
                }).join(',')
            ).join('\n');
            const csv = header + '\n' + rows;
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function handleExport(ids) {
            if (ids.length === 0) {
                alert("No responses selected.");
                return;
            }

            fetch(`${apiUrl}/forms/${formId}/responses/export`, {
                method: "POST",
                headers: {
                    "X-API-KEY": apiKey,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ response_ids: ids.map(id => parseInt(id)) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Extract data from frontend for CSV generation since backend only returns success
                    const selectedData = allResponses.filter(r => ids.includes(r.id.toString()));
                    
                    // Flatten JSON for CSV
                    const flattenedData = selectedData.map(r => {
                        const flat = {
                            id: r.id,
                            created_at: r.created_at ? new Date(r.created_at).toLocaleString() : ''
                        };
                        if (r.response_json) {
                            dynamicKeys.forEach(k => {
                                flat[k] = r.response_json[k] || '';
                            });
                        }
                        return flat;
                    });
                    
                    downloadCSV(flattenedData, `form-${formId}-export-${new Date().getTime()}.csv`);
                    fetchResponses(); // refresh table
                } else {
                    alert("Export failed on the backend.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("An error occurred during export.");
            });
        }

        document.getElementById('btnExportSelected').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.response-checkbox:checked')).map(cb => cb.value);
            handleExport(selectedIds);
        });

        document.getElementById('btnExportAllNew').addEventListener('click', function() {
            const newIds = allResponses.filter(r => !r.is_exported).map(r => r.id.toString());
            if (newIds.length === 0) {
                alert("No new responses to export.");
                return;
            }
            handleExport(newIds);
        });

        fetchResponses();
    });
</script>
