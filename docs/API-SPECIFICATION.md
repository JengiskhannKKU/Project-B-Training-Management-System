# API Specification

เอกสารนี้อธิบาย API endpoints ทั้งหมดของระบบ Training Management System

---

## 1. Authentication APIs

### 1.1 สมัครสมาชิก (Register)

**Endpoint:** `POST /auth/register`

**คำอธิบาย:**
ใช้สำหรับสมัครสมาชิกใหม่เข้าระบบ

**Request Body:**
```json
{
  "name": "ชื่อผู้ใช้",
  "email": "user@example.com",
  "password": "รหัสผ่าน",
  "password_confirmation": "รหัสผ่านยืนยัน"
}
```

**Response (สำเร็จ - 201):**
```json
{
  "message": "Registration successful",
  "user": {
    "id": 1,
    "name": "ชื่อผู้ใช้",
    "email": "user@example.com",
    "role": "student"
  }
}
```

**Response (ล้มเหลว - 422):**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**การใช้งาน:**
- ใช้สำหรับผู้ใช้ใหม่ที่ต้องการสมัครเข้าระบบ
- จะได้รับ role = "student" โดยอัตโนมัติ
- Admin สามารถเปลี่ยน role ได้ภายหลัง

---

## 2. Admin - User Management APIs

### 2.1 ดึงรายการผู้ใช้ทั้งหมด

**Endpoint:** `GET /api/admin/users`

**คำอธิบาย:**
ดึงรายการผู้ใช้ทั้งหมดในระบบ (เฉพาะ Admin)

**Query Parameters:**
- `search` (optional): ค้นหาจากชื่อหรืออีเมล
- `role` (optional): กรองตาม role (admin, trainer, student)
- `page` (optional): หมายเลขหน้า (default: 1)
- `per_page` (optional): จำนวนรายการต่อหน้า (default: 15)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "ชื่อผู้ใช้",
      "email": "user@example.com",
      "role": {
        "id": 3,
        "name": "student"
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

### 2.2 สร้างผู้ใช้ใหม่

**Endpoint:** `POST /api/admin/users`

**Request Body:**
```json
{
  "name": "ชื่อผู้ใช้",
  "email": "newuser@example.com",
  "password": "รหัสผ่าน",
  "role_id": 3
}
```

**Response (201):**
```json
{
  "message": "User created successfully",
  "user": {
    "id": 10,
    "name": "ชื่อผู้ใช้",
    "email": "newuser@example.com",
    "role": {
      "id": 3,
      "name": "student"
    }
  }
}
```

### 2.3 อัปเดตข้อมูลผู้ใช้

**Endpoint:** `PUT /api/admin/users/{id}`

**Request Body:**
```json
{
  "name": "ชื่อใหม่",
  "email": "newemail@example.com",
  "role_id": 2
}
```

**Response (200):**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 10,
    "name": "ชื่อใหม่",
    "email": "newemail@example.com",
    "role": {
      "id": 2,
      "name": "trainer"
    }
  }
}
```

### 2.4 ลบผู้ใช้

**Endpoint:** `DELETE /api/admin/users/{id}`

**Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

---

## 3. Profile Management APIs

### 3.1 ดูข้อมูล Profile ของตัวเอง

**Endpoint:** `GET /api/me`

**คำอธิบาย:**
ดึงข้อมูล profile ของผู้ใช้ที่ login อยู่

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "ชื่อผู้ใช้",
    "email": "user@example.com",
    "role": {
      "id": 3,
      "name": "student"
    }
  },
  "profile": {
    "phone": "0812345678",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "organization": "บริษัท ABC",
    "department": "IT",
    "bio": "คำแนะนำตัว"
  },
  "avatar_present": true
}
```

### 3.2 อัปเดตข้อมูล Profile

**Endpoint:** `PUT /api/me/profile`

**Request Body:**
```json
{
  "name": "ชื่อใหม่",
  "phone": "0898765432",
  "date_of_birth": "1990-01-01",
  "gender": "female",
  "organization": "บริษัท XYZ",
  "department": "HR",
  "bio": "คำแนะนำตัวใหม่"
}
```

**Response (200):**
```json
{
  "message": "Profile updated successfully",
  "user": { ... },
  "profile": { ... }
}
```

---

## 4. Avatar Management APIs

### 4.1 ดู Avatar ของตัวเอง

**Endpoint:** `GET /api/me/avatar`

**คำอธิบาย:**
ดึงรูป avatar ของผู้ใช้ (binary image)

**Response (200):**
- Content-Type: image/jpeg, image/png, etc.
- Binary image data

**Response (404):**
```json
{
  "message": "Avatar not found"
}
```

### 4.2 อัปโหลด Avatar

**Endpoint:** `POST /api/me/avatar`

**Request Body:**
Form-data
- `avatar` (file): ไฟล์รูปภาพ (jpg, png, jpeg)

**Response (200):**
```json
{
  "message": "Avatar uploaded successfully",
  "avatar_url": "/api/me/avatar"
}
```

**ข้อจำกัด:**
- ขนาดไฟล์ไม่เกิน 2MB
- รองรับ: jpg, jpeg, png

### 4.3 ลบ Avatar

**Endpoint:** `DELETE /api/me/avatar`

**Response (200):**
```json
{
  "message": "Avatar deleted successfully"
}
```

---

## 5. Catalog APIs (Programs/Sessions)

### 5.1 ดูรายการ Programs ทั้งหมด

**Endpoint:** `GET /api/programs`

**Query Parameters:**
- `search` (optional): ค้นหาจากชื่อหรือคำอธิบาย
- `category` (optional): กรองตามหมวดหมู่
- `status` (optional): กรองตามสถานะ (draft, published)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "ชื่อคอร์ส",
      "code": "COURSE-001",
      "description": "คำอธิบายคอร์ส",
      "category": "Programming",
      "level": "Beginner",
      "duration": "3 days",
      "image_url": "/storage/programs/image.jpg",
      "status": "published"
    }
  ]
}
```

### 5.2 ดูรายละเอียด Program

**Endpoint:** `GET /api/programs/{id}`

**Response (200):**
```json
{
  "id": 1,
  "name": "ชื่อคอร์ส",
  "code": "COURSE-001",
  "description": "คำอธิบายคอร์ส",
  "category": "Programming",
  "level": "Beginner",
  "duration": "3 days",
  "image_url": "/storage/programs/image.jpg",
  "sessions": [
    {
      "id": 1,
      "title": "Session 1",
      "start_date": "2025-02-01",
      "end_date": "2025-02-03",
      "start_time": "09:00",
      "end_time": "17:00",
      "location": "ห้อง 201",
      "capacity": 30,
      "enrolled_count": 15,
      "status": "open"
    }
  ]
}
```

### 5.3 ดู Sessions ของ Program

**Endpoint:** `GET /api/programs/{program_id}/sessions`

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Session 1",
      "start_date": "2025-02-01",
      "end_date": "2025-02-03",
      "start_time": "09:00",
      "end_time": "17:00",
      "location": "ห้อง 201",
      "trainer": "ชื่อวิทยากร",
      "capacity": 30,
      "enrolled_count": 15,
      "status": "open",
      "approval_status": "approved"
    }
  ]
}
```

---

## 6. Enrollment APIs

### 6.1 ลงทะเบียนเรียน (Enroll)

**Endpoint:** `POST /api/enrollments`

**Request Body:**
```json
{
  "session_id": 1
}
```

**Response (สำเร็จ - 201):**
```json
{
  "message": "Enrollment created successfully",
  "data": {
    "id": 10,
    "user_id": 5,
    "session_id": 1,
    "status": "pending",
    "enrolled_at": "2025-01-15T10:00:00.000000Z"
  }
}
```

**Response (ล้มเหลว - 422):**
```json
{
  "message": "Cannot enroll: Session is closed or not open for registration"
}
```

**กรณีที่ไม่สามารถลงทะเบียนได้:**
- Session ไม่ได้รับอนุมัติ (approval_status != 'approved')
- Session ไม่เปิดรับสมัคร (status != 'open')
- Session เต็มแล้ว (enrolled >= capacity)
- Session เสร็จสิ้นแล้ว (status = 'completed')
- ลงทะเบียนซ้ำ (มี enrollment อยู่แล้ว และไม่ใช่ status = 'cancelled')

### 6.2 ยกเลิกการลงทะเบียน

**Endpoint:** `PUT /api/enrollments/{id}/cancel`

**Response (200):**
```json
{
  "message": "Enrollment cancelled successfully",
  "data": {
    "id": 10,
    "status": "cancelled"
  }
}
```

**Response (ล้มเหลว - 422):**
```json
{
  "message": "Cannot cancel on or after the start date"
}
```

**เงื่อนไขการยกเลิก:**
- ต้องเป็นเจ้าของ enrollment
- Session ยังไม่เริ่ม (start_date > วันนี้)
- Status ไม่ใช่ 'cancelled' อยู่แล้ว

### 6.3 ดูรายการลงทะเบียนของตัวเอง

**Endpoint:** `GET /api/me/enrollments`

**Response (200):**
```json
[
  {
    "id": 10,
    "user_id": 5,
    "session_id": 1,
    "status": "pending",
    "enrolled_at": "2025-01-15T10:00:00.000000Z",
    "session": {
      "id": 1,
      "title": "Session 1",
      "start_date": "2025-02-01",
      "end_date": "2025-02-03",
      "start_time": "09:00",
      "end_time": "17:00",
      "location": "ห้อง 201",
      "trainer": "ชื่อวิทยากร",
      "status": "open",
      "program": {
        "id": 1,
        "name": "ชื่อคอร์ส",
        "code": "COURSE-001",
        "category": "Programming",
        "image_url": "/storage/programs/image.jpg"
      }
    }
  }
]
```

---

## สถานะต่างๆ ในระบบ

### Enrollment Status
- `pending`: รอการอนุมัติ (เพิ่งลงทะเบียน)
- `confirmed`: อนุมัติแล้ว (สามารถเข้าเรียนได้)
- `completed`: เรียนจบแล้ว (สามารถออก certificate ได้)
- `cancelled`: ยกเลิกแล้ว

### Session Status
- `pending`: รอการอนุมัติ
- `open`: เปิดรับสมัคร
- `closed`: ปิดรับสมัครแล้ว (หลัง end_date)
- `completed`: เสร็จสิ้นแล้ว (หลังจบ + ประเมินผลแล้ว)
- `cancelled`: ยกเลิก

### Program Status
- `draft`: ร่าง (ยังไม่เผยแพร่)
- `published`: เผยแพร่แล้ว (แสดงใน catalog)
- `archived`: เก็บถาวร

---

## การ Authentication

ทุก API (ยกเว้น register) ต้องใช้ token authentication:

**Header:**
```
Authorization: Bearer {token}
```

**ตัวอย่าง:**
```bash
curl -H "Authorization: Bearer your-token-here" \
     https://api.example.com/api/me
```

---

## Error Codes

| Code | ความหมาย |
|------|----------|
| 200 | สำเร็จ |
| 201 | สร้างสำเร็จ |
| 401 | ไม่ได้ login หรือ token หมดอายุ |
| 403 | ไม่มีสิทธิ์เข้าถึง |
| 404 | ไม่พบข้อมูล |
| 422 | ข้อมูลไม่ถูกต้อง (Validation Error) |
| 500 | เกิดข้อผิดพลาดในระบบ |

---

**อัปเดตล่าสุด:** 2025-01-15
**เวอร์ชัน:** 1.0
