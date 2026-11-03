# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 7 items |
| Skills run | alt-text-headings, aria-specialist, forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 16 |

Audited:

- `app/Shared/admin_section_content.php`
- `app/Shared/dashboard_layout.php`
- `app/Shared/super_admin_section_content.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/auth.js`
- `public/assets/js/dashboard.js`
- `public/super-admin-section.php`

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

16 checks ran and found nothing.

- Server validation enforces a minimum of 12 Unicode characters and the UTF-8 byte limit; both are explained in the visible hint and linked field error.
- Success and error summaries are focusable and receive focus through the shared dashboard script.
- Tab order follows the form: password field, show/hide button, then submit button; the control has a visible focus indicator.
- Teacher/Student controls have visible associated labels, native required states where applicable, and relevant autocomplete tokens.
- Teacher/Student field-validation errors receive a generic error message, which renders in a focusable role=alert summary; dashboard.js moves focus to that summary.
- The Super Admin success notice has data-status-summary and tabindex=-1; dashboard.js finds and focuses it.
- The User Management page title is descriptive and includes the ISMS name.
- The account directory table has a descriptive caption and column headings.
- The account form uses native controls with visible focus styles, and its layout collapses to one column at narrow viewport widths.
- The keyboard-operable Show password button keeps its label stable and exposes state through aria-pressed only.
- The page has exactly one page-specific H1 and a sequential heading hierarchy.
- The page has one main landmark, labeled navigation, page header and footer, and a skip link.
- The password toggle keeps Show password as its stable accessible name and updates only aria-pressed when visibility changes.
- The profile-number hint explains when the number is needed; field errors are linked to affected controls and marked invalid.
- The show/hide control is a native type=button, reachable by Tab and operable with Enter or Space without submitting the form.
- aria-controls references the existing school-account-password input ID.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **forms-specialist**: Password recovery is not represented in the scoped account-creation form, so recovery availability at sign-in was not assessed.
- **keyboard-navigator**: Keyboard behavior was reviewed from source; automated Playwright scans could not run because Playwright is unavailable, and the protected account route could not be independently verified in an authenticated runtime session.

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
    "forms-specialist",
    "keyboard-navigator"
  ],
  "scope": [
    "app/Shared/admin_section_content.php",
    "app/Shared/dashboard_layout.php",
    "app/Shared/super_admin_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/auth.js",
    "public/assets/js/dashboard.js",
    "public/super-admin-section.php"
  ],
  "findings": [],
  "passed": [
    "Server validation enforces a minimum of 12 Unicode characters and the UTF-8 byte limit; both are explained in the visible hint and linked field error.",
    "Success and error summaries are focusable and receive focus through the shared dashboard script.",
    "Tab order follows the form: password field, show/hide button, then submit button; the control has a visible focus indicator.",
    "Teacher/Student controls have visible associated labels, native required states where applicable, and relevant autocomplete tokens.",
    "Teacher/Student field-validation errors receive a generic error message, which renders in a focusable role=alert summary; dashboard.js moves focus to that summary.",
    "The Super Admin success notice has data-status-summary and tabindex=-1; dashboard.js finds and focuses it.",
    "The User Management page title is descriptive and includes the ISMS name.",
    "The account directory table has a descriptive caption and column headings.",
    "The account form uses native controls with visible focus styles, and its layout collapses to one column at narrow viewport widths.",
    "The keyboard-operable Show password button keeps its label stable and exposes state through aria-pressed only.",
    "The page has exactly one page-specific H1 and a sequential heading hierarchy.",
    "The page has one main landmark, labeled navigation, page header and footer, and a skip link.",
    "The password toggle keeps Show password as its stable accessible name and updates only aria-pressed when visibility changes.",
    "The profile-number hint explains when the number is needed; field errors are linked to affected controls and marked invalid.",
    "The show/hide control is a native type=button, reachable by Tab and operable with Enter or Space without submitting the form.",
    "aria-controls references the existing school-account-password input ID."
  ],
  "notes": [
    {
      "skill": "forms-specialist",
      "note": "Password recovery is not represented in the scoped account-creation form, so recovery availability at sign-in was not assessed."
    },
    {
      "skill": "keyboard-navigator",
      "note": "Keyboard behavior was reviewed from source; automated Playwright scans could not run because Playwright is unavailable, and the protected account route could not be independently verified in an authenticated runtime session."
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
  "checks": 16,
  "generated": "2026-11-02",
  "template": "web"
}
```
