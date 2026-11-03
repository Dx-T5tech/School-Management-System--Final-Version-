# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 8 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 11 |

Audited:

- `app\Shared\dashboard_layout.php`
- `app\Shared\student_enrollment_content.php`
- `public\assets\css\auth.css`
- `public\assets\css\dashboard.css`
- `public\assets\js\auth.js`
- `public\assets\js\dashboard.js`
- `public\login.php`
- `public\student-enrollment.php`

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

11 checks ran and found nothing.

- Empty-state structure
- Error and status structure
- Heading hierarchy
- Invalid enrollment selection receives focus through dashboard.js, and the selector has a visible focus style.
- Landmarks
- Login errors receive focus through auth.js, and the focused error summary has a visible focus style.
- Page title
- The enrollment form is named by its visible heading through aria-labelledby.
- The field-error summary now links to the invalid class-section field.
- The invalid select has aria-invalid="true" and aria-describedby references the visible field error.
- The student error notice border contrast is 5.5:1 against #fbefed, exceeding the 3:1 minimum.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Confirmed by code inspection; runtime keyboard verification was unavailable because Playwright is not installed.

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
    "forms-specialist",
    "keyboard-navigator"
  ],
  "scope": [
    "app\\Shared\\dashboard_layout.php",
    "app\\Shared\\student_enrollment_content.php",
    "public\\assets\\css\\auth.css",
    "public\\assets\\css\\dashboard.css",
    "public\\assets\\js\\auth.js",
    "public\\assets\\js\\dashboard.js",
    "public\\login.php",
    "public\\student-enrollment.php"
  ],
  "findings": [],
  "passed": [
    "Empty-state structure",
    "Error and status structure",
    "Heading hierarchy",
    "Invalid enrollment selection receives focus through dashboard.js, and the selector has a visible focus style.",
    "Landmarks",
    "Login errors receive focus through auth.js, and the focused error summary has a visible focus style.",
    "Page title",
    "The enrollment form is named by its visible heading through aria-labelledby.",
    "The field-error summary now links to the invalid class-section field.",
    "The invalid select has aria-invalid=\"true\" and aria-describedby references the visible field error.",
    "The student error notice border contrast is 5.5:1 against #fbefed, exceeding the 3:1 minimum."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Confirmed by code inspection; runtime keyboard verification was unavailable because Playwright is not installed."
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
  "checks": 11,
  "generated": "2026-11-02",
  "template": "web"
}
```
