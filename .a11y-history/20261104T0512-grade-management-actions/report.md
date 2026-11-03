# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 2 items |
| Skills run | keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/teacher_section_content.php`
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

- Form trigger source order is score, then term grade (teacher_section_content.php:14, 40).
- Result panels follow the form panels in source order (teacher_section_content.php:134-141).
- The shared button focus-visible rule gives keyboard-focused triggers a visible outline (dashboard.css:60-65).
- The two-column grade layout preserves row-major order; it collapses to one column at the responsive breakpoint (dashboard.css:2136-2139, 4680-4682).

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Static source review only; runtime-generated trigger behavior was not exercised.

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
    "app/Shared/teacher_section_content.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [],
  "passed": [
    "Form trigger source order is score, then term grade (teacher_section_content.php:14, 40).",
    "Result panels follow the form panels in source order (teacher_section_content.php:134-141).",
    "The shared button focus-visible rule gives keyboard-focused triggers a visible outline (dashboard.css:60-65).",
    "The two-column grade layout preserves row-major order; it collapses to one column at the responsive breakpoint (dashboard.css:2136-2139, 4680-4682)."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Static source review only; runtime-generated trigger behavior was not exercised."
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
