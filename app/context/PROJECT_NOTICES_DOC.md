# Project Notices - Complete API & Implementation Guide

This document contains full implementation details, API specifications, and frontend workflow for the **Project Notices** module. You can use this guide to implement the exact same feature in another project using the same backend APIs.

---

## 📌 Overview

The **Project Notices** module allows:
1. **CED :**
   - Select any project using a searchable dropdown.
   - View all existing broadcast notices for that specific project.
   - Create and broadcast new notices with a title, description, and image attachment.
   - Delete outdated or unwanted notices.

---

## ⚙️ Environment Configuration

Ensure your `.env` file contains the API base URL:
```env
WEB_API_BASE_URL=https://api.dpremiumhomes.com/api
```

---

## 📡 API Endpoints Reference

| Action | Method | Endpoint | Auth Required? | Payload Format |
|---|---|---|---|---|
| **1. Fetch All Projects** | `GET` | `/projects` | Optional / Public | None |
| **2. Fetch Project Notices** | `GET` | `/projects/{projectId}/notices` | Public / User | None |
| **3. Create New Notice** | `POST` | `/project-notices` | ✅ Bearer Token  | `multipart/form-data` (`FormData`) |
| **4. Delete Notice** | `DELETE` | `/project-notices/{id}` | ✅ Bearer Token  | None |
| **5. Record View Count** | `POST` | `/projects/notices/{noticeId}/view` | Public / User | `application/json` |

---

## 📋 API Details & Data Contracts

### 1. Fetch All Projects
Loads all available projects for the searchable dropdown.

* **Method:** `GET`
* **URL:** `${WEB_API_BASE_URL}/projects`
* **Headers:**
  ```http
  Accept: application/json
  ```
* **Success Response (`200 OK`):**
  ```json
  [
    {
      "id": 1,
      "name": "The Grand Residence",
      "community": "Gulshan 2, Dhaka",
      "image": "https://example.com/uploads/project1.jpg"
    },
    {
      "id": 5,
      "name": "Skyline Heights",
      "community": "Banani, Dhaka",
      "image": "https://example.com/uploads/project5.jpg"
    }
  ]
  ```

---

### 2. Fetch Notices for a Project
Retrieves all notices previously posted under a selected project.

* **Method:** `GET`
* **URL:** `${WEB_API_BASE_URL}/projects/{projectId}/notices`
  * Example: `${WEB_API_BASE_URL}/projects/5/notices`
* **Headers:**
  ```http
  Accept: application/json
  ```
* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "notices": [
      {
        "id": 12,
        "project_id": 5,
        "title": "Roof Slab Casting Completed",
        "description": "We are pleased to announce that 8th floor roof slab casting has been successfully completed today.",
        "image": "/uploads/notices/slab-casting-123.jpg",
        "views_count": 24,
        "created_at": "2026-08-20T10:30:00.000000Z",
        "updated_at": "2026-08-20T10:30:00.000000Z"
      }
    ]
  }
  ```

---

### 3. Create / Broadcast a Notice
Admin sends a notice to a specific project. Supports image file attachment.

* **Method:** `POST`
* **URL:** `${WEB_API_BASE_URL}/project-notices`
* **Headers:**
  ```http
  Authorization: Bearer <AUTH_TOKEN>
  Accept: application/json
  ```
  > ⚠️ **Important:** Do NOT manually set `Content-Type: multipart/form-data`. When you pass a `new FormData()` object to `fetch` or `axios`, the browser automatically sets the correct `Content-Type` with boundary string.

* **Request Body (`FormData`):**
  | Field Name | Type | Required? | Description / Example |
  |---|---|---|---|
  | `project_id` | String / Number | ✅ **Required** | The target project ID (e.g. `5`) |
  | `description` | String (Text) | ✅ **Required** | The body text of the notice |
  | `title` | String | ❌ Optional | Short title or headline (e.g. `"Important Site Notice"`) |
  | `image` | Binary File | ❌ Optional | Image file attachment (PNG, JPG, JPEG, GIF up to 5MB) |

* **Code Example (Fetch):**
  ```javascript
  const formData = new FormData();
  formData.append('project_id', selectedProjectId);
  if (title) formData.append('title', title);
  formData.append('description', description);
  if (imageFile) formData.append('image', imageFile);

  const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/project-notices`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${adminToken}`,
      'Accept': 'application/json'
    },
    body: formData
  });

  const data = await response.json();
  ```

* **Success Response (`200 OK` or `201 Created`):**
  ```json
  {
    "success": true,
    "message": "Notice posted successfully",
    "notice": {
      "id": 13,
      "project_id": 5,
      "title": "Important Site Notice",
      "description": "Please be advised of scheduled maintenance...",
      "image": "/uploads/notices/maintenance.jpg",
      "views_count": 0,
      "created_at": "2026-09-07T10:00:00.000000Z"
    }
  }
  ```

---

### 4. Delete Notice
Removes a notice by its ID.

* **Method:** `DELETE`
* **URL:** `$WEB_API_BASE_URL}/project-notices/{id}`
  * Example: `${WEB_API_BASE_URL}/project-notices/12`
* **Headers:**
  ```http
  Authorization: Bearer <ADMIN_AUTH_TOKEN>
  Accept: application/json
  ```
* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Notice deleted"
  }
  ```

---

## 💻 Admin Panel Implementation Logic (`AdminProjectNotices.jsx`)

### 1. State Variables:
```javascript
const [projects, setProjects] = useState([]);               // All projects list
const [selectedProjectId, setSelectedProjectId] = useState(''); // Active project ID
const [notices, setNotices] = useState([]);                 // Notices for active project
const [loading, setLoading] = useState(false);              // Loading state for posting/deleting
const [loadingProjects, setLoadingProjects] = useState(true);// Loading state for initial project load
const [searchQuery, setSearchQuery] = useState('');         // Dropdown live filter text
const [isDropdownOpen, setIsDropdownOpen] = useState(false);// Dropdown toggle
const [formData, setFormData] = useState({
  title: '',
  description: '',
  image: null
});
const [imagePreview, setImagePreview] = useState(null);     // Blob preview for uploaded image
```

### 2. Searchable Dropdown Filtering:
```javascript
const filteredProjects = projects.filter(p => 
  p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
  String(p.id).includes(searchQuery)
);
```

### 3. Image URL Resolution:
Backend usually stores relative image paths like `/uploads/notices/img.jpg`. Resolve the full URL by stripping `/api` from base URL:
```javascript
const imageUrl = `${import.meta.env.VITE_API_BASE_URL.replace('/api', '')}${notice.image}`;
```

---

## 📱 User Dashboard Implementation Logic (`UserNoticesTab.jsx`)

1. **Telegram-style Date Grouping:**
   ```javascript
   const formatDate = (dateString) => {
     return new Date(dateString).toLocaleDateString('en-US', {
       month: 'long',
       day: 'numeric',
       year: 'numeric'
     });
   };

   const groupedNotices = notices.reduce((acc, notice) => {
     const date = formatDate(notice.created_at);
     if (!acc[date]) acc[date] = [];
     acc[date].push(notice);
     return acc;
   }, {});
   ```

2. **Auto View Count Tracking with Intersection Observer:**
   ```javascript
   useEffect(() => {
     const observer = new IntersectionObserver(
       (entries) => {
         if (entries[0].isIntersecting) {
           // Notice reached 50% screen visibility
           const storedUser = JSON.parse(localStorage.getItem('user') || '{}');
           recordNoticeView(notice.id, storedUser.id || null);
           observer.disconnect(); // Fire view counter only once per session
         }
       },
       { threshold: 0.5 }
     );

     if (noticeRef.current) observer.observe(noticeRef.current);
     return () => observer.disconnect();
   }, [notice.id]);
   ```

---

## ⚡ Quick Checklist for New Project

1. [ ] Install dependencies: `lucide-react`, `react-toastify` (or any icon/toast library of your choice).
2. [ ] Define `VITE_API_BASE_URL` in `.env`.
3. [ ] Provide Admin Authentication token via context or localStorage headers.
4. [ ] Remember to use `new FormData()` when calling `POST /project-notices`.
5. [ ] Ensure `replace('/api', '')` is applied to notice image paths if backend returns relative paths.

