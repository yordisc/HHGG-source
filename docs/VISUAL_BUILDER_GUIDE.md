# Visual Builder Guide - Certification Editor

## Overview

La **interfaz visual de edición de certificaciones** es un sistema completo de gestión que permite a administradores modificar, versionar y validar certificaciones sin escribir código. Este documento describe todas las características, componentes y funcionalidades disponibles.

## Table of Contents

- [Getting Started](#getting-started)
- [Interface Components](#interface-components)
- [Editing Workflow](#editing-workflow)
- [Real-time Validation](#real-time-validation)
- [Version Control](#version-control)
- [Change Preview](#change-preview)
- [API Integration](#api-integration)
- [Best Practices](#best-practices)
- [Keyboard Shortcuts](#keyboard-shortcuts)

---

## Getting Started

### Accessing the Editor

```
URL: /admin/certifications/{id}/edit
Method: GET
Authentication: Admin role required
Response: Blade template with full editor interface
```

### Required Permissions

```php
// User must have:
- admin role
- certification:edit permission
```

### Page Structure

```
┌─────────────────────────────────────────────────────┐
│        Certification Editor - [Name] (*unsaved)     │
├─────────────────────────────────────────────────────┤
│ [Save] [Discard] [View Changes] [History]           │
├──────────────────────┬──────────────────────────────┤
│                      │                              │
│   Left Column        │   Right Column               │
│   (2/3 width)        │   (1/3 width)                │
│                      │                              │
│  Edit Form           │  Questions Panel             │
│  - Name              │  - Active questions (n)      │
│  - Description       │  - Required questions       │
│  - Pass Score %      │  - Validation status        │
│  - Cooldown Days     │  - Question selector        │
│  - Result Mode       │                             │
│  - Settings JSON     │                             │
│  - Live Validation   │                             │
│                      │                             │
└──────────────────────┴──────────────────────────────┘
```

---

## Interface Components

### 1. Header Section

**Purpose:** Quick actions and status display

```html
<header class="bg-white border-b border-gray-200 p-4">
  <div class="flex justify-between items-center">
    <!-- Left: Title with unsaved indicator -->
    <h1>
      Certification Editor
      <span class="text-gray-500">{{ certification.name }}</span>
      <span class="unsaved-indicator">*</span> <!-- Shows when changes exist -->
    </h1>
    
    <!-- Right: Action buttons -->
    <div class="space-x-2">
      <button>Save Changes</button>
      <button>Discard Changes</button>
      <button>View Changes</button>
      <button>Version History</button>
    </div>
  </div>
</header>
```

**Components:**
- **Unsaved Indicator:** Asterisk (*) appears in title when form has unsaved changes
- **Save Button:** Submits form with validation
- **Discard Button:** Resets form to last saved state
- **View Changes Button:** Opens modal showing diff of all changes
- **History Button:** Displays version history timeline

### 2. Left Column: Edit Form (2/3 Width)

#### 2.1 Basic Information

```html
<fieldset>
  <legend>Basic Information</legend>
  
  <div class="form-group">
    <label for="name">Certification Name *</label>
    <input 
      type="text"
      id="name"
      name="name"
      required
      maxlength="255"
      placeholder="e.g., Financial Literacy 2024"
      @input="validateField('name')"
    />
    <error-message v-if="errors.name">{{ errors.name }}</error-message>
  </div>
  
  <div class="form-group">
    <label for="slug">Slug (Auto-generated)</label>
    <input 
      type="text"
      id="slug"
      name="slug"
      disabled
      readonly
      value="financial-literacy-2024"
    />
    <hint>Generated from: name-year-variant</hint>
  </div>
  
  <div class="form-group">
    <label for="description">Description</label>
    <textarea 
      id="description"
      name="description"
      rows="4"
      placeholder="Brief description of the certification..."
    ></textarea>
  </div>
</fieldset>
```

**Validation Rules:**
- **name:** Required, 1-255 characters, unique per year
- **slug:** Auto-generated from name, immutable after creation
- **description:** Optional, max 1000 characters

#### 2.2 Scoring Configuration

```html
<fieldset>
  <legend>Scoring & Requirements</legend>
  
  <div class="form-group">
    <label for="questions_required">Questions Required *</label>
    <input 
      type="number"
      id="questions_required"
      name="questions_required"
      required
      min="1"
      max="50"
      @input="validateField('questions_required')"
    />
    <hint>Total questions needed to pass certification</hint>
    <warning v-if="warnings.questions_required">
      {{ warnings.questions_required }}
    </warning>
  </div>
  
  <div class="form-group">
    <label for="pass_score_percentage">Pass Score % *</label>
    <input 
      type="number"
      id="pass_score_percentage"
      name="pass_score_percentage"
      required
      min="0"
      max="100"
      step="1"
      @input="validateField('pass_score_percentage')"
    />
    <hint>Minimum percentage needed to pass</hint>
    <error-message v-if="errors.pass_score_percentage">
      {{ errors.pass_score_percentage }}
    </error-message>
  </div>
  
  <div class="form-group">
    <label for="cooldown_days">Cooldown Days *</label>
    <input 
      type="number"
      id="cooldown_days"
      name="cooldown_days"
      required
      min="0"
      max="365"
      @input="validateField('cooldown_days')"
    />
    <hint>Days user must wait before retaking</hint>
  </div>
</fieldset>
```

**Validation Rules:**
- **questions_required:** 1-50, must not exceed available questions
- **pass_score_percentage:** 0-100, integer only
- **cooldown_days:** 0-365, 0 = no cooldown

**Live Validation Feedback:**
```
✅ Pass (70% shown as passing score)
⚠️  Warning (Above 90% pass rate - may be too strict)
❌ Error (Below 0% or above 100%)
```

#### 2.3 Result Mode

```html
<fieldset>
  <legend>Result Display</legend>
  
  <div class="form-group">
    <label for="result_mode">How Results are Displayed</label>
    <select id="result_mode" name="result_mode">
      <option value="binary_threshold">
        Binary (Pass/Fail only)
      </option>
      <option value="score_breakdown">
        Score with breakdown by category
      </option>
      <option value="detailed_report">
        Detailed report with analysis
      </option>
      <option value="certification_badge">
        Certification badge only
      </option>
    </select>
  </div>
  
  <div class="form-group">
    <label for="pdf_view">Enable PDF Export</label>
    <input type="checkbox" id="pdf_view" name="pdf_view" />
    <hint>Allow users to download certificate as PDF</hint>
  </div>
</fieldset>
```

**Available Modes:**
| Mode | Display | Use Case |
|------|---------|----------|
| `binary_threshold` | Simple Pass/Fail | Quick assessments |
| `score_breakdown` | Score + categories | Detailed feedback |
| `detailed_report` | Full analysis | Professional certs |
| `certification_badge` | Badge only | Digital badges |

#### 2.4 Advanced Settings (JSON)

```html
<fieldset>
  <legend>Advanced Settings</legend>
  
  <div class="form-group">
    <label for="settings">Configuration (JSON)</label>
    <textarea 
      id="settings"
      name="settings"
      rows="8"
      class="font-mono"
      @input="validateField('settings')"
      placeholder="{
  &quot;theme&quot;: &quot;modern&quot;,
  &quot;show_explanations&quot;: true,
  &quot;randomize_questions&quot;: false
}"
    ></textarea>
    <error-message v-if="errors.settings">
      {{ errors.settings }}
    </error-message>
  </div>
  
  <div v-if="settings_valid" class="settings-preview">
    <h4>Parsed Settings:</h4>
    <ul>
      <li v-for="(value, key) in parsedSettings" :key="key">
        <code>{{ key }}: {{ value }}</code>
      </li>
    </ul>
  </div>
</fieldset>
```

**Supported Settings:**
```json
{
  "theme": "modern|classic|minimal",
  "show_explanations": true|false,
  "randomize_questions": true|false,
  "time_limit": 3600,
  "allow_review": true|false,
  "shuffle_options": true|false,
  "require_all_questions": true|false
}
```

**Validation:**
- Must be valid JSON
- Schema validation against allowed keys
- Type checking for each value

#### 2.5 Live Validation Feedback

```html
<div class="live-validation-panel">
  <h3>Validation Status</h3>
  
  <!-- Shows as user types -->
  <div class="validation-item" v-for="field in validationFields">
    <span class="field-name">{{ field.label }}</span>
    
    <span v-if="field.status === 'valid'" class="badge-success">
      ✓ Valid
    </span>
    
    <span v-if="field.status === 'error'" class="badge-error">
      ✗ {{ field.message }}
    </span>
    
    <span v-if="field.status === 'warning'" class="badge-warning">
      ⚠ {{ field.message }}
    </span>
  </div>
  
  <!-- Form submit button state -->
  <button 
    type="submit"
    :disabled="hasErrors"
    class="btn btn-primary"
  >
    {{ hasErrors ? 'Fix errors to save' : 'Save Changes' }}
  </button>
</div>
```

**Validation Rules Displayed:**
- name: 1-255 characters
- questions_required: 1 to available count
- pass_score_percentage: 0-100%
- cooldown_days: 0-365 days
- settings: Must be valid JSON

### 3. Right Column: Questions Panel (1/3 Width)

#### 3.1 Statistics Box

```html
<div class="questions-stats">
  <div class="stat-card">
    <span class="label">Active Questions</span>
    <span class="value" :class="statusClass">{{ activeCount }}</span>
  </div>
  
  <div class="stat-card">
    <span class="label">Required</span>
    <span class="value">{{ questionsRequired }}</span>
  </div>
  
  <div class="stat-card">
    <span class="label">Status</span>
    <span class="value badge" :class="statusBadgeClass">
      {{ statusText }}
    </span>
  </div>
</div>

<!-- Dynamic updates when questions_required input changes -->
<script>
document.getElementById('questions_required').addEventListener('input', (e) => {
  updateStatistics(e.target.value);
});
</script>
```

**Status Indicators:**
```
✓ Ready     (Active questions >= Required)
⚠ Warning   (Active questions < Required)
✗ Not Ready (No questions assigned)
```

#### 3.2 Question Selection

```html
<div class="questions-selector">
  <input 
    type="search"
    placeholder="Search questions..."
    @input="filterQuestions"
    class="w-full"
  />
  
  <div class="questions-list">
    <div 
      v-for="question in availableQuestions"
      :key="question.id"
      class="question-item"
      :class="{ 'is-assigned': question.assigned }"
    >
      <input 
        type="checkbox"
        :value="question.id"
        v-model="selectedQuestions"
        @change="updateQuestions"
      />
      
      <div class="question-info">
        <span class="type-badge">{{ question.type }}</span>
        <span class="prompt">{{ question.prompt }}</span>
        <span class="languages">
          {{ question.translationsCount }} languages
        </span>
      </div>
    </div>
  </div>
</div>
```

**Features:**
- **Search:** Filter by prompt, ID, or language
- **Checkboxes:** Multi-select questions
- **Type Badge:** Visual indicator (MCQ, TRUE/FALSE, etc.)
- **Translation Count:** Shows localization coverage
- **Live Counter:** Updates with selections

---

## Editing Workflow

### Standard Edit Flow

```
1. User navigates to /admin/certifications/{id}/edit
   ↓
2. Form loads with current certification data
   ↓
3. User makes changes (form data captured)
   ↓
4. JavaScript tracks changes via FormData comparison
   ↓
5. Unsaved indicator (*) appears in title
   ↓
6. User can:
   a) Save → POST /admin/certifications/{id}
   b) View Changes → Modal with diff
   c) Discard → Reset form
   d) Navigate Away → Warning modal
```

### Change Detection Mechanism

```javascript
// On page load
const initialFormData = new FormData(form);
const initialState = Object.fromEntries(initialFormData);

// On any input
form.addEventListener('input', () => {
  const currentFormData = new FormData(form);
  const currentState = Object.fromEntries(currentFormData);
  
  const hasChanges = JSON.stringify(initialState) !== 
                     JSON.stringify(currentState);
  
  updateUnsavedIndicator(hasChanges);
});
```

### Saving Changes

```
POST /admin/certifications/{id}
{
  "name": "Updated Name",
  "description": "Updated desc",
  "questions_required": 15,
  "pass_score_percentage": 75,
  "cooldown_days": 14,
  "result_mode": "score_breakdown",
  "pdf_view": true,
  "settings": "{...}"
}

Response:
- Success: 302 redirect to edit page + success flash
- Validation error: 422 with error messages
- Active attempts: 409 Conflict with warning
```

### Handling Active Attempts

```
SCENARIO: User tries to change sensitive settings
         but there are active quiz attempts

RESPONSE: 409 Conflict status
{
  "error": "Cannot modify settings with active attempts",
  "active_attempts": 5,
  "message": "5 users are currently taking this quiz"
}

UI SHOWS: Modal with:
- Warning icon
- Number of active attempts
- Which fields are locked
- Recommended action: Wait or force update
```

---

## Real-time Validation

### Client-Side Validation

All validation happens in real-time as user types, with immediate feedback.

```html
<!-- Validation results shown per field -->
<div class="field-validation">
  <input 
    type="text"
    name="name"
    @input="validateField('name')"
  />
  
  <!-- Green checkmark for valid -->
  <span v-if="fields.name.valid" class="icon-check">✓</span>
  
  <!-- Red X for error -->
  <span v-if="fields.name.error" class="icon-error">✗</span>
  
  <!-- Yellow warning for warnings -->
  <span v-if="fields.name.warning" class="icon-warning">⚠</span>
  
  <!-- Error message -->
  <p v-if="fields.name.error" class="error">
    {{ fields.name.errorMessage }}
  </p>
  
  <!-- Warning message -->
  <p v-if="fields.name.warning" class="warning">
    {{ fields.name.warningMessage }}
  </p>
</div>
```

### Validation Rules

| Field | Rule | Feedback |
|-------|------|----------|
| `name` | 1-255 chars, unique | ✓/✗/⚠ |
| `questions_required` | 1 to count(questions) | ✓/⚠ |
| `pass_score_percentage` | 0-100, integer | ✓/⚠/✗ |
| `cooldown_days` | 0-365 | ✓/✗ |
| `settings` | Valid JSON | ✓/✗ |

### Error vs Warning

```javascript
// ERROR: Prevents form submission
- Invalid JSON in settings
- Pass score > 100%
- Negative cooldown days

// WARNING: Allows submission with notice
- No questions assigned
- Pass score > 90% (too strict)
- Very low pass score (< 30%)
```

---

## Version Control

### Version History Interface

```html
<button @click="showVersionHistory">📜 Version History</button>

<!-- Modal that appears -->
<modal v-if="showHistory" title="Version History">
  <div class="timeline">
    <div 
      v-for="version in versions"
      :key="version.id"
      class="timeline-item"
      :class="{ 'is-current': version.isCurrent }"
    >
      <span class="version-number">v{{ version.number }}</span>
      <span class="timestamp">{{ version.createdAt }}</span>
      <span class="user">by {{ version.author }}</span>
      
      <button @click="viewDiff(version)">View Changes</button>
      <button v-if="!version.isCurrent" @click="revert(version)">
        Revert to this version
      </button>
    </div>
  </div>
</modal>
```

### What Gets Versioned

Every time certification is saved, a snapshot is created:

```php
{
  "version": 5,
  "created_at": "2024-04-07T14:30:00Z",
  "user_id": 42,
  "data": {
    "name": "Financial Literacy Q2 2024",
    "description": "Quarter 2 certification...",
    "questions_required": 20,
    "pass_score_percentage": 75,
    "cooldown_days": 14,
    "result_mode": "score_breakdown",
    "settings": {...}
  },
  "metadata": {
    "changes_made": ["name", "questions_required"],
    "reason": "Q2 update - increased difficulty",
    "active_attempts": 0
  }
}
```

### Reverting to Previous Version

```
GET /admin/api/certifications/{id}/versions/{versionId}/compare
Response: Shows all differences between current and target version

POST /admin/certifications/{id}/versions/{versionId}/restore
Response: 
- Restores all data from that version
- Creates new version noting the revert
- Redirects to edit page
```

---

## Change Preview

### Viewing Changes Before Save

```html
<button @click="showChangesModal">👁 View Changes</button>

<!-- Change preview modal -->
<modal v-if="showChanges" title="Preview Changes">
  <div class="change-preview">
    <div 
      v-for="change in changes"
      :key="change.field"
      class="change-row"
      :class="change.sensitivity"
    >
      <!-- Field name with sensitivity badge -->
      <span class="field-name">
        {{ change.field }}
        <span v-if="change.isSensitive" class="badge-sensitive">
          ⚠ Sensitive
        </span>
      </span>
      
      <!-- Before/After comparison -->
      <div class="before-after">
        <div class="before">
          <label>Current</label>
          <code>{{ change.before }}</code>
        </div>
        
        <div class="arrow">→</div>
        
        <div class="after">
          <label>New</label>
          <code>{{ change.after }}</code>
        </div>
      </div>
      
      <!-- Impact note -->
      <p v-if="change.impact" class="impact-note">
        ℹ {{ change.impact }}
      </p>
    </div>
  </div>
</modal>
```

### Sensitive Fields

Changes to these fields trigger warnings:

```
🔴 CRITICAL:
  - questions_required (affects user progress)
  - pass_score_percentage (affects pass/fail)
  - cooldown_days (affects retry restrictions)

🟡 WARNING:
  - result_mode (affects result display)
  - settings (affects behavior)

🟢 SAFE:
  - name (cosmetic only)
  - description (cosmetic only)
  - pdf_view (feature toggle)
```

---

## API Integration

### Frontend API Endpoints

#### Get Available Questions

```javascript
fetch(`/admin/api/certifications/${certId}/available-questions`)
  .then(r => r.json())
  .then(data => {
    console.log(data.questions); // Array of questions
    console.log(data.total); // Count
    console.log(data.by_type); // Grouped by type
  });
```

**Response:**
```json
{
  "questions": [
    {
      "id": 1,
      "prompt": "What is...",
      "type": "mcq_4",
      "active": true,
      "translations_count": 5
    }
  ],
  "total": 42,
  "by_type": {
    "mcq_4": 20,
    "true_false": 15,
    "essay": 7
  }
}
```

#### Get Active Attempts

```javascript
fetch(`/admin/api/certifications/${certId}/active-attempts`)
  .then(r => r.json())
  .then(data => {
    console.log(data.count); // Number of active attempts
    console.log(data.users); // List of users
  });
```

**Response:**
```json
{
  "count": 3,
  "users": [
    {
      "id": 101,
      "name": "John Doe",
      "email": "john@company.com",
      "started_at": "2024-04-07T10:00:00Z",
      "progress": 0.45
    }
  ]
}
```

#### Compare Versions

```javascript
fetch(`/admin/api/certifications/${certId}/versions/${versionId}/compare`)
  .then(r => r.json())
  .then(data => {
    data.changes.forEach(change => {
      console.log(`${change.field}: ${change.before} → ${change.after}`);
    });
  });
```

**Response:**
```json
{
  "from_version": 4,
  "to_version": 5,
  "changes": [
    {
      "field": "questions_required",
      "before": 15,
      "after": 20,
      "is_sensitive": true
    }
  ]
}
```

---

## Best Practices

### 1. Making Large Changes

```
✓ GOOD:
  1. Make one major change at a time
  2. Save after each logical group
  3. Document reason in audit log
  4. Monitor active attempts after change
  
✗ BAD:
  1. Change many unrelated fields
  2. Try to save if validation shows errors
  3. Forget to check active attempts
  4. Make breaking changes during quiz hours
```

### 2. Managing Pass Scores

```
✓ Recommended ranges:
  - Easy certification: 60-70%
  - Medium certification: 70-80%
  - Hard certification: 80-90%

⚠️ Avoid:
  - Above 95% (almost impossible to pass)
  - Below 50% (trivializes certification)
  - Frequent changes (confuses users)
```

### 3. Question Management

```
✓ DO:
  - Ensure questions_required < total_questions
  - Have multiple question types for variety
  - Keep translations consistent
  - Review questions before publishing

✗ DON'T:
  - Set requirements higher than available
  - Use low-quality or outdated questions
  - Leave untranslated questions active
  - Publish without testing
```

### 4. Settings JSON

```json
// ✓ VALID structure
{
  "theme": "modern",
  "show_explanations": true,
  "randomize_questions": true,
  "time_limit": 3600,
  "allow_review": false
}

// ✗ INVALID - will cause validation error
{
  "theme": "modern"
  "show_explanations": true  // Missing comma
}
```

### 5. Handling Concurrent Changes

```
If multiple admins try to edit same certification:

Scenario: Admin A and Admin B both editing
- Admin A saves first ✓
- Admin B receives 409 Conflict warning
- Admin B must reload and redo changes

Solution: Add note in change preview modal:
"This certification was modified by [Admin A]
 since you started editing. Please review
 current state before saving your changes."
```

---

## Keyboard Shortcuts

```
Ctrl+S / Cmd+S     → Save changes (if form is valid)
Escape              → Discard changes (with confirmation)
Ctrl+Z / Cmd+Z      → Not supported (form based, not undo)
Tab                 → Navigate between fields
Enter               → Submit form (when submit button focused)
```

### Navigation Shortcuts

```
[H]  → Show Help modal
[V]  → View Changes modal
[C]  → Clear all changes (with confirmation)
[R]  → Reload from server (with confirmation)
```

---

## Accessibility Features

### Screen Reader Support

```html
<input 
  type="text"
  aria-label="Certification Name"
  aria-required="true"
  aria-invalid="false"
  aria-describedby="name-help"
/>

<p id="name-help" class="help-text">
  Enter the certification name (1-255 characters)
</p>
```

### Keyboard Navigation

- All buttons accessible via Tab
- Form fields in logical order
- Error messages linked to fields
- Focus indicators visible
- ARIA labels for icons

### Color Accessibility

```
Status indicators use:
  ✓ not just green, but: Green + checkmark
  ✗ not just red, but: Red + X symbol
  ⚠ not just yellow, but: Yellow + warning icon
```

---

## Troubleshooting Guide

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues.

---

## Summary

The Visual Builder provides a complete interface for managing certifications with:

- **Real-time validation** for immediate feedback
- **Change preview** showing exactly what will change
- **Version control** with full history and rollback
- **Active attempt detection** preventing breaking changes
- **Responsive design** with organized two-column layout
- **API integration** for dynamic data updates
- **Accessibility** for all users

All features are designed to prevent data loss and provide transparency into what changes are being made before they're applied.
