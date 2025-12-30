# Sequence Diagrams

เอกสารนี้แสดง flow การทำงานของระบบในรูปแบบ sequence diagram

---

## 1. Flow หลัก: สมัคร → Login → Update Profile → Enroll Session

### ภาพรวม
```
ผู้ใช้ → สมัครสมาชิก → Login → แก้ไขข้อมูล Profile → ลงทะเบียนเรียน
```

---

### 1.1 การสมัครสมาชิก (Register)

```
ผู้ใช้                    Frontend                Backend                 Database
  |                          |                        |                        |
  |-- กรอกข้อมูลสมัคร ------>|                        |                        |
  |                          |-- POST /auth/register ->|                        |
  |                          |                        |-- ตรวจสอบข้อมูล ----->|
  |                          |                        |                        |
  |                          |                        |<-- email ซ้ำหรือไม่? --|
  |                          |                        |                        |
  |                          |                        |-- สร้าง user --------->|
  |                          |                        |-- สร้าง role=student ->|
  |                          |                        |<-- user id ------------|
  |                          |<-- 201 Created --------|                        |
  |                          |    { user, token }     |                        |
  |<-- แสดงข้อความสำเร็จ ----|                        |                        |
  |-- redirect to login ---->|                        |                        |
```

**ขั้นตอน:**
1. ผู้ใช้กรอกข้อมูล: ชื่อ, email, password
2. Frontend ส่งข้อมูลไปที่ `POST /auth/register`
3. Backend ตรวจสอบว่า email ซ้ำหรือไม่
4. ถ้าไม่ซ้ำ → สร้าง user ใหม่ พร้อม role = "student"
5. ส่งข้อมูล user และ token กลับมา
6. Frontend redirect ไปหน้า login

**กรณีล้มเหลว:**
- Email ซ้ำ → ส่ง error 422 "The email has already been taken"
- Password ไม่ตรงกัน → ส่ง error 422 "Password confirmation does not match"

---

### 1.2 การ Login

```
ผู้ใช้                    Frontend                Backend                 Database
  |                          |                        |                        |
  |-- กรอก email, password -->|                        |                        |
  |                          |-- POST /auth/login --->|                        |
  |                          |                        |-- ตรวจสอบ credentials ->|
  |                          |                        |<-- user data ----------|
  |                          |                        |                        |
  |                          |                        |-- สร้าง token -------->|
  |                          |<-- 200 OK -------------|                        |
  |                          |    { user, token }     |                        |
  |<-- เก็บ token ----------|                        |                        |
  |-- redirect to dashboard ->|                        |                        |
```

**ขั้นตอน:**
1. ผู้ใช้กรอก email และ password
2. Frontend ส่งไปที่ `POST /auth/login`
3. Backend ตรวจสอบ email และ password
4. ถ้าถูกต้อง → สร้าง token และส่งกลับมา
5. Frontend เก็บ token ไว้ (localStorage/cookie)
6. Redirect ไปหน้า dashboard ตาม role

**กรณีล้มเหลว:**
- Email/Password ผิด → ส่ง error 401 "Invalid credentials"

---

### 1.3 การอัปเดต Profile

```
ผู้ใช้                    Frontend                Backend                 Database
  |                          |                        |                        |
  |-- เปิดหน้า Profile ----->|                        |                        |
  |                          |-- GET /api/me -------->|                        |
  |                          |    (with token)        |                        |
  |                          |                        |-- ดึงข้อมูล user ----->|
  |                          |                        |<-- user + profile -----|
  |                          |<-- 200 OK -------------|                        |
  |<-- แสดงข้อมูลปัจจุบัน ----|                        |                        |
  |                          |                        |                        |
  |-- แก้ไขข้อมูล ----------->|                        |                        |
  |   (name, phone, etc.)    |                        |                        |
  |                          |-- PUT /api/me/profile ->|                        |
  |                          |    (with token)        |                        |
  |                          |                        |-- ตรวจสอบข้อมูล ----->|
  |                          |                        |-- update user -------->|
  |                          |                        |-- update profile ----->|
  |                          |                        |<-- success ------------|
  |                          |<-- 200 OK -------------|                        |
  |<-- แสดงข้อความสำเร็จ ----|                        |                        |
```

**ขั้นตอน:**
1. ผู้ใช้เปิดหน้า Profile
2. Frontend ดึงข้อมูลปัจจุบันจาก `GET /api/me`
3. แสดงข้อมูลให้ผู้ใช้แก้ไข
4. เมื่อกด Save → ส่งไปที่ `PUT /api/me/profile`
5. Backend อัปเดตข้อมูลในฐานข้อมูล
6. ส่งข้อความสำเร็จกลับมา

**ข้อมูลที่แก้ไขได้:**
- name, phone, date_of_birth, gender
- organization, department, bio
- avatar (ใช้ API แยก: POST /api/me/avatar)

---

### 1.4 การลงทะเบียนเรียน (Enroll Session)

```
ผู้ใช้                    Frontend                Backend                 Database
  |                          |                        |                        |
  |-- ดูรายการคอร์ส -------->|                        |                        |
  |                          |-- GET /api/programs -->|                        |
  |                          |<-- programs list ------|                        |
  |<-- แสดงรายการคอร์ส ------|                        |                        |
  |                          |                        |                        |
  |-- เลือกคอร์ส ----------->|                        |                        |
  |                          |-- GET /programs/{id} ->|                        |
  |                          |<-- program + sessions -|                        |
  |<-- แสดงรายละเอียด --------|                        |                        |
  |                          |                        |                        |
  |-- เลือก session -------->|                        |                        |
  |-- กดปุ่ม Enroll -------->|                        |                        |
  |                          |-- POST /api/enrollments|                        |
  |                          |    { session_id: 1 }   |                        |
  |                          |    (with token)        |                        |
  |                          |                        |-- ตรวจสอบ session ---->|
  |                          |                        |<-- session data -------|
  |                          |                        |                        |
  |                          |                        |-- เช็คเงื่อนไข:       |
  |                          |                        |   - status = open?     |
  |                          |                        |   - ไม่เต็มแล้ว?      |
  |                          |                        |   - ไม่ซ้ำ?           |
  |                          |                        |                        |
  |                          |                        |-- สร้าง enrollment --->|
  |                          |                        |   status = pending     |
  |                          |                        |<-- enrollment id ------|
  |                          |<-- 201 Created --------|                        |
  |<-- แสดงข้อความสำเร็จ ----|                        |                        |
  |<-- ไป My Courses --------|                        |                        |
```

**ขั้นตอน:**
1. ผู้ใช้ดูรายการคอร์สจาก `GET /api/programs`
2. เลือกคอร์สที่สนใจ → ดูรายละเอียด `GET /api/programs/{id}`
3. เลือก session ที่ต้องการ
4. กดปุ่ม "Enroll" → ส่ง `POST /api/enrollments` พร้อม session_id
5. Backend ตรวจสอบเงื่อนไข:
   - Session เปิดรับสมัครหรือไม่? (status = 'open')
   - ที่นั่งเต็มหรือยัง? (enrolled < capacity)
   - ลงทะเบียนซ้ำหรือไม่?
   - Session จบแล้วหรือยัง? (status != 'completed')
6. ถ้าผ่านทุกเงื่อนไข → สร้าง enrollment พร้อม status = "pending"
7. ส่งข้อความสำเร็จกลับมา
8. Frontend redirect ไปหน้า "My Courses"

**กรณีล้มเหลว:**
- Session เต็มแล้ว → "Cannot enroll: Session capacity is full"
- Session ปิดรับสมัคร → "Cannot enroll: Session is closed"
- Session เสร็จสิ้นแล้ว → "Cannot enroll: Session has already been completed"
- ลงทะเบียนซ้ำ → "You are already enrolled in this session"

---

## 2. Flow การยกเลิกการลงทะเบียน

```
ผู้ใช้                    Frontend                Backend                 Database
  |                          |                        |                        |
  |-- เปิด My Courses ------>|                        |                        |
  |                          |-- GET /api/me/enrollments|                      |
  |                          |<-- enrollments list ---|                        |
  |<-- แสดงรายการที่ลงไว้ ----|                        |                        |
  |                          |                        |                        |
  |-- กดปุ่ม Cancel -------->|                        |                        |
  |   (เฉพาะ pending)        |                        |                        |
  |                          |-- PUT /enrollments/{id}/cancel               |
  |                          |    (with token)        |                        |
  |                          |                        |-- ตรวจสอบ:           |
  |                          |                        |   - เป็นเจ้าของ?      |
  |                          |                        |   - session ยังไม่เริ่ม?|
  |                          |                        |                        |
  |                          |                        |-- update status ------>|
  |                          |                        |   = cancelled          |
  |                          |                        |<-- success ------------|
  |                          |<-- 200 OK -------------|                        |
  |<-- แสดงข้อความสำเร็จ ----|                        |                        |
  |<-- badge เปลี่ยนเป็นแดง --|                        |                        |
```

**เงื่อนไขการยกเลิก:**
- ต้องเป็นเจ้าของ enrollment
- Status = 'pending' หรือ 'confirmed'
- Session ยังไม่เริ่ม (start_date > วันนี้)

---

## 3. Flow Admin อนุมัติ Enrollment

```
Admin                     Frontend                Backend                 Database
  |                          |                        |                        |
  |-- เปิด Enrollments ----->|                        |                        |
  |                          |-- GET /api/admin/enrollments                  |
  |                          |    ?status=pending     |                        |
  |                          |<-- pending list -------|                        |
  |<-- แสดงรายการรออนุมัติ --|                        |                        |
  |                          |                        |                        |
  |-- กด Approve ----------->|                        |                        |
  |                          |-- PUT /api/admin/enrollments/{id}/approve     |
  |                          |                        |-- update status ------>|
  |                          |                        |   = confirmed          |
  |                          |                        |-- ส่ง email (optional)->|
  |                          |                        |<-- success ------------|
  |                          |<-- 200 OK -------------|                        |
  |<-- แสดงข้อความสำเร็จ ----|                        |                        |
```

**หมายเหตุ:**
- เมื่อ Admin approve → status เปลี่ยนจาก 'pending' → 'confirmed'
- นักเรียนจะได้รับอีเมลแจ้งเตือน (ถ้ามี)
- หลังจาก confirmed แล้ว ปุ่ม Cancel จะหายไป (ตาม business logic)

---

## 4. Flow การ Auto-Complete (Background Jobs)

```
Scheduler                  Command                  Backend                 Database
  |                          |                        |                        |
  |-- ทุกวันเที่ยงคืน ------>|                        |                        |
  |                          |-- sessions:close-expired|                       |
  |                          |                        |-- หา sessions -------->|
  |                          |                        |   end_date < today     |
  |                          |                        |   status = open        |
  |                          |                        |<-- sessions list ------|
  |                          |                        |-- update status ------>|
  |                          |                        |   = closed             |
  |                          |<-- จำนวนที่ปิด ---------|                        |
  |                          |                        |                        |
  |-- ทุกวัน 01:00 ---------->|                        |                        |
  |                          |-- enrollments:evaluate-completions             |
  |                          |                        |-- TODO: logic -------->|
  |                          |                        |                        |
  |-- ทุกวัน 02:00 ---------->|                        |                        |
  |                          |-- sessions:auto-complete|                       |
  |                          |    --days=7            |                        |
  |                          |                        |-- หา sessions -------->|
  |                          |                        |   end_date < today-7d  |
  |                          |                        |   status != completed  |
  |                          |                        |<-- sessions list ------|
  |                          |                        |-- update status ------>|
  |                          |                        |   = completed          |
  |                          |<-- จำนวนที่ complete ---|                        |
```

**คำอธิบาย:**
1. **00:00** - ปิด sessions ที่หมดเวลา (open → closed)
2. **01:00** - ประเมิน enrollments ที่ควร completed (ยังไม่มี logic)
3. **02:00** - Auto-complete sessions หลังจบ 7 วัน

---

## สัญลักษณ์ที่ใช้

- `|` = Timeline
- `-->` = Request
- `<--` = Response
- `--` = Action/Process

---

**อัปเดตล่าสุด:** 2025-01-15
**เวอร์ชัน:** 1.0
