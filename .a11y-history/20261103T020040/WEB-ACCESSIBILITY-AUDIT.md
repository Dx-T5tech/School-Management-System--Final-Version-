# Web Accessibility Audit

An audit of web content, run on 2026-11-02. Read the executive summary for the verdict, the priorities section for what to do first, and the findings tables for detail.

## Metadata

What was audited, with what, and under which settings.

| Field | Value |
|---|---|
| Date | 2026-11-02 |
| Scope | 3 items |
| Skills run | accessibility-lead |
| Tooling | node v24.14.1, a11y-mcp-server 7.1.0 |
| Scan configuration | defaults, no config file found |
| Checks counted | 10 |

Audited:

- `app/Shared/dashboard_layout.php`
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

6 checks ran and found nothing.

- A pagehide event cancels delayed navigation, and pageshow clears stale page-exit state.
- Collapsed navigation groups are hidden from layout and interaction when closed.
- Hash-current links expand their group and receive aria-current=location.
- Keyboard Space toggles a navigation group, updates aria-expanded, and returns focus to the toggle on close.
- Reduced-motion mode opens and closes groups without spatial animation or delayed hiding.
- The mobile menu control is exposed only after its handler is initialized; without JavaScript, the mobile sidebar remains visible as static navigation.

## Needs a human

Items a skill could not decide, or could not check automatically.

- **accessibility-lead**: Keyboard-navigator and aria-specialist agent launches failed before review because the runtime rejected the requested model. The accessibility lead completed a direct code review and targeted Playwright fixture checks instead.
- **accessibility-lead**: The authenticated dashboard could not be visually reviewed because opening the route redirected to sign-in.

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
    "accessibility-lead"
  ],
  "scope": [
    "app/Shared/dashboard_layout.php",
    "public/assets/css/dashboard.css",
    "public/assets/js/dashboard.js"
  ],
  "findings": [],
  "passed": [
    "A pagehide event cancels delayed navigation, and pageshow clears stale page-exit state.",
    "Collapsed navigation groups are hidden from layout and interaction when closed.",
    "Hash-current links expand their group and receive aria-current=location.",
    "Keyboard Space toggles a navigation group, updates aria-expanded, and returns focus to the toggle on close.",
    "Reduced-motion mode opens and closes groups without spatial animation or delayed hiding.",
    "The mobile menu control is exposed only after its handler is initialized; without JavaScript, the mobile sidebar remains visible as static navigation."
  ],
  "notes": [
    {
      "skill": "accessibility-lead",
      "note": "Keyboard-navigator and aria-specialist agent launches failed before review because the runtime rejected the requested model. The accessibility lead completed a direct code review and targeted Playwright fixture checks instead."
    },
    {
      "skill": "accessibility-lead",
      "note": "The authenticated dashboard could not be visually reviewed because opening the route redirected to sign-in."
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
