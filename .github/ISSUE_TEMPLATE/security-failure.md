---
name: "Security Workflow Failure"
about: "Automated issue for security workflow failures requiring triage"
title: "Security Workflow Failure"
labels: ["security", "needs-review"]
assignees: []
---

## Security Workflow Failure

The security job failed. This alone does not establish a vulnerability or its severity.

**Failure stage:** `{{ env.FAILURE_STAGE }}`

**Failure Details:**

- **PHP Version:** {{ env.PHP_VERSION }}
- **Workflow Run:** [View Details]({{ env.WORKFLOW_URL }})
- **Run ID:** {{ env.RUN_ID }}

**What happened:**
Inspect whether dependency setup, the advisory checker, or the source-pattern
scan failed. Check reported advisories against the resolved package version and
review source-pattern matches in context before classifying a vulnerability.

**What needs to be done:**

1. Review the security check output in the failed workflow run
2. Separate setup errors, advisory matches and source-pattern findings
3. Confirm the affected version, reachable behavior and remediation scope
4. Propose any dependency change with its manifest/lock diff and validation plan
5. Re-run the affected checks after an approved fix

Keep suspected vulnerabilities and sensitive logs in the private reporting
channel described in [SECURITY.md](https://github.com/EngineScript/enginescript-site-optimizer/blob/main/SECURITY.md).

**Priority:** Triage the original failure first; assign vulnerability severity only when supported by evidence.

**Resources:**

- [Symfony Security Checker](https://github.com/FriendsOfPHP/security-advisories)
- [WordPress Security Best Practices](https://developer.wordpress.org/plugins/security/)

This issue was automatically created by the CI/CD pipeline.
