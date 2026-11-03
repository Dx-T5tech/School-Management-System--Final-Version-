# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 3 items |
| Skills run | forms-specialist, keyboard-navigator, runtime-verification |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 13 |

Audited:

- `app/Shared/admin_section_content.php`
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

## What passed

13 checks ran and found nothing.

- A manual Apply filter button remains available when JavaScript or requestSubmit is unavailable.
- At a 360 CSS-pixel viewport, the curriculum grid is one column and the document has no horizontal overflow.
- Changing the selection submits the GET filter automatically when requestSubmit is supported.
- Choosing All programs clears the selection and returns the all-program results.
- Curriculum folders and semesters use native details/summary controls in document order.
- Folder summaries receive a visible focus outline.
- PHP lint and JavaScript syntax checks passed.
- Selecting a program immediately reloads the curriculum with program_id in the URL and shows the selected program.
- The JavaScript-enabled view hides the manual fallback button without removing the no-JavaScript fallback.
- The Program selector has a visible label and a programmatically associated hint.
- The curriculum region, individual program cards, and nested headings have accessible names and a logical heading sequence.
- The empty program card exposes an Open curriculum folder control and a separate Add a subject link.
- The program-card grid collapses to one column at narrow widths.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: The selector and responsive behavior were also verified in the integrated browser.
- **runtime-verification**: The Impeccable detector reported existing accent-border warnings in unrelated dashboard styles; it reported no findings in the new curriculum layout rules.

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
    "forms-specialist",
    "keyboard-navigator",
    "runtime-verification"
  ],
  "scope": [
    "app/Shared/admin_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    "A manual Apply filter button remains available when JavaScript or requestSubmit is unavailable.",
    "At a 360 CSS-pixel viewport, the curriculum grid is one column and the document has no horizontal overflow.",
    "Changing the selection submits the GET filter automatically when requestSubmit is supported.",
    "Choosing All programs clears the selection and returns the all-program results.",
    "Curriculum folders and semesters use native details/summary controls in document order.",
    "Folder summaries receive a visible focus outline.",
    "PHP lint and JavaScript syntax checks passed.",
    "Selecting a program immediately reloads the curriculum with program_id in the URL and shows the selected program.",
    "The JavaScript-enabled view hides the manual fallback button without removing the no-JavaScript fallback.",
    "The Program selector has a visible label and a programmatically associated hint.",
    "The curriculum region, individual program cards, and nested headings have accessible names and a logical heading sequence.",
    "The empty program card exposes an Open curriculum folder control and a separate Add a subject link.",
    "The program-card grid collapses to one column at narrow widths."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "The selector and responsive behavior were also verified in the integrated browser."
    },
    {
      "skill": "runtime-verification",
      "note": "The Impeccable detector reported existing accent-border warnings in unrelated dashboard styles; it reported no findings in the new curriculum layout rules."
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
  "checks": 13,
  "generated": "2026-11-02",
  "template": "web"
}
```
