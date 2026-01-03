                <div class="row justify-content-center">
                    <div class="col-xxl-12 col-xl-12 col-lg-12">
                        <!-- Header -->
                        <div class="card_wrapper">
                            <div class="card__body">
                                <form action="<?= $GLOBALS['base_url'] ?>/store-payslip" method="POST" id="payslipForm">
                                     <div class="form-header p-3 mb-4 border-bottom">
                                        <h4 class="card__title text-center mb-4">
                                            Payslip for the month of <span id="paydateText"></span>
                                        </h4>
                                        
                                                             <div class="row g-20 gy-20">
                                <div class="col-md-7">
                                    <div class="invoice__logo mb-20">
                                        <img class=" d-nonelight-logo" src="<?= getImage('settings', getSetting('logo')); ?>" alt="payslip logo">
                                        <img class="dark-logo" src="assets/images/logo/logo-white.svg" alt="payslip logo">
                                    </div>
                                    <p class="mb-5"><?= getSetting('website_title') ?></p>
                                    <p class="mb-5"><?= getSetting('website_address') ?></p>
                                    <p class="mb-5"><?= getSetting('website_email') ?></p>
                                    <p class="mb-5"><?= getSetting('website_phone') ?></p>
                                </div>
                                <div class="col-md-5">
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-3">
                                            <div class="form__input-title">
                                                <label for="invoiceNumber">Invoice No.</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="form__input">
                                                <input id="invoiceNumber" type="text" class="form-control" placeholder="#MZ-00114">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-3">
                                            <div class="form__input-title">
                                                <label for="basicInput">Date</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="form__input">
                                                <input class="form-control flatpickr-input active" id="paydate" type="text" placeholder="Select Date" readonly="readonly">
                                            </div>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                                        <!-- <div class="row align-items-center">
                                            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                                                <img src="<= getImage('settings', getSetting('logo')); ?>" class="inv-logo mb-2" alt="Logo" style="max-width:150px;">
                                                <ul class="list-unstyled mb-0">
                                                    <li><= getSetting('website_title') ?></li>
                                                    <li><= getSetting('address') ?>,</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                                <h3 class="text-uppercase">Payslip #</h3>
                                                <ul class="list-unstyled">
                                                    <li>
                                                        Pay Date: <span id="paydateText2"></span>
                                                        <input type="date" id="paydate" class="form-control " name="paydate">
                                                    </li>
                                                </ul>
                                            </div>
                                        </div> -->
                                    </div>
                                <!-- new here  -->
                                <!-- new here  -->






                                   

                                    <!-- Employee Info -->
                                    <div class="row mb-4">
                                        <div class="col-sm-6">
                                            <div class="form__group">
                                                <label class="form__input-title" for="employeeSelect"><strong>Employee</strong></label>
                                                <select id="employeeSelect" class="form-select">
                                                    <option value="">-- Select Employee --</option>
                                                    <?php foreach ($employees as $emp): ?>
                                                        <option
                                                            value="<?= $emp['id'] ?>"
                                                            data-designation="<?= htmlspecialchars($emp['designation']) ?>"
                                                            data-designation-id="<?= $emp['designation_id'] ?>"
                                                            data-empid="<?= $emp['employee_id'] ?>"
                                                            data-email="<?= $emp['email'] ?>"
                                                            data-basic-salary="<?= $emp['basic_salary'] ?>">
                                                            <?= htmlspecialchars($emp['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" id="designation_id" class="form-control" readonly>
                                        <div class="col-sm-6">
                                            <div class="form__group">
                                                <label class="form__input-title"><strong>Designation</strong></label>
                                                <input type="text" id="designation" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-sm-6">
                                            <label class="form__input-title"><strong>Employee ID</strong></label>
                                            <input type="text" id="emp_id" class="form-control" readonly>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form__input-title"><strong>Email</strong></label>
                                            <input type="text" id="email" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <!-- Earnings & Deductions -->
                                    <div class="p-3 border rounded">
                                        <div class="row g-4">
                                            <!-- Earnings -->
                                            <div class="col-md-6">
                                                <h5 class="mb-3">Earnings</h5>
                                                <div class="row g-3">
                                                    <div class="col-12 mb-2">
                                                        <label class="form-label">Basic Salary</label>
                                                        <input id="basicSalary" type="number" class="form-control earning" value="0" readonly>
                                                    </div>
                                                    <?php foreach ($items as $item): ?>
                                                        <?php if ($item['type'] === 'earning'): ?>
                                                            <div class="col-12 mb-2">
                                                                <label class="form-label"><?= $item['name'] ?></label>
                                                                <input type="number"
                                                                    name="earning[<?= $item['id'] ?>]"
                                                                    class="form-control earning"
                                                                    value="0">
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <div class="col-12 mt-3">
                                                        <label class="form-label">Total Earnings</label>
                                                        <input type="number" id="totalEarningInput" class="form-control" value="0" readonly style="font-weight:700;">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Deductions -->
                                            <div class="col-md-6">
                                                <h5 class="mb-3">Deductions</h5>
                                                <div class="row g-3">
                                                    <?php foreach ($items as $item): ?>
                                                        <?php if ($item['type'] === 'deduction'): ?>
                                                            <div class="col-12 mb-2">
                                                                <label class="form-label"><?= $item['name'] ?></label>
                                                                <input type="number"
                                                                    name="deduction[<?= $item['id'] ?>]"
                                                                    class="form-control deduction"
                                                                    value="0">
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <div class="col-12 mt-3">
                                                        <label class="form-label">Total Deductions</label>
                                                        <input type="number" id="totalDeductionInput" class="form-control" value="0" readonly style="font-weight:700;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Net Salary -->
                                    <div class="net-salary-box">
                                        <div class="amount">
                                            <strong>Total Earning:</strong> <span id="totalEarningText">0</span><br>
                                            <strong>Total Deduction:</strong> <span id="totalDeductionText">0</span><br>
                                            <strong>Net Salary:</strong> <span id="netSalaryText">0</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="payslip_payload" id="payslipPayload">

                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary">Save Payslip</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    const STORAGE_KEY = 'payslip_data';

                    // Save localStorage
                    function collectPayslipData() {
                        const employeeSelect = document.getElementById('employeeSelect');
                        if (!employeeSelect.value) return;

                        const selected = employeeSelect.selectedOptions[0];

                        const earnings = {};
                        const deductions = {};

                        document.querySelectorAll('.earning[name]').forEach(input => {
                            const id = input.name.match(/\d+/)?.[0];
                            if (id) earnings[id] = parseFloat(input.value || 0);
                        });

                        document.querySelectorAll('.deduction[name]').forEach(input => {
                            const id = input.name.match(/\d+/)?.[0];
                            if (id) deductions[id] = parseFloat(input.value || 0);
                        });

                        const paydate = document.getElementById('paydate').value;

                        const data = {
                            employee_id: employeeSelect.value,
                            designation: selected.dataset.designation || null,
                            designation_id: selected.dataset.designationId || null,
                            basic_salary: document.getElementById('basicSalary').value,
                            total_earning: document.getElementById('totalEarningInput').value,
                            total_deduction: document.getElementById('totalDeductionInput').value,
                            net_salary: (parseFloat(document.getElementById('totalEarningInput').value) - parseFloat(document.getElementById('totalDeductionInput').value)).toFixed(2),
                            earnings,
                            deductions,
                            paydate
                        };

                        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                        document.getElementById('payslipPayload').value = JSON.stringify(data);

                        // Update display month
                        if (paydate) {
                            const d = new Date(paydate);
                            document.getElementById('paydateText').textContent = d.toLocaleDateString('en-US', {
                                month: 'long',
                                year: 'numeric',
                                day: 'numeric'
                            });
                        }
                        if (paydate) {
                            const d = new Date(paydate);
                            document.getElementById('paydateText2').textContent = d.toLocaleDateString('en-US', {
                                month: 'long',
                                year: 'numeric',
                                day: 'numeric'
                            });
                        }
                    }

                    // Restore localStorage
                    function restorePayslipData() {
                        const saved = localStorage.getItem(STORAGE_KEY);
                        if (!saved) return;

                        const data = JSON.parse(saved);

                        document.getElementById('employeeSelect').value = data.employee_id;
                        document.getElementById('employeeSelect').dispatchEvent(new Event('change'));

                        document.getElementById('basicSalary').value = data.basic_salary || 0;
                        document.getElementById('paydate').value = data.paydate || new Date().toISOString().slice(0, 10);

                        Object.entries(data.earnings || {}).forEach(([id, amount]) => {
                            const input = document.querySelector(`input[name="earning[${id}]"]`);
                            if (input) input.value = amount;
                        });

                        Object.entries(data.deductions || {}).forEach(([id, amount]) => {
                            const input = document.querySelector(`input[name="deduction[${id}]"]`);
                            if (input) input.value = amount;
                        });

                        calculateNetSalary();
                    }

                    // Calculate totals
                    function calculateNetSalary() {
                        let totalEarning = 0;
                        let totalDeduction = 0;

                        document.querySelectorAll('.earning').forEach(input => totalEarning += Number(input.value || 0));
                        document.querySelectorAll('.deduction').forEach(input => totalDeduction += Number(input.value || 0));

                        document.getElementById('totalEarningInput').value = totalEarning.toFixed(2);
                        document.getElementById('totalDeductionInput').value = totalDeduction.toFixed(2);
                        document.getElementById('totalEarningText').textContent = totalEarning.toFixed(2);
                        document.getElementById('totalDeductionText').textContent = totalDeduction.toFixed(2);
                        document.getElementById('netSalaryText').textContent = (totalEarning - totalDeduction).toFixed(2);

                        collectPayslipData();
                    }

                    // Event listeners
                    document.querySelectorAll('.earning, .deduction').forEach(input => {
                        input.addEventListener('input', calculateNetSalary);
                    });

                    document.getElementById('employeeSelect').addEventListener('change', () => {
                        const selected = document.getElementById('employeeSelect').selectedOptions[0];
                        document.getElementById('designation').value = selected.dataset.designation || '';
                        document.getElementById('emp_id').value = selected.dataset.empid || '';
                        document.getElementById('email').value = selected.dataset.email || '';
                        document.getElementById('basicSalary').value = selected.dataset.basicSalary || 0;

                        calculateNetSalary();
                    });

                    document.getElementById('paydate').addEventListener('change', collectPayslipData);

                    document.getElementById('payslipForm').addEventListener('submit', e => {
                        const data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
                        document.getElementById('payslipPayload').value = JSON.stringify(data);
                    });

                    // Init
                    document.addEventListener('DOMContentLoaded', restorePayslipData);
                </script>












