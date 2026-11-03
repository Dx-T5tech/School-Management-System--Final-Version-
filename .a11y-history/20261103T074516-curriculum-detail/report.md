# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 5 items |
| Skills run | alt-text-headings, aria-specialist, contrast-master, forms-specialist, keyboard-navigator, modal-specialist, tables-data-specialist |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 14 |

Audited:

- `C:/xampp/htdocs/School Management System (Final Version)/public/assets/js/dashboard.js`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Shared\admin_section_content.php`
- `C:\xampp\htdocs\School Management System (Final Version)\public\admin-section.php`
- `C:\xampp\htdocs\School Management System (Final Version)\public\assets\css\dashboard.css`
- `C:\xampp\htdocs\School Management System (Final Version)\public\assets\js\dashboard.js`

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

14 checks ran and found nothing.

- Column headers use th scope="col"; subject codes use th scope="row".
- Curriculum controls have associated labels, visible required indicators, and native required attributes.
- Curriculum overview and program detail headings follow the page H1 with sequential levels.
- Curriculum tables have descriptive captions identifying academic year, year level, and semester.
- Edit and Remove controls have names that identify the subject and curriculum action.
- ROUTE-001 verified fixed: returnProgramId falls back to validated GET curriculum_program_id/program_id, and the curriculum Cancel editing URL preserves both IDs.
- Tables are wrapped in keyboard-focusable, named horizontal-scroll containers; the CSS enables horizontal overflow and the minimum table width preserves columns.
- The curriculum scroll-region border #8a887e exceeds 3:1 contrast against nearby surfaces: 3.52:1 against #fffefa, 3.37:1 against #faf9f4, and 3.23:1 against #f5f4ef.
- The curriculum section has an accessible name; the shared layout provides main and navigation landmarks.
- The folder SVG icon is hidden from assistive technology; no meaningful images lack text alternatives.
- The page title is descriptive: Curriculum - ISMS.
- The selected program is populated in the add form on the curriculum detail route.
- Validation errors are visible, associated with affected controls, and marked with aria-invalid; error focus is handled.
- When validation errors open the dialog, the error summary is moved inside it and focused after showModal(), so it is announced.

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
    "modal-specialist",
    "tables-data-specialist"
  ],
  "scope": [
    "C:/xampp/htdocs/School Management System (Final Version)/public/assets/js/dashboard.js",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Shared\\admin_section_content.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\admin-section.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\css\\dashboard.css",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\js\\dashboard.js"
  ],
  "findings": [],
  "passed": [
    "Column headers use th scope=\"col\"; subject codes use th scope=\"row\".",
    "Curriculum controls have associated labels, visible required indicators, and native required attributes.",
    "Curriculum overview and program detail headings follow the page H1 with sequential levels.",
    "Curriculum tables have descriptive captions identifying academic year, year level, and semester.",
    "Edit and Remove controls have names that identify the subject and curriculum action.",
    "ROUTE-001 verified fixed: returnProgramId falls back to validated GET curriculum_program_id/program_id, and the curriculum Cancel editing URL preserves both IDs.",
    "Tables are wrapped in keyboard-focusable, named horizontal-scroll containers; the CSS enables horizontal overflow and the minimum table width preserves columns.",
    "The curriculum scroll-region border #8a887e exceeds 3:1 contrast against nearby surfaces: 3.52:1 against #fffefa, 3.37:1 against #faf9f4, and 3.23:1 against #f5f4ef.",
    "The curriculum section has an accessible name; the shared layout provides main and navigation landmarks.",
    "The folder SVG icon is hidden from assistive technology; no meaningful images lack text alternatives.",
    "The page title is descriptive: Curriculum - ISMS.",
    "The selected program is populated in the add form on the curriculum detail route.",
    "Validation errors are visible, associated with affected controls, and marked with aria-invalid; error focus is handled.",
    "When validation errors open the dialog, the error summary is moved inside it and focused after showModal(), so it is announced."
  ],
  "notes": [],
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
  "checks": 14,
  "generated": "2026-11-02",
  "template": "web"
}
```
