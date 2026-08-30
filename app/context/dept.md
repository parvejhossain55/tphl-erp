You are working on our existing TPHL ERP project.

I want you to create a new management page under the existing:

"Tipsoi Employee Data"

section in the sidebar.

The new page should manage both:

1. Departments
2. Designations

The page must support complete CRUD functionality:

- Create
- View/List
- Update
- Delete

IMPORTANT:
- Do NOT create a separate/demo application.
- Work inside the existing TPHL ERP project.
- First inspect the existing project structure and understand the current Tipsoi Employee Data section.
- Reuse the existing sidebar, navbar, layout, API utilities, authentication/JWT handling, modal components, table components, SweetAlert/toast system, loading states, and styling.
- Do NOT change the global theme.
- Do NOT break any existing functionality.
- Use the existing TPHL ERP theme and UI conventions.

==================================================
1. SIDEBAR
==================================================

Under:

Tipsoi Employee Data

add a new menu item:

"Department & Designation"

For example:

Tipsoi Employee Data
    ├── Tipsoi Employees
    ├── Create Employee
    ├── Tipsoi Attendance
    ├── Tipsoi Leave History
    ├── Tipsoi Leave Balances
    ├── Monthly LWP
    ├── All Pending Requests
    ├── Email Log for Pending Req
    ├── Tipsoi Holidays
    ├── Day Off Lists
    └── Department & Designation

Use the existing sidebar icon/style.

The new route should follow the existing routing conventions.

Suggested route:

/tipsoi-department-designation

However, inspect the existing project routing structure first and use the project's naming convention if another route format is preferred.

==================================================
2. PAGE TITLE
==================================================

Page title:

"Department & Designation Management"

Use the same page-header/breadcrumb structure as the existing Tipsoi Employee Data pages.

Example:

Department & Designation Management

Breadcrumb:

Employee
>
Tipsoi Employee Data
>
Department & Designation

Follow the existing project UI.

==================================================
3. PAGE STRUCTURE
==================================================

The page should have TWO separate management sections.

SECTION 1:

"Department Management"

SECTION 2:

"Designation Management"

Each section should be displayed as a clean ERP card.

Each section should contain:

- Section title
- Add/Create button
- Search field
- Data table
- Edit action
- Delete action
- Loading state
- Empty state
- Error state

Suggested layout:

--------------------------------------------------

Department Management

[ Search Department................ ] [ + Add Department ]

--------------------------------------------------
| S/N | Name | Hierarchy | Status | Office ID | Actions |
--------------------------------------------------
| 1   | Management | 1 | Active | 4699 | Edit Delete |
| 2   | Engineering | 1 | Active | 4699 | Edit Delete |
--------------------------------------------------


--------------------------------------------------

Designation Management

[ Search Designation............... ] [ + Add Designation ]

--------------------------------------------------
| S/N | Name | Hierarchy | Status | Office ID | Actions |
--------------------------------------------------
| 1   | CEO | 1 | Active | 4699 | Edit Delete |
| 2   | Software Tester | 1 | Active | 4699 | Edit Delete |
--------------------------------------------------

==================================================
4. DEPARTMENT API
==================================================

CREATE:

POST /department

Request body:

{
  "name": "Support",
  "hierarchy": 1,
  "officeId": 4397
}

When creating a department:

- Show a modal/form.
- Required field:
  - Name
- Hierarchy should be an input/select according to the existing project conventions.
- Office ID should be handled appropriately.

Do NOT hardcode office IDs if the existing application already has the authenticated user's office/company/Tipsoi office information available.

If the existing application has an office ID stored in the authenticated user/context/configuration, use that.

If the API requires the officeId and there is no existing dynamic office ID source, inspect the current Tipsoi integration before deciding how to handle it.

--------------------------------------------------

RETRIEVE:

GET /department

Response:

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

Use:

departmentList

as the source for the Department table.

Display at minimum:

- S/N
- Department Name
- Hierarchy
- Office ID
- Status
- Actions

You may display additional useful fields if they fit the existing UI.

--------------------------------------------------

UPDATE:

POST /department/{id}

Example:

POST /department/23186

Request body:

{
  "name": "Engineering",
  "hierarchy": 1,
  "status": 1
}

The ID must be dynamic.

Do NOT hardcode the department ID.

When clicking Edit:

- Open the update modal.
- Populate the existing department information.
- Allow editing:
  - Name
  - Hierarchy
  - Status
- Submit to:
  POST /department/{id}

--------------------------------------------------

DELETE:

POST /department/{id}

The ID must come from the selected department.

Do NOT hardcode IDs.

Before deleting:

Show a SweetAlert confirmation.

Example:

"Are you sure you want to delete the department 'Engineering'?"

Buttons:

Cancel
Delete

Do NOT use:

alert()
confirm()

After successful deletion:

- Show success toast/SweetAlert.
- Refresh the department list.
- Remove the deleted department from the table.
- Do not reload the entire browser page.

==================================================
5. DESIGNATION API
==================================================

CREATE:

POST /designation

Request body:

{
  "name": "Software Tester",
  "hierarchy": 1,
  "status": 1
}

When creating a designation:

Show a modal/form with:

- Name
- Hierarchy
- Status

--------------------------------------------------

RETRIEVE:

GET /designation

Response:

{
  "message": "Success",
  "designationList": [
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

Use:

designationList

as the source for the Designation table.

Display at minimum:

- S/N
- Designation Name
- Hierarchy
- Office ID
- Status
- Actions

--------------------------------------------------

UPDATE:

POST /designation/{id}

Example:

POST /designation/23186

Request body:

{
  "name": "Engineering",
  "hierarchy": 1,
  "status": 1
}

The ID must be dynamic.

When clicking Edit:

- Open update modal.
- Populate existing designation data.
- Allow editing:
  - Name
  - Hierarchy
  - Status
- Submit to:
  POST /designation/{id}

--------------------------------------------------

DELETE:

POST /designation/{id}

The ID must be dynamically taken from the selected designation.

Before deleting:

Show a SweetAlert confirmation.

Example:

"Are you sure you want to delete the designation 'Software Tester'?"

Buttons:

Cancel
Delete

Do NOT use browser confirm().

After successful deletion:

- Show success notification.
- Refresh the designation list.
- Remove the deleted designation from the table.
- Do not reload the browser.

==================================================
6. CREATE MODALS
==================================================

Use the existing project's modal component if available.

Department Create Modal:

----------------------------------------
        Create Department
----------------------------------------

Department Name
[________________________]

Hierarchy
[________________________]

Office ID
[________________________]

              [Cancel] [Create]
----------------------------------------

Designation Create Modal:

----------------------------------------
        Create Designation
----------------------------------------

Designation Name
[________________________]

Hierarchy
[________________________]

Status
[ Active ▼ ]

              [Cancel] [Create]
----------------------------------------

Follow the project's existing form styling.

Do not create a completely new visual style.

==================================================
7. UPDATE MODALS
==================================================

Department Update Modal:

----------------------------------------
        Update Department
----------------------------------------

Department Name
[ Engineering              ]

Hierarchy
[ 1                       ]

Status
[ Active ▼ ]

              [Cancel] [Update]
----------------------------------------

Designation Update Modal:

----------------------------------------
        Update Designation
----------------------------------------

Designation Name
[ Software Tester          ]

Hierarchy
[ 1                       ]

Status
[ Active ▼ ]

              [Cancel] [Update]
----------------------------------------

Pre-populate the fields from the selected row.

==================================================
8. STATUS
==================================================

The API uses:

status: 1

for active.

Handle status correctly.

Recommended UI:

1 → Active
0 → Inactive

Display a professional badge:

Active
Inactive

When updating, provide a dropdown:

Active
Inactive

and submit:

1

or

0

Do not submit the text "Active" or "Inactive" to the API.

==================================================
9. SEARCH
==================================================

Add search functionality separately to each section.

Department:

Search by department name.

Designation:

Search by designation name.

Search should be client-side if the GET API returns the complete list.

Do not add unnecessary API requests for every keystroke.

Example:

Department Management
[ Search department... ]

Designation Management
[ Search designation... ]

Search should be case-insensitive.

==================================================
10. LOADING STATE
==================================================

Each section should have its own loading state.

For example:

Department API loading
→ Department section shows loading.

Designation API loading
→ Designation section shows loading.

Do not block the entire page if only one API is loading.

Reuse the project's existing loading/skeleton component.

==================================================
11. EMPTY STATE
==================================================

If there are no departments:

"No departments found."

If there are no designations:

"No designations found."

The empty state should appear inside the corresponding table/card.

Do not show fake data.

==================================================
12. ERROR HANDLING
==================================================

Handle API errors properly.

If Department API fails:

- Show error only in Department section.
- Designation section should still work.

If Designation API fails:

- Show error only in Designation section.
- Department section should still work.

Handle:

- 401
- 403
- 404
- 422
- 500
- Network errors

Use the existing project's API error handling.

Do not expose JWT/token information.

==================================================
13. SUCCESS NOTIFICATIONS
==================================================

After successful operations use the existing project's notification system.

Examples:

Department created successfully.
Department updated successfully.
Department deleted successfully.

Designation created successfully.
Designation updated successfully.
Designation deleted successfully.

Prefer SweetAlert/toast already used in the project.

Do not introduce another notification library.

==================================================
14. TABLE ACTIONS
==================================================

Each row must have:

Edit
Delete

Use icons consistent with the existing ERP.

For example:

✏️ Edit
🗑 Delete

But preferably use the project's existing icon library.

Buttons should have tooltips if the project already supports them.

==================================================
15. API SERVICE ARCHITECTURE
==================================================

Before implementation, inspect the existing project.

Find:

- Tipsoi API service
- Axios instance
- Base URL
- JWT/token interceptor
- Existing API utility functions
- Existing Tipsoi employee APIs

Reuse them.

Do NOT create a second Axios configuration.

Do NOT duplicate JWT handling.

If the project has a centralized API service, add:

getDepartments()
createDepartment()
updateDepartment()
deleteDepartment()

and:

getDesignations()
createDesignation()
updateDesignation()
deleteDesignation()

following the existing naming conventions.

==================================================
16. DATA HANDLING
==================================================

Do not assume the API always returns perfect data.

Safely handle:

- null
- undefined
- empty arrays
- missing fields
- unexpected response structure

For Department:

response.departmentList

For Designation:

response.designationList

Normalize data if necessary.

Do not silently hide API errors.

==================================================
17. DUPLICATE SUBMISSIONS
==================================================

While creating/updating/deleting:

- Disable the corresponding button.
- Show loading state.
- Prevent multiple clicks.
- Re-enable after request completion.

Example:

[ Creating... ]

instead of:

[ Create ]

==================================================
18. DELETE SAFETY
==================================================

Deletion is destructive.

Always ask for confirmation.

For example:

----------------------------------------
Delete Department?

Are you sure you want to delete
"Engineering"?

This action cannot be undone.

[Cancel] [Delete]
----------------------------------------

Use SweetAlert or the project's existing confirmation component.

==================================================
19. RESPONSIVE DESIGN
==================================================

The page must work properly on:

- Desktop
- Laptop
- Tablet
- Mobile

Tables should use the existing responsive table pattern.

On mobile:

- Allow horizontal scrolling where necessary.
- Keep action buttons accessible.
- Do not make the table unreadable.

Do not damage the current desktop layout.

==================================================
20. UI / THEME
==================================================

Use the existing TPHL ERP theme.

The current application uses:

- White cards
- Light background
- Green/teal brand colors
- Clean typography
- Rounded borders
- Existing sidebar/navbar
- Existing icons
- Professional ERP styling

Do not introduce a completely different color palette.

Do not modify global CSS unless absolutely necessary.

Reuse existing Tailwind classes/components.

==================================================
21. PAGE REFRESH
==================================================

After:

CREATE:
→ refresh relevant list.

UPDATE:
→ update or refresh relevant list.

DELETE:
→ remove/update relevant row and refresh count if applicable.

Do not perform a full browser reload.

Prefer updating local state when appropriate.

==================================================
22. IMPORTANT ROUTING REQUIREMENT
==================================================

Add the new page to the application's existing routing system.

Protect the route using the same authentication/authorization mechanism used by the other Tipsoi Employee Data pages.

Do not create a separate authentication system.

==================================================
23. IMPORTANT: DO NOT BREAK EXISTING FEATURES
==================================================

The following existing features must continue working:

- Tipsoi Employees
- Create Employee
- Tipsoi Attendance
- Tipsoi Leave History
- Tipsoi Leave Balances
- Monthly LWP
- All Pending Requests
- Email Log for Pending Requests
- Tipsoi Holidays
- Day Off Lists
- Sidebar navigation
- Navbar
- Authentication
- API authentication

Do not modify unrelated pages.

==================================================
24. CODE QUALITY
==================================================

Keep the implementation clean and maintainable.

Avoid unnecessary duplication.

If Department and Designation tables have similar functionality, consider reusable components where appropriate.

For example:

<ManagementTable />
<ManagementModal />

But do not over-engineer.

Follow the existing project's architecture first.

==================================================
25. FINAL VERIFICATION
==================================================

After implementation:

1. Run the application.
2. Open the new Department & Designation page.
3. Verify the sidebar menu appears under Tipsoi Employee Data.
4. Verify the route works.
5. Verify Department GET works.
6. Verify Designation GET works.
7. Verify Department table displays correctly.
8. Verify Designation table displays correctly.
9. Test Department search.
10. Test Designation search.
11. Create a Department.
12. Verify POST /department is called with the correct body.
13. Verify the new department appears.
14. Edit a Department.
15. Verify POST /department/{id}.
16. Verify the correct ID is used.
17. Delete a Department.
18. Verify confirmation appears.
19. Verify POST /department/{id}.
20. Verify the department disappears.
21. Create a Designation.
22. Verify POST /designation.
23. Edit a Designation.
24. Verify POST /designation/{id}.
25. Delete a Designation.
26. Verify POST /designation/{id}.
27. Verify success/error notifications.
28. Verify loading states.
29. Verify empty states.
30. Verify responsive behavior.
31. Check browser console.
32. Make sure there are no React warnings.
33. Make sure there are no duplicate API calls.
34. Make sure JWT/authentication is handled through the existing system.
35. Make sure no existing page/functionality is broken.

==================================================
MOST IMPORTANT RULES
==================================================

DO NOT:

- hardcode IDs
- hardcode API data
- create fake departments/designations
- create fake API responses
- create a new authentication system
- expose JWT tokens
- use alert()
- use confirm()
- create duplicate Axios configuration
- change the global ERP theme
- modify unrelated pages
- break existing Tipsoi Employee Data functionality

Use the real Tipsoi APIs:

DEPARTMENT

POST /department
GET /department
POST /department/{id}
POST /department/{id}


DESIGNATION

POST /designation
GET /designation
POST /designation/{id}
POST /designation/{id}


The final page should feel like a native part of the existing TPHL ERP application and should provide a professional CRUD interface for managing Tipsoi Departments and Designations.