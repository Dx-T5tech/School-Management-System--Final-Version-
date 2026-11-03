# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 7 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 20 |

Audited:

- `app\Core\student.php`
- `app\Shared\dashboard_layout.php`
- `app\Shared\student_section_content.php`
- `public\assets\css\dashboard.css`
- `public\assets\js\dashboard.js`
- `public\student-dashboard.php`
- `public\student-section.php`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 98 out of 100 |
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
| moderate | 0 | Real friction, workaround exists |
| minor | 2 | Noticeable but rarely blocking |

## Findings

2 findings, ordered by severity then by rule.

### Minor (2)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `ARIA-REGION-OVERUSE` | n/a | app\Shared\student_section_content.php:33,38 | Each populated table creates nested named region landmarks for the same content, adding redundant stops to screen-reader landmark navigation. | Keep the table heading and section, but remove the parent section’s aria-labelledby so it remains a semantic grouping; retain the named, focusable scroll region. |
| `CONTENT-001` | n/a | app\Shared\dashboard_layout.php:135-153 | The Announcements section repeats the latest announcements in both the shared feed and the section table. | Suppress the shared announcement feed on the dedicated student Announcements page so each announcement is presented once. |

## Remediation priorities

The remaining 2 moderate and minor findings can follow in normal work.

## What passed

18 checks ran and found nothing.

- ARIA references in the reviewed templates point to existing IDs.
- Content structure: profile details use a description list; read-only data tables have captions and column headers.
- Heading hierarchy: one page-specific H1, followed by sequential H2/H3 headings.
- Image alternatives: no img elements; SVGs and the brand mark are hidden as decorative.
- Landmarks: one main landmark, a skip link, and named navigation are present.
- Notice text meets WCAG 1.4.3 AA at 9.65:1.
- Page titles: descriptive titles are generated from each page’s title.
- Student navigation marks the active link with aria-current="page".
- Student sidebar text and active-state colors meet WCAG 1.4.3 AA; checked text/background pairs range from 7.49:1 to 15.57:1.
- Student table scroll containers are keyboard-focusable, named regions with visible focus styling.
- Student table text and profile labels meet WCAG 1.4.3 AA; checked pairs range from 5.57:1 to 12.65:1.
- Table regions have accessible names; tables use captions and column headers with scope="col".
- The responsive menu uses a button with aria-expanded, supports Escape to close, and returns focus to the toggle.
- The sidebar navigation, main content, page header, and footer use appropriate landmark semantics and names.
- The skip link precedes the dashboard content and targets a programmatically focusable main landmark.
- The student navigation uses links with valid section keys and marks the active page.
- The student sidebar focus ring contrasts with its dark background at 15.97:1; the active accent contrasts at 9.98:1.
- The student table focus outline contrasts with its light panel background at 14.4:1.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **aria-specialist**: The Menu button has aria-expanded="false" and aria-controls references the sidebar; runtime state updates are outside the requested file scope.
- **keyboard-navigator**: Automated keyboard scans could not run because Playwright is not installed; responsive behavior was reviewed statically.

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
    "keyboard-navigator"
  ],
  "scope": [
    "app\\Core\\student.php",
    "app\\Shared\\dashboard_layout.php",
    "app\\Shared\\student_section_content.php",
    "public\\assets\\css\\dashboard.css",
    "public\\assets\\js\\dashboard.js",
    "public\\student-dashboard.php",
    "public\\student-section.php"
  ],
  "findings": [
    {
      "rule": "ARIA-REGION-OVERUSE",
      "wcag": "n/a",
      "severity": "minor",
      "location": "app\\Shared\\student_section_content.php:33,38",
      "summary": "Each populated table creates nested named region landmarks for the same content, adding redundant stops to screen-reader landmark navigation.",
      "fix": "Keep the table heading and section, but remove the parent section’s aria-labelledby so it remains a semantic grouping; retain the named, focusable scroll region.",
      "evidence": "<section aria-labelledby=\"student-table-heading-…\"> contains <div tabindex=\"0\" role=\"region\" aria-label=\"Scrollable table: …\">",
      "component": "Student section table panels",
      "foundBy": [
        "aria-specialist"
      ]
    },
    {
      "rule": "CONTENT-001",
      "wcag": "n/a",
      "severity": "minor",
      "location": "app\\Shared\\dashboard_layout.php:135-153",
      "summary": "The Announcements section repeats the latest announcements in both the shared feed and the section table.",
      "fix": "Suppress the shared announcement feed on the dedicated student Announcements page so each announcement is presented once.",
      "evidence": "Shared feed shows recent announcements; public\\student-section.php:348-350 also renders the latest 100 in a table.",
      "foundBy": [
        "alt-text-headings"
      ]
    }
  ],
  "passed": [
    "ARIA references in the reviewed templates point to existing IDs.",
    "Content structure: profile details use a description list; read-only data tables have captions and column headers.",
    "Heading hierarchy: one page-specific H1, followed by sequential H2/H3 headings.",
    "Image alternatives: no img elements; SVGs and the brand mark are hidden as decorative.",
    "Landmarks: one main landmark, a skip link, and named navigation are present.",
    "Notice text meets WCAG 1.4.3 AA at 9.65:1.",
    "Page titles: descriptive titles are generated from each page’s title.",
    "Student navigation marks the active link with aria-current=\"page\".",
    "Student sidebar text and active-state colors meet WCAG 1.4.3 AA; checked text/background pairs range from 7.49:1 to 15.57:1.",
    "Student table scroll containers are keyboard-focusable, named regions with visible focus styling.",
    "Student table text and profile labels meet WCAG 1.4.3 AA; checked pairs range from 5.57:1 to 12.65:1.",
    "Table regions have accessible names; tables use captions and column headers with scope=\"col\".",
    "The responsive menu uses a button with aria-expanded, supports Escape to close, and returns focus to the toggle.",
    "The sidebar navigation, main content, page header, and footer use appropriate landmark semantics and names.",
    "The skip link precedes the dashboard content and targets a programmatically focusable main landmark.",
    "The student navigation uses links with valid section keys and marks the active page.",
    "The student sidebar focus ring contrasts with its dark background at 15.97:1; the active accent contrasts at 9.98:1.",
    "The student table focus outline contrasts with its light panel background at 14.4:1."
  ],
  "notes": [
    {
      "skill": "aria-specialist",
      "note": "The Menu button has aria-expanded=\"false\" and aria-controls references the sidebar; runtime state updates are outside the requested file scope."
    },
    {
      "skill": "keyboard-navigator",
      "note": "Automated keyboard scans could not run because Playwright is not installed; responsive behavior was reviewed statically."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 0,
    "minor": 2
  },
  "components": [],
  "score": 98,
  "grade": "A",
  "penalty": 2,
  "checks": 20,
  "generated": "2026-11-02",
  "template": "web"
}
```
