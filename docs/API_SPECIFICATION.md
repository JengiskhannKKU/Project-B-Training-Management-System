# API Specification

Complete API Reference for Training Management System

**Version:** 1.0.0
**Last Updated:** December 28, 2025
**Base URL:** `/api`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Profile & Avatar Management](#profile--avatar-management)
4. [Programs](#programs)
5. [Training Sessions](#training-sessions)
6. [Public Catalog](#public-catalog)
7. [Enrollments](#enrollments)
8. [Admin - User Management](#admin---user-management)
9. [Error Responses](#error-responses)

---

## Overview

### Environments

| Environment | Base URL |
|-------------|----------|
| Development | `http://localhost:8000/api` |
| Production  | `https://your-domain.com/api` |

### Response Format

All endpoints return JSON with this structure:

**Success:**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["Error message"] }
}
```

### Authentication

Protected endpoints require a Bearer token in the header:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success (GET, PUT, DELETE) |
| 201 | Created (POST) |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error - Invalid input |
| 500 | Server Error |

---

## Authentication

### POST /auth/register

Register a new user. New users are automatically assigned the `student` role.

**Authentication:** Not required

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Max 255 chars |
| email | string | Yes | Valid email, unique |
| password | string | Yes | Min 8 chars |

**Response (201):**
```json
{
  "success": true,
  "message": "Registered successfully",
  "data": {
    "token": "1|AbC123...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role_id": 3,
      "status": "active",
      "created_at": "2025-01-01T00:00:00Z"
    }
  }
}
```

**Error (422) - Validation:**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## Profile & Avatar Management

### GET /me

Get current authenticated user's profile information.

**Authentication:** Required

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": 3,
    "status": "active",
    "role": {
      "id": 3,
      "name": "student",
      "label": "Student"
    }
  },
  "profile": {
    "id": 1,
    "user_id": 1,
    "phone": "0812345678",
    "date_of_birth": "1990-01-15",
    "gender": "male",
    "organization": "ABC Company",
    "department": "IT",
    "bio": "Software developer passionate about learning"
  },
  "avatar_present": true
}
```

**Notes:**
- Returns `profile: null` if user hasn't created a profile yet
- `avatar_present` indicates whether user has uploaded an avatar image

---

### PUT /me/profile

Update current user's profile information.

**Authentication:** Required

**Request:**
```json
{
  "name": "John Smith",
  "phone": "0899999999",
  "date_of_birth": "1990-01-15",
  "gender": "male",
  "organization": "XYZ Corp",
  "department": "Engineering",
  "bio": "Full-stack developer"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | No | Max 255 chars (updates users.name) |
| phone | string | No | Max 50 chars, numeric only |
| date_of_birth | date | No | Valid date (YYYY-MM-DD) |
| gender | string | No | Max 50 chars |
| organization | string | No | Max 255 chars |
| department | string | No | Max 255 chars |
| bio | text | No | Any length |

**Response (200):**
```json
{
  "message": "Profile updated successfully.",
  "user": {
    "id": 1,
    "name": "John Smith",
    "email": "john@example.com",
    "profile": {
      "phone": "0899999999",
      "date_of_birth": "1990-01-15",
      "gender": "male",
      "organization": "XYZ Corp",
      "department": "Engineering",
      "bio": "Full-stack developer"
    },
    "role": {
      "id": 3,
      "name": "student"
    }
  }
}
```

**Error (422) - Validation:**
```json
{
  "message": "The phone must only contain numbers.",
  "errors": {
    "phone": ["The phone must match the format ^[0-9]+$."]
  }
}
```

---

### POST /me/avatar

Upload user avatar image (stored as BLOB in database).

**Authentication:** Required

**Content-Type:** `multipart/form-data`

**Request:**
```
POST /api/me/avatar
Content-Type: multipart/form-data

avatar: [binary file data]
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| avatar | file | Yes | Image (jpeg, png, jpg, gif), max 2MB |

**Response (200):**
```json
{
  "message": "Avatar updated successfully."
}
```

**Error (422) - Invalid File:**
```json
{
  "message": "The avatar field must be an image.",
  "errors": {
    "avatar": ["The avatar field must be an image."]
  }
}
```

**Error (422) - File Too Large:**
```json
{
  "message": "The avatar field must not be greater than 2048 kilobytes.",
  "errors": {
    "avatar": ["The avatar field must not be greater than 2048 kilobytes."]
  }
}
```

**Notes:**
- Avatar is stored as binary (BLOB) in `profiles.avatar_image`
- MIME type is stored in `profiles.avatar_mime_type`
- No URL is stored; avatar is retrieved via `GET /api/me/avatar`

---

### GET /me/avatar

Retrieve current user's avatar image.

**Authentication:** Required

**Response (200):**
- **Content-Type:** `image/jpeg`, `image/png`, `image/gif`, etc. (based on uploaded file)
- **Body:** Binary image data

**Response Headers:**
```http
Content-Type: image/jpeg
Content-Length: 52341
```

**Error (404):**
```json
{
  "message": "Avatar not found"
}
```

**Usage Example (HTML):**
```html
<img src="/api/me/avatar" alt="My Avatar" />
```

**Notes:**
- Returns image directly (not JSON)
- Can be used in `<img>` tags
- Add query param `?t=timestamp` to bust cache after upload

---

### DELETE /me/avatar

Delete current user's avatar image.

**Authentication:** Required

**Response (200):**
```json
{
  "message": "Avatar deleted successfully."
}
```

**Error (404) - No Avatar:**
```json
{
  "message": "No avatar to delete."
}
```

**Notes:**
- Sets `avatar_image` and `avatar_mime_type` to `null`
- User will see default avatar after deletion

---

## Programs

### GET /programs

Retrieve all programs.

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "message": "Programs retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Laravel Advanced Course",
      "code": "LAR-ADV-001",
      "description": "Learn advanced Laravel concepts",
      "category": "Web Development",
      "duration_hours": 40,
      "image_url": "https://example.com/image.jpg",
      "status": "active",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

---

### GET /programs/{id}

Retrieve a single program by ID.

**Authentication:** Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Program ID |

**Response (200):**
```json
{
  "success": true,
  "message": "Program retrieved successfully",
  "data": {
    "id": 1,
    "name": "Laravel Advanced Course",
    "code": "LAR-ADV-001",
    "description": "Learn advanced Laravel concepts",
    "category": "Web Development",
    "duration_hours": 40,
    "status": "active"
  }
}
```

**Error (404):**
```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

---

### POST /programs

Create a new program.

**Authentication:** Required

**Request:**
```json
{
  "name": "Vue.js Fundamentals",
  "code": "VUE-FUND-001",
  "description": "Learn Vue.js from scratch",
  "category": "Frontend Development",
  "duration_hours": 30,
  "image_url": "https://example.com/vue.jpg",
  "status": "active"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Max 255 chars |
| code | string | Yes | Max 50 chars, unique |
| description | text | No | - |
| category | string | Yes | Max 100 chars |
| duration_hours | integer | Yes | Min 1 |
| image_url | string | No | Valid URL, max 2048 chars |
| status | string | Yes | `active` or `inactive` |

**Response (201):**
```json
{
  "success": true,
  "message": "Program created successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Fundamentals",
    "code": "VUE-FUND-001",
    "category": "Frontend Development",
    "duration_hours": 30,
    "status": "active",
    "created_by": 1,
    "created_at": "2025-01-02T00:00:00Z"
  }
}
```

**Error (422):**
```json
{
  "message": "The code has already been taken.",
  "errors": {
    "code": ["The code has already been taken."]
  }
}
```

---

### PUT /programs/{id}

Update an existing program. All fields are optional.

**Authentication:** Required

**Request (partial update):**
```json
{
  "name": "Vue.js Advanced",
  "duration_hours": 50
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Program updated successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Advanced",
    "duration_hours": 50,
    "updated_at": "2025-01-02T10:00:00Z"
  }
}
```

---

### DELETE /programs/{id}

Delete a program (hard delete).

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "message": "Program deleted successfully",
  "data": null
}
```

**Error (404):**
```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

---

## Training Sessions

### GET /sessions

Retrieve all training sessions with optional filtering.

**Authentication:** Required

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| program_id | integer | No | Filter by program ID |

**Examples:**
```
GET /sessions
GET /sessions?program_id=1
```

**Response (200):**
```json
{
  "success": true,
  "message": "Sessions retrieved successfully",
  "data": [
    {
      "id": 1,
      "program_id": 1,
      "title": "Laravel Advanced - Batch 1",
      "start_date": "2025-02-01",
      "end_date": "2025-02-28",
      "start_time": "09:00",
      "end_time": "17:00",
      "capacity": 30,
      "trainer_id": 2,
      "location": "Room A101",
      "status": "open",
      "created_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

---

### GET /sessions/{id}

Retrieve a single session by ID.

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "message": "Session retrieved successfully",
  "data": {
    "id": 1,
    "program_id": 1,
    "title": "Laravel Advanced - Batch 1",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "capacity": 30,
    "trainer_id": 2,
    "location": "Room A101",
    "status": "open"
  }
}
```

---

### POST /sessions

Create a new training session.

**Authentication:** Required

**Request:**
```json
{
  "program_id": 1,
  "title": "Laravel Advanced - Batch 2",
  "start_date": "2025-03-01",
  "end_date": "2025-03-31",
  "start_time": "09:00",
  "end_time": "17:00",
  "capacity": 25,
  "trainer_id": 2,
  "location": "Room B202",
  "status": "open"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| program_id | integer | Yes | Must exist in programs table |
| title | string | Yes | Max 255 chars |
| start_date | date | Yes | Format: YYYY-MM-DD, before end_date |
| end_date | date | Yes | Format: YYYY-MM-DD, after start_date |
| start_time | time | No | Format: HH:MM |
| end_time | time | No | Format: HH:MM, after start_time |
| capacity | integer | Yes | Min 1 |
| trainer_id | integer | Yes | Must exist in users table |
| location | string | No | Max 255 chars |
| status | string | No | `upcoming`, `open`, `closed`, `completed`, `cancelled` |

**Response (201):**
```json
{
  "success": true,
  "message": "Session created successfully",
  "data": {
    "id": 2,
    "program_id": 1,
    "title": "Laravel Advanced - Batch 2",
    "start_date": "2025-03-01",
    "end_date": "2025-03-31",
    "capacity": 25,
    "created_at": "2025-01-02T00:00:00Z"
  }
}
```

**Error (422) - Invalid Dates:**
```json
{
  "message": "The start date must be before the end date.",
  "errors": {
    "start_date": ["The start date must be before the end date."]
  }
}
```

---

### PUT /sessions/{id}

Update an existing session. All fields are optional.

**Authentication:** Required

**Request (partial update):**
```json
{
  "capacity": 30,
  "status": "closed"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Session updated successfully",
  "data": {
    "id": 2,
    "capacity": 30,
    "status": "closed",
    "updated_at": "2025-01-02T10:00:00Z"
  }
}
```

---

### DELETE /sessions/{id}

Delete a session (hard delete).

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "message": "Session deleted successfully",
  "data": null
}
```

---

## Public Catalog

Public APIs for browsing approved programs and sessions (no authentication required for GET endpoints).

### GET /catalog/programs

Get all approved and active programs (public access).

**Authentication:** Not required

**Response (200):**
```json
[
  {
    "id": 1,
    "code": "LAR-ADV-001",
    "name": "Laravel Advanced Course",
    "description": "Learn advanced Laravel concepts",
    "category_id": 1,
    "level": "advanced",
    "duration_hours": 40,
    "created_by": 2,
    "approved_by": 1,
    "approval_status": "approved",
    "status": "active",
    "image_url": "https://example.com/image.jpg",
    "created_at": "2025-01-01T00:00:00Z",
    "updated_at": "2025-01-02T00:00:00Z"
  },
  {
    "id": 2,
    "code": "VUE-FUND-001",
    "name": "Vue.js Fundamentals",
    "description": "Learn Vue.js from scratch",
    "category_id": 2,
    "level": "beginner",
    "duration_hours": 30,
    "approval_status": "approved",
    "status": "active",
    "created_at": "2025-01-03T00:00:00Z"
  }
]
```

**Notes:**
- Only returns programs where `approval_status = 'approved'` AND `status = 'active'`
- Sorted by latest first
- No pagination (returns all matching programs)

---

### GET /catalog/programs/{program}/sessions

Get all approved and open sessions for a specific program (public access).

**Authentication:** Not required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| program | integer | Program ID |

**Response (200):**
```json
[
  {
    "id": 1,
    "program_id": 1,
    "title": "Laravel Advanced - Batch 1",
    "description": "First batch of Laravel advanced training",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "start_time": "09:00:00",
    "end_time": "17:00:00",
    "location": "Room A101",
    "capacity": 30,
    "trainer_id": 2,
    "approved_by": 1,
    "approval_status": "approved",
    "status": "open",
    "created_at": "2025-01-05T00:00:00Z",
    "updated_at": "2025-01-06T00:00:00Z"
  },
  {
    "id": 2,
    "program_id": 1,
    "title": "Laravel Advanced - Batch 2",
    "start_date": "2025-03-01",
    "end_date": "2025-03-31",
    "capacity": 25,
    "approval_status": "approved",
    "status": "open",
    "created_at": "2025-01-07T00:00:00Z"
  }
]
```

**Error (404) - Program Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

**Notes:**
- Only returns sessions where `approval_status = 'approved'` AND `status = 'open'`
- Sorted by `start_date DESC, start_time DESC`
- Shows sessions available for enrollment

---

## Enrollments

### POST /enrollments

Create a new enrollment for the authenticated user.

**Authentication:** Required

**Request:**
```json
{
  "session_id": 1
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| session_id | integer | Yes | Must exist in training_sessions table |

**Response (201):**
```json
{
  "message": "Enrollment created successfully.",
  "data": {
    "id": 1,
    "user_id": 3,
    "session_id": 1,
    "status": "confirmed",
    "enrolled_at": "2025-01-10T10:30:00Z",
    "created_at": "2025-01-10T10:30:00Z",
    "updated_at": "2025-01-10T10:30:00Z"
  }
}
```

**Error (422) - Session Not Open:**
```json
{
  "message": "Cannot enroll: Session is closed or not open for registration."
}
```

**Error (422) - Session Full:**
```json
{
  "message": "Cannot enroll: Session capacity is full."
}
```

**Error (422) - Already Enrolled:**
```json
{
  "message": "You are already enrolled in this session."
}
```

**Business Rules:**
- User can only enroll in sessions with `approval_status = 'approved'` AND `status = 'open'`
- Session must have available capacity
- User cannot enroll twice in the same session
- Enrollment status is automatically set to `confirmed`

---

### PUT /enrollments/{enrollment}/cancel

Cancel an existing enrollment (before session starts).

**Authentication:** Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| enrollment | integer | Enrollment ID |

**Response (200):**
```json
{
  "message": "Enrollment cancelled successfully.",
  "data": {
    "id": 1,
    "user_id": 3,
    "session_id": 1,
    "status": "cancelled",
    "enrolled_at": "2025-01-10T10:30:00Z",
    "updated_at": "2025-01-15T14:20:00Z"
  }
}
```

**Error (403) - Unauthorized:**
```json
{
  "message": "Unauthorized"
}
```

**Error (422) - Cannot Cancel (Session Started):**
```json
{
  "message": "Cannot cancel on or after the start date."
}
```

**Error (200) - Already Cancelled:**
```json
{
  "message": "Enrollment already cancelled."
}
```

**Business Rules:**
- Only the enrolled user can cancel their own enrollment
- Cannot cancel on or after the session's `start_date`
- If already cancelled, returns success (idempotent)

---

### GET /me/enrollments

List all enrollments for the authenticated user.

**Authentication:** Required

**Response (200):**
```json
[
  {
    "id": 1,
    "user_id": 3,
    "session_id": 1,
    "status": "confirmed",
    "enrolled_at": "2025-01-10T10:30:00Z",
    "created_at": "2025-01-10T10:30:00Z",
    "updated_at": "2025-01-10T10:30:00Z",
    "session": {
      "id": 1,
      "program_id": 1,
      "title": "Laravel Advanced - Batch 1",
      "start_date": "2025-02-01",
      "end_date": "2025-02-28",
      "location": "Room A101",
      "status": "open",
      "program": {
        "id": 1,
        "code": "LAR-ADV-001",
        "name": "Laravel Advanced Course",
        "category_id": 1,
        "level": "advanced"
      }
    }
  },
  {
    "id": 2,
    "user_id": 3,
    "session_id": 2,
    "status": "cancelled",
    "enrolled_at": "2025-01-08T09:00:00Z",
    "session": {
      "id": 2,
      "title": "Laravel Advanced - Batch 2",
      "program": {
        "name": "Laravel Advanced Course"
      }
    }
  }
]
```

**Notes:**
- Returns all enrollments (confirmed, cancelled, etc.)
- Includes nested `session` and `program` data
- Sorted by latest first

---

## Admin - User Management

### GET /admin/users

Retrieve all users with filtering and pagination. Admin only.

**Authentication:** Required (Admin role)

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| role | string | No | Filter by role: `admin`, `trainer`, `student` |
| status | string | No | Filter by status: `active`, `inactive` |
| per_page | integer | No | Items per page (default: 15, max: 100) |

**Examples:**
```
GET /admin/users
GET /admin/users?role=trainer
GET /admin/users?status=active&per_page=20
```

**Response (200):**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "status": "active",
        "role": {
          "id": 1,
          "name": "admin",
          "label": "Admin"
        },
        "created_at": "2025-01-01T00:00:00Z"
      }
    ],
    "total": 50,
    "per_page": 15,
    "last_page": 4
  }
}
```

---

### POST /admin/users

Create a new user with specified role. Admin only.

**Authentication:** Required (Admin role)

**Request:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password123",
  "role": "trainer"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Max 255 chars |
| email | string | Yes | Valid email, unique |
| password | string | Yes | Min 8 chars |
| role | string | Yes | `admin`, `trainer`, or `student` |

**Response (201):**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "status": "active",
    "role_id": 2,
    "created_at": "2025-01-02T00:00:00Z"
  }
}
```

---

### PUT /admin/users/{id}

Update user information. Admin only.

**Authentication:** Required (Admin role)

**Request (partial update):**
```json
{
  "name": "Jane Smith (Updated)",
  "role": "admin",
  "status": "inactive"
}
```

**Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | No | Max 255 chars |
| email | string | No | Valid email, unique |
| role | string | No | `admin`, `trainer`, `student` |
| status | string | No | `active`, `inactive` |

**Response (200):**
```json
{
  "success": true,
  "message": "User updated successfully",
  "data": {
    "id": 2,
    "name": "Jane Smith (Updated)",
    "email": "jane@example.com",
    "status": "inactive",
    "role": {
      "id": 1,
      "name": "admin",
      "label": "Admin"
    }
  }
}
```

---

### DELETE /admin/users/{id}

Deactivate a user (soft delete - sets status to inactive). Admin only.

**Authentication:** Required (Admin role)

**Response (200):**
```json
{
  "success": true,
  "message": "User deactivated successfully",
  "data": {
    "id": 2,
    "status": "inactive"
  }
}
```

---

## Error Responses

### 401 Unauthorized

Missing or invalid authentication token.

```json
{
  "message": "Unauthenticated."
}
```

**Solution:** Login and include valid token in Authorization header.

---

### 403 Forbidden

Valid token but insufficient permissions.

```json
{
  "success": false,
  "message": "Forbidden"
}
```

**Solution:** Use an account with appropriate permissions (e.g., admin).

---

### 404 Not Found

Requested resource doesn't exist.

```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

**Solution:** Verify the resource ID exists.

---

### 422 Validation Error

Invalid request data.

```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "code": ["The code has already been taken."],
    "duration_hours": ["The duration hours field must be at least 1."]
  }
}
```

**Solution:** Fix validation errors listed in the `errors` object.

---

### 500 Server Error

Internal server error.

```json
{
  "message": "Server Error",
  "error": "Internal server error occurred"
}
```

**Solution:** Check server logs, contact system administrator.

---

## Quick Reference

### Endpoints Summary

#### Authentication
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /auth/register | Register user | No |
| POST | /auth/login | Login user | No |

#### Profile & Avatar
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /me | Get current user | Yes |
| PUT | /me/profile | Update profile | Yes |
| POST | /me/avatar | Upload avatar | Yes |
| GET | /me/avatar | Get avatar image | Yes |
| DELETE | /me/avatar | Delete avatar | Yes |

#### Public Catalog
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /catalog/programs | List public programs | No |
| GET | /catalog/programs/{id}/sessions | List program sessions | No |

#### Enrollments
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /enrollments | Create enrollment | Yes |
| PUT | /enrollments/{id}/cancel | Cancel enrollment | Yes |
| GET | /me/enrollments | List my enrollments | Yes |

#### Programs
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /programs | List programs | Yes |
| GET | /programs/{id} | Get program | Yes |
| POST | /programs | Create program | Yes |
| PUT | /programs/{id} | Update program | Yes |
| DELETE | /programs/{id} | Delete program | Yes |

#### Training Sessions
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /sessions | List sessions | Yes |
| GET | /sessions/{id} | Get session | Yes |
| POST | /sessions | Create session | Yes |
| PUT | /sessions/{id} | Update session | Yes |
| DELETE | /sessions/{id} | Delete session | Yes |

#### Admin - User Management
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /admin/users | List users | Admin |
| POST | /admin/users | Create user | Admin |
| PUT | /admin/users/{id} | Update user | Admin |
| DELETE | /admin/users/{id} | Deactivate user | Admin |
| PUT | /admin/users/{id}/deactivate | Deactivate user | Admin |

---

**Last Updated:** December 28, 2025
**Version:** 1.0.0
