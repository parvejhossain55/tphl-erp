Implementation Plan - HRM Module REST API & Views (Remaining Batches)
This plan outlines the implementation for the remaining batches (Batch 2, 3, and 4) of the HRM Module. We will proceed batch-by-batch, verifying each batch before moving to the next.

Completed: Batch 1
ExternalApiService fully integrated with https://app.tipsoi.ai/inovace-client/api/v1/.
Bearer Token authentication & session caching implemented.
Client-side pagination, search, and dynamic stats cards for Tipsoi Employees (Registered vs. Missing in ERP matched by ID or Email).
Cleaned up the redundant hrm-erp-employees view, route, and menu item.
Batch 2: Attendance & Absence (Today + History + Filters)
Goal Description
Retrieve today's attendance stats and overall attendance history from Tipsoi, match with local employees, and display them on a comprehensive Attendance Dashboard with stats cards and filters.

Proposed Changes
[MODIFY] 
routes/hrm.php
Add the following routes for Batch 2:

php

'hrm-attendance-dashboard'   => ['controller' => 'EmpAttendanceController', 'method' => 'attendanceDashboardView'],
'api/hrm/attendance/today'   => ['controller' => 'HrmApiController', 'method' => 'getTodayAttendance'],
'api/hrm/attendance/history' => ['controller' => 'HrmApiController', 'method' => 'getAttendanceHistory'],
'api/hrm/absence/today'      => ['controller' => 'HrmApiController', 'method' => 'getTodayAbsence'],
[MODIFY] 
services/ExternalApiService.php
Add functions to fetch attendance data from the Tipsoi API:

getTodayAttendance(): Calls Tipsoi's /attendance/today or equivalent endpoint.
getTodayAbsence(): Calls Tipsoi's /absence/today or equivalent endpoint.
getAttendanceHistory($startDate, $endDate, $limit, $page): Fetch log history.
[MODIFY] 
controllers/HrmApiController.php
Implement endpoint methods:

getTodayAttendance(): Retrieves today's active attendance logs, matches with local database (ID or Email) and returns JSON.
getTodayAbsence(): Retrieves today's absence list, maps to local database and returns JSON.
getAttendanceHistory(): Retrieves date-filtered history from Tipsoi and returns structured JSON.
[MODIFY] 
controllers/EmpAttendanceController.php
Add method:

attendanceDashboardView(): Renders the frontend dashboard layout.
[NEW] 
views/hrm/attendance/attendance-dashboard.php
A modern grid/table layout featuring:

Stats cards: Total Present Today, Late Today, Absent Today, On Leave Today.
Dynamic filters: Date Range Picker, Department Filter, Search Box.
Dynamic table listing attendance history with Check-in time, Check-out time, working hours, and Late status.
Batch 3: Requests System (Leave, Manual Entry, Late Requests)
Goal Description
Build a unified requests management dashboard to fetch all user-submitted requests (Leave Requests, Manual Attendance Entries, and Late Excuses) from the system.

Proposed Changes
[MODIFY] 
routes/hrm.php
Add the following routes:

php

'hrm-requests-manager'  => ['controller' => 'LeaveController', 'method' => 'requestsManagerView'],
'api/hrm/requests'      => ['controller' => 'HrmApiController', 'method' => 'getRequests'],
[MODIFY] 
controllers/HrmApiController.php
Add endpoint:

getRequests(): Combines leave requests, manual entries, and late requests from the database (or Tipsoi API if managed externally), filters by type (leave, manual_entry, late), employee, and date range.
[MODIFY] 
controllers/LeaveController.php
Add view rendering method:

requestsManagerView(): Renders the central Requests Manager page.
[NEW] 
views/hrm/requests/requests-manager.php
A unified page showing tabs for:

Leave Requests (Start date, end date, status, actions to Approve/Reject).
Manual Attendance Entries (Requested Check-in/out times, reason, approval status).
Late Requests (Late explanation status).
Batch 4: Leave Balances & Analytics
Goal Description
Retrieve and view remaining vs spent leaves per employee, enabling HR managers to monitor leave balances.

Proposed Changes
[MODIFY] 
routes/hrm.php
Add the following routes:

php

'hrm-leave-balances'     => ['controller' => 'LeaveController', 'method' => 'leaveBalancesView'],
'api/hrm/leave-balances' => ['controller' => 'HrmApiController', 'method' => 'getLeaveBalances'],
[MODIFY] 
controllers/HrmApiController.php
Add endpoint:

getLeaveBalances(): Fetches leave types and remaining balances per employee from the DB.
[MODIFY] 
controllers/LeaveController.php
Add view rendering method:

leaveBalancesView(): Renders the leave balance details table.
[NEW] 
views/hrm/leaves/leave-balances.php
An analytical details page containing:

A list of employees with columns for: Casual Leave, Sick Leave, Earned Leave (Spent / Total remaining).
Search and department filter dropdowns.
Direct action buttons to "View History" or "Apply Adjustment".
Verification Plan
Automated/Scratch Verification
We will write/update a test script /public/test_hrm_api.php to execute curl requests directly to these new HrmApi endpoints and print matching results, status codes, and JSON validity.
Manual Verification
Access the newly added sidebar menu items for each view.
Verify AJAX loading spinners, data populating on tabs, and filter/date-picker reactions.
Validate approval buttons in the Requests Manager page and check if updates persist in the database.