# Test Data Reference Guide

## Server Information
- **URL:** http://127.0.0.1:8005
- **Database:** SQLite (database/database.sqlite)

---

## Test Accounts

### Admin
- **Email:** admin@example.com
- **Password:** password
- **User ID:** 1
- **Can:** View all session evaluations

### Trainer #1 (Trainer User)
- **Email:** trainer@example.com
- **Password:** password
- **User ID:** 2
- **Teaches:** Session #3 (Advanced React Patterns - Session 1), Session #4 (Advanced React Patterns - Session 2)

### Trainer #2 (John Trainer)
- **Email:** john.trainer@example.com
- **Password:** password
- **User ID:** 5
- **Teaches:** Session #1 (Web Development Fundamentals - Session 1), Session #2 (Web Development Fundamentals - Session 2)

### Trainee (Trainee User)
- **Email:** trainee@example.com
- **Password:** password
- **User ID:** 3
- **Has Certificates:** Session #1, #4, #6, #7
- **Attendance 100%:** Multiple sessions
- **Already Submitted Feedback:** Session #1, Session #4

### Student (Another Trainee)
- **Email:** student@example.com
- **Password:** password
- **User ID:** 4

---

## Training Sessions

| ID | Title | Trainer | Trainer ID | Status | Has Evaluations |
|----|-------|---------|------------|--------|-----------------|
| 1 | Web Development Fundamentals - Session 1 | John Trainer | 5 | completed | Yes (1) |
| 2 | Web Development Fundamentals - Session 2 | John Trainer | 5 | cancelled | No |
| 3 | Advanced React Patterns - Session 1 | Trainer User | 2 | scheduled | No |
| 4 | Advanced React Patterns - Session 2 | Trainer User | 2 | completed | Yes (1) |
| 5 | Data Science 101 - Session 1 | John Trainer | 5 | scheduled | No |

---

## Certificates

### Trainee User (ID: 3) Certificates:
1. **Certificate #1** - Session #1 (Web Dev Fundamentals - Session 1)
   - Status: Can Download = No (Already submitted feedback)
   - Should be able to download now

2. **Certificate #4** - Session #4 (Advanced React Patterns - Session 2)
   - Status: Can Download = No (Already submitted feedback)
   - Should be able to download now

3. **Certificate #6, #7** - Other sessions
   - Status: Can Download = No
   - Need to check if feedback submitted

### Other Trainees:
- Student User (ID: 4) has certificates for Session #1, #2
- Alice Wonder (ID: 6) has certificate for Session #1
- Bob Builder (ID: 7) has certificates for Session #4

---

## Existing Evaluations

| ID | User | Session | Rating | Status |
|----|------|---------|--------|--------|
| 4 | Trainee User (ID: 3) | Session #1 | 4/5 | Submitted |
| 5 | Trainee User (ID: 3) | Session #4 | 5/5 | Submitted |

---

## Test Scenarios by URL

### Trainee Testing

#### 1. Feedback Page (List eligible sessions)
**URL:** http://127.0.0.1:8005/trainee/feedback
**Login as:** trainee@example.com
**Expected:**
- Should NOT show Session #1 or #4 (already submitted)
- Should show other sessions with 80%+ attendance and certificates

#### 2. Certificates Page
**URL:** http://127.0.0.1:8005/trainee/certificates
**Login as:** trainee@example.com
**Expected:**
- Show all certificates for Trainee User
- Certificates for Session #1 and #4 should allow download (feedback submitted)
- Other certificates should be blocked with feedback modal

#### 3. Access Evaluation Results (Should Fail)
**URL:** http://127.0.0.1:8005/sessions/1/evaluation
**Login as:** trainee@example.com
**Expected:** 403 Forbidden or Access Denied

---

### Trainer Testing

#### 1. View Own Session Evaluations
**URL:** http://127.0.0.1:8005/sessions/4/evaluation
**Login as:** trainer@example.com (Trainer User, ID: 2)
**Expected:**
- ✅ SUCCESS - Can view (Session #4 belongs to this trainer)
- Shows 1 evaluation from Trainee User
- Rating: 5/5

#### 2. Try to View Other Trainer's Session (Should Fail)
**URL:** http://127.0.0.1:8005/sessions/1/evaluation
**Login as:** trainer@example.com (Trainer User, ID: 2)
**Expected:**
- ❌ 403 Forbidden - Cannot view (Session #1 belongs to John Trainer, ID: 5)

#### 3. Alternative - John Trainer Views His Session
**URL:** http://127.0.0.1:8005/sessions/1/evaluation
**Login as:** john.trainer@example.com (John Trainer, ID: 5)
**Expected:**
- ✅ SUCCESS - Can view (Session #1 belongs to this trainer)
- Shows 1 evaluation from Trainee User
- Rating: 4/5

---

### Admin Testing

#### 1. Admin Feedback Sessions List
**URL:** http://127.0.0.1:8005/admin/feedback
**Login as:** admin@example.com
**Expected:**
- Shows list of all completed sessions
- Session #1: 1 evaluation, avg rating 4.0
- Session #4: 1 evaluation, avg rating 5.0
- Can search and filter

#### 2. View Any Session Evaluation (Admin Privilege)
**URL:** http://127.0.0.1:8005/sessions/1/evaluation
**Login as:** admin@example.com
**Expected:**
- ✅ SUCCESS - Admin can view all sessions
- Shows evaluation data for Session #1

**URL:** http://127.0.0.1:8005/sessions/4/evaluation
**Login as:** admin@example.com
**Expected:**
- ✅ SUCCESS - Admin can view all sessions
- Shows evaluation data for Session #4

---

## API Testing

### 1. GET Evaluation Results
```bash
# Session #1 evaluations (1 evaluation)
curl -X GET http://127.0.0.1:8005/api/sessions/1/evaluation \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_COOKIE"

# Session #4 evaluations (1 evaluation)
curl -X GET http://127.0.0.1:8005/api/sessions/4/evaluation \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_COOKIE"
```

### 2. POST New Evaluation
```bash
# Submit evaluation for a session (as trainee)
curl -X POST http://127.0.0.1:8005/sessions/2/evaluation \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_COOKIE" \
  -d '{
    "overall_rating": 5,
    "content_quality": 5,
    "trainer_quality": 4,
    "material_quality": 5,
    "organization": 5,
    "would_recommend": true,
    "difficulty_level": "appropriate",
    "strengths": "Great hands-on exercises",
    "improvements": "More real-world examples",
    "comments": "Excellent course overall"
  }'
```

---

## Quick Test Flow

### For Trainee (trainee@example.com):
1. ✅ Login → Dashboard
2. ✅ Navigate to Certificates → See 4+ certificates
3. ⚠️ Try to download certificate for session without feedback → Blocked with modal
4. ✅ Click "Go to Feedback" → Navigate to feedback page
5. ✅ See list of sessions needing feedback (excluding #1 and #4)
6. ✅ Click "Submit Feedback" on a session
7. ✅ Fill form and submit
8. ✅ Return to certificates → Now can download
9. ❌ Try to access `/sessions/1/evaluation` → 403 Forbidden

### For Trainer (trainer@example.com):
1. ✅ Login → Dashboard
2. ✅ Navigate to `/sessions/4/evaluation` → See evaluation results (own session)
3. ❌ Navigate to `/sessions/1/evaluation` → 403 Forbidden (not own session)

### For Admin (admin@example.com):
1. ✅ Login → Dashboard
2. ✅ Navigate to Feedback tab → See sessions list
3. ✅ Search/filter sessions
4. ✅ Click any session → View evaluation results
5. ✅ Navigate to `/sessions/1/evaluation` → Success
6. ✅ Navigate to `/sessions/4/evaluation` → Success

---

## Database Queries for Verification

```sql
-- Check all evaluations
SELECT e.id, u.name as trainee, s.title as session, e.overall_rating, e.submitted_at
FROM evaluations e
JOIN users u ON e.user_id = u.id
JOIN training_sessions s ON e.session_id = s.id;

-- Check certificates for Trainee User
SELECT c.id, s.title as session, c.can_download
FROM certificates c
JOIN training_sessions s ON c.session_id = s.id
WHERE c.user_id = 3;

-- Check enrollments with attendance
SELECT u.name, s.title, e.attendance_percent
FROM enrollments e
JOIN users u ON e.user_id = u.id
JOIN training_sessions s ON e.session_id = s.id
WHERE e.attendance_percent >= 80;

-- Check which sessions need evaluations
SELECT s.id, s.title, s.status, COUNT(e.id) as eval_count
FROM training_sessions s
LEFT JOIN evaluations e ON s.id = e.session_id
GROUP BY s.id
ORDER BY s.id;
```

---

## Common Issues & Solutions

### Issue: Cannot download certificate
**Check:**
1. Does user have 80%+ attendance?
2. Has user submitted feedback?
3. Is certificate marked as `can_download = true`?

### Issue: 403 when accessing evaluation results
**Check:**
1. Is user logged in?
2. Does user have correct role (Admin or Trainer)?
3. If Trainer, does session belong to them? (session.trainer_id = user.id)

### Issue: Session not showing in feedback list
**Check:**
1. Is session status = "completed"?
2. Does user have certificate for this session?
3. Is attendance >= 80%?
4. Has user already submitted feedback for this session?

### Issue: Cannot submit feedback
**Check:**
1. All required fields filled?
2. Ratings are between 1-5?
3. User hasn't already submitted for this session?
4. Session is eligible (certificate + 80% attendance)?

---

## Reset Test Data (if needed)

```bash
# Delete all evaluations
php artisan tinker --execute="App\Models\Evaluation::truncate();"

# Reset certificate download flags
php artisan tinker --execute="App\Models\Certificate::query()->update(['can_download' => false]);"

# Or run fresh migrations (WARNING: deletes all data)
php artisan migrate:fresh --seed
```
