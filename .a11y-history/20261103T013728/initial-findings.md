# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 6 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator, live-region-controller, tables-data-specialist |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 28 |

Audited:

- `app/Core/finance.php`
- `app/Modules/StudentManagement/Students/Controllers/section.php`
- `app/Modules/StudentManagement/Students/Views/section_content.php`
- `app/Shared/dashboard_layout.php`
- `public/assets/css/dashboard.css`
- `public/assets/js/dashboard.js`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 90 out of 100 |
| Grade | A |
| Findings | 8 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 7 | Real friction, workaround exists |
| minor | 1 | Noticeable but rarely blocking |

## Findings

8 findings, ordered by severity then by rule.

### Moderate (7)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `FORM-ERROR-FOCUS` | n/a | public/assets/js/dashboard.js:13-18 | When payment fields have errors but no form-level error is rendered, focus has no error target and does not move to the first invalid field. | After checking [data-error-focus], focus the first invalid payment control before falling back to errorSummary or statusSummary. |
| `FORM-ERROR-RECOVERY-001` | 3.3.1 A | app/Modules/StudentManagement/Students/Views/section_content.php:110,122-170; app/Modules/StudentManagement/Students/Controllers/section.php:188-203 | Payment field errors render inline without a summary or focus target, so after submission users must locate each error in the reloaded form. | Render an error summary with links to affected controls, focus it after the response, and keep inline messages and their aria-invalid and aria-describedby associations. |
| `FORM-REQUIRED-001` | 3.3.2 AA | app/Modules/StudentManagement/Students/Views/section_content.php:117-174 | Required payment fields have no visible required-state convention, and the tuition term is conditionally mandatory without a programmatically exposed required state. | Add a visible required cue or short form instruction identifying required fields. When a tuition fee is selected, expose the tuition-term field as required and state that it is optional for other fees. |
| `KEYBOARD-001` | 2.1.1 A | app/Modules/StudentManagement/Students/Views/section_content.php:235,262 | The financial report's horizontally scrollable fee and payment tables cannot be scrolled using the keyboard. | Make both report table scroll containers keyboard-focusable with tabindex=0 and give each an accessible name. |
| `LIVE-001` | 4.1.3 AA | app/Modules/StudentManagement/Students/Views/section_content.php:109,285 | Post-submit confirmations are rendered as populated status regions on the redirected page, so assistive technology may not announce that the payment request or read-state change succeeded. | After redirect, move focus to the confirmation or populate a status region already present in the page shell; keep the confirmation text in page content. |
| `LIVE-002` | 4.1.3 AA | app/Modules/StudentManagement/Students/Views/section_content.php:110 | Payment errors appear in a newly rendered alert after submission without focus being moved to the error summary, so the error may not be announced or encountered promptly. | Move focus to the existing error summary or first invalid field after an unsuccessful submission while preserving field-level descriptions and invalid states. |
| `TABLE-ROW-HEADER-001` | 1.3.1 A | app/Modules/StudentManagement/Students/Views/section_content.php:190,269 | Payment-history rows have no row header to identify which payment the remaining cell values describe. | Use a stable payment identifier, such as the receipt number, as a th scope=row in each history table. |

### Minor (1)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `ARIA-STATUS-001` | n/a | app/Modules/StudentManagement/Students/Views/section_content.php:105,227 | Static unlinked-payment notices use role=status despite not reporting a live update, adding unnecessary status-region semantics. | Remove role=status from static financial notices while retaining it for action-result messages. |

## Remediation priorities

The remaining 8 moderate and minor findings can follow in normal work.

## What passed

20 checks ran and found nothing.

- Decorative SVGs and visual-only icons or initials are hidden from assistive technology; no images requiring alt text appear in scope.
- Fee reminders and payment-status notifications persist and render readable titles, messages, timestamps, and types.
- Fee-balance and financial-report tables have captions, scoped column headers, and scoped fee row headers.
- Finance progress indicator: #3d7188 against #e4e8f0 is 4.37:1, exceeding the 3:1 non-text threshold.
- Focus indicators are defined for links, buttons, payment inputs/selects, and scrollable tables.
- Notice text: standard 9.65:1 and error 8.99:1.
- Notification actions provide status feedback and retry guidance for an expired security token.
- Payment and notification controls have programmatically associated labels and named submit buttons.
- Payment field errors use aria-invalid and aria-describedby associations.
- Payment input borders: #7d8a8c on #fffefa is 3.54:1, exceeding the 3:1 non-text threshold.
- Payment-history tables have captions and scoped column headers.
- Sidebar notification badge: white on #a33b37 is 6.49:1.
- Status badge text: default 9.24:1, success 8.6:1, attention 7.18:1; all exceed 4.5:1.
- Text contrast: #526170 on #fffefa is 6.3:1; #1d2b37 on #fffefa is 14.32:1.
- The dashboard navigation and sidebar are named, and page-level header, main, and footer landmarks are present.
- The document has an English language attribute, a main landmark, a skip link, and a title template.
- The shared layout provides one page-level H1; finance-page headings follow it with H2 sections and H3 subsections.
- Unread counts are included in the notification navigation link's accessible name, and the visual badge is hidden from assistive technology as duplicate content.
- Unread counts are included in the notification navigation link's accessible name; the visual badge is hidden as duplicate content.
- Unread notifications retain text and a Mark as read action in addition to the background distinction; status badges include text labels.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Static source review only; no runtime keyboard or print-dialog scan was performed.
- **alt-text-headings**: Actual page-title and H1 text are supplied dynamically outside these files, so their accuracy and uniqueness across finance pages could not be verified.
- **forms-specialist**: Autocomplete was reviewed; collected fields do not require standard personal-information or payment-credential autocomplete tokens.
- **contrast-master**: Review was based on source CSS and markup; no live-page browser sampling or forced-colors runtime test was performed.

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
    "keyboard-navigator",
    "live-region-controller",
    "tables-data-specialist"
  ],
  "scope": [
    "app/Core/finance.php",
    "app/Modules/StudentManagement/Students/Controllers/section.php",
    "app/Modules/StudentManagement/Students/Views/section_content.php",
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [
    {
      "rule": "FORM-ERROR-FOCUS",
      "wcag": "n/a",
      "severity": "moderate",
      "confidence": "medium",
      "location": "public/assets/js/dashboard.js:13-18",
      "summary": "When payment fields have errors but no form-level error is rendered, focus has no error target and does not move to the first invalid field.",
      "fix": "After checking [data-error-focus], focus the first invalid payment control before falling back to errorSummary or statusSummary.",
      "evidence": "Payment field errors set aria-invalid and aria-describedby, but the script only focuses data-error-focus, data-error-summary, or data-status-summary.",
      "foundBy": [
        "aria-specialist"
      ]
    },
    {
      "rule": "FORM-ERROR-RECOVERY-001",
      "wcag": "3.3.1 A",
      "severity": "moderate",
      "confidence": "medium",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:110,122-170; app/Modules/StudentManagement/Students/Controllers/section.php:188-203",
      "summary": "Payment field errors render inline without a summary or focus target, so after submission users must locate each error in the reloaded form.",
      "fix": "Render an error summary with links to affected controls, focus it after the response, and keep inline messages and their aria-invalid and aria-describedby associations.",
      "evidence": "The controller populates paymentFieldErrors; the view rendered inline errors while the alert summary appeared only for paymentError.",
      "foundBy": [
        "forms-specialist"
      ]
    },
    {
      "rule": "FORM-REQUIRED-001",
      "wcag": "3.3.2 AA",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:117-174",
      "summary": "Required payment fields have no visible required-state convention, and the tuition term is conditionally mandatory without a programmatically exposed required state.",
      "fix": "Add a visible required cue or short form instruction identifying required fields. When a tuition fee is selected, expose the tuition-term field as required and state that it is optional for other fees.",
      "evidence": "Fee, Amount, and Payment method used required; Tuition term did not, and there was no visible required-field convention.",
      "foundBy": [
        "forms-specialist"
      ]
    },
    {
      "rule": "KEYBOARD-001",
      "wcag": "2.1.1 A",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:235,262",
      "summary": "The financial report's horizontally scrollable fee and payment tables cannot be scrolled using the keyboard.",
      "fix": "Make both report table scroll containers keyboard-focusable with tabindex=0 and give each an accessible name.",
      "evidence": "Both div.student-table-scroll wrappers lacked tabindex while dashboard.css applied overflow-x: auto.",
      "foundBy": [
        "keyboard-navigator"
      ]
    },
    {
      "rule": "LIVE-001",
      "wcag": "4.1.3 AA",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:109,285",
      "summary": "Post-submit confirmations are rendered as populated status regions on the redirected page, so assistive technology may not announce that the payment request or read-state change succeeded.",
      "fix": "After redirect, move focus to the confirmation or populate a status region already present in the page shell; keep the confirmation text in page content.",
      "evidence": "Conditional role=status elements render a session flash after it is read and unset.",
      "foundBy": [
        "live-region-controller"
      ]
    },
    {
      "rule": "LIVE-002",
      "wcag": "4.1.3 AA",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:110",
      "summary": "Payment errors appear in a newly rendered alert after submission without focus being moved to the error summary, so the error may not be announced or encountered promptly.",
      "fix": "Move focus to the existing error summary or first invalid field after an unsuccessful submission while preserving field-level descriptions and invalid states.",
      "evidence": "The conditional role=alert had tabindex=-1, but no focus management was shown in scope.",
      "foundBy": [
        "live-region-controller"
      ]
    },
    {
      "rule": "TABLE-ROW-HEADER-001",
      "wcag": "1.3.1 A",
      "severity": "moderate",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:190,269",
      "summary": "Payment-history rows have no row header to identify which payment the remaining cell values describe.",
      "fix": "Use a stable payment identifier, such as the receipt number, as a th scope=row in each history table.",
      "evidence": "Each payment row began with td for payment_date, and all remaining cells were also td.",
      "component": "Payment history tables",
      "foundBy": [
        "tables-data-specialist"
      ]
    },
    {
      "rule": "ARIA-STATUS-001",
      "wcag": "n/a",
      "severity": "minor",
      "confidence": "medium",
      "location": "app/Modules/StudentManagement/Students/Views/section_content.php:105,227",
      "summary": "Static unlinked-payment notices use role=status despite not reporting a live update, adding unnecessary status-region semantics.",
      "fix": "Remove role=status from static financial notices while retaining it for action-result messages.",
      "evidence": "Unlinked completed-payment notices were rendered as role=status.",
      "foundBy": [
        "aria-specialist"
      ]
    }
  ],
  "passed": [
    "Decorative SVGs and visual-only icons or initials are hidden from assistive technology; no images requiring alt text appear in scope.",
    "Fee reminders and payment-status notifications persist and render readable titles, messages, timestamps, and types.",
    "Fee-balance and financial-report tables have captions, scoped column headers, and scoped fee row headers.",
    "Finance progress indicator: #3d7188 against #e4e8f0 is 4.37:1, exceeding the 3:1 non-text threshold.",
    "Focus indicators are defined for links, buttons, payment inputs/selects, and scrollable tables.",
    "Notice text: standard 9.65:1 and error 8.99:1.",
    "Notification actions provide status feedback and retry guidance for an expired security token.",
    "Payment and notification controls have programmatically associated labels and named submit buttons.",
    "Payment field errors use aria-invalid and aria-describedby associations.",
    "Payment input borders: #7d8a8c on #fffefa is 3.54:1, exceeding the 3:1 non-text threshold.",
    "Payment-history tables have captions and scoped column headers.",
    "Sidebar notification badge: white on #a33b37 is 6.49:1.",
    "Status badge text: default 9.24:1, success 8.6:1, attention 7.18:1; all exceed 4.5:1.",
    "Text contrast: #526170 on #fffefa is 6.3:1; #1d2b37 on #fffefa is 14.32:1.",
    "The dashboard navigation and sidebar are named, and page-level header, main, and footer landmarks are present.",
    "The document has an English language attribute, a main landmark, a skip link, and a title template.",
    "The shared layout provides one page-level H1; finance-page headings follow it with H2 sections and H3 subsections.",
    "Unread counts are included in the notification navigation link's accessible name, and the visual badge is hidden from assistive technology as duplicate content.",
    "Unread counts are included in the notification navigation link's accessible name; the visual badge is hidden as duplicate content.",
    "Unread notifications retain text and a Mark as read action in addition to the background distinction; status badges include text labels."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Static source review only; no runtime keyboard or print-dialog scan was performed."
    },
    {
      "skill": "alt-text-headings",
      "note": "Actual page-title and H1 text are supplied dynamically outside these files, so their accuracy and uniqueness across finance pages could not be verified."
    },
    {
      "skill": "forms-specialist",
      "note": "Autocomplete was reviewed; collected fields do not require standard personal-information or payment-credential autocomplete tokens."
    },
    {
      "skill": "contrast-master",
      "note": "Review was based on source CSS and markup; no live-page browser sampling or forced-colors runtime test was performed."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 7,
    "minor": 1
  },
  "components": [],
  "score": 90,
  "grade": "A",
  "penalty": 13.5,
  "checks": 28,
  "generated": "2026-11-02",
  "template": "web"
}
```
