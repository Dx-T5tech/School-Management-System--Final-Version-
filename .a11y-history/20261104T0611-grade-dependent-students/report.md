# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 3 items |
| Skills run | aria-specialist, forms-specialist |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/teacher_section_content.php`
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

7 checks ran and found nothing.

- Changing the controlling assessment or class assignment clears a stale student choice and refreshes the available student count.
- Each dependent student select references its dedicated polite atomic status region.
- Empty dependent rosters disable the student select and explain how to recover.
- The assessment-score student select is disabled until an assessment is selected, then contains only students in that assessment's class.
- The existing native select controls remain keyboard-operable.
- The status region announces the selected assessment or class assignment and the matching student count.
- The term-grade student select is disabled until a class assignment is selected, then contains only students in that assignment's class.

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
    "forms-specialist"
  ],
  "scope": [
    "app/Shared/teacher_section_content.php",
    "public/assets/js/dashboard.js",
    "public/teacher-section.php"
  ],
  "findings": [],
  "passed": [
    "Changing the controlling assessment or class assignment clears a stale student choice and refreshes the available student count.",
    "Each dependent student select references its dedicated polite atomic status region.",
    "Empty dependent rosters disable the student select and explain how to recover.",
    "The assessment-score student select is disabled until an assessment is selected, then contains only students in that assessment's class.",
    "The existing native select controls remain keyboard-operable.",
    "The status region announces the selected assessment or class assignment and the matching student count.",
    "The term-grade student select is disabled until a class assignment is selected, then contains only students in that assignment's class."
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
