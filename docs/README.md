# Documentation - Training Management System

ระบบจัดการอบรม (Training Management System)

---

## เอกสารที่มีในโฟลเดอร์นี้

### 1. API-SPECIFICATION.md
เอกสารแสดง API endpoints ทั้งหมด พร้อมตัวอย่าง request/response

**หัวข้อหลัก:**
- Authentication APIs (register, login)
- Admin - User Management APIs
- Profile Management APIs
- Avatar Management APIs
- Catalog APIs (Programs/Sessions)
- Enrollment APIs

### 2. SEQUENCE-DIAGRAMS.md
เอกสารแสดง flow การทำงานของระบบในรูปแบบ sequence diagram

**หัวข้อหลัก:**
- Flow หลัก: สมัคร → Login → Update Profile → Enroll Session
- Flow การยกเลิกการลงทะเบียน
- Flow Admin อนุมัติ Enrollment
- Flow Auto-Complete (Background Jobs)

---

## วิธีใช้งานเอกสาร

### สำหรับ Developer
1. อ่าน API-SPECIFICATION.md เพื่อเข้าใจ endpoints ทั้งหมด
2. ใช้ตัวอย่าง request/response ในการพัฒนา frontend/backend
3. ตรวจสอบ error codes และ status codes

### สำหรับ Tester/QA
1. ใช้ API spec เป็น test cases
2. ตรวจสอบ flow ต่างๆ ตาม sequence diagrams
3. ทดสอบ edge cases และ error handling

### สำหรับ Product Owner/Manager
1. อ่าน sequence diagrams เพื่อเข้าใจ user journey
2. ตรวจสอบว่า business logic ถูกต้องหรือไม่
3. ใช้เป็นข้อมูลในการวางแผน features ต่อไป

---

## Changelog

### Version 1.0.0 (2025-01-15)

**เพิ่มเติม:**
- สร้างเอกสาร API Specification ครบทุก endpoints
- สร้าง Sequence Diagrams สำหรับ main flows
- เพิ่มคำอธิบายสถานะต่างๆ (enrollment, session, program)
- เพิ่ม error codes และความหมาย

**API Changes:**
- Enrollment status เริ่มต้นเป็น "pending" (เปลี่ยนจาก "confirmed")
- เพิ่มการตรวจสอบ session.status = 'completed' ใน enrollment API
- เพิ่ม auto-close และ auto-complete sessions ผ่าน cron jobs

**Business Logic:**
- ปุ่ม Cancel แสดงเฉพาะ status = "pending" และ session ยังไม่เริ่ม
- Cancelled badge เป็นสีแดง (เปลี่ยนจากสีเทา)
- Student Settings navigation ไปที่ /me/profile

---

## TODO / Feedback

### จาก Phase 4
- [ ] เพิ่ม logic ใน `enrollments:evaluate-completions` command
  - ตรวจสอบ attendance percentage
  - ตรวจสอบ evaluation results
  - Mark enrollment = "completed" เมื่อผ่านเกณฑ์

### จาก Phase 6 (อนาคต)
- [ ] เพิ่ม API สำหรับ download certificate
- [ ] เงื่อนไข: enrollment.status = "completed"
- [ ] เพิ่ม certificate template

### Improvements
- [ ] เพิ่มการแจ้งเตือนผ่าน email เมื่อ enrollment เปลี่ยนสถานะ
- [ ] เพิ่ม webhook สำหรับ third-party integrations
- [ ] เพิ่ม API versioning (v1, v2)

---

## ข้อมูลเพิ่มเติม

### การ Setup Local
```bash
# Clone repository
git clone <repo-url>

# Install dependencies
composer install
npm install

# Copy .env
cp .env.example .env

# Generate key
php artisan key:generate

# Migrate database
php artisan migrate --seed

# Run server
php artisan serve
```

### การทดสอบ API
```bash
# ใช้ Postman หรือ curl
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password","password_confirmation":"password"}'
```

### การตั้งค่า Cron Jobs (Production)
```bash
# เพิ่มใน crontab
crontab -e

# เพิ่มบรรทัดนี้
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## ติดต่อ

หากมีคำถามหรือข้อเสนอแนะเกี่ยวกับเอกสาร กรุณาติดต่อ:
- Email: dev@example.com
- Slack: #training-system-dev

---

**อัปเดตล่าสุด:** 2025-01-15
**ผู้จัดทำ:** Development Team
