# Web Accessibility Audit

An audit of web content, run on 2026-11-04. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-04 |
| Scope | 3 items |
| Skills run | contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `public/assets/css/admissions.css`
- `public/assets/css/auth.css`
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

9 checks ran and found nothing.

- Admissions input accent-color resolves to the blue accent in the final override.
- Admissions main-content focus uses a blue outline on the pale surface.
- Dashboard present and late status colors are #126B4A and #8B5700; both exceed 4.5:1 on #F5F7FA.
- Focus-visible indicators retain clear outlines and offset in dashboard, auth, and admissions styles.
- Representative contrast pairs passed manual checks; the sign-in page was visually inspected.
- Reviewed text, semantic status colors, control colors, and focus indicators; no remaining WCAG AA contrast failures were identified.
- The final sidebar focus style uses a dark ring on the pale sidebar and a white offset outline for separation on dark surfaces.
- The shared blue theme and #214A70 hover token are applied in the final dashboard, auth, and admissions overrides.
- The sidebar focus style provides a dark #1B3654 ring and a white outline; the dark ring is visible against the pale sidebar, and the white outline separates on dark surfaces.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **contrast-master**: The automated Playwright contrast scan could not run because Playwright is not installed.
- **keyboard-navigator**: The final sidebar surface is pale; no dark sidebar surface is defined in this theme.
- **keyboard-navigator**: Automated Playwright keyboard scanning could not run because Playwright is not installed.

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
    "public/assets/css/admissions.css",
    "public/assets/css/auth.css",
    "public/assets/css/dashboard.css"
  ],
  "findings": [],
  "passed": [
    "Admissions input accent-color resolves to the blue accent in the final override.",
    "Admissions main-content focus uses a blue outline on the pale surface.",
    "Dashboard present and late status colors are #126B4A and #8B5700; both exceed 4.5:1 on #F5F7FA.",
    "Focus-visible indicators retain clear outlines and offset in dashboard, auth, and admissions styles.",
    "Representative contrast pairs passed manual checks; the sign-in page was visually inspected.",
    "Reviewed text, semantic status colors, control colors, and focus indicators; no remaining WCAG AA contrast failures were identified.",
    "The final sidebar focus style uses a dark ring on the pale sidebar and a white offset outline for separation on dark surfaces.",
    "The shared blue theme and #214A70 hover token are applied in the final dashboard, auth, and admissions overrides.",
    "The sidebar focus style provides a dark #1B3654 ring and a white outline; the dark ring is visible against the pale sidebar, and the white outline separates on dark surfaces."
  ],
  "notes": [
    {
      "skill": "contrast-master",
      "note": "The automated Playwright contrast scan could not run because Playwright is not installed."
    },
    {
      "skill": "keyboard-navigator",
      "note": "The final sidebar surface is pale; no dark sidebar surface is defined in this theme."
    },
    {
      "skill": "keyboard-navigator",
      "note": "Automated Playwright keyboard scanning could not run because Playwright is not installed."
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
  "generated": "2026-11-04",
  "template": "web"
}
```
