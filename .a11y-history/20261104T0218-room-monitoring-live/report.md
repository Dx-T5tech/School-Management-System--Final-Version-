# Web Accessibility Audit

An audit of web content, run on 2026-11-03. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-03 |
| Scope | 5 items |
| Skills run | aria-specialist, keyboard-navigator, live-region-controller |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `C:/xampp/htdocs/School Management System (Final Version)/app/Shared/admin_section_content.php`
- `C:/xampp/htdocs/School Management System (Final Version)/public/admin-section.php`
- `C:/xampp/htdocs/School Management System (Final Version)/public/assets/js/dashboard.js`
- `C:\xampp\htdocs\School Management System (Final Version)\app\Shared\admin_section_content.php`
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

9 checks ran and found nothing.

- Occupancy changes and schedule-detail-only changes use distinct announcement wording.
- Outages are announced and logged once per outage; recovery is explicitly announced, including alongside room updates.
- Revised live-status, resumed-update, and unavailable messages update the existing status element and do not alter keyboard navigation or control usability.
- Room controls and modal behavior remain unchanged.
- The 10-second polling updates room details and the timestamp in place without replacing controls or moving focus.
- The live status region announces actual room or schedule changes, outage, and recovery without announcing routine timestamp changes.
- The refresh cadence is qualified for page visibility and delayed responses.
- The timestamp remains visible and is updated with both its datetime value and text; it does not need to be in a live region.
- The timestamp remains visible outside the live region, avoiding redundant announcements at the 10-second polling cadence.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **aria-specialist**: Keeping the timestamp outside the live region is appropriate: WCAG does not require announcing every routine timestamp update, and doing so every 10 seconds could be disruptive.
- **live-region-controller**: As with snapshot polling generally, a transient room state that starts and ends between successful polls cannot be reported.

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
    "keyboard-navigator",
    "live-region-controller"
  ],
  "scope": [
    "C:/xampp/htdocs/School Management System (Final Version)/app/Shared/admin_section_content.php",
    "C:/xampp/htdocs/School Management System (Final Version)/public/admin-section.php",
    "C:/xampp/htdocs/School Management System (Final Version)/public/assets/js/dashboard.js",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\app\\Shared\\admin_section_content.php",
    "C:\\xampp\\htdocs\\School Management System (Final Version)\\public\\assets\\js\\dashboard.js"
  ],
  "findings": [],
  "passed": [
    "Occupancy changes and schedule-detail-only changes use distinct announcement wording.",
    "Outages are announced and logged once per outage; recovery is explicitly announced, including alongside room updates.",
    "Revised live-status, resumed-update, and unavailable messages update the existing status element and do not alter keyboard navigation or control usability.",
    "Room controls and modal behavior remain unchanged.",
    "The 10-second polling updates room details and the timestamp in place without replacing controls or moving focus.",
    "The live status region announces actual room or schedule changes, outage, and recovery without announcing routine timestamp changes.",
    "The refresh cadence is qualified for page visibility and delayed responses.",
    "The timestamp remains visible and is updated with both its datetime value and text; it does not need to be in a live region.",
    "The timestamp remains visible outside the live region, avoiding redundant announcements at the 10-second polling cadence."
  ],
  "notes": [
    {
      "skill": "aria-specialist",
      "note": "Keeping the timestamp outside the live region is appropriate: WCAG does not require announcing every routine timestamp update, and doing so every 10 seconds could be disruptive."
    },
    {
      "skill": "live-region-controller",
      "note": "As with snapshot polling generally, a transient room state that starts and ends between successful polls cannot be reported."
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
  "generated": "2026-11-03",
  "template": "web"
}
```
