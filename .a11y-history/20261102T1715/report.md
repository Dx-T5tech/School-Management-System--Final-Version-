# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 7 items |
| Skills run | alt-text-headings, aria-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 13 |

Audited:

- `app/Shared/dashboard_layout.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/dashboard.js`
- `public/login.php`
- `public/role-dashboard.php`
- `public/student-dashboard.php`
- `public/teacher-dashboard.php`

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
| `KEYBOARD-001` | 2.4.11 AA | public/assets/css/dashboard.css:824-835 | The fixed mobile sidebar has no vertical scrolling, so its navigation links can extend outside short viewports and receive focus while off-screen. | Make the mobile sidebar vertically scrollable within the viewport, for example with `max-height: 100dvh; overflow-y: auto`, and verify focused links remain visible on short screens. |

## Remediation priorities

The remaining 1 moderate and minor findings can follow in normal work.

## What passed

12 checks ran and found nothing.

- ARIA ID references resolve to existing elements.
- Dashboard and login pages place a skip link first in the tab order and target a main element with tabindex="-1".
- Decorative icons are hidden from assistive technology, and the menu button has an accessible name.
- No positive tabindex values were found; keyboard focus styles are defined for links, buttons, and programmatically focused dashboard sections.
- The mobile menu's aria-expanded state is updated when the menu opens, closes, or the viewport changes.
- The responsive navigation uses a native button and links; its script updates aria-expanded, supports Escape, and returns focus to the toggle.
- The role dashboard performs a server redirect to the role-specific page rather than an in-page route change.
- heading hierarchy
- landmark structure
- language attributes
- page titles
- text alternatives

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Automated keyboard and viewport scans could not run because Playwright is not installed; findings are based on source review.
- **alt-text-headings**: public/role-dashboard.php redirects to the role-specific dashboard and does not render a page; the shared template supplies the dashboard title and structure.

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
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js",
    "public/login.php",
    "public/role-dashboard.php",
    "public/student-dashboard.php",
    "public/teacher-dashboard.php"
  ],
  "findings": [
    {
      "rule": "KEYBOARD-001",
      "wcag": "2.4.11 AA",
      "severity": "moderate",
      "location": "public/assets/css/dashboard.css:824-835",
      "summary": "The fixed mobile sidebar has no vertical scrolling, so its navigation links can extend outside short viewports and receive focus while off-screen.",
      "fix": "Make the mobile sidebar vertically scrollable within the viewport, for example with `max-height: 100dvh; overflow-y: auto`, and verify focused links remain visible on short screens.",
      "evidence": "At max-width 860px the sidebar is displayed with 72px top padding, but no max-height or overflow-y is set.",
      "component": "Shared teacher and student dashboard navigation",
      "foundBy": [
        "keyboard-navigator"
      ]
    }
  ],
  "passed": [
    "ARIA ID references resolve to existing elements.",
    "Dashboard and login pages place a skip link first in the tab order and target a main element with tabindex=\"-1\".",
    "Decorative icons are hidden from assistive technology, and the menu button has an accessible name.",
    "No positive tabindex values were found; keyboard focus styles are defined for links, buttons, and programmatically focused dashboard sections.",
    "The mobile menu's aria-expanded state is updated when the menu opens, closes, or the viewport changes.",
    "The responsive navigation uses a native button and links; its script updates aria-expanded, supports Escape, and returns focus to the toggle.",
    "The role dashboard performs a server redirect to the role-specific page rather than an in-page route change.",
    "heading hierarchy",
    "landmark structure",
    "language attributes",
    "page titles",
    "text alternatives"
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Automated keyboard and viewport scans could not run because Playwright is not installed; findings are based on source review."
    },
    {
      "skill": "alt-text-headings",
      "note": "public/role-dashboard.php redirects to the role-specific dashboard and does not render a page; the shared template supplies the dashboard title and structure."
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
  "penalty": 2,
  "checks": 13,
  "generated": "2026-11-02",
  "template": "web"
}
```
