                <div class="row justify-content-center">
                    <div class="col-xxl-12 col-xl-12 col-lg-12">
                        <!-- Header -->
                        <div class="card_wrapper">
                            <div class="card__body">
                                <form action="<?= $GLOBALS['base_url'] ?>/store-payslip" method="POST" id="payslipForm">

                                <!-- new here  -->
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
                                                <input class="form-control flatpickr-input active" id="basicInput" type="text" placeholder="Select Date" readonly="readonly">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-3">
                                            <div class="form__input-title">
                                                <label for="basicInput2">Due Date</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="form__input">
                                                <input class="form-control flatpickr-input active" id="basicInput2" type="text" placeholder="Select Date" readonly="readonly">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                <!-- new here  -->






                                    <div class="form-header p-3 mb-4 border-bottom">
                                        <h4 class="card__title text-center mb-4">
                                            Payslip for the month of <span id="paydateText"></span>
                                        </h4>
                                        <div class="row align-items-center">
                                            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                                                <img src="<?= getImage('settings', getSetting('logo')); ?>" class="inv-logo mb-2" alt="Logo" style="max-width:150px;">
                                                <ul class="list-unstyled mb-0">
                                                    <li><?= getSetting('website_title') ?></li>
                                                    <li><?= getSetting('address') ?>,</li>
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
                                        </div>
                                    </div>

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













                <div class="row justify-content-center">
                    <div class="col-xxl-12 col-xl-12 col-lg-12">
                        <div class="card__wrapper">
                            <div class="row g-20 gy-20">
                                <div class="col-md-7">
                                    <div class="invoice__logo mb-20">
                                        <img class="light-logo" src="assets/images/logo/logo.svg" alt="payslip logo">
                                        <img class="dark-logo" src="assets/images/logo/logo-white.svg" alt="payslip logo">
                                    </div>
                                    <p class="mb-5">100 Terminal, Fort Lauderdale,</p>
                                    <p class="mb-5">Miami 33315, United States</p>
                                    <p class="mb-5">name@manez.com</p>
                                    <p class="mb-5">+1(800) 642 7676</p>
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
                                                <input class="form-control flatpickr-input active" id="basicInput" type="text" placeholder="Select Date" readonly="readonly">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-3">
                                            <div class="form__input-title">
                                                <label for="basicInput2">Due Date</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="form__input">
                                                <input class="form-control flatpickr-input active" id="basicInput2" type="text" placeholder="Select Date" readonly="readonly">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-line"></div>
                            <div class="row g-60 gy-20">
                                <div class="col-xl-6">
                                    <div class="mb-15">
                                        <h5 class="card__heading-title">Billing Address</h5>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="billingName">Full Name</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="billingName" placeholder="Full Name" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="billingEmail">Email</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="email" class="form-control" id="billingEmail" placeholder="Email address" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="billingAddress">Address</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <textarea class="form-control" id="billingAddress" rows="3" placeholder="Address" required=""></textarea>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="billingNumber">Mobile Number</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="billingNumber" placeholder="+1(800) 642 7676" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="billingPhone">Phone Number</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="billingPhone" placeholder="+1(800) 642 7676" required="">
                                        </div>
                                    </div>

                                    <div class="form-check d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input" id="sameAddresses" name="sameAddresses">
                                        <label class="form-check-label" for="sameAddresses">
                                            Are your billing and shipping addresses the same?
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-15">
                                        <h5 class="card__heading-title">Shipping Address</h5>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="shippinggName">Full Name</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="shippinggName" placeholder="Full Name" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="shippinggEmail">Email</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="email" class="form-control" id="shippinggEmail" placeholder="Email address" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="shippinggAddress">Address</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <textarea class="form-control" id="shippinggAddress" rows="3" placeholder="Address" required=""></textarea>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="shippinggNumber">Mobile Number</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="shippinggNumber" placeholder="+1(800) 642 7676" required="">
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-10">
                                        <div class="col-lg-4">
                                            <div class="form__input-title">
                                                <label for="shippinggPhone">Phone Number</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="shippinggPhone" placeholder="+1(800) 642 7676" required="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-line"></div>
                            <div class="row">
                                <div class="table-responsive">
                                    <table class="table table-borderless table-nowrap mb-0" id="productTableRepeater">
                                        <thead class="align-middle">
                                            <tr class="table__title bg-title">
                                                <th scope="col" class="" style="width: 30px;">#</th>
                                                <th scope="col">
                                                    Product Details
                                                </th>
                                                <th scope="col" style="width: 120px;">
                                                    Rate
                                                </th>
                                                <th scope="col" style="width: 120px;">Quantity</th>
                                                <th scope="col" class="text-end" style="width: 150px;">Amount</th>
                                                <th scope="col" class="text-end" style="width: 30px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="product">
                                                <td>01</td>
                                                <td class="text-start">
                                                    <div class="mb-10">
                                                        <input type="text" class="form-control" id="productName" placeholder="Product Name" required>
                                                    </div>
                                                    <textarea class="form-control" id="productDetails" rows="2" placeholder="Product Details"></textarea>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control product-rate" id="productRate" step="0.01" placeholder="0.00" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control product-quantity" id="productQnty" placeholder="1" min="1" max="1000">
                                                </td>
                                                <td class="text-end">
                                                    <div>
                                                        <input type="text" class="form-control total-price" id="productTotalPrice" placeholder="$0.00" readonly>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button id="productRemoval" class="product__removal-btn"><span><i
                                                                class="fa-solid fa-xmark"></i></span></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tbody>
                                            <tr>
                                                <td colspan="5">
                                                    <button id="addItemBtn" class="btn btn-primary">Add Item</button>
                                                </td>
                                            </tr>
                                            <tr class="invoice-line">
                                                <td colspan="2" class="align-middle">
                                                    <div class="form__input-title">
                                                        <label for="extraNote">Notes:</label>
                                                    </div>
                                                    <textarea class="form-control" id="extraNote" placeholder="Extra Notes..."></textarea>
                                                </td>
                                                <td colspan=""></td>
                                                <td colspan="3" class="p-0">
                                                    <table class="table table-borderless mb-0 mt-15">
                                                        <tbody>
                                                            <tr>
                                                                <td>Sub Total :</td>
                                                                <td class="text-end">$00.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Discount: <span class="text-black fw-700">(20%)</span></td>
                                                                <td class="text-end">-$00.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Vat :<span class="text-black fw-700">(7.5%)</span></td>
                                                                <td class="text-end">+$00.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Shipping Charge :</td>
                                                                <td class="text-end">$00.00</td>
                                                            </tr>
                                                            <tr class="border-top">
                                                                <th scope="row">Total :</th>
                                                                <th class="text-end">$00.00</th>
                                                            </tr>
                                                            <tr class="border-top">
                                                                <td>Rebate :</td>
                                                                <td class="text-end">-$00.00</td>
                                                            </tr>
                                                            <tr class="border-top">
                                                                <th scope="row">Payable Amount :</th>
                                                                <th class="text-end">$00.00</th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="invoice-line"></div>
                            <div class="row">
                                <div class="col-xl-6">
                                    <div class="card__info-wrapper">
                                        <div class="payment__card-tab">
                                            <ul class="nav nav-pills mb-15" id="pills-tab" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <div class="nav-link active" id="pills-bank-tab" data-bs-toggle="pill" data-bs-target="#pills-bank" role="tab" aria-controls="pills-bank" aria-selected="true">
                                                        <span class="form-check form-check-inline form-check-primary">
                                                            <input name="customRadio" class="form-check-input" type="radio" value=""
                                                                id="defaultRadio1" checked="">
                                                            <label class="form-check-label cursor" for="defaultRadio1">
                                                                Bank Account
                                                            </label>
                                                        </span>
                                                    </div>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <div class="nav-link" id="pills-paypal-tab" data-bs-toggle="pill" data-bs-target="#pills-paypal" role="tab" aria-controls="pills-paypal" aria-selected="false">
                                                        <span class="form-check form-check-inline form-check-secondary">
                                                            <input name="customRadio" class="form-check-input" type="radio" value=""
                                                                id="defaultRadio2">
                                                            <label class="form-check-label cursor" for="defaultRadio2">
                                                                PayPal
                                                            </label>
                                                        </span>
                                                    </div>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <div class="nav-link" id="pills-cod-tab" data-bs-toggle="pill" data-bs-target="#pills-cod" role="tab" aria-controls="pills-cod" aria-selected="false">
                                                        <span class="form-check form-check-inline form-check-info">
                                                            <input name="customRadio" class="form-check-input" type="radio" value=""
                                                                id="defaultRadio3">
                                                            <label class="form-check-label cursor" for="defaultRadio3">
                                                                Cash On Delivery
                                                            </label>
                                                        </span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content" id="pills-tabContent">
                                            <div class="tab-pane fade show active" id="pills-bank" role="tabpanel" aria-labelledby="pills-bank-tab">
                                                <div class="payment__card-content">
                                                    <div class="row g-20 gy-20 align-items-center align-content-end justify-content-between">
                                                        <div class="col-md-6 col-sm-6">
                                                            <div class="from__input-box">
                                                                <div class="form__input-title">
                                                                    <label for="cardname">Card Name</label>
                                                                </div>
                                                                <div class="form__input">
                                                                    <input class="form-control" name="name" id="cardname" type="text" placeholder="Card Holder Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6">
                                                            <div class="from__input-box">
                                                                <div class="form__input-title">
                                                                    <label for="creditCard">Card Number</label>
                                                                </div>
                                                                <div class="card-form-control p-relative">
                                                                    <input class="form-control" type="text" id="creditCard" name="creditCard" placeholder="XXXX XXXX XXXX XXXX">
                                                                    <div class="credit-card__logo">
                                                                        <i class="credit-card__logo" id="creditCardLogo"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6">
                                                            <div class="from__input-box">
                                                                <div class="form__input-title">
                                                                    <label class="form-check-label" for="cardmmyy">Exp.
                                                                        Date</label>
                                                                </div>
                                                                <input class="form-control" id="cardmmyy" placeholder="MM/YY" type="tel">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6">
                                                            <div class="from__input-box">
                                                                <div class="form__input-title">
                                                                    <label class="form-check-label" for="cvvcode">CVV
                                                                        Code</label>
                                                                </div>
                                                                <input class="form-control" id="cvvcode" type="text" placeholder="XXX">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="pills-paypal" role="tabpanel" aria-labelledby="pills-paypal-tab">
                                                <div class="payment__card-content mt-20 mb-20">
                                                    <div class="from__input-box">
                                                        <div class="form__input-title">
                                                            <label for="cardname2">Send Mony</label>
                                                        </div>
                                                        <div class="form__input">
                                                            <input class="form-control" name="name" id="cardname2" type="text" placeholder="Name, email or mobile number">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="pills-cod" role="tabpanel" aria-labelledby="pills-cod-tab">
                                                <div class="payment__card-content mt-20 mb-20"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-line"></div>
                            <div class="d-flex flex-wrap gap-10 justify-content-lg-end">
                                <a class="btn btn-info" href="app-invoice-preview.html"><i class="fa-sharp fa-regular fa-eye"></i> Preview</a>
                                <button type="submit" class="btn btn-success"><i class="fa-sharp fa-light fa-floppy-disk"></i> Save</button>
                                <a class="btn btn-warning" href="assets/pdf/invoice-print-version.pdf" download="invoice-print-version.pdf"><i class="fa-sharp fa-thin fa-file-arrow-down"></i> Download</a>
                                <button type="submit" class="btn btn-primary"><i class="fa-light fa-paper-plane"></i> Send</button>
                            </div>
                        </div>
                    </div>
                </div>