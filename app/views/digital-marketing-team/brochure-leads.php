<div class="app__slide-wrapper">
    <!-- Topbar/Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="page-title text-dark fw-bold mb-0">Brochure Leads</h4>
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
                    <h6 class="text-muted mb-2">Total Leads</h6>
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
                        <table class="table no-border-table" id="brochureLeadsTable">
                            <thead class="py-3">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Property Name</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="brochureLeadsBody">
                                <tr>
                                    <td colspan="8" class="text-center py-4">Loading leads...</td>
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
        let projectsMap = <?= json_encode($projects ?? []) ?>;
        let allLeads = [];

        async function fetchLiveProjects() {
            try {
                // The backend base URL can be derived from the API URL
                const baseUrl = apiUrl.replace(/\/api\/erp\/?$/, '');
                const response = await fetch(`${baseUrl}/api/projects`);
                if (response.ok) {
                    const data = await response.json();
                    let projectList = Array.isArray(data) ? data : (data.projects || []);
                    projectList.forEach(p => {
                        projectsMap[p.id] = p.name;
                    });
                }
            } catch (error) {
                console.error("Error fetching live projects:", error);
            }
        }

        function fetchLeads() {
            fetch(`${apiUrl}/brochure-leads`, {
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
                if (data.success && data.leads) {
                    allLeads = data.leads;
                    renderTable(data.leads);
                    updateStats(data.leads);
                } else {
                    document.getElementById('brochureLeadsBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Failed to load leads</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('brochureLeadsBody').innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${err.message}</td></tr>`;
            });
        }

        function updateStats(leads) {
            const now = new Date();
            const oneWeekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            const oneMonthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);

            let total = leads.length;
            let exported = 0;
            let newLeads = 0;
            let weekly = 0;
            let monthly = 0;

            leads.forEach(lead => {
                if (lead.is_exported) exported++;
                else newLeads++;

                const leadDate = new Date(lead.created_at);
                if (leadDate >= oneWeekAgo) weekly++;
                if (leadDate >= oneMonthAgo) monthly++;
            });

            document.getElementById('statTotal').innerText = total;
            document.getElementById('statNew').innerText = newLeads;
            document.getElementById('statExported').innerText = exported;
            document.getElementById('statWeekly').innerText = weekly;
            document.getElementById('statMonthly').innerText = monthly;
        }

        function renderTable(leads) {
            const tbody = document.getElementById('brochureLeadsBody');
            tbody.innerHTML = '';

            if (leads.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">No leads found.</td></tr>';
                return;
            }

            leads.forEach(lead => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="checkbox" class="lead-checkbox" value="${lead.id}"></td>
                    <td>${lead.id}</td>
                    <td class="fw-bold">${lead.name || ''}</td>
                    <td>${lead.email || ''}</td>
                    <td>${lead.phone || ''}</td>
                    <td><span class="badge bg-secondary">${projectsMap[lead.property_id] || (lead.property_id ? `ID: ${lead.property_id}` : 'N/A')}</span></td>
                    <td>${lead.is_exported ? '<span class="badge bg-success text-white">Exported</span>' : '<span class="badge bg-warning text-dark">New</span>'}</td>
                    <td class="text-muted small">${new Date(lead.created_at).toLocaleString()}</td>
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
                alert("No leads selected.");
                return;
            }
            // Sort IDs to find min and max easily
            ids.sort((a, b) => a - b);
            const start_id = ids[0];
            const end_id = ids[ids.length - 1];

            fetch(`${apiUrl}/brochure-leads/export`, {
                method: "POST",
                headers: {
                    "X-API-KEY": apiKey,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ start_id: parseInt(start_id), end_id: parseInt(end_id) })
            })
            .then(res => res.json())
            .then(data => {
                let exportData = null;
                if (data.leads && Array.isArray(data.leads)) {
                    exportData = data.leads;
                } else if (data.success && Array.isArray(data.data)) {
                    exportData = data.data;
                } else if (Array.isArray(data)) {
                    exportData = data;
                }

                if (exportData) {
                    // Replace property_id with property_name using projectsMap and remove is_exported
                    exportData = exportData.map(row => {
                        let newRow = {};
                        for (let key in row) {
                            if (key === 'is_exported') continue;
                            
                            if (key === 'property_id') {
                                newRow['property_name'] = projectsMap[row[key]] || (row[key] ? `ID: ${row[key]}` : 'N/A');
                            } else if (key === 'created_at') {
                                newRow['created_at'] = row[key] ? new Date(row[key]).toLocaleString() : '';
                            } else {
                                newRow[key] = row[key];
                            }
                        }
                        return newRow;
                    });
                    downloadCSV(exportData, `brochure-leads-export-${new Date().getTime()}.csv`);
                    fetchLeads(); // refresh table
                } else {
                    alert("Export failed or returned unexpected data format.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("An error occurred during export.");
            });
        }

        document.getElementById('selectAll').addEventListener('change', function(e) {
            document.querySelectorAll('.lead-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
        });

        document.getElementById('btnExportSelected').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.lead-checkbox:checked')).map(cb => cb.value);
            handleExport(selectedIds);
        });

        document.getElementById('btnExportAllNew').addEventListener('click', function() {
            const newIds = allLeads.filter(l => !l.is_exported).map(l => l.id);
            if (newIds.length === 0) {
                alert("No new leads to export.");
                return;
            }
            handleExport(newIds);
        });

        async function init() {
            await fetchLiveProjects();
            fetchLeads();
        }

        init();
    });
</script>
