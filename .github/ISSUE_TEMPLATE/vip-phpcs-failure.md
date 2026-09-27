---
title: WordPress VIP Coding Standards Failure - PHP {{ env.PHP_VERSION }}
labels: ['vip-standards', 'coding-standards', 'needs-review', 'php-{{ env.PHP_VERSION }}']
assignees: []
---

## WordPress VIP Coding Standards Failure

The VIP coding-standards job failed. Inspect the failure stage and original check output.

**Failure stage:** `{{ env.FAILURE_STAGE }}`

### Details

- **PHP Version:** {{ env.PHP_VERSION }}
- **Run ID:** {{ env.RUN_ID }}
- **Workflow:** [View Failed Run]({{ env.WORKFLOW_URL }})
- **Standards Used:** Generated WordPress-VIP-Go ruleset

### Scope

The workflow generates a VIP ruleset for the six optimizer production PHP files.
Inspect that ruleset, its documented exclusions and the resolved VIPCS version
when diagnosing a finding. Passing this configured coding-standards scan does
not establish acceptance for a particular hosting environment.

### Next Steps

1. Resolve setup failures before attributing the failure to a coding rule
2. Record the specific rule, source location and exact-commit diagnostic artifact
3. Fix the demonstrated issue while retaining the mandatory VIP gate
4. Document the rationale and obtain review for any proposed rule exception
5. Re-run the preserved GitHub job and link its result

### Resources

- [VIP Coding Standards source](https://github.com/Automattic/VIP-Coding-Standards)
- [WordPress VIP documentation](https://docs.wpvip.com/)

This report describes the configured coding-standards job; it does not authorize
skipping checks based on a different deployment target.
