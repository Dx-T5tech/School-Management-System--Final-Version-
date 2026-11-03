# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 7 items |
| Skills run | aria-specialist, contrast-master, forms-specialist, keyboard-navigator, live-region-controller |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `C:\xampp\htdocs\School Management System (Final Version)\app\Modules\StudentManagement\Admissions\Controllers\apply.php`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Modules\StudentManagement\Admissions\Views\apply_page.php`
- `C:\xampp\htdocs\School Management System (Final Version)\public\assets\css\admissions.css`
- `C:\xampp\htdocs\School Management System (Final Version)\public\assets\js\admissions-exam.js`
- `app/Modules/StudentManagement/Admissions/Views/apply_page.php`
- `public/assets/css/admissions.css`
- `public/assets/js/admissions-exam.js`

## Executive summary

Pass. No critical or serious findings.

The headline numbers for this run.

| Measure | Value |
|---|---|
| Score | 97 out of 100 |
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
| `LIVE-REGION-001` | 4.1.3 AA | C:\xampp\htdocs\School Management System (Final Version)\public\assets\js\admissions-exam.js:30 | On QR failure, the code changes the image to a status role while inserting its message, so assistive technology may not announce the failure reliably. | Add an initially empty, persistent status element in the template and update its text on failure; keep the QR image separate rather than changing its role. |

## Remediation priorities

The remaining 1 moderate and minor findings can follow in normal work.

## What passed

4 checks ran and found nothing.

- Generate Exam is a native submit button with a visible focus style and a 48px minimum height.
- Quiz answers use native radio inputs with visible focus styling and labels providing a 42px-high clickable area.
- Static review found no keyboard-order or keyboard-operation issues introduced by the scoped changes.
- The exam code and QR are noninteractive and do not add unnecessary keyboard stops.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **live-region-controller**: After review, QR error output was moved to a persistent status live region and the QR image role remains unchanged. This fix was verified directly in source.

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
    "aria-specialist",
    "contrast-master",
    "forms-specialist",
    "keyboard-navigator",
    "live-region-controller"
  ],
  "scope": [
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Modules\\StudentManagement\\Admissions\\Controllers\\apply.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Modules\\StudentManagement\\Admissions\\Views\\apply_page.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\css\\admissions.css",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\js\\admissions-exam.js",
    "app/Modules/StudentManagement/Admissions/Views/apply_page.php",
    "public/assets/css/admissions.css",
    "public/assets/js/admissions-exam.js"
  ],
  "findings": [
    {
      "rule": "LIVE-REGION-001",
      "wcag": "4.1.3 AA",
      "severity": "moderate",
      "confidence": "medium",
      "location": "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\js\\admissions-exam.js:30",
      "summary": "On QR failure, the code changes the image to a status role while inserting its message, so assistive technology may not announce the failure reliably.",
      "fix": "Add an initially empty, persistent status element in the template and update its text on failure; keep the QR image separate rather than changing its role.",
      "evidence": "qrContainer.setAttribute(\"role\", \"status\"); qrContainer.textContent = \"QR code unavailable...\"",
      "foundBy": [
        "live-region-controller"
      ]
    }
  ],
  "passed": [
    "Generate Exam is a native submit button with a visible focus style and a 48px minimum height.",
    "Quiz answers use native radio inputs with visible focus styling and labels providing a 42px-high clickable area.",
    "Static review found no keyboard-order or keyboard-operation issues introduced by the scoped changes.",
    "The exam code and QR are noninteractive and do not add unnecessary keyboard stops."
  ],
  "notes": [
    {
      "skill": "live-region-controller",
      "note": "After review, QR error output was moved to a persistent status live region and the QR image role remains unchanged. This fix was verified directly in source."
    }
  ],
  "counts": {
    "critical": 0,
    "serious": 0,
    "moderate": 1,
    "minor": 0
  },
  "components": [],
  "score": 97,
  "grade": "A",
  "penalty": 1.4,
  "checks": 10,
  "generated": "2026-11-03",
  "template": "web"
}
```
