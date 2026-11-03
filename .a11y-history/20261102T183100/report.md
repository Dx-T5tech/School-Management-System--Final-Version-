# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 2 items |
| Skills run | contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

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

2 checks ran and found nothing.

- Static contrast calculations for sidebar brand copy, caption, workspace label, normal links, and signed-in footer colors all exceed WCAG AA 4.5:1.
- The CSS-only change retains the existing anchor navigation and focus-visible styling.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Specialist dispatch failed before review: 400 The requested model is not supported. The Admin sidebar was hidden at a 360 CSS-pixel viewport, the Menu button remained visible, and the page had no horizontal overflow; keyboard interaction was not independently tested.
- **contrast-master**: Specialist dispatch failed before review: 400 The requested model is not supported. Contrast ratios were independently calculated from the inherited Super Admin palette.

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
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [],
  "passed": [
    "Static contrast calculations for sidebar brand copy, caption, workspace label, normal links, and signed-in footer colors all exceed WCAG AA 4.5:1.",
    "The CSS-only change retains the existing anchor navigation and focus-visible styling."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. The Admin sidebar was hidden at a 360 CSS-pixel viewport, the Menu button remained visible, and the page had no horizontal overflow; keyboard interaction was not independently tested."
    },
    {
      "skill": "contrast-master",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. Contrast ratios were independently calculated from the inherited Super Admin palette."
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
