# Troubleshooting Guide - Certification Editor

This guide covers common issues and their solutions when using the certification editor and management system.

## Table of Contents

- [Common Issues](#common-issues)
- [Testing Issues](#testing-issues)
- [Form & Validation Issues](#form--validation-issues)
- [Save & Update Issues](#save--update-issues)
- [Version & History Issues](#version--history-issues)
- [Performance Issues](#performance-issues)
- [API Issues](#api-issues)
- [Database Issues](#database-issues)
- [Getting Help](#getting-help)

---

## Testing Issues

### Issue: `Unknown option --no-header`

**Symptom:** PHPUnit 11 reports unknown option when running `php artisan test --no-header`.

**Cause:** `--no-header` is not available in this PHPUnit version.

**Solution:**

```bash
php artisan test
php artisan test tests/Unit
php artisan test tests/Feature
```

### Issue: Coverage driver not available

**Symptom:** `Code coverage driver not available. Did you install Xdebug or PCOV?`

**Cause:** No coverage engine enabled in CLI.

**Solution options:**

```bash
# Option 1: phpdbg
phpdbg -qrr artisan test --coverage

# Option 2: Xdebug
XDEBUG_MODE=coverage php artisan test --coverage

# Option 3: PCOV
php -d pcov.enabled=1 artisan test --coverage
```

### Issue: SQLite says "no such table"

**Symptom:** During tests, model factories fail with missing table errors.

**Cause:** The test class is hitting DB without reset/migration lifecycle.

**Solution:**

1. Add `RefreshDatabase` to the test class.
2. Re-run the failing test file only.

```bash
php artisan test tests/Feature/SomeFailingTest.php
```

---

## Common Issues

### Issue 1: Unsaved Indicator Won't Go Away

**Symptom:** The asterisk (*) in the title persists even after saving

**Possible Causes:**
1. Browser cache not cleared
2. Form state not properly reset
3. JavaScript error preventing reset

**Solution:**

```bash
# Option 1: Hard refresh page
Ctrl+Shift+R (or Cmd+Shift+R on Mac)

# Option 2: Clear browser cache
Dev Tools → Application → Cache Storage → Clear All

# Option 3: Check browser console for errors
F12 → Console → Look for red errors
```

**If the issue persists:**

```javascript
// In browser console, check the form state:
document.querySelector('form').reset();
window.location.reload();
```

---

### Issue 2: Cannot Save Changes - "Fix Errors" Button Disabled

**Symptom:** The Save button is disabled and says "Fix errors to save"

**Solution:**

1. **Check for validation errors in the form:**
   - Red error messages under fields
   - Look for JSON syntax errors in settings field

2. **Common validation failures:**

   ```
   ❌ name field empty
      → Type a certification name
   
   ❌ questions_required > 50
      → Must be between 1-50
   
   ❌ pass_score_percentage not 0-100
      → Enter a percentage between 0 and 100
   
   ❌ settings field has invalid JSON
      → Check syntax: missing commas, quotes
   ```

3. **To debug validation:**

   ```javascript
   // In browser console
   // Check what validation is failing
   const form = document.querySelector('form');
   console.log(form.checkValidity()); // true/false
   
   // List invalid fields
   Array.from(form.elements).forEach(el => {
     if (!el.checkValidity()) {
       console.log(`Invalid: ${el.name}`, el.validationMessage);
     }
   });
   ```

---

### Issue 3: Form Fields Show "Warning" Instead of Error

**Symptom:** Yellow warning badge appears, but save button isn't disabled

**This is normal!** Warnings allow submission. Common warnings:

```
⚠ Pass score > 90%
   → Message: "Certification is very strict"
   → Action: Review if intentional

⚠ questions_required > available questions
   → Message: "Fewer questions than required"
   → Action: Add more questions or reduce requirement

⚠ No questions assigned
   → Message: "No questions selected"
   → Action: Select questions from right panel
```

**To fix warnings (optional):**

1. Adjust the problematic field
2. Watch the warning badge change
3. Save when ready

---

## Form & Validation Issues

### Issue 4: Validation Rules Not Showing

**Symptom:** Form fields don't show "✓" or "✗" feedback

**Cause:** JavaScript component not loaded

**Solution:**

1. **Check if JavaScript includes are enabled:**
   ```bash
   # View source (Ctrl+U)
   # Look for: <script src="/js/..."></script>
   ```

2. **Clear browser cache:**
   ```bash
   # Hard refresh
   Ctrl+Shift+R
   
   # Or delete site data
   Dev Tools → Application → Cookies/Storage → Delete
   ```

3. **Check browser console for errors:**
   ```
   F12 → Console → Look for red error messages
   ```

---

### Issue 5: JSON Settings Validation Fails

**Symptom:** "Invalid JSON" error shown for settings field

**Common JSON Syntax Errors:**

```json
// ❌ WRONG: Missing quotes
{
  theme: modern,
  show_explanations: true
}

// ✓ CORRECT: Proper quotes
{
  "theme": "modern",
  "show_explanations": true
}
```

**Debugging JSON:**

```javascript
// Paste this in browser console
const json = document.getElementById('settings').value;
try {
  JSON.parse(json);
  console.log("✓ Valid JSON");
} catch(e) {
  console.log("✗ Invalid JSON:", e.message);
}
```

**Online Tool:** Use [jsonlint.com](https://jsonlint.com) to validate

---

### Issue 6: Questions Panel Not Showing Count

**Symptom:** "Active Questions" stat shows 0 even with questions selected

**Cause:** JavaScript not initialized

**Solution:**

1. **Reload page:**
   ```
   F5 or Cmd+R
   ```

2. **Check if questions are actually assigned:**
   ```
   Dev Tools → Network → Look for /admin/api/certifications/{id}/available-questions
   Response should show assigned questions
   ```

3. **Manually update questions:**
   - Click checkboxes in questions list
   - Watch the count update
   - If still not updating, refresh page

---

## Save & Update Issues

### Issue 7: Getting "409 Conflict" Error

**Symptom:** Save fails with message "Cannot modify settings with active attempts"

**What This Means:** Users are currently taking the certification

**Reason:** Changing critical settings while users are mid-quiz:
- Affects which questions they see
- Could invalidate their progress
- System prevents this by default

**Solutions:**

**Option A: Wait for attempts to finish**
```
1. Check number of active attempts
   → Button shows: "5 users taking this quiz"

2. Wait for them to complete

3. Try saving again after they finish
```

**Option B: Force update (not recommended)**
```
1. Acknowledge the warning
2. Click "Force Update" button
3. Active users will see updated version
4. Audit log records this action
```

**Option C: Use different timing**
```
Best practice: Schedule changes for off-hours
- Schedule updates at night
- Or on weekends
- Stop new attempts temporarily: Set active: false
```

---

### Issue 8: Redirect Loop After Save

**Symptom:** Save button clicked, but page keeps redirecting

**Cause:** Server-side validation or permission issue

**Solution:**

1. **Check browser console:**
   ```
   F12 → Network tab
   Look for 422 or 500 responses
   ```

2. **Check server logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify permissions:**
   ```
   User must have:
   - admin role
   - certification:edit permission
   ```

4. **Check data integrity:**
   ```bash
   # Verify certification exists
   php artisan tinker
   > Certification::find($id)
   ```

---

### Issue 9: "Cannot Access" After Saving

**Symptom:** Error 403 Forbidden after saving certification

**Cause:** Permission was removed or revoked

**Solution:**

1. **Check user role:**
   ```bash
   php artisan tinker
   > $user->hasRole('admin')
   ```

2. **Verify permission exists:**
   ```bash
   > $user->can('edit', Certification::find($id))
   ```

3. **Re-assign role if needed:**
   ```bash
   > $user->assignRole('admin')
   ```

---

## Version & History Issues

### Issue 10: Version History Shows Blank Modal

**Symptom:** "Version History" button clicked, but nothing appears

**Cause:** API endpoint failing

**Solution:**

1. **Check API endpoint:**
   ```bash
   curl http://localhost/admin/api/certifications/{id}/versions
   
   Should return JSON with version list
   ```

2. **Check permissions:**
   ```
   API requires auth + admin role
   ```

3. **Verify database has versions:**
   ```bash
   php artisan tinker
   > CertificationVersion::where('certification_id', $id)->get()
   ```

---

### Issue 11: Cannot Revert to Previous Version

**Symptom:** "Revert" button is disabled or shows error

**Cause:** Multiple possible reasons:

| Cause | Solution |
|-------|----------|
| Reverting to current version | Can't revert to same version |
| Active attempts blocking revert | Wait for attempts to finish |
| Permission denied | Check user has admin role |
| Version doesn't exist | Refresh page or reload data |

**To force revert via API:**

```bash
POST /admin/certifications/{id}/versions/{versionId}/restore
Header: Accept: application/json
Header: X-Requested-With: XMLHttpRequest

Response should show:
{
  "success": true,
  "new_version_number": 6
}
```

---

### Issue 12: Version Comparison Won't Load

**Symptom:** "View Changes" button shows loading spinner forever

**Cause:** API call stuck or timeout

**Solution:**

1. **Check network:**
   ```
   Dev Tools → Network tab
   Look for /admin/api/certifications/.../compare
   Should complete in < 1 second
   ```

2. **Try with smaller set:**
   ```
   Compare v4 to v5 (fewer changes)
   vs
   Compare v1 to current
   ```

3. **Restart if stuck:**
   ```
   Escape or close modal
   Refresh page completely
   Try again
   ```

---

## Performance Issues

### Issue 13: Page Loads Slowly

**Symptom:** Edit page takes 5+ seconds to load

**Possible Causes:**

```
1. Too many questions (1000+)
   → Paginate questions list
   
2. Large JSON settings
   → Validates on load
   
3. Database query slow
   → Check certification relationship loads
   
4. Network latency
   → Check network tab timing
```

**Solution:**

1. **Profile the load time:**
   ```bash
   # In browser console
   window.time_start = performance.now();
   
   # Wait for page to load
   
   window.time_end = performance.now();
   console.log(time_end - time_start); // ms
   ```

2. **Check specific endpoints:**
   ```
   Dev Tools → Network → Slow request?
   See which endpoint takes longest
   ```

3. **Optimize database:**
   ```bash
   # Run migrations
   php artisan migrate
   
   # Check indexes
   php artisan tinker
   > Schema::getIndexes('certifications')
   ```

---

### Issue 14: Form Input Lag

**Symptom:** Typing in fields is slow/laggy

**Cause:** Validation running on every keystroke

**Solution:**

```javascript
// In browser console, disable validation temporarily
document.getElementById('name').removeEventListener('input', validateField);

// Or increase debounce time in code
// (Contact dev team to adjust)
```

---

## API Issues

### Issue 15: API Endpoints Return 401 Unauthorized

**Symptom:** API calls fail with 401 status

**Cause:** Not authenticated for API

**Solution:**

```bash
# Verify authentication
curl -H "Cookie: XSRF-TOKEN=..." \
     /admin/api/certifications/{id}/versions

# Or use auth token
curl -H "Authorization: Bearer {token}" \
     /admin/api/certifications/{id}/versions
```

---

### Issue 16: API Returns 404 Not Found

**Symptom:** `/admin/api/certifications/{id}/...` returns 404

**Cause:** Certification doesn't exist or route not defined

**Solution:**

1. **Verify certification exists:**
   ```bash
   php artisan tinker
   > Certification::find($id) // Should not be null
   ```

2. **Check routes are registered:**
   ```bash
   php artisan route:list | grep api/certifications
   ```

3. **Verify route in web.php:**
   ```php
   Route::prefix('admin/api')->group(function () {
       Route::get('certifications/{id}/versions', [...]);
   });
   ```

---

### Issue 17: API Response Missing Data

**Symptom:** API returns empty array for questions

**Cause:** No questions assigned or query error

**Solution:**

1. **Check questions exist:**
   ```bash
   php artisan tinker
   > Certification::find($id)->questions()->count()
   // Should be > 0
   ```

2. **Check questions are active:**
   ```bash
   > Certification::find($id)->questions()->where('active', true)->count()
   // Should match expected count
   ```

---

## Database Issues

### Issue 18: Migration Fails During Deployment

**Symptom:** Database migration error when deploying

**Common Migration Errors:**

```
❌ "Column doesn't exist"
   → Check previous migrations ran
   
❌ "Table already exists"
   → Already migrated
   
❌ "Foreign key constraint fails"
   → Referenced table missing
```

**Solution:**

```bash
# Check migration status
php artisan migrate:status

# See which migrations haven't run
php artisan migrate:reset --seed

# Or rollback and re-migrate
php artisan migrate:rollback --step=1
php artisan migrate
```

---

### Issue 19: Versions Table Is Too Large

**Symptom:** Database storage grows very quickly

**Cause:** Each save creates new version

**Solution:**

```bash
# Check table size
php artisan tinker
> DB::select("SELECT COUNT(*) FROM certification_versions");
// Returns number of versions

# Archive old versions (not delete)
php artisan certifications:archive-versions --before="2024-01-01"

# Or manually
> CertificationVersion::where('created_at', '<', '2024-01-01')->export('backup.json');
```

---

## Getting Help

### How to Report an Issue

When reporting a problem:

1. **Provide details:**
   ```
   - What were you doing?
   - What happened?
   - What should have happened?
   - Any error messages?
   - Browser/OS version?
   ```

2. **Include screenshots:**
   - Error message
   - Form state
   - Network response

3. **Check logs:**
   ```bash
   tail -n 100 storage/logs/laravel.log
   # Paste relevant entries
   ```

4. **Gather debug info:**
   ```bash
   # Browser console (F12):
   - Copy all red errors
   - Copy network responses (422, 500, etc)
   - Run: window.location.pathname
   ```

---

### Debug Mode

Enable debug logging:

```bash
# .env
APP_DEBUG=true
LOG_LEVEL=debug

# Restart application
php artisan cache:clear
php artisan config:cache
```

Then check `storage/logs/laravel.log` for detailed errors.

---

### Asking for Help

**Tell us:**

```
Title: [ISSUE] Certification editor won't save

Description:
- I was editing certification "Financial Literacy"
- Clicked Save button
- Got error: "409 Conflict"
- 3 users are taking the quiz

Expected: Should allow save with warning
Actual: Save blocked completely

Steps to reproduce:
1. Go to certifications edit page
2. Change pass_score_percentage
3. Click Save
4. See 409 error

Attachment: screenshot.png
```

---

## Checklist Before Asking For Help

- [ ] Cleared browser cache (Ctrl+Shift+R)
- [ ] Checked browser console (F12) for errors
- [ ] Verified user is logged in with admin role
- [ ] Confirmed certification exists in database
- [ ] Checked server logs (storage/logs/laravel.log)
- [ ] Tried on different browser
- [ ] Restarted application server
- [ ] Ran migrations (php artisan migrate)

---

## FAQ

### Q: Can I have multiple admins editing the same certification?

A: Not simultaneously. If two admins try:
- First one succeeds and creates new version
- Second one gets 409 Conflict error
- Second must reload page and redo changes

**Workaround:** Establish editing schedule among admins

---

### Q: Will reverting a version affect active quiz attempts?

A: No. Active users continue with their version:
- Users mid-quiz see original questions (v5)
- After revert to v3, new attempts get v3 questions
- Old v5 attempts continue unaffected

---

### Q: How long are versions kept?

A: Indefinitely. They are immutable records for audit.

**Storage management:** Archive to external storage if needed

---

### Q: What happens if JSON settings are corrupted?

A: Validation will prevent save:
- Error message shows: "Invalid JSON syntax"
- Fix the JSON and try again
- Use jsonlint.com to validate

---

### Q: Can I delete a version?

A: No. Versions are immutable for compliance.

**Alternative:** Archive old certifications instead

---

## Related Documentation

- [VISUAL_BUILDER_GUIDE.md](./VISUAL_BUILDER_GUIDE.md) - Complete interface guide
- [VERSIONING_SYSTEM.md](./VERSIONING_SYSTEM.md) - Version control system
- [COMPLETION_CHECKLIST.md](./COMPLETION_CHECKLIST.md) - Feature checklist

---

## Still Need Help?

Check the logs, ask your team, or create an issue with:
- Exact error message
- Steps to reproduce
- Screenshots of the error
- Server logs (storage/logs/laravel.log)
