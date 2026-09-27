---
name: "WordPress Dependency Report"
about: "Manual report for a WordPress dependency issue"
title: "WordPress Dependency Issue - PHP {{ env.PHP_VERSION }}"
labels: ["bug", "dependencies", "wordpress", "monitoring"]
assignees: []
---

## WordPress Dependency Report

This template is available for manual dependency reports. No current workflow
invokes it or defines a WordPress Dependencies Monitoring job. Replace the
placeholders when filing a report; their presence is not evidence of an automated
monitoring result.

### Evidence

- **PHP Version:** {{ env.PHP_VERSION }}
- **Workflow Run, if applicable:** [{{ env.RUN_ID }}]({{ env.WORKFLOW_URL }})
- **Date:** {{ date | date('YYYY-MM-DD') }}
- **Source commit and resolved package versions:** Add the observed values
- **Original error or advisory:** Add a concise, non-sensitive excerpt

### Next Steps

1. Identify whether the report concerns dependency installation, an advisory or a failing assertion
2. Link the actual job and diagnostics if the report came from GitHub Actions
3. Confirm the affected package/API and supported PHP/WordPress combination
4. Propose a scoped fix with any required manifest/lock change made explicit
5. Record the affected exact-commit checks after the fix

The existing compatibility matrix and security job provide separate evidence.
Do not infer plugin incompatibility or raise version minimums from a dependency
installation failure alone.
