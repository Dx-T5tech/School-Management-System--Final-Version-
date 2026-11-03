# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 4 items |
| Skills run | keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Modules/StudentManagement/Students/Controllers/section.php`
- `app/Modules/StudentManagement/Students/Views/section_content.php`
- `app/Shared/dashboard_layout.php`
- `public/assets/css/dashboard.css`

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

4 checks ran and found nothing.

- Dashboard chrome and the print action are hidden with display:none only in print media, so those controls remain available on screen and are not keyboard focus targets in the print layout.
- Dashboard navigation, sign-out, the report action, and named table scroll regions follow the document’s natural focus order; the report tables use tabindex="0" rather than positive tabindex values.
- The screen-hidden report heading contains static text, not controls; it is displayed for print without introducing hidden keyboard targets.
- The skip link is first in the page’s focus order and targets the programmatically focusable main landmark.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Source-only review; browser keyboard interaction and rendered print output were not tested.

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
    "keyboard-navigator"
  ],
  "scope": [
    "app/Modules/StudentManagement/Students/Controllers/section.php",
    "app/Modules/StudentManagement/Students/Views/section_content.php",
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [],
  "passed": [
    "Dashboard chrome and the print action are hidden with display:none only in print media, so those controls remain available on screen and are not keyboard focus targets in the print layout.",
    "Dashboard navigation, sign-out, the report action, and named table scroll regions follow the document’s natural focus order; the report tables use tabindex=\"0\" rather than positive tabindex values.",
    "The screen-hidden report heading contains static text, not controls; it is displayed for print without introducing hidden keyboard targets.",
    "The skip link is first in the page’s focus order and targets the programmatically focusable main landmark."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Source-only review; browser keyboard interaction and rendered print output were not tested."
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
  "checks": 10,
  "generated": "2026-11-03",
  "template": "web"
}
```
