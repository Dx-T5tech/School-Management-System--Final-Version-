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
| Checks counted | 21 |

Audited:

- `app/Core/auth.php`
- `public/account.php`
- `public/assets/css/auth.css`
- `public/assets/css/dashboard.css`
- `public/assets/js/auth.js`
- `public/login.php`
- `public/logout.php`
- `public/setup.php`

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

- Autocomplete and paste support remain enabled.
- Both forms show that fields are required and retain native required validation.
- Browser accessibility snapshot exposes the sign-in heading, labels, fields, password toggle, and submit action with names.
- Default form borders measure 3.54:1 against the input surface.
- Error and success status text use high-contrast color combinations.
- Focus indicators measure at least 3:1 against adjacent surfaces.
- Headings describe each page task and retain a logical hierarchy.
- Helper text measures 5.77:1 against the page background.
- Links and form actions have clear text.
- Login and setup controls have a sequential DOM order and visible focus styles.
- Login and setup inputs have visible labels.
- Login, setup, and account pages have descriptive titles and English document language.
- Pages have semantic main landmarks and skip links.
- Password length guidance is associated with both setup password fields; password values are not repopulated.
- Setup field error descriptions and password guidance reference their matching IDs.
- Setup validation identifies invalid fields, associates specific messages, and preserves escaped non-password values.
- Skip links move focus to main content and the target has a visible focus indicator.
- Submission errors use alert semantics, and success information uses status semantics.
- The account fallback sign-out button submits by keyboard.
- The password-toggle control works with native button activation.
- The show/hide password buttons are keyboard operable and update the input type, accessible name, and pressed state.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **aria-specialist**: The shared dashboard layout is absent; the account fallback provides the sign-out control.
- **forms-specialist**: The authenticated account fallback is used because the shared dashboard layout is absent.
- **keyboard-navigator**: The shared dashboard layout is absent, so its original dashboard navigation markup could not be reviewed.
- **contrast-master**: The shared dashboard layout is absent; the account fallback uses the reviewed auth and dashboard stylesheets.
- **alt-text-headings**: The logout endpoint intentionally redirects rather than rendering a separate page.

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
    "app/Core/auth.php",
    "public/account.php",
    "public/assets/css/auth.css",
    "public/assets/css/dashboard.css",
    "public/assets/js/auth.js",
    "public/login.php",
    "public/logout.php",
    "public/setup.php"
  ],
  "findings": [],
  "passed": [
    "Autocomplete and paste support remain enabled.",
    "Both forms show that fields are required and retain native required validation.",
    "Browser accessibility snapshot exposes the sign-in heading, labels, fields, password toggle, and submit action with names.",
    "Default form borders measure 3.54:1 against the input surface.",
    "Error and success status text use high-contrast color combinations.",
    "Focus indicators measure at least 3:1 against adjacent surfaces.",
    "Headings describe each page task and retain a logical hierarchy.",
    "Helper text measures 5.77:1 against the page background.",
    "Links and form actions have clear text.",
    "Login and setup controls have a sequential DOM order and visible focus styles.",
    "Login and setup inputs have visible labels.",
    "Login, setup, and account pages have descriptive titles and English document language.",
    "Pages have semantic main landmarks and skip links.",
    "Password length guidance is associated with both setup password fields; password values are not repopulated.",
    "Setup field error descriptions and password guidance reference their matching IDs.",
    "Setup validation identifies invalid fields, associates specific messages, and preserves escaped non-password values.",
    "Skip links move focus to main content and the target has a visible focus indicator.",
    "Submission errors use alert semantics, and success information uses status semantics.",
    "The account fallback sign-out button submits by keyboard.",
    "The password-toggle control works with native button activation.",
    "The show/hide password buttons are keyboard operable and update the input type, accessible name, and pressed state."
  ],
  "notes": [
    {
      "skill": "aria-specialist",
      "note": "The shared dashboard layout is absent; the account fallback provides the sign-out control."
    },
    {
      "skill": "forms-specialist",
      "note": "The authenticated account fallback is used because the shared dashboard layout is absent."
    },
    {
      "skill": "keyboard-navigator",
      "note": "The shared dashboard layout is absent, so its original dashboard navigation markup could not be reviewed."
    },
    {
      "skill": "contrast-master",
      "note": "The shared dashboard layout is absent; the account fallback uses the reviewed auth and dashboard stylesheets."
    },
    {
      "skill": "alt-text-headings",
      "note": "The logout endpoint intentionally redirects rather than rendering a separate page."
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
  "generated": "2026-11-02",
  "template": "web"
}
```
