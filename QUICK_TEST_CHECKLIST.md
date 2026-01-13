# Quick Test Checklist - Evaluation System

⏰ **Estimated Time:** 15-20 minutes
🌐 **Server:** http://127.0.0.1:8005

---

## ✅ Pre-Test Setup

- [ ] Server is running on port 8005
- [ ] Database has test data (2 existing evaluations)
- [ ] Browser is ready (Chrome/Firefox recommended)
- [ ] Open browser console (F12) to check for errors

---

## 🧪 Test Suite 1: TRAINEE ROLE

**Login:** trainee@example.com / password

### Test 1A: View Feedback Page ✓
- [ ] Navigate to **Feedback** from sidebar
- [ ] Page loads without errors
- [ ] Sessions WITHOUT feedback already submitted are shown
- [ ] Session #1 and #4 are NOT shown (already submitted)

**Expected Result:** List of eligible sessions displayed

---

### Test 1B: Certificate Download Blocked 🚫
- [ ] Navigate to **Certificates** from sidebar
- [ ] Find any certificate where feedback NOT submitted
- [ ] Click **Download Certificate** button
- [ ] Modal appears: "Complete Course Evaluation First"
- [ ] Click **Go to Feedback** button
- [ ] Redirects to feedback page

**Expected Result:** Download blocked with clear modal message

---

### Test 1C: Submit Feedback Form 📝
- [ ] From Feedback page, click **Submit Feedback** on any session
- [ ] Modal opens with feedback form
- [ ] Fill all fields:
  - Overall Rating: ★★★★★ (5 stars)
  - Content Quality: ★★★★★ (5 stars)
  - Trainer Quality: ★★★★★ (5 stars)
  - Material Quality: ★★★★☆ (4 stars)
  - Organization: ★★★★★ (5 stars)
  - Would Recommend: Yes
  - Difficulty Level: Appropriate
  - Strengths: "Excellent hands-on practice and real-world examples"
  - Improvements: "More time for Q&A sessions would be helpful"
  - Comments: "Great course overall, highly recommend!"
- [ ] Click **Submit Feedback**
- [ ] Success message appears
- [ ] Session disappears from feedback list

**Expected Result:** Feedback submitted successfully

---

### Test 1D: Certificate Download After Feedback ✅
- [ ] Navigate back to **Certificates**
- [ ] Find certificate for session you just submitted feedback for
- [ ] Click **Download Certificate**
- [ ] PDF downloads without modal blocking
- [ ] Open PDF and verify content

**Expected Result:** Certificate downloads successfully

---

### Test 1E: Prevent Duplicate Feedback 🔒
- [ ] Navigate to **Feedback** page again
- [ ] Verify session you submitted feedback for is NOT shown
- [ ] Try refreshing the page
- [ ] Confirm you cannot submit feedback twice

**Expected Result:** Session not available for duplicate feedback

---

### Test 1F: Trainee Cannot Access Evaluation Results ❌
- [ ] Open new tab: http://127.0.0.1:8005/sessions/1/evaluation
- [ ] Verify **403 Forbidden** or **Access Denied** message

**Expected Result:** Access denied for trainee role

---

## 🧪 Test Suite 2: TRAINER ROLE

**Login:** trainer@example.com / password
**(This is Trainer User, ID: 2, teaches Session #3 and #4)**

### Test 2A: View Own Session Evaluations ✅
- [ ] Navigate to: http://127.0.0.1:8005/sessions/4/evaluation
- [ ] Page loads successfully
- [ ] Shows session info: "Advanced React Patterns - Session 2"
- [ ] Displays summary statistics
- [ ] Shows evaluation from Trainee User (Rating: 5/5)
- [ ] No errors in console

**Expected Result:** Can view evaluation results for own session

---

### Test 2B: Cannot View Other Trainer's Sessions ❌
- [ ] Navigate to: http://127.0.0.1:8005/sessions/1/evaluation
- [ ] Verify **403 Forbidden** message
- [ ] Trainer User (ID: 2) cannot access Session #1 (belongs to John Trainer, ID: 5)

**Expected Result:** Access denied for other trainer's sessions

---

### Test 2C: Feedback Page Navigation 🔍
- [ ] Navigate to **Feedback** from sidebar
- [ ] Page loads (currently shows old mock data interface)
- [ ] No errors in console

**Expected Result:** Page loads without errors

**Note:** Trainer feedback page hasn't been updated to new system yet (known limitation)

---

## 🧪 Test Suite 3: ADMIN ROLE

**Login:** admin@example.com / password

### Test 3A: Admin Feedback Sessions List 📋
- [ ] Navigate to **Feedback** tab from sidebar
- [ ] Page loads showing sessions list
- [ ] Verify UI components:
  - Search bar present
  - Status filter dropdown present
  - Session cards displayed
- [ ] Check session cards show:
  - Session title and status badge
  - Course name
  - Trainer name
  - Start date
  - Feedback count
  - Average rating

**Expected Result:** Admin sees complete feedback sessions list

---

### Test 3B: Search Functionality 🔎
- [ ] In search bar, type: "Web Development"
- [ ] Verify only matching sessions shown
- [ ] Clear search
- [ ] Verify all sessions return
- [ ] Try searching by trainer name: "John"
- [ ] Verify results filter correctly

**Expected Result:** Search works for session, course, and trainer names

---

### Test 3C: Filter Functionality 🎯
- [ ] Click **Status filter** dropdown
- [ ] Select "Completed"
- [ ] Verify only completed sessions shown
- [ ] Select "All Status"
- [ ] Verify all sessions return
- [ ] Check "Showing X of Y sessions" updates correctly

**Expected Result:** Filter works for session status

---

### Test 3D: View Evaluation Results (Admin Privilege) 👑
- [ ] Click on **Session #1** card (Web Development Fundamentals)
- [ ] Redirects to: http://127.0.0.1:8005/sessions/1/evaluation
- [ ] Page loads successfully
- [ ] Shows 1 evaluation (Trainee User, Rating: 4/5)
- [ ] Back to Feedback page
- [ ] Click on **Session #4** card (Advanced React Patterns)
- [ ] Shows 1 evaluation (Trainee User, Rating: 5/5)
- [ ] Admin can access evaluations for ANY session

**Expected Result:** Admin can view all session evaluations regardless of trainer

---

### Test 3E: Empty State Handling 📭
- [ ] Navigate to a session with 0 evaluations (Session #3 or #5)
- [ ] Example: http://127.0.0.1:8005/sessions/3/evaluation
- [ ] Verify page shows:
  - "No evaluations submitted yet" or similar message
  - 0 total responses
  - No errors or crashes

**Expected Result:** Empty state handled gracefully

---

## 🧪 Test Suite 4: API ENDPOINTS

### Test 4A: GET Evaluation Results API 🔌

**Using Browser Console:**
```javascript
// Open browser console (F12) and run:
fetch('/api/sessions/1/evaluation', {
  headers: { 'Accept': 'application/json' }
})
.then(r => r.json())
.then(data => console.log(data))
```

- [ ] API returns 200 status
- [ ] Response has correct structure:
  - `success: true`
  - `data.session` object
  - `data.evaluations` array
  - `data.averages` object
  - `data.total_evaluations` number

**Expected Result:** API returns well-structured JSON

---

### Test 4B: POST Evaluation Submission API 📤

**Note:** This is complex to test manually. Verify it works through the UI instead.

- [ ] Use UI to submit feedback (Test 1C)
- [ ] Check browser Network tab (F12 → Network)
- [ ] Find POST request to `/sessions/{id}/evaluation`
- [ ] Verify request payload includes all fields
- [ ] Verify response shows success

**Expected Result:** POST request succeeds with valid data

---

## ✅ Final Verification Checklist

### Database Check (Optional - Advanced)
Run these commands in terminal:

```bash
# Check total evaluations
php artisan tinker --execute="echo App\Models\Evaluation::count();"

# Should be 2 initially, 3 after Test 1C

# View all evaluations
php artisan tinker --execute="App\Models\Evaluation::with(['user', 'session'])->get()->each(function(\$e) { echo \$e->user->name . ' - ' . \$e->session->title . ' - ' . \$e->overall_rating . PHP_EOL; });"
```

---

## 🐛 Bug Tracking

If you find issues, record them here:

| Test # | Issue Description | Severity | Status |
|--------|------------------|----------|--------|
| | | | |

**Severity Levels:**
- 🔴 Critical - Blocks functionality
- 🟡 Major - Impacts user experience
- 🟢 Minor - Cosmetic or edge case

---

## 📊 Test Results Summary

**Date:** _________________
**Tester:** _________________
**Total Tests:** 19
**Passed:** _____ / 19
**Failed:** _____ / 19
**Skipped:** _____ / 19

### Overall Status:
- [ ] ✅ All tests passed - Ready for production
- [ ] ⚠️ Minor issues found - Can proceed with notes
- [ ] ❌ Critical issues found - Requires fixes

---

## 📝 Notes & Observations

Use this space for additional comments:

```
_______________________________________________________________

_______________________________________________________________

_______________________________________________________________

_______________________________________________________________
```

---

## 🎉 Completion

Once all tests are complete:

1. Review TEST_DATA_REFERENCE.md for details on test data
2. Review TESTING_EVALUATION_SYSTEM.md for comprehensive guide
3. Check for any console errors during testing
4. Verify database state if needed
5. Report findings to development team

**Next Steps:**
- [ ] Fix any critical bugs found
- [ ] Update Trainer feedback page to use new system (if needed)
- [ ] Consider adding more test scenarios
- [ ] Plan for production deployment
