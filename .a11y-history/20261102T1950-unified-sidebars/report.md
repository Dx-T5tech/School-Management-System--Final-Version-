# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 3 items |
| Skills run | contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app\Shared\dashboard_layout.php`
- `public\assets\css\dashboard.css`
- `public\assets\js\dashboard.js`

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

8 checks ran and found nothing.

- Keyboard focus indicators are provided for links and buttons with a 3px outline and contrasting halo; no outline removal was found.
- The Workspace label is noninteractive text before the navigation, so it does not add a tab stop or change link order.
- The mobile menu uses a native button, updates aria-expanded, focuses the first navigation link when opened, and Escape closes it and returns focus to the button. Breakpoint changes also reset the menu state and move focus when needed.
- The shared layout uses a skip link and programmatically focusable main content; no positive tabindex values were found.
- WCAG 1.4.11 AA: the gold active marker on the composed hover surface passes at 5.11:1.
- WCAG 1.4.3 AA: sidebar text combinations, including inherited Admin colors, pass; sampled ratios range from 6.60:1 to 11.83:1.
- WCAG 1.4.3 AA: white active/hover link text on the composed hover surface passes at 9.01:1.
- WCAG 2.4.7 AA: sidebar links inherit a visible focus style with a 2px paper-colored shadow ring and a 3px outline.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **contrast-master**: CSS-only review; the translucent gradient and hover layers were assessed using composed colors, not live-browser rendering.
- **keyboard-navigator**: This was a source review; authenticated runtime keyboard scans for the four role pages were not performed.

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
    "contrast-master",
    "keyboard-navigator"
  ],
  "scope": [
    "app\\Shared\\dashboard_layout.php",
    "public\\assets\\css\\dashboard.css",
    "public\\assets\\js\\dashboard.js"
  ],
  "findings": [],
  "passed": [
    "Keyboard focus indicators are provided for links and buttons with a 3px outline and contrasting halo; no outline removal was found.",
    "The Workspace label is noninteractive text before the navigation, so it does not add a tab stop or change link order.",
    "The mobile menu uses a native button, updates aria-expanded, focuses the first navigation link when opened, and Escape closes it and returns focus to the button. Breakpoint changes also reset the menu state and move focus when needed.",
    "The shared layout uses a skip link and programmatically focusable main content; no positive tabindex values were found.",
    "WCAG 1.4.11 AA: the gold active marker on the composed hover surface passes at 5.11:1.",
    "WCAG 1.4.3 AA: sidebar text combinations, including inherited Admin colors, pass; sampled ratios range from 6.60:1 to 11.83:1.",
    "WCAG 1.4.3 AA: white active/hover link text on the composed hover surface passes at 9.01:1.",
    "WCAG 2.4.7 AA: sidebar links inherit a visible focus style with a 2px paper-colored shadow ring and a 3px outline."
  ],
  "notes": [
    {
      "skill": "contrast-master",
      "note": "CSS-only review; the translucent gradient and hover layers were assessed using composed colors, not live-browser rendering."
    },
    {
      "skill": "keyboard-navigator",
      "note": "This was a source review; authenticated runtime keyboard scans for the four role pages were not performed."
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
  "generated": "2026-11-02",
  "template": "web"
}
```
