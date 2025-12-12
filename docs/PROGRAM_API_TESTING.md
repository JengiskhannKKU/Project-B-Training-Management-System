# Program API Testing Guide

## Base URL
```
http://localhost:8000/api
```

## Authentication
Most endpoints require authentication using Laravel Sanctum token.

```bash
# Add this header to your requests:
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 🧪 API Endpoints for Program

### 1. GET All Programs
**Endpoint:** `GET /api/programs`

**Description:** Retrieve all programs

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/programs \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Programs retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Laravel Advanced",
      "code": "LAR-ADV-001",
      "description": "Advanced Laravel course",
      "category": "Web Development",
      "duration_hours": 40,
      "image_url": "https://example.com/image.jpg",
      "status": "active",
      "approval_status": "pending",
      "created_by": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 2. GET Single Program
**Endpoint:** `GET /api/programs/{id}`

**Description:** Retrieve a specific program by ID

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/programs/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Program retrieved successfully",
  "data": {
    "id": 1,
    "name": "Laravel Advanced",
    "code": "LAR-ADV-001",
    "description": "Advanced Laravel course",
    "category": "Web Development",
    "duration_hours": 40,
    "image_url": "https://example.com/image.jpg",
    "status": "active",
    "approval_status": "pending",
    "created_by": 1,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Expected Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

---

### 3. POST Create Program
**Endpoint:** `POST /api/programs`

**Description:** Create a new program

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**
```json
{
  "name": "Vue.js Fundamentals",
  "code": "VUE-FUND-001",
  "description": "Learn Vue.js from scratch",
  "category": "Frontend Development",
  "duration_hours": 30,
  "image_url": "https://example.com/vue-course.jpg",
  "status": "active"
}
```

**Required Fields:**
- `name` (string, max:255)
- `code` (string, max:50, unique)
- `category` (string, max:100)
- `duration_hours` (integer, min:1)
- `status` (enum: active, inactive)

**Optional Fields:**
- `description` (string)
- `image_url` (url, max:2048)

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/programs \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Vue.js Fundamentals",
    "code": "VUE-FUND-001",
    "description": "Learn Vue.js from scratch",
    "category": "Frontend Development",
    "duration_hours": 30,
    "image_url": "https://example.com/vue-course.jpg",
    "status": "active"
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Program created successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Fundamentals",
    "code": "VUE-FUND-001",
    "description": "Learn Vue.js from scratch",
    "category": "Frontend Development",
    "duration_hours": 30,
    "image_url": "https://example.com/vue-course.jpg",
    "status": "active",
    "created_by": 1,
    "created_at": "2025-01-02T00:00:00.000000Z",
    "updated_at": "2025-01-02T00:00:00.000000Z"
  }
}
```

**Validation Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "code": ["The code field is required."],
    "category": ["The category field is required."]
  }
}
```

---

### 4. PUT/PATCH Update Program
**Endpoint:** `PUT /api/programs/{id}` or `PATCH /api/programs/{id}`

**Description:** Update an existing program

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body (all fields optional):**
```json
{
  "name": "Vue.js Advanced",
  "description": "Advanced Vue.js techniques",
  "duration_hours": 50,
  "status": "inactive"
}
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/programs/2 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Vue.js Advanced",
    "description": "Advanced Vue.js techniques",
    "duration_hours": 50
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Program updated successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Advanced",
    "code": "VUE-FUND-001",
    "description": "Advanced Vue.js techniques",
    "category": "Frontend Development",
    "duration_hours": 50,
    "image_url": "https://example.com/vue-course.jpg",
    "status": "active",
    "created_by": 1,
    "created_at": "2025-01-02T00:00:00.000000Z",
    "updated_at": "2025-01-02T01:00:00.000000Z"
  }
}
```

---

### 5. DELETE Program
**Endpoint:** `DELETE /api/programs/{id}`

**Description:** Delete a program (hard delete)

**Headers:**
```
Accept: application/json
Authorization: Bearer YOUR_TOKEN
```

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/programs/2 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Program deleted successfully",
  "data": null
}
```

---

## 📮 Postman Collection

### Import to Postman:
1. Open Postman
2. Click "Import"
3. Create new collection "Program API"
4. Add requests as shown above

### Setup Environment Variables:
- `base_url`: `http://localhost:8000/api`
- `token`: Your authentication token

### Use Variables:
```
{{base_url}}/programs
```

---

## 🧪 Testing Scenarios

### Test Case 1: Create and Retrieve
1. POST new program
2. Note the returned ID
3. GET that program by ID
4. Verify data matches

### Test Case 2: Update
1. GET program by ID
2. PUT with updated data
3. GET again to verify changes

### Test Case 3: Delete
1. DELETE program by ID
2. GET same ID (should return 404)

### Test Case 4: Validation
1. POST without required fields
2. Verify 422 error with proper messages
3. POST with duplicate code
4. Verify unique validation error

### Test Case 5: List All
1. Create multiple programs
2. GET all programs
3. Verify all are returned

---

## 🚨 Common Errors

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Check your Bearer token

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Program] {id}"
}
```
**Solution:** Program with that ID doesn't exist

### 422 Validation Error
```json
{
  "message": "The code has already been taken.",
  "errors": {
    "code": ["The code has already been taken."]
  }
}
```
**Solution:** Fix validation errors as indicated

---

## 🔧 Quick Start with cURL

### 1. First, register or login to get token:
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

### 2. Copy the token from response

### 3. Test the Program API:
```bash
# Replace YOUR_TOKEN with the actual token
TOKEN="YOUR_TOKEN_HERE"

# Get all programs
curl -X GET http://localhost:8000/api/programs \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```
