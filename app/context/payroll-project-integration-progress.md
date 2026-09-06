# 📊 Payroll Project-Centric Architecture — Master Progress Tracker

**Document Location:** `app/context/payroll-project-integration-progress.md`  
**Created Date:** September 3, 2026  
**Last Updated:** September 3, 2026  
**Status:** In Progress (Step-by-Step Resolution)

---

## 🎯 Core Architectural Vision

> **"Payroll Workspace is the Center Point."**
>
> 1. HR creates a **Payroll Project** (with name, month/custom date range).
> 2. Employee attendance, allowances, deductions, adjustments, and holds are reviewed and saved into that project via **Payroll Workspace**.
> 3. **ALL** downstream reports, generation pipelines, PDF exports, and payslip lists must strictly filter and display data **Project-wise (`payroll_project_id`)**.

---

## 📋 Master Implementation Checklist

| #       | Module / Feature                           | Route                        | Project-Wise Status |                                                     Current State                                                     | Priority |
| ------- | ------------------------------------------ | ---------------------------- | :-----------------: | :-------------------------------------------------------------------------------------------------------------------: | :------: |
| **0**   | **Email System in Payslip Lists**          | `/payslip-lists`             |    ✅ COMPLETED     |                                    Single & bulk email with PDF attachment working                                    |   Done   |
| **0.5** | **Payroll Workspace Calculations & Align** | `/payroll-workspace`         |    ✅ COMPLETED     |                     Allowances dynamic, Meal Rate added, No negative bank, payslip_details synced                     |   Done   |
| **1**   | **Payslip List Project Filter**            | `/payslip-lists`             |    ✅ COMPLETED     |                    Project filter dropdown populated, auto-sync date, summary cards & query synced                    |   Done   |
| **2**   | **Salary Hold Management**                 | `/payroll-salary-hold`       |    ✅ COMPLETED     |          Consolidated routes in `hrm.php`, date auto-sync, held amount column with totals, PDF & CSV export           |   Done   |
| **3**   | **Resign List**                            | `/payroll-resign-list`       |    ✅ COMPLETED     |                 Project dropdown added, auto-sync date, project-scoped filtering, PDF export updated                  |   Done   |
| **4**   | **Joining List Date Sync**                 | `/payroll-joining-list`      |    ✅ COMPLETED     |  Project dropdown date auto-sync (monthly & custom), project workforce matching, and PDF export with project header   |   Done   |
| **5**   | **Workspace vs Auto Generate Alignment**   | `/auto-create-payslip`       |    ✅ COMPLETED     | Workspace saves draft (28 cols), Auto Generate preloads project data (omits deselected), Generate promotes to pending |   Done   |
| **6**   | **Attendance Report PDF & Sync**           | `/payroll-attendance-report` |    ✅ COMPLETED     |           Project sync, DB overrides (approved lates, deductions, etc.) in PDF, Tipsoi fallback resilience            |   Done   |
| **7**   | **Payroll Summary Report**                 | `/payroll-summary-report`    |    ✅ COMPLETED     |              Chronological project sort & slice, draft/exclusion filter, tax from p.tax, split bank/cash              |   Done   |
| **8**   | **Detailed Summary**                       | `/payroll-detailed-summary`  |    ✅ COMPLETED     |              Excluded drafts & deselected, project date in header, tfoot grand totals, inline PDF export              | Complete |
| **9**   | **Salary Report**                          | `/payroll-salary-report`     |    ✅ COMPLETED     |           Department & Office clean queries, split bank/cash support, tfoot grand totals, inline PDF & CSV            | Complete |

---

## 🔍 Detailed Module Status & Roadmap

---

### Step 1: Payslip List (`/payslip-lists`)

- **Current Issue:**
  - `<select name="project_id">` dropdown is empty because `PayrollController::index()` never passes `$payroll_projects`.
  - Controller never reads `$_GET['project_id']` or passes it to `getAllWithEmployeeFiltered()`.
- **Target Solution:**
  1. Fetch and pass `$payroll_projects` in `PayrollController::index()`.
  2. Read `$_GET['project_id']` and pass to `getAllWithEmployeeFiltered($year, $month, $status, $projectId)`.
  3. Keep the selected project selected in dropdown on page reload.
  4. Ensure summary cards and batch actions respect the selected project.
- **Status:** ✅ Completed.

---

### Step 2: Salary Hold (`/payroll-salary-hold`)

- **Current Issue:**
  - `routes/hrm.php` defined `'payroll-salary-hold'` twice (lines 305 and 335).
  - `exportSalaryHoldPDF()` checked broken `p.status = 'on_hold'` instead of `p.salary_hold` and omitted project-level filtering.
  - View had no Export buttons and lacked held amount column.
- **Target Solution:**
  1. Removed redundant routes in `hrm.php` and unified on modern `salaryHoldPage()`, `salaryHoldSave()`, `salaryHoldRemove()`, and `exportSalaryHoldPDF()`.
  2. Updated `salaryHoldPage()` to auto-sync year & month from selected project date, fetch `gross_salary`/`net_salary`, and use parameterized queries on `LOWER(TRIM(p.salary_hold)) = 'hold'`.
  3. Updated `exportSalaryHoldPDF()` to filter by `payroll_project_id`, display project header information, employee department/designation, and print held amounts with grand total.
  4. Enhanced `salary_hold.php` view with project date auto-sync, Export PDF and Export CSV buttons, Held Amount column, and summary total footer.
- **Status:** ✅ Completed.

---

### Step 3: Resign List (`/payroll-resign-list`)

- **Current Issue:**
  - Zero project support. No `$payroll_projects` in controller, no dropdown in view, no `payroll_project_id` in SQL or PDF export.
- **Target Solution:**
  1. Passed `$payroll_projects` from `resignList()` and populated project selector in `resign-list.php`.
  2. Added JavaScript auto-sync to populate month and year whenever a project is selected.
  3. Scoped resigned employees to the selected project's workforce (via `payslips` / `attendance_monthly_update`).
  4. Updated `exportResignListPDF()` to accept `payroll_project_id`, display project name in header, apply project filter, and output styled TCPDF report.
- **Status:** ✅ Completed.

---

### Step 4: Joining List (`/payroll-joining-list`)

- **Current Issue:**
  - Has project dropdown and checks `payslips.payroll_project_id`, BUT default dates remain stuck on current month instead of taking the project's actual month/date range.
- **Target Solution:**
  1. When project is selected, automatically synchronize `$fromDate` and `$toDate` from `payroll_projects.start_date` / `end_date` / `date` (supports both monthly and custom date projects).
  2. Workforce matching checks both `payslips` and `attendance_monthly_update`, safely displaying newly joined employees if the project is new without erroring.
  3. Update `exportJoiningListPDF()` to maintain the same project synchronization and display project name in PDF header.
- **Status:** ✅ Completed.

---

### Step 5: Auto Generate Payslip vs Workspace Alignment (`/auto-create-payslip`)

- **Current Issue:**
  - Workspace saved 28-column draft records into `payslips` under `payroll_project_id`.
  - Auto Generate operated as a parallel disconnected island that calculated its own numbers, skipped existing records with `if ($exists) continue`, and discarded breakdown columns.
  - Deselected employees in workspace (`is_excluded = 1`) were not filtered out.
- **Target Solution:**
  1. Updated `getDepartmentAttendance()` to require saved workspace data, filter by `payroll_project_id` and `is_excluded = 0`, omitting deselected employees.
  2. Updated `auto-create-payslip.php` to preload all saved workspace breakdown numbers (advance, lunch, absent days/amount, punctuality, overtime, arrear, duty allowance, tax, bank, cash) into the input fields instead of resetting to 0.
  3. Updated `auto-create-payslip.php` submit payload to send all 28 breakdown columns.
  4. Refactored `PayrollController::storeBulkPayslips()` to promote existing draft payslips to `pending`, updating all 28 breakdown columns, syncing `payslip_details`, tracking advance salary deductions, and submitting `approval_requests`.
  5. Updated `Payslip` model to strictly hide `draft` records and `is_excluded = 1` from `/payslip-lists` and summary cards until officially submitted.
- **Status:** ✅ Completed.

---

### Step 6: Attendance Report (`/payroll-attendance-report`)

- **Current State:**
  - Project dropdown works and saves to `attendance_monthly_update`.
  - Updated `exportAttendancePDF()` to accept `payroll_project_id`, resolve project dates (monthly & custom), format PDF header with project name, apply saved `attendance_monthly_update` overrides (reflecting actual approved lates, deductions, etc.), and provide graceful local database fallback if Tipsoi API is offline.
  - Updated view form `#exportPdfForm` to pass `payroll_project_id` and supported `selected_project_id` on load.
- **Status:** ✅ Completed.

---

### Step 7: Payroll Summary Report (`/payroll-summary-report`)

- **Current Issue:**
  - Tax column displays ৳0 because it queries `payslip_details` instead of `payslips.tax`.
  - Compares project IDs with `>=` and `<=` instead of chronological project dates, breaking when IDs are non-contiguous.
  - Double-counts draft records from workspace.
- **Target Solution:**
  1. Sorted `payroll_projects` chronologically and used array position (index) slicing to capture all projects in range.
  2. Fixed SQL query to use `WHERE p.payroll_project_id IN ($inClause)` with `p.status != 'draft'` and `is_excluded = 0`.
  3. Pulled canonical tax from `COALESCE(p.tax, 0)`.
  4. Handled split payments via `COALESCE(p.bank_amount, 0)` and `COALESCE(p.cash_amount, 0)`.
  5. Updated PDF export with project range in header and inline view mode (`'I'`).
- **Status:** ✅ Completed.

---

### Step 8: Detailed Summary (`/payroll-detailed-summary`)

- **Current Issue:**
  - Double-counted draft records from workspace (e.g. 393 records vs 190 in Project 6).
  - Included deselected/excluded workforce (`is_excluded = 1`).
  - Lacked project date in PDF header and forced download (`'D'`) instead of browser inline preview (`'I'`).
  - Web table lacked grand total footer.
- **Target Solution:**
  1. Filtered `p.status != 'draft' AND (p.is_excluded = 0 OR p.is_excluded IS NULL)`.
  2. Displayed project date in PDF header alongside project name.
  3. Switched PDF output to inline view mode (`'I'`).
  4. Added `<tfoot>` with grand total employee count and net payable amount.
- **Status:** ✅ Completed.

---

### Step 9: Salary Report (`/payroll-salary-report`)

- **Current Issue:**
  - Drafts and excluded records were included across department and office reports.
  - Office-wise bank/cash was calculated with naive string matching (`payment_method LIKE '%bank%'`), ignoring split payments (`bank_amount` & `cash_amount`).
  - Lacked grand total summary footers on web tables.
  - Forced PDF download (`'D'`) without opening preview in new tab.
- **Target Solution:**
  1. Filtered `p.status != 'draft' AND (p.is_excluded = 0 OR p.is_excluded IS NULL)` across Department and Office queries (Web, PDF, and CSV).
  2. Implemented accurate split salary aggregation for `bank_amount` and `cash_amount`, matching Master Summary Report (Step 7) to 2 decimal places.
  3. Added grand total footers (`<tfoot>`) for both Department-wise and Office-wise tables.
  4. Switched PDF export to inline mode (`'I'`) with `target="_blank"`.
- **Status:** ✅ Completed.

---

## 📝 Work History Log

| Date           | Completed Task                                                          | Details                                                                                                                                                                                                                                                                                                                                                               |
| -------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **2026-09-03** | **Module Audit Reports**                                                | Created comprehensive audit reports in `app/context/`: `auto-generate-payslip-report.md` and `payslip-list-report.md`.                                                                                                                                                                                                                                                |
| **2026-09-03** | **Payslip Email System**                                                | Built complete single & bulk email system in `/payslip-lists` with TCPDF in-memory PDF generation, PHPMailer SMTP, SweetAlert2 UI, and DB tracking columns.                                                                                                                                                                                                           |
| **2026-09-03** | **Bug Fixes in PayrollController**                                      | Fixed fatal SQL syntax error in `disburseBatch()` and implemented missing `bulkDelete()` method.                                                                                                                                                                                                                                                                      |
| **2026-09-03** | **Project-Centric Architecture Plan**                                   | Analyzed all 10 payroll modules and created this master progress tracking document.                                                                                                                                                                                                                                                                                   |
| **2026-09-03** | **Payroll Workspace Calculations & Sync**                               | Replaced allowance input boxes with auto-calculated cells (50/30/10/10), added Meal Rate column (defaults to profile rate or 0.00), prevented negative bank/cash amounts, preserved Amount & ID Check columns, and synced full itemized earnings/deductions into `payslip_details`.                                                                                   |
| **2026-09-03** | **Absent Calculation & Editable Effective Days**                        | Fixed 0.00 issue in `Late/Unpaid/Absent (Amount)` by computing `$unpaidAbsentsDays * $daily` on render & page load, made `Actual Effective Days` editable (`.in-effective-days`), dynamically linked daily rate (`$gross / effectiveDays`) to effective days, and removed number input spinner arrows.                                                                |
| **2026-09-05** | **Step 1: Payslip List Project Filter**                                 | Fully implemented project-centric filtering in `/payslip-lists`. Populated project dropdown from `payroll_projects`, auto-synced year/month with project dates, updated `Payslip` model summary metrics & list query with parameterized project binding, and updated summary card links to preserve project selection.                                                |
| **2026-09-05** | **Step 5: Workspace vs Auto Generate Alignment**                        | Unified the 3-stage flow: Payroll Workspace saves `draft` with 28 columns and exclusion flags -> Auto Generate preloads project data and omits deselected employees -> Generate & Submit promotes to `pending` persisting all 28 breakdown columns and approval requests -> Payslip Lists displays only submitted records.                                            |
| **2026-09-05** | **Step 2: Salary Hold Management (`/payroll-salary-hold`)**             | Consolidated duplicate routes in `hrm.php` to `salaryHoldPage()`, implemented project selection with automatic year & month date synchronization, added Export PDF and Export CSV buttons, displayed held net salary column with total footer, and updated `exportSalaryHoldPDF()` to query by `payroll_project_id` and `salary_hold = 'Hold'`.                       |
| **2026-09-05** | **Step 3: Resign List (`/payroll-resign-list`)**                        | Added Payroll Project filter dropdown, implemented date auto-synchronization on project change, scoped resigned employees to selected project's workforce (via `payslips` / `attendance_monthly_update`), and upgraded `exportResignListPDF()` with project name in header and project filtering.                                                                     |
| **2026-09-05** | **Step 4: Joining List Date Sync (`/payroll-joining-list`)**            | Added project data attributes (`data-is-custom`, `data-start-date`, `data-end-date`, `data-date`) to `#payrollProject` dropdown, enabled instant date auto-sync without cascading submits, updated `joiningList()` and `exportJoiningListPDF()` with project date resolution (monthly & custom), workforce scoping, and project-labeled PDF header.                   |
| **2026-09-05** | **Step 6: Attendance Report PDF & Sync (`/payroll-attendance-report`)** | Added `selected_project_id` support and `#exportPdfForm` `payroll_project_id` input, enabled global `window.exportPdf`, updated `exportAttendancePDF()` with project date auto-sync, project header title, seamless DB overrides from `attendance_monthly_update` (real approved lates & deductions), and resilient local database fallback if Tipsoi API is offline. |
| **2026-09-05** | **Step 7: Payroll Summary Report (`/payroll-summary-report`)**          | Sorted projects chronologically, replaced broken ID `>= <=` with index-based array slicing and `WHERE payroll_project_id IN (...)`, excluded drafts & deselected records, read canonical tax from `p.tax`, accurately summed split bank/cash amounts, and updated PDF export with project range and inline mode (`'I'`).                                              |
| **2026-09-05** | **Step 8: Detailed Summary (`/payroll-detailed-summary`)**              | Excluded drafts and deselected employees, displayed project date in PDF header and dropdown, switched PDF output to inline preview (`'I'`), added grand total employee and payable footer (`<tfoot>`). Verified across all projects against Payslip benchmark.                                                                                                        |
| **2026-09-05** | **Step 9: Salary Report (`/payroll-salary-report`)**                    | Standardized Department and Office queries to strictly exclude drafts and deselected records across web view, PDF, and CSV. Implemented accurate split `bank_amount` & `cash_amount` aggregation, added grand total footers (`<tfoot>`), and enabled inline PDF preview (`'I'`). Verified 100% harmony with Master Summary Report across all projects.                |
