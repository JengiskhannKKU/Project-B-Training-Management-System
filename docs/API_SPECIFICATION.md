# API Specification - Training Management System

> เอกสารอธิบาย API ทั้งหมดในระบบ พร้อม input, output และ error responses

**Version:** 1.0
**Last Updated:** 12 ธันวาคม 2025

---

## 📋 Table of Contents

- [ข้อมูลทั่วไป](#ข้อมูลทั่วไป)
- [Authentication API](#authentication-api)
- [Program API](#program-api)
- [Training Session API](#training-session-api)
- [Admin User API](#admin-user-api)
- [Error Responses](#error-responses)

---

## 🌐 ข้อมูลทั่วไป

### Base URL
```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### Response Format
ทุก API จะ return JSON ในรูปแบบมาตรฐานเดียวกัน:

**Success Response:**
```json
{
  "success": true,
  "message": "ข้อความอธิบาย",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "ข้อความแจ้ง error",
  "errors": { ... }
}
```

### Authentication
ส่วนใหญ่ของ API ต้องการ Authentication Token

**Header:**
```
Authorization: Bearer {your_token}
Accept: application/json
```

---

## 🔐 Authentication API

### 1. ลงทะเบียนผู้ใช้ใหม่

**Endpoint:** `POST /auth/register`

**Description:** สร้าง user ใหม่ในระบบ (role = student โดยอัตโนมัติ)

**Authentication:** ❌ ไม่ต้องการ

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Input Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | String | ✅ Yes | Max 255 ตัวอักษร |
| email | String | ✅ Yes | รูปแบบ email ถูกต้อง, ห้ามซ้ำ |
| password | String | ✅ Yes | อย่างน้อย 8 ตัวอักษร |

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Registered successfully",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role_id": 3,
      "status": "active",
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## 📚 Program API

### 1. ดึงรายการ Programs ทั้งหมด

**Endpoint:** `GET /programs`

**Description:** แสดง programs ทั้งหมดในระบบ

**Authentication:** ✅ Required

**Query Parameters:** ไม่มี

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Programs retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Laravel Advanced Course",
      "code": "LAR-ADV-001",
      "description": "เรียนรู้ Laravel ขั้นสูง",
      "category": "Web Development",
      "duration_hours": 40,
      "image_url": "https://example.com/image.jpg",
      "status": "active",
      "approval_status": "approved",
      "created_by": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 2. ดึงข้อมูล Program เดียว

**Endpoint:** `GET /programs/{id}`

**Description:** แสดงข้อมูล program ตาม ID

**Authentication:** ✅ Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | Integer | Program ID |

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Program retrieved successfully",
  "data": {
    "id": 1,
    "name": "Laravel Advanced Course",
    "code": "LAR-ADV-001",
    "description": "เรียนรู้ Laravel ขั้นสูง",
    "category": "Web Development",
    "duration_hours": 40,
    "image_url": "https://example.com/image.jpg",
    "status": "active",
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

---

### 3. สร้าง Program ใหม่

**Endpoint:** `POST /programs`

**Description:** สร้างหลักสูตรใหม่

**Authentication:** ✅ Required

**Request Body:**
```json
{
  "name": "Vue.js Fundamentals",
  "code": "VUE-FUND-001",
  "description": "เรียน Vue.js เบื้องต้น",
  "category": "Frontend Development",
  "duration_hours": 30,
  "image_url": "https://example.com/vue.jpg",
  "status": "active"
}
```

**Input Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | String | ✅ Yes | Max 255 ตัวอักษร |
| code | String | ✅ Yes | Max 50 ตัวอักษร, ห้ามซ้ำ |
| description | String | ❌ No | - |
| category | String | ✅ Yes | Max 100 ตัวอักษร |
| duration_hours | Integer | ✅ Yes | อย่างน้อย 1 ชั่วโมง |
| image_url | String | ❌ No | ต้องเป็น URL, Max 2048 |
| status | String | ✅ Yes | `active` หรือ `inactive` |

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Program created successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Fundamentals",
    "code": "VUE-FUND-001",
    "description": "เรียน Vue.js เบื้องต้น",
    "category": "Frontend Development",
    "duration_hours": 30,
    "status": "active",
    "created_by": 1,
    "created_at": "2025-01-02T00:00:00.000000Z"
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "message": "The code has already been taken.",
  "errors": {
    "code": ["The code has already been taken."]
  }
}
```

---

### 4. แก้ไข Program

**Endpoint:** `PUT /programs/{id}` หรือ `PATCH /programs/{id}`

**Description:** แก้ไขข้อมูล program

**Authentication:** ✅ Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | Integer | Program ID ที่ต้องการแก้ไข |

**Request Body (ส่งแค่ field ที่ต้องการแก้ไข):**
```json
{
  "name": "Vue.js Advanced",
  "duration_hours": 50,
  "status": "inactive"
}
```

**Input Validation:** เหมือนการสร้าง แต่ทุก field เป็น optional

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Program updated successfully",
  "data": {
    "id": 2,
    "name": "Vue.js Advanced",
    "code": "VUE-FUND-001",
    "duration_hours": 50,
    "status": "inactive",
    "updated_at": "2025-01-02T10:00:00.000000Z"
  }
}
```

---

### 5. ลบ Program

**Endpoint:** `DELETE /programs/{id}`

**Description:** ลบ program ออกจากระบบ

**Authentication:** ✅ Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | Integer | Program ID ที่ต้องการลบ |

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Program deleted successfully",
  "data": null
}
```

---

## 🎓 Training Session API

### 1. ดึงรายการ Sessions ทั้งหมด

**Endpoint:** `GET /sessions`

**Description:** แสดง training sessions ทั้งหมด (สามารถ filter ได้)

**Authentication:** ✅ Required

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| program_id | Integer | ❌ No | กรองตาม program ID |

**ตัวอย่าง:**
```
GET /sessions
GET /sessions?program_id=1
```

**Success Response (200 OK):**
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
      "approval_status": "approved",
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 2. ดึงข้อมูล Session เดียว

**Endpoint:** `GET /sessions/{id}`

**Description:** แสดงข้อมูล training session ตาม ID

**Authentication:** ✅ Required

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | Integer | Session ID |

**Success Response (200 OK):**
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

### 3. สร้าง Session ใหม่

**Endpoint:** `POST /sessions`

**Description:** สร้าง training session ใหม่

**Authentication:** ✅ Required

**Request Body:**
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

**Input Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| program_id | Integer | ✅ Yes | ต้องมีใน programs table |
| title | String | ✅ Yes | Max 255 ตัวอักษร |
| start_date | Date | ✅ Yes | รูปแบบ YYYY-MM-DD, ต้องมาก่อน end_date |
| end_date | Date | ✅ Yes | รูปแบบ YYYY-MM-DD, ต้องมาหลัง start_date |
| start_time | Time | ❌ No | รูปแบบ HH:MM (เช่น 09:00) |
| end_time | Time | ❌ No | รูปแบบ HH:MM, ต้องมาหลัง start_time |
| capacity | Integer | ✅ Yes | อย่างน้อย 1 คน |
| trainer_id | Integer | ✅ Yes | ต้องมีใน users table |
| location | String | ❌ No | Max 255 ตัวอักษร |
| status | String | ❌ No | `upcoming`, `open`, `closed`, `completed`, `cancelled` |

**Success Response (201 Created):**
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
    "trainer_id": 2,
    "created_at": "2025-01-02T00:00:00.000000Z"
  }
}
```

**Error Response (422 - Invalid Dates):**
```json
{
  "message": "The start date must be before the end date.",
  "errors": {
    "start_date": ["The start date must be before the end date."]
  }
}
```

---

### 4. แก้ไข Session

**Endpoint:** `PUT /sessions/{id}` หรือ `PATCH /sessions/{id}`

**Description:** แก้ไขข้อมูล training session

**Authentication:** ✅ Required

**Request Body (ส่งแค่ field ที่ต้องการแก้ไข):**
```json
{
  "title": "Laravel Advanced - Batch 2 (Updated)",
  "capacity": 30,
  "status": "closed"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Session updated successfully",
  "data": {
    "id": 2,
    "title": "Laravel Advanced - Batch 2 (Updated)",
    "capacity": 30,
    "status": "closed",
    "updated_at": "2025-01-02T10:00:00.000000Z"
  }
}
```

---

### 5. ลบ Session

**Endpoint:** `DELETE /sessions/{id}`

**Description:** ลบ training session ออกจากระบบ

**Authentication:** ✅ Required

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Session deleted successfully",
  "data": null
}
```

---

## 👥 Admin User API

### 1. ดึงรายการ Users (พร้อม Filter + Pagination)

**Endpoint:** `GET /admin/users`

**Description:** แสดง users ทั้งหมดในระบบ (Admin เท่านั้น)

**Authentication:** ✅ Required (Admin only)

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| role | String | ❌ No | กรองตาม role (`admin`, `trainer`, `student`) |
| status | String | ❌ No | กรองตาม status (`active`, `inactive`) |
| per_page | Integer | ❌ No | จำนวนต่อหน้า (default: 15, max: 100) |

**ตัวอย่าง:**
```
GET /admin/users
GET /admin/users?role=trainer
GET /admin/users?status=active&per_page=20
```

**Success Response (200 OK - Paginated):**
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
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 15,
    "last_page": 4
  }
}
```

---

### 2. สร้าง User ใหม่ (Admin)

**Endpoint:** `POST /admin/users`

**Description:** Admin สร้าง user ใหม่ (เลือก role ได้)

**Authentication:** ✅ Required (Admin only)

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password123",
  "role": "trainer"
}
```

**Input Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | String | ✅ Yes | Max 255 ตัวอักษร |
| email | String | ✅ Yes | รูปแบบ email ถูกต้อง, ห้ามซ้ำ |
| password | String | ✅ Yes | อย่างน้อย 8 ตัวอักษร |
| role | String | ✅ Yes | `admin`, `trainer`, `student` |

**Success Response (201 Created):**
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
    "created_at": "2025-01-02T00:00:00.000000Z"
  }
}
```

---

### 3. แก้ไข User

**Endpoint:** `PUT /admin/users/{id}`

**Description:** Admin แก้ไขข้อมูล user (เปลี่ยน role, status ได้)

**Authentication:** ✅ Required (Admin only)

**Request Body (ส่งแค่ field ที่ต้องการแก้ไข):**
```json
{
  "name": "Jane Smith (Updated)",
  "role": "admin",
  "status": "inactive"
}
```

**Input Validation:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | String | ❌ No | Max 255 ตัวอักษร |
| email | String | ❌ No | รูปแบบ email ถูกต้อง, ห้ามซ้ำ |
| role | String | ❌ No | `admin`, `trainer`, `student` |
| status | String | ❌ No | `active`, `inactive` |

**Success Response (200 OK):**
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

### 4. ลบ / Deactivate User

**Endpoint:** `DELETE /admin/users/{id}`

**Description:** ปิดการใช้งาน user (soft delete - เปลี่ยน status เป็น inactive)

**Authentication:** ✅ Required (Admin only)

**Success Response (200 OK):**
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

## ❌ Error Responses

### 1. Unauthorized (401)
**เมื่อไหร่:** ไม่มี token หรือ token หมดอายุ

```json
{
  "message": "Unauthenticated."
}
```

**แก้ไข:** เข้าสู่ระบบใหม่ และใช้ token ใหม่

---

### 2. Forbidden (403)
**เมื่อไหร่:** มี token แต่ไม่มีสิทธิ์เข้าถึง (เช่น ไม่ใช่ Admin)

```json
{
  "success": false,
  "message": "Forbidden"
}
```

**แก้ไข:** ใช้ user ที่มีสิทธิ์เหมาะสม

---

### 3. Not Found (404)
**เมื่อไหร่:** หา resource ตาม ID ไม่เจอ

```json
{
  "message": "No query results for model [App\\Models\\Program] 999"
}
```

**แก้ไข:** ตรวจสอบ ID ว่ามีอยู่ในระบบ

---

### 4. Validation Error (422)
**เมื่อไหร่:** ข้อมูลที่ส่งมาไม่ผ่าน validation

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

**แก้ไข:** แก้ไขข้อมูลตาม errors ที่ระบุ

---

### 5. Server Error (500)
**เมื่อไหร่:** มีปัญหาที่ server

```json
{
  "message": "Server Error",
  "error": "Internal server error occurred"
}
```

**แก้ไข:** ติดต่อ developer หรือตรวจสอบ server logs

---

## 📊 Status Codes สรุป

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | สำเร็จ (GET, PUT, DELETE) |
| 201 | Created | สร้างสำเร็จ (POST) |
| 204 | No Content | ลบสำเร็จ (บาง endpoints) |
| 400 | Bad Request | Request ผิดพลาด |
| 401 | Unauthorized | ไม่มี token หรือ token ไม่ถูกต้อง |
| 403 | Forbidden | ไม่มีสิทธิ์เข้าถึง |
| 404 | Not Found | ไม่พบข้อมูล |
| 422 | Validation Error | ข้อมูล validation ไม่ผ่าน |
| 500 | Server Error | เกิดข้อผิดพลาดที่ server |

---

## 🔗 Related Documents

- [README.md](../README.md) - วิธีใช้งาน API
- [ADMIN_GUIDE.md](./ADMIN_GUIDE.md) - คู่มือ Admin
- [TESTING_SUMMARY.md](../TESTING_SUMMARY.md) - สรุปการทดสอบ

---

**วันที่:** 12 ธันวาคม 2025
