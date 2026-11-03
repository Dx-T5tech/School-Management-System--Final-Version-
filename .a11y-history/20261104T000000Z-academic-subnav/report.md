# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 4 items |
| Skills run | aria-specialist, contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app\Shared\dashboard_layout.php`
- `public\admin-section.php`
- `public\assets\css\dashboard.css`
- `public\assets\js\dashboard.js`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 94 out of 100 |
| Grade | A |
| Findings | 2 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 2 | Real friction, workaround exists |
| minor | 0 | Noticeable but rarely blocking |

## Findings

2 findings, ordered by severity then by rule.

### Moderate (2)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `KEYBOARD-001` | 2.4.3 A | app/Shared/dashboard_layout.php:47-63,110-119; public/assets/js/dashboard.js:78-90 | On mobile, opening the menu moves focus into the sidebar even though the Menu button follows the sidebar in DOM order, making subsequent tab order loop back to the button after the navigation links. | Place the Menu button before the sidebar navigation in DOM order and align focus behavior with that order, so forward and reverse Tab navigation proceed predictably. |
| `KEYBOARD-002` | 2.4.11 AA | public/assets/js/dashboard.js:101-123; public/assets/css/dashboard.css:1866-1884 | On mobile, Tab can move focus from the open sidebar into page content while the drawer remains over the content, obscuring focused controls or their focus indicators. | When keyboard focus leaves the open drawer for page content, close it and update aria-expanded, or implement a modal drawer that keeps focus contained and the background unavailable. |

## Remediation priorities

The remaining 2 moderate and minor findings can follow in normal work.

## What passed

3 checks ran and found nothing.

- Hover and active link text (#ffffff) contrast: 11.03:1 against the hover surface and 10.66:1 against the hover surface over the radial tint; both exceed 4.5:1.
- Nested-link text (#d3dcde) contrast: 10.42:1 against the Admin sidebar base (#172b3b) and 8.55:1 against the composited radial-gradient tint; both exceed WCAG 2.2 AA’s 4.5:1 minimum.
- The active-state gold indicator (#e7bd72) has 8.25:1 contrast against the base sidebar and 6.05:1 against the hover surface over the radial tint; both exceed the 3:1 non-text minimum.

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
    "app\\Shared\\dashboard_layout.php",
    "public\\admin-section.php",
    "public\\assets\\css\\dashboard.css",
    "public\\assets\\js\\dashboard.js"
  ],
  "findings": [
    {
      "rule": "KEYBOARD-001",
      "wcag": "2.4.3 A",
      "severity": "moderate",
      "confidence": "medium",
      "location": "app/Shared/dashboard_layout.php:47-63,110-119; public/assets/js/dashboard.js:78-90",
      "summary": "On mobile, opening the menu moves focus into the sidebar even though the Menu button follows the sidebar in DOM order, making subsequent tab order loop back to the button after the navigation links.",
      "fix": "Place the Menu button before the sidebar navigation in DOM order and align focus behavior with that order, so forward and reverse Tab navigation proceed predictably.",
      "evidence": "The sidebar precedes the workspace and Menu button in the template; setMenuOpen(true) moves focus to the first sidebar link.",
      "foundBy": [
        "keyboard-navigator"
      ]
    },
    {
      "rule": "KEYBOARD-002",
      "wcag": "2.4.11 AA",
      "severity": "moderate",
      "confidence": "medium",
      "location": "public/assets/js/dashboard.js:101-123; public/assets/css/dashboard.css:1866-1884",
      "summary": "On mobile, Tab can move focus from the open sidebar into page content while the drawer remains over the content, obscuring focused controls or their focus indicators.",
      "fix": "When keyboard focus leaves the open drawer for page content, close it and update aria-expanded, or implement a modal drawer that keeps focus contained and the background unavailable.",
      "evidence": "The drawer stays displayed while .is-open is set; only a link click or Escape closes it, and there is no focus-exit handling.",
      "foundBy": [
        "keyboard-navigator"
      ]
    }
  ],
  "passed": [
    "Hover and active link text (#ffffff) contrast: 11.03:1 against the hover surface and 10.66:1 against the hover surface over the radial tint; both exceed 4.5:1.",
    "Nested-link text (#d3dcde) contrast: 10.42:1 against the Admin sidebar base (#172b3b) and 8.55:1 against the composited radial-gradient tint; both exceed WCAG 2.2 AA’s 4.5:1 minimum.",
    "The active-state gold indicator (#e7bd72) has 8.25:1 contrast against the base sidebar and 6.05:1 against the hover surface over the radial tint; both exceed the 3:1 non-text minimum."
  ],
  "notes": [],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 2,
    "minor": 0
  },
  "components": [],
  "score": 94,
  "grade": "A",
  "penalty": 2.8,
  "checks": 10,
  "generated": "2026-11-02",
  "template": "web"
}
```
