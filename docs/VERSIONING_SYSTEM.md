# Certification Versioning System

## Overview

The **Versioning System** provides complete change tracking and rollback capabilities for certifications. Every modification is recorded with full context, allowing administrators to audit changes, compare versions, and restore previous states if needed.

## Table of Contents

- [Core Concepts](#core-concepts)
- [Version Structure](#version-structure)
- [Creating Versions](#creating-versions)
- [Accessing Version History](#accessing-version-history)
- [Comparing Versions](#comparing-versions)
- [Reverting Changes](#reverting-changes)
- [Architecture](#architecture)
- [API Reference](#api-reference)
- [Best Practices](#best-practices)

---

## Core Concepts

### What is a Version?

A **version** is a immutable snapshot of a certification's state at a point in time.

```
Certification "Financial Literacy"
├── Version 1 (2024-01-15) - Initial creation
├── Version 2 (2024-02-01) - Updated questions
├── Version 3 (2024-03-10) - Changed pass score
├── Version 4 (2024-03-15) - Reverted to v2 (special version)
└── Version 5 (2024-04-07) - Current state
```

### Key Properties

Each version records:

```php
[
  'id' => 'uuid',
  'certification_id' => 'uuid',
  'version_number' => 5,
  'data' => [...], // Full certification state
  'metadata' => [...], // Context about the change
  'created_by_user_id' => 'uuid',
  'created_at' => 'timestamp'
]
```

### Immutability Guarantee

Once created, a version **cannot be modified or deleted**. This ensures:

✅ Audit trail integrity  
✅ Historical accuracy  
✅ Legal compliance  
✅ Dispute resolution capability

---

## Version Structure

### Complete Version Example

```json
{
  "id": "v5-cert-financial",
  "certification_id": "cert-123",
  "version_number": 5,
  
  "data": {
    "name": "Financial Literacy Q2 2024",
    "slug": "financial-literacy",
    "description": "Updated for Q2 with new topics",
    "active": true,
    "questions_required": 20,
    "pass_score_percentage": 75,
    "cooldown_days": 14,
    "result_mode": "score_breakdown",
    "pdf_view": true,
    "home_order": 2,
    "settings": {
      "theme": "modern",
      "show_explanations": true,
      "randomize_questions": true,
      "time_limit": 3600
    },
    "question_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20]
  },
  
  "metadata": {
    "reason": "Q2 quarterly update - expanded topics",
    "changes": [
      "name",
      "description",
      "questions_required",
      "settings"
    ],
    "sensitive_changes": [
      "questions_required",
      "pass_score_percentage"
    ],
    "active_attempts_at_time": 0,
    "previous_version": 4,
    "created_by": {
      "id": "user-42",
      "name": "Admin Name",
      "email": "admin@company.com"
    },
    "created_at": "2024-04-07T14:30:00Z",
    "ip_address": "192.168.1.100"
  }
}
```

### Metadata Fields

| Field | Purpose |
|-------|---------|
| `reason` | Human-readable explanation of changes |
| `changes` | Array of fields that changed |
| `sensitive_changes` | Fields that affect user progress |
| `active_attempts_at_time` | How many users were taking quiz |
| `previous_version` | Reference to prior version |
| `created_by` | Who made the change |
| `ip_address` | For security tracking |

### Sensitive Fields

These fields are marked as "sensitive" when changed:

```
🔴 CRITICAL (Affects Pass/Fail):
  - questions_required
  - pass_score_percentage
  - cooldown_days

🟡 WARNING (Affects Experience):
  - result_mode
  - settings

🟢 SAFE (Non-Breaking):
  - name
  - description
  - pdf_view
```

---

## Creating Versions

### Automatic Version Creation

A new version is created automatically when certification is saved:

```php
// In CertificationAdminController@update()

public function update(UpdateCertificationRequest $request, Certification $certification)
{
    // Validation passes
    
    // Update the model
    $certification->update($request->validated());
    
    // Version is created automatically via event:
    // CertificationUpdatedEvent → CertificationVersioning::handle()
    
    return redirect()->route('admin.certifications.edit', $certification)
        ->with('success', 'Certification updated successfully');
}
```

### Manual Version Creation with Metadata

```php
// If you need to create version with specific metadata:

CertificationVersioning::create(
    $certification,
    $request->validated(),
    [
        'reason' => 'Migrated from old system',
        'is_migration' => true,
    ]
);
```

### Version Number Increment

```
Save 1 → Version 1
Save 2 → Version 2 (auto-increment)
Save 3 → Version 3
Save 4 → Version 4

Even if you revert to Version 2, the next save is Version 5
(numbers never go backwards)
```

### Handling Concurrent Updates

```php
// If two admins save simultaneously:

Timeline:
09:00:00 - Admin A starts editing
09:00:01 - Admin B starts editing
09:00:30 - Admin A saves → Version 5 created
09:00:35 - Admin B attempts save → 409 Conflict

Admin B receives:
{
  "error": "Certification was modified",
  "current_version": 5,
  "your_changes_version": 4,
  "action": "The certification has been updated since you started."
}
```

---

## Accessing Version History

### Via Admin Interface

```
URL: /admin/certifications/{id}/edit
Button: [📜 Version History]

Shows Timeline:
v5  | 2024-04-07 | By Admin User
    | "Q2 update with new questions"
    | [View Changes] [Revert]
    
v4  | 2024-03-15 | By Admin User
    | "Fixed pass score calculation"
    | [View Changes] [Revert]
    
v3  | 2024-03-10 | By Admin User
    | "Changed result mode"
    | [View Changes] [Revert]
    
v2  | 2024-02-01 | By Admin User
    | "Initial questions assignment"
    | [View Changes] [Revert]
    
v1  | 2024-01-15 | By Admin User
    | "Created certification"
    | [View Changes] [Revert - Disabled]
```

### Via API

```bash
# Get all versions for a certification
curl /admin/api/certifications/{id}/versions

# Response:
{
  "versions": [
    {
      "version": 5,
      "created_at": "2024-04-07T14:30:00Z",
      "author": "Admin Name",
      "reason": "Q2 update"
    },
    ...
  ],
  "current_version": 5,
  "total_versions": 5
}
```

---

## Comparing Versions

### Visual Diff in UI

```
[View Changes] button shows modal with before/after:

name:
  Before: "Financial Literacy"
  After:  "Financial Literacy Q2 2024"
  
  Status: ✓ Safe change

questions_required:
  Before: 15
  After:  20
  
  Status: ⚠ Sensitive - affects user progress

settings.time_limit:
  Before: 7200
  After:  3600
  
  Status: ⚠ Sensitive - reduces time
```

### API Comparison

```bash
curl /admin/api/certifications/{id}/versions/{versionId}/compare

# Response:
{
  "from_version": 3,
  "to_version": 5,
  "changes": [
    {
      "field": "name",
      "before": "Financial Literacy",
      "after": "Financial Literacy Q2 2024",
      "is_sensitive": false,
      "impact": null
    },
    {
      "field": "questions_required",
      "before": 15,
      "after": 20,
      "is_sensitive": true,
      "impact": "Users need to answer 5 more questions"
    }
  ]
}
```

### Change Grouping

Changes are grouped by type:

```
🔄 Modified Fields:
  - name
  - description
  
🆕 Added:
  - pdf_view (was false, now true)
  
🗑️  Removed:
  - old_setting (was deleted)
  
➕ Array Changes:
  - Added questions: [15, 20, 25]
  - Removed questions: [5]
```

---

## Reverting Changes

### One-Step Revert

```
Current State (v5):
  - name: "Financial Literacy Q2 2024"
  - questions_required: 20
  
User clicks: [Revert to v3]
  ↓
Modal shows changes that would be reverted
  ↓
User confirms
  ↓
New Version 6 created with v3's data
  → name: "Financial Literacy"
  → questions_required: 15
```

### Revert API Call

```bash
POST /admin/certifications/{id}/versions/{versionId}/restore

Response:
{
  "success": true,
  "message": "Reverted to version 3",
  "new_version": 6,
  "created_at": "2024-04-07T15:00:00Z"
}
```

### Revert Limitations

```
Can Revert:
✓ Any previous version (v1-v4 when on v5)
✓ Even if there are active attempts
✓ Gets recorded as version 6

Cannot Revert:
✗ Current version (already there)
✗ Future versions (don't exist)
```

### Revert with Active Attempts

```
If certification has active attempts:

Scenario: 8 users taking v5 quiz
Admin reverts to v3 (different questions)

Result:
1. Version 6 is created with v3's data
2. Active attempts continue with v5 questions
3. Users who restart get v6 questions
4. Audit log shows: "Reverted while 8 attempts active"

⚠️ Users mid-quiz are NOT interrupted
```

---

## Architecture

### Version Storage

```
Database Tables:
├── certifications
│   ├── id, name, slug, active, ...
│   └── current_version_number (reference only)
│
├── certification_versions
│   ├── id (uuid)
│   ├── certification_id (fk)
│   ├── version_number (int)
│   ├── data (json)
│   ├── metadata (json)
│   ├── created_by_user_id
│   ├── created_at
│   └── indexes: cert_id + version_number (unique)
│
└── Immutable: No UPDATE or DELETE operations
```

### Event Flow

```
User saves certification
        ↓
CertificationAdminController@update()
        ↓
Certification model updated
        ↓
CertificationUpdatedEvent fired
        ↓
CertificationVersioning listener triggered
        ↓
1. Determine which fields changed
2. Mark sensitive changes
3. Create CertificationVersion record
4. Redirect back to edit page
```

### Data Flow for Reverting

```
User clicks [Revert to v3]
        ↓
GET /admin/api/certifications/{id}/versions/{vId}/compare
        ↓
Show modal with differences
        ↓
User confirms
        ↓
POST /admin/certifications/{id}/versions/{vId}/restore
        ↓
1. Load v3 data from database
2. Update Certification model with v3 data
3. CertificationUpdatedEvent fires
4. New version created (v6) with revert metadata
5. Redirect to edit page
```

---

## API Reference

### List All Versions

```
GET /admin/api/certifications/{id}/versions

Query Parameters:
  ?limit=10 (default: 50)
  ?offset=0
  ?sort=created_at (asc|desc)

Response:
{
  "versions": [...],
  "pagination": {
    "total": 42,
    "limit": 10,
    "offset": 0,
    "next": "?limit=10&offset=10"
  },
  "current_version": 5
}
```

### Get Single Version

```
GET /admin/api/certifications/{id}/versions/{versionId}

Response:
{
  "version": {
    "number": 3,
    "created_at": "2024-03-10T10:00:00Z",
    "created_by": "Admin Name",
    "data": {...},
    "metadata": {...}
  }
}
```

### Compare Two Versions

```
GET /admin/api/certifications/{id}/versions/{v1}/compare?to={v2}

Or specify in body:
POST /admin/api/certifications/{id}/versions/compare
{
  "from_version": 3,
  "to_version": 5
}

Response:
{
  "from_version": 3,
  "to_version": 5,
  "changes": [...]
}
```

### Restore Version

```
POST /admin/certifications/{id}/versions/{versionId}/restore

Request Body:
{
  "confirm": true,
  "reason": "Revert Q1 changes" (optional)
}

Response:
{
  "success": true,
  "new_version_number": 6,
  "created_at": "2024-04-07T15:00:00Z",
  "redirect": "/admin/certifications/{id}/edit"
}
```

---

## Best Practices

### 1. Clear Change Reasons

```
✓ GOOD:
  "Q2 quarterly update - updated questions and pass score"
  "Fixed typo in description"
  "Increased difficulty as per feedback"

✗ BAD:
  "update"
  "changes"
  "test"
  (empty reason)
```

### 2. Group Related Changes

```
✓ GOOD:
  Save 1: Update questions_required + questions
  Save 2: Update pass_score_percentage
  Save 3: Update result_mode
  
  Each version has clear purpose

✗ BAD:
  Save 1: Change everything at once
  
  Single version mixes name, questions,
  scoring, and settings
```

### 3. Monitor Sensitive Changes

```
When changing sensitive fields:

1. Check active attempts: 
   → /admin/api/certifications/{id}/active-attempts

2. Plan the change window:
   → Weekend? Off-hours?

3. Stop assignments temporarily:
   → Set active: false before critical changes

4. Monitor the change:
   → Check audit logs during/after
```

### 4. Version Cleanup

Versions are immutable and never deleted. To manage storage:

```
Strategy: Archive old certifications periodically

For example:
- Keep live versions: Unlimited
- Archive at: 100+ versions per cert
- Archive process: Export to external backup

Note: Archiving doesn't delete data, just exports it
```

### 5. Revert Decision Workflow

```
Before reverting to previous version:

1. Load version details
   → What changed since then?

2. Check active users
   → How many are taking this cert?

3. Preview the changes
   → Will this break anything?

4. Document the reason
   → Why are we reverting?

5. Confirm and proceed
   → Changes are recorded permanently
```

---

## Audit Trail

### What Gets Recorded

Every version includes audit information:

```json
{
  "created_by": {
    "id": "user-42",
    "name": "Admin Name",
    "email": "admin@company.com"
  },
  "created_at": "2024-04-07T14:30:00Z",
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "changes": ["name", "questions_required"],
  "sensitive_changes": ["questions_required"]
}
```

### Querying Audit Trail

```php
// Get all changes by a specific user
$versions = CertificationVersion::where('created_by_user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

// Get all sensitive changes to a certification
$versions = CertificationVersion::where('certification_id', $certId)
    ->where('metadata->sensitive_changes', '!=', [])
    ->get();

// Get versions in a date range
$versions = CertificationVersion::whereBetween('created_at', [
    '2024-04-01', '2024-04-30'
])->get();
```

---

## Edge Cases

### Edge Case 1: Revert Chain

```
Timeline:
v1 (original)
v2 (add questions)
v3 (change pass score)
v4 (revert to v1) ← New version containing v1's data
v5 (add new questions)

Result:
- v4 and v1 have identical data
- Both are separate versions with timestamps
- v4 shows "reverted from v3" in metadata
```

### Edge Case 2: Concurrent Reverts

```
Two admins both trying to revert:

Timeline:
09:00 - Admin A clicks [Revert to v2]
09:01 - Admin B clicks [Revert to v3]
09:02 - Admin A's revert succeeds → v6 created
09:03 - Admin B's revert succeeds → v7 created

Result:
- v6 has v2's data (from A's revert)
- v7 has v3's data (from B's revert)
- Latest version is v7
```

### Edge Case 3: Cyclic Reverts

```
Admin reverts from v5 → v3 (creating v6)
Then reverts from v6 → v5 (creating v7)

Result:
v7 is identical to v5
Both v5 and v7 exist in history
Not a problem - versions are immutable records
```

---

## Performance Considerations

### Version Retrieval Time

```
Fetching 100 versions: ~5ms
Comparing 2 versions: ~15ms
Creating new version: ~50ms (includes disk write)

With proper indexing on:
- certification_id
- version_number
- created_at
```

### Storage Usage Per Version

```
Average certification version size:
- Basic data: 1-5 KB (JSON)
- Metadata: 0.5-1 KB
- Total per version: ~2-6 KB

For 1000 certifications with 50 versions each:
- 50,000 versions × 4 KB = ~200 MB
```

---

## Migration Guide

### Moving Between Systems

If migrating certifications from another system:

```php
// Import certification with versions
$cert = Certification::create([...]);

// Create version 1 to mark initial import
CertificationVersion::create([
    'certification_id' => $cert->id,
    'version_number' => 1,
    'data' => [...],
    'metadata' => [
        'reason' => 'Imported from legacy system',
        'is_migration' => true,
    ],
    'created_by_user_id' => auth()->id(),
]);

// Now any future edits create versions 2, 3, 4...
```

---

## Summary

The Versioning System provides:

✅ **Complete audit trail** of all changes  
✅ **One-click rollback** to any previous version  
✅ **Immutable history** for compliance  
✅ **Change tracking** with context  
✅ **Sensitive field flagging** for risky changes  
✅ **Version comparison** before reverting  
✅ **Concurrent edit detection** preventing conflicts  

Every certification change is permanent, traceable, and reversible if needed.
