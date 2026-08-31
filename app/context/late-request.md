I need you to implement a complete new "Late Request" feature in my existing HR/Employee frontend application.

IMPORTANT:
- First inspect the existing project structure, routing, API service/helper functions, authentication/JWT handling, sidebar navigation, employee/Tipsoi pages, table components, modal components, form patterns, status badges, approval logic, and existing Leave Request implementation.
- Follow the existing project architecture and coding style.
- DO NOT create a completely separate architecture.
- Reuse existing components, utilities, API handlers, authentication logic, styling, and UI patterns wherever possible.
- Do not break any existing functionality.
- Make the feature production-ready.
- After implementation, test all flows and fix any errors.

==================================================
1. UI LOCATION
==================================================

Currently there is a "Tipsoi My Data" section in the left sidebar.

Under:

Tipsoi My Data
  - My Profile
  - Attendance Data
  - My Leave Balance
  - Pending Request
  - Approval Pending
  - Apply Leave
  - Applied Leave List

Add a new menu item:

  - Late Request

Use an appropriate icon consistent with the existing sidebar.

Also add "Late Request" as a new tab/menu inside the "My Tipsoi Information" page shown in the provided screenshot.

The existing page has tabs like:

- My Profile
- Attendance Data
- My Leave Balance
- Pending Requests
- Approval Pending
- Apply Leave
- Applied Leave List

Add:

- Late Request

Keep the exact existing design language, spacing, typography, colors, borders, cards, navigation behavior, and responsive behavior.

Do not redesign the entire page.

==================================================
2. LATE REQUEST PAGE
==================================================

Create a dedicated Late Request page.

The page should allow an employee to:

1. Create a late request
2. View their late requests
3. See approval status
4. Cancel their pending late request when allowed
5. If they are an approver in the approval flow, approve/reject the request

The page should have a clean HR-system style UI.

Suggested structure:

--------------------------------------------------
Late Request
--------------------------------------------------

[ Create Late Request ]

Then a table/card/list containing:

Date
Employee
Attendance Time
Late By
Reason
Status
Approval Progress
Actions

Make the table responsive.

On mobile, use the existing responsive table/card pattern from the project.

==================================================
3. CREATE LATE REQUEST API
==================================================

Endpoint:

POST /late-approval-management/apply/employee/{employeeId}

Request body:

{
  "attendanceDate": 1756000000000,
  "reason": "Stuck in traffic"
}

Implementation requirements:

- Get the currently logged-in employee ID using the application's existing authentication/user data mechanism.
- Do NOT hardcode employeeId.
- Use the same JWT/authentication mechanism already used by the other Tipsoi APIs.
- attendanceDate should be sent as the required timestamp format.
- Provide a date picker for the user.
- Provide a reason input/textarea.
- Validate required fields before submitting.
- Show a loading state while submitting.
- Prevent duplicate submissions.
- Show success/error feedback using the project's existing alert/modal system.
- After successful creation, refresh the late request list automatically.

Example UI:

Create Late Request modal:

Date *
[ Select Date ]

Reason *
[ Stuck in traffic................ ]

[ Cancel ] [ Submit Request ]

Use the existing modal implementation if one already exists.

==================================================
4. GET USER'S LATE REQUESTS
==================================================

Endpoint:

GET /late-approval-management/approval-requests/{employeeId}

Response:

{
  "message": "Success",
  "lateApprovalApplications": [
    {
      "lateApprovalLogId": 88,
      "employeeId": 55,
      "employeeName": "...",
      "attendanceTime": 1756000000000,
      "lateBy": "00:23",
      "reason": "...",
      "status": 0,
      "approverSystem": "MULTIPLE",
      "approvalFlow": "PARALLEL",
      "firstApprovalStatus": 0,
      "firstApprovalComment": null,
      "secondApprovalStatus": 0,
      "secondApprovalComment": null,
      "otherApproverId": 91,
      "otherApproverName": "...",
      "otherApproverStatus": "...",
      "otherApproverComment": "..."
    }
  ]
}

Use the logged-in employee ID dynamically.

Display the returned requests.

Do not assume the API always returns data.

Handle:

- Empty array
- Loading
- API error
- Expired authentication
- Missing fields/null values

==================================================
5. STATUS HANDLING
==================================================

The API returns:

status

and approval-specific statuses:

firstApprovalStatus
secondApprovalStatus
otherApproverStatus

Do NOT blindly assume what numeric values mean without checking the existing project/API conventions.

Inspect the existing Leave Request / Approval Pending implementation to determine how approval statuses are represented.

If the project already has a status mapping utility, reuse it.

The UI should clearly display statuses such as:

- Pending
- Approved
- Rejected
- Cancelled

Use the same badge/status styling already used elsewhere in the application.

==================================================
6. APPROVAL FLOW
==================================================

This is very important.

A user can potentially be:

- The employee who submitted the late request
- First approver
- Second approver
- Another approver

The response contains:

approverSystem
approvalFlow
firstApprovalStatus
firstApprovalComment
secondApprovalStatus
secondApprovalComment
otherApproverId
otherApproverName
otherApproverStatus
otherApproverComment

The UI must determine whether the currently logged-in user is authorized to approve/reject the request.

DO NOT show Approve/Reject buttons to every user.

Only show approval actions when the logged-in user is actually an approver for that request and the corresponding approval step is pending.

Use the currently authenticated employee/user ID.

If the existing project already contains approval-role logic, reuse it.

The approval UI should clearly show:

First Approval
  Status
  Comment

Second Approval
  Status
  Comment

Other Approver
  Name
  Status
  Comment

For example:

Approval Progress

✓ First Approver - Approved
⏳ Second Approver - Pending

or:

First Approval
Pending

Second Approval
Pending

==================================================
7. APPROVE LATE REQUEST
==================================================

Endpoint:

POST /late-approval-management/approve/{lateApprovalLogId}

Request:

{
  "approverComment": "Approved"
}

When the logged-in user is authorized to approve the request:

Show:

[ Approve ]

When clicked:

- Open a confirmation modal.
- Allow the approver to enter an optional/required comment based on existing project conventions.
- Submit the API request.
- Show loading state.
- Prevent duplicate clicks.
- On success, close modal and refresh the data.
- Show success feedback.

Do not use a browser alert.

Use the existing SweetAlert/modal system used by the application.

==================================================
8. REJECT LATE REQUEST
==================================================

Endpoint:

POST /late-approval-management/reject/{lateApprovalLogId}

Request:

{
  "approverComment": "Rejected because..."
}

When authorized:

Show:

[ Reject ]

On click:

- Open a confirmation modal.
- Require/allow rejection comment according to existing application conventions.
- Submit API request.
- Show loading state.
- Refresh data after success.
- Show proper success/error feedback.

Do not use browser alert().

==================================================
9. CANCEL LATE REQUEST
==================================================

Endpoint:

POST /late-approval-management/cancel/{lateApprovalLogId}

Request:

{
  "approverComment": "Not needed"
}

Only allow the employee to cancel their own request when the request is in a cancellable/pending state.

Do NOT show Cancel to everyone.

Show:

[ Cancel Request ]

Confirmation modal:

Are you sure you want to cancel this late request?

Optional comment:

[ Not needed ]

[ Keep Request ] [ Cancel Request ]

Call:

POST /late-approval-management/cancel/{lateApprovalLogId}

Then refresh the list.

==================================================
10. LATE REQUEST HISTORY
==================================================

Also implement:

GET /late-approval-management/late-approvals/{employeeId}

This endpoint provides:

Late Request Applications History (with approval state)

Use this endpoint for a "History" section/tab if appropriate based on the existing application structure.

If the existing project already separates pending/current requests and history, follow that pattern.

The history should show:

- Date
- Late by
- Reason
- Overall status
- First approval
- Second approval
- Other approver
- Comments
- Actions when applicable

==================================================
11. DATA FORMATTING
==================================================

The API uses Unix timestamps in milliseconds.

Example:

1756000000000

Convert timestamps into a human-readable date/time using the project's existing date formatting utility.

Do not display raw timestamps.

For example:

27 Aug 2025
09:35 AM

Use the project's existing date/time formatting library if available.

==================================================
12. API SERVICE
==================================================

Create/reuse API service functions according to the project's existing architecture.

For example:

createLateRequest(employeeId, data)

getLateApprovalRequests(employeeId)

approveLateRequest(lateApprovalLogId, data)

rejectLateRequest(lateApprovalLogId, data)

cancelLateRequest(lateApprovalLogId, data)

getLateApprovalHistory(employeeId)

Do not create duplicate API clients.

Use the existing Axios/fetch wrapper.

Use the existing base URL.

Use the existing JWT/token mechanism.

==================================================
13. ROUTING
==================================================

Add a proper route for:

Late Request

The route should be protected using the same authentication/authorization mechanism as the other Tipsoi My Data pages.

The sidebar item and the "My Tipsoi Information" tab must navigate to the same page/route.

Do not duplicate the entire page implementation.

==================================================
14. UI DESIGN
==================================================

Match the screenshot and existing application.

Important design requirements:

- White cards
- Existing green brand color
- Existing border radius
- Existing border styles
- Existing typography
- Existing spacing
- Existing sidebar style
- Existing tab style
- Existing table style
- Existing icons
- Existing hover effects
- Existing responsive behavior

Do NOT introduce a new visual design system.

The Late Request page should feel like it has always been part of the application.

==================================================
15. USER EXPERIENCE
==================================================

Include proper states:

Loading:

Show skeleton/spinner consistent with existing pages.

Empty:

"No late requests found."

Error:

"Failed to load late requests. Please try again."

Success:

"Late request submitted successfully."

Approve:

"Late request approved successfully."

Reject:

"Late request rejected successfully."

Cancel:

"Late request cancelled successfully."

Use the project's existing notification/alert system.

==================================================
16. SECURITY / PERMISSIONS
==================================================

VERY IMPORTANT:

Never trust frontend-only permissions.

The frontend should hide inappropriate actions, but the backend API remains the authority.

Do not allow the user to manually manipulate an employeeId in the UI to submit requests for another employee.

Use the authenticated employee ID.

For approval actions, determine whether the current user is the correct approver based on the available API response and existing application's approval logic.

If the backend rejects unauthorized approval, handle the error gracefully.

==================================================
17. DO NOT BREAK EXISTING FEATURES
==================================================

Before making changes:

Inspect:

- Sidebar component
- Tipsoi My Data navigation
- My Tipsoi Information page
- Employee profile
- Leave Request pages
- Pending Requests
- Approval Pending
- API service
- Auth context/store
- Route configuration
- Status/badge components
- Modal/SweetAlert components

Reuse existing implementations.

Do not rewrite unrelated files.

Do not change existing API behavior.

Do not remove existing functionality.

==================================================
18. ERROR HANDLING
==================================================

Handle API errors properly.

For example:

401:
- Use existing authentication handling.
- If the project redirects to login/session refresh, follow the existing mechanism.

403:
- Show permission error.

404:
- Show appropriate not found message.

422:
- Display validation errors from API.

500:
- Show generic server error.

Network error:
- Show retry-friendly message.

Do not expose raw technical errors to users unless the existing project does so.

==================================================
19. RESPONSIVE DESIGN
==================================================

The feature must work properly on:

- Desktop
- Tablet
- Mobile

The screenshot is desktop, but do not only implement desktop.

On small screens:

- Table should remain usable.
- Use horizontal scrolling or existing responsive card layout.
- Modal should fit the screen.
- Buttons should not overflow.
- Sidebar behavior should follow the existing application.

==================================================
20. IMPORTANT IMPLEMENTATION PROCESS
==================================================

Follow this exact process:

STEP 1:
Inspect the existing project and understand its architecture.

STEP 2:
Find how Tipsoi API calls are currently implemented.

STEP 3:
Find how employee ID is obtained.

STEP 4:
Find how Leave Request and Approval Pending work.

STEP 5:
Reuse those patterns.

STEP 6:
Add the Late Request API service functions.

STEP 7:
Add the Late Request page.

STEP 8:
Add sidebar navigation under "Tipsoi My Data".

STEP 9:
Add the Late Request tab to "My Tipsoi Information".

STEP 10:
Implement create request.

STEP 11:
Implement request list.

STEP 12:
Implement approval progress.

STEP 13:
Implement conditional Approve/Reject buttons.

STEP 14:
Implement Cancel Request.

STEP 15:
Implement history.

STEP 16:
Add loading, empty, success, and error states.

STEP 17:
Test all routes and API interactions.

STEP 18:
Fix lint/build/runtime errors.

==================================================
21. ACCEPTANCE CRITERIA
==================================================

The implementation is complete only when:

✓ "Late Request" appears under Tipsoi My Data in the sidebar.

✓ "Late Request" appears in the My Tipsoi Information navigation/tabs.

✓ Clicking either navigation item opens the Late Request page.

✓ Employee can create a late request.

✓ Employee ID is dynamic and comes from authentication/current employee data.

✓ Attendance date is sent correctly.

✓ Reason is sent correctly.

✓ User can see their late requests.

✓ API timestamps are formatted properly.

✓ Overall request status is displayed.

✓ First approval status is displayed.

✓ Second approval status is displayed.

✓ Other approver status/comment is displayed when available.

✓ Only the correct approver sees Approve/Reject actions.

✓ Approve works.

✓ Reject works.

✓ Employee can cancel their own eligible pending request.

✓ History endpoint is implemented.

✓ Loading states work.

✓ Empty states work.

✓ API errors are handled.

✓ JWT authentication works using the existing system.

✓ No browser alert() is used.

✓ Existing SweetAlert/modal system is reused.

✓ Desktop UI matches the existing application.

✓ Mobile UI works properly.

✓ Existing features continue working.

✓ No hardcoded employee IDs.

✓ No unnecessary architectural changes.

✓ No duplicated API/authentication logic.

==================================================
FINAL REQUIREMENT
==================================================

After implementation, review every changed file and make sure the feature is actually connected end-to-end:

Sidebar
→ Route
→ Late Request Page
→ API Service
→ Authentication
→ Create Request
→ Get Requests
→ Approval Status
→ Approve
→ Reject
→ Cancel
→ History

Do not just create UI mockups.

Everything must be functional with the provided Tipsoi APIs.

If an existing project convention conflicts with any assumption above, prioritize the existing project's implementation pattern and explain the decision in the final response.

At the end, provide a concise summary of:
1. Files/components changed
2. APIs integrated
3. Routes added
4. Approval logic implemented
5. Any issues or assumptions
6. Testing/build result