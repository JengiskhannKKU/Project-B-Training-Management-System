# Admin Guide

Administrator's Guide for Training Management System

**Version:** 1.0.0
**Last Updated:** December 12, 2025
**For:** System Administrators

---

## Table of Contents

1. [Admin Responsibilities](#admin-responsibilities)
2. [Managing Programs](#managing-programs)
3. [Managing Training Sessions](#managing-training-sessions)
4. [Managing Users](#managing-users)
5. [Best Practices](#best-practices)
6. [Common Workflows](#common-workflows)
7. [Troubleshooting](#troubleshooting)

---

## Admin Responsibilities

### Key Responsibilities

1. **Program Management** - Create, update, and manage training programs
2. **Session Management** - Schedule and manage training sessions
3. **User Management** - Create users, assign roles, manage access
4. **System Oversight** - Monitor system usage and maintain data quality

### Access Levels

| Role | Permissions |
|------|-------------|
| **Admin** | Full access to all operations |
| **Trainer** | View programs/sessions, manage enrollments |
| **Student** | View programs/sessions, self-enroll |

---

## Managing Programs

### Creating a New Program

**Required Information:**
- Program Name (e.g., "Laravel Advanced Development")
- Unique Code (e.g., "LAR-ADV-001")
- Category (e.g., "Web Development")
- Duration (hours, minimum 1)
- Status (`active` or `inactive`)

**Optional Information:**
- Description
- Image URL

**API Request:**
```bash
curl -X POST http://localhost:8000/api/programs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laravel Advanced Development",
    "code": "LAR-ADV-001",
    "category": "Web Development",
    "duration_hours": 40,
    "description": "Advanced Laravel course covering APIs, testing, and deployment",
    "status": "active"
  }'
```

**Common Errors:**

| Error | Cause | Solution |
|-------|-------|----------|
| "Code already taken" | Duplicate program code | Use a unique code |
| "Duration must be at least 1" | Invalid duration | Set duration >= 1 |
| "Status must be active or inactive" | Invalid status | Use 'active' or 'inactive' |

### Updating a Program

Update specific fields without affecting others:

```bash
curl -X PUT http://localhost:8000/api/programs/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laravel Advanced (Updated)",
    "duration_hours": 50
  }'
```

**What You Can Update:**
- Name
- Description
- Category
- Duration
- Image URL
- Status
- Code (must remain unique)

### Deactivating vs Deleting

**Deactivate (Recommended):**
```bash
# Set status to inactive
curl -X PUT http://localhost:8000/api/programs/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "inactive"}'
```

**Benefits:**
- Data is preserved
- Can be reactivated later
- Historical records remain intact

**Delete (Permanent):**
```bash
curl -X DELETE http://localhost:8000/api/programs/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Warning:** Deletion is permanent and cannot be undone.

---

## Managing Training Sessions

### Creating a Training Session

**Required Information:**
- Program ID (must exist)
- Session Title (e.g., "Laravel Advanced - Batch 1")
- Start Date (YYYY-MM-DD format)
- End Date (must be after start date)
- Capacity (minimum 1)
- Trainer ID (must exist, user with trainer role)

**Optional Information:**
- Start Time (HH:MM format)
- End Time (must be after start time)
- Location
- Status (`upcoming`, `open`, `closed`, `completed`, `cancelled`)

**API Request:**
```bash
curl -X POST http://localhost:8000/api/sessions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "program_id": 1,
    "title": "Laravel Advanced - Batch 1",
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "start_time": "09:00",
    "end_time": "17:00",
    "capacity": 30,
    "trainer_id": 2,
    "location": "Room A101",
    "status": "open"
  }'
```

**Common Errors:**

| Error | Cause | Solution |
|-------|-------|----------|
| "Start date must be before end date" | Dates in wrong order | Ensure start_date < end_date |
| "Program not found" | Invalid program_id | Verify program exists |
| "Trainer not found" | Invalid trainer_id | Verify user exists and is trainer |
| "End time must be after start time" | Times in wrong order | Ensure start_time < end_time |

### Session Status Management

**Status Workflow:**

1. **upcoming** → Session created, not yet open for enrollment
2. **open** → Accepting enrollments
3. **closed** → No longer accepting enrollments (full or approaching start date)
4. **completed** → Session finished
5. **cancelled** → Session canceled

**Changing Status:**

```bash
# Open for enrollment
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "open"}'

# Close enrollment
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "closed"}'

# Mark as completed
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type": application/json" \
  -d '{"status": "completed"}'
```

### Modifying Sessions

**Common Modifications:**

**Increase Capacity:**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"capacity": 40}'
```

**Change Location:**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"location": "Room B202"}'
```

**Reschedule:**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2025-03-01",
    "end_date": "2025-03-31"
  }'
```

**Best Practice:** Notify enrolled students before making significant changes.

---

## Managing Users

### Creating Users

**Default Registration:**
- Regular users self-register via `/auth/register`
- Automatically assigned `student` role

**Admin Creating Users:**
- Can assign any role: `admin`, `trainer`, or `student`

```bash
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "password123",
    "role": "trainer"
  }'
```

**Requirements:**
- Unique email address
- Password minimum 8 characters
- Valid role: `admin`, `trainer`, or `student`

### Viewing Users

**List All Users:**
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Filter by Role:**
```bash
# View all trainers
curl -X GET "http://localhost:8000/api/admin/users?role=trainer" \
  -H "Authorization: Bearer YOUR_TOKEN"

# View all students
curl -X GET "http://localhost:8000/api/admin/users?role=student" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Filter by Status:**
```bash
# View active users
curl -X GET "http://localhost:8000/api/admin/users?status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"

# View inactive users
curl -X GET "http://localhost:8000/api/admin/users?status=inactive" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Pagination:**
```bash
# 20 users per page
curl -X GET "http://localhost:8000/api/admin/users?per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Updating Users

**Change Role:**
```bash
# Promote student to trainer
curl -X PUT http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "trainer"}'
```

**Change Status:**
```bash
# Deactivate user
curl -X PUT http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "inactive"}'

# Reactivate user
curl -X PUT http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "active"}'
```

**Update Multiple Fields:**
```bash
curl -X PUT http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith (Updated)",
    "role": "admin",
    "status": "active"
  }'
```

### Deactivating Users

```bash
curl -X DELETE http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Note:** DELETE sets status to `inactive` (soft delete). User data is preserved.

---

## Best Practices

### Program Management

**1. Use Systematic Naming:**
```
Good Examples:
- Code: LAR-ADV-001, LAR-FUND-001, VUE-FUND-001
- Name: Framework Name + Level + "Development/Course"

Bad Examples:
- Code: ABC, XYZ, 001
- Name: Course 1, Training A
```

**2. Keep Codes Unique:**
- Use a prefix for each category (LAR-, VUE-, REACT-)
- Include level indicator (FUND, INT, ADV)
- Add sequential number (-001, -002)

**3. Set Appropriate Status:**
- Use `inactive` for programs under development
- Use `active` only for programs ready to offer
- Deactivate instead of delete to preserve history

### Session Management

**1. Verify Dates:**
```
Correct:
- start_date: 2025-02-01
- end_date: 2025-02-28

Incorrect (will fail):
- start_date: 2025-02-28
- end_date: 2025-02-01
```

**2. Set Appropriate Capacity:**
```
Room Types:
- Small classroom: 15-25 students
- Standard classroom: 25-35 students
- Lecture hall: 50-100 students
- Online: 50-100+ students
```

**3. Use Status Correctly:**
- **upcoming**: Created but not yet announced
- **open**: Ready for enrollment
- **closed**: Full or enrollment period ended
- **completed**: Session finished
- **cancelled**: Will not occur

**4. Communicate Changes:**
- Notify enrolled students before rescheduling
- Update location with sufficient notice
- Announce capacity increases

### User Management

**1. Role Assignment:**
```
Choose Carefully:
- admin: Full system access (limited to necessary personnel)
- trainer: Can manage sessions and enrollments
- student: Can view and enroll
```

**2. Avoid Unnecessary Role Changes:**
- Don't change trainer to student if they have active sessions
- Don't change admin to student (creates access issues)
- Create new accounts instead when in doubt

**3. Deactivate Instead of Delete:**
- Preserves enrollment history
- Allows reactivation if needed
- Maintains data integrity

---

## Common Workflows

### Launching a New Training Program

**Step 1: Create Program**
```bash
curl -X POST http://localhost:8000/api/programs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "React Fundamentals",
    "code": "REACT-FUND-001",
    "category": "Frontend Development",
    "duration_hours": 30,
    "description": "Learn React from scratch",
    "status": "active"
  }'
```

**Step 2: Create First Session**
```bash
curl -X POST http://localhost:8000/api/sessions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "program_id": 1,
    "title": "React Fundamentals - Batch 1",
    "start_date": "2025-03-01",
    "end_date": "2025-03-30",
    "start_time": "09:00",
    "end_time": "16:00",
    "capacity": 30,
    "trainer_id": 2,
    "location": "Room A101",
    "status": "upcoming"
  }'
```

**Step 3: Open for Enrollment**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "open"}'
```

**Step 4: Monitor Enrollment**
- Check enrollment count regularly
- Close when at capacity or near start date

**Step 5: Close Enrollment**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "closed"}'
```

**Step 6: Mark as Completed**
```bash
curl -X PUT http://localhost:8000/api/sessions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "completed"}'
```

### Adding a New Trainer

**Step 1: Create Trainer Account**
```bash
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Trainer",
    "email": "john.trainer@example.com",
    "password": "tempPassword123",
    "role": "trainer"
  }'
```

**Step 2: Inform Trainer**
- Send login credentials
- Provide system access instructions
- Share training materials

**Step 3: Assign to Sessions**
- Use trainer's ID when creating sessions
- Ensure they have appropriate access

---

## Troubleshooting

### Problem: "Code already taken"

**Cause:** Program code is not unique

**Solution:**
1. Check existing programs:
```bash
curl -X GET http://localhost:8000/api/programs \
  -H "Authorization: Bearer YOUR_TOKEN"
```
2. Use a different, unique code

### Problem: "Start date must be before end date"

**Cause:** Dates are in wrong order

**Solution:**
```bash
# Correct
{
  "start_date": "2025-02-01",
  "end_date": "2025-02-28"
}

# Incorrect
{
  "start_date": "2025-02-28",
  "end_date": "2025-02-01"
}
```

### Problem: Session not visible to students

**Cause:** Program or session status not set correctly

**Checklist:**
1. Program status must be `active`
2. Session status must be `open`
3. Dates must be valid (start in future or present)

### Problem: Cannot delete program

**Cause:** Program may have associated sessions

**Solution:**
1. Delete or cancel associated sessions first
2. Or set program status to `inactive` instead

### Problem: User cannot login

**Possible Causes:**
1. User status is `inactive`
2. Incorrect credentials
3. Token expired

**Solutions:**
1. Check user status:
```bash
curl -X GET "http://localhost:8000/api/admin/users?email=user@example.com" \
  -H "Authorization: Bearer YOUR_TOKEN"
```
2. Reactivate if needed:
```bash
curl -X PUT http://localhost:8000/api/admin/users/5 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "active"}'
```
3. User should request new token by logging in again

---

## Quick Reference

### Program Status

| Status | When to Use |
|--------|-------------|
| `active` | Program ready to offer |
| `inactive` | Program not currently offered |

### Session Status

| Status | When to Use |
|--------|-------------|
| `upcoming` | Created, not yet open for enrollment |
| `open` | Accepting enrollments |
| `closed` | Enrollment closed (full or starting soon) |
| `completed` | Session finished |
| `cancelled` | Session canceled |

### User Roles

| Role | Permissions |
|------|-------------|
| `admin` | Full system access |
| `trainer` | Manage sessions and enrollments |
| `student` | View programs and enroll |

---

## Support

**For technical issues:**
- Check API Specification: `API_SPECIFICATION.md`
- Review test results: `../TESTING_SUMMARY.md`
- Contact development team

**For system questions:**
- Consult this guide
- Review API documentation
- Check error messages for specific guidance

---

**Last Updated:** December 12, 2025
**Version:** 1.0.0
