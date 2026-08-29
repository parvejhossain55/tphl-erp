I need to add a fully functional "Create Employee" feature to my existing PHP-based ERP project.

IMPORTANT:
- This project uses PHP with server-rendered HTML/CSS.
- Do NOT use React, Vue, Angular, or any SPA framework.
- HTML and CSS are rendered/sent from the PHP server.
- Follow the existing project architecture and coding style.
- Before making changes, inspect the existing routes, controllers, views, Tipsoi API integration, authentication/JWT handling, and HTTP request helpers.
- Reuse the existing Tipsoi authentication/API client instead of creating a new authentication system.
- Do not modify unrelated features.

====================================================
FEATURE
====================================================

Create an employee through the Tipsoi API.

The Create Employee UI should be available from the existing:

HR & PAYROLL
    → Employee Manage
    → Create Employee

or the existing appropriate employee-management location.

The design should closely match the Create Employee screen shown in the provided screenshot.

Keep the existing:
- Header
- Sidebar
- Navbar
- Typography
- Colors
- Input styling
- Dropdown styling
- Buttons
- Spacing
- Responsive layout

Do not redesign the entire ERP.

====================================================
TIPSOI CREATE EMPLOYEE API
====================================================

The employee must be created through the Tipsoi API.

Use the appropriate existing Tipsoi API base URL and authentication.

The request body must be:

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

The API request should be sent server-side from PHP.

Do NOT expose the Tipsoi JWT/token/credentials to the browser.

====================================================
CREATE EMPLOYEE FORM
====================================================

Build the form similar to the provided screenshot.

MAIN FIELDS:

1. Name *
   - Text input
   - Maps to:
     "name"

2. Employee ID *
   - Text/number input
   - Maps to:
     "employeeOfficeId"

3. Email *
   - Email input
   - Maps to:
     "email"

4. Password *
   - Password input
   - Maps to:
     "password"

5. Confirm Password *
   - Password input
   - This is frontend validation only.
   - It should NOT be sent separately to Tipsoi.
   - Verify password and confirm password match before submitting.

6. Gender *
   - Dropdown
   - Keep the design similar to the screenshot.
   - The current screenshot shows Male.
   - Use the correct Tipsoi gender value.
   - Based on the supplied payload:
       Male = 1
   - Verify this against the existing Tipsoi implementation/API if possible.

====================================================
ADVANCED OPTIONS
====================================================

Keep an "Advanced Options" section similar to the screenshot.

The following fields SHOULD be included where applicable:

1. Start Date
   - Date input
   - Maps to:
     "dateOfJoin"

2. Designation
   - Dropdown
   - Maps to:
     "designationId"

3. Department
   - Dropdown
   - Maps to:
     "departmentId"

4. Leave Policy
   - Dropdown
   - Maps to:
     "leavePolicyId"

5. Employment Type
   - Dropdown
   - Maps to:
     "employmentTypeId"

6. Shift Group
   - Dropdown
   - Maps to:
     "shiftGroupId"

7. Workplace
   - Dropdown if an existing Tipsoi workplace API/data source is available.
   - Maps to:
     "workplaceId"
   - If there is already an existing workplace integration in the project, reuse it.
   - Do not invent a new API endpoint.
   - If no workplace endpoint exists, leave workplaceId as null and keep the UI consistent with the existing project.

8. End Date
   - Optional date input
   - Maps to:
     "dateOfResignation"

====================================================
FIELDS THAT MUST NOT APPEAR IN UI
====================================================

IMPORTANT:

The screenshot has several fields marked with a red X.

Do NOT show these fields in the Create Employee UI:

- Report Position
- RFID
- Primary Display Text
- Secondary Display Text
- PersonID 3rd Party Device

These fields should not be visible to the user.

For the API request, send them as null:

"reportPosition": null,
"primaryDisplayText": null,
"secondaryDisplayText": null,
"rfid": null,
"personId3rdPartyDevice": null

====================================================
SUB DEPARTMENT
====================================================

IMPORTANT:

Do NOT add "Sub Department" to the UI.

There must be NO Sub Department dropdown/input in the Create Employee form.

However, the API body still requires:

"subDepartmentId": null

So always send:

"subDepartmentId": null

====================================================
OTHER FIELDS NOT REQUIRED IN UI
====================================================

Do not add unnecessary UI fields for these values.

Send them as null unless the existing project already has a proper UI/data source for them:

"shiftId": null,
"employeeIdentifier": null,
"weeklyOffDayPolicyId": null,
"subsidiaryId": null

Do not invent dropdowns for these.

====================================================
DROPDOWN: DESIGNATION
====================================================

Designation must come from Tipsoi:

GET /designation

Example response:

{
  "message": "Success",
  "designationList": [
    {
      "id": 39249,
      "name": "CEO",
      "hierarchy": 1,
      "officeId": 4699,
      "status": 1,
      "createdBy": 298780,
      "updatedBy": null,
      "creationDate": 1762150496480,
      "updateDate": 1762150496480,
      "subsidiaryId": null,
      "externalSyncId": null
    }
  ]
}

Load the dropdown from:

designationList

Display:

name

Use:

id

as the submitted value.

When a designation is selected:

"designationId": selectedDesignation.id

Do NOT hardcode designation names.

====================================================
DROPDOWN: DEPARTMENT
====================================================

Department must come from:

GET /department

Example response:

{
  "message": "Success",
  "departmentList": [
    {
      "id": 23186,
      "name": "Management",
      "officeId": 4699,
      "status": 1,
      "isForGuests": false,
      "hierarchy": 1,
      "subsidiaryId": null,
      "externalSyncId": null
    }
  ]
}

Load:

departmentList

Display:

name

Submit:

id

When selected:

"departmentId": selectedDepartment.id

Do NOT hardcode departments.

====================================================
DROPDOWN: LEAVE POLICY
====================================================

Load Leave Policy from:

GET /leave-policy

Example response:

{
  "message": "Success",
  "leavePolicies": [
    {
      "id": 2011,
      "name": "Employee Policy",
      "officeId": 4699,
      "status": 1
    }
  ]
}

Use:

leavePolicies

Display:

name

Submit:

id

When selected:

"leavePolicyId": selectedLeavePolicy.id

Do NOT hardcode leave policies.

====================================================
DROPDOWN: EMPLOYMENT TYPE
====================================================

Load Employment Type from:

GET /employment-type

Example response:

{
  "message": "Success",
  "employmentTypeList": [
    {
      "id": 2880,
      "name": "Full-time",
      "officeId": 4699,
      "status": 1,
      "createdBy": 298780,
      "updatedBy": null,
      "creationDate": 1762150496496,
      "updateDate": 1762150496496,
      "subsidiaryId": null,
      "externalSyncId": null
    }
  ]
}

Use:

employmentTypeList

Display:

name

Submit:

id

When selected:

"employmentTypeId": selectedEmploymentType.id

Do NOT hardcode employment types.

====================================================
DROPDOWN: SHIFT GROUP
====================================================

Load Shift Group from:

GET /shift-group

Use the actual response returned by the Tipsoi API.

First inspect the response structure.

Determine the correct array property containing the shift groups.

For example, it may be something similar to:

shiftGroupList

or another property.

Do NOT assume the property name without checking the actual API response.

Display the shift group name.

Submit the corresponding ID as:

"shiftGroupId": selectedShiftGroup.id

Do NOT hardcode shift groups.

====================================================
API DATA LOADING
====================================================

The dropdown data should be loaded server-side through PHP wherever possible.

Recommended flow:

Browser
   ↓
PHP Controller
   ↓
Tipsoi API
   ↓
PHP Controller
   ↓
PHP View
   ↓
HTML form

Do not expose Tipsoi credentials/JWT in JavaScript.

If the existing project already has a reusable Tipsoi API service/helper, use it.

For example:

Controller
    ↓
Existing Tipsoi API helper/service
    ↓
GET /designation
GET /department
GET /leave-policy
GET /employment-type
GET /shift-group

Then pass all dropdown data to the view.

====================================================
CREATE API FLOW
====================================================

When the user clicks:

"Create Employee"

or the equivalent existing submit button:

1. Validate required fields in PHP.
2. Validate email.
3. Validate password.
4. Validate password confirmation.
5. Validate required dropdown values.
6. Build the exact Tipsoi payload.
7. Send POST request to the correct Tipsoi employee creation endpoint.
8. Use the existing Tipsoi authentication/JWT mechanism.
9. Handle the API response.
10. Show success/error message.
11. On success, redirect or stay on the page according to the existing project convention.

IMPORTANT:

The payload should look like:

{
  "name": "...",
  "employeeOfficeId": ...,
  "email": "...",
  "gender": ...,
  "password": "...",

  "reportPosition": null,

  "shiftId": null,
  "shiftGroupId": ...,
  "leavePolicyId": ...,

  "primaryDisplayText": null,
  "secondaryDisplayText": null,

  "departmentId": ...,
  "subDepartmentId": null,
  "designationId": ...,

  "employeeIdentifier": null,
  "weeklyOffDayPolicyId": null,
  "rfid": null,
  "personId3rdPartyDevice": null,

  "workplaceId": ...,

  "dateOfJoin": "...",
  "dateOfResignation": null,

  "employmentTypeId": ...,
  "subsidiaryId": null
}

Do not send confirmPassword to Tipsoi.

====================================================
VALIDATION
====================================================

Required fields:

- Name
- Employee ID
- Email
- Gender
- Password
- Confirm Password

Also validate required business fields according to what Tipsoi requires.

Password confirmation:

password === confirmPassword

If they don't match, do not call the API.

Show a friendly validation message.

Do not expose PHP exceptions or sensitive API errors directly.

====================================================
SUCCESS MESSAGE
====================================================

After successful employee creation, show a clean success alert/message.

If the project already uses SweetAlert or another alert library, reuse the existing implementation.

Example:

"Employee created successfully."

Do not use browser alert():

alert("...")

unless the existing project specifically uses it.

====================================================
ERROR HANDLING
====================================================

Handle:

- Tipsoi authentication failure
- JWT/token failure
- API timeout
- Network failure
- Validation errors
- Duplicate employee
- Duplicate email
- Invalid employee ID
- Invalid department
- Invalid designation
- Invalid leave policy
- Invalid employment type
- Invalid shift group
- Unknown Tipsoi API errors

Show a user-friendly error.

For example:

"Unable to create employee. Please check the information and try again."

If Tipsoi returns a useful validation message, display that message safely.

Never expose:
- JWT
- API secret
- Authorization header
- Internal stack trace
- PHP filesystem path

====================================================
ROUTES
====================================================

Inspect the current employee-management routes.

Add/update routes following the existing project convention.

There should be:

GET route
    → Display Create Employee form

POST route
    → Process form
    → Call Tipsoi create employee API

Do not create duplicate routes if suitable routes already exist.

====================================================
CONTROLLER
====================================================

Update the appropriate existing Controller.

Suggested responsibilities:

showCreateEmployee()
    - Fetch designation
    - Fetch department
    - Fetch leave policy
    - Fetch employment type
    - Fetch shift groups
    - Fetch workplace if an existing API exists
    - Return the Create Employee view

createEmployee()
    - Validate request
    - Build Tipsoi payload
    - Call Tipsoi employee creation API
    - Handle success/error
    - Redirect/return response

Use existing naming conventions if the project already has different method names.

Do not put large amounts of duplicated API logic directly inside the view.

====================================================
VIEW
====================================================

Update the existing PHP/Blade view.

The layout should approximately be:

CREATE EMPLOYEE

--------------------------------------------------

Name *                         Employee ID *
[ Name ]                       [ ID ]

Email *                        Password *
[ Email ]                      [ Password ]

Gender *                       Confirm Password *
[ Male ▼ ]                     [ Confirm Password ]

[ Advanced Options ▲ ]

Start Date                     Workplace
[ date ]                       [ Select Workplace ▼ ]

Designation
[ Select Designation ▼ ]

Department
[ Select Department ▼ ]

Leave Policy
[ Employee Policy ▼ ]

Employment Type
[ Select Employment Type ▼ ]

Shift Group
[ Select Shift Group ▼ ]

End Date
[ date ]

--------------------------------------------------

[ Create Employee ] [ Reset ]

Adjust the exact arrangement to match the existing screenshot and project's two-column form style.

IMPORTANT:
Do NOT include:

- Report Position ❌
- RFID ❌
- Primary Display Text ❌
- Secondary Display Text ❌
- PersonID 3rd Party Device ❌
- Sub Department ❌

====================================================
DROPDOWN UX
====================================================

Every dynamic dropdown should have:

- Placeholder such as "Select Designation"
- "Select Department"
- "Select Leave Policy"
- "Select Employment Type"
- "Select Shift Group"

Do not automatically select an arbitrary employee-related value.

If the API data is unavailable, show a proper error/empty state instead of silently using fake values.

====================================================
RESET
====================================================

The Reset button should clear the form and restore the initial/default state.

It must not reload the page unnecessarily unless that is how the existing project handles forms.

====================================================
SECURITY
====================================================

Do all Tipsoi API communication server-side.

Never place the Tipsoi Bearer token/JWT in:
- HTML
- hidden inputs
- JavaScript
- browser localStorage
- browser sessionStorage

Use the existing secure server-side authentication mechanism.

Also make sure passwords are never logged.

====================================================
IMPORTANT: EXISTING TIPSOI AUTHENTICATION
====================================================

The project already communicates with Tipsoi.

Previously there was a Tipsoi authentication issue:

{
  "message": "JWT claims string is empty.",
  "status": 401
}

Therefore:

DO NOT create a new JWT/authentication implementation.

Inspect how the existing Tipsoi Employee List / Tipsoi Employee Data page authenticates successfully.

Reuse exactly the same:
- Base URL
- Bearer token generation
- JWT claims
- Authorization header
- HTTP client
- Request headers

If the existing Employee List page successfully gets:

GET /employee

use that implementation as the reference.

====================================================
IMPORTANT: API RESPONSE INSPECTION
====================================================

Before implementing the dropdowns:

Actually inspect the existing API responses where possible.

Especially inspect:

GET /shift-group

Do not guess its response property.

Also verify:
- gender values
- create employee endpoint
- required fields
- employeeOfficeId format
- workplace ID format

Use the actual Tipsoi API contract.

====================================================
NO DATABASE DUPLICATION
====================================================

Do not create a local employee record unless the existing ERP architecture explicitly requires it.

The primary employee creation should happen in Tipsoi.

If the ERP currently synchronizes employees into a local table after creation, follow the existing synchronization architecture.

Do not invent a new employee database system.

====================================================
RESPONSIVE DESIGN
====================================================

The form must work correctly on:

- Desktop
- Laptop
- Tablet
- Mobile

Maintain the existing ERP responsive behavior.

On smaller screens, the two-column layout should stack appropriately.

Do not break the existing sidebar/navbar.

====================================================
TESTING
====================================================

After implementation, test the complete flow.

TEST 1:
Open Create Employee page.

TEST 2:
Verify all dropdowns load from Tipsoi:

- Designation → /designation
- Department → /department
- Leave Policy → /leave-policy
- Employment Type → /employment-type
- Shift Group → /shift-group

TEST 3:
Verify no Sub Department field exists.

TEST 4:
Verify the X-marked fields do not exist:

- Report Position
- RFID
- Primary Display Text
- Secondary Display Text
- PersonID 3rd Party Device

TEST 5:
Fill the form.

Example:

Name:
Testing 3

Employee ID:
3

Email:
testing3@example.com

Gender:
Male

Password:
Tipsoi@25

Confirm Password:
Tipsoi@25

Select:
- Department
- Designation
- Leave Policy
- Employment Type
- Shift Group

TEST 6:
Submit the form.

Verify the PHP backend creates the following payload correctly:

{
  "name": "Testing 3",
  "employeeOfficeId": 3,
  "email": "testing3@example.com",
  "gender": 1,
  "password": "Tipsoi@25",
  "reportPosition": null,
  "shiftId": null,
  "shiftGroupId": <selected shift group ID>,
  "leavePolicyId": <selected leave policy ID>,
  "primaryDisplayText": null,
  "secondaryDisplayText": null,
  "departmentId": <selected department ID>,
  "subDepartmentId": null,
  "designationId": <selected designation ID>,
  "employeeIdentifier": null,
  "weeklyOffDayPolicyId": null,
  "rfid": null,
  "personId3rdPartyDevice": null,
  "workplaceId": <selected workplace ID or null>,
  "dateOfJoin": <selected date or null>,
  "dateOfResignation": <selected date or null>,
  "employmentTypeId": <selected employment type ID>,
  "subsidiaryId": null
}

TEST 7:
Verify confirm password is NOT sent to Tipsoi.

TEST 8:
Verify success response.

TEST 9:
Verify API validation/error response.

TEST 10:
Verify invalid password confirmation.

TEST 11:
Verify empty dropdown/API response handling.

TEST 12:
Verify mobile layout.

TEST 13:
Verify existing Employee List and other HR/Payroll features still work.

====================================================
FINAL REQUIREMENT
====================================================

Do not just create the frontend UI.

This must be a COMPLETE working feature:

PHP Route
    ↓
PHP Controller
    ↓
Existing Tipsoi Authentication
    ↓
Tipsoi APIs
    ↓
Dropdown data
    ↓
PHP View
    ↓
Form submission
    ↓
PHP Controller
    ↓
Tipsoi Create Employee API
    ↓
Success/Error response

After implementation, provide a summary of:

1. Routes changed/added
2. Controller changed
3. View changed
4. Any helper/service changed
5. Any CSS/JS changed
6. Tipsoi endpoints used
7. Actual /shift-group response structure found
8. Create Employee API endpoint used
9. Payload mapping
10. Validation implemented
11. Testing performed
12. Any assumptions or issues

IMPORTANT:
Do not modify unrelated code.
Do not hardcode API dropdown values.
Do not use fake employee data.
Do not use React/Vue.
Do not expose JWT credentials.
Do not add Sub Department.
Do not add the X-marked fields.