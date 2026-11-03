# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 6 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator, live-region-controller, tables-data-specialist |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 18 |

Audited:

- `app/Modules/StudentManagement/Students/Controllers/section.php`
- `app/Modules/StudentManagement/Students/Views/section_content.php`
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

18 checks ran and found nothing.

- Browser verification confirmed the final grade is announced after input pauses and prerequisite messages appear immediately on period changes.
- Calculator body text, helper/status text, labels, result, input borders, and notification link meet their reviewed contrast targets.
- Calculator input focus styling is visible.
- Component inputs have labels, required state, 0-100 constraints, shared help, and associated server errors.
- Correcting a server-invalid percentage clears aria-invalid and its error description while retaining shared help; verified in the browser.
- Grade output updates immediately while polite status announcements are debounced and concise.
- Native controls retain natural tab order and become enabled when their selections are complete.
- Selector changes remove stale component error IDs from aria-describedby.
- Teacher and student term-grade tables label Prelim, Midterm, and Overall Final values and format saved grades as percentages.
- The calculator fieldset border uses --control-line against --soft, providing 3.23:1 non-text contrast.
- The calculator preserves validation input and blocks later-period calculation when a prerequisite grade is missing.
- The calculator uses a fieldset and legend, labeled fields, and a single polite status region.
- The calculator's fieldset/legend and the descriptive View My Grades link use appropriate semantics.
- The dashboard retains its main landmark, page H1, and logical section headings.
- The existing native dialog closes with Escape and returns focus to the Record term grade trigger; verified in the browser.
- The teacher table has a caption and scoped column/row headers; the student table has a caption and scoped column headers.
- The visual result is hidden from assistive technology to avoid duplicating the live status announcement.
- Unchanged status messages are not re-announced.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **forms-specialist**: The initial review identified stale component errors after correction; the issue was fixed and directly verified.
- **aria-specialist**: The initial review found stale error relationships; correction behavior was fixed and confirmed with a browser DOM check.
- **live-region-controller**: The initial review identified repetitive announcements; debouncing and concise status copy were added and verified.
- **keyboard-navigator**: The specialist performed source review; the dialog Escape and focus-return behavior was additionally checked in the browser.
- **contrast-master**: The initial review identified a low-contrast fieldset boundary; it was changed to --control-line.

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
    "tables-data-specialist"
  ],
  "scope": [
    "app/Modules/StudentManagement/Students/Controllers/section.php",
    "app/Modules/StudentManagement/Students/Views/section_content.php",
    "app/Shared/teacher_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js",
    "public/teacher-section.php"
  ],
  "findings": [],
  "passed": [
    "Browser verification confirmed the final grade is announced after input pauses and prerequisite messages appear immediately on period changes.",
    "Calculator body text, helper/status text, labels, result, input borders, and notification link meet their reviewed contrast targets.",
    "Calculator input focus styling is visible.",
    "Component inputs have labels, required state, 0-100 constraints, shared help, and associated server errors.",
    "Correcting a server-invalid percentage clears aria-invalid and its error description while retaining shared help; verified in the browser.",
    "Grade output updates immediately while polite status announcements are debounced and concise.",
    "Native controls retain natural tab order and become enabled when their selections are complete.",
    "Selector changes remove stale component error IDs from aria-describedby.",
    "Teacher and student term-grade tables label Prelim, Midterm, and Overall Final values and format saved grades as percentages.",
    "The calculator fieldset border uses --control-line against --soft, providing 3.23:1 non-text contrast.",
    "The calculator preserves validation input and blocks later-period calculation when a prerequisite grade is missing.",
    "The calculator uses a fieldset and legend, labeled fields, and a single polite status region.",
    "The calculator's fieldset/legend and the descriptive View My Grades link use appropriate semantics.",
    "The dashboard retains its main landmark, page H1, and logical section headings.",
    "The existing native dialog closes with Escape and returns focus to the Record term grade trigger; verified in the browser.",
    "The teacher table has a caption and scoped column/row headers; the student table has a caption and scoped column headers.",
    "The visual result is hidden from assistive technology to avoid duplicating the live status announcement.",
    "Unchanged status messages are not re-announced."
  ],
  "notes": [
    {
      "skill": "forms-specialist",
      "note": "The initial review identified stale component errors after correction; the issue was fixed and directly verified."
    },
    {
      "skill": "aria-specialist",
      "note": "The initial review found stale error relationships; correction behavior was fixed and confirmed with a browser DOM check."
    },
    {
      "skill": "live-region-controller",
      "note": "The initial review identified repetitive announcements; debouncing and concise status copy were added and verified."
    },
    {
      "skill": "keyboard-navigator",
      "note": "The specialist performed source review; the dialog Escape and focus-return behavior was additionally checked in the browser."
    },
    {
      "skill": "contrast-master",
      "note": "The initial review identified a low-contrast fieldset boundary; it was changed to --control-line."
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
  "checks": 18,
  "generated": "2026-11-03",
  "template": "web"
}
```
