# API Specification

Complete API Reference for Training Management System

**Version:** 1.0.0
**Last Updated:** December 12, 2025
**Base URL:** `/api`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Programs](#programs)
4. [Training Sessions](#training-sessions)
5. [Admin - User Management](#admin---user-management)
6. [Error Responses](#error-responses)

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

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /auth/register | Register user | No |
| GET | /programs | List programs | Yes |
| GET | /programs/{id} | Get program | Yes |
| POST | /programs | Create program | Yes |
| PUT | /programs/{id} | Update program | Yes |
| DELETE | /programs/{id} | Delete program | Yes |
| GET | /sessions | List sessions | Yes |
| GET | /sessions/{id} | Get session | Yes |
| POST | /sessions | Create session | Yes |
| PUT | /sessions/{id} | Update session | Yes |
| DELETE | /sessions/{id} | Delete session | Yes |
| GET | /admin/users | List users | Admin |
| POST | /admin/users | Create user | Admin |
| PUT | /admin/users/{id} | Update user | Admin |
| DELETE | /admin/users/{id} | Deactivate user | Admin |

---

**Last Updated:** December 12, 2025
**Version:** 1.0.0
