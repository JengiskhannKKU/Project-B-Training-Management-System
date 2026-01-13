# Trainer Authorization Testing Guide

## Overview
ทดสอบว่า Trainer แต่ละคนสามารถดู feedback ได้เฉพาะ sessions ของตัวเองเท่านั้น

**Server URL:** http://127.0.0.1:8005

---

## Test Accounts Created

### 1. Trainer User (Original)
- **Email:** trainer@example.com
- **Password:** password
- **User ID:** 2
- **Completed Sessions:**
  - Session #4: Advanced React Patterns - Session 2 (1 feedback, 5.0/5)
  - Session #9: Data Science Bootcamp - Jan 2026 (1 feedback, 5.0/5)
  - Session #10: Data Science Bootcamp - Jan 2026 (0 feedback)

### 2. John Trainer (Original)
- **Email:** john.trainer@example.com
- **Password:** password
- **User ID:** 5
- **Completed Sessions:**
  - Session #1: Web Development Fundamentals - Session 1 (1 feedback, 4.0/5)

### 3. Sarah Johnson (NEW)
- **Email:** sarah.trainer@example.com
- **Password:** password
- **User ID:** 8
- **Completed Sessions:** None (all scheduled)
  - Session #3: Advanced React Patterns - Session 1 (scheduled)
  - Session #6: Data Science 101 - Session 2 (scheduled)

### 4. Mike Chen (NEW)
- **Email:** mike.trainer@example.com
- **Password:** password
- **User ID:** 9
- **Completed Sessions:**
  - Session #7: Web Dev Bootcamp - Jan 2026 (1 feedback, 5.0/5)
  - Session #8: Mobile Dev Bootcamp - Jan 2026 (1 feedback, 4.0/5)

---

## Test Scenarios

### Scenario 1: Trainer User (trainer@example.com)

#### ✅ Test 1A: View Own Feedback Page
1. Login as **trainer@example.com**
2. Navigate to **Feedback** from sidebar
3. **Expected Result:**
   - See 3 sessions (only their own):
     - Session #4: Advanced React Patterns - Session 2
     - Session #9: Data Science Bootcamp - Jan 2026
     - Session #10: Data Science Bootcamp - Jan 2026
   - Should NOT see sessions from John, Sarah, or Mike

#### ✅ Test 1B: View Own Session Evaluations
1. Click on **Session #4** (Advanced React Patterns - Session 2)
2. **Expected Result:**
   - ✅ Page loads successfully
   - Shows 1 evaluation with rating 5/5
   - Can see all feedback details

#### ❌ Test 1C: Try to Access Other Trainer's Session (Should Fail)
1. Manually navigate to: `http://127.0.0.1:8005/sessions/1/evaluation` (John's session)
2. **Expected Result:**
   - ❌ **403 Forbidden** error
   - Cannot view evaluation results

#### ❌ Test 1D: Try to Access Mike's Session (Should Fail)
1. Navigate to: `http://127.0.0.1:8005/sessions/7/evaluation` (Mike's session)
2. **Expected Result:**
   - ❌ **403 Forbidden** error

---

### Scenario 2: John Trainer (john.trainer@example.com)

#### ✅ Test 2A: View Own Feedback Page
1. Login as **john.trainer@example.com**
2. Navigate to **Feedback**
3. **Expected Result:**
   - See 1 session (only his own):
     - Session #1: Web Development Fundamentals - Session 1
   - Should NOT see sessions from Trainer User, Sarah, or Mike

#### ✅ Test 2B: View Own Session Evaluations
1. Click on **Session #1**
2. **Expected Result:**
   - ✅ Page loads successfully
   - Shows 1 evaluation with rating 4/5

#### ❌ Test 2C: Try to Access Trainer User's Session (Should Fail)
1. Navigate to: `http://127.0.0.1:8005/sessions/4/evaluation` (Trainer User's session)
2. **Expected Result:**
   - ❌ **403 Forbidden** error

---

### Scenario 3: Sarah Johnson (sarah.trainer@example.com)

#### ✅ Test 3A: View Empty Feedback Page
1. Login as **sarah.trainer@example.com**
2. Navigate to **Feedback**
3. **Expected Result:**
   - See **0 sessions** (no completed sessions yet)
   - Shows "No sessions found" message
   - All her sessions are "scheduled" status, not "completed"

#### ❌ Test 3B: Try to Access Other's Sessions (Should Fail)
1. Navigate to: `http://127.0.0.1:8005/sessions/1/evaluation`
2. **Expected Result:**
   - ❌ **403 Forbidden** error

---

### Scenario 4: Mike Chen (mike.trainer@example.com)

#### ✅ Test 4A: View Own Feedback Page
1. Login as **mike.trainer@example.com**
2. Navigate to **Feedback**
3. **Expected Result:**
   - See 2 sessions (only his own):
     - Session #7: Web Dev Bootcamp (1 feedback, 5.0/5)
     - Session #8: Mobile Dev Bootcamp (1 feedback, 4.0/5)

#### ✅ Test 4B: View Own Session Evaluations
1. Click on **Session #7**
2. **Expected Result:**
   - ✅ Page loads successfully
   - Shows evaluation: "Mike is an excellent instructor!"
   - Rating: 5/5

3. Click on **Session #8**
4. **Expected Result:**
   - ✅ Page loads successfully
   - Shows evaluation: "Good coverage of mobile development fundamentals"
   - Rating: 4/5

#### ❌ Test 4C: Try to Access Other Trainer's Sessions (Should Fail)
1. Navigate to: `http://127.0.0.1:8005/sessions/1/evaluation` (John's)
2. Navigate to: `http://127.0.0.1:8005/sessions/4/evaluation` (Trainer User's)
3. **Expected Result:**
   - ❌ **403 Forbidden** error for both

---

## Cross-Authorization Test Matrix

| Trainer | Can Access Session #1? | Can Access Session #4? | Can Access Session #7? | Can Access Session #9? |
|---------|------------------------|------------------------|------------------------|------------------------|
| Trainer User (ID: 2) | ❌ 403 | ✅ YES | ❌ 403 | ✅ YES |
| John Trainer (ID: 5) | ✅ YES | ❌ 403 | ❌ 403 | ❌ 403 |
| Sarah Johnson (ID: 8) | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 403 |
| Mike Chen (ID: 9) | ❌ 403 | ❌ 403 | ✅ YES | ❌ 403 |

---

## Quick Test Commands

### Check Who Owns Which Session:
```bash
php artisan tinker --execute="
\$sessions = \App\Models\TrainingSession::with('trainer')->whereIn('id', [1,4,7,9])->get();
foreach (\$sessions as \$s) {
    echo 'Session ' . \$s->id . ': ' . \$s->title . ' -> Trainer: ' . \$s->trainer->name . ' (ID: ' . \$s->trainer_id . ')\n';
}
"
```

### Verify Evaluations Exist:
```bash
php artisan tinker --execute="
\$evals = \App\Models\Evaluation::with('session')->get();
foreach (\$evals as \$e) {
    echo 'Session ' . \$e->session_id . ': ' . \$e->session->title . ' - Rating: ' . \$e->overall_rating . '/5\n';
}
"
```

---

## Expected Policy Logic

The `EvaluationPolicy` should work as follows:

```php
public function viewSessionEvaluations(User $user, TrainingSession $session): bool
{
    // Admin can view all evaluations
    if ($user->role->name === 'admin') {
        return true; // ✅
    }

    // Trainer can only view evaluations for their own sessions
    if ($user->role->name === 'trainer' && $session->trainer_id === $user->id) {
        return true; // ✅
    }

    return false; // ❌ 403 Forbidden
}
```

---

## Test Result Tracking

| Test | Trainer | Session | Expected | Actual | Status |
|------|---------|---------|----------|--------|--------|
| 1A | Trainer User | Own feedback page | 3 sessions | | ⬜ |
| 1B | Trainer User | Session #4 | ✅ Allow | | ⬜ |
| 1C | Trainer User | Session #1 | ❌ 403 | | ⬜ |
| 1D | Trainer User | Session #7 | ❌ 403 | | ⬜ |
| 2A | John Trainer | Own feedback page | 1 session | | ⬜ |
| 2B | John Trainer | Session #1 | ✅ Allow | | ⬜ |
| 2C | John Trainer | Session #4 | ❌ 403 | | ⬜ |
| 3A | Sarah Johnson | Own feedback page | 0 sessions | | ⬜ |
| 3B | Sarah Johnson | Session #1 | ❌ 403 | | ⬜ |
| 4A | Mike Chen | Own feedback page | 2 sessions | | ⬜ |
| 4B | Mike Chen | Session #7 | ✅ Allow | | ⬜ |
| 4C | Mike Chen | Session #1 | ❌ 403 | | ⬜ |

---

## Admin Control Test (Bonus)

Login as **admin@example.com** and verify:

1. Can see ALL sessions from ALL trainers in `/admin/feedback`
2. Can access `/sessions/1/evaluation` ✅
3. Can access `/sessions/4/evaluation` ✅
4. Can access `/sessions/7/evaluation` ✅
5. Can access `/sessions/9/evaluation` ✅

**Admin should have unrestricted access to all evaluations.**

---

## Notes

- Only **completed** sessions appear in the feedback list
- **Scheduled** or **cancelled** sessions are filtered out
- Authorization is enforced at the Controller level via `$this->authorize('viewSessionEvaluations', $session)`
- Policy is registered in `AppServiceProvider::boot()` via `Gate::policy()`

---

## Troubleshooting

### If Authorization Fails Unexpectedly:

1. **Clear cache:**
   ```bash
   php artisan optimize:clear
   ```

2. **Check session trainer_id:**
   ```bash
   php artisan tinker --execute="
   \$session = \App\Models\TrainingSession::find(1);
   echo 'Session 1 trainer_id: ' . \$session->trainer_id;
   "
   ```

3. **Verify user role:**
   ```bash
   php artisan tinker --execute="
   \$user = \App\Models\User::where('email', 'trainer@example.com')->first();
   echo 'User role: ' . \$user->role->name;
   "
   ```

4. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

**Ready to test!** Start with Trainer User, then test cross-authorization with other trainers. 🚀
