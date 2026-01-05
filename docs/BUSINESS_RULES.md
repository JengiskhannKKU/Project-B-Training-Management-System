# Business Rules - Training Management System

เอกสารนี้ระบุกฎเกณฑ์ทางธุรกิจ (Business Rules) ทั้งหมดของระบบ Training Management System

---

## Table of Contents

1. [Enrollment Rules](#1-enrollment-rules)
2. [Attendance Rules](#2-attendance-rules)
3. [Session Completion Rules](#3-session-completion-rules)
4. [Enrollment Completion Rules](#4-enrollment-completion-rules)
5. [Certificate Generation Rules](#5-certificate-generation-rules)
6. [Authorization & Permissions](#6-authorization--permissions)
7. [Data Integrity Rules](#7-data-integrity-rules)

---

## 1. Enrollment Rules

### 1.1 Student Self-Enrollment

**Prerequisites:**
```
✓ User must be authenticated
✓ User role must be 'student'
✓ Session must exist
```

**Validation Rules:**
```
✓ session.approval_status = 'approved'
✓ session.status = 'open' (only open sessions)
✗ session.status = 'completed' → Error 422
✓ active_enrollments_count < session.capacity
✓ No existing enrollment with status != 'cancelled'
```

**Initial Status:** `pending`

**Code Reference:** `EnrollmentController.php:31-41`

---

### 1.2 Admin/Trainer Approve Trainee

**Prerequisites:**
```
✓ User must be admin
✓ AdminRequest must exist with target_type = 'trainee'
✓ Session must exist
```

**Validation Rules:**
```
✓ session exists
✓ active_enrollments_count < session.capacity
✓ email and full_name provided
✗ NO check for session.status (can enroll even if completed)
```

**Initial Status:** `confirmed`

**Auto-Create User:**
- If email doesn't exist → Create new user with role = 'student'
- Auto-generate random password
- User can reset password later

**Code Reference:** `AdminRequestActionController.php:284-286`

---

### 1.3 Enrollment Status Lifecycle

```
Student:     [pending] ──approve──> [confirmed] ──session complete──> [completed]
                 │                                                           │
                 └───────────────── cancel ────────────────> [cancelled]    │
                                                                             │
Admin:       [confirmed] ──────────session complete──────────> [completed]  │
                 │                                                           │
                 └───────────────── cancel ────────────────> [cancelled] <───┘
```

**Business Rules:**
1. **Cannot Cancel After Start:**
   - Can only cancel if `session.start_date > today`
   - Error 422 if trying to cancel on/after start date

2. **Cancelled Enrollments:**
   - Status remains `cancelled` forever
   - Not evaluated during session completion
   - Cannot re-enroll (must create new enrollment)

3. **Reactivation:**
   - If previously cancelled, can enroll again (creates new record or updates to `pending`)

---

## 2. Attendance Rules

### 2.1 Attendance Status Definitions

| Status | Counted as Attended? | Business Meaning | Impact on Completion |
|--------|---------------------|------------------|---------------------|
| `present` | ✅ YES | มาเรียนตรงเวลา | Counts toward 80% |
| `late` | ✅ YES | มาสาย | Counts toward 80% |
| `absent` | ❌ NO | ขาดเรียน | Does NOT count |

**Code Reference:** `CompletionService.php:11`
```php
private const ATTENDED_STATUSES = ['present', 'late'];
```

**Business Rationale:**
- `late` counts because student still attended and received training
- Only `absent` is penalized
- No time limit defined for "late" (up to trainer discretion)

---

### 2.2 Attendance Recording Rules

**Who Can Record:**
- Admin (any session)
- Trainer (own sessions only)

**Prerequisites:**
```
✓ enrollment.status IN ['confirmed', 'completed']
✓ enrollment.status != 'pending' (must be approved first)
✓ enrollment.status != 'cancelled' (cannot mark attendance)
✓ attendance_date BETWEEN session.start_date AND session.end_date
```

**Constraints:**
```
✓ One attendance record per enrollment per date
✓ If record exists, UPDATE instead of CREATE
✓ Can change status anytime (present → late → absent)
```

**Code Reference:** `AttendanceController.php:58-60`

---

### 2.3 Bulk Attendance Recording

**Purpose:** Record attendance for multiple students in one day

**Business Rules:**
1. **Date Consistency:**
   - All records in bulk request use same `attendance_date`
   - Must be within session date range

2. **Idempotent Operation:**
   - If attendance exists → UPDATE
   - If attendance doesn't exist → CREATE
   - No errors for duplicates

3. **Partial Success:**
   - Process all records even if some fail
   - Return summary: `{created: X, updated: Y, total: Z}`

**Use Case:**
Trainer marks attendance at end of each training day for entire class

---

## 3. Session Completion Rules

### 3.1 Who Can Mark Session Completed?

**Authorization Matrix:**

| User Role | Condition | Can Complete? |
|-----------|-----------|---------------|
| Admin | Any session | ✅ YES |
| Trainer | Own sessions (created_by = user.id) | ✅ YES |
| Trainer | Other's sessions | ❌ NO (403) |
| Student | Any session | ❌ NO (403) |

**Code Reference:** `TrainingSessionController.php:136-143`

---

### 3.2 Session Status Requirements

**Before Completion:**
```
✓ session.status IN ['open', 'closed']
✗ session.status = 'upcoming' → Error 422 (not started yet)
✗ session.status = 'completed' → Error 422 (already completed)
✗ session.status = 'cancelled' → Error 422 (cannot complete cancelled)
```

**After Completion:**
```
→ session.status = 'completed'
→ Trigger auto-evaluation of ALL enrollments
→ Return summary {total: X, completed: Y}
```

**Business Rationale:**
- `upcoming` → Too early, session hasn't started
- `open`/`closed` → Valid states for completion
- `completed` → Already done
- `cancelled` → Invalid operation

**Code Reference:** `TrainingSessionController.php:130-133`

---

### 3.3 Auto-Evaluation Process

**Triggered By:** Session marked as completed

**Process:**
```
1. Fetch ALL enrollments for session
2. For each enrollment:
   a. Skip if status = 'cancelled'
   b. Count attended days (status IN ['present', 'late'])
   c. Calculate required days (80% rule)
   d. Compare attended vs required
   e. Update enrollment status
3. Return summary
```

**Code Reference:** `CompletionService.php:54-71`

---

### 3.4 Session Completion is Irreversible

**Business Rule:**
- Once session.status = 'completed', **CANNOT revert**
- Attendance changes after completion **do NOT** re-evaluate enrollments
- Must manually update enrollment.status if needed

**Rationale:**
- Completion triggers certificate generation
- Prevents data inconsistency
- Audit trail integrity

---

## 4. Enrollment Completion Rules

### 4.1 Completion Threshold (80% Rule)

**Formula:**
```php
totalDays = (end_date - start_date) + 1
attendedCount = COUNT(attendances WHERE status IN ['present', 'late'])

if (totalDays <= 1) {
    requiredCount = 1              // 100% for single-day
} else {
    requiredCount = CEIL(totalDays × 0.8)  // 80% for multi-day
}

if (attendedCount >= requiredCount) {
    enrollment.status = 'completed'
    enrollment.completed_at = NOW()
} else {
    enrollment.status = 'confirmed'   // Did not pass
    enrollment.completed_at = NULL
}
```

**Code Reference:** `CompletionService.php:32-41`

---

### 4.2 Completion Examples

#### Single-Day Sessions (100% Required)

| Total Days | Attended | Required | Result | Reason |
|-----------|----------|----------|--------|--------|
| 1 | 1 | 1 | ✅ Completed | 1 ≥ 1 |
| 1 | 0 | 1 | ❌ Not completed | 0 < 1 |

**Business Rationale:**
- Single-day sessions require full attendance
- No partial completion allowed
- Must attend the entire session

---

#### Multi-Day Sessions (80% Required)

| Total Days | Attended | Required | Calculation | Result |
|-----------|----------|----------|-------------|--------|
| 3 | 3 | 3 | ceil(3×0.8)=3 | ✅ Completed |
| 3 | 2 | 3 | ceil(3×0.8)=3 | ❌ Not completed |
| 5 | 5 | 4 | ceil(5×0.8)=4 | ✅ Completed |
| 5 | 4 | 4 | ceil(5×0.8)=4 | ✅ Completed |
| 5 | 3 | 4 | ceil(5×0.8)=4 | ❌ Not completed |
| 10 | 8 | 8 | ceil(10×0.8)=8 | ✅ Completed |
| 10 | 7 | 8 | ceil(10×0.8)=8 | ❌ Not completed |

**Key Points:**
- `CEIL` function rounds UP: ceil(2.4) = 3
- 3-day course requires 3 days (100%), not 2.4 days
- 5-day course requires 4 days (80%)
- 10-day course requires 8 days (80%)

---

### 4.3 Constants & Configuration

```php
// CompletionService.php
private const ATTENDED_STATUSES = ['present', 'late'];
private const MULTI_DAY_THRESHOLD = 0.8;  // 80%
```

**Business Configuration:**
- **80% threshold** is HARDCODED
- Cannot be changed per program/session
- Future: May add configurable thresholds

**Rationale:**
- Industry standard for training completion
- Balances flexibility with accountability
- Prevents abuse (e.g., attending only 1 day of 10-day course)

---

### 4.4 Edge Cases

#### Case 1: Enrolled After Session Started
```
Scenario: Student enrolls on Day 3 of 5-day session
Rule: Still need to attend 80% of TOTAL days (4/5), not remaining days
Result: Likely will NOT complete (only 3 days remaining)
```

**Business Decision:**
- Late enrollments are discouraged
- Completion based on session duration, not enrollment date
- Admin can manually override if justified

---

#### Case 2: Cancelled Mid-Session
```
Scenario: Student cancels on Day 3 of 5-day session
Rule: enrollment.status = 'cancelled'
Action: Skip during auto-evaluation
Result: Status remains 'cancelled', NOT evaluated
```

**Business Decision:**
- Cancellation is final
- Cannot earn completion/certificate
- Attendance records preserved for audit

---

#### Case 3: Mix of Present and Late
```
Scenario: 5-day session, student has:
  - Day 1: present
  - Day 2: late
  - Day 3: present
  - Day 4: absent
  - Day 5: late

Calculation:
  attended = 4 (present + late + present + late)
  required = ceil(5 × 0.8) = 4
  Result: ✅ COMPLETED (4 ≥ 4)
```

**Business Rationale:**
- Both `present` and `late` count equally
- Encourages attendance even if late
- No distinction in completion (same weight)

---

## 5. Certificate Generation Rules

### 5.1 Eligibility Requirements

**Prerequisites:**
```
✓ enrollment.status = 'completed'
✓ enrollment.completed_at IS NOT NULL
✓ session.status = 'completed'
✓ No existing certificate for this enrollment
```

**Code Reference:** `CertificateController.php`

---

### 5.2 Generation Triggers

#### Manual Triggers

**Session-Level:**
```
POST /api/sessions/{id}/certificates/generate
- Generates for ALL completed enrollments in session
- Admin or Trainer only
```

**Program-Level:**
```
POST /api/programs/{id}/certificates/generate
- Generates for ALL completed enrollments in ALL sessions
- Admin or Trainer only
```

**Business Decision:**
- Manual trigger only (no auto-generation)
- Allows admin/trainer to verify before issuing
- Bulk generation for efficiency

---

### 5.3 Template Selection Priority

```
1. Session-specific template (highest priority)
   WHERE scope = 'session' AND scope_id = session.id

2. Program-specific template
   WHERE scope = 'program' AND scope_id = program.id

3. Global template (fallback)
   WHERE scope = 'global'

4. Default system template (if no custom templates)
```

**Business Rules:**
- More specific templates override general ones
- Allows customization per program/session
- Ensures certificate is always generated (fallback exists)

**Code Reference:** `CertificateService.php`

---

### 5.4 Certificate Uniqueness

**Constraints:**
```
✓ One certificate per enrollment (UNIQUE KEY on enrollment_id)
✓ Unique certificate_code generated (UUID-based)
✓ Cannot generate duplicate for same enrollment
```

**Business Rules:**
1. **Idempotent Generation:**
   - If certificate exists → Skip (do not regenerate)
   - Return existing certificate

2. **Immutability:**
   - Once generated, certificate cannot be edited
   - Can only REVOKE (sets revoked_at date)

3. **Revocation:**
   - Sets `revoked_at = NOW()`
   - Sets `revoke_reason = text`
   - Certificate still exists but marked invalid
   - QR verification shows "revoked"

---

### 5.5 Certificate Delivery

**Automatic Actions on Generation:**
```
1. Create PDF file (stored in storage/certificates/)
2. Generate QR code (verification URL embedded)
3. Save file paths to database
4. Send email notification to student
5. Email includes:
   - Congratulations message
   - Download link
   - Verification QR code
```

**Email Template Fields:**
- Student name
- Program name
- Session title
- Issue date
- Certificate code
- Download URL

---

## 6. Authorization & Permissions

### 6.1 Role-Based Access Control (RBAC)

#### Admin Role

**Can Do:**
- ✅ Manage users (create, update, delete)
- ✅ Approve/reject ALL requests (program, session, trainee)
- ✅ View ALL programs/sessions/enrollments
- ✅ Mark ANY session completed
- ✅ Record attendance for ANY session
- ✅ Generate certificates for ANY program/session
- ✅ Revoke certificates
- ✅ Manage certificate templates (global, program, session)

**Cannot Do:**
- ❌ Enroll as student (must use separate student account)

---

#### Trainer Role

**Can Do:**
- ✅ Create program/session/trainee requests
- ✅ View OWN programs/sessions
- ✅ Mark OWN sessions completed
- ✅ Record attendance for OWN sessions
- ✅ Generate certificates for OWN programs/sessions
- ✅ Manage OWN certificate templates

**Cannot Do:**
- ❌ Approve/reject requests (admin only)
- ❌ View/modify other trainers' programs/sessions
- ❌ Manage users
- ❌ Revoke certificates (admin only)
- ❌ Access global templates (view only)

---

#### Student Role

**Can Do:**
- ✅ Self-enroll in open sessions
- ✅ Cancel own enrollment (before start date)
- ✅ View own enrollments
- ✅ View own attendance records
- ✅ View/download own certificates
- ✅ Update own profile

**Cannot Do:**
- ❌ Create programs/sessions
- ❌ Record attendance
- ❌ View other students' data
- ❌ Mark sessions completed
- ❌ Generate certificates

---

### 6.2 Ownership Rules

**Session Ownership:**
```php
// Trainer can only complete own sessions
if (user.role === 'trainer' && session.created_by !== user.id) {
    return 403 Forbidden
}
```

**Enrollment Ownership:**
```php
// Student can only cancel own enrollments
if (enrollment.user_id !== user.id) {
    return 403 Forbidden
}
```

**Certificate Ownership:**
```php
// Student can only view own certificates
// Trainer can view certificates from own programs
// Admin can view all certificates
```

---

### 6.3 Data Visibility Rules

| Data Type | Admin | Trainer | Student |
|-----------|-------|---------|---------|
| Users | All | None | Self only |
| Programs | All | Own + Published | Published only |
| Sessions | All | Own | Enrolled only |
| Enrollments | All | Own sessions | Self only |
| Attendances | All | Own sessions | Self only |
| Certificates | All | Own programs | Self only |
| Templates | All | Own + Global | None |

---

## 7. Data Integrity Rules

### 7.1 Referential Integrity

**Cascade Deletes:**
```sql
DELETE user → CASCADE DELETE user_profile
DELETE user → CASCADE DELETE enrollments
DELETE user → CASCADE DELETE attendances
DELETE user → CASCADE DELETE certificates

DELETE program → CASCADE DELETE training_sessions
DELETE training_sessions → CASCADE DELETE enrollments
DELETE enrollments → CASCADE DELETE attendances
DELETE enrollments → CASCADE DELETE certificates
```

**Business Rationale:**
- Maintain data consistency
- Prevent orphaned records
- Audit trail preserved through soft deletes (future)

---

### 7.2 Unique Constraints

```sql
-- One profile per user
UNIQUE KEY (user_id) ON user_profiles

-- One enrollment per user per session
UNIQUE KEY (user_id, session_id) ON enrollments

-- One attendance per enrollment per date
UNIQUE KEY (enrollment_id, attendance_date) ON attendances

-- One certificate per enrollment
UNIQUE KEY (enrollment_id) ON certificates

-- Unique certificate code
UNIQUE KEY (certificate_code) ON certificates

-- Unique email
UNIQUE KEY (email) ON users

-- Unique program code
UNIQUE KEY (code) ON programs
```

---

### 7.3 Date Validation Rules

**Session Dates:**
```
✓ start_date <= end_date
✓ start_date must be future or today (for creation)
✓ end_date must be >= start_date
```

**Attendance Dates:**
```
✓ attendance_date BETWEEN session.start_date AND session.end_date
✓ Cannot record attendance for future dates
✓ Cannot record attendance before session starts
```

**Enrollment Dates:**
```
✓ enrolled_at <= NOW()
✓ completed_at <= NOW()
✓ completed_at >= enrolled_at (if not null)
```

---

### 7.4 Status Transition Rules

**Enrollment Status Transitions:**
```
pending → confirmed (admin approval)
pending → cancelled (student cancellation)
confirmed → completed (session completion + 80% attendance)
confirmed → cancelled (manual cancellation)

INVALID:
completed → confirmed (cannot undo completion)
cancelled → confirmed (cannot reactivate, must re-enroll)
```

**Session Status Transitions:**
```
upcoming → open (manual or scheduled)
open → closed (after end_date or manual)
closed → completed (manual completion)
open → completed (manual completion)
any → cancelled (manual cancellation)

INVALID:
completed → any other status (completion is final)
```

---

## 8. Business Exceptions & Edge Cases

### 8.1 Grandfather Clause

**Scenario:** Rules change mid-session

**Business Decision:**
- Rules at time of enrollment apply
- No retroactive changes
- Document rule version in enrollment metadata

**Example:**
- Session created when threshold was 70%
- Threshold changed to 80% globally
- Existing enrollments still use 70%

**Implementation Status:** ⚠️ NOT IMPLEMENTED (future feature)

---

### 8.2 Force Completion Override

**Scenario:** Special circumstances require manual override

**Business Rule:**
- Admin can manually set `enrollment.status = 'completed'`
- Must document reason in notes/admin_requests
- Bypasses 80% rule
- Certificate can still be generated

**Use Cases:**
- Student had emergency, approved exception
- Attendance records lost/corrupted
- Special arrangement with trainer

**Implementation:** Direct database update or admin API (future)

---

### 8.3 Partial Session Attendance

**Scenario:** Student enrolled mid-session or left early

**Current Rule:**
- Completion based on TOTAL session days, not attended days
- Likely will not reach 80% if joined late

**Future Consideration:**
- Pro-rate completion based on enrollment date
- Requires business approval

---

### 8.4 Session Date Extension

**Scenario:** Session extended beyond original end_date

**Business Rule:**
- Update `session.end_date`
- Already-completed enrollments NOT re-evaluated
- Students can attend additional days
- Re-evaluation requires manual trigger

**Implementation Status:** ✅ Supported (update session dates anytime)

---

## 9. Validation Rules Summary

### 9.1 Enrollment Validation

| Rule | Student Enroll | Admin Approve | Error Code |
|------|---------------|---------------|------------|
| Session exists | Required | Required | 404 |
| Session approved | Required | Not checked | 422 |
| Session open | Required | Not checked | 422 |
| Session not completed | Required | Not checked | 422 |
| Capacity not full | Required | Required | 422 |
| Not already enrolled | Required | Not checked | 422 |
| Email provided | N/A | Required | 422 |

---

### 9.2 Attendance Validation

| Rule | Single | Bulk | Error Code |
|------|--------|------|------------|
| Enrollment exists | Required | Required | 404 |
| Session exists | Required | Required | 404 |
| Date within range | Required | Required | 422 |
| Valid status | Required | Required | 422 |
| Enrollment confirmed | Required | Required | 422 |

---

### 9.3 Session Completion Validation

| Rule | Check | Error Code |
|------|-------|------------|
| Session exists | Required | 404 |
| User is admin or trainer | Required | 403 |
| Trainer owns session | If trainer | 403 |
| Status is open/closed | Required | 422 |
| Not already completed | Required | 422 |

---

## 10. Constants & Configuration

### 10.1 Hardcoded Constants

```php
// Attendance statuses that count as attended
ATTENDED_STATUSES = ['present', 'late']

// Multi-day completion threshold
MULTI_DAY_THRESHOLD = 0.8  // 80%

// Single-day completion threshold
SINGLE_DAY_THRESHOLD = 1.0  // 100% (implicit)
```

**Location:** `app/Services/CompletionService.php`

---

### 10.2 Configurable Settings (Future)

**Potential Configuration Options:**
```php
// Per-program completion threshold
program.completion_threshold = 0.8  // Default 80%

// Grace period for late attendance
program.late_grace_minutes = 30  // Default no limit

// Minimum session duration
program.min_session_days = 1  // Default 1 day

// Certificate auto-generation
program.auto_generate_certificates = false  // Default manual
```

**Implementation Status:** ⚠️ NOT IMPLEMENTED

---

## 11. Audit & Compliance

### 11.1 Audit Trail

**Tracked Events:**
- Enrollment created/updated/cancelled
- Attendance recorded/updated
- Session completed
- Certificate generated/revoked
- Request approved/rejected

**Audit Fields:**
```
created_at - When record created
updated_at - Last modification time
created_by - User who created (programs/sessions)
resolved_by - Admin who approved/rejected
enrolled_at - When enrollment occurred
completed_at - When enrollment completed
issued_at - When certificate issued
revoked_at - When certificate revoked
```

---

### 11.2 Data Retention

**Business Rules:**
- Completed enrollments: Keep indefinitely
- Cancelled enrollments: Keep for audit (1 year)
- Attendance records: Keep indefinitely
- Certificates: Keep indefinitely (even if revoked)
- Revoked certificates: Keep with revocation reason

**Implementation Status:** ⚠️ No automatic cleanup (manual only)

---

## 12. Business Rule Changes Log

| Date | Version | Rule | Change | Reason |
|------|---------|------|--------|--------|
| 2026-01-06 | 1.0 | Initial | All rules documented | First documentation |
| 2026-01-06 | 1.1 | Enrollment | Student cannot enroll in completed sessions | Data consistency |
| 2026-01-06 | 1.1 | Enrollment | Admin CAN enroll in completed sessions | Flexibility for exceptions |

---

## Related Documentation

- [Enrollment Flow](./ENROLLMENT_FLOW.md)
- [Database Schema](./DATABASE_SCHEMA.md)
- [API Specification](./API-SPECIFICATION.md)
- [Sequence Diagrams](./SEQUENCE-DIAGRAMS.md)

---

**Last Updated:** 2026-01-06
**Version:** 1.1
**Maintained By:** Development Team
