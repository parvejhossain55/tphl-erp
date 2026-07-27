<div class="row justify-content-center">
    <div class="col-xxl-12 col-xl-12 col-lg-12">
        <!-- Filter Card -->
        <div class="card__wrapper mb-25 shadow-sm border-0" style="background: linear-gradient(145deg, #ffffff, #fbfbfb); border-radius: 12px;">
            <div class="card__body p-25">
                <div class="d-flex align-items-center justify-content-between mb-20">
                    <h5 class="card__heading-title m-0" style="font-weight: 700; color: #0f766e;">
                        <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Automated Payslip Generator
                    </h5>
                </div>
                
                <div class="row g-20 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Department <span class="text-danger">*</span></label>
                            <select id="departmentSelect" class="form-select select2">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Salary Month <span class="text-danger">*</span></label>
                            <input id="monthSelect" type="month" class="form-control" value="<?= date('Y-m') ?>" style="height: 48px; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Generation Date <span class="text-danger">*</span></label>
                            <input id="generationDate" type="date" class="form-control" value="<?= date('Y-m-d') ?>" style="height: 48px; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <button id="loadEmployeesBtn" class="btn btn-primary w-100" style="height: 48px; font-weight: 600; border-radius: 6px; background-color: #0f766e; border-color: #0f766e; transition: all 0.2s ease;">
                            <i class="fa-solid fa-users-gear me-2"></i>Load List
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info/Status Card -->
        <div id="tipsoiStatusCard" class="card__wrapper mb-25 p-20 border-start border-4 border-warning shadow-sm" style="display: none; border-radius: 6px; background: #fffdf5;">
            <div class="d-flex align-items-center gap-15">
                <div class="bg-warning-light p-3 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255, 193, 7, 0.1);">
                    <i class="fa-solid fa-circle-info fs-5"></i>
                </div>
                <div>
                    <h6 class="m-0 fw-bold text-dark" id="statusTitle">Tipsoi API Syncing</h6>
                    <small class="text-muted" id="statusDescription">Fetching attendance logs and calculating absent deductions...</small>
                </div>
            </div>
        </div>

        <!-- Employee Grid Card -->
        <div id="employeeGridCard" class="card__wrapper shadow-sm border-0 mb-30" style="display: none; border-radius: 12px; overflow: hidden; background: #fff;">
            <div class="card__body p-0">
                <div class="p-20 d-flex justify-content-between align-items-center border-bottom bg-light">
                    <h6 class="m-0 fw-bold text-secondary">Department Employee List & Adjustments</h6>
                    <div style="max-width: 250px;">
                        <input id="employeeSearch" type="text" class="form-control form-control-sm" placeholder="Search employee...">
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="generatorTable">
                        <thead class="table-light sticky-top" style="z-index: 10;">
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input" checked>
                                </th>
                                <th>Employee Details</th>
                                <th class="text-end">Basic Salary</th>
                                <th class="text-end">Gross Salary</th>
                                <th class="text-center" style="width: 100px;">Absents</th>
                                <!-- Dynamic Headers will be appended here -->
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <!-- JS populated rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sticky Floating Summary & Actions Footer -->
        <div id="stickySummaryFooter" class="card__wrapper shadow-lg border-top" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); margin: 0; border-radius: 0; box-shadow: 0 -10px 25px rgba(0,0,0,0.08);">
            <div class="container-fluid py-15 px-30">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-15">
                    <div class="d-flex align-items-center gap-30 flex-wrap">
                        <div>
                            <span class="text-muted fs-12 text-uppercase d-block fw-semibold">Employees Selected</span>
                            <span class="fw-bold fs-5 text-dark" id="selectedCount">0</span>
                        </div>
                        <div class="border-start pe-30 ps-30">
                            <span class="text-muted fs-12 text-uppercase d-block fw-semibold">Total Allowances</span>
                            <span class="fw-bold fs-5 text-success">৳ <span id="summaryAllowances">0.00</span></span>
                        </div>
                        <div class="border-start pe-30">
                            <span class="text-muted fs-12 text-uppercase d-block fw-semibold">Total Deductions</span>
                            <span class="fw-bold fs-5 text-danger">৳ <span id="summaryDeductions">0.00</span></span>
                        </div>
                        <div class="border-start pe-30">
                            <span class="text-muted fs-12 text-uppercase d-block fw-semibold">Total Net Payroll</span>
                            <span class="fw-bold fs-5 text-primary" style="color: #0f766e !important;">৳ <span id="summaryNetPayroll">0.00</span></span>
                        </div>
                    </div>
                    <div>
                        <button id="generateBtn" class="btn btn-lg btn-success" style="padding: 12px 30px; font-weight: 600; border-radius: 6px; background-color: #10b981; border-color: #10b981;">
                            <i class="fa-solid fa-circle-check me-2"></i>Generate & Submit for Approval
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styling -->
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
    }
    #generatorTable input[type="number"] {
        padding: 5px 8px;
        font-size: 13px;
        text-align: right;
        border-radius: 4px;
        border: 1px solid #ced4da;
    }
    #generatorTable input[type="number"]:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25);
    }
    .employee-card-details {
        line-height: 1.3;
    }
    .employee-card-details small {
        font-size: 11px;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
    /* Add extra padding at the bottom of the page container to prevent sticky footer overlapping last row */
    body {
        padding-bottom: 100px !important;
    }
</style>

<!-- Script -->
<script>
window.addEventListener('load', function () {
    const baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';
    const loadEmployeesBtn = document.getElementById('loadEmployeesBtn');
    const departmentSelect = document.getElementById('departmentSelect');
    const monthSelect      = document.getElementById('monthSelect');
    const generationDate   = document.getElementById('generationDate');
    const employeeGridCard = document.getElementById('employeeGridCard');
    const tipsoiStatusCard = document.getElementById('tipsoiStatusCard');
    const statusTitle      = document.getElementById('statusTitle');
    const statusDescription = document.getElementById('statusDescription');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const employeeTableBody = document.getElementById('employeeTableBody');
    const stickySummaryFooter = document.getElementById('stickySummaryFooter');
    const employeeSearch    = document.getElementById('employeeSearch');
    const generateBtn       = document.getElementById('generateBtn');

    let payslipItems = [];
    let employeeDataList = [];

    // Load Employee List Click
    loadEmployeesBtn.addEventListener('click', function() {
        const deptId = departmentSelect.value;
        const month  = monthSelect.value;

        if (!deptId) {
            alert('Please select a department.');
            return;
        }
        if (!month) {
            alert('Please select a month.');
            return;
        }

        // Show loading state
        loadEmployeesBtn.disabled = true;
        loadEmployeesBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Loading...';
        employeeGridCard.style.display = 'none';
        stickySummaryFooter.style.display = 'none';

        // Show Status bar
        tipsoiStatusCard.style.display = 'block';
        tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-warning shadow-sm';
        statusTitle.textContent = 'Tipsoi API Syncing...';
        statusDescription.textContent = 'Retrieving employee schedules and attendance records to count monthly absents...';

        fetch(`${baseUrl}/api/payroll/department-attendance?department_id=${deptId}&month=${month}`)
        .then(res => res.json())
        .then(res => {
            loadEmployeesBtn.disabled = false;
            loadEmployeesBtn.innerHTML = '<i class="fa-solid fa-users-gear me-2"></i>Load List';

            if (res.success) {
                payslipItems = res.payslip_items || [];
                employeeDataList = res.employees || [];

                if (employeeDataList.length === 0) {
                    tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-danger shadow-sm';
                    statusTitle.textContent = 'No Active Employees Found';
                    statusDescription.textContent = 'No active employees belong to the selected department.';
                    return;
                }

                // Update Status bar
                tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-success shadow-sm';
                statusTitle.textContent = 'Tipsoi Sync Completed';
                statusDescription.textContent = 'Successfully synchronized monthly attendance logs and calculated absent deductions.';

                // Build Table Headers & Body
                buildTableHeaders();
                renderTableRows();

                employeeGridCard.style.display = 'block';
                stickySummaryFooter.style.display = 'block';
                calculateSummary();
            } else {
                tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-danger shadow-sm';
                statusTitle.textContent = 'Tipsoi Connection Failure';
                statusDescription.textContent = res.message || 'An error occurred while fetching attendance logs.';
            }
        })
        .catch(err => {
            loadEmployeesBtn.disabled = false;
            loadEmployeesBtn.innerHTML = '<i class="fa-solid fa-users-gear me-2"></i>Load List';
            tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-danger shadow-sm';
            statusTitle.textContent = 'Network Error';
            statusDescription.textContent = 'Failed to make API request. Please verify connection.';
            console.error(err);
        });
    });

    // Dynamically build the table headers based on fetched active payslip items
    function buildTableHeaders() {
        const table = document.getElementById('generatorTable');
        const headerRow = table.tHead.rows[0];

        // Keep first 5 default headers
        while (headerRow.cells.length > 5) {
            headerRow.deleteCell(5);
        }

        // Add earning columns
        payslipItems.forEach(item => {
            if (item.type === 'earning') {
                const th = document.createElement('th');
                th.className = 'text-end text-success';
                th.style.minWidth = '120px';
                th.innerHTML = `${htmlspecialchars(item.name)}<br><small class="text-muted fw-normal">Earning</small>`;
                headerRow.appendChild(th);
            }
        });

        // Add deduction columns
        payslipItems.forEach(item => {
            if (item.type === 'deduction') {
                const th = document.createElement('th');
                th.className = 'text-end text-danger';
                th.style.minWidth = '120px';
                th.innerHTML = `${htmlspecialchars(item.name)}<br><small class="text-muted fw-normal">Deduction</small>`;
                headerRow.appendChild(th);
            }
        });

        // Add Net Salary Header
        const thNet = document.createElement('th');
        thNet.className = 'text-end text-primary';
        thNet.style.minWidth = '120px';
        thNet.innerHTML = 'Net Salary<br><small class="text-muted fw-normal">Take Home</small>';
        headerRow.appendChild(thNet);
    }

    // Render Table Rows
    function renderTableRows() {
        employeeTableBody.innerHTML = '';

        employeeDataList.forEach((emp, index) => {
            const tr = document.createElement('tr');
            tr.id = `emp_row_${emp.id}`;
            tr.dataset.index = index;

            // Details info
            const tipsoiIcon = emp.matched_in_tipsoi 
                ? '<span class="badge bg-success-light text-success border-success-light ms-2"><i class="fa-solid fa-fingerprint me-1"></i>Synced</span>' 
                : '<span class="badge bg-danger-light text-danger border-danger-light ms-2"><i class="fa-solid fa-ban me-1"></i>Not Synced</span>';

            let html = `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input row-checkbox" data-id="${emp.id}" checked>
                </td>
                <td>
                    <div class="employee-card-details">
                        <div class="fw-bold text-dark">${htmlspecialchars(emp.full_name)}</div>
                        <div class="text-muted small">${htmlspecialchars(emp.employee_id)} | ${htmlspecialchars(emp.designation_name || 'N/A')} ${tipsoiIcon}</div>
                    </div>
                </td>
                <td class="text-end fw-semibold text-secondary">৳ ${parseFloat(emp.basic_salary).toFixed(2)}</td>
                <td class="text-end fw-semibold text-secondary">৳ ${parseFloat(emp.gross_salary).toFixed(2)}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-center mx-auto input-absents" 
                        value="${emp.absent_days}" min="0" max="31" style="width: 70px;" data-index="${index}">
                </td>
            `;

            // Append earning inputs
            payslipItems.forEach(item => {
                if (item.type === 'earning') {
                    // Pre-fill amount from configured items (defaults to 0.00)
                    const val = emp.configured_items[item.id] || 0.00;
                    html += `
                        <td class="text-end">
                            <input type="number" class="form-control form-control-sm text-end input-earning" 
                                value="${parseFloat(val).toFixed(2)}" step="0.01" style="width: 110px;" data-item-id="${item.id}" data-index="${index}">
                        </td>
                    `;
                }
            });

            // Append deduction inputs
            payslipItems.forEach(item => {
                if (item.type === 'deduction') {
                    const itemNameNormalized = String(item.name || '').toLowerCase().trim();
                    const isUnpaidLeave = (item.id == 3 || item.id == 4 || itemNameNormalized.includes('unpaid') || itemNameNormalized.includes('leave') || itemNameNormalized.includes('lwp'));
                    let val = emp.configured_items[item.id] || 0.00;
                    
                    if (isUnpaidLeave) {
                        val = emp.absent_deduction; // Pre-fill with the calculated absent deduction
                    }

                    html += `
                        <td class="text-end">
                            <input type="number" class="form-control form-control-sm text-end input-deduction" 
                                value="${parseFloat(val).toFixed(2)}" step="0.01" style="width: 110px;" 
                                data-item-id="${item.id}" data-index="${index}" data-is-unpaid-leave="${isUnpaidLeave ? '1' : '0'}">
                        </td>
                    `;
                }
            });

            // Net Salary cell
            html += `
                <td class="text-end fw-bold text-dark text-nowrap cell-net-salary">৳ 0.00</td>
            `;

            tr.innerHTML = html;
            employeeTableBody.appendChild(tr);

            // Bind live calculations
            calculateRowNetSalary(tr);
        });

        // Bind row events
        bindTableEvents();
    }

    // Calculate Row Net Salary
    function calculateRowNetSalary(row) {
        const index = row.dataset.index;
        const emp = employeeDataList[index];
        const basicSalary = parseFloat(emp.basic_salary);

        let totalEarnings = basicSalary;
        let totalDeductions = 0.0;

        // Sum earnings
        row.querySelectorAll('.input-earning').forEach(inp => {
            totalEarnings += parseFloat(inp.value || 0);
        });

        // Sum deductions
        row.querySelectorAll('.input-deduction').forEach(inp => {
            totalDeductions += parseFloat(inp.value || 0);
        });

        const netSalary = totalEarnings - totalDeductions;
        row.querySelector('.cell-net-salary').textContent = `৳ ${netSalary.toFixed(2)}`;
    }

    // Bind Table Events (live typing + checking)
    function bindTableEvents() {
        // Individual checkbox change
        employeeTableBody.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', calculateSummary);
        });

        // Input changes
        employeeTableBody.querySelectorAll('input[type="number"]').forEach(inp => {
            inp.addEventListener('input', function() {
                const row = this.closest('tr');
                const idx = this.dataset.index;
                const emp = employeeDataList[idx];

                // If absents changed, recalculate absent deduction & update Unpaid Leave field in same row
                if (this.classList.contains('input-absents')) {
                    const absents = parseInt(this.value || 0);
                    const perDaySal = emp.gross_salary / 30;
                    const calculatedDeduction = roundTo(absents * perDaySal, 2);

                    // Update local list
                    emp.absent_days = absents;
                    emp.absent_deduction = calculatedDeduction;

                    // Update corresponding Unpaid Leave input in the row
                    const unpaidLeaveInp = row.querySelector('.input-deduction[data-is-unpaid-leave="1"]');
                    if (unpaidLeaveInp) {
                        unpaidLeaveInp.value = calculatedDeduction.toFixed(2);
                    }
                }

                // Recalculate Net Salary for this row
                calculateRowNetSalary(row);
                // Update global footer summary
                calculateSummary();
            });
        });
    }

    // Select All Checkbox
    selectAllCheckbox.addEventListener('change', function() {
        const checked = this.checked;
        employeeTableBody.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        calculateSummary();
    });

    // Calculate Summary Footer
    function calculateSummary() {
        let count = 0;
        let totalEarnings = 0;
        let totalDeductions = 0;
        let totalNetPayroll = 0;

        employeeTableBody.querySelectorAll('tr').forEach(row => {
            const checkbox = row.querySelector('.row-checkbox');
            if (checkbox && checkbox.checked) {
                count++;

                const index = row.dataset.index;
                const emp = employeeDataList[index];

                let rowAllowances = 0.0;
                row.querySelectorAll('.input-earning').forEach(inp => {
                    rowAllowances += parseFloat(inp.value || 0);
                });

                let rowDeductions = 0.0;
                row.querySelectorAll('.input-deduction').forEach(inp => {
                    rowDeductions += parseFloat(inp.value || 0);
                });

                const basic = parseFloat(emp.basic_salary);
                totalEarnings += (basic + rowAllowances);
                totalDeductions += rowDeductions;
                totalNetPayroll += ((basic + rowAllowances) - rowDeductions);
            }
        });

        document.getElementById('selectedCount').textContent = count;
        document.getElementById('summaryAllowances').textContent = totalEarnings.toFixed(2);
        document.getElementById('summaryDeductions').textContent = totalDeductions.toFixed(2);
        document.getElementById('summaryNetPayroll').textContent = totalNetPayroll.toFixed(2);
    }

    // Search filter
    employeeSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        employeeTableBody.querySelectorAll('tr').forEach(row => {
            const name = row.querySelector('.fw-bold').textContent.toLowerCase();
            const details = row.querySelector('.text-muted').textContent.toLowerCase();
            if (name.includes(query) || details.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Submit Bulk Payslips AJAX
    generateBtn.addEventListener('click', function() {
        const month = monthSelect.value;
        const genDate = generationDate.value;

        if (!month || !genDate) {
            alert('Please select salary month and generation date.');
            return;
        }

        const selectedEmployees = [];
        employeeTableBody.querySelectorAll('tr').forEach(row => {
            const checkbox = row.querySelector('.row-checkbox');
            if (checkbox && checkbox.checked) {
                const index = row.dataset.index;
                const emp = employeeDataList[index];

                // Gather earnings
                const earnings = {};
                let rowAllowances = 0.0;
                row.querySelectorAll('.input-earning').forEach(inp => {
                    const itemId = inp.dataset.itemId;
                    const val = parseFloat(inp.value || 0);
                    earnings[itemId] = val;
                    rowAllowances += val;
                });

                // Gather deductions
                const deductions = {};
                let rowDeductions = 0.0;
                row.querySelectorAll('.input-deduction').forEach(inp => {
                    const itemId = inp.dataset.itemId;
                    const val = parseFloat(inp.value || 0);
                    deductions[itemId] = val;
                    rowDeductions += val;
                });

                const basic = parseFloat(emp.basic_salary);
                const totalEarning = basic + rowAllowances;
                const netSalary = totalEarning - rowDeductions;

                selectedEmployees.push({
                    employee_id: emp.id,
                    employee_name: emp.full_name,
                    designation_id: emp.designation_id,
                    basic_salary: basic,
                    gross_salary: parseFloat(emp.gross_salary),
                    absent_days: parseInt(row.querySelector('.input-absents').value || 0),
                    total_earning: totalEarning,
                    total_deduction: rowDeductions,
                    net_salary: netSalary,
                    earnings: earnings,
                    deductions: deductions
                });
            }
        });

        if (selectedEmployees.length === 0) {
            alert('Please select at least one employee to generate payslips.');
            return;
        }

        if (!confirm(`Are you sure you want to generate payslips and submit approval requests for ${selectedEmployees.length} employees?`)) {
            return;
        }

        // Disable button
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Generating...';

        const payload = {
            payslip_month: month,
            payslip_date: genDate,
            employees: selectedEmployees
        };

        fetch(`${baseUrl}/store-bulk-payslips`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Generate & Submit for Approval';

            if (res.success) {
                alert(res.message);
                window.location.href = `${baseUrl}/payslip-lists`;
            } else {
                alert(res.message || 'Failed to generate bulk payslips.');
            }
        })
        .catch(err => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Generate & Submit for Approval';
            alert('A network error occurred. Please try again.');
            console.error(err);
        });
    });

    // Helper functions
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function roundTo(value, decimals) {
        return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
    }
});
</script>
