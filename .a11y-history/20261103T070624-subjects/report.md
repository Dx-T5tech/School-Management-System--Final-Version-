# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 3 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 16 |

Audited:

- `app/Shared/admin_section_content.php`
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

## What passed

16 checks ran and found nothing.

- All three subject filters have associated labels and the immediate-reload instruction.
- Catalog text and badges meet WCAG AA text contrast; the lowest measured combination is 5.09:1.
- Disclosure focus outlines exceed the 3:1 non-text contrast requirement.
- Disclosure summaries contain phrasing text and counts that provide meaningful accessible names.
- Disclosure summaries provide clear accessible names.
- Filter control borders exceed 3:1 contrast against their light fill.
- Headings are outside summary controls, remain within their details, and follow the catalog hierarchy.
- Native details/summary controls supply disclosure semantics and state without redundant ARIA.
- Native details/summary disclosures remain keyboard-operable and have visible focus styling.
- Status and requirement badges use readable text/background combinations and do not rely on color alone.
- Subject edit and deactivate actions include each subject's identity in their accessible names.
- The Apply filters button remains available when JavaScript or requestSubmit is unavailable.
- The automatic filter submission is explained in visible text associated with each filter.
- The program, year, semester, and subject heading levels remain coherent when disclosures are expanded.
- The subject add form uses the existing native dialog, a descriptive name, and first-field focus.
- Unassigned subjects have an h3 group heading followed by h4 subject headings.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: Integrated-browser checks confirmed automatic program, year, and semester filter submission and no horizontal overflow at 320, 768, 1024, and 1280 CSS pixels.
- **contrast-master**: The automated Playwright contrast scanner could not run because Playwright is not installed for the accessibility MCP server; specialist source review completed.

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
    "app/Shared/admin_section_content.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    "All three subject filters have associated labels and the immediate-reload instruction.",
    "Catalog text and badges meet WCAG AA text contrast; the lowest measured combination is 5.09:1.",
    "Disclosure focus outlines exceed the 3:1 non-text contrast requirement.",
    "Disclosure summaries contain phrasing text and counts that provide meaningful accessible names.",
    "Disclosure summaries provide clear accessible names.",
    "Filter control borders exceed 3:1 contrast against their light fill.",
    "Headings are outside summary controls, remain within their details, and follow the catalog hierarchy.",
    "Native details/summary controls supply disclosure semantics and state without redundant ARIA.",
    "Native details/summary disclosures remain keyboard-operable and have visible focus styling.",
    "Status and requirement badges use readable text/background combinations and do not rely on color alone.",
    "Subject edit and deactivate actions include each subject's identity in their accessible names.",
    "The Apply filters button remains available when JavaScript or requestSubmit is unavailable.",
    "The automatic filter submission is explained in visible text associated with each filter.",
    "The program, year, semester, and subject heading levels remain coherent when disclosures are expanded.",
    "The subject add form uses the existing native dialog, a descriptive name, and first-field focus.",
    "Unassigned subjects have an h3 group heading followed by h4 subject headings."
  ],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "Integrated-browser checks confirmed automatic program, year, and semester filter submission and no horizontal overflow at 320, 768, 1024, and 1280 CSS pixels."
    },
    {
      "skill": "contrast-master",
      "note": "The automated Playwright contrast scanner could not run because Playwright is not installed for the accessibility MCP server; specialist source review completed."
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
