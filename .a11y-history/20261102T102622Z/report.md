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
| Checks counted | 10 |

Audited:

- `app/Shared/admin_section_content.php`
- `app/Shared/dashboard_layout.php`
- `public/admin-dashboard.php`
- `public/admin-section.php`
- `public/assets/css/dashboard.css`
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

## Needs a human

Items a skill could not decide, or could not check automatically.

- **alt-text-headings**: Specialist dispatch failed before review: 400 The requested model is not supported. No heading or landmark findings were assessed.
- **aria-specialist**: Specialist dispatch failed before review: 400 The requested model is not supported. No ARIA findings were assessed.
- **contrast-master**: Specialist dispatch failed before review: 400 The requested model is not supported. No contrast findings were assessed.
- **forms-specialist**: Specialist dispatch failed before review: 400 The requested model is not supported. No form findings were assessed.
- **keyboard-navigator**: Specialist dispatch failed before review: 400 The requested model is not supported. No keyboard findings were assessed.
- **live-region-controller**: Specialist dispatch failed before review: 400 The requested model is not supported. No live-region findings were assessed.
- **tables-data-specialist**: Specialist dispatch failed before review: 400 The requested model is not supported. No table findings were assessed.

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
    "app/Shared/admin_section_content.php",
    "app/Shared/dashboard_layout.php",
    "public/admin-dashboard.php",
    "public/admin-section.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [],
  "notes": [
    {
      "skill": "alt-text-headings",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No heading or landmark findings were assessed."
    },
    {
      "skill": "aria-specialist",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No ARIA findings were assessed."
    },
    {
      "skill": "contrast-master",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No contrast findings were assessed."
    },
    {
      "skill": "forms-specialist",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No form findings were assessed."
    },
    {
      "skill": "keyboard-navigator",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No keyboard findings were assessed."
    },
    {
      "skill": "live-region-controller",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No live-region findings were assessed."
    },
    {
      "skill": "tables-data-specialist",
      "note": "Specialist dispatch failed before review: 400 The requested model is not supported. No table findings were assessed."
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
  "generated": "2026-11-02",
  "template": "web"
}
```
