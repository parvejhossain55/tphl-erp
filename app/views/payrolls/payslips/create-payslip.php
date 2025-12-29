<style>
    .form-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .form-header {
        padding-bottom: 20px;
        border-bottom: 2px solid #66ea6dff;
        margin-bottom: 40px;
    }

    .form-header .inv-logo {
        display: block;
        max-width: 100%;
        height: auto;
    }

    @media (max-width: 768px) {
        .form-header .text-md-end {
            text-align: left !important;
        }
    }


    .form-header h2 {
        color: #247539;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 14px;
    }

    .section-title {
        background: linear-gradient(to right, #247539, #4ce3c9);
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        margin: 30px 0 20px 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-control:read-only {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .net-salary-box {
        background: linear-gradient(to right, #247539, #4ce3c9);
        color: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        margin: 30px 0;
    }

    .net-salary-box h4 {
        margin: 0;
        font-weight: 300;
        font-size: 16px;
        margin-bottom: 10px;
    }

    .net-salary-box .amount {
        font-size: 42px;
        font-weight: 700;
    }

    .btn-create {
        background: linear-gradient(to right, #247539, #4ce3c9);
        border: none;
        padding: 15px 50px;
        font-size: 18px;
        font-weight: 600;
        border-radius: 50px;
        color: white;
        transition: all 0.3s;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }

    .btn-create:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
    }

    .input-group-text {
        background: #667eea;
        color: white;
        border: none;
        border-radius: 10px 0 0 10px;
    }

    .earning-item,
    .deduction-item {
        position: relative;
        margin-bottom: 20px;
    }

    .badge-earning {
        background: #28a745;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 11px;
        margin-left: 8px;
    }

    .badge-deduction {
        background: #dc3545;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 11px;
        margin-left: 8px;
    }
</style>
<div class="app__slide-wrapper">
    <!-- Header -->
    <div class="card_wrapper">
        <div class="card__body">
            <div class="form-header p-3 mb-4 border-bottom">
                <h4 class="card__title text-center mb-4">Payslip for the month of <span id="paydate">December 2025</span></h4>

                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <img src="<?= getImage('settings', getSetting('logo')); ?>" class="inv-logo mb-2" alt="Logo" style="max-width:150px;">
                        <ul class="list-unstyled mb-0">
                            <li><?= getSetting('website_title') ?></li>
                            <li><?= getSetting('address') ?>,</li>
                        </ul>
                    </div>

                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <h3 class="text-uppercase">Payslip #202500001</h3>
                        <ul class="list-unstyled">
                            <li>Pay Date: <span id="paydate">Dec 27, 2025</span></li>
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
                            <option value="1" data-designation="Software Engineer" data-empid="EMP-001" data-joining="01-Jan-2023">John Doe</option>
                            <option value="2" data-designation="Project Manager" data-empid="EMP-002" data-joining="15-Feb-2022">Jane Smith</option>
                            <option value="3" data-designation="QA Engineer" data-empid="EMP-003" data-joining="10-Mar-2021">Robert Brown</option>
                        </select>
                    </div>
                </div>
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
                    <label class="form__input-title"><strong>Joining Date</strong></label>
                    <input type="text" id="joining_date" class="form-control" readonly>
                </div>
            </div>

            <!-- Earnings & Deductions -->
            <div class="p-3 border rounded">
                <div class="row g-4">
                    <!-- Basic Salary (Earning) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Basic Salary</label>
                        <input type="number" id="basicSalary" class="form-control earning" value="0" readonly>
                    </div>
                    <!-- Provident Fund (Deduction) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Provident Fund (PF)</label>
                        <input type="number" class="form-control deduction" value="2000">
                    </div>

                    <!-- Mobile Allowance (Earning) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Mobile Allowance (MA)</label>
                        <input type="number" class="form-control earning" value="1500">
                    </div>
                    <!-- Personal Loan (Deduction) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Personal Loan (PL)</label>
                        <input type="number" class="form-control deduction" value="0">
                    </div>

                    <!-- Bonus (Earning) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Bonus</label>
                        <input type="number" class="form-control earning" value="0">
                    </div>
                    <!-- Early Leaving (Deduction) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Early Leaving (EL)</label>
                        <input type="number" class="form-control deduction" value="0">
                    </div>

                    <!-- Others (Earning) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Others (Earning)</label>
                        <input type="number" class="form-control earning" value="0">
                    </div>
                    <!-- Advance Payment (Deduction) -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Advance Payment</label>
                        <input type="number" class="form-control deduction" value="0">
                    </div>

                    <!-- Total Earnings -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Total Earnings</label>
                        <input type="number" id="totalEarnings" class="form-control" value="0" readonly style="font-weight: 700;">
                    </div>
                    <!-- Total Deductions -->
                    <div class="col-md-6">
                        <label class="form__input-title" class="form-label">Total Deductions</label>
                        <input type="number" id="totalDeductions" class="form-control" value="0" readonly style="font-weight: 700;">
                    </div>
                </div>
            </div>

            <!-- Net Salary -->
            <div class="net-salary-box">
                <h4>NET SALARY (TAKE HOME)</h4>
                <div class="amount">
                    <span id="netSalary">0</span> <small style="font-size: 24px;">BDT</small>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    // Auto-fill designation and basic salary when employee is selected
    document.getElementById('employeeSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const designation = selectedOption.getAttribute('data-designation');
        const basicSalary = selectedOption.getAttribute('data-basic');

        document.getElementById('designation').value = designation || '';
        document.getElementById('basicSalary').value = basicSalary || 0;

        calculateSalary();
    });

    // Calculate salary on any input change
    const earningInputs = document.querySelectorAll('.earning');
    const deductionInputs = document.querySelectorAll('.deduction');

    earningInputs.forEach(input => {
        input.addEventListener('input', calculateSalary);
    });

    deductionInputs.forEach(input => {
        input.addEventListener('input', calculateSalary);
    });

    function calculateSalary() {
        let totalEarnings = 0;
        let totalDeductions = 0;

        // Calculate total earnings
        earningInputs.forEach(input => {
            totalEarnings += parseFloat(input.value) || 0;
        });

        // Calculate total deductions
        deductionInputs.forEach(input => {
            totalDeductions += parseFloat(input.value) || 0;
        });

        // Update totals
        document.getElementById('totalEarnings').value = totalEarnings.toFixed(2);
        document.getElementById('totalDeductions').value = totalDeductions.toFixed(2);

        // Calculate net salary
        const netSalary = totalEarnings - totalDeductions;
        document.getElementById('netSalary').textContent = netSalary.toLocaleString('en-BD', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Form submission
    document.getElementById('payslipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Payslip created successfully! ✓');
    });

    // Initial calculation
    calculateSalary();
</script>
</body>

</html>