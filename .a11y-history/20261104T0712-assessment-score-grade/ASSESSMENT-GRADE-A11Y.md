# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 4 items |
| Skills run | aria-specialist, forms-specialist, keyboard-navigator, live-region-controller |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/teacher_section_content.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/dashboard.js`
- `public/teacher-section.php`

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

- Automatic components are readonly native inputs, and the calculated output remains available through the polite status region.
- Browser validation confirmed missing Exam/Quiz scores are rejected without creating a grade row.
- Browser verification confirmed the status announces Exam 80.0%, Quizzes 60.0%, and a calculated Prelim of 77.0%.
- Calculator controls follow logical source and visual order.
- Exam and Quiz are labeled readonly calculated inputs; missing score errors are associated with the relevant fields.
- Native fieldset and legend group the calculator; inputs have associated labels and errors reference their descriptions.
- No calculator-specific keyboard trap or focus-order defect was found in the code review.
- Readonly automatic component fields remain focusable and use the existing visible focus style.
- Term-grade controls have explicit labels; Attendance and Recitation remain required native inputs.
- The existing polite status now summarizes automatic Exam/Quiz percentages with calculated-grade or missing-score information.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **forms-specialist**: A real assessment-score save was not performed because no eligible local Exam/Quiz scores existed; the complete score-save-to-draft-resume path was code-reviewed.
- **keyboard-navigator**: A dedicated runtime Tab scan was not performed.

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
    "forms-specialist",
    "keyboard-navigator",
    "live-region-controller"
  ],
  "scope": [
    "app/Shared/teacher_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js",
    "public/teacher-section.php"
  ],
  "findings": [],
  "passed": [
    "Automatic components are readonly native inputs, and the calculated output remains available through the polite status region.",
    "Browser validation confirmed missing Exam/Quiz scores are rejected without creating a grade row.",
    "Browser verification confirmed the status announces Exam 80.0%, Quizzes 60.0%, and a calculated Prelim of 77.0%.",
    "Calculator controls follow logical source and visual order.",
    "Exam and Quiz are labeled readonly calculated inputs; missing score errors are associated with the relevant fields.",
    "Native fieldset and legend group the calculator; inputs have associated labels and errors reference their descriptions.",
    "No calculator-specific keyboard trap or focus-order defect was found in the code review.",
    "Readonly automatic component fields remain focusable and use the existing visible focus style.",
    "Term-grade controls have explicit labels; Attendance and Recitation remain required native inputs.",
    "The existing polite status now summarizes automatic Exam/Quiz percentages with calculated-grade or missing-score information."
  ],
  "notes": [
    {
      "skill": "forms-specialist",
      "note": "A real assessment-score save was not performed because no eligible local Exam/Quiz scores existed; the complete score-save-to-draft-resume path was code-reviewed."
    },
    {
      "skill": "keyboard-navigator",
      "note": "A dedicated runtime Tab scan was not performed."
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
