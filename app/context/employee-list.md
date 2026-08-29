I need to add an Employee List section to the existing "My Tipsoi Information" page.

IMPORTANT PROJECT DETAILS:
- This project is PHP-based.
- Do NOT use React, Vue, Angular, or any SPA framework.
- The frontend HTML/CSS is rendered and sent from the PHP server.
- Follow the existing project architecture and coding style.
- Do not rewrite or restructure unrelated parts of the project.
- First inspect the existing Controller, Route, View, API/service/helper structure, authentication/JWT handling, and existing Tipsoi API integrations before making changes.

CURRENT PAGE:
"My Tipsoi Information"

There are already tabs/sections such as:
- My Profile
- Attendance Data
- My Leave Balance
- Pending Requests
- Approval Pending
- Pending Requests Filter
- Applied Leave List
- Apply Leave

I want to add a new "Employee List" section/tab that displays ALL employees returned from the Tipsoi API.

TIPSOI API:
GET /employee

The employee data must come from the Tipsoi API endpoint:
GET /employee

Do NOT create a separate local employee table/database unless the existing architecture absolutely requires it. The source of truth for the employee list should be Tipsoi's /employee endpoint.

==================================================
1. BACKEND REQUIREMENTS
==================================================

Inspect the existing backend and identify how the application currently communicates with Tipsoi.

Reuse the existing:
- Base Tipsoi URL
- Authentication mechanism
- JWT/token generation
- HTTP client
- Headers
- Error handling
- API response handling

Do NOT create a second/different Tipsoi authentication system.

Create/update the appropriate PHP Controller method to fetch employees from:

GET /employee

The controller should:
- Call Tipsoi /employee.
- Handle authentication correctly.
- Handle successful API responses.
- Handle empty employee responses.
- Handle Tipsoi API errors.
- Handle timeout/network errors.
- Avoid exposing sensitive credentials or tokens to the browser.
- Pass the employee data to the PHP view.

If the existing project has a reusable Tipsoi API service/helper, use it instead of duplicating API logic.

IMPORTANT:
Check the actual response structure of the existing Tipsoi /employee API integration before mapping fields.

Do not blindly assume field names.

==================================================
2. ROUTE
==================================================

Add an appropriate route for the Employee List page/section following the existing routing convention.

For example, if appropriate:

GET /my-tipsoi-info/employees

or use the existing My Tipsoi Information route structure.

Do NOT introduce a new URL structure if the project already has a standard route pattern.

Make sure authentication/middleware is applied exactly like the other My Tipsoi Information pages.

==================================================
3. FRONTEND / VIEW
==================================================

The frontend is server-rendered PHP HTML.

Update the appropriate PHP Blade/PHP view used by:

"My Tipsoi Information"

Add an "Employee List" tab/menu item near the existing Tipsoi-related tabs.

The new tab should match the existing UI design.

The screenshot I provided shows the current design.

Keep the existing:
- Navbar
- Sidebar
- Breadcrumb
- Typography
- Colors
- Icons
- Borders
- Spacing
- Buttons
- Responsive behavior

Do NOT redesign the whole page.

Add an Employee List section that displays all employees.

Suggested tab position:

My Profile
Attendance Data
My Leave Balance
Pending Requests
Approval Pending
Employee List
Apply Leave
Applied Leave List

However, place it wherever it makes the most sense according to the existing UI structure.

==================================================
4. EMPLOYEE TABLE
==================================================

Display the employees in a clean responsive table.

First inspect the actual /employee API response and use the meaningful fields returned by Tipsoi.

At minimum, if available, display fields such as:

- Employee ID
- Employee Name
- Employee Code
- Email
- Phone
- Department
- Designation
- Status

Only display fields that actually exist in the API response.

Do not invent API fields.

If the API uses different names, map them correctly.

Example:

Employee ID | Name | Employee Code | Email | Phone | Department | Designation | Status

The table should:
- Show all employees.
- Have a proper table header.
- Be responsive on smaller screens.
- Use horizontal scrolling on mobile if necessary.
- Maintain the existing project's styling.
- Have proper empty-state handling.

==================================================
5. SEARCH / FILTER
==================================================

If practical within the existing architecture, add a simple employee search field.

The search should allow searching by:
- Employee ID
- Name
- Employee Code
- Email

Do not add a complicated JavaScript framework.

Use lightweight existing JavaScript only if the project already uses it.

If server-side filtering is more consistent with the current project, follow the existing approach.

==================================================
6. LOADING / ERROR / EMPTY STATES
==================================================

The page must handle these cases properly.

SUCCESS:
Display the employee list.

EMPTY:
Show a friendly message such as:

"No employees found."

API ERROR:
Show a proper error message such as:

"Unable to load employee data from Tipsoi. Please try again."

Do not expose:
- JWT
- API credentials
- Internal exceptions
- Stack traces
- Sensitive API response information

If the project already has a standard alert/error component, use it.

==================================================
7. IMPORTANT: EXISTING LEAVE MANAGEMENT
==================================================

Do NOT break any existing functionality.

Especially verify that these existing sections still work:

- My Profile
- Attendance Data
- My Leave Balance
- Pending Requests
- Approval Pending
- Pending Requests Filter
- Applied Leave List
- Apply Leave

The existing Leave API integrations must continue working.

Do not modify their behavior unless required to integrate the Employee List.

==================================================
8. EMPLOYEE DATA FOR OTHER FEATURES
==================================================

The Employee List should be based on Tipsoi /employee data.

If the existing application already needs an employee ID for:
- Leave management
- Attendance
- Approval
- Other Tipsoi operations

make sure the correct Tipsoi employee ID is preserved in the rendered data.

Do not replace Tipsoi employee IDs with local/generated IDs.

==================================================
9. SECURITY
==================================================

Follow the existing authentication system.

The Tipsoi API token/JWT must remain server-side.

The browser should receive only the employee data required for rendering.

Do not put:
- API secret
- JWT generation logic
- Tipsoi credentials

inside HTML or JavaScript.

==================================================
10. CODE QUALITY
==================================================

Before coding:

1. Find the current My Tipsoi Information controller.
2. Find its routes.
3. Find the corresponding PHP/Blade views.
4. Find existing Tipsoi API calls.
5. Find how JWT/authentication is currently generated.
6. Find whether there is an existing Tipsoi API helper/service.
7. Inspect the existing response handling.
8. Inspect the existing CSS/classes/components used by this page.

Then implement the Employee List using the existing architecture.

Avoid duplicate code.

Reuse existing functions/components/classes wherever possible.

==================================================
11. RESPONSIVE DESIGN
==================================================

The Employee List must work on:

- Desktop
- Laptop
- Tablet
- Mobile

On mobile, the employee table should not break the page layout.

Use a responsive table wrapper with horizontal scrolling if necessary.

Do not change the existing desktop layout unnecessarily.

==================================================
12. TESTING
==================================================

After implementation, test:

1. Open My Tipso Information.
2. Open Employee List.
3. Verify employees are loaded from Tipsoi /employee.
4. Verify all employees are displayed.
5. Verify employee fields are mapped correctly.
6. Verify empty API response.
7. Verify Tipsoi API error.
8. Verify authentication/JWT handling.
9. Verify search if implemented.
10. Verify mobile responsiveness.
11. Verify existing Leave Management pages still work.
12. Verify there are no PHP errors/warnings.
13. Verify there are no console errors.
14. Verify there are no broken routes.

IMPORTANT:
Do not just make the UI static.

The Employee List MUST be connected to the real Tipsoi:

GET /employee

API.

==================================================
13. FINAL RESPONSE
==================================================

After completing the work, clearly report:

- Which Controller was changed
- Which Route was added/changed
- Which View was changed
- Which service/helper/API integration was changed
- Which CSS/JS files were changed, if any
- How the Tipsoi /employee API response was mapped
- Any assumptions made about the API response
- What was tested
- Whether existing Tipsoi/Leave functionality still works

Do not modify unrelated files.