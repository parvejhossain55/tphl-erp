# Auto Generate Payslip Module — Bug Audit & Improvement Report

**Date Created:** September 3, 2026  
**Status:** Under Review / Awaiting Step-by-Step Fixes  
**Location:** `app/context/auto-generate-payslip-report.md`  
**Target Route:** `/auto-generate-payslip`  
**Primary Files Audited:**

- View: `app/views/payrolls/payslips/auto-create-payslip.php`
- Controller: `app/controllers/PayrollController.php` (`showAutoGenerateForm`, `getDepartmentAttendance`, `storeBulkPayslips`)
- Models: `app/models/Payslip.php`, `app/models/AdvanceSalary.php`, `app/models/PayslipItem.php`
- Database Tables: `payslips`, `payslip_details`, `employee_salary_items`, `attendance_monthly_update`, `advance_salaries`, `payroll_projects`

---

## 1. Executive Summary

The **Auto Generate Payslip** module (`auto-create-payslip`) is designed to streamline bulk payroll processing for all active employees across various departments and payroll projects. It handles monthly gross/basic calculations, attendance logs, meal counts, subsidized lunch deductions, absent day deductions, advance salary repayments, overtime, bonuses, tax, and splits into bank/cash disbursements.

During a comprehensive code and architectural audit, several **critical bugs, calculation discrepancies, broken item mappings, and data persistence omissions** were discovered. Most notably, detailed breakdown figures calculated on the frontend are not saved into the database, leaving reports and payslips incomplete.

---

## 2. Categorized Problem Audit

### 🚨 Category A: Critical Data Persistence Bugs (Data Lost on Save)

#### 1. Breakdown Columns Not Saved in Database (`storeBulkPayslips`)

- **Location:** `PayrollController.php` (Line 773-785) & `Payslip.php` (`create`)
- **Problem:**
  - The `payslips` table contains dedicated breakdown columns:
    `recreation_allowance`, `conveyance_allowance`, `medical_allowance`, `house_rent_allowance`, `advance_salary`, `meal_count`, `subsidized_lunch`, `late_unpaid_absent_days`, `unpaid_absent_amount`, `arrears_deduction`, `others_deduction`, `punctuality_bonus`, `overtime`, `arrears_earning`, `duty_allowance`, `tax`, `bank_amount`, `cash_amount`, `salary_hold`, `remarks`, `attendance_remarks`.
  - However, `storeBulkPayslips()` only inserts summary values: `employee_id`, `payroll_project_id`, `designation_id`, `payslip_month`, `total_earning`, `total_deduction`, `net_salary`, `status`, `upload_by`, `created_by`.
- **Impact:**
  - When viewing **Payroll Workspace**, **Salary Report**, **Detailed Summary**, or exporting PDFs, all individual breakdown figures show `0.00`.
  - Cash and Bank disbursement amounts default to `0.00`.

#### 2. Subsidized Lunch & Tax Missing in `payslip_details`

- **Location:** `auto-create-payslip.php` (Line 331-342, 397, 422)
- **Problem:**
  - In `payslip_items`, there is no item for "Tax" or "Subsidized Lunch".
  - As a result, `itemMap.lunch` and `itemMap.tax` evaluate to `undefined`.
  - Inputs for lunch and tax do not receive a valid `data-item-id`.
- **Impact:**
  - Although these amounts are deducted from `net_salary`, they are completely omitted from `payslip_details` and `payslips` columns.
  - In `show-payslip.php`, the deductions section will not show Lunch or Tax items, causing the item total to mismatch the payslip total deductions.

---

### ⚠️ Category B: UI Column Duplication & Broken Item Mapping

#### 3. Earning Columns Duplicated in Table

- **Location:** `auto-create-payslip.php` (Header: Lines 110-114, 132-135 | Body: Lines 372-387, 414-417)
- **Problem:**
  - The `<thead>` dynamically loops through all earning items from `payslip_items` (which already includes Arrears, Punctuality Bonus, Overtime, Duty/Food/Car Allowance).
  - Immediately after the loop, static columns for `Punctuality Bonus`, `Overtime`, `Arrear`, and `Duty/Food/Car Allowance` are rendered again.
  - The JS `renderTableRows()` repeats this exact duplication in the table body.
- **Impact:**
  - Table shows duplicate columns for 4 earnings.
  - Multiple inputs share the same item IDs, causing values to overwrite each other upon submission.

#### 4. Broken `itemMap` & Inverted Deduction/Earning IDs

- **Location:** `auto-create-payslip.php` (Lines 331-342)
- **Problem:**
  - `name.includes('arrear')`: Matches both `Arrears (earning)` (ID 7) and `Arrears (deduction)` (ID 10). Because ID 10 is iterated later, `itemMap.arrear` becomes 10 (Deduction), but is bound to the **Earning input**.
  - `name.includes('other')`: Matches `Others Deduction` (ID 8), but is bound to the **Duty/Food/Car Allowance (Earning input)**.
  - `name.includes('lunch')`: Matches nothing (`undefined`).
  - `name.includes('tax')`: Matches nothing (`undefined`).
- **Impact:**
  - Extra earnings are mistakenly saved as deductions in `payslip_details`.

---

### 🧮 Category C: Calculation & Input Logic Flaws

#### 5. Advance Salary Hardcoded to 0 in Table

- **Location:** `auto-create-payslip.php` (Line 395) vs `PayrollController.php` (Line 704-709)
- **Problem:**
  - The backend calculates each employee's monthly advance salary deduction installment and passes it in `$emp['configured_items'][$advanceSalaryItemId]`.
  - The frontend input explicitly hardcodes `value="0"`, ignoring the backend calculated value.
- **Impact:**
  - Advance salary deductions are not applied unless the user manually types them in for each employee.

#### 6. Bank Amount Cannot Be Edited (Instant Overwrite Bug)

- **Location:** `auto-create-payslip.php` (Lines 488-495)
- **Problem:**
  - Whenever an `input` event triggers anywhere in the row, `calculateRowNetSalary()` runs.
  - If `payMethod` is 'bank', it resets `bankInput.value = (netPayable - cashVal).toFixed(2)`.
- **Impact:**
  - Users are unable to manually adjust the Bank amount. Any typed value is immediately reverted to the full net payable minus cash.

#### 7. Net Salary vs Tax vs Net Payable Logic Discrepancy

- **Location:** `auto-create-payslip.php` (Lines 471-482)
- **Problem:**
  - Tax input has the CSS class `in-deduct`, so it is already included in `totalDeduction`.
  - Net salary is calculated as `netSalary = totalEarning - totalDeduction` (Tax is already subtracted).
  - Then the code sets `let netPayable = netSalary;`.
- **Impact:**
  - Table displays identical values for `Net Salary` and `Net Payable`, despite `Tax` sitting between them (e.g., Net Salary: 30,000 | Tax: 2,000 | Net Payable: 30,000).

#### 8. Dynamic Allowances Disconnected from Total Earning

- **Location:** `auto-create-payslip.php` (Lines 458-467)
- **Problem:**
  - `totalEarning` starts with the static text value of `gross`.
  - Manual changes to House Rent, Medical, Conveyance, or Recreation inputs do not update `totalEarning` or `netSalary`.

#### 9. Hardcoded 30 Days & Missing Proration

- **Location:** `auto-create-payslip.php` (Lines 356, 420)
- **Problem:**
  - Daily salary is hardcoded to `gross / 30`.
  - Actual effective days is hardcoded to static `30`.
  - Joining dates midway through the month do not prorate the salary or effective days.

---

### ⚙️ Category D: Project & Filter Synchronization

#### 10. Missing Project & Month Synchronization

- **Location:** `auto-create-payslip.php` (Lines 18-24, 43-46)
- **Problem:**
  - The `<select id="projectSelect">` does not contain project date metadata (`data-date`).
  - Selecting a payroll project does not automatically set or sync the `Salary Month` input.
- **Impact:**
  - Risk of generating payslips with mismatched months under the wrong project.

#### 11. No Visual Indicator for Already Generated Payslips

- **Location:** `PayrollController.php` (`getDepartmentAttendance`) & `auto-create-payslip.php`
- **Problem:**
  - When loading the employee list, all employees are returned and selected, even if a payslip has already been generated for that project and month.
  - The user only learns about duplicates after clicking "Generate", when the backend reports skipped employees.

#### 12. Misleading Tipsoi Sync Message

- **Location:** `auto-create-payslip.php` (Lines 68-81)
- **Problem:**
  - Banner indicates "Tipsoi API Syncing... Fetching attendance logs and calculating absent deductions...".
  - The backend actually queries the local `attendance_monthly_update` table. If monthly attendance has not been modified/saved yet for that project, attendance values default to 0.

---

## 3. Step-by-Step Fix Roadmap

The proposed fixes should be executed in the following sequential order:

1. **Step 1: Database Columns & Backend Persistence (`storeBulkPayslips`)**
   - Update `Payslip.php` and `PayrollController::storeBulkPayslips()` to receive and save all breakdown columns (`bank_amount`, `cash_amount`, `tax`, `subsidized_lunch`, `meal_count`, `unpaid_absent_amount`, `advance_salary`, allowances, etc.).
2. **Step 2: Fix Table Columns & Eliminate Duplications (`auto-create-payslip.php`)**
   - Clean up table header and row rendering.
   - Separate core allowances (House Rent, Medical, Conveyance, Recreation) from extra earnings (Punctuality, Overtime, Arrear, Duty Allowance) so columns are never duplicated.
3. **Step 3: Fix `itemMap` & Ensure System Payslip Items Exist**
   - Fix item detection logic (distinguish between Arrear Earning vs Deduction, fix Duty Allowance ID).
   - Ensure `Subsidized Lunch` and `Tax` exist as items or are properly handled.
4. **Step 4: Fix Frontend Calculations & Bank/Cash Input Interaction**
   - Allow proper two-way editing for Bank vs Cash inputs without reverting.
   - Correct Net Salary vs Tax vs Net Payable calculation order.
   - Populate Advance Salary default value from backend `configured_items`.
5. **Step 5: Project Month Sync & Already Generated Badging**
   - Auto-fill Salary Month when a Project is selected.
   - Mark employees who already have generated payslips with an "Already Generated" badge and uncheck them by default.
