# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 4 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator, live-region-controller, modal-specialist |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 20 |

Audited:

- `app/Shared/teacher_section_content.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/dashboard.js`
- `public/teacher-section.php`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 100 out of 100 |
| Grade | A |
| Findings | 0 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 0 | Real friction, workaround exists |
| minor | 0 | Noticeable but rarely blocking |

## Findings

No findings. Every check listed under "What passed" ran and came back clean.

## Remediation priorities

Nothing to remediate.

## What passed

20 checks ran and found nothing.

- Attendance controls use native labeled inputs, selects, checkboxes, and fieldsets with legends.
- Attendance page and section heading hierarchy is logical.
- Attendance schedule summary and roster boundaries use #7D8A8C against white at 3.57:1, meeting WCAG 1.4.11.
- Attendance text, selected checkboxes, and focus indicators meet their applicable contrast requirements.
- Browser testing confirmed visible focus indicators and focus restoration to the Record attendance trigger.
- Class, date, and status controls are labeled, required, and associated with validation errors.
- Class/date changes update one concise polite schedule status.
- Closing the dialog returns focus to its connected trigger; Escape behavior was verified in the browser.
- Dialog title and form descriptions reference existing IDs.
- Each class roster legend explains the student group and required selection.
- Escape, the close button, and backdrop dismissal close the native dialog.
- Roster groups use native fieldsets and legends; checkbox controls reference the roster error and expose aria-invalid when validation fails.
- Select-all checked and indeterminate states update from the student checkbox selection.
- Server-side validation restores submitted class/date/status and selected student values; an invalid empty selection reopens the modal with an error.
- Student checkboxes provide their native checked state while bulk selection avoids simultaneous count announcements.
- The attendance dialog is named by its heading and focuses that heading on open; an error summary receives focus after invalid submission.
- The generated dialog is associated with its heading.
- The native modal contains keyboard focus; browser testing confirmed focus remains in the dialog and Escape closes it.
- The page has labeled controls and meaningful landmarks with an Attendance-specific page title.
- The selected student count stays visually current without repetitive live announcements.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **contrast-master**: Unchecked checkbox borders retain browser-native rendering.

## Next steps

1. Keep the current checks in continuous integration so this does not regress.
2. Re-audit when the interface changes materially, or quarterly.
3. Confirm the automated result with a manual screen reader and keyboard pass.

Automated checks find a minority of accessibility barriers. A passing score is a floor, not a guarantee.

## Findings data

The merged findings, embedded so the next run can compute a delta without parsing this document.

```json a11y-findings
{
  "skills": [
    "alt-text-headings",
    "aria-specialist",
    "contrast-master",
    "forms-specialist",
    "keyboard-navigator",
    "live-region-controller",
    "modal-specialist"
  ],
  "scope": [
    "app/Shared/teacher_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js",
    "public/teacher-section.php"
  ],
  "findings": [],
  "passed": [
    "Attendance controls use native labeled inputs, selects, checkboxes, and fieldsets with legends.",
    "Attendance page and section heading hierarchy is logical.",
    "Attendance schedule summary and roster boundaries use #7D8A8C against white at 3.57:1, meeting WCAG 1.4.11.",
    "Attendance text, selected checkboxes, and focus indicators meet their applicable contrast requirements.",
    "Browser testing confirmed visible focus indicators and focus restoration to the Record attendance trigger.",
    "Class, date, and status controls are labeled, required, and associated with validation errors.",
    "Class/date changes update one concise polite schedule status.",
    "Closing the dialog returns focus to its connected trigger; Escape behavior was verified in the browser.",
    "Dialog title and form descriptions reference existing IDs.",
    "Each class roster legend explains the student group and required selection.",
    "Escape, the close button, and backdrop dismissal close the native dialog.",
    "Roster groups use native fieldsets and legends; checkbox controls reference the roster error and expose aria-invalid when validation fails.",
    "Select-all checked and indeterminate states update from the student checkbox selection.",
    "Server-side validation restores submitted class/date/status and selected student values; an invalid empty selection reopens the modal with an error.",
    "Student checkboxes provide their native checked state while bulk selection avoids simultaneous count announcements.",
    "The attendance dialog is named by its heading and focuses that heading on open; an error summary receives focus after invalid submission.",
    "The generated dialog is associated with its heading.",
    "The native modal contains keyboard focus; browser testing confirmed focus remains in the dialog and Escape closes it.",
    "The page has labeled controls and meaningful landmarks with an Attendance-specific page title.",
    "The selected student count stays visually current without repetitive live announcements."
  ],
  "notes": [
    {
      "skill": "contrast-master",
      "note": "Unchecked checkbox borders retain browser-native rendering."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 0,
    "minor": 0
  },
  "components": [],
  "score": 100,
  "grade": "A",
  "penalty": 0,
  "checks": 20,
  "generated": "2026-11-03",
  "template": "web"
}
```
