# QA / Testing Guide - Training Management System

> คู่มือการทดสอบระบบอย่างครบถ้วน

**Version:** 1.0
**Last Updated:** 12 ธันวาคม 2025

---

## 📋 Table of Contents

- [ภาพรวมการทดสอบ](#ภาพรวมการทดสอบ)
- [Automated Testing](#automated-testing)
- [Manual Testing Checklist](#manual-testing-checklist)
- [Performance Testing](#performance-testing)
- [Test Cases รายละเอียด](#test-cases-รายละเอียด)
- [Test Results Template](#test-results-template)

---

## 🎯 ภาพรวมการทดสอบ

### สิ่งที่ต้องทดสอบ

1. ✅ **CRUD Program** - สร้าง อ่าน แก้ไข ลบ
2. ✅ **CRUD Session** - สร้าง อ่าน แก้ไข ลบ
3. ✅ **Validation** - ตรวจสอบข้อมูล input
4. ✅ **Error Cases** - กรณีที่ผิดปกติ
5. ✅ **Performance** - ความเร็วในการโหลด
6. ✅ **Relationships** - Program ↔ Session

### แบ่งเป็น 2 ประเภท

**Automated Testing (รันโดยโค้ด)**
- ✅ มีอยู่แล้ว 61 tests
- ✅ รันด้วย `php artisan test`
- ✅ รวดเร็ว (1.68 วินาที)

**Manual Testing (ทดสอบด้วยมือ)**
- ✅ ใช้ Postman / Browser
- ✅ ตรวจสอบ UI/UX
- ✅ ทดสอบ edge cases

---

## 🤖 Automated Testing

### ขั้นตอนที่ 1: รัน Tests ทั้งหมด

```bash
# รัน tests ทั้งหมด
php artisan test

# ผลลัพธ์ที่คาดหวัง:
# ✓ 61 tests passed (231 assertions)
```

### ขั้นตอนที่ 2: รันแยกตาม Module

```bash
# ทดสอบเฉพาะ Program API
php artisan test --filter=ProgramApiTest

# ผลลัพธ์: ✓ 16 tests passed

# ทดสอบเฉพาะ Session API
php artisan test --filter=TrainingSessionApiTest

# ผลลัพธ์: ✓ 20 tests passed
```

### ขั้นตอนที่ 3: ดูรายละเอียด

```bash
# แสดงรายละเอียดแต่ละ test
php artisan test --testdox

# จะแสดง:
# ✓ can get all programs
# ✓ can create program
# ✓ validation fails when required fields missing
# ...
```

### ✅ Automated Tests ครอบคลุม

#### Program API (16 tests)
- ✅ GET all programs
- ✅ GET single program
- ✅ GET empty list
- ✅ POST create program
- ✅ POST validation (missing fields)
- ✅ POST validation (duplicate code)
- ✅ POST validation (invalid duration)
- ✅ POST validation (invalid status)
- ✅ PUT update program
- ✅ PUT partial update
- ✅ PUT validation (duplicate code)
- ✅ DELETE program
- ✅ 404 when not found
- ✅ 401 when unauthorized
- ✅ Response format consistency

#### Session API (20 tests)
- ✅ GET all sessions
- ✅ GET with filter (program_id)
- ✅ GET single session
- ✅ POST create session
- ✅ POST validation (missing fields)
- ✅ POST validation (date order)
- ✅ POST validation (time order)
- ✅ POST validation (capacity)
- ✅ POST validation (foreign keys)
- ✅ POST validation (invalid status)
- ✅ PUT update session
- ✅ PUT partial update
- ✅ PUT validation (dates)
- ✅ DELETE session
- ✅ All CRUD operations

---

## ✋ Manual Testing Checklist

### 📚 Task 1: ทดสอบ CRUD Program

#### 1.1 Create Program (สร้าง)

**Test Case:** สร้างหลักสูตรใหม่

**Steps:**
1. เปิด Postman
2. GET token จาก `/api/auth/register` หรือ login
3. POST `/api/programs` พร้อม body:
```json
{
  "name": "QA Test Program",
  "code": "QA-TEST-001",
  "category": "Testing",
  "duration_hours": 20,
  "status": "active"
}
```

**Expected Result:**
```json
{
  "success": true,
  "message": "Program created successfully",
  "data": {
    "id": 1,
    "name": "QA Test Program",
    "code": "QA-TEST-001",
    ...
  }
}
```

**Status:** ⬜ Pass / ⬜ Fail

**Notes:** _______________________________

---

#### 1.2 Read Program (อ่าน)

**Test Case 1.2.1:** ดูหลักสูตรทั้งหมด

**Steps:**
1. GET `/api/programs`

**Expected Result:**
- Status: 200
- Response มี array ของ programs
- มี program ที่สร้างไว้ก่อนหน้า

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 1.2.2:** ดูหลักสูตรเดียว

**Steps:**
1. GET `/api/programs/1`

**Expected Result:**
- Status: 200
- Response มีข้อมูลหลักสูตร ID 1

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 1.2.3:** ดูหลักสูตรที่ไม่มี (404)

**Steps:**
1. GET `/api/programs/99999`

**Expected Result:**
- Status: 404
- Message: "No query results..."

**Status:** ⬜ Pass / ⬜ Fail

---

#### 1.3 Update Program (แก้ไข)

**Test Case:** แก้ไขหลักสูตร

**Steps:**
1. PUT `/api/programs/1`
```json
{
  "name": "QA Test Program (Updated)",
  "duration_hours": 30
}
```

**Expected Result:**
- Status: 200
- data.name = "QA Test Program (Updated)"
- data.duration_hours = 30
- data.code ไม่เปลี่ยน (ยังเป็น QA-TEST-001)

**Status:** ⬜ Pass / ⬜ Fail

---

#### 1.4 Delete Program (ลบ)

**Test Case:** ลบหลักสูตร

**Steps:**
1. DELETE `/api/programs/1`
2. GET `/api/programs/1` (ตรวจสอบว่าลบแล้ว)

**Expected Result:**
- DELETE response: 200, success = true
- GET response: 404 (หาไม่เจอ)

**Status:** ⬜ Pass / ⬜ Fail

---

### 🎓 Task 2: ทดสอบ CRUD Session

#### 2.1 Create Session (สร้าง)

**Prerequisites:** ต้องมี program_id และ trainer_id ที่ valid

**Steps:**
1. สร้าง Program ก่อน (หรือใช้ที่มีอยู่)
2. POST `/api/sessions`
```json
{
  "program_id": 1,
  "title": "QA Test Session - Batch 1",
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "start_time": "09:00",
  "end_time": "17:00",
  "capacity": 30,
  "trainer_id": 1,
  "location": "Test Room",
  "status": "open"
}
```

**Expected Result:**
- Status: 201
- success = true
- data มีข้อมูล session ที่สร้าง

**Status:** ⬜ Pass / ⬜ Fail

---

#### 2.2 Read Sessions (อ่าน)

**Test Case 2.2.1:** ดู sessions ทั้งหมด

**Steps:**
1. GET `/api/sessions`

**Expected Result:**
- Status: 200
- มี array ของ sessions

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 2.2.2:** Filter by program_id

**Steps:**
1. GET `/api/sessions?program_id=1`

**Expected Result:**
- Status: 200
- ทุก session ที่ return มี program_id = 1

**Status:** ⬜ Pass / ⬜ Fail

---

#### 2.3 Update Session (แก้ไข)

**Steps:**
1. PUT `/api/sessions/1`
```json
{
  "capacity": 40,
  "status": "closed"
}
```

**Expected Result:**
- Status: 200
- data.capacity = 40
- data.status = "closed"

**Status:** ⬜ Pass / ⬜ Fail

---

#### 2.4 Delete Session (ลบ)

**Steps:**
1. DELETE `/api/sessions/1`

**Expected Result:**
- Status: 200
- success = true
- data = null

**Status:** ⬜ Pass / ⬜ Fail

---

### ✅ Task 3: ทดสอบ Validation

#### 3.1 Program Validation

**Test Case 3.1.1:** Missing required fields

**Steps:**
```bash
POST /api/programs
Body: {}  # ว่างเปล่า
```

**Expected Result:**
- Status: 422
- errors มี: name, code, category, duration_hours, status

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.1.2:** Duplicate code

**Steps:**
1. สร้าง program code "TEST-001"
2. สร้าง program code "TEST-001" อีกครั้ง

**Expected Result:**
- Status: 422
- errors.code = "The code has already been taken."

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.1.3:** Invalid duration (0)

**Steps:**
```json
{
  "name": "Test",
  "code": "TEST-002",
  "category": "Test",
  "duration_hours": 0,  // ผิด!
  "status": "active"
}
```

**Expected Result:**
- Status: 422
- errors.duration_hours = "must be at least 1"

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.1.4:** Invalid status

**Steps:**
```json
{
  "name": "Test",
  "code": "TEST-003",
  "category": "Test",
  "duration_hours": 10,
  "status": "invalid-status"  // ผิด!
}
```

**Expected Result:**
- Status: 422
- errors.status = "invalid"

**Status:** ⬜ Pass / ⬜ Fail

---

#### 3.2 Session Validation

**Test Case 3.2.1:** Start date after end date

**Steps:**
```json
{
  "program_id": 1,
  "title": "Test",
  "start_date": "2025-02-28",  // หลัง
  "end_date": "2025-02-01",    // ก่อน (ผิด!)
  "capacity": 20,
  "trainer_id": 1
}
```

**Expected Result:**
- Status: 422
- errors.start_date = "must be before end date"

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.2.2:** End time before start time

**Steps:**
```json
{
  "program_id": 1,
  "title": "Test",
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "start_time": "17:00",  // หลัง
  "end_time": "09:00",    // ก่อน (ผิด!)
  "capacity": 20,
  "trainer_id": 1
}
```

**Expected Result:**
- Status: 422
- errors.end_time = "must be after start time"

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.2.3:** Invalid capacity (0)

**Steps:**
```json
{
  "program_id": 1,
  "title": "Test",
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "capacity": 0,  // ผิด!
  "trainer_id": 1
}
```

**Expected Result:**
- Status: 422
- errors.capacity = "must be at least 1"

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 3.2.4:** Invalid program_id (foreign key)

**Steps:**
```json
{
  "program_id": 99999,  // ไม่มีในระบบ
  "title": "Test",
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "capacity": 20,
  "trainer_id": 1
}
```

**Expected Result:**
- Status: 422
- errors.program_id = "invalid"

**Status:** ⬜ Pass / ⬜ Fail

---

### ❌ Task 4: ทดสอบ Error Cases

#### 4.1 Authentication Errors

**Test Case 4.1.1:** No token (401)

**Steps:**
1. GET `/api/programs` โดยไม่ส่ง Authorization header

**Expected Result:**
- Status: 401
- message = "Unauthenticated."

**Status:** ⬜ Pass / ⬜ Fail

---

**Test Case 4.1.2:** Invalid token

**Steps:**
1. GET `/api/programs` with Authorization: Bearer invalid-token-123

**Expected Result:**
- Status: 401

**Status:** ⬜ Pass / ⬜ Fail

---

#### 4.2 Not Found Errors (404)

**Test Case:** Resource not found

**Steps:**
1. GET `/api/programs/99999`
2. GET `/api/sessions/99999`

**Expected Result:**
- Status: 404

**Status:** ⬜ Pass / ⬜ Fail

---

### ⚡ Task 5: ทดสอบ Performance

#### 5.1 List Programs Performance

**Test Case:** โหลดหน้า list programs

**Steps:**
1. สร้าง programs 100 รายการ (ใช้ loop หรือ seeder)
2. GET `/api/programs`
3. วัดเวลา response time

**Expected Result:**
- Response time < 500ms
- ได้ข้อมูลครบ 100 รายการ

**Actual Result:**
- Response time: _______ ms

**Status:** ⬜ Pass / ⬜ Fail

---

#### 5.2 List Sessions Performance

**Test Case:** โหลดหน้า list sessions

**Steps:**
1. สร้าง sessions 100 รายการ
2. GET `/api/sessions`
3. วัดเวลา response time

**Expected Result:**
- Response time < 500ms

**Actual Result:**
- Response time: _______ ms

**Status:** ⬜ Pass / ⬜ Fail

---

#### 5.3 Filter Performance

**Test Case:** Filter sessions by program

**Steps:**
1. GET `/api/sessions?program_id=1`
2. วัดเวลา

**Expected Result:**
- Response time < 300ms
- ได้เฉพาะ sessions ของ program นั้น

**Actual Result:**
- Response time: _______ ms

**Status:** ⬜ Pass / ⬜ Fail

---

### 🔗 Task 6: ทดสอบ Relationships

#### 6.1 Program → Sessions

**Test Case:** Program มี sessions หลายตัว

**Steps:**
1. สร้าง Program ID = 1
2. สร้าง Session 3 ตัวที่ program_id = 1
3. GET `/api/sessions?program_id=1`

**Expected Result:**
- ได้ sessions ทั้ง 3 ตัว
- ทุกตัวมี program_id = 1

**Status:** ⬜ Pass / ⬜ Fail

---

#### 6.2 Session → Program

**Test Case:** Session ต้องมี program

**Steps:**
1. สร้าง Session โดยใส่ program_id ที่ไม่มี (99999)

**Expected Result:**
- Status: 422
- errors.program_id = "invalid"

**Status:** ⬜ Pass / ⬜ Fail

---

#### 6.3 Delete Program with Sessions

**Test Case:** ลบ program ที่มี sessions

**Steps:**
1. สร้าง Program ID = 1
2. สร้าง Session program_id = 1
3. DELETE `/api/programs/1`

**Expected Result:**
- อาจจะ error (ขึ้นอยู่กับ database constraints)
- หรือลบได้แต่ sessions หาย (cascade delete)

**Actual Result:**
- _______________________________

**Status:** ⬜ Pass / ⬜ Fail

---

## 📊 Performance Testing Guide

### วิธีวัด Response Time

#### ใช้ Postman
1. เปิด Postman
2. ส่ง request
3. ดูที่ "Time" ด้านล่างขวา (แสดงเป็น ms)

#### ใช้ cURL with time
```bash
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/api/programs

# สร้างไฟล์ curl-format.txt:
echo "time_total: %{time_total}s\n" > curl-format.txt
```

#### ใช้ Browser DevTools
1. เปิด Network tab
2. Refresh หน้า
3. ดู Time column

---

## 📝 Test Results Template

### สรุปผลการทดสอบ

**Date:** _______________________
**Tester:** _____________________
**Environment:** Development / Staging / Production

### Test Summary

| Category | Total | Pass | Fail | Pass Rate |
|----------|-------|------|------|-----------|
| Program CRUD | 10 | ___ | ___ | ___% |
| Session CRUD | 10 | ___ | ___ | ___% |
| Validation | 8 | ___ | ___ | ___% |
| Error Cases | 4 | ___ | ___ | ___% |
| Performance | 3 | ___ | ___ | ___% |
| Relationships | 3 | ___ | ___ | ___% |
| **TOTAL** | **38** | **___** | **___** | **___%** |

### Critical Issues Found

1. _______________________________________
2. _______________________________________
3. _______________________________________

### Minor Issues Found

1. _______________________________________
2. _______________________________________

### Performance Results

- Average response time: ______ ms
- Slowest endpoint: ____________
- Fastest endpoint: ____________

### Recommendations

1. _______________________________________
2. _______________________________________
3. _______________________________________

---

## 🔧 Troubleshooting

### ปัญหาที่พบบ่อย

**1. Tests fail - Database error**
```bash
# แก้: รัน migration ใหม่
php artisan migrate:fresh --seed
php artisan test
```

**2. 401 Unauthorized**
```bash
# แก้: สร้าง user และ token ใหม่
php artisan tinker
$user = User::factory()->create();
$token = $user->createToken('test')->plainTextToken;
echo $token;
```

**3. Performance ช้า**
- เช็ค database indexing
- เช็ค N+1 query problem
- ลอง optimize query

---

## ✅ Checklist สำหรับ QA Sign-off

ก่อน approve ให้ production:

- [ ] Automated tests ผ่านหมด (61/61)
- [ ] Manual tests ผ่าน >= 90%
- [ ] Performance tests ผ่าน (< 500ms)
- [ ] Validation ทำงานถูกต้อง
- [ ] Error handling เหมาะสม
- [ ] Relationships ถูกต้อง
- [ ] Documentation ครบถ้วน
- [ ] Security testing ผ่าน
- [ ] No critical bugs

---

**Created by:** QA Team
**Last Updated:** 12 ธันวาคม 2025
