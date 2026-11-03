# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 5 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 22 |

Audited:

- `app/Core/super_admin.php`
- `app/Shared/dashboard_layout.php`
- `app/Shared/super_admin_section_content.php`
- `public/assets/css/dashboard.css`
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
| Fixed | 3 |
| New | 0 |
| Persistent | 0 |
| Regressed | 0 |

Score moved from 96 to 100.

## What passed

22 checks ran and found nothing.

- CSRF failures receive visible alert feedback that tells users to reload and retry.
- Heading structure: one page H1, followed by the SMTP content H2 and its configuration H3; automated heading check found no issues.
- Landmarks: one main landmark, a labeled dashboard navigation, and page-level header and footer are present.
- Page title: the SMTP section title resolves to SMTP Settings - ISMS and matches the visible page heading.
- SMTP failure feedback gives actionable recovery steps and directs technical details to the PHP/Apache error log.
- SMTP readiness states are described in text and are not conveyed by color alone.
- Shared dashboard styles provide visible focus indicators for interactive elements.
- Skip link: its #main-content target exists on the main landmark, which has tabindex=-1.
- Static SMTP readiness messages no longer use role=status.
- Success feedback uses a status role, and SMTP readiness states explain whether email can be sent.
- The POST success message uses a status role, and the error message uses an alert role; both are focused after rendering.
- The SMTP code block is keyboard-focusable and retains the shared visible focus outline.
- The enabled test-email action uses a native submit button in the page’s sequential content order.
- The form contains no visible inputs requiring labels.
- The shared dashboard script focuses the success or error summary after the test-email response.
- The test-email button has visible text and uses the native disabled attribute when SMTP is not ready.
- The test-email form uses a native submit button with a clear accessible name.
- WCAG 1.4.11: The shared accent focus outline is 5.32:1 against the white page background.
- WCAG 1.4.3: Enabled button text is 5.32:1 and disabled button text is 5.37:1 against their backgrounds.
- WCAG 1.4.3: SMTP notice text passes AA; info text is 4.52:1 and success text is 4.88:1 against their backgrounds.
- WCAG 1.4.3: Shared muted text is 5.68:1 on white; code-block text is 4.76:1 on its background.
- When SMTP is unavailable, the disabled button is accompanied by visible configuration instructions.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Runtime keyboard scanning was unavailable because Playwright was not installed.
- **contrast-master**: Contrast was checked from declared CSS colors; no rendered browser scan was performed.

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
    "app/Core/super_admin.php",
    "app/Shared/dashboard_layout.php",
    "app/Shared/super_admin_section_content.php",
    "public/assets/css/dashboard.css",
    "public/super-admin-section.php"
  ],
  "findings": [],
  "passed": [
    "CSRF failures receive visible alert feedback that tells users to reload and retry.",
    "Heading structure: one page H1, followed by the SMTP content H2 and its configuration H3; automated heading check found no issues.",
    "Landmarks: one main landmark, a labeled dashboard navigation, and page-level header and footer are present.",
    "Page title: the SMTP section title resolves to SMTP Settings - ISMS and matches the visible page heading.",
    "SMTP failure feedback gives actionable recovery steps and directs technical details to the PHP/Apache error log.",
    "SMTP readiness states are described in text and are not conveyed by color alone.",
    "Shared dashboard styles provide visible focus indicators for interactive elements.",
    "Skip link: its #main-content target exists on the main landmark, which has tabindex=-1.",
    "Static SMTP readiness messages no longer use role=status.",
    "Success feedback uses a status role, and SMTP readiness states explain whether email can be sent.",
    "The POST success message uses a status role, and the error message uses an alert role; both are focused after rendering.",
    "The SMTP code block is keyboard-focusable and retains the shared visible focus outline.",
    "The enabled test-email action uses a native submit button in the page’s sequential content order.",
    "The form contains no visible inputs requiring labels.",
    "The shared dashboard script focuses the success or error summary after the test-email response.",
    "The test-email button has visible text and uses the native disabled attribute when SMTP is not ready.",
    "The test-email form uses a native submit button with a clear accessible name.",
    "WCAG 1.4.11: The shared accent focus outline is 5.32:1 against the white page background.",
    "WCAG 1.4.3: Enabled button text is 5.32:1 and disabled button text is 5.37:1 against their backgrounds.",
    "WCAG 1.4.3: SMTP notice text passes AA; info text is 4.52:1 and success text is 4.88:1 against their backgrounds.",
    "WCAG 1.4.3: Shared muted text is 5.68:1 on white; code-block text is 4.76:1 on its background.",
    "When SMTP is unavailable, the disabled button is accompanied by visible configuration instructions."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Runtime keyboard scanning was unavailable because Playwright was not installed."
    },
    {
      "skill": "contrast-master",
      "note": "Contrast was checked from declared CSS colors; no rendered browser scan was performed."
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
  "checks": 22,
  "generated": "2026-11-03",
  "template": "web"
}
```
