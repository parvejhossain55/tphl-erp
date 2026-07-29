# Goal Description

Add a robust financial reporting suite to the Financial Dashboard, including a new tab for "P&L and Balance Sheet", which will contain three cards: **Profit and Loss**, **Balance Sheet**, and **Cashflow**. Each card will link to a dedicated report page with filtering options for Project, Office, and Date Range.

## User Review Required

### Calculation Strategies

**1. Profit and Loss (Income Statement)**
- **Revenue Accounts:** Calculated as `SUM(Credit) - SUM(Debit)`.
- **Expense Accounts:** Calculated as `SUM(Debit) - SUM(Credit)`.
- **Net Income (Profit/Loss):** Calculated as `Total Revenue - Total Expenses`. 
- **Filters:** Date range, Project, Office.

**2. Balance Sheet**
- **Asset Accounts:** Calculated as `SUM(Debit) - SUM(Credit)`.
- **Liability Accounts:** Calculated as `SUM(Credit) - SUM(Debit)`.
- **Equity Accounts:** Calculated as `SUM(Credit) - SUM(Debit)`.
- **Retained Earnings / Current Year Profit:** The Net Income from the P&L calculation is automatically added to the Equity section to ensure that **Assets = Liabilities + Equity**.
- **Filters:** As of Date (End Date), Project, Office. (Note: Balance Sheets are cumulative up to a specific date).

**3. Cashflow Statement**
- A simplified Cashflow statement tracking all transactions involving "Cash" and "Bank" accounts.
- **Cash Inflows:** Calculated from Debits to Cash/Bank accounts.
- **Cash Outflows:** Calculated from Credits to Cash/Bank accounts.
- **Net Cashflow:** `Total Inflows - Total Outflows`.
- **Filters:** Date range, Project, Office.

> [!IMPORTANT]
> The Balance Sheet calculation relies on Net Income from the P&L to balance. Do you have a specific "Retained Earnings" account we should roll this into, or should I dynamically display it as "Current Period Net Income" under Equity? 
> Also, for Cashflow, I will identify cash accounts by looking for names containing "Cash" or "Bank". Does this work for your Chart of Accounts?

## Proposed Changes

### Controllers & Models

#### [NEW] `app/controllers/ReportsController.php`
- Handles the routing and data preparation for the three report pages (`balanceSheet`, `profitLoss`, `cashflow`).
- Applies the filters (Project, Office, Dates) received via GET parameters.

#### [NEW] `app/models/Report.php`
- Contains complex SQL queries to aggregate debits and credits across the `journal_details`, joined with `journals` (for status, dates, projects, offices) and `chart_of_accounts` (for account types).
- Ensures only "Approved" or "Posted" journals are included in the financial reports.

### Views & Routes

#### [MODIFY] `app/routes/accounts.php`
- Add routes for `/reports/balance-sheet`, `/reports/profit-loss`, and `/reports/cashflow`.

#### [MODIFY] `app/views/accounts/financial-dashboard.php`
- Add a new Tab for "Financial Reports".
- Add a 3-column grid containing cards for "Balance Sheet", "Profit & Loss", and "Cashflow", each with a brief description and a "View Report" button.

#### [NEW] `app/views/accounts/reports/balance-sheet.php`
- Standard accounting layout for Balance Sheet (Assets on left/top, Liabilities & Equity on right/bottom).
- Filter bar at the top (Project, Office, Date).

#### [NEW] `app/views/accounts/reports/profit-loss.php`
- Standard P&L layout (Revenue minus Expenses).
- Filter bar at the top.

#### [NEW] `app/views/accounts/reports/cashflow.php`
- Standard Cashflow layout (Inflows vs Outflows).
- Filter bar at the top.

## Verification Plan

### Automated Tests
- N/A

### Manual Verification
- Navigate to the Financial Dashboard and verify the new tab and cards.
- Click "View Report" for the Balance Sheet and ensure the report page loads.
- Create a test journal entry with Revenue/Expense and Asset/Liability accounts.
- Verify the P&L correctly calculates Net Income.
- Verify the Balance Sheet perfectly balances (Assets = Liabilities + Equity) by rolling the Net Income into the Equity section.
- Apply Project and Office filters and verify the numbers adjust correctly.
