# Training Session API Testing Guide

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require authentication using Laravel Sanctum token.

```bash
# Add this header to your requests:
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 🧪 API Endpoints for Training Sessions

### 1. GET All Sessions
**Endpoint:** `GET /api/sessions`

**Description:** Retrieve all training sessions with optional filtering by program

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**Query Parameters:**
- `program_id` (optional) - Filter sessions by program ID

**cURL Example (All sessions):**
```bash
curl -X GET http://localhost:8000/api/sessions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**cURL Example (Filter by program):**
```bash
curl -X GET "http://localhost:8000/api/sessions?program_id=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Sessions retrieved successfully",
  "data": [
    {
      "id": 1,
      "program_id": 1,
      "title": "Laravel Advanced - Batch 1",
      "start_date": "2025-01-15",
      "end_date": "2025-02-15",
      "start_time": "09:00",
      "end_time": "17:00",
      "capacity": 30,
      "trainer_id": 2,
      "location": "Room A101",
      "status": "open",
      "approval_status": "approved",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 2. GET Single Session
**Endpoint:** `GET /api/sessions/{id}`

**Description:** Retrieve a specific training session by ID

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/sessions/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Session retrieved successfully",
  "data": {
    "id": 1,
    "program_id": 1,
    "title": "Laravel Advanced - Batch 1",
    "start_date": "2025-01-15",
    "end_date": "2025-02-15",
    "start_time": "09:00",
    "end_time": "17:00",
    "capacity": 30,
    "trainer_id": 2,
    "location": "Room A101",
    "status": "open",
    "approval_status": "approved"
  }
}
```

**Expected Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\TrainingSession] 999"
}
```

---

### 3. POST Create Session
**Endpoint:** `POST /api/sessions`

**Description:** Create a new training session

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**
```json
{
  "program_id": 1,
  "title": "Vue.js Fundamentals - Batch 1",
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "start_time": "09:00",
  "end_time": "17:00",
  "capacity": 25,
  "trainer_id": 2,
  "location": "Room B202",
  "status": "open"
}
```

**Required Fields:**
- `program_id` (integer, must exist in programs table)
- `title` (string, max:255)
- `start_date` (date, must be before end_date)
- `end_date` (date, must be after start_date)
- `capacity` (integer, min:1)
- `trainer_id` (integer, must exist in users table)

**Optional Fields:**
- `start_time` (time format H:i, e.g., "09:00")
- `end_time` (time format H:i, must be after start_time)
- `location` (string, max:255)
- `status` (enum: upcoming, open, closed, completed, cancelled)
- `approval_status` (enum: pending, approved, rejected)
- `approved_by` (integer, user ID)
- `approved_at` (date)
- `approval_note` (string)

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/sessions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "program_id": 1,
    "title": "Vue.js Fundamentals - Batch 1",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "start_time": "09:00",
    "end_time": "17:00",
    "capacity": 25,
    "trainer_id": 2,
    "location": "Room B202",
    "status": "open"
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Session created successfully",
  "data": {
    "id": 2,
    "program_id": 1,
    "title": "Vue.js Fundamentals - Batch 1",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "start_time": "09:00",
    "end_time": "17:00",
    "capacity": 25,
    "trainer_id": 2,
    "location": "Room B202",
    "status": "open",
    "created_at": "2025-01-02T00:00:00.000000Z",
    "updated_at": "2025-01-02T00:00:00.000000Z"
  }
}
```

**Validation Error Response (422):**
```json
{
  "message": "The start date must be before the end date.",
  "errors": {
    "start_date": ["The start date must be before the end date."]
  }
}
```

---

### 4. PUT/PATCH Update Session
**Endpoint:** `PUT /api/sessions/{id}` or `PATCH /api/sessions/{id}`

**Description:** Update an existing training session

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body (all fields optional):**
```json
{
  "title": "Vue.js Advanced - Batch 1",
  "capacity": 30,
  "status": "closed"
}
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/sessions/2 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "Vue.js Advanced - Batch 1",
    "capacity": 30,
    "status": "closed"
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Session updated successfully",
  "data": {
    "id": 2,
    "program_id": 1,
    "title": "Vue.js Advanced - Batch 1",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "capacity": 30,
    "status": "closed",
    "updated_at": "2025-01-02T01:00:00.000000Z"
  }
}
```

---

### 5. DELETE Session
**Endpoint:** `DELETE /api/sessions/{id}`

**Description:** Delete a training session (hard delete)

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/sessions/2 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Session deleted successfully",
  "data": null
}
```

---

## 🧪 Testing Scenarios

### Test Case 1: Date Validation
**Purpose:** Ensure start_date must be before end_date

**Steps:**
1. POST with start_date = "2025-02-15" and end_date = "2025-02-01"
2. Expect 422 validation error

**Expected Error:**
```json
{
  "message": "The start date must be before the end date.",
  "errors": {
    "start_date": ["The start date must be before the end date."]
  }
}
```

### Test Case 2: Time Validation
**Purpose:** Ensure end_time must be after start_time

**Steps:**
1. POST with start_time = "17:00" and end_time = "09:00"
2. Expect 422 validation error

**Expected Error:**
```json
{
  "message": "The end time field must be a time after start time.",
  "errors": {
    "end_time": ["The end time field must be a time after start time."]
  }
}
```

### Test Case 3: Foreign Key Validation
**Purpose:** Ensure program_id and trainer_id exist

**Steps:**
1. POST with program_id = 99999 (non-existent)
2. Expect 422 validation error

**Expected Error:**
```json
{
  "message": "The selected program id is invalid.",
  "errors": {
    "program_id": ["The selected program id is invalid."]
  }
}
```

### Test Case 4: Capacity Validation
**Purpose:** Ensure capacity is a positive integer

**Steps:**
1. POST with capacity = 0
2. Expect 422 validation error

**Expected Error:**
```json
{
  "message": "The capacity field must be at least 1.",
  "errors": {
    "capacity": ["The capacity field must be at least 1."]
  }
}
```

### Test Case 5: Filter by Program
**Purpose:** Test filtering sessions by program_id

**Steps:**
1. Create sessions for program 1 and program 2
2. GET /api/sessions?program_id=1
3. Verify only program 1's sessions are returned

### Test Case 6: Update Date Validation
**Purpose:** Ensure date validation works on update too

**Steps:**
1. Create a session with valid dates
2. Update only end_date to be before start_date
3. Expect 422 validation error

---

## 🚨 Common Errors

### 422 - Date Order Error
```json
{
  "message": "The start date must be before the end date.",
  "errors": {
    "start_date": ["The start date must be before the end date."]
  }
}
```
**Solution:** Ensure start_date < end_date

### 422 - Foreign Key Error
```json
{
  "message": "The selected program id is invalid.",
  "errors": {
    "program_id": ["The selected program id is invalid."]
  }
}
```
**Solution:** Use a valid program_id that exists in database

### 422 - Invalid Status
```json
{
  "message": "The selected status is invalid.",
  "errors": {
    "status": ["The selected status is invalid."]
  }
}
```
**Solution:** Use one of: upcoming, open, closed, completed, cancelled

---

## 📋 Validation Rules Summary

| Field | Required | Type | Rules |
|-------|----------|------|-------|
| program_id | Yes | Integer | Must exist in programs table |
| title | Yes | String | Max 255 characters |
| start_date | Yes | Date | Must be before end_date |
| end_date | Yes | Date | Must be after start_date |
| start_time | No | Time | Format H:i (e.g., "09:00") |
| end_time | No | Time | Must be after start_time |
| capacity | Yes | Integer | Minimum 1 |
| trainer_id | Yes | Integer | Must exist in users table |
| location | No | String | Max 255 characters |
| status | No | Enum | upcoming, open, closed, completed, cancelled |
| approval_status | No | Enum | pending, approved, rejected |

---

## 🔧 Quick Test Commands

### 1. Get your authentication token:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'
```

### 2. Test all sessions:
```bash
TOKEN="YOUR_TOKEN_HERE"

# Get all sessions
curl -X GET http://localhost:8000/api/sessions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Test create with validation error:
```bash
# This should fail - start_date after end_date
curl -X POST http://localhost:8000/api/sessions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "program_id": 1,
    "title": "Test Session",
    "start_date": "2025-02-28",
    "end_date": "2025-02-01",
    "capacity": 20,
    "trainer_id": 1
  }'
```
