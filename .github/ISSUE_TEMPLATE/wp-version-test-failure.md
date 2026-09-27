---
name: "WordPress Version Compatibility Test Failure"
about: "Automated issue created when WordPress version compatibility tests fail"
title: "WordPress Compatibility Failure - WP {{ env.WP_VERSION }} / PHP {{ env.PHP_VERSION }} / {{ env.DEPENDENCY_VERSIONS }} / {{ env.FAILURE_STAGE }}"
labels: ["bug", "compatibility", "wordpress-version"]
assignees: []
---

## WordPress Version Compatibility Test Failed

The compatibility job failed. An installation or bootstrap failure is not proof of plugin incompatibility.

**Failure stage:** `{{ env.FAILURE_STAGE }}`

**Failure Details:**

- **PHP Version:** {{ env.PHP_VERSION }}
- **WordPress Version:** {{ env.WP_VERSION }}
- **Dependency Versions:** {{ env.DEPENDENCY_VERSIONS }}
- **Workflow Run:** [View Details]({{ env.WORKFLOW_URL }})
- **Run ID:** {{ env.RUN_ID }}

**What happened:**
Inspect the failed stage and its logs for WordPress {{ env.WP_VERSION }}, PHP {{ env.PHP_VERSION }}, and {{ env.DEPENDENCY_VERSIONS }} dependencies. Diagnose infrastructure separately from failed test assertions.

**What needs to be done:**

1. Review the test output in the failed workflow run
2. Identify whether the isolated unit suite, native bootstrap or native assertions failed
3. Inspect the recorded dependency versions; the unit suite runs before the native PHPUnit 9.6 pin
4. Verify both single-site and multisite results for the affected cell
5. Fix the demonstrated issue and re-run this GitHub matrix cell
6. Change compatibility metadata only when supported by successful exact-commit evidence and an authorized metadata update

**Potential Issues:**

- Deprecated WordPress functions
- Changed WordPress APIs
- PHP version incompatibilities with this WordPress version
- Plugin initialization problems

**Resources:**

- [WordPress Plugin Directory Paths](https://developer.wordpress.org/plugins/plugin-basics/determining-plugin-and-content-directories/)
- [WordPress Function Reference](https://developer.wordpress.org/reference/functions/)

This issue was automatically created by the CI/CD pipeline.
