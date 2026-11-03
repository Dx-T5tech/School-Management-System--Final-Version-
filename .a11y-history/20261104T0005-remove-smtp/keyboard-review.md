# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 7 items |
| Skills run | keyboard-navigator |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `C:\xampp\htdocs\School Management System (Final Version)\app\Core\super_admin.php`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Modules\StudentManagement\Admissions\Controllers\apply.php`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Modules\StudentManagement\Admissions\Views\apply_page.php`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Shared\admin_teacher_lifecycle_content.php`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Shared\super_admin_section_content.php`
- `C:\xampp\htdocs\School Management System (Final Version)\public\admin-teacher-lifecycle.php`
- `C:\xampp\htdocs\School Management System (Final Version)\public\super-admin-section.php`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 96 out of 100 |
| Grade | A |
| Findings | 1 |
| Blocking (critical plus serious) | 0 |
| Verdict | Pass |

## Severity breakdown

Counts by severity, highest impact first.

| Severity | Count | What it means |
|---|---:|---|
| critical | 0 | Blocks a person from completing the task |
| serious | 0 | Major barrier with a difficult workaround |
| moderate | 1 | Real friction, workaround exists |
| minor | 0 | Noticeable but rarely blocking |

## Findings

1 findings, ordered by severity then by rule.

### Moderate (1)

Each row names the rule, the criterion it fails, where it is, and what to change.

| Rule | WCAG | Location | Problem | Fix |
|---|---|---|---|---|
| `KBD-001` | 2.1.1 A | C:\xampp\htdocs\School Management System (Final Version)\app\Shared\admin_teacher_lifecycle_content.php:57 | The setup URL is shown only in a focusable preformatted block, without a keyboard-operable way to copy just the link. | Add a semantic “Copy link” button that copies the URL and reports success or failure; provide a selectable fallback if clipboard access is unavailable. |

## Remediation priorities

The remaining 1 moderate and minor findings can follow in normal work.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **keyboard-navigator**: The reported preformatted-block issue was fixed after review by replacing it with a labeled, read-only textarea for native keyboard selection and copying. The change was PHP-linted; no specialist re-scan was run.

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
    "keyboard-navigator"
  ],
  "scope": [
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Core\\super_admin.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Modules\\StudentManagement\\Admissions\\Controllers\\apply.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Modules\\StudentManagement\\Admissions\\Views\\apply_page.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Shared\\admin_teacher_lifecycle_content.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Shared\\super_admin_section_content.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\admin-teacher-lifecycle.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\super-admin-section.php"
  ],
  "findings": [
    {
      "rule": "KBD-001",
      "wcag": "2.1.1 A",
      "severity": "moderate",
      "location": "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Shared\\admin_teacher_lifecycle_content.php:57",
      "summary": "The setup URL is shown only in a focusable preformatted block, without a keyboard-operable way to copy just the link.",
      "fix": "Add a semantic “Copy link” button that copies the URL and reports success or failure; provide a selectable fallback if clipboard access is unavailable.",
      "evidence": "<pre class=\"admin-code\" tabindex=\"0\" aria-label=\"One-time teacher setup link\">",
      "foundBy": [
        "keyboard-navigator"
      ]
    }
  ],
  "passed": [],
  "notes": [
    {
      "skill": "keyboard-navigator",
      "note": "The reported preformatted-block issue was fixed after review by replacing it with a labeled, read-only textarea for native keyboard selection and copying. The change was PHP-linted; no specialist re-scan was run."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 1,
    "minor": 0
  },
  "components": [],
  "score": 96,
  "grade": "A",
  "penalty": 2,
  "checks": 10,
  "generated": "2026-11-03",
  "template": "web"
}
```
