I need you to implement a complete "Create Employee" feature in our existing PHP project.

IMPORTANT PROJECT RULES:
- This project uses PHP only.
- HTML/CSS is rendered from the server.
- Do NOT use React, Vue, Angular, or any SPA framework.
- Follow the existing project architecture.
- Before making changes, inspect the existing routes, controllers, views, Tipsoi API integration, authentication, and HTTP client.
- Reuse the existing Tipsoi API authentication/JWT/HTTP logic.
- Do not create a new authentication mechanism.
- Do not modify unrelated features.

I have provided screenshots showing:
1. The existing "Tipsoi Employees" page/design.
2. The Tipsoi "Create Employee" form design.

I want to add a Create Employee feature to our ERP.

==================================================
1. CREATE EMPLOYEE API
==================================================

The Tipsoi API expects the following request body:

{
  "name": "Testing 3",
  "employeeOfficeId": 3,
  "email": "testing3@example.com",
  "gender": 1,
  "password": "Tipsoi@25",
  "reportPosition": null,
  "shiftId": null,
  "shiftGroupId": null,
  "leavePolicyId": null,
  "primaryDisplayText": null,
  "secondaryDisplayText": null,
  "departmentId": null,
  "subDepartmentId": null,
  "designationId": null,
  "employeeIdentifier": null,
  "weeklyOffDayPolicyId": null,
  "rfid": null,
  "personId3rdPartyDevice": null,
  "workplaceId": null,
  "dateOfJoin": null,
  "dateOfResignation": null,
  "employmentTypeId": null,
  "subsidiaryId": null
}

IMPORTANT:
- Inspect the existing Tipsoi API documentation/integration to determine the exact HTTP method and endpoint for creating an employee.
- Do NOT guess the endpoint.
- If the existing project already has an employee-create API method, reuse it.
- Send the request from the PHP backend to Tipsoi.
- Never expose the Tipsoi JWT/token or API credentials to the browser.

==================================================
2. CREATE EMPLOYEE UI
==================================================

Create a new "Create Employee" page/form.

The UI should follow the design shown in the provided screenshot.

Use the same:
- Header
- Sidebar
- Breadcrumb
- Card style
- Input style
- Button style
- Font
- Spacing
- Colors
- Border radius
- Existing CSS conventions

Do NOT redesign the entire application.

The page should have a clear title:

CREATE EMPLOYEE

Use a two-column layout on desktop, similar to the screenshot.

On mobile:
- Stack the fields vertically.
- Make the form fully responsive.
- Do not cause horizontal page overflow.

==================================================
3. FORM FIELDS
==================================================

Add the following fields.

REQUIRED FIELDS:

1. Name
   API field:
   name

2. Employee ID
   API field:
   employeeOfficeId

3. Email
   API field:
   email

4. Password
   API field:
   password

5. Confirm Password
   This is frontend-only.
   It should NOT be sent to Tipsoi.

6. Gender
   API field:
   gender

Gender should be a dropdown/select.

Use the values expected by Tipsoi.

If:
1 = Male
2 = Female

then display:

Male
Female

while sending the numeric value to Tipsoi.

Verify this against the existing Tipsoi API behavior/documentation before implementing.

==================================================
4. ADVANCED OPTIONS
==================================================

Add an "Advanced Options" section/button similar to the provided screenshot.

The advanced section should contain only the fields that we actually want to expose in this ERP UI.

Include:

- Start Date
- Workplace
- Designation
- Report Position
- Department
- Leave Policy
- Employment Type
- Shift Group
- RFID

Map them to the API fields:

Start Date:
dateOfJoin

Workplace:
workplaceId

Designation:
designationId

Report Position:
reportPosition

Department:
departmentId

Leave Policy:
leavePolicyId

Employment Type:
employmentTypeId

Shift Group:
shiftGroupId

RFID:
rfid

==================================================
5. DO NOT ADD THESE FIELDS TO UI
==================================================

The following fields should NOT appear in the Create Employee UI.

subDepartmentId
primaryDisplayText
secondaryDisplayText
employeeIdentifier
weeklyOffDayPolicyId
personId3rdPartyDevice
dateOfResignation
shiftId
shiftGroupId should NOT be duplicated as a separate field
subsidiaryId

IMPORTANT:

"Shift Group" IS required in the UI.

Use:

shiftGroupId

for the Shift Group dropdown.

Do not add a separate "Shift" dropdown.

Do NOT add Sub Department.

==================================================
6. REMOVE / DO NOT DISPLAY THE X-MARKED FIELDS
==================================================

In the provided screenshot, some fields are marked with a red X.

These fields should NOT be included in our new UI.

Specifically remove/do not create:

- RFID
- Primary Display Text
- Secondary Display Text
- PersonID 3rd party Device

Also do not add any other unnecessary Tipsoi fields that are not part of our requested UI.

IMPORTANT:

Even though RFID exists in the API body, the screenshot/request indicates that the X-marked fields should not be shown.

Therefore:
- Do not show RFID in the UI.
- Send:
  "rfid": null

Similarly:

"primaryDisplayText": null
"secondaryDisplayText": null
"personId3rdPartyDevice": null

should be handled by the backend/request builder.

==================================================
7. DROPDOWN DATA FROM TIPSOI
==================================================

These dropdowns MUST load their options dynamically from Tipsoi APIs.

Do NOT hardcode the dropdown options.

--------------------------------------------------
Designation
--------------------------------------------------

GET:

/designation

Load the designation list from Tipsoi.

The dropdown should display the designation name.

When selected, send the corresponding Tipsoi designation ID as:

designationId

--------------------------------------------------
Department
--------------------------------------------------

GET:

/department

Load departments from Tipsoi.

Display the department name.

Send the selected department ID as:

departmentId

--------------------------------------------------
Leave Policy
--------------------------------------------------

GET:

/leave-policy

Load leave policies from Tipsoi.

Display the policy name.

Send the selected ID as:

leavePolicyId

--------------------------------------------------
Employment Type
--------------------------------------------------

GET:

/employment-type

Load employment types from Tipsoi.

Display the employment type name.

Send the selected ID as:

employmentTypeId

--------------------------------------------------
Shift Group
--------------------------------------------------

GET:

/shift-group

Load shift groups from Tipsoi.

Display the shift group name.

Send the selected ID as:

shiftGroupId

==================================================
8. WORKPLACE
==================================================

The request body contains:

"workplaceId": null

Inspect the existing Tipsoi integration to determine whether there is already an endpoint for workplaces.

If an existing workplace endpoint is available in the project/API documentation:
- Load workplaces dynamically.
- Display the workplace name.
- Send the selected ID as workplaceId.

If no workplace endpoint exists, keep Workplace as a normal/select field according to the existing application's available data source.

Do NOT invent an API endpoint.

==================================================
9. REPORT POSITION
==================================================

Add:

Report Position

This maps to:

reportPosition

It can be a normal input field.

Default value:

null / empty

If the Tipsoi API expects a numeric value, validate and convert it appropriately.

Do not send an invalid empty string if Tipsoi expects null.

==================================================
10. DATE FIELDS
==================================================

Start Date:

Map to:

dateOfJoin

Use a proper HTML date input.

The displayed format should follow the existing project's date input style.

When submitting, convert the date to the format expected by Tipsoi.

If no date is selected:

dateOfJoin = null

Do not add End Date/Date of Resignation to the UI.

The API should receive:

"dateOfResignation": null

==================================================
11. PASSWORD VALIDATION
==================================================

Add:

Password
Confirm Password

Frontend validation:

- Password is required.
- Confirm Password is required.
- Both values must match.

If they do not match, prevent submission and show a clear validation message.

Do not send confirmPassword to Tipsoi.

Only send:

password

==================================================
12. FORM VALIDATION
==================================================

Required fields:

- Name
- Employee ID
- Email
- Password
- Confirm Password
- Gender

Validate:

- Name cannot be empty.
- Employee ID cannot be empty.
- Email must be a valid email.
- Password cannot be empty.
- Confirm Password must match.
- Gender must be selected.

Dropdown fields should allow an empty/null value where the API body supports null.

For optional fields, send:

null

rather than an empty string when appropriate.

==================================================
13. REQUEST BODY
==================================================

When submitting the form, construct the Tipsoi request body exactly in this structure:

{
  "name": "<name>",
  "employeeOfficeId": <employeeOfficeId>,
  "email": "<email>",
  "gender": <gender>,
  "password": "<password>",
  "reportPosition": <reportPosition or null>,
  "shiftId": null,
  "shiftGroupId": <selected shift group ID or null>,
  "leavePolicyId": <selected leave policy ID or null>,
  "primaryDisplayText": null,
  "secondaryDisplayText": null,
  "departmentId": <selected department ID or null>,
  "subDepartmentId": null,
  "designationId": <selected designation ID or null>,
  "employeeIdentifier": null,
  "weeklyOffDayPolicyId": null,
  "rfid": null,
  "personId3rdPartyDevice": null,
  "workplaceId": <selected workplace ID or null>,
  "dateOfJoin": <date or null>,
  "dateOfResignation": null,
  "employmentTypeId": <selected employment type ID or null>,
  "subsidiaryId": null
}

IMPORTANT:
Use numeric IDs where Tipsoi expects numeric IDs.

Do not send the dropdown's displayed text instead of its ID.

==================================================
14. BACKEND ARCHITECTURE
==================================================

Implement the feature through the PHP backend.

Recommended flow:

Browser
   ↓
PHP Route
   ↓
PHP Controller
   ↓
Existing Tipsoi API helper/service/client
   ↓
Tipsoi API
   ↓
PHP Controller
   ↓
View / redirect / success message

Do NOT call Tipsoi directly from browser JavaScript if the project currently uses server-side API communication.

Reuse the project's existing Bearer Token/JWT authentication.

==================================================
15. DROPDOWN API LOADING
==================================================

For the Create Employee page, the backend should fetch:

/designation
/department
/leave-policy
/employment-type
/shift-group

and provide the data to the view.

Do not expose authentication credentials.

Prefer server-side fetching because this project is server-rendered PHP.

If the existing application already has reusable methods for these endpoints, use them.

Do not duplicate API authentication code five times.

==================================================
16. ERROR HANDLING
==================================================

Handle errors from every Tipsoi endpoint.

For example:

- /designation fails
- /department fails
- /leave-policy fails
- /employment-type fails
- /shift-group fails
- Create employee API fails

The page should not show raw PHP exceptions or API credentials.

Display a user-friendly message.

Example:

"Unable to load department data. Please try again."

For employee creation:

"Employee created successfully."

or:

"Unable to create employee. Please check the information and try again."

If Tipsoi returns a validation error, display the useful validation message to the user without exposing sensitive information.

==================================================
17. SUCCESS FLOW
==================================================

After successfully creating the employee:

- Show a success message/alert using the existing project's alert system.
- Do not use browser alert() if the project already has a styled alert/modal system.
- Clear/reset the form if appropriate.
- Optionally redirect back to the Tipsoi Employees list.

Prefer redirecting to the existing:

Tipsoi Employees

page after successful creation.

The new employee should then appear in the employee list after refresh.

==================================================
18. CREATE BUTTON
==================================================

Add a clear button:

Create Employee

Use the existing application's button styling.

Also provide:

Reset

if the existing design supports it.

Prevent duplicate submissions while the request is being processed.

==================================================
19. NAVIGATION
==================================================

Add a navigation option for:

Create Employee

under the appropriate HR / Tipsoi Employee menu.

For example:

Tipsoi Employee Data
    ├── Tipsoi Employees
    └── Create Employee

Follow the existing sidebar/navigation structure.

Do not break the current:

Tipsoi Employees

page.

==================================================
20. IMPORTANT API RESPONSE INSPECTION
==================================================

Before implementing the dropdown mapping, inspect the actual responses from:

GET /designation
GET /department
GET /leave-policy
GET /employment-type
GET /shift-group

Do NOT assume the response format.

For example, the response could be:

{
  "data": [...]
}

or:

[
  ...
]

or another structure.

Determine the actual structure and correctly extract:

ID
Name

from each response.

Likewise, verify the Create Employee API's actual endpoint and expected HTTP method.

==================================================
21. EXISTING EMPLOYEE LIST INTEGRATION
==================================================

There is already a "Tipsoi Employees" page that displays employees fetched from Tipsoi.

Do not create another separate employee list.

The Create Employee page should integrate with the existing employee list.

After successful creation:
- Redirect to the existing Tipsoi Employees page.
- The newly created employee should be visible after data refresh.

==================================================
22. FILES TO INSPECT / UPDATE
==================================================

First identify the exact files used by this feature.

Likely areas include:

- routes/web.php or equivalent route file
- Tipsoi Employee Controller
- Tipsoi API service/helper
- Create Employee PHP/Blade view
- Existing sidebar/navigation view
- Existing CSS file

Do not assume filenames.

Find the actual files first.

Only modify files that are necessary.

==================================================
23. DO NOT BREAK EXISTING FEATURES
==================================================

After implementing Create Employee, verify that these continue working:

- Tipsoi Employees
- Tipsoi Attendance
- Tipsoi Leave History
- Tipsoi Leave Balances
- All Pending Requests
- Monthly LWP
- Tipsoi Reports
- Department Lists
- Designation Lists
- Existing employee functionality

Do not modify unrelated functionality.

==================================================
24. TESTING CHECKLIST
==================================================

Test the complete flow:

1. Open Create Employee.
2. Verify the page matches the provided design.
3. Verify Name field.
4. Verify Employee ID field.
5. Verify Email field.
6. Verify Password.
7. Verify Confirm Password.
8. Verify Gender.
9. Verify Department dropdown loads from /department.
10. Verify Designation dropdown loads from /designation.
11. Verify Leave Policy dropdown loads from /leave-policy.
12. Verify Employment Type dropdown loads from /employment-type.
13. Verify Shift Group dropdown loads from /shift-group.
14. Verify Sub Department is NOT displayed.
15. Verify RFID is NOT displayed.
16. Verify Primary Display Text is NOT displayed.
17. Verify Secondary Display Text is NOT displayed.
18. Verify PersonID 3rd party Device is NOT displayed.
19. Verify Date of Join.
20. Verify Workplace.
21. Verify Report Position.
22. Submit a valid employee.
23. Verify correct request body is sent to Tipsoi.
24. Verify successful employee creation.
25. Verify duplicate submission is prevented.
26. Verify validation errors.
27. Verify Tipsoi API errors.
28. Verify JWT/Bearer authentication.
29. Verify newly created employee appears in Tipsoi Employees.
30. Verify mobile responsiveness.
31. Verify no PHP errors.
32. Verify no broken existing features.

==================================================
25. FINAL REPORT
==================================================

After implementation, provide a concise report containing:

1. Files changed.
2. Routes added/changed.
3. Controller methods added/changed.
4. Tipsoi endpoints used.
5. Actual Create Employee endpoint and HTTP method discovered.
6. Dropdown response structures discovered.
7. Fields displayed in UI.
8. Fields intentionally excluded from UI.
9. Request body sent to Tipsoi.
10. Validation implemented.
11. Error handling implemented.
12. Testing performed.
13. Any issues or assumptions.

IMPORTANT:
Do not stop after creating the UI.

The feature must be fully functional:
UI → PHP Backend → Tipsoi API → Success/Error → Employee List.