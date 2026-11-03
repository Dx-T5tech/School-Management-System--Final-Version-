# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 3 items |
| Skills run | aria-specialist, contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/admin_section_content.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/dashboard.js`

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

## Delta tracking

Movement since the previous report.

| Change | Count |
|---|---:|
| Fixed | 0 |
| New | 0 |
| Persistent | 0 |
| Regressed | 0 |

Score moved from 100 to 100.

## What passed

8 checks ran and found nothing.

- Building and floor choices are native submit buttons; the search field has a label and native submit button.
- Building and floor selectors use native submit buttons, grouped by fieldsets with legends; aria-pressed reflects the selected value on render.
- DOM tab order matches the visual order of building, floor, and search controls at desktop and responsive layouts.
- Dynamic room changes and refresh errors are announced through a role=status live region.
- Room status labels are text, not color-only indicators, and decorative status dots are hidden from assistive technology.
- Selector buttons are at least 32 × 36 CSS pixels, and search controls are at least 38 pixels tall.
- The selector and search borders compute to #73808A and meet WCAG 1.4.11 against #FFFFFF and #F5F7F6.
- Visible focus styles apply to selector buttons, search input, and submit button.

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
    "aria-specialist",
    "contrast-master",
    "keyboard-navigator"
  ],
  "scope": [
    "app/Shared/admin_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    "Building and floor choices are native submit buttons; the search field has a label and native submit button.",
    "Building and floor selectors use native submit buttons, grouped by fieldsets with legends; aria-pressed reflects the selected value on render.",
    "DOM tab order matches the visual order of building, floor, and search controls at desktop and responsive layouts.",
    "Dynamic room changes and refresh errors are announced through a role=status live region.",
    "Room status labels are text, not color-only indicators, and decorative status dots are hidden from assistive technology.",
    "Selector buttons are at least 32 × 36 CSS pixels, and search controls are at least 38 pixels tall.",
    "The selector and search borders compute to #73808A and meet WCAG 1.4.11 against #FFFFFF and #F5F7F6.",
    "Visible focus styles apply to selector buttons, search input, and submit button."
  ],
  "notes": [],
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
  "checks": 10,
  "generated": "2026-11-03",
  "template": "web"
}
```
