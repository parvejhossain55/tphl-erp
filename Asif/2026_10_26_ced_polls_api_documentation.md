# CED Polls (Voting System) API Documentation
**Date**: October 26, 2026  
**Module**: Client Engagement Department (CED)  
**Target Applications**: ERP & Public Web / Client Portal / Mobile App  
**Base URL**: `http://<your-domain>/TPHL-ERP` (Local: `http://localhost/TPHL-ERP` or `http://127.0.0.1/TPHL-ERP`)  
**Authentication**: Open API (No authentication required)  
**Standard Response Format**: JSON (`{ success: boolean, message: string, data?: any }`)

---

## 📌 Table of Contents
1. [Core Features & Business Rules](#core-features--business-rules)
2. [Data Structure](#data-structure)
3. [API Endpoints Summary](#api-endpoints-summary)
4. [Endpoint Details & Examples](#endpoint-details--examples)
   - [1. Create Poll](#1-create-poll)
   - [2. Get All Polls](#2-get-all-polls)
   - [3. Get Project-Wise Polls](#3-get-project-wise-polls)
   - [4. Get Single Poll Details](#4-get-single-poll-details)
   - [5. Cast / Change Vote](#5-cast--change-vote)
   - [6. Update Poll](#6-update-poll)
   - [7. Delete Poll](#7-delete-poll)

---

## 💡 Core Features & Business Rules

1. **Open Access**: Both ERP Admin and Client Web apps can access these endpoints directly.
2. **Multipart Form-Data for Creation/Update**: Text data is passed as a JSON string inside the `data` field, while the banner image is uploaded in the `image` field.
3. **Vote Results Privacy Rule**:
   - **Before Voting**: Options are visible, but `votes_count` and `percentage` are **hidden** (`has_voted: false`).
   - **After Voting**: The user can see the vote count and percentage for each option, with `is_user_choice: true` marked on their selected option.
   - **Admin Access**: Passing `is_admin=1` in query parameters always shows total votes, counts, and percentages.
4. **Vote Change (Switching Choice)**:
   - A client can vote once per poll.
   - If a client casts another vote with a different `option_id`, their vote is **automatically updated** to the new choice without creating duplicate votes.
5. **Project-Specific Scope**: Polls can be tied to a specific `project_id` or left general for all projects.

---

## 🗄 Data Structure

### Poll Object
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | Integer | Unique poll identifier |
| `project_id` | Integer / null | Associated project ID |
| `project_name` | String / null | Joined project name |
| `title` | String | Poll title / question |
| `description` | String / null | Detailed description / instructions |
| `image` | String / null | Relative storage path (`uploads/polls/...`) |
| `image_url` | String / null | Fully qualified public URL to the image |
| `poll_type` | Enum | `'single_choice'` or `'multiple_choice'` |
| `status` | Enum | `'draft'`, `'active'`, `'closed'` |
| `start_date` | Date (YYYY-MM-DD) | Start date |
| `end_date` | Date (YYYY-MM-DD) | Expiration / deadline date |
| `is_expired` | Boolean | True if end_date has passed |
| `has_voted` | Boolean | True if the requesting `client_id` has voted |
| `user_voted_option_id`| Integer / null | ID of the option selected by the user |
| `total_votes` | Integer | Shown only if user has voted or admin request |
| `options` | Array | List of Poll Option objects |

### Poll Option Object
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | Integer | Option identifier |
| `poll_id` | Integer | ID of parent poll |
| `option_text` | String | Title / Text of the option |
| `sort_order` | Integer | Display sequence order |
| `is_user_choice` | Boolean | True if this option was chosen by the current `client_id` |
| `votes_count` | Integer | Total votes for this option *(visible only after voting)* |
| `percentage` | Float | Percentage share of total votes *(visible only after voting)* |

---

## 🚀 API Endpoints Summary

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/polls` | Create a new poll (Supports multipart `data` + `image`) |
| `GET` | `/api/polls` | List all polls (Filterable by `project_id`, `status`, `client_id`) |
| `GET` | `/api/polls/{id}` | Get single poll details (Pass `?client_id=X` to check voting status) |
| `GET` | `/api/polls/project/{projectId}` | Get all polls for a specific project |
| `POST` | `/api/polls/vote` | Submit a vote or change an existing vote |
| `POST` | `/api/polls/update/{id}` | Update poll details or replace image |
| `POST` | `/api/polls/delete/{id}` | Delete a poll and its associated data/images |

---

## 📖 Endpoint Details & Examples

### 1. Create Poll
Creates a new poll along with 2 or more options and an optional banner image.

- **Method**: `POST`
- **URL**: `/api/polls` or `/api/polls/create`
- **Content-Type**: `multipart/form-data`

#### Form-Data Parameters:
- **`data`** *(Text, Required)*: JSON string containing:
  ```json
  {
    "title": "Which Rooftop Garden Design Do You Prefer?",
    "description": "Please cast your vote to help us finalize the rooftop layout.",
    "project_id": 1,
    "end_date": "2026-12-31",
    "options": [
      "Design A: Modern Zen Garden with Pergola",
      "Design B: Green Lawn with Coffee Corner",
      "Design C: Infinity Walkway with Flowering Plants"
    ]
  }
  ```
- **`image`** *(File, Optional)*: Image file (`.jpg`, `.jpeg`, `.png`, `.webp`). Maximum size: 10MB.

#### Response (201 Created):
```json
{
  "success": true,
  "message": "Poll created successfully.",
  "data": {
    "id": 1,
    "project_id": 1,
    "title": "Which Rooftop Garden Design Do You Prefer?",
    "description": "Please cast your vote to help us finalize the rooftop layout.",
    "image": "uploads/polls/poll_20261026_123000_a1b2c3d4.jpg",
    "image_url": "/TPHL-ERP/uploads/polls/poll_20261026_123000_a1b2c3d4.jpg",
    "poll_type": "single_choice",
    "status": "active",
    "start_date": "2026-10-26",
    "end_date": "2026-12-31",
    "is_expired": false,
    "has_voted": false,
    "user_voted_option_id": null,
    "options": [
      { "id": 1, "poll_id": 1, "option_text": "Design A: Modern Zen Garden with Pergola", "sort_order": 0 },
      { "id": 2, "poll_id": 1, "option_text": "Design B: Green Lawn with Coffee Corner", "sort_order": 1 },
      { "id": 3, "poll_id": 1, "option_text": "Design C: Infinity Walkway with Flowering Plants", "sort_order": 2 }
    ]
  }
}
```

---

### 2. Get All Polls
Fetch a list of polls with optional filters.

- **Method**: `GET`
- **URL**: `/api/polls`
- **Query Parameters**:
  - `project_id` *(int, optional)*: Filter by project (e.g. `?project_id=1`)
  - `status` *(string, optional)*: `'active'`, `'closed'`, or `'draft'` (e.g. `?status=active`)
  - `client_id` *(int, optional)*: Pass customer ID to attach individual voting state (e.g. `?client_id=101`)
  - `is_admin` *(boolean, optional)*: Set `1` to show all voting counts and percentages regardless of client vote state.

#### Example URL:
`GET /api/polls?project_id=1&client_id=101`

#### Response:
```json
{
  "success": true,
  "message": "Polls retrieved successfully.",
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "project_name": "Premium Heights",
      "title": "Which Rooftop Garden Design Do You Prefer?",
      "image_url": "/TPHL-ERP/uploads/polls/poll_20261026_123000_a1b2c3d4.jpg",
      "status": "active",
      "end_date": "2026-12-31",
      "has_voted": false,
      "user_voted_option_id": null,
      "options": [
        { "id": 1, "poll_id": 1, "option_text": "Design A", "sort_order": 0, "is_user_choice": false },
        { "id": 2, "poll_id": 1, "option_text": "Design B", "sort_order": 1, "is_user_choice": false }
      ]
    }
  ]
}
```

---

### 3. Get Project-Wise Polls
Convenience endpoint for retrieving all polls of a specific project.

- **Method**: `GET`
- **URL**: `/api/polls/project/{projectId}`
- **Query Parameters**: `?client_id=101`, `?status=active`

#### Example URL:
`GET /api/polls/project/1?client_id=101`

---

### 4. Get Single Poll Details
Fetch full details of a specific poll.

- **Method**: `GET`
- **URL**: `/api/polls/{id}`
- **Query Parameters**: `?client_id=101`

#### A. When Client HAS NOT Voted:
```json
{
  "success": true,
  "message": "Poll details retrieved.",
  "data": {
    "id": 1,
    "title": "Which Rooftop Garden Design Do You Prefer?",
    "description": "Please cast your vote...",
    "image_url": "/TPHL-ERP/uploads/polls/poll_20261026_123000_a1b2c3d4.jpg",
    "has_voted": false,
    "user_voted_option_id": null,
    "options": [
      { "id": 1, "poll_id": 1, "option_text": "Design A", "sort_order": 0, "is_user_choice": false },
      { "id": 2, "poll_id": 1, "option_text": "Design B", "sort_order": 1, "is_user_choice": false }
    ]
  }
}
```

#### B. When Client HAS Voted:
*(Notice `votes_count`, `percentage`, `total_votes`, and `is_user_choice` are now exposed)*
```json
{
  "success": true,
  "message": "Poll details retrieved.",
  "data": {
    "id": 1,
    "title": "Which Rooftop Garden Design Do You Prefer?",
    "image_url": "/TPHL-ERP/uploads/polls/poll_20261026_123000_a1b2c3d4.jpg",
    "has_voted": true,
    "user_voted_option_id": 2,
    "total_votes": 10,
    "options": [
      {
        "id": 1,
        "poll_id": 1,
        "option_text": "Design A",
        "sort_order": 0,
        "is_user_choice": false,
        "votes_count": 3,
        "percentage": 30.0
      },
      {
        "id": 2,
        "poll_id": 1,
        "option_text": "Design B",
        "sort_order": 1,
        "is_user_choice": true,
        "votes_count": 7,
        "percentage": 70.0
      }
    ]
  }
}
```

---

### 5. Cast / Change Vote
Used by a client to submit their vote or switch their vote to another option.

- **Method**: `POST`
- **URL**: `/api/polls/vote`
- **Content-Type**: `application/json`

#### Request Body:
```json
{
  "poll_id": 1,
  "option_id": 2,
  "client_id": 101,
  "remarks": "I prefer Design B for the green lawn."
}
```

#### Response (200 OK):
Returns the updated poll object with live vote counts and percentages immediately.
```json
{
  "success": true,
  "message": "Vote recorded successfully.",
  "data": {
    "id": 1,
    "title": "Which Rooftop Garden Design Do You Prefer?",
    "has_voted": true,
    "user_voted_option_id": 2,
    "total_votes": 1,
    "options": [
      {
        "id": 1,
        "option_text": "Design A",
        "is_user_choice": false,
        "votes_count": 0,
        "percentage": 0
      },
      {
        "id": 2,
        "option_text": "Design B",
        "is_user_choice": true,
        "votes_count": 1,
        "percentage": 100
      }
    ]
  }
}
```

---

### 6. Update Poll
Updates poll properties, status, end date, or replaces the banner image.

- **Method**: `POST`
- **URL**: `/api/polls/update/{id}`
- **Content-Type**: `application/json` OR `multipart/form-data`

#### JSON Body Example:
```json
{
  "title": "Which Rooftop Garden Design Do You Prefer? [Final Call]",
  "description": "Deadline extended to mid November.",
  "status": "active",
  "end_date": "2026-11-15"
}
```

#### Multipart Form-Data Example (if updating image):
- `data`: `{"title": "Updated Title", "end_date": "2026-11-15"}`
- `image`: *(New Image File)*

---

### 7. Delete Poll
Deletes a poll, cascades delete to all associated options and votes, and removes the uploaded banner image from the server.

- **Method**: `POST` (or `DELETE`)
- **URL**: `/api/polls/delete/{id}`

#### Response:
```json
{
  "success": true,
  "message": "Poll deleted successfully."
}
```
