<div class="row justify-content-center">
    <div class="col-xxl-12 col-xl-12 col-lg-12">
        <!-- Filter Card -->
        <div class="card__wrapper mb-25 shadow-sm border-0"
            style="background: linear-gradient(145deg, #ffffff, #fbfbfb); border-radius: 12px;">
            <div class="card__body p-25">
                <div class="d-flex align-items-center justify-content-between mb-20">
                    <h5 class="card__heading-title m-0" style="font-weight: 700; color: #0f766e;">
                        <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Automated Payslip Generator
                    </h5>
                </div>

                <div class="row g-20 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Payroll Project <span
                                    class="text-danger">*</span></label>
                            <select id="projectSelect" class="form-select select2">
                                <option value="">-- Select Project --</option>
                                <?php foreach ($payroll_projects as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>" <?= (isset($selected_project_id) && $selected_project_id == $proj['id']) ? 'selected' : '' ?>><?= htmlspecialchars($proj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Department</label>
                            <select id="departmentSelect" class="form-select select2">
                                <option value="0">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Salary Month <span
                                    class="text-danger">*</span></label>
                            <input id="monthSelect" type="month" class="form-control" value="<?= date('Y-m') ?>"
                                style="height: 48px; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <div class="form__group">
                            <label class="form__input-title mb-2" style="font-weight: 600;">Generation Date <span
                                    class="text-danger">*</span></label>
                            <input id="generationDate" type="date" class="form-control" value="<?= date('Y-m-d') ?>"
                                style="height: 48px; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <button id="loadEmployeesBtn" class="btn btn-primary w-100"
                            style="height: 48px; font-weight: 600; border-radius: 6px; background-color: #0f766e; border-color: #0f766e; transition: all 0.2s ease;">
                            <i class="fa-solid fa-users-gear me-2"></i>Load List
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info/Status Card -->
        <div id="tipsoiStatusCard" class="card__wrapper mb-25 p-20 border-start border-4 border-warning shadow-sm"
            style="display: none; border-radius: 6px; background: #fffdf5;">
            <div class="d-flex align-items-center gap-15">
                <div class="bg-warning-light p-3 rounded-circle text-warning d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px; background: rgba(255, 193, 7, 0.1);">
                    <i class="fa-solid fa-circle-info fs-5"></i>
                </div>
                <div>
                    <h6 class="m-0 fw-bold text-dark" id="statusTitle">Tipsoi API Syncing</h6>
                    <small class="text-muted" id="statusDescription">Fetching attendance logs and calculating absent
                        deductions...</small>
                </div>
            </div>
        </div>

        <!-- Employee Grid Card -->
        <div id="employeeGridCard" class="card__wrapper shadow-sm border-0 mb-30"
            style="display: none; border-radius: 12px; overflow: hidden; background: #fff;">
            <div class="card__body p-0">
                <div class="p-20 d-flex justify-content-between align-items-center border-bottom bg-light">
                    <h6 class="m-0 fw-bold text-secondary">Department Employee List & Adjustments</h6>
                    <div style="max-width: 250px;">
                        <input id="employeeSearch" type="text" class="form-control form-control-sm"
                            placeholder="Search employee...">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="generatorTable">
                        <thead class="table-light sticky-top" style="z-index: 10; white-space: nowrap;">
                            <tr class="table__title table__sort">
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input" checked>
                                </th>
                                <th>Department</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>ID</th>
                                <th>Workplace</th>
                                <th>Designation</th>
                                <th>Basic Salary</th>

                                <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'earning'): ?>
                                        <th><?= htmlspecialchars($item['name']) ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <th>Gross</th>
                                <th>Daily Salary</th>
                                <th>Advance on Salary</th>
                                <th>Meal Count</th>
                                <th>Subsidized Lunch</th>
                                <th>Late/Unpaid/Absent (Days)</th>
                                <th>Late/Unpaid/Absent (Amount)</th>

                                <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'deduction'): ?>
                                        <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false && strpos(strtolower($item['name']), 'lwp') === false && strpos(strtolower($item['name']), 'salary hold') === false): ?>
                                            <th><?= htmlspecialchars($item['name']) ?></th>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <th>Punctuality Bonus</th>
                                <th>Overtime</th>
                                <th>Arrear</th>
                                <th>Duty/Food/Car Allowance</th>
                                <th>Joining Date</th>
                                <th>Resignation Date</th>
                                <th>Actual Effective Days</th>
                                <th>Net Salary</th>
                                <th>Tax</th>
                                <th>Net Payable</th>
                                <th>Bank</th>
                                <th>Cash</th>
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
        <div id="stickySummaryFooter" class="card__wrapper shadow-lg border-top"
            style="display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); margin: 0; border-radius: 0; box-shadow: 0 -10px 25px rgba(0,0,0,0.08);">
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
                            <span class="fw-bold fs-5 text-primary" style="color: #0f766e !important;">৳ <span
                                    id="summaryNetPayroll">0.00</span></span>
                        </div>
                    </div>
                    <div>
                        <button id="generateBtn" class="btn btn-lg btn-success"
                            style="padding: 12px 30px; font-weight: 600; border-radius: 6px; background-color: #10b981; border-color: #10b981;">
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
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
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
        const projectSelect = document.getElementById('projectSelect');
        const departmentSelect = document.getElementById('departmentSelect');
        const monthSelect = document.getElementById('monthSelect');
        const generationDate = document.getElementById('generationDate');
        const employeeGridCard = document.getElementById('employeeGridCard');
        const tipsoiStatusCard = document.getElementById('tipsoiStatusCard');
        const statusTitle = document.getElementById('statusTitle');
        const statusDescription = document.getElementById('statusDescription');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const employeeTableBody = document.getElementById('employeeTableBody');
        const stickySummaryFooter = document.getElementById('stickySummaryFooter');
        const employeeSearch = document.getElementById('employeeSearch');
        const generateBtn = document.getElementById('generateBtn');

        let payslipItems = [];
        let employeeDataList = [];

        function syncMonthFromProject() {
            const selectedOpt = projectSelect.options[projectSelect.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.date) {
                const pDate = selectedOpt.dataset.date;
                if (pDate && pDate.length >= 7) {
                    monthSelect.value = pDate.substring(0, 7);
                }
            }
        }
        projectSelect.addEventListener('change', syncMonthFromProject);
        if (projectSelect.value) {
            syncMonthFromProject();
        }

        // Load Employee List Click
        loadEmployeesBtn.addEventListener('click', function () {
            const projectId = projectSelect.value;
            const deptId = departmentSelect.value;
            const month = monthSelect.value;

            if (!projectId) {
                alert('Please select a Payroll Project.');
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
            statusTitle.textContent = 'Loading Records...';
            statusDescription.textContent = 'Retrieving employee and local attendance records...';

            fetch(`${baseUrl}/api/payroll/department-attendance?project_id=${projectId}&department_id=${deptId}&month=${month}`)
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(text || res.statusText || 'Invalid server response');
                    }
                })
                .then(res => {
                    loadEmployeesBtn.disabled = false;
                    loadEmployeesBtn.innerHTML = '<i class="fa-solid fa-users-gear me-2"></i>Load List';

                    if (res && res.success) {
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
                        statusTitle.textContent = 'Employee Data Loaded';
                        statusDescription.textContent = 'Successfully loaded employees and applied local attendance deductions.';

                        // Render Table Rows
                        renderTableRows();

                        employeeGridCard.style.display = 'block';
                        stickySummaryFooter.style.display = 'block';
                        calculateSummary();
                    } else {
                        tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-danger shadow-sm';
                        statusTitle.textContent = 'Data Fetch Failure';
                        statusDescription.textContent = (res && res.message) ? res.message : 'An error occurred while fetching employee records.';
                    }
                })
                .catch(err => {
                    loadEmployeesBtn.disabled = false;
                    loadEmployeesBtn.innerHTML = '<i class="fa-solid fa-users-gear me-2"></i>Load List';
                    tipsoiStatusCard.className = 'card__wrapper mb-25 p-20 border-start border-4 border-danger shadow-sm';
                    statusTitle.textContent = 'Request Error';
                    statusDescription.textContent = err.message || 'Failed to make API request. Please verify connection.';
                    console.error(err);
                });
        });

        // Identify special items
        let itemMap = {};
        payslipItems.forEach(x => {
            const name = (x.name || '').toLowerCase();
            if (name.includes('advance')) itemMap.advance = x.id;
            else if (name.includes('lunch') || name.includes('meal')) itemMap.lunch = x.id;
            else if (name.includes('absent') || name.includes('lwp')) itemMap.absent = x.id;
            else if (name.includes('punctuality')) itemMap.punctuality = x.id;
            else if (name.includes('overtime')) itemMap.overtime = x.id;
            else if (name.includes('arrear')) itemMap.arrear = x.id;
            else if (name.includes('tax')) itemMap.tax = x.id;
            else if (name.includes('other')) itemMap.other = x.id;
        });

        // Render Table Rows
        function renderTableRows() {
            employeeTableBody.innerHTML = '';

            employeeDataList.forEach((emp, index) => {
                const tr = document.createElement('tr');
                tr.id = `emp_row_${emp.id}`;
                tr.dataset.index = index;
                tr.className = 'calc-row';
                
                const gross = parseFloat(emp.gross_salary) || 0;
                const basic = parseFloat(emp.basic_salary) || 0;
                const daily = gross / 30;

                let html = `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input row-checkbox" data-id="${emp.id}" checked>
                </td>
                <td>${htmlspecialchars(emp.department_name || 'N/A')}</td>
                <td>${htmlspecialchars(emp.full_name)}</td>
                <td>${htmlspecialchars(emp.email || 'N/A')}</td>
                <td>${htmlspecialchars(emp.employee_id)}</td>
                <td>${htmlspecialchars(emp.workplace || 'N/A')}</td>
                <td>${htmlspecialchars(emp.designation_name || 'N/A')}</td>
                <td class="cell-basic" data-val="${basic}">${basic.toFixed(2)}</td>
                `;

                // Dynamic Earnings
                payslipItems.forEach(item => {
                    if (item.type === 'earning') {
                        let val = parseFloat(emp.configured_items[item.id] || 0);
                        const nameStr = (item.name || '').toLowerCase();
                        if (nameStr.includes('recreation')) {
                            val = (emp.recreation_allowance !== null && emp.recreation_allowance !== undefined && emp.recreation_allowance !== '') ? parseFloat(emp.recreation_allowance) : (basic * 0.10);
                        } else if (nameStr.includes('conveyance')) {
                            val = (emp.conveyance_allowance !== null && emp.conveyance_allowance !== undefined && emp.conveyance_allowance !== '') ? parseFloat(emp.conveyance_allowance) : (basic * 0.10);
                        } else if (nameStr.includes('medical')) {
                            val = (emp.medical_allowance !== null && emp.medical_allowance !== undefined && emp.medical_allowance !== '') ? parseFloat(emp.medical_allowance) : (basic * 0.30);
                        } else if (nameStr.includes('house rent')) {
                            val = (emp.house_rent_allowance !== null && emp.house_rent_allowance !== undefined && emp.house_rent_allowance !== '') ? parseFloat(emp.house_rent_allowance) : (basic * 0.50);
                        }
                        html += `<td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning" data-item-id="${item.id}" value="${val.toFixed(2)}" style="min-width: 80px;"></td>`;
                    }
                });

                const mealCount = parseFloat(emp.meal_count !== undefined && emp.meal_count !== null ? emp.meal_count : (emp.tipsoi_present || 0));
                const unpaidAbsentsDays = parseFloat(emp.late_unpaid_absent_days !== undefined && emp.late_unpaid_absent_days !== null ? emp.late_unpaid_absent_days : (emp.absent_days || 0));
                const advanceSalary = parseFloat(emp.advance_salary || 0);
                const subsidizedLunch = parseFloat(emp.subsidized_lunch || 0);
                const unpaidAbsentAmount = parseFloat(emp.unpaid_absent_amount || 0);

                html += `
                <td class="cell-gross fw-bold text-nowrap">${gross.toFixed(2)}</td>
                <td class="cell-daily text-nowrap" data-val="${daily.toFixed(2)}">${daily.toFixed(2)}</td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-advance" ${itemMap.advance ? `data-item-id="${itemMap.advance}"` : ''} value="${advanceSalary.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-mealcount" data-permeal="${emp.per_meal_amount || 0}" value="${mealCount}" style="width: 60px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-sublunch" ${itemMap.lunch ? `data-item-id="${itemMap.lunch}"` : ''} value="${subsidizedLunch.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-absent-days" value="${unpaidAbsentsDays}" style="width: 60px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-unpaid" ${itemMap.absent ? `data-item-id="${itemMap.absent}"` : ''} value="${unpaidAbsentAmount.toFixed(2)}" style="min-width: 80px;"></td>
                `;

                // General Deductions
                payslipItems.forEach(item => {
                    if (item.type === 'deduction') {
                        const lname = (item.name || '').toLowerCase();
                        if (!lname.includes('advance') && !lname.includes('lunch') && !lname.includes('absent') && !lname.includes('lwp') && !lname.includes('salary hold')) {
                            let existingAmt = parseFloat(emp.configured_items[item.id] || 0);
                            html += `<td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-other-deduct" data-item-id="${item.id}" value="${existingAmt.toFixed(2)}" style="min-width: 80px;"></td>`;
                        }
                    }
                });

                const punctuality = parseFloat(emp.punctuality_bonus || 0);
                const overtime = parseFloat(emp.overtime || 0);
                const arrearEarning = parseFloat(emp.arrears_earning || 0);
                const dutyAllowance = parseFloat(emp.duty_allowance || 0);
                const tax = parseFloat(emp.tax || 0);
                const bank = parseFloat(emp.bank_amount || 0);
                const cash = parseFloat(emp.cash_amount || 0);
                const savedNetSalary = parseFloat(emp.net_salary || 0);

                html += `
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-punctuality" ${itemMap.punctuality ? `data-item-id="${itemMap.punctuality}"` : ''} value="${punctuality.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-overtime" ${itemMap.overtime ? `data-item-id="${itemMap.overtime}"` : ''} value="${overtime.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-arrear" ${itemMap.arrear ? `data-item-id="${itemMap.arrear}"` : ''} value="${arrearEarning.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-otherallow" ${itemMap.other ? `data-item-id="${itemMap.other}"` : ''} value="${dutyAllowance.toFixed(2)}" style="min-width: 80px;"></td>
                <td class="text-nowrap">${emp.joining_date ? (new Date(emp.joining_date)).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-') : '&nbsp;'}</td>
                <td class="text-nowrap">&nbsp;</td>
                <td>30</td>
                <td class="cell-net fw-bold text-primary text-nowrap" data-payment-method="${emp.payment_method || 'bank'}">${savedNetSalary > 0 ? savedNetSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'}</td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-tax" ${itemMap.tax ? `data-item-id="${itemMap.tax}"` : ''} value="${tax.toFixed(2)}" style="min-width: 80px;"></td>
                <td class="cell-netpayable fw-bold text-success text-nowrap">${savedNetSalary > 0 ? savedNetSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'}</td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-bank" value="${bank.toFixed(2)}" style="min-width: 80px;"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-cash" value="${cash.toFixed(2)}" style="min-width: 80px;"></td>
                `;

                tr.innerHTML = html;
                employeeTableBody.appendChild(tr);

                // Initial run of our calculations for this row
                calculateRowNetSalary(tr, true);
            });

            bindTableEvents();
        }

        // Calculate Row Net Salary (Workspace Logic)
        function calculateRowNetSalary(row, isInitial = false) {
            const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
            const daily = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0;
            const perMeal = parseFloat(row.querySelector('.in-mealcount').dataset.permeal) || 0;
            
            const mealCount = parseFloat(row.querySelector('.in-mealcount').value) || 0;
            const currentSubLunch = row.querySelector('.in-sublunch');
            
            if (!isInitial) {
                // User interacted
            } else if (!currentSubLunch.value || currentSubLunch.value === "0.00" || currentSubLunch.value === "0") {
                if (mealCount > 0 && perMeal > 0) {
                    currentSubLunch.value = (mealCount * perMeal).toFixed(2);
                }
            }

            const absentDays = parseFloat(row.querySelector('.in-absent-days').value) || 0;
            const currentUnpaid = row.querySelector('.in-unpaid');
            if (!isInitial) {
                // User interacted
            } else if (!currentUnpaid.value || currentUnpaid.value === "0.00" || currentUnpaid.value === "0") {
                if (absentDays > 0 && daily > 0) {
                    currentUnpaid.value = (absentDays * daily).toFixed(2);
                }
            }

            let gross = parseFloat(row.querySelector('.cell-gross').textContent.replace(/,/g, '')) || 0;
            let totalEarning = gross;
            row.querySelectorAll('.in-earning').forEach(inp => {
                if (inp.classList.contains('in-punctuality') || 
                    inp.classList.contains('in-overtime') || 
                    inp.classList.contains('in-arrear') || 
                    inp.classList.contains('in-otherallow')) {
                    totalEarning += (parseFloat(inp.value) || 0);
                }
            });

            let totalDeduction = 0;
            row.querySelectorAll('.in-deduct').forEach(inp => {
                totalDeduction += (parseFloat(inp.value) || 0);
            });

            let netSalary = totalEarning - totalDeduction;
            let netCell = row.querySelector('.cell-net');
            if(netCell) netCell.textContent = netSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            let tax = parseFloat(row.querySelector('.in-tax').value) || 0;
            let netPayable = netSalary; 
            
            let netPayableCell = row.querySelector('.cell-netpayable');
            if(netPayableCell) netPayableCell.textContent = netPayable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const payMethod = (row.querySelector('.cell-net').dataset.paymentMethod || '').toLowerCase();
            let bankInput = row.querySelector('.in-bank');
            let cashInput = row.querySelector('.in-cash');

            let bankVal = parseFloat(bankInput.value) || 0;
            let cashVal = parseFloat(cashInput.value) || 0;

            if (!isInitial || (bankVal === 0 && cashVal === 0)) {
                if (payMethod.includes('bank')) {
                    bankInput.value = Math.max(0, netPayable - cashVal).toFixed(2);
                } else {
                    cashInput.value = Math.max(0, netPayable - bankVal).toFixed(2);
                }
            }
        }

        // Bind Table Events (live typing + checking)
        function bindTableEvents() {
            // Individual checkbox change
            employeeTableBody.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', calculateSummary);
            });

            document.querySelectorAll('.calc-row').forEach(row => {
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('input', function (e) {
                        const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
                        const daily = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0;
                        const perMeal = parseFloat(row.querySelector('.in-mealcount').dataset.permeal) || 0;

                        if (!e.target.classList.contains('in-sublunch')) {
                            const mealCount = parseFloat(row.querySelector('.in-mealcount').value) || 0;
                            row.querySelector('.in-sublunch').value = (mealCount * perMeal).toFixed(2);
                        } else {
                            if (perMeal > 0) {
                                const subLunch = parseFloat(row.querySelector('.in-sublunch').value) || 0;
                                row.querySelector('.in-mealcount').value = (subLunch / perMeal).toFixed(2);
                            }
                        }

                        if (e.target.classList.contains('in-absent-days')) {
                            const absentDays = parseFloat(row.querySelector('.in-absent-days').value) || 0;
                            row.querySelector('.in-unpaid').value = (absentDays * daily).toFixed(2);
                        }

                        calculateRowNetSalary(row);
                        calculateSummary();
                    });
                });
            });
        }

        // Select All Checkbox
        selectAllCheckbox.addEventListener('change', function () {
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

                    let rowExtraEarnings = 0.0;
                    row.querySelectorAll('.in-earning').forEach(inp => {
                        if (inp.classList.contains('in-punctuality') || 
                            inp.classList.contains('in-overtime') || 
                            inp.classList.contains('in-arrear') || 
                            inp.classList.contains('in-otherallow')) {
                            rowExtraEarnings += parseFloat(inp.value || 0);
                        }
                    });

                    let rowDeductions = 0.0;
                    row.querySelectorAll('.in-deduct').forEach(inp => {
                        rowDeductions += parseFloat(inp.value || 0);
                    });

                    const gross = parseFloat(row.querySelector('.cell-gross').textContent.replace(/,/g, '')) || 0;
                    totalEarnings += (gross + rowExtraEarnings);
                    totalDeductions += rowDeductions;
                    totalNetPayroll += parseFloat(row.querySelector('.cell-netpayable').textContent.replace(/,/g, '')) || 0;
                }
            });

            document.getElementById('selectedCount').textContent = count;
            document.getElementById('summaryAllowances').textContent = totalEarnings.toFixed(2);
            document.getElementById('summaryDeductions').textContent = totalDeductions.toFixed(2);
            document.getElementById('summaryNetPayroll').textContent = totalNetPayroll.toFixed(2);
        }

        // Search filter
        employeeSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            employeeTableBody.querySelectorAll('tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Submit Bulk Payslips AJAX
        generateBtn.addEventListener('click', function () {
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
                    let extraAllowances = 0.0;
                    let recreationVal = 0, conveyanceVal = 0, medicalVal = 0, houseRentVal = 0;

                    row.querySelectorAll('.in-earning').forEach(inp => {
                        const itemId = inp.dataset.itemId;
                        const val = parseFloat(inp.value || 0);
                        if (itemId && val > 0) {
                            earnings[itemId] = val;
                        }
                        if (inp.classList.contains('in-punctuality') || 
                            inp.classList.contains('in-overtime') || 
                            inp.classList.contains('in-arrear') || 
                            inp.classList.contains('in-otherallow')) {
                            extraAllowances += val;
                        }
                    });

                    // Gather itemized recreation, conveyance, medical, house rent if present
                    payslipItems.forEach(item => {
                        const lname = (item.name || '').toLowerCase();
                        const inp = row.querySelector(`.in-earning[data-item-id="${item.id}"]`);
                        if (inp) {
                            const val = parseFloat(inp.value || 0);
                            if (lname.includes('recreation')) recreationVal = val;
                            else if (lname.includes('conveyance')) conveyanceVal = val;
                            else if (lname.includes('medical')) medicalVal = val;
                            else if (lname.includes('house rent')) houseRentVal = val;
                        }
                    });

                    // Gather deductions (including advance, lunch, absent, tax, and others)
                    const deductions = {};
                    let rowDeductions = 0.0;
                    row.querySelectorAll('.in-deduct').forEach(inp => {
                        const itemId = inp.dataset.itemId;
                        const val = parseFloat(inp.value || 0);
                        if (itemId && val > 0) {
                            deductions[itemId] = val;
                        }
                        rowDeductions += val;
                    });

                    const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
                    const gross = parseFloat(row.querySelector('.cell-gross').textContent.replace(/,/g, '')) || parseFloat(emp.gross_salary) || 0;
                    const totalEarning = gross + extraAllowances;
                    const netSalary = parseFloat(row.querySelector('.cell-netpayable').textContent.replace(/,/g, '')) || (totalEarning - rowDeductions);

                    // Breakdown columns
                    const advanceSalary = parseFloat(row.querySelector('.in-advance')?.value || 0);
                    const mealCount = parseFloat(row.querySelector('.in-mealcount')?.value || 0);
                    const subsidizedLunch = parseFloat(row.querySelector('.in-sublunch')?.value || 0);
                    const absentDays = parseFloat(row.querySelector('.in-absent-days')?.value || 0);
                    const unpaidAbsentAmount = parseFloat(row.querySelector('.in-unpaid')?.value || 0);
                    const punctualityBonus = parseFloat(row.querySelector('.in-punctuality')?.value || 0);
                    const overtime = parseFloat(row.querySelector('.in-overtime')?.value || 0);
                    const arrearsEarning = parseFloat(row.querySelector('.in-arrear')?.value || 0);
                    const dutyAllowance = parseFloat(row.querySelector('.in-otherallow')?.value || 0);
                    const tax = parseFloat(row.querySelector('.in-tax')?.value || 0);
                    const bankAmount = parseFloat(row.querySelector('.in-bank')?.value || 0);
                    const cashAmount = parseFloat(row.querySelector('.in-cash')?.value || 0);
                    const salaryHold = emp.salary_hold || '';
                    const arrearsDeduction = parseFloat(emp.arrears_deduction || 0);
                    const othersDeduction = parseFloat(emp.others_deduction || 0);

                    selectedEmployees.push({
                        payslip_id: emp.saved_payslip_id || 0,
                        employee_id: emp.id,
                        employee_name: emp.full_name,
                        designation_id: emp.designation_id,
                        basic_salary: basic,
                        gross_salary: gross,
                        absent_days: absentDays,
                        late_unpaid_absent_days: absentDays,
                        unpaid_absent_amount: unpaidAbsentAmount,
                        total_earning: totalEarning,
                        total_deduction: rowDeductions,
                        net_salary: netSalary,
                        recreation_allowance: recreationVal,
                        conveyance_allowance: conveyanceVal,
                        medical_allowance: medicalVal,
                        house_rent_allowance: houseRentVal,
                        advance_salary: advanceSalary,
                        meal_count: mealCount,
                        subsidized_lunch: subsidizedLunch,
                        arrears_deduction: arrearsDeduction,
                        others_deduction: othersDeduction,
                        punctuality_bonus: punctualityBonus,
                        overtime: overtime,
                        arrears_earning: arrearsEarning,
                        duty_allowance: dutyAllowance,
                        tax: tax,
                        bank_amount: bankAmount,
                        cash_amount: cashAmount,
                        salary_hold: salaryHold,
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
                payroll_project_id: projectSelect.value,
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