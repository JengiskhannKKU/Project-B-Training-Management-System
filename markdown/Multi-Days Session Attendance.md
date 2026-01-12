# ระบบบันทึกการเข้าอบรม (Attendance) แบบ Multi-Days Session

พัฒนาระบบบันทึกการเข้าอบรม (Attendance) ที่รองรับ Session หลายวัน  
สามารถเช็กชื่อรายวัน คำนวณเปอร์เซ็นต์การเข้าอบรม และใช้ผลเพื่อออก Certificate อัตโนมัติ

---

## ✅ Definition of Done (Epic Level)

- เช็กชื่อรายวันได้  
- รองรับ Session หลายวัน  
- คำนวณ % การเข้าอบรมถูกต้อง (≥ 80%)  
- Certificate ออกอัตโนมัติ  
- Policy + Test ครบถ้วน  

---

## Implement Plan: Multi-Days Session Attendance

## 1. Goal & Scope

### 🎯 เป้าหมาย
- รองรับ Session ที่มีหลายวัน  
- เช็ก Attendance รายวัน (per day)  
- คำนวณ % การเข้าอบรมอัตโนมัติ  
- ใช้ผล Attendance สำหรับการออก Certificate  

### 📦 Scope
- Admin / Trainer บันทึก Attendance  
- Trainee ดูผล Attendance ของตัวเอง (read-only)  
- ❌ ไม่รวม auto attendance จาก online log (future scope)

---

## 2. Data Model Implementation

### 2.1 New Table: `session_days`

**Purpose:** เก็บข้อมูลวันอบรมของแต่ละ session

| Field       | Type        | Description              |
|------------|-------------|--------------------------|
| id         | bigint      | PK                       |
| session_id | bigint (FK) | อ้างอิง sessions         |
| date       | date        | วันที่อบรม               |
| start_time | time        | เวลาเริ่ม                |
| end_time   | time        | เวลาจบ                   |
| order_no   | int         | ลำดับวัน (1,2,3)         |
| created_at | timestamp   | -                        |

---

### 2.2 Update Table: `attendance`

**Change:** เช็กชื่อแบบ “ต่อวัน”

| Field          | Type        | Description                     |
|----------------|-------------|---------------------------------|
| id             | bigint      | PK                              |
| session_day_id | bigint (FK) | อ้างอิง session_days            |
| user_id        | bigint (FK) | ผู้เข้าอบรม                     |
| status         | enum        | present / absent / late         |
| checked_at     | timestamp   | เวลาเช็กชื่อ                    |

**Constraint**
- `unique(session_day_id, user_id)`

---

## 3. Backend Implementation Plan (Laravel)

### 3.1 Session Creation Logic

**When:** Admin สร้าง Session

**Steps**
1. รับ input: `start_date`, `end_date`  
2. คำนวณจำนวนวันทั้งหมด  
3. Loop สร้าง `session_days`  
4. Save พร้อม `order_no`

**Result**
- Session พร้อมใช้งานกับ multi-days

---

### 3.2 Attendance APIs

#### 3.2.1 Get Attendance Page Data

GET /sessions/{session_id}/attendance

yaml
Copy code

**Return**
- session info  
- session_days  
- registered trainees  
- attendance ของแต่ละวัน  

---

## 3.2.2 Save Attendance (Per Day)

**Endpoint**
### Payload
```json
{
  "session_day_id": 12,
  "records": [
    { "user_id": 1, "status": "present" },
    { "user_id": 2, "status": "absent" }
  ]
}
```
### Logic
- Validate role (Admin / Trainer)
- Upsert attendance (create or update by `session_day_id + user_id`)
- บันทึก timestamp `checked_at`

---

## 3.3 Attendance Calculation Logic

### Trigger
- เมื่อ Admin ปิด Session (`status = completed`)

### Steps
1. ดึงจำนวน `session_days` ทั้งหมดของ session
2. นับจำนวนวันที่ผู้เรียนมีสถานะ `present`
3. คำนวณเปอร์เซ็นต์การเข้าอบรม  
attendance_percent = (present_days / total_days) * 100

yaml
Copy code
4. Update `registrations.status`
- ผ่าน (pass) : attendance ≥ 80%
- ไม่ผ่าน (fail) : attendance < 80%

---

## 3.4 Certificate Issuing

### Condition
- `attendance_percent ≥ 80%`

### Process
1. Dispatch `IssueCertificateJob`
2. Generate Certificate (PDF)
3. บันทึกข้อมูลลงตาราง `certificates`

---

## 4. Frontend Implementation Plan

## 4.1 Attendance Page UI

### Route
/sessions/{id}/attendance

yaml
Copy code

---

## 4.2 Layout Structure
- Session Info Header
- Day Selector (Tabs / Dropdown)
- Attendance Table
- Save Button

---

## 4.3 Day Selector Logic
- Disable วันอบรมในอนาคต
- Highlight วันปัจจุบัน
- แสดงสถานะของแต่ละวัน (checked / not checked)

---

## 4.4 Attendance Table

### Columns
- Name
- Email
- Present
- Late

### Features
- Bulk Present (เช็กชื่อทุกคนเป็น present)
- Reset Day (ล้างข้อมูล attendance ของวันนั้น)
- Unsaved changes warning

---

## 5. Role & Permission Control

| Action                | Admin | Trainer | Trainee |
|----------------------|:-----:|:-------:|:-------:|
| View attendance page | ✅    | ✅      | ❌      |
| Edit attendance      | ✅    | ✅      | ❌      |
| View own attendance  | ❌    | ❌      | ✅      |

---

## 6. Edge Case Handling

| Case                   | Handling                                  |
|------------------------|-------------------------------------------|
| เช็กชื่อซ้ำ            | ใช้ Upsert (update แทน insert)            |
| วันอบรมถูกยกเลิก       | mark `session_day.status = cancelled`     |
| Trainer เช็กย้อนหลัง   | จำกัดช่วงเวลา ตาม system config           |
| ผู้เรียนยกเลิกกลางคัน | exclude ออกจากการคำนวณ attendance         |

---

## 7. Testing Plan

## 7.1 Unit Test
- Session day generation
- Attendance % calculation
- Pass / Fail logic

## 7.2 Feature Test
- Trainer สามารถเช็กชื่อได้
- Unauthorized user ถูก block
- Certificate ถูกออกอัตโนมัติเมื่อผ่านเกณฑ์

---

## 8. Definition of Done (DoD)

- ✅ รองรับ multi-days session
- ✅ เช็กชื่อรายวันได้
- ✅ คำนวณ % การเข้าอบรมถูกต้อง
- ✅ Certificate ออกอัตโนมัติ
- ✅ Policy + Validation ครบถ้วน
- ✅ UI ใช้งานจริงได้