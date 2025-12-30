Commands ที่เราสร้าง

  1. php artisan sessions:close-expired

  ทำอะไร:
  - ปิด sessions ที่หมดเวลาแล้ว (past end_date)

  ตัวอย่าง:
  Session A: end_date = 1 ม.ค. 2025, status = "open"
  วันนี้: 5 ม.ค. 2025

  → รัน command → Session A status เปลี่ยนเป็น "closed" ✅

  ทำไมต้องมี:
  - Admin/Trainer อาจลืมปิด session
  - ป้องกันนักเรียน enroll session ที่จบไปแล้ว

  เมื่อไหร่ที่รัน:
  - Auto: ทุกวันเที่ยงคืน (00:00)
  - Manual: php artisan sessions:close-expired

  ---
  2. php artisan enrollments:evaluate-completions

  ทำอะไร:
  - ตรวจสอบและ mark enrollments เป็น "completed"
  - (ตอนนี้ยังเป็น skeleton - ยังไม่มี logic จริง)

  ตัวอย่าง (อนาคต):
  Enrollment A:
  - Session: completed ✅
  - Attendance: 90% ✅
  - Evaluation: passed ✅

  → รัน command → Enrollment A เปลี่ยนเป็น "completed"
  → นักเรียนสามารถดาวน์โหลด certificate ได้! 🎓

  ทำไมต้องมี:
  - Phase 6 ต้องใช้ enrollment = "completed" เป็นเงื่อนไขออก certificate
  - วางฐานไว้ก่อน จะเพิ่ม logic ทีหลัง

  เมื่อไหร่ที่รัน:
  - Auto: ทุกวันเวลา 01:00
  - Manual: php artisan enrollments:evaluate-completions

  ---
  3. php artisan sessions:auto-complete --days=7

  ทำอะไร:
  - Auto mark sessions เป็น "completed" หลังจบ 7 วัน
  - ป้องกันกรณี Admin/Trainer ลืม mark session

  ตัวอย่าง:
  Session B:
  - end_date = 1 ม.ค. 2025
  - วันนี้ = 10 ม.ค. 2025 (ผ่านไป 9 วัน)
  - status = "closed" (ยังไม่ completed)

  → รัน command → Session B เปลี่ยนเป็น "completed" ✅
  เพราะผ่านไป > 7 วันแล้ว

  ทำไมต้องมี:
  - บางที session จบแล้วแต่ Admin ลืม mark เป็น completed
  - หลัง 7 วัน ถือว่าจบแน่นอนแล้ว → ระบบ auto mark ให้

  ปรับค่าได้:
  # ใช้ 3 วันแทน
  php artisan sessions:auto-complete --days=3

  # ใช้ 14 วัน
  php artisan sessions:auto-complete --days=14

  เมื่อไหร่ที่รัน:
  - Auto: ทุกวันเวลา 02:00 (default 7 วัน)
  - Manual: php artisan sessions:auto-complete --days=7

  ---
  🔄 Flow ทั้งหมด

  Session สร้างมา → status = "pending"
      ↓
  Admin approve → status = "open"
      ↓
  Session เริ่ม... กำลังเรียน...
      ↓
  end_date ผ่านไป → [Command 1] auto ปิด → status = "closed"
      ↓
  ผ่านไป 7 วัน → [Command 3] auto mark → status = "completed"
      ↓
  [Command 2] ตรวจสอบ enrollment → mark enrollment = "completed"
      ↓
  นักเรียนดาวน์โหลด certificate ได้! 🎓

  ---
  🧪 วิธีใช้งาน

  ทดสอบ Manual (รันเอง)

  # ปิด sessions ที่หมดเวลา
  php artisan sessions:close-expired

  # ตรวจสอบ enrollments (ยังไม่ทำอะไร)
  php artisan enrollments:evaluate-completions

  # Auto-complete sessions หลัง 7 วัน
  php artisan sessions:auto-complete --days=7

  # ดู schedule ทั้งหมด
  php artisan schedule:list

  # รัน schedule ทุกอันเลย (ทดสอบ)
  php artisan schedule:run

  ---
  ทำงานอัตโนมัติ (Production)

  ตั้ง cron job ครั้งเดียว:
  crontab -e

  เพิ่มบรรทัดนี้:
  * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

  หมายความว่า:
  - ทุก 1 นาที Laravel จะเช็คว่ามี command ไหนถึงเวลารันหรือยัง
  - ถ้าถึงเวลา → รัน command นั้นอัตโนมัติ
  - เช่น ถึงเที่ยงคืน → รัน sessions:close-expired เอง

  ---
  📊 ตัวอย่างผลลัพธ์

  $ php artisan sessions:close-expired
  Checking for expired sessions...
  Successfully closed 3 expired session(s).

  $ php artisan sessions:auto-complete --days=7
  Checking for sessions to auto-complete (threshold: 7 days)...
  Successfully auto-completed 2 session(s).

  ---
  🎯 สรุป

  | Command                          | ทำอะไร                             | เมื่อไหร่     |
  |----------------------------------|-----------------------------------|------------|
  | sessions:close-expired           | ปิด sessions หลัง end_date          | ทุกวัน 00:00 |
  | enrollments:evaluate-completions | Mark enrollments completed (วางฐาน) | ทุกวัน 01:00 |
  | sessions:auto-complete           | Mark sessions completed หลัง 7 วัน  | ทุกวัน 02:00 |

  ประโยชน์:
  - ✅ ลดงาน manual ของ Admin
  - ✅ ป้องกันข้อมูลไม่ consistent
  - ✅ ระบบทำงานอัตโนมัติ 24/7

  เข้าใจแล้วไหมครับ? มีอะไรสงสัยเพิ่มเติมถามได้เลยครับ! 😊