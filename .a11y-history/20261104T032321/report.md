# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 3 items |
| Skills run | forms-specialist, keyboard-navigator, live-region-controller |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/admin_section_content.php`
- `public/admin-section.php`
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

7 checks ran and found nothing.

- Curriculum subject-name and units fields have visible labels and are optional.
- Dynamic subject labels use a separate subjectName value rather than parsing the displayed label.
- Teaching-assignment field order is section, semester, then subject.
- The existing role=status region announces the available curriculum subject count and selected subject label, including units, when section, semester, or subject changes.
- The status region reports available-subject count and selected subject.
- Validation errors are associated with their fields and use aria-invalid; units and name have server-side validation.
- Visible help text is programmatically associated with the subject, name, and units controls.

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
    "live-region-controller"
  ],
  "scope": [
    "app/Shared/admin_section_content.php",
    "public/admin-section.php",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    "Curriculum subject-name and units fields have visible labels and are optional.",
    "Dynamic subject labels use a separate subjectName value rather than parsing the displayed label.",
    "Teaching-assignment field order is section, semester, then subject.",
    "The existing role=status region announces the available curriculum subject count and selected subject label, including units, when section, semester, or subject changes.",
    "The status region reports available-subject count and selected subject.",
    "Validation errors are associated with their fields and use aria-invalid; units and name have server-side validation.",
    "Visible help text is programmatically associated with the subject, name, and units controls."
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
  "generated": "2026-11-03",
  "template": "web"
}
```
