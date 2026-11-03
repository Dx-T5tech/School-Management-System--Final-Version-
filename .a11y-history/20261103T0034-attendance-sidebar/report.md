# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 4 items |
| Skills run | keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app\Shared\admin_section_content.php`
- `app\Shared\dashboard_layout.php`
- `public\assets\css\dashboard.css`
- `public\assets\js\dashboard.js`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 97 out of 100 |
| Grade | A |
| Findings | 1 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 1 | Real friction, workaround exists |
| minor | 0 | Noticeable but rarely blocking |

## Findings

1 findings, ordered by severity then by rule.

### Moderate (1)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `focus-not-obscured` | 2.4.11 AA | public/assets/js/dashboard.js:78-94; public/assets/css/dashboard.css:81-86, 2092-2104 | On mobile, Tab can move focus to page controls behind the open fixed sidebar while the drawer remains visible, obscuring the focused control. | Close the drawer when keyboard focus leaves it, without stealing focus, or make it modal by keeping focus inside and making background content inert; keep the expanded state synchronized. |

## Remediation priorities

The remaining 1 moderate and minor findings can follow in normal work.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: The focus-obscuring behavior is in the existing mobile sidebar interaction; this navigation-only change did not modify the drawer behavior.

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
    "app\\Shared\\admin_section_content.php",
    "app\\Shared\\dashboard_layout.php",
    "public\\assets\\css\\dashboard.css",
    "public\\assets\\js\\dashboard.js"
  ],
  "findings": [
    {
      "rule": "focus-not-obscured",
      "wcag": "2.4.11 AA",
      "severity": "moderate",
      "confidence": "medium",
      "location": "public/assets/js/dashboard.js:78-94; public/assets/css/dashboard.css:81-86, 2092-2104",
      "summary": "On mobile, Tab can move focus to page controls behind the open fixed sidebar while the drawer remains visible, obscuring the focused control.",
      "fix": "Close the drawer when keyboard focus leaves it, without stealing focus, or make it modal by keeping focus inside and making background content inert; keep the expanded state synchronized.",
      "evidence": "The sidebar stays open until Escape, a fragment link, or the toggle closes it; no focus-leave handler closes the fixed drawer.",
      "foundBy": [
        "keyboard-navigator"
      ]
    }
  ],
  "passed": [],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "The focus-obscuring behavior is in the existing mobile sidebar interaction; this navigation-only change did not modify the drawer behavior."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 1,
    "minor": 0
  },
  "components": [],
  "score": 97,
  "grade": "A",
  "penalty": 1.4,
  "checks": 10,
  "generated": "2026-11-02",
  "template": "web"
}
```
