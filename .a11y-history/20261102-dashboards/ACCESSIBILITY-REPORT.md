# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 3 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/dashboard_layout.php`
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

10 checks ran and found nothing.

- .responsibility-number text (#76521f on #fffefa) measures 6.94:1 in both themes.
- Current-location state updates on initial load and fragment changes.
- Focus indicators use a 3px navy outline and paper-colored ring; the ring contrasts 13.2:1 against the navy sidebar, and the outline contrasts at least 13.2:1 against light surfaces. These styles are shared by both themes.
- Footer text (#5d6a72 on #f5f4ef) measures 5.06:1 in both themes.
- Heading structure has one H1 and a valid H1–H2–H3 hierarchy with no skipped levels.
- Sidebar fragment-link activation closes the mobile menu and focuses the destination.
- Skip link targets the main element, which has tabindex="-1" and a visible focus style.
- The brand link’s accessible name includes its visible text.
- The page-level footer is outside main and uses a native footer element, exposing the contentinfo landmark.
- When the viewport crosses to mobile while focus is in the sidebar, :focus-within keeps it displayed until the resize handler moves focus to the visible menu toggle.

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
    "contrast-master",
    "keyboard-navigator"
  ],
  "scope": [
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    ".responsibility-number text (#76521f on #fffefa) measures 6.94:1 in both themes.",
    "Current-location state updates on initial load and fragment changes.",
    "Focus indicators use a 3px navy outline and paper-colored ring; the ring contrasts 13.2:1 against the navy sidebar, and the outline contrasts at least 13.2:1 against light surfaces. These styles are shared by both themes.",
    "Footer text (#5d6a72 on #f5f4ef) measures 5.06:1 in both themes.",
    "Heading structure has one H1 and a valid H1–H2–H3 hierarchy with no skipped levels.",
    "Sidebar fragment-link activation closes the mobile menu and focuses the destination.",
    "Skip link targets the main element, which has tabindex=\"-1\" and a visible focus style.",
    "The brand link’s accessible name includes its visible text.",
    "The page-level footer is outside main and uses a native footer element, exposing the contentinfo landmark.",
    "When the viewport crosses to mobile while focus is in the sidebar, :focus-within keeps it displayed until the resize handler moves focus to the visible menu toggle."
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
  "generated": "2026-11-02",
  "template": "web"
}
```
