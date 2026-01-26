# Testing Guide: Evaluation & Feedback System

## Overview
This guide provides step-by-step instructions for testing the evaluation and feedback system across all three roles: Trainee, Trainer, and Admin.

**Server URL:** http://127.0.0.1:8005

---

## Test Credentials

### Trainee Account
- **Email:** trainee@example.com
- **Password:** password

### Trainer Account
- **Email:** trainer@example.com
- **Password:** password

### Admin Account
- **Email:** admin@example.com
- **Password:** password

---

## Part 1: Trainee Testing

### Test 1.1: View Feedback Page (Eligible Sessions)
**Expected Result:** See list of sessions where feedback can be submitted

1. Login as Trainee
2. Navigate to **Feedback** page from sidebar
3. Verify you see sessions where:
   - ✅ You have a certificate
   - ✅ Attendance is >= 80%
   - ❌ You haven't submitted feedback yet

**Pass Criteria:**
- Sessions are displayed with correct information
- Only eligible sessions are shown
- Page loads without errors

---

### Test 1.2: Certificate Download Blocked (No Feedback)
**Expected Result:** Cannot download certificate before submitting feedback

1. Still logged in as Trainee
2. Navigate to **Certificates** page
3. Find a certificate for a session where feedback hasn't been submitted
4. Click **Download Certificate** button
5. Verify a modal appears with message:
   - "Complete Course Evaluation First"
   - "Please submit your feedback to unlock your certificate"
6. Click **Go to Feedback** button
7. Verify you're redirected to the feedback page

**Pass Criteria:**
- Download is blocked with clear messaging
- Modal provides explanation and action button
- Redirect works correctly

---

### Test 1.3: Submit Feedback Form
**Expected Result:** Successfully submit evaluation with all required fields

1. From Feedback page, click **Submit Feedback** on a session
2. Fill out all fields:
   - **Overall Rating:** 5 stars
   - **Content Quality:** 5 stars
   - **Trainer Quality:** 5 stars
   - **Material Quality:** 4 stars
   - **Organization:** 5 stars
   - **Would Recommend:** Yes
   - **Difficulty Level:** Appropriate
   - **Strengths:** "Excellent hands-on practice and real-world examples"
   - **Improvements:** "More time for Q&A sessions"
   - **Additional Comments:** "Great course overall!"
3. Click **Submit Feedback** button
4. Verify success message appears
5. Verify the session disappears from the feedback page

**Pass Criteria:**
- All fields accept input correctly
- Form validation works (try submitting with missing fields)
- Success message displays
- Session is removed from list after submission

---

### Test 1.4: Certificate Download After Feedback
**Expected Result:** Can download certificate after submitting feedback

1. Navigate back to **Certificates** page
2. Find the certificate for the session you just submitted feedback for
3. Click **Download Certificate** button
4. Verify the PDF certificate downloads successfully
5. Open the PDF and verify it contains correct information

**Pass Criteria:**
- Download proceeds without modal blocking
- PDF generates correctly
- Certificate contains accurate trainee and session info

---

### Test 1.5: Prevent Duplicate Feedback
**Expected Result:** Cannot submit feedback twice for same session

1. Try to navigate to `/trainee/feedback` again
2. Verify the session you submitted feedback for is no longer shown
3. Try to access the feedback form directly (if possible)
4. Verify you cannot submit again

**Pass Criteria:**
- Session doesn't appear in feedback list after submission
- System prevents duplicate submissions

---

### Test 1.6: Insufficient Attendance Case
**Expected Result:** Sessions with < 80% attendance don't show in feedback list

1. Check if you have any sessions with attendance < 80%
2. Verify these sessions do NOT appear in the feedback page
3. If you try to download certificate for low-attendance session, verify appropriate message

**Pass Criteria:**
- Only sessions with >= 80% attendance are eligible
- Clear communication about attendance requirements

---

### Test 1.7: Access Restriction (Trainee Cannot View Results)
**Expected Result:** Trainee cannot access evaluation results page

1. Try to navigate to `/sessions/1/evaluation`
2. Verify you get a **403 Forbidden** or **Access Denied** message
3. Verify you cannot see evaluation results

**Pass Criteria:**
- Route is protected
- Clear error message displayed
- Trainee is redirected or shown error page

---

## Part 2: Trainer Testing

### Test 2.1: Access Feedback Page
**Expected Result:** Trainer can view feedback page

1. Logout from Trainee account
2. Login as Trainer
3. Navigate to **Feedback** page from sidebar
4. Verify you see the old feedback interface (with mock data)

**Note:** The Trainer feedback page hasn't been updated yet to use the new evaluation system.

**Pass Criteria:**
- Page loads successfully
- No errors in console

---

### Test 2.2: View Own Session Evaluations
**Expected Result:** Trainer can view evaluation results for their own sessions

1. Navigate to `/sessions/{session_id}/evaluation` where the session belongs to this trainer
   - **Example:** If Trainer ID = 2, find a session where `trainer_id = 2`
2. Verify the evaluation results page loads
3. Verify you see:
   - **Session Information:** Title, course name, trainer name
   - **Summary Stats:** Total responses, overall rating, average ratings
   - **Individual Feedback:** Each trainee's ratings and comments
   - **Average Ratings:** Progress bars for each category

**Pass Criteria:**
- Page loads without errors
- All statistics display correctly
- Individual feedback entries are shown
- Data matches submitted evaluations

---

### Test 2.3: Authorization Check (Cannot View Other Trainer's Sessions)
**Expected Result:** Trainer cannot view evaluation results for sessions they don't teach

1. Try to navigate to `/sessions/{other_session_id}/evaluation`
   - **Example:** Session where `trainer_id != 2`
2. Verify you get a **403 Forbidden** message
3. Verify authorization policy blocks access

**Pass Criteria:**
- Route is protected by policy
- Clear error message displayed
- Cannot access other trainer's data

---

## Part 3: Admin Testing

### Test 3.1: Access Admin Feedback Page
**Expected Result:** Admin can view feedback sessions list

1. Logout from Trainer account
2. Login as Admin
3. Navigate to **Feedback** tab from sidebar
4. Verify you see the **FeedbackSessions** page with:
   - Search bar
   - Status filter dropdown
   - List of completed sessions
   - Session cards showing:
     - Session title and status badge
     - Course name
     - Trainer name
     - Start date
     - Number of feedback submissions
     - Average rating

**Pass Criteria:**
- Page loads without errors
- All sessions are listed
- Data is accurate and complete
- Hover effects work on session cards

---

### Test 3.2: Search and Filter Functionality
**Expected Result:** Admin can search and filter sessions

1. Test **Search functionality:**
   - Type a session title in search box
   - Verify matching sessions are shown
   - Clear search and verify all sessions return

2. Test **Status filter:**
   - Select "Completed" from dropdown
   - Verify only completed sessions are shown
   - Select "All Status"
   - Verify all sessions return

**Pass Criteria:**
- Search works for session title, course name, and trainer name
- Filter works for status
- Results count updates correctly
- "Showing X of Y sessions" displays accurate numbers

---

### Test 3.3: View Evaluation Results for Any Session
**Expected Result:** Admin can view evaluation results for all sessions

1. Click on any session card from the feedback sessions list
2. Verify you're redirected to `/sessions/{session_id}/evaluation`
3. Verify the evaluation results page loads
4. Verify you see all evaluation data:
   - Session information
   - Summary statistics
   - Average ratings with progress bars
   - Individual feedback entries with comments

5. Test with multiple different sessions
6. Verify Admin can access evaluation results for sessions from any trainer

**Pass Criteria:**
- Admin can view results for all sessions (no authorization restrictions)
- Page displays all evaluation data correctly
- Navigation between sessions works smoothly
- No 403 errors

---

### Test 3.4: Empty State Handling
**Expected Result:** Proper messaging when no evaluations exist

1. Find a session with 0 evaluations (if available)
2. Click on the session
3. Verify the page shows:
   - "No evaluations submitted yet" message
   - 0 total responses
   - N/A or 0 for average ratings

**Pass Criteria:**
- Empty state is handled gracefully
- No errors when no data exists
- Clear messaging to admin

---

## Part 4: API Testing

### Test 4.1: GET Evaluation Results API
**Expected Result:** API returns correct JSON response

1. Open browser console or use Postman
2. Make GET request to: `/api/sessions/1/evaluation`
3. Verify response structure:

```json
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "title": "Session Title",
      "course_name": "Course Name",
      "trainer_name": "Trainer Name"
    },
    "evaluations": [
      {
        "id": 1,
        "trainee_name": "Trainee Name",
        "overall_rating": 5,
        "content_quality": 5,
        "trainer_quality": 5,
        "material_quality": 4,
        "organization": 5,
        "would_recommend": true,
        "difficulty_level": "appropriate",
        "strengths": "...",
        "improvements": "...",
        "comments": "...",
        "submitted_at": "January 13, 2026 14:30"
      }
    ],
    "averages": {
      "overall_rating": 4.5,
      "content_quality": 4.8,
      "trainer_quality": 4.7,
      "material_quality": 4.3,
      "organization": 4.6,
      "would_recommend_percentage": 85.5
    },
    "total_evaluations": 10
  }
}
```

**Pass Criteria:**
- API returns 200 status
- JSON structure matches expected format
- Calculations are correct

---

### Test 4.2: POST Evaluation Submission API
**Expected Result:** API accepts and stores evaluation

1. Make POST request to: `/sessions/1/evaluation` with body:

```json
{
  "overall_rating": 5,
  "content_quality": 5,
  "trainer_quality": 4,
  "material_quality": 5,
  "organization": 5,
  "would_recommend": true,
  "difficulty_level": "appropriate",
  "strengths": "Great hands-on practice",
  "improvements": "More examples",
  "comments": "Excellent course"
}
```

2. Verify response indicates success
3. Verify data is stored in database

**Pass Criteria:**
- API returns 200/201 status
- Evaluation is saved to database
- Validation works (try invalid data)

---

## Test Summary Checklist

### Trainee Role ✓
- [ ] View feedback page with eligible sessions
- [ ] Certificate download blocked without feedback
- [ ] Submit feedback form successfully
- [ ] Certificate download works after feedback
- [ ] Cannot submit duplicate feedback
- [ ] Low attendance sessions not eligible
- [ ] Cannot access evaluation results page

### Trainer Role ✓
- [ ] Access feedback page
- [ ] View own session evaluation results
- [ ] Cannot view other trainer's sessions

### Admin Role ✓
- [ ] Access feedback sessions list
- [ ] Search and filter sessions
- [ ] View evaluation results for any session
- [ ] Empty state handled correctly

### API Testing ✓
- [ ] GET evaluation results API works
- [ ] POST evaluation submission API works

---

## Known Issues / Notes

1. **Trainer Feedback Page:** Still using old mock data component. Need to update to use new FeedbackSessions pattern similar to Admin.

2. **Session Status:** Only "completed" sessions should appear in the feedback list. Verify session status is set correctly in database.

3. **Date Format:** Verify start_date displays correctly using sessionDays relation.

---

## Database Verification

To verify data in database, run these queries:

```sql
-- Check evaluations
SELECT * FROM evaluations WHERE session_id = 1;

-- Check certificates
SELECT * FROM certificates WHERE user_id = 1;

-- Check enrollments with attendance
SELECT * FROM enrollments WHERE user_id = 1;

-- Check session trainers
SELECT id, title, trainer_id, status FROM training_sessions;
```

---

## Test Results

Record your test results here:

| Test | Expected | Actual | Status | Notes |
|------|----------|--------|--------|-------|
| 1.1 | View eligible sessions | | ⬜ | |
| 1.2 | Blocked certificate download | | ⬜ | |
| 1.3 | Submit feedback | | ⬜ | |
| 1.4 | Download after feedback | | ⬜ | |
| 1.5 | Prevent duplicate | | ⬜ | |
| 1.6 | Insufficient attendance | | ⬜ | |
| 1.7 | Trainee access denied | | ⬜ | |
| 2.1 | Trainer feedback page | | ⬜ | |
| 2.2 | View own evaluations | | ⬜ | |
| 2.3 | Cannot view others | | ⬜ | |
| 3.1 | Admin sessions list | | ⬜ | |
| 3.2 | Search/filter | | ⬜ | |
| 3.3 | View all evaluations | | ⬜ | |
| 3.4 | Empty state | | ⬜ | |
| 4.1 | GET API | | ⬜ | |
| 4.2 | POST API | | ⬜ | |

---

**Legend:**
- ✅ Pass
- ❌ Fail
- ⬜ Not tested
- ⚠️ Partial pass / needs attention
