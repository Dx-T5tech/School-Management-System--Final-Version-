# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 3 items |
| Skills run | aria-specialist, contrast-master, forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 21 |

Audited:

- `app/Shared/admin_section_content.php`
- `public/admin-section.php`
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

21 checks ran and found nothing.

- Conflict acknowledgement forms and manual report controls use native form elements; report fields have associated labels.
- Conflict controls and focus indicators retain visible contrast.
- Conflict mode navigation has a distinct label and marks the active mode with aria-current.
- Conflict notices use status and alert roles.
- Conflict tables have captions and scoped column headers; each automatic conflict row has a time row header.
- Conflict-mode links, acknowledged visibility links, re-scan, and report links are native links or buttons and follow a logical DOM order.
- Controls use native required attributes; the reason has maxlength 1000 and matching server-side validation.
- Each schedule select and the reason textarea has an explicitly associated label and visible Required marker; a required-fields note is provided.
- Manual form validation errors are visible and associated with invalid controls using aria-invalid and aria-describedby.
- Policy success and warning states use text labels in addition to color.
- Required indicators and field-specific validation errors are visible; invalid fields receive associated descriptions and focus.
- Required indicators and validation errors are visibly labeled and use high-contrast text.
- The Scheduling policy h3 retains readable sizing and contrast.
- The Scheduling policy label is a heading and correctly labels its section.
- The Scheduling policy section is labelled by its visible h3.
- The Scheduling policy title uses a correctly nested h3.
- The current mode has an underline cue in addition to its color treatment.
- The first invalid control receives autofocus, and submitted values are preserved on validation errors.
- The selected conflict mode has an underline cue in addition to aria-current.
- Validation failures provide visible field-specific errors associated through aria-describedby and aria-invalid.
- Visible focus styling is provided for links, buttons, form controls, and focusable conflict-table scroll containers.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Source inspection only; live keyboard behavior was not runtime-tested.

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
    "contrast-master",
    "forms-specialist",
    "keyboard-navigator"
  ],
  "scope": [
    "app/Shared/admin_section_content.php",
    "public/admin-section.php",
    "public/assets/css/dashboard.css"
  ],
  "findings": [],
  "passed": [
    "Conflict acknowledgement forms and manual report controls use native form elements; report fields have associated labels.",
    "Conflict controls and focus indicators retain visible contrast.",
    "Conflict mode navigation has a distinct label and marks the active mode with aria-current.",
    "Conflict notices use status and alert roles.",
    "Conflict tables have captions and scoped column headers; each automatic conflict row has a time row header.",
    "Conflict-mode links, acknowledged visibility links, re-scan, and report links are native links or buttons and follow a logical DOM order.",
    "Controls use native required attributes; the reason has maxlength 1000 and matching server-side validation.",
    "Each schedule select and the reason textarea has an explicitly associated label and visible Required marker; a required-fields note is provided.",
    "Manual form validation errors are visible and associated with invalid controls using aria-invalid and aria-describedby.",
    "Policy success and warning states use text labels in addition to color.",
    "Required indicators and field-specific validation errors are visible; invalid fields receive associated descriptions and focus.",
    "Required indicators and validation errors are visibly labeled and use high-contrast text.",
    "The Scheduling policy h3 retains readable sizing and contrast.",
    "The Scheduling policy label is a heading and correctly labels its section.",
    "The Scheduling policy section is labelled by its visible h3.",
    "The Scheduling policy title uses a correctly nested h3.",
    "The current mode has an underline cue in addition to its color treatment.",
    "The first invalid control receives autofocus, and submitted values are preserved on validation errors.",
    "The selected conflict mode has an underline cue in addition to aria-current.",
    "Validation failures provide visible field-specific errors associated through aria-describedby and aria-invalid.",
    "Visible focus styling is provided for links, buttons, form controls, and focusable conflict-table scroll containers."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Source inspection only; live keyboard behavior was not runtime-tested."
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
  "checks": 21,
  "generated": "2026-11-03",
  "template": "web"
}
```
