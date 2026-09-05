# Payslip List Module — Bug Audit & Improvement Report

**Date Created:** September 3, 2026  
**Status:** Under Review / Awaiting Step-by-Step Fixes  
**Location:** `app/context/payslip-list-report.md`  
**Target Route:** `/payslip-lists`  
**Primary Files Audited:**

- View: `app/views/payrolls/payslips/payslip-lists.php`
- Controller: `app/controllers/PayrollController.php` (`index`, `updatePay`, `disburseBatch`, `delete`, `bulkDelete`)
- Models: `app/models/Payslip.php`, `app/models/ApprovalRequest.php`
- Database Tables: `payslips`, `journals`, `journal_details`, `chart_of_accounts`, `company_bank_accounts`, `payroll_projects`, `approval_requests`

---

## 1. Executive Summary

The **Payslip List** module (`/payslip-lists`) serves as the central control panel for managing employee payslips. It provides monthly/yearly filters, summary metric cards, accounts batch disbursement with automatic journal posting, single payment modals, draft certificate management, and Tipsoi/LWP synchronization.

A thorough technical audit of the codebase revealed several **critical system-crashing bugs, accounting discrepancies, non-functional filters, and UI/UX misalignments**. Most notably, batch disbursement and bulk delete both crash due to invalid SQL syntax and a missing controller method, while individual payments bypass accounting ledgers completely.

---

## 2. Categorized Problem Audit

### 🚨 Category A: Fatal Execution & Crash Errors (System Failures)

#### 1. SQL Syntax Error & Parameter Mismatch in `disburseBatch`

- **Location:** `PayrollController.php` (Lines 1014–1025)
- **Problem:**
  The `UPDATE payslips` query accidentally combines two statements without proper SQL syntax and has 7 `?` placeholders, but `bind_param` only supplies 4 variables:
  ```sql
  UPDATE payslips
  SET status = 'paid', paid_via_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_id = ?
      bank_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_voucher_id = ?
  WHERE id = ?
  ```
  ```php
  $stmtUpd->bind_param("iiii", $bankAccountId, $userId, $journalId, $p['id']);
  ```
- **Impact:**
  Clicking **"Disburse Selected & Post Journal"** fails immediately with an SQL Syntax error or unhandled database exception. Batch disbursement is completely broken.

#### 2. Missing Controller Method `bulkDelete`

- **Location:** `PayrollController.php` & `app/routes/hrm.php` (Line 211)
- **Problem:**
  The route `'bulk-delete-payslips'` maps to `PayrollController::bulkDelete`, and the view provides a "Bulk Delete" button calling `bulkDeleteSelected()`. However, `PayrollController.php` **does not define `bulkDelete()` at all**.
- **Impact:**
  Clicking "Bulk Delete" results in a `500 Internal Server Error` or a fatal crash ("Call to undefined method PayrollController::bulkDelete").

---

### 💳 Category B: Accounting & Transaction Omissions

#### 3. Single Payment (`pay-payslip`) Omits Journal Voucher Posting

- **Location:** `PayrollController.php` (`updatePay`, Lines 234–261)
- **Problem:**
  When paying an individual payslip via the modal "Payment" button, `updatePay()` only updates `payslips.status = 'paid'`. Unlike `disburseBatch()`, it **never creates a journal entry** in `journals` and `journal_details`.
- **Impact:**
  Salaries paid individually do not deduct funds from bank/cash accounts in the Chart of Accounts, leaving General Journal and Ledger balances severely mismatched.

#### 4. Paid Payslips Can Be Deleted Without Reversal

- **Location:** `payslip-lists.php` (Line 420) & `PayrollController.php` (`delete`)
- **Problem:**
  The Delete button (`delete-payslip/{id}`) is visible and active for all payslips, including those marked `paid`.
  When a paid payslip is deleted, its corresponding journal entry in `journals` remains orphaned.
- **Impact:**
  Loss of audit trail and unlinked journal entries in accounting records.

#### 5. Management Approval Not Synchronized to `payslips.status`

- **Location:** `ApprovalRequestController.php` & `Payslip.php`
- **Problem:**
  When management approves a salary request, only `approval_requests.status` is set to `'approved'`. The parent record in `payslips.status` remains `'pending'`.
- **Impact:**
  The status badge on `/payslip-lists` almost never displays `Approved`, remaining stuck on `Pending`.

---

### 🔍 Category C: Filter & Data Querying Bugs

#### 6. Payroll Project Filter Ineffective (Empty Dropdown)

- **Location:** `payslip-lists.php` (Lines 96–114) vs `PayrollController.php` (`index`)
- **Problem:**
  - `payslip-lists.php` iterates over `$payroll_projects` to build the dropdown options.
  - `PayrollController::index()` **never queries or passes `$payroll_projects`** to the view.
  - Furthermore, `index()` ignores `$_GET['project_id']` and does not pass it into `getAllWithEmployeeFiltered()`.
- **Impact:**
  Users cannot filter payslips by Payroll Project; the dropdown is always blank except for "All Projects".

#### 7. "Unpaid" Filter Includes Employees Without Payslips (`p.id IS NULL`)

- **Location:** `Payslip.php` (`getAllWithEmployeeFiltered`, Lines 296–340)
- **Problem:**
  When `$status === 'unpaid'`, the query performs a `LEFT JOIN` on employees where `(p.id IS NOT NULL AND p.status != 'paid') OR (p.id IS NULL)`.
  For employees with no payslip generated yet, `payslip['id']` is `null`.
- **Impact:**
  Checkbox `value` renders as `""`. If the user selects all and attempts batch disbursement or bulk deletion, empty string IDs are submitted to the API, causing unexpected errors.

---

### 📊 Category D: Metric & Card Calculation Discrepancies

#### 8. Card 5 (Total Unpaid) Count vs Amount Discrepancy

- **Location:** `PayrollController.php` (Lines 33–37) & `payslip-lists.php` (Lines 223–242)
- **Problem:**
  - Card 5 calculates the pending employee count as `$totalEmployees - $paidCount` (e.g., 100 active staff - 10 paid = 90 pending).
  - However, the amount displayed (`$unpaidSalarySum`) is calculated by querying existing rows in `payslips WHERE status IN ('pending', 'approved')`. If only 5 payslips have been generated, it sums only those 5 (e.g., ৳150,000).
- **Impact:**
  Card 5 displays "90 Pending Slips | 150,000.00 TK", which is misleading because the 90 staff count includes employees whose payslips haven't been generated yet.

#### 9. Duplicate Card (Card 4 vs Card 6)

- **Location:** `payslip-lists.php` (Card 4: Lines 204–221 | Card 6: Lines 244–261)
- **Problem:**
  - Card 4 (Total Paid): Shows Paid Slips count and Paid Amount in TK.
  - Card 6 (Total Paid Amount): Shows the exact same Paid Amount in TK with identical filter links.
- **Impact:**
  Redundant metric taking up dashboard space without providing new insight.

---

### 🎨 Category E: UI, Performance & Display Deficiencies

#### 10. Table Column "Gross Salary" Displays "Net Salary" Data

- **Location:** `payslip-lists.php` (Header: Line 317 | Cell: Line 351, Draft Header: Line 453 | Draft Cell: Line 475)
- **Problem:**
  The table header says `<th>Gross Salary (Monthly)</th>`, but the table cell outputs:
  ```php
  <td class="table__salary"><?= $payslip['net_salary'] ?></td>
  ```
- **Impact:**
  Users see the Net Salary under the Gross Salary column, creating confusion during review.

#### 11. Developer Debug Banner Visible on Production

- **Location:** `payslip-lists.php` (Line 275 & Lines 857–869)
- **Problem:**
  On page load, JavaScript populates `#lwpDebugBanner` with internal debug information and removes the `d-none` class:
  `LWP Debug: [Month: 2026-09] [API Recs: ...] [LWP Approved: ...] [LWP Groups: ...] [Table Rows: ...]`
- **Impact:**
  Raw developer debug text is exposed to end users and clients on live pages.

#### 12. Heavy Tipsoi External API Calls on Every Page Load

- **Location:** `payslip-lists.php` (Lines 710–850)
- **Problem:**
  Every time `/payslip-lists` loads, two external API requests are made (`/api/hrm/external-employees` and `/api/hrm/leave-history`).
- **Impact:**
  If Tipsoi API is slow or temporarily unreachable, spinners in the "LWP Days" column freeze, and page rendering lags significantly.

#### 13. Missing DataTables Pagination & Instant Search

- **Location:** `payslip-lists.php` (`#payslipListTable`)
- **Problem:**
  The main table is rendered as a plain HTML table without DataTables initialization.
- **Impact:**
  When viewing hundreds of payslips, the page becomes excessively long without instant column searching, sorting, or pagination.

---

## 3. Step-by-Step Fix Roadmap

1. **Step 1: Fix Fatal Errors (`disburseBatch` & `bulkDelete`)**
   - Clean up SQL syntax in `PayrollController::disburseBatch` and align parameter count.
   - Implement `PayrollController::bulkDelete()` method with proper transaction handling and response formatting.
2. **Step 2: Accounting Integrity for Single Payments**
   - Refactor `PayrollController::updatePay()` to generate and post General Journal entries identically to batch disbursement.
   - Add delete guards to prevent deleting paid payslips without proper reversal.
3. **Step 3: Fix Project & Unpaid Filters**
   - Query and pass `$payroll_projects` to `payslip-lists.php`.
   - Update `getAllWithEmployeeFiltered()` to cleanly separate generated payslips from un-generated employee records.
4. **Step 4: Metric Card & Mislabel Corrections**
   - Fix Card 5 formula to use actual pending payslip counts (`Payslip::getPendingCount()`).
   - Remove or repurpose duplicate Card 6.
   - Fix Gross Salary vs Net Salary column headers and cells.
5. **Step 5: Production Cleanup & Performance Optimization**
   - Remove `#lwpDebugBanner` from UI.
   - Add graceful caching or fallback for Tipsoi external API calls.
   - Initialize DataTables on `#payslipListTable` for clean pagination and live search.
