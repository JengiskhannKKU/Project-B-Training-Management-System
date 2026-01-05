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

## 7. Session Management APIs

### 7.1 Mark Session as Completed

**Endpoint:** `POST /api/sessions/{id}/complete`

**Authorization:** Admin or Session Trainer

**คำอธิบาย:**
Mark session เป็น completed และ trigger auto-evaluation ของ enrollments ทั้งหมด

**Response (สำเร็จ - 200):**
```json
{
  "message": "Session marked as completed.",
  "data": {
    "session": {
      "id": 1,
      "title": "Session 1",
      "status": "completed"
    },
    "summary": {
      "total": 10,
      "completed": 7
    }
  }
}
```

**Response (ล้มเหลว - 422):**
```json
{
  "message": "Cannot complete: Session status must be 'open' or 'closed'"
}
```

**เงื่อนไข:**
- Session status ต้องเป็น 'open' หรือ 'closed'
- ผู้ใช้ต้องเป็น admin หรือ trainer ของ session นั้น
- เมื่อ mark completed แล้ว:
  - `session.status = 'completed'`
  - Auto-evaluate ทุก enrollment ในระบบ
  - Enrollment ที่มา ≥ 80% จะถูกตั้งเป็น `status = 'completed'`

### 7.2 ดูรายการ Sessions (Admin)

**Endpoint:** `GET /api/admin/sessions`

**Authorization:** Admin

**คำอธิบาย:**
ดูรายการ programs พร้อม sessions ทั้งหมดในระบบ (สำหรับหน้า Attendance)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "code": "PRG-001",
      "name": "Program Name",
      "request_id": 5,
      "image_url": "...",
      "rating": 4.5,
      "level": "Beginner",
      "students_count": 25,
      "price": "Free",
      "date": "Jan 15 - Jan 20, 2025",
      "time": "09:00 - 17:00",
      "location": "Room 201",
      "category": "Programming",
      "duration": "40 hours",
      "status": "active",
      "sessions": [
        {
          "id": 1,
          "name": "Session 1",
          "title": "Session 1",
          "date": "Jan 15, 2025",
          "start_date": "2025-01-15",
          "end_date": "2025-01-20",
          "time": "09:00 - 17:00",
          "start_time": "09:00",
          "end_time": "17:00",
          "location": "Room 201",
          "capacity": 30,
          "enrolled": 25,
          "status": "open"
        }
      ]
    }
  ],
  "message": "Admin programs retrieved successfully"
}
```

### 7.3 ดูรายการ Sessions (Trainer)

**Endpoint:** `GET /api/trainer/sessions`

**Authorization:** Trainer

**คำอธิบาย:**
ดูรายการ programs พร้อม sessions ที่ trainer เป็นผู้สร้าง

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "code": "PRG-001",
      "name": "Program Name",
      "request_id": 5,
      "image_url": "...",
      "sessions": [...]
    }
  ],
  "message": "Trainer programs retrieved successfully"
}
```

---

## 8. Attendance Management APIs

### 8.1 ดู Attendance ของ Enrollment (Student)

**Endpoint:** `GET /api/enrollments/{enrollment}/attendances`

**Authorization:** Student (owner) or Admin/Trainer

**คำอธิบาย:**
Student ดูประวัติการเข้าเรียนของตัวเอง

**Response (200):**
```json
[
  {
    "id": 1,
    "enrollment_id": 10,
    "session_id": 1,
    "user_id": 5,
    "attendance_date": "2025-01-15",
    "status": "present",
    "notes": null,
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-01-15T10:00:00.000000Z"
  },
  {
    "id": 2,
    "enrollment_id": 10,
    "session_id": 1,
    "user_id": 5,
    "attendance_date": "2025-01-16",
    "status": "late",
    "notes": "Arrived 30 minutes late",
    "created_at": "2025-01-16T10:00:00.000000Z",
    "updated_at": "2025-01-16T10:00:00.000000Z"
  }
]
```

### 8.2 ดู Enrollments สำหรับบันทึก Attendance

**Endpoint:** `GET /api/sessions/{session}/enrollments-for-attendance`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
ดึงรายชื่อผู้เรียนทั้งหมดใน session สำหรับหน้าบันทึกการเข้าเรียน

**Query Parameters:**
- `date` (required): วันที่ต้องการบันทึก (YYYY-MM-DD)

**Response (200):**
```json
{
  "data": [
    {
      "id": 10,
      "user_id": 5,
      "session_id": 1,
      "status": "confirmed",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "attendance": {
        "id": 1,
        "attendance_date": "2025-01-15",
        "status": "present",
        "notes": null
      }
    }
  ]
}
```

**หมายเหตุ:**
- ถ้ายังไม่มีการบันทึก attendance สำหรับวันนั้น `attendance` จะเป็น `null`
- ใช้สำหรับแสดงรายชื่อนักเรียนและสถานะการเข้าเรียนในวันที่เลือก

### 8.3 ดู Attendances ทั้งหมดของ Session

**Endpoint:** `GET /api/sessions/{session}/attendances`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
ดู attendance records ทั้งหมดของ session

**Query Parameters:**
- `date` (optional): กรองตามวันที่ (YYYY-MM-DD)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "enrollment_id": 10,
      "user_id": 5,
      "attendance_date": "2025-01-15",
      "status": "present",
      "notes": null,
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ]
}
```

### 8.4 ดู Attendance Summary

**Endpoint:** `GET /api/sessions/{session}/attendance-summary`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
ดูสรุปการเข้าเรียนของ session (จำนวนที่มา/สาย/ขาด แต่ละวัน)

**Response (200):**
```json
{
  "summary": {
    "2025-01-15": {
      "present": 20,
      "late": 3,
      "absent": 2,
      "total": 25
    },
    "2025-01-16": {
      "present": 18,
      "late": 4,
      "absent": 3,
      "total": 25
    }
  },
  "overall": {
    "total_enrollments": 25,
    "average_attendance": 88.0
  }
}
```

### 8.5 ดู Eligible Enrollments

**Endpoint:** `GET /api/sessions/{session}/eligible-enrollments`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
ดูรายชื่อผู้เรียนที่สามารถบันทึก attendance ได้ (status = 'confirmed' หรือ 'completed')

**Response (200):**
```json
{
  "data": [
    {
      "id": 10,
      "user_id": 5,
      "session_id": 1,
      "status": "confirmed",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ]
}
```

### 8.6 บันทึก Attendance (Single)

**Endpoint:** `POST /api/attendances`

**Authorization:** Trainer or Admin

**Request Body:**
```json
{
  "enrollment_id": 10,
  "session_id": 1,
  "attendance_date": "2025-01-15",
  "status": "present",
  "notes": "Optional notes"
}
```

**Response (สำเร็จ - 201):**
```json
{
  "message": "Attendance recorded successfully",
  "data": {
    "id": 1,
    "enrollment_id": 10,
    "session_id": 1,
    "user_id": 5,
    "attendance_date": "2025-01-15",
    "status": "present",
    "notes": "Optional notes"
  }
}
```

**Validation:**
- `enrollment_id` (required): ต้องมี enrollment ที่ระบุ
- `session_id` (required): ต้องตรงกับ enrollment.session_id
- `attendance_date` (required): ต้องอยู่ระหว่าง session.start_date และ session.end_date
- `status` (required): 'present', 'late', หรือ 'absent'
- `notes` (optional): บันทึกเพิ่มเติม

### 8.7 อัปเดต Attendance

**Endpoint:** `PUT /api/attendances/{attendance}`

**Authorization:** Trainer or Admin

**Request Body:**
```json
{
  "status": "late",
  "notes": "Arrived 30 minutes late"
}
```

**Response (200):**
```json
{
  "message": "Attendance updated successfully",
  "data": {
    "id": 1,
    "enrollment_id": 10,
    "session_id": 1,
    "user_id": 5,
    "attendance_date": "2025-01-15",
    "status": "late",
    "notes": "Arrived 30 minutes late"
  }
}
```

### 8.8 บันทึก Attendance (Bulk)

**Endpoint:** `POST /api/sessions/{session}/attendances/bulk`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
บันทึก attendance หลายคนพร้อมกันในวันเดียวกัน

**Request Body:**
```json
{
  "attendance_date": "2025-01-15",
  "records": [
    {
      "enrollment_id": 10,
      "status": "present",
      "notes": null
    },
    {
      "enrollment_id": 11,
      "status": "late",
      "notes": "Arrived 15 minutes late"
    },
    {
      "enrollment_id": 12,
      "status": "absent",
      "notes": null
    }
  ]
}
```

**Response (สำเร็จ - 200):**
```json
{
  "message": "Bulk attendance recorded successfully",
  "data": {
    "created": 15,
    "updated": 10,
    "total": 25
  }
}
```

**หมายเหตุ:**
- ถ้ามี attendance record อยู่แล้วสำหรับ enrollment + date นั้น จะทำการ update แทน
- ใช้สำหรับบันทึกการเข้าเรียนของนักเรียนทั้งหมดในหนึ่งวัน

---

## 9. Certificate APIs

### 9.1 ดูรายการ Certificates ของตัวเอง (Student)

**Endpoint:** `GET /api/me/certificates`

**Authorization:** Student

**Response (200):**
```json
[
  {
    "id": 1,
    "certificate_code": "CERT-2025-001",
    "issued_at": "2025-01-20",
    "enrollment": {
      "id": 10,
      "session": {
        "id": 1,
        "title": "Session 1",
        "program": {
          "id": 1,
          "name": "Program Name"
        }
      }
    }
  }
]
```

### 9.2 ดูรายละเอียด Certificate

**Endpoint:** `GET /api/certificates/{certificate}`

**Authorization:** Owner, Trainer, or Admin

**Response (200):**
```json
{
  "id": 1,
  "certificate_code": "CERT-2025-001",
  "issued_at": "2025-01-20",
  "enrollment": {
    "id": 10,
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "session": {
      "id": 1,
      "title": "Session 1",
      "start_date": "2025-01-15",
      "end_date": "2025-01-20",
      "program": {
        "id": 1,
        "name": "Program Name",
        "code": "PRG-001"
      }
    }
  }
}
```

### 9.3 Download Certificate (PDF)

**Endpoint:** `GET /api/certificates/{certificate}/download`

**Authorization:** Owner, Trainer, or Admin

**Response:**
- Content-Type: application/pdf
- Binary PDF file

### 9.4 View Certificate (In Browser)

**Endpoint:** `GET /api/certificates/{certificate}/view`

**Authorization:** Owner, Trainer, or Admin

**Response:**
- Content-Type: application/pdf
- Content-Disposition: inline (แสดงใน browser)

### 9.5 Verify Certificate (Public)

**Endpoint:** `GET /api/verify/{certificateCode}`

**Authorization:** None (Public)

**คำอธิบาย:**
ตรวจสอบความถูกต้องของใบรับรองผ่าน QR code

**Response (200):**
```json
{
  "valid": true,
  "certificate": {
    "certificate_code": "CERT-2025-001",
    "issued_at": "2025-01-20",
    "holder_name": "John Doe",
    "program_name": "Program Name",
    "session_title": "Session 1",
    "revoked": false
  }
}
```

**Response (404):**
```json
{
  "valid": false,
  "message": "Certificate not found"
}
```

### 9.6 Generate Certificates for Session

**Endpoint:** `POST /api/sessions/{session}/certificates/generate`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
สร้างใบรับรองสำหรับผู้เรียนทุกคนที่มี enrollment.status = 'completed' ใน session

**Response (200):**
```json
{
  "message": "Certificates generated successfully",
  "data": {
    "total": 10,
    "generated": 7,
    "skipped": 3,
    "errors": []
  }
}
```

### 9.7 Generate Certificates for Program

**Endpoint:** `POST /api/programs/{program}/certificates/generate`

**Authorization:** Trainer or Admin

**คำอธิบาย:**
สร้างใบรับรองสำหรับผู้เรียนทุกคนที่มี enrollment.status = 'completed' ในทุก session ของ program

**Response (200):**
```json
{
  "message": "Certificates generated successfully",
  "data": {
    "total": 25,
    "generated": 20,
    "skipped": 5,
    "errors": []
  }
}
```

---

## สถานะต่างๆ ในระบบ

### Enrollment Status
- `pending`: รอการอนุมัติ (เพิ่งลงทะเบียน)
- `confirmed`: อนุมัติแล้ว (สามารถเข้าเรียนได้)
- `completed`: เรียนจบแล้ว (สามารถออก certificate ได้)
- `cancelled`: ยกเลิกแล้ว

### Session Status
- `upcoming`: กำลังจะมาถึง
- `open`: เปิดรับสมัคร
- `closed`: ปิดรับสมัครแล้ว (หลัง end_date)
- `completed`: เสร็จสิ้นแล้ว (ระบบ auto-evaluate enrollments)
- `cancelled`: ยกเลิก

### Program Status
- `draft`: ร่าง (ยังไม่เผยแพร่)
- `published`: เผยแพร่แล้ว (แสดงใน catalog)
- `active`: ใช้งาน
- `archived`: เก็บถาวร

### Attendance Status
- `present`: มาเรียน (นับเป็นเข้าเรียน ✅)
- `late`: มาสาย (นับเป็นเข้าเรียน ✅)
- `absent`: ขาดเรียน (ไม่นับ ❌)

**หมายเหตุ:**
- Attendance ที่มี status = `present` หรือ `late` จะนับเป็นการเข้าเรียน
- ใช้ในการคำนวณการผ่านหลักสูตร (80% attendance rule)

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

## API Endpoints Summary

### Authentication
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login

### User Management (Admin)
- `GET /api/admin/users` - List users
- `POST /api/admin/users` - Create user
- `PUT /api/admin/users/{id}` - Update user
- `DELETE /api/admin/users/{id}` - Delete user

### Profile
- `GET /api/me` - Get profile
- `PUT /api/me/profile` - Update profile
- `POST /api/me/avatar` - Upload avatar
- `GET /api/me/avatar` - Get avatar
- `DELETE /api/me/avatar` - Delete avatar

### Programs & Sessions
- `GET /api/catalog/programs` - List programs (public)
- `GET /api/catalog/programs/{id}/sessions` - List sessions (public)
- `GET /api/programs/{id}` - Get program details
- `GET /api/sessions` - List sessions

### Enrollments
- `POST /api/enrollments` - Create enrollment (student)
- `PUT /api/enrollments/{id}/cancel` - Cancel enrollment
- `GET /api/me/enrollments` - My enrollments

### Sessions (Admin/Trainer)
- `POST /api/sessions/{id}/complete` - Mark session completed
- `GET /api/admin/sessions` - Admin view sessions
- `GET /api/trainer/sessions` - Trainer view sessions

### Attendance (Admin/Trainer)
- `GET /api/sessions/{session}/enrollments-for-attendance` - Get enrollments for attendance
- `GET /api/sessions/{session}/attendances` - Get all attendances
- `GET /api/sessions/{session}/attendance-summary` - Get summary
- `GET /api/sessions/{session}/eligible-enrollments` - Get eligible students
- `POST /api/attendances` - Create single attendance
- `PUT /api/attendances/{id}` - Update attendance
- `POST /api/sessions/{session}/attendances/bulk` - Bulk create/update

### Attendance (Student)
- `GET /api/enrollments/{enrollment}/attendances` - View own attendance

### Certificates
- `GET /api/me/certificates` - My certificates (student)
- `GET /api/certificates/{id}` - Certificate details
- `GET /api/certificates/{id}/download` - Download PDF
- `GET /api/certificates/{id}/view` - View in browser
- `GET /api/verify/{code}` - Verify certificate (public)
- `POST /api/sessions/{id}/certificates/generate` - Generate for session
- `POST /api/programs/{id}/certificates/generate` - Generate for program

---

**อัปเดตล่าสุด:** 2026-01-06
**เวอร์ชัน:** 2.0

**Changelog:**
- v2.0 (2026-01-06): เพิ่ม Session Management, Attendance Management และ Certificate APIs
- v1.0 (2025-01-15): เวอร์ชันแรก - Authentication, User Management, Profile, Enrollment
