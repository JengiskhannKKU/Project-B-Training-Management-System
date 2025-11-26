# 📘 Training Management System (TMS)

A full-stack web application for managing training programs, sessions, attendance tracking, feedback collection, and automated online certificate generation.  
Designed for scalability using **Laravel + Blade + Vue**, with deployment targets such as **Nginx, PHP-FPM, MySQL**, and **CI/CD pipelines**.

This README summarizes the functional backlog, system architecture, man-day planning, and installation guidelines.

---

## 🚀 Features Overview

### ✅ 1. Authentication & RBAC
- Login, registration, password reset  
- Role-based access (Admin, Trainer, Trainee)  
- Laravel Policies for authorization  
- Secure with CSRF, hashed passwords, and validation rules

---

### ✅ 2. Program & Session Management
Admins can:
- Create, edit, delete **Programs**  
- Create, edit, delete **Sessions**  
- Assign trainers, set schedule, manage capacity  
- Program/session lists with search & filtering

---

### ✅ 3. Registration & Attendance
Trainee:
- Register for sessions (capacity-checked)
- Cancel registration before the start date

Trainer/Admin:
- Mark attendance
- Completion calculation (e.g., ≥ 80% attendance)
- Session closing workflow

---

### ✅ 4. Feedback Module & Reporting
- Post-session feedback (rating + comments)
- Admin summary reports:
  - Average score
  - Common keywords
- Export CSV for analytics

---

### ✅ 5. Certificate System (PDF + QR + Email)
Automatic certificate generation when trainee meets completion criteria.

Includes:
- PDF generation using DOMPDF or Snappy  
- Unique certificate number + verification hash  
- QR Code verification page  
- Email queue that sends certificate PDF  
- Downloadable from user profile

---

### ✅ 6. Dashboard & Analytics
- Charts for attendance, completion, feedback score  
- Time-based filtering  
- CSV export  
- Admin summary view

---

## 🗄️ Database Schema (Summary)

### Main Tables:
users
programs
sessions
registrations
attendance
feedback
certificates
