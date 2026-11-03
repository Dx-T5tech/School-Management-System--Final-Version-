# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 3 items |
| Skills run | forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Modules/StudentManagement/Students/Controllers/section.php`
- `app/Modules/StudentManagement/Students/Views/section_content.php`
- `public/assets/css/dashboard.css`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 92 out of 100 |
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
| `FORM-ERROR-FOCUS` | 3.3.1 A | app/Modules/StudentManagement/Students/Views/section_content.php:27-29 | Field validation errors do not trigger the form-level error alert or move focus, so users may not know why submission failed. | When profile field errors exist, render a focused error summary with links to affected controls and retain inline error associations. |
| `KEY-FOCUS-001` | 2.4.3 A | app/Modules/StudentManagement/Students/Views/section_content.php:28 | After profile validation errors reload the page, keyboard focus is not moved to the error or form. | Focus the error summary or first invalid field after a rejected profile update. |

## Remediation priorities

The remaining 2 moderate and minor findings can follow in normal work.

## What passed

5 checks ran and found nothing.

- Dashboard CSS provides visible focus indicators for links, buttons, inputs, and textareas.
- Editable controls have associated labels and appropriate autocomplete values.
- Inline field errors are associated with their controls and set aria-invalid.
- Profile fields and actions follow a sensible DOM tab order; Cancel is a native link.
- Save confirmation is rendered with role=status; successful saves use a POST/redirect/GET flow.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **forms-specialist**: Assistive-technology announcement of the status message after the full-page redirect was not runtime-tested.

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
    "keyboard-navigator"
  ],
  "scope": [
    "app/Modules/StudentManagement/Students/Controllers/section.php",
    "app/Modules/StudentManagement/Students/Views/section_content.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [
    {
      "rule": "FORM-ERROR-FOCUS",
      "wcag": "3.3.1 A",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:27-29",
      "summary": "Field validation errors do not trigger the form-level error alert or move focus, so users may not know why submission failed.",
      "fix": "When profile field errors exist, render a focused error summary with links to affected controls and retain inline error associations.",
      "evidence": "The alert was conditional on $profileError while field errors were rendered separately.",
      "foundBy": [
        "forms-specialist"
      ]
    },
    {
      "rule": "KEY-FOCUS-001",
      "wcag": "2.4.3 A",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:28",
      "summary": "After profile validation errors reload the page, keyboard focus is not moved to the error or form.",
      "fix": "Focus the error summary or first invalid field after a rejected profile update.",
      "evidence": "The error message had tabindex=\"-1\", but was not selected by dashboard error-focus logic.",
      "foundBy": [
        "keyboard-navigator"
      ]
    }
  ],
  "passed": [
    "Dashboard CSS provides visible focus indicators for links, buttons, inputs, and textareas.",
    "Editable controls have associated labels and appropriate autocomplete values.",
    "Inline field errors are associated with their controls and set aria-invalid.",
    "Profile fields and actions follow a sensible DOM tab order; Cancel is a native link.",
    "Save confirmation is rendered with role=status; successful saves use a POST/redirect/GET flow."
  ],
  "notes": [
    {
      "skill": "forms-specialist",
      "note": "Assistive-technology announcement of the status message after the full-page redirect was not runtime-tested."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 2,
    "minor": 0
  },
  "components": [],
  "score": 92,
  "grade": "A",
  "penalty": 4,
  "checks": 10,
  "generated": "2026-11-03",
  "template": "web"
}
```
