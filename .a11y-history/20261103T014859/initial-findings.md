# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 6 items |
| Skills run | alt-text-headings, aria-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Core/admin.php`
- `app/Core/super_admin.php`
- `app/Core/teacher.php`
- `app/Modules/StudentManagement/Students/Services/student.php`
- `app/Shared/dashboard_layout.php`
- `public/assets/css/dashboard.css`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 92 out of 100 |
| Grade | A |
| Findings | 3 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 3 | Real friction, workaround exists |
| minor | 0 | Noticeable but rarely blocking |

## Findings

3 findings, ordered by severity then by rule.

### Moderate (3)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `KEYBOARD-CURRENT` | 1.3.1 A | app/Shared/dashboard_layout.php:79-92 | An active Academic or Attendance parent link can look current but lack aria-current whenever its submenu exists and no child link is current. | Suppress aria-current on a parent only when one of its submenu links is current; otherwise set aria-current=page on the active parent. |
| `NAV-CURRENT-PAGE-001` | 4.1.2 A | app/Shared/dashboard_layout.php:79,92 | A current Academic Management or Attendance & Scheduling parent link loses aria-current whenever its submenu exists, so a landing page can appear current visually without exposing that state to assistive technology. | Suppress aria-current on the parent only when a nested link is current; otherwise retain aria-current=page on the current parent link. |
| `NAV-GROUP-SEMANTICS-001` | 1.3.1 A | app/Shared/dashboard_layout.php:73 | The visible navigation-group captions are plain spans, so their heading-like structure is not exposed to assistive technology. | Use appropriate heading elements for the group captions so users can identify and navigate the sidebar sections by heading. |

## Remediation priorities

The remaining 3 moderate and minor findings can follow in normal work.

## What passed

4 checks ran and found nothing.

- Navigation groups preserve item order, and Academic/Attendance submenu links are ordinary anchors in sequential Tab order.
- The sidebar uses a labeled navigation landmark with H2 group headings, followed by one H1 in the main landmark; no heading levels are skipped.
- The skip link is first in document order; links and buttons have visible focus styling.
- The template includes a skip link, labeled sidebar landmark, main landmark, and page-level header and footer.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Static source review only; no browser keyboard scan was run.
- **alt-text-headings**: Headings in section-content templates included at runtime were outside this scope.

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
    "keyboard-navigator"
  ],
  "scope": [
    "app/Core/admin.php",
    "app/Core/super_admin.php",
    "app/Core/teacher.php",
    "app/Modules/StudentManagement/Students/Services/student.php",
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [
    {
      "rule": "KEYBOARD-CURRENT",
      "wcag": "1.3.1 A",
      "severity": "moderate",
      "confidence": "medium",
      "location": "app/Shared/dashboard_layout.php:79-92",
      "summary": "An active Academic or Attendance parent link can look current but lack aria-current whenever its submenu exists and no child link is current.",
      "fix": "Suppress aria-current on a parent only when one of its submenu links is current; otherwise set aria-current=page on the active parent.",
      "evidence": "The is-current class depends on item.current, but aria-current was suppressed based on submenu presence instead of whether a child was current.",
      "foundBy": [
        "keyboard-navigator"
      ]
    },
    {
      "rule": "NAV-CURRENT-PAGE-001",
      "wcag": "4.1.2 A",
      "severity": "moderate",
      "confidence": "medium",
      "location": "app/Shared/dashboard_layout.php:79,92",
      "summary": "A current Academic Management or Attendance & Scheduling parent link loses aria-current whenever its submenu exists, so a landing page can appear current visually without exposing that state to assistive technology.",
      "fix": "Suppress aria-current on the parent only when a nested link is current; otherwise retain aria-current=page on the current parent link.",
      "evidence": "The condition checked whether the submenu existed rather than whether it contained the current destination.",
      "foundBy": [
        "aria-specialist"
      ]
    },
    {
      "rule": "NAV-GROUP-SEMANTICS-001",
      "wcag": "1.3.1 A",
      "severity": "moderate",
      "confidence": "medium",
      "location": "app/Shared/dashboard_layout.php:73",
      "summary": "The visible navigation-group captions are plain spans, so their heading-like structure is not exposed to assistive technology.",
      "fix": "Use appropriate heading elements for the group captions so users can identify and navigate the sidebar sections by heading.",
      "evidence": "The group caption used a span element.",
      "foundBy": [
        "aria-specialist"
      ]
    }
  ],
  "passed": [
    "Navigation groups preserve item order, and Academic/Attendance submenu links are ordinary anchors in sequential Tab order.",
    "The sidebar uses a labeled navigation landmark with H2 group headings, followed by one H1 in the main landmark; no heading levels are skipped.",
    "The skip link is first in document order; links and buttons have visible focus styling.",
    "The template includes a skip link, labeled sidebar landmark, main landmark, and page-level header and footer."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Static source review only; no browser keyboard scan was run."
    },
    {
      "skill": "alt-text-headings",
      "note": "Headings in section-content templates included at runtime were outside this scope."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 3,
    "minor": 0
  },
  "components": [],
  "score": 92,
  "grade": "A",
  "penalty": 4.2,
  "checks": 10,
  "generated": "2026-11-02",
  "template": "web"
}
```
